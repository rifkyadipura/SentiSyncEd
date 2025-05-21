<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    http_response_code(403);
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

header('Content-Type: application/json');

// Get data from POST request
$dosen_id = $_SESSION['user_id'];
$class_session_id = isset($_POST['class_session_id']) ? (int)$_POST['class_session_id'] : 0;
$alert_timestamp = isset($_POST['alert_timestamp']) ? $_POST['alert_timestamp'] : null;

if (!$class_session_id || !$alert_timestamp) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

try {
    // Check if this alert has already been viewed
    $stmt = $conn->prepare("
        SELECT id FROM emotion_alert_views 
        WHERE dosen_id = ? AND class_session_id = ? AND alert_timestamp = ?
    ");
    $stmt->execute([$dosen_id, $class_session_id, $alert_timestamp]);
    
    if ($stmt->rowCount() === 0) {
        // Insert new record
        $stmt = $conn->prepare("
            INSERT INTO emotion_alert_views (dosen_id, class_session_id, alert_timestamp, viewed_at) 
            VALUES (?, ?, ?, NOW())
        ");
        $stmt->execute([$dosen_id, $class_session_id, $alert_timestamp]);
    }
    
    echo json_encode(['status' => 'success']);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
