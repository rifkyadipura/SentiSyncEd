<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Get classes the student is enrolled in
try {
    $stmt = $conn->prepare("
        SELECT c.id, c.class_name
        FROM classes c
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ?
        ORDER BY c.class_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil daftar kelas.";
    $classes = [];
}

// Handle support note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    // Validate class selection
    if (empty($class_id)) {
        $error_message = "Silakan pilih kelas terlebih dahulu.";
    } else {
        try {
            // Begin transaction for data consistency
            $conn->beginTransaction();
            
            // First, ensure we have a valid session in the sessions table
            $sessionId = null;
            
            // 1. Check if there's already a valid session in the sessions table
            $sessionStmt = $conn->prepare("SELECT id FROM sessions ORDER BY id DESC LIMIT 1");
            $sessionStmt->execute();
            $sessionRow = $sessionStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sessionRow) {
                // Use existing session
                $sessionId = $sessionRow['id'];
            } else {
                // Create a new session if none exists
                $sessionStmt = $conn->prepare("INSERT INTO sessions (start_time) VALUES (NOW())");
                $sessionStmt->execute();
                $sessionId = $conn->lastInsertId();
            }
            
            // Check if class_id column exists in support_notes table
            $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
            $stmt->execute();
            $class_id_exists = $stmt->rowCount() > 0;
            
            if ($class_id_exists) {
                $stmt = $conn->prepare("INSERT INTO support_notes (user_id, class_id, message, session_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $class_id, $message, $sessionId]);
            } else {
                // Fallback if class_id column doesn't exist
                $stmt = $conn->prepare("INSERT INTO support_notes (user_id, message, session_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $message, $sessionId]);
            }
            
            // Commit the transaction
            $conn->commit();
            
            logAction($conn, $user_id, "Added support note for class ID: $class_id");
            $success_message = "Curhat berhasil disimpan!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "Terjadi kesalahan saat menyimpan curhat: " . $e->getMessage();
        }
    }
}

// Get previous notes with class information
try {
    // Check if class_id column exists in support_notes table
    $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
    $stmt->execute();
    $class_id_exists = $stmt->rowCount() > 0;
    
    if ($class_id_exists) {
        $stmt = $conn->prepare("
            SELECT sn.message, sn.timestamp, c.class_name
            FROM support_notes sn
            LEFT JOIN classes c ON sn.class_id = c.id
            WHERE sn.user_id = ? 
            ORDER BY sn.timestamp DESC 
            LIMIT 5
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT sn.message, sn.timestamp, NULL as class_name
            FROM support_notes sn
            WHERE sn.user_id = ? 
            ORDER BY sn.timestamp DESC 
            LIMIT 5
        ");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $previous_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil riwayat curhat: " . $e->getMessage();
    $previous_notes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Curhat - SentiSyncEd</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: white;
            padding: 2rem;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar h3 {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover {
            background: #f0f7ff;
            color: #4A90E2;
        }
        .sidebar-menu a.active {
            background: #4A90E2;
            color: white;
        }
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f9f9f9;
        }
        .curhat-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 800px;
            margin: 0 auto;
        }
        .curhat-form h2 {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #666;
            font-weight: 600;
        }
        .form-group textarea {
            width: 100%;
            padding: 1rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            color: #666;
            transition: all 0.3s ease;
            min-height: 150px;
            resize: vertical;
        }
        .form-group textarea:focus {
            border-color: #4A90E2;
            outline: none;
        }
        .submit-btn {
            background: #4A90E2;
            color: white;
            border: none;
            padding: 1rem 2rem;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            width: 100%;
        }
        .submit-btn:hover {
            background: #357ABD;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #E8F5E9;
            color: #2E7D32;
            border: 1px solid #A5D6A7;
        }
        .alert-error {
            background: #FFEBEE;
            color: #C62828;
            border: 1px solid #FFCDD2;
        }
        .previous-notes {
            margin-top: 2rem;
        }
        .previous-notes h3 {
            color: #666;
            margin-bottom: 1rem;
            font-size: 1.2rem;
        }
        .note-card {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
            border-left: 4px solid #4A90E2;
        }
        .note-time {
            color: #999;
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .note-message {
            color: #666;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>
                <i class="fas fa-graduation-cap"></i>
                Menu Mahasiswa
            </h3>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_mahasiswa.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="input_emosi.php">
                        <i class="fas fa-smile"></i>
                        Input Emosi
                    </a>
                </li>
                <li>
                    <a href="tulis_curhat.php" class="active">
                        <i class="fas fa-comment-dots"></i>
                        Tulis Curhat
                    </a>
                </li>
                <li>
                    <a href="grafik_emosi.php">
                        <i class="fas fa-chart-line"></i>
                        Grafik Emosi
                    </a>
                </li>
                <li>
                    <a href="pilih_kelas.php">
                        <i class="fas fa-chalkboard"></i>
                        Pilih Kelas
                    </a>
                </li>
                <li>
                    <a href="kelas_saya.php">
                        <i class="fas fa-book"></i>
                        Kelas Saya
                    </a>
                </li>
                <li>
                    <a href="../login.php?logout=1">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <div class="curhat-form">
                <h2>
                    <i class="fas fa-comment-dots"></i>
                    Tulis Curhat
                </h2>

                <?php if ($success_message): ?>
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle"></i>
                        <?php echo $success_message; ?>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-error">
                        <i class="fas fa-exclamation-circle"></i>
                        <?php echo $error_message; ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="" id="curhatForm">
                    <div class="form-group">
                        <label for="class_id">Pilih Kelas:</label>
                        <select name="class_id" id="class_id" class="form-select" required onchange="showMessageInput()">
                            <option value="">-- Pilih Kelas --</option>
                            <?php foreach ($classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group" id="messageInputContainer" style="display: none;">
                        <label for="message">Bagaimana perasaan Anda? Ceritakan di sini:</label>
                        <textarea name="message" id="message" required placeholder="Tuliskan curhat Anda..."></textarea>
                    </div>
                    <button type="submit" class="submit-btn" id="submitButton" style="display: none;">
                        <i class="fas fa-paper-plane"></i>
                        Kirim Curhat
                    </button>
                </form>
                
                <script>
                function showMessageInput() {
                    var classSelect = document.getElementById('class_id');
                    var messageContainer = document.getElementById('messageInputContainer');
                    var submitButton = document.getElementById('submitButton');
                    
                    if (classSelect.value !== '') {
                        messageContainer.style.display = 'block';
                        submitButton.style.display = 'block';
                    } else {
                        messageContainer.style.display = 'none';
                        submitButton.style.display = 'none';
                    }
                }
                
                // Check if class is already selected (e.g., after page refresh)
                document.addEventListener('DOMContentLoaded', function() {
                    showMessageInput();
                });
                </script>

                <?php if (!empty($previous_notes)): ?>
                    <div class="previous-notes">
                        <h3>Riwayat Curhat Terakhir</h3>
                        <?php foreach ($previous_notes as $note): ?>
                            <div class="note-card">
                                <div class="note-time">
                                    <i class="fas fa-clock"></i>
                                    <?php echo date('d/m/Y H:i', strtotime($note['timestamp'])); ?>
                                    <?php if (!empty($note['class_name'])): ?>
                                        <span class="badge bg-primary ms-2"><?php echo htmlspecialchars($note['class_name']); ?></span>
                                    <?php endif; ?>
                                </div>
                                <div class="note-message">
                                    <?php echo htmlspecialchars($note['message']); ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
