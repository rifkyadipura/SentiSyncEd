<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

$message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $class_name = trim($_POST['class_name']);
    $description = trim($_POST['description']);
    $dosen_id = $_SESSION['user_id'];

    if (empty($class_name)) {
        $message = 'Nama kelas tidak boleh kosong';
    } else {
        $sql = "INSERT INTO classes (class_name, description, dosen_id) VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        
        if ($stmt->execute([$class_name, $description, $dosen_id])) {
            $message = 'Kelas berhasil dibuat!';
            header('Location: dashboard_dosen.php');
            exit();
        } else {
            $message = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
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
        <h1 class="page-title">Buat Kelas Baru</h1>
        <div class="card">
            <div class="card-header">
                Formulir Pembuatan Kelas
            </div>
            <div class="card-body">
                <?php if ($message): ?>
                    <div class="alert alert-info"><?php echo $message; ?></div>
                <?php endif; ?>

                <form method="POST" action="">
                    <div class="mb-3">
                        <label for="class_name" class="form-label">Nama Kelas *</label>
                        <input type="text" class="form-control" id="class_name" name="class_name" required>
                    </div>
                    <div class="mb-3">
                        <label for="description" class="form-label">Deskripsi Kelas</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">Buat Kelas</button>
                        <a href="dashboard_dosen.php" class="btn btn-secondary">Kembali</a>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
