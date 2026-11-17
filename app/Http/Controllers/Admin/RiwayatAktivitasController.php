<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;

class RiwayatAktivitasController extends Controller
{
    public function index()
    {
        $riwayat = DB::table('log_aktivitas_pesanan')
            ->orderBy('waktu_aktivitas', 'desc')
            ->orderBy('id', 'desc')
            ->get();

        return view('admin.riwayat-aktivitas.index', compact(
            'riwayat'
        ));
    }
}

