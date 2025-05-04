<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get dosen info
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'Dosen'");
$stmt->execute([$_SESSION['user_id']]);
$dosen = $stmt->fetch(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content-wrapper">
        <h1 class="page-title">Dashboard Dosen</h1>
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card welcome-card p-4 h-100">
                    <h2>Selamat Datang, <?php echo htmlspecialchars($dosen['name']); ?>!</h2>
                    <p class="mt-2">Ini adalah dashboard dosen SentiSyncEd. Anda dapat mengelola kelas dan melihat laporan emosi mahasiswa di sini.</p>
                    <a href="create_class.php" class="btn btn-light mt-3">Buat Kelas Baru</a>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header">Statistik</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-6 mb-3">
                                <h5>Jumlah Kelas</h5>
                                <h3>0</h3>
                            </div>
                            <div class="col-md-6 mb-3">
                                <h5>Total Mahasiswa</h5>
                                <h3>0</h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Kelas Anda</div>
                    <div class="card-body">
                        <p class="text-center">Belum ada kelas yang dibuat. <a href="create_class.php">Buat kelas baru</a>.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
