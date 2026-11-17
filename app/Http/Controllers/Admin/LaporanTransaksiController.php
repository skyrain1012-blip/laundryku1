<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanTransaksiController extends Controller
{
    public function index(Request $request)
    {
        // MULTI JOIN: Menggabungkan 4 tabel
        $laporan = DB::select("
            SELECT
                orders.id,
                orders.order_code,
                orders.order_date,
                orders.total_price,
                orders.status,
                orders.payment_status,
                users.name AS nama_pelanggan,
                packages.name AS nama_paket,
                order_details.quantity AS berat,
                order_details.subtotal
            FROM orders
            JOIN users
                ON orders.customer_id = users.id
            JOIN order_details
                ON orders.id = order_details.order_id
            JOIN packages
                ON order_details.package_id = packages.id
            WHERE users.role = ?
            ORDER BY orders.order_date DESC,
                     orders.id DESC
        ", ['customer']);

                // FILTER MULTI JOIN 2:
        // Hanya menampilkan transaksi selesai dan sudah lunas
        $filterSelesaiLunas = $request->query('filter') === 'selesai-lunas';

        if ($filterSelesaiLunas) {
            $laporan = DB::select("
                SELECT
                    orders.id,
                    orders.order_code,
                    orders.order_date,
                    orders.total_price,
                    orders.status,
                    orders.payment_status,
                    users.name AS nama_pelanggan,
                    packages.name AS nama_paket,
                    order_details.quantity AS berat,
                    order_details.subtotal
                FROM orders
                JOIN users
                    ON orders.customer_id = users.id
                JOIN order_details
                    ON orders.id = order_details.order_id
                JOIN packages
                    ON order_details.package_id = packages.id
                WHERE users.role = ?
                    AND orders.status = ?
                    AND orders.payment_status = ?
                ORDER BY orders.order_date DESC,
                         orders.id DESC
            ", ['customer', 'completed', 'paid']);
        }


        // Menghitung transaksi lunas yang tidak dibatalkan
        $jumlahLunas = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'paid')
            ->count();


        // Menghitung transaksi belum lunas yang tidak dibatalkan
        $jumlahBelumLunas = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'unpaid')
            ->count();


        // Total transaksi aktif = transaksi lunas + belum lunas
        $totalTransaksiAktif = $jumlahLunas + $jumlahBelumLunas;


        // Menghitung total pendapatan yang benar-benar sudah diterima
        // Hanya transaksi paid dan tidak dibatalkan
        $totalPendapatan = DB::table('orders')
            ->where('status', '!=', 'cancelled')
            ->where('payment_status', 'paid')
            ->sum('total_price');


        // SUBQUERY: Mencari paket paling banyak dipesan
        $paketTerlaris = DB::selectOne("
            SELECT
                packages.name AS nama_paket,
                (
                    SELECT COUNT(DISTINCT order_details.order_id)
                    FROM order_details
                    JOIN orders
                        ON order_details.order_id = orders.id
                    JOIN users
                        ON orders.customer_id = users.id
                    WHERE order_details.package_id = packages.id
                    AND users.role = ?
                    AND orders.status != ?
                ) AS total_pesanan
            FROM packages
            HAVING total_pesanan > 0
            ORDER BY total_pesanan DESC
            LIMIT 1
        ", ['customer', 'cancelled']);


        // Menghitung jumlah pesanan untuk setiap paket
        $rincianPaket = DB::select("
            SELECT
                packages.id,
                packages.name AS nama_paket,
                COUNT(DISTINCT CASE
                    WHEN users.id IS NOT NULL THEN orders.id
                END) AS total_pesanan
            FROM packages
            LEFT JOIN order_details
                ON packages.id = order_details.package_id
            LEFT JOIN orders
                ON order_details.order_id = orders.id
                AND orders.status != ?
            LEFT JOIN users
                ON orders.customer_id = users.id
                AND users.role = ?
            GROUP BY packages.id, packages.name
            ORDER BY total_pesanan DESC
        ", ['cancelled', 'customer']);


        return view('admin.laporan-transaksi.index', compact(
            'laporan',
            'paketTerlaris',
            'rincianPaket',
            'filterSelesaiLunas'
        ));
    }
   

// HALAMAN GRAFIK LAUNDRYKU
public function grafikLaundryKu(Request $request)
{
    // Total pendapatan sepanjang waktu
    $totalPendapatan = DB::table('orders')
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->whereNotNull('paid_at')
        ->sum('total_price');

    // Total pesanan sepanjang waktu
    $totalPesanan = DB::table('orders')
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->whereNotNull('paid_at')
        ->count();

    // Mengambil pilihan periode dari URL
    $periodePilihan = $request->query('periode', '6');

    // Menentukan rentang tanggal grafik
    $akhir = Carbon::now()->endOfMonth();

    if ($periodePilihan === 'all') {

        // Cari bulan pertama pesanan selesai dan dibayar
        $transaksiPertama = DB::table('orders')
            ->where('status', 'completed')
            ->where('payment_status', 'paid')
            ->whereNotNull('paid_at')
            ->min('paid_at');

        $awal = $transaksiPertama
            ? Carbon::parse($transaksiPertama)->startOfMonth()
            : Carbon::now()->startOfMonth();

    } else {

        // Pilihan periode dalam bulan
        $jumlahBulan = match ($periodePilihan) {
            '12' => 12,
            '36' => 36,
            '60' => 60,
            default => 6,
        };

        $periodePilihan = (string) $jumlahBulan;

        $awal = Carbon::now()
            ->startOfMonth()
            ->subMonths($jumlahBulan - 1);
    }

    // Mengambil data pendapatan dan jumlah pesanan per bulan
    $dataGrafik = DB::table('orders')
        ->selectRaw("
            DATE_FORMAT(paid_at, '%Y-%m') AS periode,
            SUM(total_price) AS pendapatan,
            COUNT(*) AS jumlah_pesanan
        ")
        ->where('status', 'completed')
        ->where('payment_status', 'paid')
        ->whereNotNull('paid_at')
        ->whereBetween('paid_at', [$awal, $akhir])
        ->groupByRaw("DATE_FORMAT(paid_at, '%Y-%m')")
        ->orderBy('periode')
        ->get()
        ->keyBy('periode');

    // Menyiapkan label dan data grafik
    $labelGrafik = [];
    $dataPendapatan = [];
    $dataPesanan = [];

    $tanggal = $awal->copy();

    while ($tanggal->lte($akhir)) {
        $periode = $tanggal->format('Y-m');

        $labelGrafik[] = $tanggal->format('m/Y');

        $dataPendapatan[] = (float) (
            $dataGrafik[$periode]->pendapatan ?? 0
        );

        $dataPesanan[] = (int) (
            $dataGrafik[$periode]->jumlah_pesanan ?? 0
        );

        $tanggal->addMonth();
    }

    return view('admin.laporan-transaksi.grafik', compact(
        'totalPendapatan',
        'totalPesanan',
        'labelGrafik',
        'dataPendapatan',
        'dataPesanan',
        'periodePilihan'
    ));
}
}
