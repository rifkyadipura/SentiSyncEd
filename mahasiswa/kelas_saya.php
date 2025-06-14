<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelas Saya - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
        .class-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
            height: 100%;
            display: flex;
            flex-direction: column;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 6px 12px rgba(0, 0, 0, 0.15);
        }
        
        .class-card .card-body {
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .class-info {
            flex: 1;
            margin-bottom: 1rem;
        }
        
        .class-info p {
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }
        
        .class-info i {
            margin-right: 8px;
            width: 20px;
            color: var(--bs-primary);
        }
        
        .unenroll-btn {
            width: 100%;
            margin-top: auto;
        }
        
        .no-classes {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 10px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .no-classes i {
            font-size: 3rem;
            color: var(--bs-primary);
            margin-bottom: 1rem;
        }
        
        .no-classes h3 {
            color: #666;
            margin-bottom: 1rem;
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
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
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
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
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
            <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>
    
    <div class="content-wrapper">
        <div class="container-fluid px-0">
            <h1 class="page-title mb-4">Kelas Saya</h1>

            <?php if ($success_message): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i>
                    <?php echo $success_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($error_message): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <?php echo $error_message; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if (empty($classes)): ?>
                <div class="card">
                    <div class="card-body no-classes text-center py-5">
                        <i class="bi bi-journal-x display-1 text-muted mb-3"></i>
                        <h3 class="mb-4">Anda belum terdaftar di kelas manapun</h3>
                        <a href="pilih_kelas.php" class="btn btn-primary btn-lg">
                            <i class="bi bi-plus-circle me-2"></i>
                            Pilih Kelas
                        </a>
                    </div>
                </div>
            <?php else: ?>
                <div class="row g-4">
                    <?php foreach ($classes as $class): ?>
                        <div class="col-lg-4 col-md-6">
                            <div class="card class-card h-100">
                                <div class="card-header bg-light border-primary">
                                    <h5 class="card-title mb-0 text-primary fw-bold"><?php echo htmlspecialchars($class['class_name']); ?></h5>
                                </div>
                                <div class="card-body">
                                    <div class="class-info">
                                        <p>
                                            <i class="bi bi-person-badge"></i>
                                            <span><?php echo htmlspecialchars($class['dosen_name']); ?></span>
                                        </p>
                                        <p>
                                            <i class="bi bi-people"></i>
                                            <span><?php echo $class['student_count']; ?> mahasiswa terdaftar</span>
                                        </p>
                                        <?php if ($class['description']): ?>
                                            <p>
                                                <i class="bi bi-info-circle"></i>
                                                <span><?php echo htmlspecialchars($class['description']); ?></span>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                    
                                    <form method="POST" action="" onsubmit="return confirm('Apakah Anda yakin ingin keluar dari kelas ini?');">
                                        <input type="hidden" name="class_id" value="<?php echo $class['id']; ?>">
                                        <button type="submit" name="unenroll" class="btn btn-danger unenroll-btn">
                                            <i class="bi bi-box-arrow-left me-2"></i>
                                            Keluar dari Kelas
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copyright Footer -->
    <footer class="py-3 text-center text-muted border-top" style="position: fixed; bottom: 0; width: 100%; background-color: #f8f9fa; z-index: 1000;">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Padding to prevent content from being hidden behind fixed footer -->
    <div style="height: 60px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>

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
