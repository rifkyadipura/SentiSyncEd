<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            overflow-x: hidden;
        }

        /* Sidebar styles */
        .sidebar {
            background-color: #4A90E2;
            color: #fff;
            height: 100vh;
            position: fixed;
            width: 250px;
            z-index: 1030;
            transition: all 0.3s ease;
        }

        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }

        .sidebar-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 24px;
            color: #fff;
        }

        .nav-link {
            color: #e3eaf3;
            padding: 12px 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }

        .nav-link:hover,
        .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.12);
        }

        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }

        .content-wrapper {
            transition: margin-left 0.3s ease;
            padding: 30px;
        }

        /* Desktop view */
        @media (min-width: 992px) {
            .content-wrapper {
                margin-left: 250px;
            }
            .sidebar {
                transform: translateX(0);
            }
            .navbar-toggler {
                display: none;
            }
            .mobile-navbar {
                display: none !important;
            }
        }

        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }

        .card-header {
            background-color: #ffffff;
            border-bottom: none;
            padding: 20px 30px;
            font-weight: 600;
            font-size: 18px;
        }

        .card-body {
            padding: 30px;
        }

        .btn-primary {
            background-color: #3c4b64;
            border: none;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 6px;
        }

        .btn-primary:hover {
            background-color: #2c3a50;
        }

        .page-title {
            font-weight: 700;
            margin-bottom: 25px;
            color: #343a40;
        }

        .stat-card {
            background-color: #ffffff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }

        .stat-card i {
            font-size: 24px;
            margin-bottom: 15px;
            color: #3c4b64;
        }

        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #343a40;
        }

        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
        }

        .class-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }

        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }

        .class-card .card-title {
            font-weight: 600;
            color: #343a40;
        }

        .class-card .card-text {
            color: #6c757d;
        }

        /* Emotion Alert System Styles */
        .emotion-alert-badge {
            position: relative;
            display: inline-block;
        }

        .emotion-alert-badge .badge {
            position: absolute;
            top: -8px;
            right: -8px;
            border-radius: 50%;
            background-color: #dc3545;
            color: white;
            font-size: 10px;
            padding: 3px 6px;
            animation: pulse 1.5s infinite;
        }
        
        /* Profile dropdown styles */
        .dropdown-toggle::after {
            margin-left: 8px;
        }
        
        .dropdown-menu {
            border: none;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
        }
        
        .dropdown-item {
            padding: 10px 15px;
            font-size: 14px;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
            color: #4A90E2;
        }
        
        .dropdown-item i {
            color: #6c757d;
            transition: all 0.2s ease;
        }
        
        .dropdown-item:hover i {
            color: #4A90E2;
        }
        
        .mobile-navbar {
            background-color: #fff;
            padding: 10px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1020;
            display: none;
        }

        @keyframes pulse {
            0% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
            }

            70% {
                transform: scale(1);
                box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
            }

            100% {
                transform: scale(0.95);
                box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
            }
        }

        .emotion-alert-item {
            border-left: 4px solid #dc3545;
            margin-bottom: 10px;
            padding: 10px;
            background-color: #fff;
            border-radius: 5px;
            box-shadow: 0 2px 5px rgba(0, 0, 0, 0.1);
            transition: all 0.3s ease;
        }

        .emotion-alert-item:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        }

        .emotion-alert-item.new {
            animation: highlight 5s ease-out;
        }

        @keyframes highlight {
            0% {
                background-color: #ffeaea;
            }

            100% {
                background-color: #fff;
            }
        }

        .emotion-alert-item.severity-high {
            border-left-color: #dc3545;
        }

        .emotion-alert-item.severity-medium {
            border-left-color: #fd7e14;
        }

        .emotion-alert-item.severity-low {
            border-left-color: #ffc107;
        }

        .emotion-alert-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 5px;
        }

        .emotion-alert-title {
            font-weight: 600;
            margin: 0;
        }

        .emotion-alert-badge-inline {
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
        }

        .severity-high .emotion-alert-badge-inline {
            background-color: #dc3545;
            color: white;
        }

        .severity-medium .emotion-alert-badge-inline {
            background-color: #fd7e14;
            color: white;
        }

        .severity-low .emotion-alert-badge-inline {
            background-color: #ffc107;
            color: #212529;
        }

        .emotion-alert-time {
            font-size: 12px;
            color: #6c757d;
        }

        .emotion-alert-details {
            margin-top: 5px;
            font-size: 14px;
        }

        .emotion-alert-students {
            font-style: italic;
            margin-top: 5px;
            font-size: 13px;
        }

        #emotionAlertSound {
            display: none;
        }

        .sidebar .nav-link {
            color: #e3eaf3;
            border-radius: 8px;
            margin-bottom: 6px;
            transition: background 0.2s, color 0.2s;
        }

        .sidebar .nav-link.active,
        .sidebar .nav-link:hover {
            background: rgba(255, 255, 255, 0.15) !important;
            color: #fff !important;
        }

        .sidebar .nav-link i {
            font-size: 1.2rem;
        }

        /* Tablet view */
        @media (max-width: 991.98px) {
            .sidebar {
                width: 230px;
                transform: translateX(-100%);
            }
            .content-wrapper {
                margin-left: 0;
            }
            .sidebar.show {
                transform: translateX(0);
            }
            .overlay {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0, 0, 0, 0.4);
                z-index: 1020;
            }
            .overlay.show {
                display: block;
            }
        }

        /* Mobile view */
        @media (max-width: 575.98px) {
            .sidebar {
                width: 80%;
            }
            .content-wrapper {
                padding: 20px 15px;
            }
            .page-title {
                font-size: 1.5rem;
            }
        }

        /* Navbar styles */
        .mobile-navbar {
            background-color: #4A90E2;
            padding: 10px 20px;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            display: none;
        }
        
        /* User dropdown styles */
        .user-dropdown {
            position: absolute;
            top: 20px;
            right: 30px;
            z-index: 1020;
        }
        
        .user-dropdown .btn {
            background-color: #4A90E2;
            color: white;
            border: none;
            border-radius: 6px;
            padding: 8px 16px;
            display: flex;
            align-items: center;
            transition: all 0.2s ease;
        }
        
        .user-dropdown .btn:hover {
            background-color: #3a7bc8;
        }

        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .mobile-navbar {
                display: flex !important;
                justify-content: space-between;
                align-items: center;
            }
            .content-wrapper {
                margin-left: 0;
                padding-top: 70px; /* Memberikan ruang untuk navbar mobile */
            }
        }
    </style>
</head>

<body>


    <!-- Mobile Navbar -->
    <div class="mobile-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-light me-2" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="text-white mb-0">SentiSyncEd</h4>
        </div>
        
        <!-- Profile Dropdown for Mobile -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.15) !important;">
            <h2 class="mb-0" style="color:#fff; font-weight:700; font-size:24px;">SentiSyncEd</h2>
        </div>
        <nav class="nav flex-column py-3">
            <a href="dashboard_mahasiswa.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-house me-2"></i> Dashboard
            </a>
            <a href="input_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-emoji-smile me-2"></i> Input Emosi
            </a>
            <a href="tulis_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-chat-dots me-2"></i> Tulis Curhat
            </a>
            <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
            </a>
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <!-- User Dropdown in Content Area -->
    <div class="user-dropdown dropdown d-none d-lg-block">
        <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>
    
    <div class="content-wrapper">
        <div class="container-fluid px-0">
            <h1 class="page-title mb-4">Dashboard Mahasiswa</h1>

            <!-- Dashboard content here -->
            <div class="row">
                <div class="col-lg-8 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Aktivitas Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <p>Selamat datang di dashboard mahasiswa SentiSyncEd. Gunakan menu di sebelah kiri untuk navigasi.</p>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Informasi Akun</h5>
                        </div>
                        <div class="card-body">
                            <p><strong>Nama:</strong> <?php echo isset($_SESSION['name']) ? $_SESSION['name'] : 'Mahasiswa'; ?></p>
                            <p><strong>Email:</strong> <?php echo isset($_SESSION['email']) ? $_SESSION['email'] : '-'; ?></p>
                            <p><strong>Status:</strong> Aktif</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const navLinks = document.querySelectorAll('.sidebar .nav-link');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }

            // Close sidebar when a nav link is clicked on mobile
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>