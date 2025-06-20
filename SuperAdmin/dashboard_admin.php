<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

// Get user statistics
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get class statistics
$stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
$classCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get today's emotions
$stmt = $conn->query("SELECT COUNT(*) as count FROM emotions WHERE DATE(timestamp) = CURDATE()");
$todayEmotions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get emotion distribution
$stmt = $conn->query("SELECT emotion, COUNT(*) as count FROM emotions GROUP BY emotion");
$emotionDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get active class sessions
$stmt = $conn->query("SELECT COUNT(*) as count FROM class_sessions WHERE status = 'active'");
$activeSessionsCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get recent logs
$stmt = $conn->query("SELECT l.*, u.name as user_name, u.role 
                     FROM logs l 
                     JOIN users u ON l.user_id = u.id 
                     ORDER BY l.timestamp DESC LIMIT 10");
$recentLogs = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get recent registered users
$stmt = $conn->query("SELECT * FROM users ORDER BY created_at DESC LIMIT 5");
$recentUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SuperAdmin - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #495057;
            overflow-x: hidden;
        }
        
        .sidebar {
            background-color: #3b8adb;
            background-image: linear-gradient(180deg, #3b8adb 10%, #3b8adb 100%);
            min-height: 100vh;
            position: fixed;
            z-index: 1030;
            width: 250px;
            transition: all 0.3s ease;
        }
        
        .btn-close-sidebar {
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        
        .btn-close-sidebar:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            margin: 0.2rem 0;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .stat-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
            border-left: 5px solid transparent;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        .stat-card .icon {
            font-size: 2.5rem;
            opacity: 0.8;
            transition: all 0.3s ease;
        }
        
        .stat-card:hover .icon {
            transform: scale(1.1);
            opacity: 1;
        }
        
        .stat-card.primary {
            background: linear-gradient(135deg, #4e73df, #224abe);
            color: white;
            border-left-color: #224abe;
        }
        
        .stat-card.success {
            background: linear-gradient(135deg, #1cc88a, #13855c);
            color: white;
            border-left-color: #13855c;
        }
        
        .stat-card.info {
            background: linear-gradient(135deg, #36b9cc, #258391);
            color: white;
            border-left-color: #258391;
        }
        
        .stat-card.warning {
            background: linear-gradient(135deg, #f6c23e, #dda20a);
            color: white;
            border-left-color: #dda20a;
        }
        
        .stat-card.danger {
            background: linear-gradient(135deg, #e74a3b, #be2617);
            color: white;
            border-left-color: #be2617;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #495057;
        }
        
        .activity-item {
            padding: 1rem;
            border-bottom: 1px solid #e9ecef;
        }
        
        .activity-item:last-child {
            border-bottom: none;
        }
        
        .activity-item .time {
            font-size: 0.8rem;
            color: #6c757d;
        }
        
        /* scrollable recent activity list */
        .activity-feed{
            max-height:350px;
            overflow-y:auto;
        }
        .activity-feed::-webkit-scrollbar{width:6px;}
        .activity-feed::-webkit-scrollbar-thumb{background:#c1c1c1;border-radius:3px;}
        
        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            border: none;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .topbar {
            height: 4.5rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            background-color: white;
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 1;
            padding: 0 1.5rem;
        }
        
        .content-wrapper {
            padding-top: 4.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                min-height: 100vh;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                left: 0;
                width: 100%;
            }
            
            .content-wrapper {
                padding-top: 5.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white col-md-3 col-lg-2">
        <div class="sidebar-brand d-flex align-items-center justify-content-center position-relative">
            <i class="bi bi-bar-chart-line me-2"></i>
            <span>SentiSyncEd</span>
            <button class="btn-close-sidebar d-md-none position-absolute end-0 me-3 text-white bg-transparent border-0">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link active">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="manage_dosen.php" class="nav-link text-white">
                    <i class="bi bi-person-badge"></i>
                    Kelola Dosen
                </a>
            </li>
            <li>
                <a href="manage_mahasiswa.php" class="nav-link text-white">
                    <i class="bi bi-mortarboard"></i>
                    Kelola Mahasiswa
                </a>
            </li>
            <li>
                <a href="manage_kelas.php" class="nav-link text-white">
                    <i class="bi bi-journal-text"></i>
                    Kelola Kelas
                </a>
            </li>
            <li>
                <a href="analisis_emosi.php" class="nav-link text-white">
                    <i class="bi bi-emoji-smile"></i>
                    Analisis Emosi
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar me-2 bg-white">
                    <i class="bi bi-person text-primary"></i>
                </div>
                <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="edit_profile.php">Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <button class="btn btn-link d-md-none rounded-circle me-3">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-none d-md-flex">
            <h4 class="mb-0">Dashboard SuperAdmin</h4>
        </div>
        <!-- Brand text only on mobile -->
        <div class="d-flex d-md-none">
            <span class="fw-semibold text-primary">SentiSyncEd</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Welcome Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['name']) ?></h2>
                            <p class="text-muted">Dashboard SuperAdmin SentiSyncEd | <?= date('l, d F Y') ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <!-- Total SuperAdmin Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card primary h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">TOTAL SUPERADMIN</div>
                                    <?php
                                    $superadminCount = 0;
                                    foreach ($userStats as $stat) {
                                        if ($stat['role'] == 'SuperAdmin') {
                                            $superadminCount = $stat['count'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="h1 mb-0 fw-bold"><?= $superadminCount ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-badge fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Mahasiswa Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">TOTAL MAHASISWA</div>
                                    <?php
                                    $mahasiswaCount = 0;
                                    foreach ($userStats as $stat) {
                                        if ($stat['role'] == 'Mahasiswa') {
                                            $mahasiswaCount = $stat['count'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="h1 mb-0 fw-bold"><?= $mahasiswaCount ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-people fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Dosen Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card info h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">TOTAL DOSEN</div>
                                    <?php
                                    $dosenCount = 0;
                                    foreach ($userStats as $stat) {
                                        if ($stat['role'] == 'Dosen') {
                                            $dosenCount = $stat['count'];
                                            break;
                                        }
                                    }
                                    ?>
                                    <div class="h1 mb-0 fw-bold"><?= $dosenCount ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-person-workspace fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Total Kelas Card -->
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card stat-card danger h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">TOTAL KELAS</div>
                                    <div class="h1 mb-0 fw-bold"><?= $classCount ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-book fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Secondary Statistics Cards -->
            <div class="row mb-4">
                <!-- Sesi Aktif Card -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card stat-card success h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">SESI AKTIF</div>
                                    <div class="h1 mb-0 fw-bold"><?= $activeSessionsCount ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-calendar-check fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Emosi Hari Ini Card -->
                <div class="col-xl-6 col-md-6 mb-4">
                    <div class="card stat-card info h-100">
                        <div class="card-body d-flex flex-column justify-content-center">
                            <div class="row no-gutters align-items-center">
                                <div class="col">
                                    <div class="text-xs fw-bold text-uppercase mb-2">EMOSI HARI INI</div>
                                    <div class="h1 mb-0 fw-bold"><?= $todayEmotions ?></div>
                                </div>
                                <div class="col-auto">
                                    <i class="bi bi-emoji-smile fs-1 icon"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Tables -->
            <div class="row">
                <!-- Emotion Distribution Chart -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Distribusi Emosi</h6>
                            <div class="dropdown no-arrow">
                                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-bs-toggle="dropdown" aria-expanded="false">
                                    <i class="bi bi-three-dots-vertical text-gray-400"></i>
                                </a>
                                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                                    <div class="dropdown-header">Opsi:</div>
                                    <a class="dropdown-item" href="#">Lihat Detail</a>
                                    <a class="dropdown-item" href="#">Unduh Laporan</a>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="chart-area">
                                <canvas id="emotionChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Aktivitas Terbaru</h6>
                        </div>
                        <div class="card-body p-0">
                            <div class="activity-feed">
                                <?php foreach ($recentLogs as $log): ?>
                                <div class="activity-item">
                                    <div class="d-flex align-items-start">
                                        <div class="avatar me-3 bg-light">
                                            <i class="bi bi-person-circle"></i>
                                        </div>
                                        <div>
                                            <div class="fw-bold"><?= htmlspecialchars($log['user_name']) ?></div>
                                            <div><?= htmlspecialchars($log['action']) ?></div>
                                            <div class="time"><?= timeAgo($log['timestamp']) ?></div>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                                <?php if (empty($recentLogs)): ?>
                                <div class="activity-item">
                                    <p class="text-center text-muted my-3">Tidak ada aktivitas terbaru</p>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Users and Quick Links -->
            <div class="row">
                <!-- Recent Users -->
                <div class="col-xl-8 col-lg-7">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold">Pengguna Terbaru</h6>
                            <a href="#" class="btn btn-sm btn-primary">Lihat Semua</a>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Role</th>
                                            <th>Terdaftar</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentUsers as $user): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center">
                                                    <div class="avatar me-2 bg-light">
                                                        <?= substr($user['name'], 0, 1) ?>
                                                    </div>
                                                    <?= htmlspecialchars($user['name']) ?>
                                                </div>
                                            </td>
                                            <td><?= htmlspecialchars($user['email']) ?></td>
                                            <td><span class="badge bg-<?= $user['role'] == 'SuperAdmin' ? 'danger' : ($user['role'] == 'Dosen' ? 'primary' : 'success') ?>"><?= htmlspecialchars($user['role']) ?></span></td>
                                            <td><?= date('d M Y', strtotime($user['created_at'])) ?></td>
                                            <td>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                        <i class="bi bi-three-dots-vertical"></i>
                                                    </button>
                                                    <ul class="dropdown-menu">
                                                        <li><a class="dropdown-item" href="#">Lihat Detail</a></li>
                                                        <li><a class="dropdown-item" href="#">Edit</a></li>
                                                        <li><a class="dropdown-item text-danger" href="#">Hapus</a></li>
                                                    </ul>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Links -->
                <div class="col-xl-4 col-lg-5">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h6 class="m-0 font-weight-bold">Akses Cepat</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-6 mb-3">
                                    <a href="manage_dosen.php" class="btn btn-primary w-100 py-3">
                                        <i class="bi bi-person-plus-fill mb-2 d-block fs-3"></i>
                                        Tambah Dosen
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="#" class="btn btn-success w-100 py-3">
                                        <i class="bi bi-people-fill mb-2 d-block fs-3"></i>
                                        Tambah Mahasiswa
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="#" class="btn btn-info w-100 py-3 text-white">
                                        <i class="bi bi-journal-plus mb-2 d-block fs-3"></i>
                                        Buat Kelas
                                    </a>
                                </div>
                                <div class="col-6 mb-3">
                                    <a href="#" class="btn btn-warning w-100 py-3">
                                        <i class="bi bi-file-earmark-text mb-2 d-block fs-3"></i>
                                        Laporan
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Initialize emotion distribution chart
        document.addEventListener('DOMContentLoaded', function() {
            // Prepare data for emotion chart
            const emotionData = <?= json_encode($emotionDistribution) ?>;
            const labels = emotionData.map(item => item.emotion);
            const data = emotionData.map(item => item.count);
            const colors = [
                'rgba(78, 115, 223, 0.8)',
                'rgba(28, 200, 138, 0.8)',
                'rgba(54, 185, 204, 0.8)',
                'rgba(246, 194, 62, 0.8)'
            ];

            // Create chart
            const ctx = document.getElementById('emotionChart').getContext('2d');
            new Chart(ctx, {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: data,
                        backgroundColor: colors,
                        hoverBackgroundColor: colors.map(color => color.replace('0.8', '1')),
                        hoverBorderColor: "rgba(234, 236, 244, 1)",
                    }]
                },
                options: {
                    maintainAspectRatio: false,
                    tooltips: {
                        backgroundColor: "rgb(255,255,255)",
                        bodyFontColor: "#858796",
                        borderColor: '#dddfeb',
                        borderWidth: 1,
                        xPadding: 15,
                        yPadding: 15,
                        displayColors: false,
                        caretPadding: 10,
                    },
                    legend: {
                        display: true,
                        position: 'bottom'
                    },
                    cutoutPercentage: 70,
                }
            });

            // Toggle sidebar on small screens
            document.querySelector('.btn-link').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('show');
            });
            
            // Close sidebar with the close button
            document.querySelector('.btn-close-sidebar').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.remove('show');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.querySelector('.btn-link');
                
                if (window.innerWidth <= 768 && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && 
                    !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });
        });
    </script>
</body>
</html>