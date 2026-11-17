<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PesananController extends Controller
{
    // Menampilkan daftar pesanan
    // Menampilkan daftar pesanan dengan filter dan pencarian
public function index(Request $request)
{
    $query = DB::table('orders')
        ->join('users', 'orders.customer_id', '=', 'users.id')
        ->select('orders.*', 'users.name as nama_pelanggan');

    // Pencarian kode pesanan, nama pelanggan, atau email
    if ($request->filled('search')) {
        $search = $request->search;

        $query->where(function ($q) use ($search) {
            $q->where('orders.order_code', 'like', "%{$search}%")
                ->orWhere('users.name', 'like', "%{$search}%")
                ->orWhere('users.email', 'like', "%{$search}%");
        });
    }

    // Filter status pesanan
    if ($request->filled('status')) {
        $query->where('orders.status', $request->status);
    }

    // Filter status pembayaran
    if ($request->filled('payment_status')) {
        $query->where('orders.payment_status', $request->payment_status);
    }

    // Filter tanggal pesanan
    if ($request->filled('order_date')) {
        $query->whereDate('orders.order_date', $request->order_date);
    }

    $pesanan = $query
        ->orderByDesc('orders.created_at')
        ->paginate(10)
        ->withQueryString();

    return view('admin.pesanan.index', compact('pesanan'));
}

    // Menampilkan form tambah pesanan
    public function create()
    {
        $pelanggan = DB::table('users')
            ->where('role', 'customer')
            ->orderBy('name')
            ->get();

        $paket = DB::table('packages')
            ->orderBy('name')
            ->get();

        return view('admin.pesanan.create', compact('pelanggan', 'paket'));
    }

    // Membatalkan pesanan
public function cancel(string $id)
{
    $pesanan = DB::table('orders')
        ->where('id', $id)
        ->first();

    abort_if(!$pesanan, 404);

    if ($pesanan->status === 'cancelled') {
        return back()->with('error', 'Pesanan sudah dibatalkan.');
    }

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => 'cancelled',
            'updated_at' => now(),
        ]);

        // Mengalokasikan mesin untuk pesanan yang masih mengantre
        $this->alokasikanPesananMenungguMesin();

    return redirect()
        ->route('admin.pesanan.index')
        ->with('success', 'Pesanan berhasil dibatalkan.');
    }

    // Menampilkan detail pesanan

// Menampilkan detail pesanan
public function show(string $id)
{
    $pesanan = DB::table('orders')
        ->join('users', 'orders.customer_id', '=', 'users.id')
        ->where('orders.id', $id)
        ->select(
            'orders.*',
            'users.name as nama_pelanggan',
            'users.email as email_pelanggan',
            'users.phone as telepon_pelanggan',
            'users.address as alamat_pelanggan'
        )
        ->first();

    abort_if(!$pesanan, 404);

    $detail = DB::table('order_details')
        ->join('packages', 'order_details.package_id', '=', 'packages.id')
        ->where('order_details.order_id', $id)
        ->select(
            'order_details.*',
            'packages.name as nama_paket'
        )
        ->get();

    // Menghitung subtotal seluruh layanan
    $subtotalLaundry = $detail->sum('subtotal');

    // Memanggil Stored Function kategori member
    $hasilKategori = DB::selectOne(
        'SELECT fn_cek_kategori_member(?) AS kategori',
        [$pesanan->customer_id]
    );

    $kategoriMember = $hasilKategori->kategori;

    // Memanggil Stored Function untuk menghitung diskon
    $hasilDiskon = DB::selectOne(
        'SELECT fn_hitung_diskon(?, ?) AS nominal_diskon',
        [$pesanan->customer_id, $subtotalLaundry]
    );

    $diskon = $hasilDiskon->nominal_diskon ?? 0;

    return view(
        'admin.pesanan.show',
        compact('pesanan', 'detail', 'kategoriMember', 'diskon')
    );
}


    // Menyimpan pesanan baru
    

public function store(Request $request)
{
    $validated = $request->validate([
        'customer_id' => [
            'required',
            Rule::exists('users', 'id')->where('role', 'customer'),
        ],
        'package_id' => 'required|exists:packages,id',
        'quantity' => 'required|integer|min:1',
        'order_date' => 'required|date|before_or_equal:today',
    ], [
        'order_date.before_or_equal' => 'Tanggal pesanan tidak boleh melebihi tanggal hari ini.',
    ]);

    // Mengambil harga dan nama paket
    $paket = DB::table('packages')
        ->where('id', $validated['package_id'])
        ->first();

    $harga = $paket->price;
    $berat = $validated['quantity'];
    $subtotal = $harga * $berat;

    // Mengecek apakah menggunakan paket antar-jemput
    $pakaiAntarJemput = $paket->name === 'Cuci Setrika Antar' ? 1 : 0;

    DB::transaction(function () use (
        $validated,
        $harga,
        $berat,
        $subtotal,
        $pakaiAntarJemput
    ) {

        // Memanggil Stored Function kategori member
        $hasilKategori = DB::selectOne(
            'SELECT fn_cek_kategori_member(?) AS kategori',
            [$validated['customer_id']]
        );

        $kategoriMember = $hasilKategori->kategori;

        // Memanggil Stored Function untuk menghitung diskon
        $hasilDiskon = DB::selectOne(
            'SELECT fn_hitung_diskon(?, ?) AS nominal_diskon',
            [$validated['customer_id'], $subtotal]
        );

        $diskon = $hasilDiskon->nominal_diskon ?? 0;

        // Mengurangi subtotal dengan diskon
        $subtotalSetelahDiskon = $subtotal - $diskon;

        // Menghitung ongkir menggunakan Stored Procedure INOUT
        DB::statement(
            'SET @total_biaya = ?',
            [$subtotalSetelahDiskon]
        );

        DB::statement(
            'CALL sp_hitung_pesanan_dengan_ongkir(?, ?, @total_biaya)',
            [$berat, $pakaiAntarJemput]
        );

        $hasil = DB::selectOne(
            'SELECT @total_biaya AS total_akhir'
        );

        $totalAkhir = $hasil->total_akhir ?? $subtotalSetelahDiskon;

        // Membuat kode pesanan otomatis
        do {
            $kodePesanan = 'ORD-' . now()->format('Ymd') . '-' . strtoupper(Str::random(5));
        } while (
            DB::table('orders')
                ->where('order_code', $kodePesanan)
                ->exists()
        );

        // Menyimpan data pesanan
        $orderId = DB::table('orders')->insertGetId([
            'order_code' => $kodePesanan,
            'customer_id' => $validated['customer_id'],
            'machine_id' => null,
            'order_date' => $validated['order_date'],
            'total_price' => $totalAkhir,
            'status' => 'pending',
            'payment_status' => 'unpaid',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Menyimpan detail pesanan dengan subtotal sebelum diskon
        DB::table('order_details')->insert([
            'order_id' => $orderId,
            'package_id' => $validated['package_id'],
            'quantity' => $berat,
            'price' => $harga,
            'subtotal' => $subtotal,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    });

    return redirect()
        ->route('admin.pesanan.index')
        ->with('success', 'Pesanan berhasil ditambahkan!');
}


    // Mengubah status pesanan

// Mengubah status pembayaran
public function updatePayment(Request $request, string $id)
{
    $validated = $request->validate([
        'payment_status' => 'required|in:unpaid,paid',
    ]);

    $pesanan = DB::table('orders')
        ->where('id', $id)
        ->first();

    abort_if(!$pesanan, 404);

    if ($pesanan->status === 'cancelled') {
        return back()->with(
            'error',
            'Pesanan yang dibatalkan tidak dapat diproses pembayarannya.'
        );
    }

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'payment_status' => $validated['payment_status'],
            'paid_at' => $validated['payment_status'] === 'paid'
                ? ($pesanan->paid_at ?? now())
                : null,
            'updated_at' => now(),
        ]);

    return back()->with(
        'success',
        'Status pembayaran berhasil diperbarui.'
    );
}

// Mengubah status pesanan
public function updateStatus(Request $request, string $id)
{


    $validated = $request->validate([
        'status' => 'required|in:pending,confirmed,processing,ready,completed,cancelled',
    ]);

    $pesanan = DB::table('orders')
        ->where('id', $id)
        ->first();

    abort_if(!$pesanan, 404);

    if (
    in_array($validated['status'], ['processing', 'ready', 'completed'])
    && $pesanan->machine_id === null
) {
    return back()->with(
        'error',
        'Pesanan belum mendapatkan mesin. Silakan tunggu mesin tersedia.'
    );
}

    if ($pesanan->status === 'cancelled') {
        return back()->with('error', 'Pesanan yang sudah dibatalkan tidak dapat diubah lagi.');
    }

    // Cek apakah pakaian sudah ditimbang
    if (
        in_array($validated['status'], ['processing', 'ready', 'completed'])
        && $pesanan->total_price <= 0
    ) {
        return back()->with(
            'error',
            'Silakan timbang pakaian dan tentukan harga terlebih dahulu.'
        );
    }

        // Pesanan wajib lunas sebelum diselesaikan
    if (
        $validated['status'] === 'completed' &&
        $pesanan->payment_status !== 'paid'
    ) {
        return back()->with(
            'error',
            'Pesanan belum dapat diselesaikan karena pembayaran belum lunas.'
        );
    }

    // Cek apakah pesanan menggunakan paket Cuci Setrika Antar
    $paketAntar = DB::table('order_details')
        ->join('packages', 'order_details.package_id', '=', 'packages.id')
        ->where('order_details.order_id', $id)
        ->where('packages.name', 'Cuci Setrika Antar')
        ->exists();

    // Paket antar wajib lunas sebelum diproses
    if (
        $paketAntar &&
        in_array($validated['status'], ['processing', 'ready', 'completed']) &&
        $pesanan->payment_status !== 'paid'
    ) {
        return back()->with(
            'error',
            'Pesanan Cuci Setrika Antar wajib dibayar terlebih dahulu sebelum pakaian diproses.'
        );
    }

    DB::table('orders')
        ->where('id', $id)
        ->update([
            'status' => $validated['status'],
            'updated_at' => now(),
        ]);

       // Jalankan alokasi antrean ketika pesanan dikonfirmasi
        // atau ketika mesin dibebaskan.
        if (in_array($validated['status'], [
            'confirmed',
            'ready',
            'completed',
            'cancelled'
        ])) {
            $this->alokasikanPesananMenungguMesin();
        }


    return back()->with('success', 'Status pesanan berhasil diperbarui.');
}


// Menyimpan hasil penimbangan pakaian oleh Admin
// Menyimpan hasil penimbangan dan memilih mesin otomatis


public function updateWeighing(Request $request, string $id)
{
    $validated = $request->validate([
        'quantity' => 'required|integer|min:1|max:15',
    ]);

    $pesanan = DB::table('orders')
        ->where('id', $id)
        ->first();

    abort_if(!$pesanan, 404);

    if ($pesanan->status === 'cancelled') {
        return back()->with('error', 'Pesanan yang dibatalkan tidak dapat ditimbang.');
    }

    if ($pesanan->status !== 'confirmed') {
        return back()->with('error', 'Pesanan harus dikonfirmasi sebelum ditimbang.');
    }

    if ($pesanan->payment_status === 'paid') {
        return back()->with('error', 'Berat tidak dapat diubah karena pesanan sudah dibayar.');
    }

    $detail = DB::table('order_details')
        ->join('packages', 'order_details.package_id', '=', 'packages.id')
        ->where('order_details.order_id', $id)
        ->select(
            'order_details.id',
            'packages.price as harga_paket',
            'packages.name as nama_paket'
        )
        ->first();

    if (!$detail) {
        return back()->with('error', 'Detail paket pesanan tidak ditemukan.');
    }

    $berat = $validated['quantity'];
    $harga = $detail->harga_paket;
    $subtotal = $berat * $harga;

    $pakaiAntarJemput = $detail->nama_paket === 'Cuci Setrika Antar' ? 1 : 0;

    // Memeriksa apakah pesanan ini memang sudah mengantre sebelumnya.
    $pesananSedangMengantre =
        $pesanan->machine_id === null &&
        (float) $pesanan->total_price > 0;

    $hasilPenimbangan = DB::transaction(function () use (
        $id,
        $detail,
        $berat,
        $harga,
        $subtotal,
        $pakaiAntarJemput,
        $pesanan,
        $pesananSedangMengantre
    ) {

        // Menghitung kategori member.
        $hasilKategori = DB::selectOne(
            'SELECT fn_cek_kategori_member(?) AS kategori',
            [$pesanan->customer_id]
        );

        $kategoriMember = $hasilKategori->kategori;

        // Menghitung diskon.
        $hasilDiskon = DB::selectOne(
            'SELECT fn_hitung_diskon(?, ?) AS nominal_diskon',
            [$pesanan->customer_id, $subtotal]
        );

        $diskon = $hasilDiskon->nominal_diskon ?? 0;

        $subtotalSetelahDiskon = $subtotal - $diskon;

        // Menghitung ongkir menggunakan Stored Procedure INOUT.
        DB::statement(
            'SET @total_biaya = ?',
            [$subtotalSetelahDiskon]
        );

        DB::statement(
            'CALL sp_hitung_pesanan_dengan_ongkir(?, ?, @total_biaya)',
            [$berat, $pakaiAntarJemput]
        );

        $hasil = DB::selectOne(
            'SELECT @total_biaya AS total_akhir'
        );

        $totalAkhir = $hasil->total_akhir ?? $subtotalSetelahDiskon;

        // Memperbarui berat dan subtotal pesanan.
        DB::table('order_details')
            ->where('id', $detail->id)
            ->update([
                'quantity' => $berat,
                'price' => $harga,
                'subtotal' => $subtotal,
                'updated_at' => now(),
            ]);

        if ($pesananSedangMengantre) {

            // PESANAN LAMA YANG SUDAH MENGANTRE:
            // Simpan harga terbaru tanpa mengubah created_at
            // dan tanpa mengubah posisi antrean.
            DB::table('orders')
                ->where('id', $id)
                ->update([
                    'total_price' => $totalAkhir,
                    'updated_at' => now(),
                ]);

            // Proses seluruh antrean berdasarkan FIFO,
            // termasuk pesanan ini dengan berat terbarunya.
            $this->alokasikanPesananMenungguMesin();

            // Memeriksa apakah pesanan sudah mendapat mesin.
            $mesinTerpilih = DB::table('orders')
                ->where('id', $id)
                ->value('machine_id');

        } else {

            // PESANAN BARU:
            // Dahulukan antrean lama sebelum mencari mesin
            // untuk pesanan yang sedang ditimbang.
            $this->alokasikanPesananMenungguMesin();

            // Mengambil kondisi mesin terbaru.
            $daftarMesin = DB::table('machines')
                ->orderBy('id')
                ->lockForUpdate()
                ->get();

            $mesinTerpilih = null;

            foreach ($daftarMesin as $mesin) {

                if ($mesin->status === 'maintenance') {
                    continue;
                }

                $totalBerat = DB::table('orders')
                    ->join(
                        'order_details',
                        'orders.id',
                        '=',
                        'order_details.order_id'
                    )
                    ->where('orders.machine_id', $mesin->id)
                    ->whereIn('orders.status', ['confirmed', 'processing'])
                    ->where('orders.id', '!=', $id)
                    ->sum('order_details.quantity');

                if ($totalBerat + $berat <= 15) {
                    $mesinTerpilih = $mesin->id;
                    break;
                }
            }

            // Menyimpan mesin dan total harga pesanan baru.
            DB::table('orders')
                ->where('id', $id)
                ->update([
                    'machine_id' => $mesinTerpilih,
                    'total_price' => $totalAkhir,
                    'updated_at' => now(),
                ]);
        }

        return [
            'mesin' => $mesinTerpilih,
            'total_akhir' => $totalAkhir,
        ];
    });

    $mesinTerpilih = $hasilPenimbangan['mesin'];
    $totalAkhir = $hasilPenimbangan['total_akhir'];

    if ($mesinTerpilih === null) {
        return back()->with(
            'success',
            'Penimbangan berhasil disimpan, tetapi belum ada mesin dengan kapasitas yang cukup. Pesanan menunggu mesin tersedia. Total tagihan: Rp' .
            number_format($totalAkhir, 0, ',', '.')
        );
    }

    return back()->with(
        'success',
        'Penimbangan berhasil disimpan dan dialokasikan ke Mesin Cuci & Pengering ' .
        $mesinTerpilih .
        '. Total tagihan: Rp' .
        number_format($totalAkhir, 0, ',', '.')
    );
}


// Mengalokasikan mesin untuk pesanan lama yang masih menunggu

private function alokasikanPesananMenungguMesin()
{
    DB::transaction(function () {

        // Mengambil seluruh pesanan yang sudah mengantre.
        $pesananMenunggu = DB::table('orders')
            ->where('status', 'confirmed')
            ->whereNull('machine_id')
            ->where('total_price', '>', 0)
            ->orderBy('created_at')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        // Mengambil kondisi mesin saat proses alokasi dimulai.
        $daftarMesin = DB::table('machines')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        foreach ($pesananMenunggu as $pesanan) {

            $beratPesanan = DB::table('order_details')
                ->where('order_id', $pesanan->id)
                ->sum('quantity');

            if ($beratPesanan <= 0) {
                continue;
            }

            foreach ($daftarMesin as $mesin) {

                // Antrean hanya boleh masuk ke mesin
                // yang berstatus available saat alokasi dimulai.
                if ($mesin->status !== 'available') {
                    continue;
                }

                $totalBerat = DB::table('orders')
                    ->join(
                        'order_details',
                        'orders.id',
                        '=',
                        'order_details.order_id'
                    )
                    ->where('orders.machine_id', $mesin->id)
                    ->whereIn('orders.status', [
                        'confirmed',
                        'processing'
                    ])
                    ->sum('order_details.quantity');

                if ($totalBerat + $beratPesanan <= 15) {

                    DB::table('orders')
                        ->where('id', $pesanan->id)
                        ->update([
                            'machine_id' => $mesin->id,
                            'updated_at' => now(),
                        ]);

                    break;
                }
            }
        }
    });
}


public function destroy($id)
{
    // Pastikan pesanan tersedia
    $pesanan = DB::table('orders')
        ->where('id', $id)
        ->first();

    if (!$pesanan) {
        return redirect()
            ->route('admin.pesanan.index')
            ->with('error', 'Pesanan tidak ditemukan.');
    }

    // Pastikan pesanan sudah dibatalkan
    if ($pesanan->status !== 'cancelled') {
        return redirect()
            ->route('admin.pesanan.index')
            ->with('error', 'Pesanan harus dibatalkan terlebih dahulu sebelum dihapus.');
    }

    // Hapus pesanan dalam satu transaksi
    DB::transaction(function () use ($id) {
        DB::table('orders')
            ->where('id', $id)
            ->delete();
    });

    return redirect()
        ->route('admin.pesanan.index')
        ->with('success', 'Pesanan berhasil dihapus.');
}


}