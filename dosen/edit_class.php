<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

$dosen_id = $_SESSION['user_id'];
$message = '';
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if the class exists and belongs to this dosen
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND dosen_id = ?");
$stmt->execute([$class_id, $dosen_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    header('Location: kelas.php');
    exit();
}

// Handle form submission for updating class
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_class'])) {
    $class_name = sanitizeInput($_POST['class_name']);
    $description = sanitizeInput($_POST['description']);
    
    $stmt = $conn->prepare("UPDATE classes SET class_name = ?, description = ? WHERE id = ? AND dosen_id = ?");
    
    if ($stmt->execute([$class_name, $description, $class_id, $dosen_id])) {
        $message = 'Kelas berhasil diperbarui!';
        logAction($conn, $dosen_id, "Updated class: " . $class_name);
        // Refresh class data
        $stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND dosen_id = ?");
        $stmt->execute([$class_id, $dosen_id]);
        $class = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $message = 'Terjadi kesalahan. Silakan coba lagi.';
    }
}

// Handle class deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_class'])) {
    // First check if there are any active sessions
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count FROM class_sessions 
        WHERE class_id = ? AND status = 'active'
    ");
    $stmt->execute([$class_id]);
    $active_sessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
    
    if ($active_sessions > 0) {
        $message = 'Tidak dapat menghapus kelas karena masih ada sesi aktif. Akhiri semua sesi terlebih dahulu.';
    } else {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // Delete class members
            $stmt = $conn->prepare("DELETE FROM class_members WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            // Delete class sessions
            $stmt = $conn->prepare("DELETE FROM class_sessions WHERE class_id = ?");
            $stmt->execute([$class_id]);
            
            // Delete the class
            $stmt = $conn->prepare("DELETE FROM classes WHERE id = ? AND dosen_id = ?");
            $result = $stmt->execute([$class_id, $dosen_id]);
            
            if ($result) {
                $conn->commit();
                logAction($conn, $dosen_id, "Deleted class ID: " . $class_id);
                header('Location: kelas.php');
                exit();
            } else {
                throw new Exception("Failed to delete class");
            }
        } catch (Exception $e) {
            $conn->rollBack();
            $message = 'Terjadi kesalahan saat menghapus kelas: ' . $e->getMessage();
        }
    }
}

// Get student count for this class
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM class_members WHERE class_id = ?");
$stmt->execute([$class_id]);
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

// Get session count for this class
$stmt = $conn->prepare("SELECT COUNT(*) as count FROM class_sessions WHERE class_id = ?");
$stmt->execute([$class_id]);
$session_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];
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
        <h1 class="page-title">Edit Kelas</h1>
        <div class="row mb-4">
            <div class="col-12">
                <a href="kelas.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left me-2"></i> Kembali ke Daftar Kelas</a>
            </div>
        </div>
        
        <?php if ($message): ?>
            <div class="alert <?php echo strpos($message, 'berhasil') !== false ? 'alert-success' : 'alert-danger'; ?> alert-dismissible fade show" role="alert">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <div class="row">
            <div class="col-lg-8">
                <div class="card">
                    <div class="card-header">Edit Informasi Kelas</div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="mb-3">
                                <label for="class_name" class="form-label">Nama Kelas *</label>
                                <input type="text" class="form-control" id="class_name" name="class_name" value="<?php echo htmlspecialchars($class['class_name']); ?>" required>
                            </div>
                            <div class="mb-3">
                                <label for="description" class="form-label">Deskripsi Kelas</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php echo htmlspecialchars($class['description']); ?></textarea>
                            </div>
                            <div class="d-grid gap-2">
                                <button type="submit" name="update_class" class="btn btn-primary">
                                    <i class="bi bi-save me-2"></i> Simpan Perubahan
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Informasi Kelas</div>
                    <div class="card-body">
                        <p><strong>Dibuat pada:</strong> <?php echo date('d/m/Y H:i', strtotime($class['created_at'])); ?></p>
                        <p><strong>Jumlah Mahasiswa:</strong> <?php echo $student_count; ?></p>
                        <p><strong>Jumlah Sesi:</strong> <?php echo $session_count; ?></p>
                        
                        <hr>
                        
                        <div class="delete-section mt-4">
                            <h5 class="text-danger">Zona Berbahaya</h5>
                            <p class="text-muted small">Menghapus kelas akan menghapus semua data terkait kelas ini, termasuk anggota kelas dan sesi kelas. Tindakan ini tidak dapat dibatalkan.</p>
                            
                            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteClassModal">
                                <i class="bi bi-trash me-2"></i> Hapus Kelas
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteClassModal" tabindex="-1" aria-labelledby="deleteClassModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteClassModalLabel">Konfirmasi Hapus Kelas</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Anda yakin ingin menghapus kelas <strong><?php echo htmlspecialchars($class['class_name']); ?></strong>?</p>
                    <p class="text-danger">Tindakan ini akan menghapus semua data terkait kelas ini dan tidak dapat dibatalkan.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <form method="POST" action="">
                        <button type="submit" name="delete_class" class="btn btn-danger">Hapus Kelas</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
</body>
</html>
