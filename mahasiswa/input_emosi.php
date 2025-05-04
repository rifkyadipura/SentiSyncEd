<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Get active class sessions for classes where the student is enrolled
try {
    $stmt = $conn->prepare("
        SELECT cs.id as class_session_id, cs.start_time, c.class_name, u.name as dosen_name
        FROM class_sessions cs
        JOIN classes c ON cs.class_id = c.id
        JOIN users u ON c.dosen_id = u.id
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ? 
        AND cs.status = 'active'
        AND cs.end_time IS NULL
        ORDER BY cs.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil sesi kelas.";
    $active_sessions = [];
}

// Handle emotion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emotion = filter_input(INPUT_POST, 'emotion', FILTER_SANITIZE_STRING);
    $class_session_id = filter_input(INPUT_POST, 'class_session_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Verify session exists and is active for this student
    $stmt = $conn->prepare("
        SELECT cs.id FROM class_sessions cs
        JOIN class_members cm ON cs.class_id = cm.class_id
        WHERE cs.id = ? AND cm.user_id = ? AND cs.status = 'active' AND cs.end_time IS NULL
    ");
    $stmt->execute([$class_session_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // First, check if we already have a session for this class_session_id
            $sessionStmt = $conn->prepare("SELECT id FROM sessions WHERE id = (SELECT MIN(session_id) FROM emotions WHERE class_session_id = ? LIMIT 1)");
            $sessionStmt->execute([$class_session_id]);
            $sessionId = $sessionStmt->fetchColumn();
            
            // If no session exists, create one
            if (!$sessionId) {
                // Get the start_time from class_sessions to use for the session
                $timeStmt = $conn->prepare("SELECT start_time FROM class_sessions WHERE id = ?");
                $timeStmt->execute([$class_session_id]);
                $startTime = $timeStmt->fetchColumn() ?: date('Y-m-d H:i:s');
                
                // Create a new session
                $sessionStmt = $conn->prepare("INSERT INTO sessions (start_time) VALUES (?)");
                $sessionStmt->execute([$startTime]);
                $sessionId = $conn->lastInsertId();
            }
            
            // Now insert the emotion with the valid session_id
            $stmt = $conn->prepare("INSERT INTO emotions (user_id, emotion, class_session_id, session_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $emotion, $class_session_id, $sessionId]);
            
            // Commit transaction
            $conn->commit();
            
            logAction($conn, $user_id, "Recorded emotion: " . $emotion . " for class_session_id: " . $class_session_id);
            $success_message = "Emosi berhasil disimpan!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "Terjadi kesalahan saat menyimpan emosi: " . $e->getMessage();
        }
    } else {
        $error_message = "Sesi kelas tidak valid atau sudah berakhir.";
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Emosi - SentiSyncEd</title>
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
        .emotion-form {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            max-width: 600px;
            margin: 0 auto;
        }
        .no-sessions {
            text-align: center;
            padding: 2rem;
            color: #666;
        }
        .no-sessions i {
            font-size: 3rem;
            color: #4A90E2;
            margin-bottom: 1rem;
        }
        .no-sessions p {
            margin: 0.5rem 0;
        }
        .class-select {
            margin-bottom: 1rem;
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            color: #666;
            transition: all 0.3s ease;
        }
        .class-select:focus {
            border-color: #4A90E2;
            outline: none;
        }
        .emotion-form h2 {
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
        .form-group select {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e1e1;
            border-radius: 8px;
            font-size: 1rem;
            color: #666;
            transition: all 0.3s ease;
        }
        .form-group select:focus {
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
        .emotion-icon {
            font-size: 2rem;
            margin-right: 0.5rem;
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
                    <a href="input_emosi.php" class="active">
                        <i class="fas fa-smile"></i>
                        Input Emosi
                    </a>
                </li>
                <li>
                    <a href="tulis_curhat.php">
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
            <div class="emotion-form">
                <h2>
                    <i class="fas fa-smile emotion-icon"></i>
                    Input Emosi
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

                <?php if (empty($active_sessions)): ?>
                    <div class="no-sessions">
                        <i class="fas fa-info-circle"></i>
                        <p>Tidak ada sesi kelas yang aktif saat ini.</p>
                        <p>Silakan tunggu dosen membuka sesi kelas.</p>
                    </div>
                <?php else: ?>
                    <form method="POST" action="">
                        <div class="form-group">
                            <label for="class_session_id">Pilih Kelas yang Sedang Sesi:</label>
                            <select name="class_session_id" id="class_session_id" required class="class-select">
                                <option value="">Pilih Kelas</option>
                                <?php foreach ($active_sessions as $session): ?>
                                    <option value="<?php echo $session['class_session_id']; ?>">
                                        <?php echo htmlspecialchars($session['class_name']); ?> (Dosen: <?php echo htmlspecialchars($session['dosen_name']); ?>, Mulai: <?php echo date('H:i', strtotime($session['start_time'])); ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="emotion">Bagaimana perasaan Anda saat ini?</label>
                            <select name="emotion" id="emotion" required>
                                <option value="">Pilih Emosi</option>
                                <option value="Senang">😊 Senang</option>
                                <option value="Stres">😰 Stres</option>
                                <option value="Lelah">😫 Lelah</option>
                                <option value="Netral">😐 Netral</option>
                            </select>
                        </div>
                        <button type="submit" class="submit-btn">
                            <i class="fas fa-save"></i>
                            Simpan Emosi
                        </button>
                    </form>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
