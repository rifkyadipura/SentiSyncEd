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
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../css/style.css">
</head>
<body>
    <div class="dashboard">
        <?php include 'sidebar.php'; ?>
        
        <div class="main-content">
            <div class="container py-4">
                <div class="d-flex justify-content-between align-items-center">
                    <h2>Kelola Kelas</h2>
                    <a href="create_class.php" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Buat Kelas Baru
                    </a>
                </div>
                
                <div class="row mt-4">
                    <?php if (empty($classes)): ?>
                    <div class="col-12">
                        <div class="alert alert-info">
                            Anda belum memiliki kelas. Klik tombol "Buat Kelas Baru" untuk membuat kelas pertama Anda.
                        </div>
                    </div>
                    <?php else: ?>
                        <?php foreach ($classes as $class): ?>
                        <div class="col-md-4 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                    <p class="card-text"><?php echo htmlspecialchars($class['description']); ?></p>
                                    <p class="card-text">
                                        <small class="text-muted">Dibuat: <?php echo date('d/m/Y', strtotime($class['created_at'])); ?></small>
                                    </p>
                                </div>
                                <div class="card-footer bg-transparent border-top-0">
                                    <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-info btn-sm">
                                        <i class="fas fa-eye"></i> Lihat Detail
                                    </a>
                                    <a href="edit_class.php?id=<?php echo $class['id']; ?>" class="btn btn-warning btn-sm">
                                        <i class="fas fa-edit"></i> Edit
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
