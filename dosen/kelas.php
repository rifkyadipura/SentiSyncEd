<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get all classes created by this dosen
$stmt = $conn->prepare("SELECT * FROM classes WHERE dosen_id = ? ORDER BY created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h1 class="page-title">Kelola Kelas</h1>
        <div class="row mb-4">
            <div class="col-12">
                <a href="create_class.php" class="btn btn-primary mb-3"><i class="bi bi-plus-circle me-2"></i> Buat Kelas Baru</a>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Daftar Kelas</div>
                    <div class="card-body">
                        <?php if (empty($classes)): ?>
                            <p class="text-center">Anda belum memiliki kelas. Klik tombol "Buat Kelas Baru" untuk membuat kelas pertama Anda.</p>
                        <?php else: ?>
                            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                                <?php foreach ($classes as $class): ?>
                                    <div class="col">
                                        <div class="card class-card h-100">
                                            <div class="card-body">
                                                <h5 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                                <p class="card-text"><?php echo htmlspecialchars($class['description']); ?></p>
                                                <p class="card-text"><small class="text-muted">Dibuat: <?php echo date('d/m/Y', strtotime($class['created_at'])); ?></small></p>
                                                <div class="d-flex gap-2 mt-3">
                                                    <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-primary btn-sm"><i class="bi bi-eye"></i> Lihat Detail</a>
                                                    <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm"><i class="bi bi-pencil"></i> Edit</a>
                                                    <button type="button" class="btn btn-danger btn-sm" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $class['id']; ?>"><i class="bi bi-trash"></i> Hapus</button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Modals for each class -->
    <?php foreach ($classes as $class): ?>
        <div class="modal fade" id="deleteModal<?php echo $class['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $class['id']; ?>" aria-hidden="true">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="deleteModalLabel<?php echo $class['id']; ?>">Konfirmasi Hapus Kelas</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <p>Anda yakin ingin menghapus kelas <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>?</p>
                        <p class="text-danger">Tindakan ini akan menghapus semua data terkait kelas ini dan tidak dapat dibatalkan.</p>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                        <a href="delete_class.php?id=<?php echo $class['id']; ?>" class="btn btn-danger">Hapus Kelas</a>
                    </div>
                </div>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
