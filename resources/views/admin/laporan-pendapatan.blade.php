 @extends('layouts.app')

@section('content')
<div class="container py-4">

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold mb-1">Laporan Pendapatan</h3>
            <p class="text-muted mb-0">
                Lihat total pendapatan berdasarkan periode pembayaran.
            </p>
        </div>

        <a href="{{ route('admin.dashboard') }}" class="btn btn-outline-secondary">
            Kembali ke Dashboard
        </a>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-4">
            <form action="{{ route('admin.laporanPendapatan') }}" method="GET">

                <div class="row g-3 align-items-end">
                    <div class="col-md-5">
                        <label for="tanggal_awal" class="form-label fw-semibold">
                            Tanggal Awal
                        </label>
                        <input
                            type="date"
                            name="tanggal_awal"
                            id="tanggal_awal"
                            class="form-control"
                            value="{{ $tanggalAwal }}"
                            required
                        >
                    </div>

                    <div class="col-md-5">
                        <label for="tanggal_akhir" class="form-label fw-semibold">
                            Tanggal Akhir
                        </label>
                        <input
                            type="date"
                            name="tanggal_akhir"
                            id="tanggal_akhir"
                            class="form-control"
                            value="{{ $tanggalAkhir }}"
                            required
                        >
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-primary w-100">
                            Tampilkan
                        </button>
                    </div>
                </div>

            </form>
        </div>
    </div>

    @if ($tanggalAwal && $tanggalAkhir)
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <p class="text-muted mb-2">Total Pendapatan</p>

                <h2 class="fw-bold text-success mb-3">
                    Rp{{ number_format($totalPendapatan, 0, ',', '.') }}
                </h2>

                <p class="text-muted mb-0">
                    Periode:
                    {{ \Carbon\Carbon::parse($tanggalAwal)->format('d/m/Y') }}
                    sampai
                    {{ \Carbon\Carbon::parse($tanggalAkhir)->format('d/m/Y') }}
                </p>
            </div>
        </div>
    @endif

</div>
@endsection