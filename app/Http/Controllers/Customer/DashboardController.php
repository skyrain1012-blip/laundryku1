<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index()
    {
        $customerId = Auth::id();

        // Ringkasan pesanan pelanggan
        $ringkasan = DB::table('orders')
            ->where('customer_id', $customerId)
            ->selectRaw("
                COUNT(*) as total_pesanan,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as menunggu,
                SUM(CASE WHEN status = 'processing' THEN 1 ELSE 0 END) as diproses,
                SUM(CASE WHEN status = 'ready' THEN 1 ELSE 0 END) as siap,
                SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as selesai
            ")
            ->first();

        // Pesanan terbaru
        $pesananTerbaru = DB::table('orders')
            ->where('customer_id', $customerId)
            ->orderByDesc('created_at')
            ->limit(5)
            ->get();

        // Kategori member menggunakan Stored Function
        $hasilMember = DB::selectOne(
            'SELECT fn_cek_kategori_member(?) AS kategori_member',
            [$customerId]
        );

        $kategoriMember = $hasilMember->kategori_member;

        return view('customer.dashboard', compact(
            'ringkasan',
            'pesananTerbaru',
            'kategoriMember'
        ));
    }
}
