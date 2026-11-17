@extends('layouts.app')

@section('title', 'Grafik LaundryKu')
@section('page-title', 'Grafik LaundryKu')

@section('content')
<div class="container-fluid">

    {{-- Header --}}
    <div class="mb-4">
        <h3 class="fw-bold mb-1">Grafik LaundryKu</h3>
        <p class="text-muted mb-0">
            Pantau pendapatan dan aktivitas pesanan laundry.
        </p>
    </div>

    {{-- Ringkasan --}}
    <div class="row g-3 mb-4">

        {{-- Total Pendapatan --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-primary bg-opacity-10 p-3">
                            <i class="bi bi-wallet2 fs-3 text-primary"></i>
                        </div>

                        <div>
                            <p class="text-muted mb-1">Total Pendapatan</p>
                            <h3 class="fw-bold mb-1">
                                Rp{{ number_format($totalPendapatan, 0, ',', '.') }}
                            </h3>
                            <small class="text-muted">
                                Pendapatan dari pesanan yang sudah selesai dan sudah dibayar.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- Total Pesanan --}}
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 bg-success bg-opacity-10 p-3">
                            <i class="bi bi-bag-check fs-3 text-success"></i>
                        </div>

                        <div>
                            <p class="text-muted mb-1">Total Pesanan</p>
                            <h3 class="fw-bold mb-1">
                                {{ number_format($totalPesanan, 0, ',', '.') }}
                            </h3>
                            <small class="text-muted">
                                Jumlah pesanan yang sudah selesai dan sudah dibayar.
                            </small>
                        </div>
                    </div>
                </div>
            </div>
        </div>

    </div>

    {{-- Grafik --}}
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-3">
        <div>
            <h5 class="fw-bold mb-1">Analisis Grafik</h5>
            <p class="text-muted mb-0 small">
                Perkembangan pendapatan dan jumlah pesanan.
            </p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <label for="periodeGrafik" class="small text-muted">
                Periode:
            </label>
            
            <select id="periodeGrafik" class="form-select form-select-sm" style="width: 150px;"
                onchange="window.location.href='?periode=' + this.value;">

                <option value="6" {{ $periodePilihan == '6' ? 'selected' : '' }}>
                    6 Bulan
                </option>

                <option value="12" {{ $periodePilihan == '12' ? 'selected' : '' }}>
                    12 Bulan
                </option>

                <option value="36" {{ $periodePilihan == '36' ? 'selected' : '' }}>
                    3 Tahun
                </option>

                <option value="60" {{ $periodePilihan == '60' ? 'selected' : '' }}>
                    5 Tahun
                </option>

                <option value="all" {{ $periodePilihan == 'all' ? 'selected' : '' }}>
                    Semua Data
                </option>

            </select>

        </div>
    </div>

    <div class="row g-3" id="areaGrafik">

        {{-- Grafik Utama --}}
        <div class="col-lg-8" id="kolomPendapatan">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Tren Pendapatan</h6>
                            <small class="text-muted">Pendapatan per bulan</small>
                        </div>
                        <span class="badge bg-primary bg-opacity-10 text-primary">
                            Diagram Garis
                        </span>
                    </div>

                    <div style="height: 340px;">
                        <canvas id="grafikPendapatan"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Grafik Pendamping --}}
        <div class="col-lg-4" id="kolomPesanan">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="fw-bold mb-1">Jumlah Pesanan</h6>
                            <small class="text-muted">Pesanan per bulan</small>
                        </div>
                        <span class="badge bg-success bg-opacity-10 text-success">
                            Diagram Batang
                        </span>
                    </div>

                    <div style="height: 340px;">
                        <canvas id="grafikPesanan"></canvas>
                    </div>
                </div>
            </div>
        </div>

    </div>

    <p class="text-muted small mt-3 mb-0">
        <i class="bi bi-info-circle me-1"></i>
        Klik salah satu grafik untuk menukar posisi dan ukurannya.
    </p>

</div>

<div
    id="dataGrafik"
    data-labels='@json($labelGrafik)'
    data-pendapatan='@json($dataPendapatan)'
    data-pesanan='@json($dataPesanan)'
></div>
@endsection
@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const dataElement = document.getElementById('dataGrafik');
    const labels = JSON.parse(dataElement.dataset.labels);
    const dataPendapatan = JSON.parse(dataElement.dataset.pendapatan);
    const dataPesanan = JSON.parse(dataElement.dataset.pesanan);

    const kolomPendapatan = document.getElementById('kolomPendapatan');
    const kolomPesanan = document.getElementById('kolomPesanan');

    let pendapatanBesar = true;

    const formatRupiah = (nilai) => {
        return 'Rp' + new Intl.NumberFormat('id-ID').format(nilai);
    };

    const chartPendapatan = new Chart(
        document.getElementById('grafikPendapatan'),
        {
            type: 'line',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Pendapatan',
                    data: dataPendapatan,
                    borderColor: '#4e73df',
                    backgroundColor: 'rgba(78, 115, 223, 0.12)',
                    fill: true,
                    tension: 0.35,
                    borderWidth: 3,
                    pointRadius: 4,
                    pointHoverRadius: 7
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                interaction: {
                    intersect: false,
                    mode: 'index'
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        callbacks: {
                            label: (context) => formatRupiah(context.raw)
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            callback: (value) => formatRupiah(value)
                        }
                    }
                }
            }
        }
    );

    const chartPesanan = new Chart(
        document.getElementById('grafikPesanan'),
        {
            type: 'bar',
            data: {
                labels: labels,
                datasets: [{
                    label: 'Jumlah Pesanan',
                    data: dataPesanan,
                    backgroundColor: 'rgba(28, 200, 138, 0.75)',
                    borderRadius: 6,
                    maxBarThickness: 38
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: { display: false }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        ticks: {
                            precision: 0
                        }
                    }
                }
            }
        }
    );

    function tukarUkuranGrafik() {
        pendapatanBesar = !pendapatanBesar;

        if (pendapatanBesar) {
            kolomPendapatan.className = 'col-lg-8';
            kolomPesanan.className = 'col-lg-4';
        } else {
            kolomPendapatan.className = 'col-lg-4';
            kolomPesanan.className = 'col-lg-8';
        }

        setTimeout(() => {
            chartPendapatan.resize();
            chartPesanan.resize();
        }, 100);
    }

    kolomPendapatan.addEventListener('click', tukarUkuranGrafik);
    kolomPesanan.addEventListener('click', tukarUkuranGrafik);

});
</script>
@endpush
