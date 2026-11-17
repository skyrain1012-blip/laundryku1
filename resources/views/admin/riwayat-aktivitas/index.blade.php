@extends('layouts.app')

@section('title', 'Riwayat Aktivitas - LaundryKu')
@section('page-title', 'Riwayat Aktivitas')

@section('content')

<div class="container-fluid riwayat-aktivitas">

    {{-- Header Halaman --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Riwayat Aktivitas</h3>
        <p class="text-muted mb-0">
            Catatan aktivitas penambahan dan penghapusan pesanan LaundryKu.
        </p>
    </div>

    {{-- Tabel Riwayat Aktivitas --}}
    <div class="card border-0 shadow-sm">
        <div class="card-body">

            <div class="mb-4">
                <h5 class="fw-bold mb-1">Daftar Aktivitas Pesanan</h5>
                <p class="text-muted small mb-0">
                    Riwayat aktivitas tercatat otomatis melalui database trigger.
                </p>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">

                    <thead class="table-light">
                        <tr>
                            <th>No.</th>
                            <th>Aktivitas</th>
                            <th>Kode Pesanan</th>
                            <th>ID Pelanggan</th>
                            <th>Total Harga</th>
                            <th>Pembayaran</th>
                            <th>Status Sebelum</th>
                            <th>Status Sesudah</th>
                            <th>Waktu Aktivitas</th>
                        </tr>
                    </thead>

                    <tbody>
                        @forelse ($riwayat as $item)
                            <tr>
                                <td>{{ $loop->iteration }}</td>

                                <td>
                                    @if ($item->aktivitas === 'INSERT')
                                        <span class="badge bg-success">
                                            Pesanan Baru
                                        </span>
                                    @elseif ($item->aktivitas === 'DELETE')
                                        <span class="badge bg-danger">
                                            Pesanan Dihapus
                                        </span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ $item->aktivitas }}
                                        </span>
                                    @endif
                                </td>

                                <td class="fw-semibold">
                                    {{ $item->order_code }}
                                </td>

                                <td>{{ $item->customer_id }}</td>

                                <td class="fw-semibold">
                                    Rp{{ number_format($item->total_price ?? 0, 0, ',', '.') }}
                                </td>

                                <td>
                                    @if ($item->payment_status === 'paid')
                                        <span class="badge bg-success">Lunas</span>
                                    @elseif ($item->payment_status === 'unpaid')
                                        <span class="badge bg-warning text-dark">Belum Bayar</span>
                                    @else
                                        <span class="badge bg-secondary">
                                            {{ $item->payment_status ?? '-' }}
                                        </span>
                                    @endif
                                </td>

                                <td>
                                    {{ $item->status_sebelum ?? '-' }}
                                </td>

                                <td>
                                    {{ $item->status_sesudah ?? '-' }}
                                </td>

                                <td>
                                    {{ \Carbon\Carbon::parse($item->waktu_aktivitas)->format('d-m-Y H:i:s') }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bi bi-clock-history fs-2 d-block mb-2"></i>
                                    Belum ada riwayat aktivitas.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>

                </table>
            </div>

        </div>
    </div>

</div>

@endsection
