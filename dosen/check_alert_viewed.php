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

// Get data from GET request
$dosen_id = $_SESSION['user_id'];
$class_session_id = isset($_GET['class_session_id']) ? (int)$_GET['class_session_id'] : 0;
$alert_timestamp = isset($_GET['alert_timestamp']) ? $_GET['alert_timestamp'] : null;

if (!$class_session_id) {
    http_response_code(400);
    echo json_encode(['status' => 'error', 'message' => 'Missing required parameters']);
    exit();
}

try {
    // If alert_timestamp is provided, check specific alert
    if ($alert_timestamp) {
        $stmt = $conn->prepare("
            SELECT id FROM emotion_alert_views 
            WHERE dosen_id = ? AND class_session_id = ? AND alert_timestamp = ?
        ");
        $stmt->execute([$dosen_id, $class_session_id, $alert_timestamp]);
        
        echo json_encode([
            'status' => 'success',
            'viewed' => $stmt->rowCount() > 0
        ]);
    } else {
        // Otherwise, get all viewed alerts for this session
        $stmt = $conn->prepare("
            SELECT alert_timestamp FROM emotion_alert_views 
            WHERE dosen_id = ? AND class_session_id = ?
        ");
        $stmt->execute([$dosen_id, $class_session_id]);
        
        $viewedAlerts = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        echo json_encode([
            'status' => 'success',
            'viewed_alerts' => $viewedAlerts
        ]);
    }
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
