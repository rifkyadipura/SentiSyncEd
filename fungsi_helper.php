<?php
require_once 'koneksi.php';

// Function to sanitize input
function sanitizeInput($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Function to log user actions
function logAction($conn, $user_id, $action) {
    try {
        $stmt = $conn->prepare("INSERT INTO logs (user_id, action) VALUES (?, ?)");
        $stmt->execute([$user_id, $action]);
        return true;
    } catch (PDOException $e) {
        return false;
    }
}

function getEnrolledClasses($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT c.* 
            FROM classes c
            JOIN class_members cm ON c.id = cm.class_id
            WHERE cm.user_id = ?
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get user emotions for a specific period
function getUserEmotions($userId, $startDate = null, $endDate = null) {
    global $conn;
    $query = "SELECT e.*, s.start_time, s.end_time 
              FROM emotions e 
              JOIN sessions s ON e.session_id = s.id 
              WHERE e.user_id = ?";
    $params = [$userId];
    
    if ($startDate) {
        $query .= " AND e.timestamp >= ?";
        $params[] = $startDate;
    }
    if ($endDate) {
        $query .= " AND e.timestamp <= ?";
        $params[] = $endDate;
    }
    
    $query .= " ORDER BY e.timestamp DESC";
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

// Function to get user emotions filtered by class
function getUserEmotionsByClass($userId, $classId = null) {
    global $conn;
    
    // If classId is null or 0, get all emotions across all classes
    if (!$classId) {
        $query = "SELECT e.*, cs.start_time, c.class_name, u.name as dosen_name
                FROM emotions e
                LEFT JOIN class_sessions cs ON e.class_session_id = cs.id
                LEFT JOIN classes c ON cs.class_id = c.id
                LEFT JOIN users u ON c.dosen_id = u.id
                WHERE e.user_id = ?
                ORDER BY e.timestamp DESC";
        $params = [$userId];
    } else {
        $query = "SELECT e.*, cs.start_time, c.class_name, u.name as dosen_name
                FROM emotions e
                JOIN class_sessions cs ON e.class_session_id = cs.id
                JOIN classes c ON cs.class_id = c.id
                JOIN users u ON c.dosen_id = u.id
                WHERE e.user_id = ? AND c.id = ?
                ORDER BY e.timestamp DESC";
        $params = [$userId, $classId];
    }
    
    $stmt = $conn->prepare($query);
    $stmt->execute($params);
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Function to get support notes
function getSupportNotes($userId, $isTeacher = false) {
    global $conn;
    $query = "SELECT n.*, u.name as author_name 
              FROM support_notes n 
              JOIN users u ON n.user_id = u.id";
    
    if (!$isTeacher) {
        $query .= " WHERE n.user_id = ?";
        $stmt = $conn->prepare($query);
        $stmt->execute([$userId]);
    } else {
        $stmt = $conn->prepare($query);
        $stmt->execute();
    }
    return $stmt->fetchAll();
}

// Function to generate PDF report
function generatePDFReport($userId = null) {
    require_once('tcpdf/tcpdf.php');
    
    // Create new PDF document
    $pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);
    
    // Set document information
    $pdf->SetCreator('SentiSyncEd');
    $pdf->SetAuthor('SentiSyncEd System');
    $pdf->SetTitle('Emotion Report');
    
    // Add a page
    $pdf->AddPage();
    
    // Get emotion data
    $emotions = getUserEmotions($userId);
    
    // Add content to PDF
    // ... (implementation details for PDF generation)
    
    return $pdf;
}

// Function to check for negative emotions threshold
function checkNegativeEmotions($userId) {
    global $conn;
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM emotions 
        WHERE user_id = ? 
        AND emotion IN ('Stres', 'Lelah') 
        AND timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    ");
    $stmt->execute([$userId]);
    $result = $stmt->fetch();
    return $result['count'] >= 3; // Return true if 3 or more negative emotions in last 24 hours
}
// Function to check if user is SuperAdmin
function isSuperAdmin() {
    return getUserRole() === 'SuperAdmin';
}

// Function to check if user is admin (for backward compatibility)
function isAdmin() {
    return getUserRole() === 'SuperAdmin';
}

// Function to get positive emotions count for user
function getPositiveEmotionsCount($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM emotions 
            WHERE user_id = ? 
            AND emotion IN ('Senang', 'Bahagia')
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get negative emotions count for user
function getNegativeEmotionsCount($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM emotions 
            WHERE user_id = ? 
            AND emotion IN ('Sedih', 'Marah', 'Takut')
        ");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get support notes count for user
function getSupportNotesCount($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM support_notes WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get last emotion for user
function getLastEmotion($user_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT * 
            FROM emotions 
            WHERE user_id = ? 
            ORDER BY timestamp DESC 
            LIMIT 1
        ");
        $stmt->execute([$user_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return null;
    }
}

// Function to get recent support notes for user
function getRecentSupportNotes($user_id, $limit = 5) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT sn.*, u.name as sender_name 
            FROM support_notes sn 
            JOIN users u ON sn.sender_id = u.id 
            WHERE sn.user_id = ? 
            ORDER BY sn.timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$user_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get dosen's class count
function getDosenClassCount($dosen_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("SELECT COUNT(*) as count FROM classes WHERE dosen_id = ?");
        $stmt->execute([$dosen_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get dosen's student count
function getDosenStudentCount($dosen_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(DISTINCT cm.user_id) as count 
            FROM classes c 
            JOIN class_members cm ON c.id = cm.class_id 
            WHERE c.dosen_id = ?
        ");
        $stmt->execute([$dosen_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get dosen's support notes count
function getDosenSupportNotesCount($dosen_id) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT COUNT(*) as count 
            FROM support_notes sn 
            JOIN users u ON sn.user_id = u.id 
            JOIN class_members cm ON u.id = cm.user_id 
            WHERE cm.class_id IN (
                SELECT id FROM classes WHERE dosen_id = ?
            )
        ");
        $stmt->execute([$dosen_id]);
        $result = $stmt->fetch();
        return $result['count'] ?? 0;
    } catch (PDOException $e) {
        return 0;
    }
}

// Function to get last class emotions
function getLastClassEmotions($dosen_id, $limit = 5) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT e.*, u.name as student_name 
            FROM emotions e 
            JOIN users u ON e.user_id = u.id 
            JOIN class_members cm ON u.id = cm.user_id 
            WHERE cm.class_id IN (
                SELECT id FROM classes WHERE dosen_id = ?
            )
            ORDER BY e.timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$dosen_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to get dosen's support notes
function getDosenSupportNotes($dosen_id, $limit = 5) {
    global $conn;
    try {
        $stmt = $conn->prepare("
            SELECT sn.*, u.name as student_name 
            FROM support_notes sn 
            JOIN users u ON sn.user_id = u.id 
            JOIN class_members cm ON u.id = cm.user_id 
            WHERE cm.class_id IN (
                SELECT id FROM classes WHERE dosen_id = ?
            )
            ORDER BY sn.timestamp DESC 
            LIMIT ?
        ");
        $stmt->execute([$dosen_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e) {
        return [];
    }
}

// Function to format time ago
function timeAgo($timestamp) {
    $diff = time() - strtotime($timestamp);
    
    if ($diff < 60) {
        return $diff . ' detik yang lalu';
    } elseif ($diff < 3600) {
        return floor($diff / 60) . ' menit yang lalu';
    } elseif ($diff < 86400) {
        return floor($diff / 3600) . ' jam yang lalu';
    } elseif ($diff < 604800) {
        return floor($diff / 86400) . ' hari yang lalu';
    } elseif ($diff < 2678400) {
        return floor($diff / 604800) . ' minggu yang lalu';
    }
    return date('d M Y', strtotime($timestamp));
}
?>
