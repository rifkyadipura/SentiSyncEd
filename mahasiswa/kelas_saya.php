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

// Handle class unenrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['unenroll'])) {
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_SANITIZE_NUMBER_INT);
    
    try {
        $stmt = $conn->prepare("DELETE FROM class_members WHERE class_id = ? AND user_id = ?");
        $stmt->execute([$class_id, $user_id]);
        
        logAction($conn, $user_id, "Unenrolled from class ID: " . $class_id);
        $success_message = "Berhasil keluar dari kelas.";
    } catch (PDOException $e) {
        $error_message = "Terjadi kesalahan saat keluar dari kelas.";
    }
}

// Get enrolled classes
try {
    $stmt = $conn->prepare("
        SELECT c.*, u.name as dosen_name,
        (SELECT COUNT(*) FROM class_members WHERE class_id = c.id) as student_count
        FROM classes c
        JOIN users u ON c.dosen_id = u.id
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ?
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
    <title>Kelas Saya - SentiSyncEd</title>
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
        .page-title {
            color: #4A90E2;
            margin-bottom: 2rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
        }
        .class-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
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
        .unenroll-btn {
            width: 100%;
            padding: 0.8rem;
            border: none;
            border-radius: 8px;
            background: #dc3545;
            color: white;
            font-weight: 600;
            cursor: pointer;
            transition: background 0.3s ease;
        }
        .unenroll-btn:hover {
            background: #c82333;
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
        .no-classes {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        .no-classes i {
            font-size: 3rem;
            color: #4A90E2;
            margin-bottom: 1rem;
        }
        .no-classes h3 {
            color: #666;
            margin-bottom: 1rem;
        }
        .no-classes a {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.8rem 1.5rem;
            background: #4A90E2;
            color: white;
            text-decoration: none;
            border-radius: 8px;
            font-weight: 600;
            transition: background 0.3s ease;
        }
        .no-classes a:hover {
            background: #357ABD;
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
                    <a href="kelas_saya.php" class="active">
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
            <h1 class="page-title">
                <i class="fas fa-book"></i>
                Kelas Saya
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

            <?php if (empty($classes)): ?>
                <div class="no-classes">
                    <i class="fas fa-book-open"></i>
                    <h3>Anda belum terdaftar di kelas manapun</h3>
                    <a href="pilih_kelas.php">
                        <i class="fas fa-plus"></i>
                        Pilih Kelas
                    </a>
                </div>
            <?php else: ?>
                <div class="classes-grid">
                    <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <h3><?php echo htmlspecialchars($class['class_name']); ?></h3>
                            
                            <div class="class-info">
                                <p>
                                    <i class="fas fa-user-tie"></i>
                                    <?php echo htmlspecialchars($class['dosen_name']); ?>
                                </p>
                                <p>
                                    <i class="fas fa-users"></i>
                                    <?php echo $class['student_count']; ?> mahasiswa terdaftar
                                </p>
                                <?php if ($class['description']): ?>
                                    <p>
                                        <i class="fas fa-info-circle"></i>
                                        <?php echo htmlspecialchars($class['description']); ?>
                                    </p>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari kelas ini?');">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="unenroll" class="unenroll-btn">
                                    <i class="fas fa-sign-out-alt"></i>
                                    Keluar dari Kelas
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
