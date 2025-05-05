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
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Check if the class exists and belongs to this dosen
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND dosen_id = ?");
$stmt->execute([$class_id, $dosen_id]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    $_SESSION['message'] = 'Kelas tidak ditemukan atau Anda tidak memiliki izin untuk menghapusnya.';
    $_SESSION['message_type'] = 'danger';
    header('Location: kelas.php');
    exit();
}

// First check if there are any active sessions
$stmt = $conn->prepare("
    SELECT COUNT(*) as count FROM class_sessions 
    WHERE class_id = ? AND status = 'active'
");
$stmt->execute([$class_id]);
$active_sessions = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

if ($active_sessions > 0) {
    $_SESSION['message'] = 'Tidak dapat menghapus kelas karena masih ada sesi aktif. Akhiri semua sesi terlebih dahulu.';
    $_SESSION['message_type'] = 'danger';
    header('Location: kelas.php');
    exit();
}

try {
    // Begin transaction
    $conn->beginTransaction();
    
    // Delete emotions linked to this class's sessions
    $stmt = $conn->prepare("
        DELETE e FROM emotions e
        INNER JOIN class_sessions cs ON e.class_session_id = cs.id
        WHERE cs.class_id = ?
    ");
    $stmt->execute([$class_id]);
    
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
        $_SESSION['message'] = 'Kelas berhasil dihapus.';
        $_SESSION['message_type'] = 'success';
    } else {
        throw new Exception("Failed to delete class");
    }
} catch (Exception $e) {
    $conn->rollBack();
    $_SESSION['message'] = 'Terjadi kesalahan saat menghapus kelas: ' . $e->getMessage();
    $_SESSION['message_type'] = 'danger';
}

header('Location: kelas.php');
exit();
?>
