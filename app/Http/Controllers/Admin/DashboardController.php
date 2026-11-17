<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        // Statistik umum
        $stats = [
            'customers' => DB::table('users')
                ->where('role', 'customer')
                ->count(),

            'couriers' => DB::table('users')
                ->where('role', 'courier')
                ->count(),

            'orders' => DB::table('orders')->count(),

            'machines' => DB::table('machines')->count(),

            'packages' => DB::table('packages')->count(),

            // Total nilai pesanan yang tidak dibatalkan
            'total_order_value' => DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->sum('total_price'),

            // Total pendapatan dari pesanan yang sudah lunas
            'paid_income' => DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', 'paid')
                ->sum('total_price'),

            // Total tagihan yang belum dibayar
            'unpaid_total' => DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', 'unpaid')
                ->sum('total_price'),

            // Jumlah pesanan yang sudah lunas
            'paid_orders' => DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', 'paid')
                ->count(),

            // Jumlah pesanan yang belum dibayar
            'unpaid_orders' => DB::table('orders')
                ->where('status', '!=', 'cancelled')
                ->where('payment_status', 'unpaid')
                ->count(),
        ];

        // 5 pesanan terbaru beserta nama pelanggan
        $latestOrders = DB::table('orders')
            ->join('users', 'orders.customer_id', '=', 'users.id')
            ->select(
                'orders.*',
                'users.name as nama_pelanggan'
            )
            ->orderByDesc('orders.created_at')
            ->limit(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'latestOrders'
        ));
    }

    // Menampilkan laporan pendapatan berdasarkan periode tanggal
public function laporanPendapatan(Request $request)
{
    $totalPendapatan = null;
    $tanggalAwal = null;
    $tanggalAkhir = null;

    // Jika tanggal sudah diisi, validasi dan panggil Stored Procedure
    if ($request->filled('tanggal_awal') || $request->filled('tanggal_akhir')) {

        $validated = $request->validate([
            'tanggal_awal' => 'required|date',
            'tanggal_akhir' => 'required|date|after_or_equal:tanggal_awal',
        ]);

        $tanggalAwal = $validated['tanggal_awal'];
        $tanggalAkhir = $validated['tanggal_akhir'];

        DB::statement('SET @total_pendapatan = 0');

        DB::statement(
            'CALL sp_laporan_pendapatan(?, ?, @total_pendapatan)',
            [$tanggalAwal, $tanggalAkhir]
        );

        $hasil = DB::selectOne(
            'SELECT @total_pendapatan AS total_pendapatan'
        );

        $totalPendapatan = $hasil->total_pendapatan ?? 0;
    }

    return view('admin.laporan-pendapatan', compact(
        'totalPendapatan',
        'tanggalAwal',
        'tanggalAkhir'
    ));
}
}


// namespace App\Http\Controllers\Admin;

// use App\Http\Controllers\Controller;
// use Illuminate\Support\Facades\DB;

// class DashboardController extends Controller
// {
//     public function index()
//     {
//         // Statistik dari database MySQL
//         $stats = [
//             'customers' => DB::table('users')
//                 ->where('role', 'customer')
//                 ->count(),

//             'couriers' => DB::table('users')
//                 ->where('role', 'courier')
//                 ->count(),

//             'orders' => DB::table('orders')->count(),

//             'machines' => DB::table('machines')->count(),

//             'packages' => DB::table('packages')->count(),

//             'total_order_value' => DB::table('orders')
//                 ->sum('total_price'),
//         ];

//         // 5 pesanan terbaru
//         $latestOrders = DB::table('orders')
//             ->orderByDesc('order_date')
//             ->limit(5)
//             ->get();

//         return view('admin.dashboard', compact(
//             'stats',
//             'latestOrders'
//         ));
//     }
// }


