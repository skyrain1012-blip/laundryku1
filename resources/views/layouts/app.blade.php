
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Admin LaundryKu')</title>

    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css"
          rel="stylesheet">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css"
          rel="stylesheet">

    <style>
        body {
            background: #f5f7fb;
            font-family: Arial, sans-serif;
            margin: 0;
        }

        .admin-wrapper {
            min-height: 100vh;
        }
        
        .sidebar {
            width: 250px;
            height: 100vh;
            background: #172554;
            color: white;
            position: fixed;
            top: 0;
            left: 0;
            padding: 25px 18px;
            z-index: 1000;
            overflow-y: auto;
            overflow-x: hidden;
        }

        .main-content {
            margin-left: 250px;
            min-height: 100vh;
        }

        .brand {
            font-size: 23px;
            font-weight: bold;
            margin-bottom: 35px;
        }

        .sidebar a {
            display: block;
            color: #cbd5e1;
            text-decoration: none;
            padding: 12px 15px;
            border-radius: 8px;
            margin-bottom: 8px;
        }

        .sidebar a:hover,
        .sidebar a.active {
            background: #2563eb;
            color: white;
        }

        .top-navbar {
            background: white;
            padding: 18px 30px;
            border-bottom: 1px solid #e5e7eb;
        }

        .page-content {
            padding: 30px;
        }

        .stat-card {
            border: none;
            border-radius: 14px;
            padding: 22px;
            height: 100%;
            box-shadow: 0 3px 15px rgba(0,0,0,.04);
        }

        .stat-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 23px;
        }

        @media(max-width: 768px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                max-height: 70vh;
                overflow-y: auto;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
            }

            .page-content {
                padding: 18px;
            }
        }
    </style>

    @stack('styles')
</head>

<body>

<div class="admin-wrapper">

    @include('components.admin.sidebar')

    <div class="main-content">

        @include('components.admin.navbar')

        <main class="page-content">
            @yield('content')
        </main>

    </div>

</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>


{{-- Notifikasi Global Admin --}}

<div
    id="notifikasi-admin"
    data-success="{{ session('success') }}"
    data-error="{{ session('error') }}"
></div>

<script>
document.addEventListener('DOMContentLoaded', function () {

    const notifikasi = document.getElementById('notifikasi-admin');

    const pesanSukses = notifikasi.dataset.success;
    const pesanError = notifikasi.dataset.error;

    if (pesanSukses) {
        Swal.fire({
            icon: 'success',
            title: 'Berhasil!',
            text: pesanSukses,
            confirmButtonText: 'Oke, Mengerti',
            confirmButtonColor: '#16a34a',
            customClass: {
                popup: 'rounded-4',
                title: 'fw-bold',
                confirmButton: 'rounded-3 px-4'
            }
        });
    }

    if (pesanError) {
        Swal.fire({
            icon: 'error',
            title: 'Terjadi Kesalahan!',
            text: pesanError,
            confirmButtonText: 'Tutup',
            confirmButtonColor: '#dc2626',
            customClass: {
                popup: 'rounded-4',
                title: 'fw-bold',
                confirmButton: 'rounded-3 px-4'
            }
        });
    }

});
</script>

@stack('scripts')

</body>
</html>
