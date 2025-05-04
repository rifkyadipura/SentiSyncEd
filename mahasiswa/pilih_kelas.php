<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a student
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle class enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['enroll'])) {
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        // Check if already enrolled
        $check_stmt = $conn->prepare("SELECT id FROM class_members WHERE class_id = ? AND user_id = ?");
        $check_stmt->execute([$class_id, $user_id]);
        
        if ($check_stmt->rowCount() > 0) {
            $error_message = "Anda sudah terdaftar di kelas ini.";
        } else {
            // Enroll in the class
            $enroll_stmt = $conn->prepare("INSERT INTO class_members (class_id, user_id) VALUES (?, ?)");
            $enroll_stmt->execute([$class_id, $user_id]);
            
            // Log the action
            logAction($conn, $user_id, "Enrolled in class ID: " . $class_id);
            $success_message = "Berhasil mendaftar ke kelas!";
        }
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan saat mendaftar ke kelas.";
    }
}

// Get available classes (excluding already enrolled ones)
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.name as dosen_name, 
        (SELECT COUNT(*) FROM class_members WHERE class_id = c.id) as student_count,
        CASE WHEN cm.id IS NOT NULL THEN 1 ELSE 0 END as is_enrolled
        FROM classes c
        JOIN users u ON c.dosen_id = u.id
        LEFT JOIN class_members cm ON c.id = cm.class_id AND cm.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil daftar kelas.";
    $classes = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kelas - SentiSyncEd</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        .container {
            max-width: 1200px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .class-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-card h3 {
            color: #4A90E2;
            margin: 0 0 1rem 0;
            font-size: 1.2rem;
        }
        .class-info {
            margin-bottom: 1rem;
            color: #666;
        }
        .class-info p {
            margin: 0.5rem 0;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .class-info i {
            color: #4A90E2;
            width: 20px;
        }
        .enroll-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: #4A90E2;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .enroll-btn:hover {
            background: #357ABD;
        }
        .enroll-btn:disabled {
            background: #ccc;
            cursor: not-allowed;
        }
        .enrolled-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            background: #4CAF50;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
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
        .page-title {
            color: #4A90E2;
            margin-bottom: 1rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .student-count {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .back-btn {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f0f0f0;
            border-radius: 8px;
            color: #666;
            text-decoration: none;
            margin-bottom: 1rem;
            transition: all 0.3s ease;
        }
        .back-btn:hover {
            background: #e0e0e0;
        }
    </style>
</head>
<body>
    <div class="container">
        <a href="dashboard_mahasiswa.php" class="back-btn">
            <i class="fas fa-arrow-left"></i>
            Kembali ke Dashboard
        </a>

        <h1 class="page-title">
            <i class="fas fa-chalkboard-teacher"></i>
            Pilih Kelas
        </h1>

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

        <div class="classes-grid">
            <?php foreach ($classes as $class): ?>
                <div class="class-card">
                    <?php if ($class['is_enrolled']): ?>
                        <span class="enrolled-badge">
                            <i class="fas fa-check"></i> Terdaftar
                        </span>
                    <?php endif; ?>
                    
                    <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                    
                    <div class="class-info">
                        <p>
                            <i class="fas fa-user-tie"></i>
                            <?php echo htmlspecialchars($class['dosen_name']); ?>
                        </p>
                        <p>
                            <i class="fas fa-users"></i>
                            <span class="student-count"><?php echo $class['student_count']; ?> mahasiswa terdaftar</span>
                        </p>
                        <?php if ($class['description']): ?>
                            <p>
                                <i class="fas fa-info-circle"></i>
                                <?php echo htmlspecialchars($class['description']); ?>
                            </p>
                        <?php endif; ?>
                    </div>

                    <form method="POST" action="">
                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                        <button type="submit" name="enroll" class="enroll-btn" <?php echo $class['is_enrolled'] ? 'disabled' : ''; ?>>
                            <?php echo $class['is_enrolled'] ? 'Sudah Terdaftar' : 'Daftar Kelas'; ?>
                        </button>
                    </form>
                </div>
            <?php endforeach; ?>

            <?php if (empty($classes)): ?>
                <p>Tidak ada kelas yang tersedia saat ini.</p>
            <?php endif; ?>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
