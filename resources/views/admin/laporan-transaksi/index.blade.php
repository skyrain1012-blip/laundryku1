@extends('layouts.app')

@section('title', "Laporan Transaksi - Laundryku")
@section('page-title', 'Laporan Transaksi')

@section('content')

<div class="container-fluid laporan-transaksi">

    {{-- Header Halaman --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Laporan Transaksi</h3>
        <p class="text-muted mb-0">
            Analisis, pemantauan, dan riwayat transaksi Laundryku.
        </p>
    </div>


{{-- Dashboard Fitur Laporan --}}
<div class="row g-3 mb-4">

    {{-- 1. Paket Terlaris --}}
    <div class="col-md-6">
        <a href="#"
           data-bs-toggle="modal"
           data-bs-target="#modalRincianPaket"
           class="text-decoration-none text-dark">

            <div class="card border-0 shadow-sm h-100 summary-card dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-warning bg-opacity-10 p-3 me-3 summary-icon">
                            <i class="bi bi-trophy fs-3 text-warning"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Paket Terlaris</h6>
                            <p class="text-muted small mb-1">
                                @if ($paketTerlaris)
                                    {{ $paketTerlaris->nama_paket }}
                                @else
                                    Belum Ada Pesanan
                                @endif
                            </p>
                            <small class="text-primary">Lihat rincian →</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- 2. Riwayat Aktivitas --}}
    <div class="col-md-6">
        <a href="{{ route('admin.riwayat-aktivitas.index') }}"
           class="text-decoration-none text-dark">

            <div class="card border-0 shadow-sm h-100 summary-card dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-info bg-opacity-10 p-3 me-3 summary-icon">
                            <i class="bi bi-clock-history fs-3 text-info"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Riwayat Aktivitas</h6>
                            <p class="text-muted small mb-1">
                                Catatan pesanan baru dan pesanan dihapus.
                            </p>
                            <small class="text-primary">Lihat aktivitas →</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>

    {{-- 3. Detail Transaksi --}}
    <div class="col-md-6">
        <a href="#detailTransaksi"
           data-bs-toggle="collapse"
           role="button"
           aria-expanded="false"
           class="text-decoration-none text-dark">

            <div class="card border-0 shadow-sm h-100 summary-card dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3 me-3 summary-icon">
                            <i class="bi bi-receipt fs-3 text-primary"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Detail Transaksi</h6>
                            <p class="text-muted small mb-1">
                                Data transaksi pelanggan dan paket laundry.
                            </p>
                            <small class="text-primary">Lihat detail →</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>


    {{-- 4. Grafik LaundryKu --}}
    <div class="col-md-6">
        <a href="{{ route('admin.grafik-laundryku') }}"
            class="text-decoration-none text-dark">

            <div class="card border-0 shadow-sm h-100 summary-card dashboard-card">
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="rounded-3 bg-success bg-opacity-10 p-3 me-3 summary-icon">
                            <i class="bi bi-bar-chart-line fs-3 text-success"></i>
                        </div>
                        <div>
                            <h6 class="fw-bold mb-1">Grafik LaundryKu</h6>
                            <p class="text-muted small mb-1">
                                Pantau pendapatan dan aktivitas pesanan laundry.
                            </p>
                            <small class="text-primary">Lihat grafik →</small>
                        </div>
                    </div>
                </div>
            </div>
        </a>
    </div>
</div>
<div class="collapse mt-3 {{ request('filter') === 'selesai-lunas' || request('detail') === 'buka' ? 'show' : '' }}" id="detailTransaksi">
{{-- Tabel Laporan --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">

                <div>
                    <h5 class="fw-bold mb-1">Detail Transaksi</h5>
                    <p class="text-muted small mb-0">
                        Data transaksi berdasarkan pelanggan dan paket laundry.
                    </p>
                </div>

                <div class="d-flex gap-2">

                    @if ($filterSelesaiLunas)
                        <a href="{{ route('admin.laporan-transaksi.index', ['detail' => 'buka']) }}#detailTransaksi"
                        class="btn btn-outline-secondary btn-sm">
                            <i class="bi bi-arrow-counterclockwise me-1"></i>
                            Semua Transaksi
                        </a>
                    @else
                        <a href="{{ route('admin.laporan-transaksi.index', ['filter' => 'selesai-lunas']) }}#detailTransaksi"
                        class="btn btn-outline-success btn-sm">
                            <i class="bi bi-funnel me-1"></i>
                            Selesai & Lunas
                        </a>
                    @endif

                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Kode Pesanan</th>
                            <th>Pelanggan</th>
                            <th>Tanggal</th>
                            <th>Paket</th>
                            <th>Berat (Kg)</th>
                            <th>Subtotal</th>
                            <th>Total Pesanan</th>
                            <th>Status</th>
                            <th>Pembayaran</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($laporan as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td class="fw-semibold">
                                    {{ $item->order_code }}
                                </td>

                                <td>{{ $item->nama_pelanggan }}</td>

                                <td>
                                    {{ \Carbon\Carbon::parse($item->order_date)->format('d-m-Y') }}
                                </td>

                                <td>{{ $item->nama_paket }}</td>

                                <td>
                                    {{ rtrim(rtrim(number_format($item->berat, 2, ',', '.'), '0'), ',') }}
                                </td>

                                <td class="fw-semibold">
                                    Rp{{ number_format($item->subtotal, 0, ',', '.') }}
                                </td>

                                <td class="fw-semibold">
                                    Rp{{ number_format($item->total_price, 0, ',', '.') }}
                                </td>

                                <td>
                                    @php
                                        $statusClass = [
                                            'pending' => 'bg-warning text-dark',
                                            'confirmed' => 'bg-info text-dark',
                                            'processing' => 'bg-primary',
                                            'ready' => 'bg-success',
                                            'completed' => 'bg-secondary',
                                            'cancelled' => 'bg-danger',
                                        ];

                                        $statusLabel = [
                                            'pending' => 'Pending',
                                            'confirmed' => 'Dikonfirmasi',
                                            'processing' => 'Diproses',
                                            'ready' => 'Siap Diambil',
                                            'completed' => 'Selesai',
                                            'cancelled' => 'Dibatalkan',
                                        ];
                                    @endphp

                                    <span class="badge {{ $statusClass[$item->status] ?? 'bg-secondary' }}">
                                        {{ $statusLabel[$item->status] ?? ucfirst($item->status) }}
                                    </span>
                                </td>

                                <td>
                                    @if ($item->payment_status === 'paid')
                                        <span class="badge bg-success">Lunas</span>
                                    @else
                                        <span class="badge bg-warning text-dark">Belum Bayar</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">
                                    <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                                    Belum ada data transaksi.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

{{-- Popup Rincian Paket --}}
<div class="modal fade"
     id="modalRincianPaket"
     tabindex="-1"
     aria-labelledby="judulRincianPaket"
     aria-hidden="true">

    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">

            <div class="modal-header">
                <h5 class="modal-title fw-bold" id="judulRincianPaket">
                    Rincian Pesanan per Paket
                </h5>

                <button type="button"
                        class="btn-close"
                        data-bs-dismiss="modal"></button>
            </div>

            <div class="modal-body">
                @forelse ($rincianPaket as $paket)
                    <div class="d-flex justify-content-between align-items-center border-bottom py-3">
                        <span class="fw-semibold">
                            {{ $paket->nama_paket }}
                        </span>

                        <span class="badge bg-primary">
                            {{ number_format($paket->total_pesanan, 0, ',', '.') }}
                            pesanan
                        </span>
                    </div>
                @empty
                    <p class="text-muted mb-0">
                        Belum ada data pesanan paket.
                    </p>
                @endforelse
            </div>

            <div class="modal-footer">
                <button type="button"
                        class="btn btn-secondary"
                        data-bs-dismiss="modal">
                    Tutup
                </button>
            </div>

        </div>
    </div>
</div>

@endsection

@push('styles')
<style>
    .laporan-transaksi .summary-card {
        transition: transform 0.25s ease, box-shadow 0.25s ease;
        transform-origin: center;
    }

    .laporan-transaksi .summary-card:hover {
        animation: kartuGoyang 0.4s ease-in-out;
        transform: translateY(-4px);
        box-shadow: 0 12px 25px rgba(0, 0, 0, 0.10) !important;
    }

    .laporan-transaksi .summary-icon {
        transition: transform 0.25s ease;
    }

    .laporan-transaksi .summary-card:hover .summary-icon {
        transform: scale(1.08) rotate(-5deg);
    }

    @keyframes kartuGoyang {
        0%, 100% {
            transform: translateY(-4px) rotate(0deg);
        }
        25% {
            transform: translateY(-4px) rotate(-0.7deg);
        }
        50% {
            transform: translateY(-4px) rotate(0.7deg);
        }
        75% {
            transform: translateY(-4px) rotate(-0.4deg);
        }
    }

    @media (prefers-reduced-motion: reduce) {
        .laporan-transaksi .summary-card,
        .laporan-transaksi .summary-icon {
            animation: none !important;
            transition: none !important;
        }
    }



/* Ukuran kartu dashboard */
.laporan-transaksi .dashboard-card {
    height: 175px !important;
    min-height: 175px !important;
    border-radius: 18px;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
    transform-origin: center;
}

/* Isi kartu berada di tengah */
.laporan-transaksi .dashboard-card .card-body {
    display: flex;
    align-items: center;
    height: 100%;
    padding: 30px;
}

/* Ikon */
.laporan-transaksi .dashboard-card .summary-icon {
    min-width: 95px;
    min-height: 95px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 25px !important;
    border-radius: 20px !important;
}

/* Ukuran ikon */
.laporan-transaksi .dashboard-card .summary-icon i {
    font-size: 3rem !important;
}

/* Judul */
.laporan-transaksi .dashboard-card h6 {
    font-size: 1.3rem;
}

/* Deskripsi */
.laporan-transaksi .dashboard-card p {
    font-size: 1rem;
}

/* Animasi goyang */
.laporan-transaksi .dashboard-card:hover {
    animation: kartuGoyang 0.45s ease-in-out;
    transform: translateY(-6px);
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.13) !important;
}

.laporan-transaksi .dashboard-card:hover .summary-icon {
    transform: scale(1.15) rotate(-8deg);
}


</style>
@endpush
