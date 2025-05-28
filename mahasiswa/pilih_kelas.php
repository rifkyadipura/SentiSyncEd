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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pilih Kelas - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
        .classes-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 1rem;
        }
        .class-card {
            background: white;
            border-radius: 10px;
            padding: 1.5rem;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
            position: relative;
            overflow: hidden;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        .class-card:hover {
            transform: translateY(-5px);
        }
        .class-card h3 {
            color: var(--primary-color);
            margin: 0 0 0.5rem 0;
            font-size: 1.2rem;
            word-wrap: break-word;
            overflow-wrap: break-word;
            width: calc(100% - 100px); /* Memberikan ruang untuk badge */
            line-height: 1.4;
            display: block;
        }
        .class-info {
            margin-bottom: 1rem;
            color: #666;
            flex-grow: 1;
        }
        .class-info p {
            margin: 0.5rem 0;
            display: flex;
            align-items: flex-start;
            gap: 0.5rem;
        }
        .class-info i {
            color: var(--primary-color);
            width: 20px;
            margin-top: 4px;
            flex-shrink: 0;
        }
        .enrolled-badge {
            position: absolute;
            top: 0;
            right: 0;
            background: #4CAF50;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 0.3rem;
            z-index: 5;
            white-space: nowrap;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .student-count {
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>

    <!-- Mobile Navbar -->
    <div class="mobile-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-light me-2" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="text-white mb-0">SentiSyncEd</h4>
        </div>
        
        <!-- Profile Dropdown for Mobile -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.15) !important;">
            <h2 class="mb-0" style="color:#fff; font-weight:700; font-size:24px;">SentiSyncEd</h2>
        </div>
        <nav class="nav flex-column py-3">
            <a href="dashboard_mahasiswa.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-house me-2"></i> Dashboard
            </a>
            <a href="input_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-emoji-smile me-2"></i> Input Emosi
            </a>
            <a href="tulis_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-chat-dots me-2"></i> Tulis Curhat
            </a>
            <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
            </a>
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <!-- User Dropdown in Content Area -->
    <div class="user-dropdown dropdown d-none d-lg-block">
        <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>
    
    <div class="content-wrapper">
        <div class="container-fluid px-0">
            <h1 class="page-title mb-4">Pilih Kelas</h1>

            <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo $success_message; ?>
            </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo $error_message; ?>
            </div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-journal-plus me-2 text-primary"></i>
                        Kelas yang Tersedia
                    </h5>
                </div>
                <div class="card-body">
                    <div class="classes-grid">
                        <?php foreach ($classes as $class): ?>
                        <div class="class-card">
                            <div class="position-relative mb-3">
                                <h3 title="<?php echo htmlspecialchars($class['class_name']); ?>"><?php echo htmlspecialchars($class['class_name']); ?></h3>
                                <?php if ($class['is_enrolled']): ?>
                                <div class="enrolled-badge">
                                    <i class="bi bi-check-circle"></i> Terdaftar
                                </div>
                                <?php endif; ?>
                            </div>
                            
                            <div class="class-info">
                                <p>
                                    <i class="bi bi-person-workspace"></i>
                                    <?php echo htmlspecialchars($class['dosen_name']); ?>
                                </p>
                                <p>
                                    <i class="bi bi-people"></i>
                                    <span class="student-count"><?php echo $class['student_count']; ?> mahasiswa terdaftar</span>
                                </p>
                                <?php if ($class['description']): ?>
                                <p>
                                    <i class="bi bi-info-circle"></i>
                                    <?php echo htmlspecialchars($class['description']); ?>
                                </p>
                                <?php endif; ?>
                            </div>

                            <form method="POST" action="">
                                <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                <button type="submit" name="enroll" class="btn btn-primary w-100" <?php echo $class['is_enrolled'] ? 'disabled' : ''; ?>>
                                    <?php echo $class['is_enrolled'] ? 'Sudah Terdaftar' : 'Daftar Kelas'; ?>
                                </button>
                            </form>
                        </div>
                        <?php endforeach; ?>

                        <?php if (empty($classes)): ?>
                        <div class="alert alert-info w-100">
                            <i class="bi bi-info-circle me-2"></i>
                            <p class="mb-0">Tidak ada kelas yang tersedia saat ini.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            const navLinks = document.querySelectorAll('.sidebar .nav-link');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }

            // Close sidebar when a nav link is clicked on mobile
            navLinks.forEach(link => {
                link.addEventListener('click', function() {
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('show');
                        overlay.classList.remove('show');
                    }
                });
            });

            // Handle window resize
            window.addEventListener('resize', function() {
                if (window.innerWidth >= 992) {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                }
            });
        });
    </script>
</body>

</html>
