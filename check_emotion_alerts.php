<?php
// This file is used to check for emotion alerts and return JSON data
session_start();
require_once 'koneksi.php';
require_once 'fungsi_helper.php';

header('Content-Type: application/json');

// Function to check for emotion alerts
function checkEmotionAlerts($conn, $class_session_id = null, $threshold_percentage = 20, $time_window_minutes = 30) {
    try {
        // Base query to check for negative emotions (Stres and Lelah)
        $baseQuery = "
            SELECT 
                cs.id as class_session_id,
                c.class_name,
                c.id as class_id,
                u.name as dosen_name,
                COUNT(CASE WHEN e.emotion IN ('Stres', 'Lelah') THEN 1 END) as negative_count,
                COUNT(CASE WHEN e.emotion = 'Stres' THEN 1 END) as stress_count,
                COUNT(CASE WHEN e.emotion = 'Lelah' THEN 1 END) as tired_count,
                COUNT(e.id) as total_count,
                (COUNT(CASE WHEN e.emotion IN ('Stres', 'Lelah') THEN 1 END) * 100.0 / COUNT(e.id)) as negative_percentage,
                MAX(e.timestamp) as latest_timestamp,
                GROUP_CONCAT(DISTINCT CASE WHEN e.emotion IN ('Stres', 'Lelah') THEN us.name END SEPARATOR ', ') as affected_students
            FROM 
                emotions e
            JOIN 
                class_sessions cs ON e.class_session_id = cs.id
            JOIN 
                classes c ON cs.class_id = c.id
            JOIN 
                users u ON c.dosen_id = u.id
            JOIN 
                users us ON e.user_id = us.id
            WHERE 
                cs.status = 'active'
                AND e.timestamp >= DATE_SUB(NOW(), INTERVAL ? MINUTE)
        ";
        
        $params = [$time_window_minutes];
        
        // If specific class session is provided
        if ($class_session_id) {
            $baseQuery .= " AND cs.id = ?";
            $params[] = $class_session_id;
        }
        
        $baseQuery .= "
            GROUP BY 
                cs.id, c.class_name, c.id, u.name
            HAVING 
                negative_percentage >= ?
                AND total_count >= 3
            ORDER BY 
                negative_percentage DESC
        ";
        
        $params[] = $threshold_percentage;
        
        $stmt = $conn->prepare($baseQuery);
        $stmt->execute($params);
        $alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Process alerts to add severity levels
        foreach ($alerts as &$alert) {
            // Severity levels:
            // 1. High: >= 60% negative emotions
            // 2. Medium: >= 40% negative emotions
            // 3. Low: >= 20% negative emotions
            if ($alert['negative_percentage'] >= 60) {
                $alert['severity'] = 'high';
                $alert['severity_text'] = 'Tinggi';
            } elseif ($alert['negative_percentage'] >= 40) {
                $alert['severity'] = 'medium';
                $alert['severity_text'] = 'Sedang';
            } else {
                $alert['severity'] = 'low';
                $alert['severity_text'] = 'Rendah';
            }
            
            // Format percentage
            $alert['negative_percentage'] = round($alert['negative_percentage'], 1);
        }
        
        return [
            'status' => 'success',
            'alerts' => $alerts,
            'count' => count($alerts)
        ];
    } catch (PDOException $e) {
        return [
            'status' => 'error',
            'message' => $e->getMessage()
        ];
    }
}

// Get dosen_id from session if available
$dosen_id = isset($_SESSION['user_id']) && $_SESSION['role'] === 'Dosen' ? $_SESSION['user_id'] : null;

// Get class_session_id from request if available
$class_session_id = isset($_GET['class_session_id']) ? (int)$_GET['class_session_id'] : null;

// Get alerts
$result = checkEmotionAlerts($conn, $class_session_id);

echo json_encode($result);
?>
