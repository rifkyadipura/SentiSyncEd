<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

// Get statistics
$stmt = $conn->query("SELECT role, COUNT(*) as count FROM users GROUP BY role");
$userStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $conn->query("SELECT COUNT(*) as count FROM classes");
$classCount = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $conn->query("SELECT COUNT(*) as count FROM emotions WHERE DATE(timestamp) = CURDATE()");
$todayEmotions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard SuperAdmin - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Open Sans', sans-serif;
            background-color: #f8f9fa;
        }
        .dashboard-card {
            border-radius: 15px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
        }
        .stat-card {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            color: white;
        }
        .menu-card {
            border: none;
            background-color: white;
        }
        .menu-card i {
            font-size: 2rem;
            color: #4A90E2;
        }
    </style>
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container">
            <a class="navbar-brand" href="#">SentiSyncEd SuperAdmin</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="../login.php?logout=1">
                            <i class="fas fa-sign-out-alt"></i> Logout
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container py-5">
        <div class="row mb-4">
            <div class="col-md-12">
                <h2>Selamat Datang, <?= htmlspecialchars($_SESSION['name']) ?></h2>
                <p class="text-muted">Dashboard SuperAdministrator SentiSyncEd</p>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <?php foreach ($userStats as $stat): ?>
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card stat-card">
                    <div class="card-body">
                        <h5 class="card-title">Total <?= htmlspecialchars($stat['role']) ?></h5>
                        <h2 class="mb-0"><?= htmlspecialchars($stat['count']) ?></h2>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Menu Cards -->
        <div class="row">
            <div class="col-md-4 mb-3">
                <div class="card dashboard-card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-user-tie mb-3"></i>
                        <h5 class="card-title">Kelola Dosen</h5>
                        <p class="card-text">Tambah, edit, dan kelola akun dosen</p>
                        <a href="SuperAdmin/manage_dosen.php" class="btn btn-primary">Akses</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card dashboard-card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-graduation-cap mb-3"></i>
                        <h5 class="card-title">Data Kelas</h5>
                        <p class="card-text">Lihat statistik dan data kelas</p>
                        <p class="mb-2">Total Kelas: <?= $classCount ?></p>
                        <a href="#" class="btn btn-primary">Lihat Detail</a>
                    </div>
                </div>
            </div>

            <div class="col-md-4 mb-3">
                <div class="card dashboard-card menu-card">
                    <div class="card-body text-center">
                        <i class="fas fa-chart-line mb-3"></i>
                        <h5 class="card-title">Statistik Emosi</h5>
                        <p class="card-text">Pantau statistik emosi mahasiswa</p>
                        <p class="mb-2">Hari ini: <?= $todayEmotions ?> entri</p>
                        <a href="#" class="btn btn-primary">Lihat Statistik</a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
