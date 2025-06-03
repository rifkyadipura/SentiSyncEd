<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Verify SuperAdmin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';
$mahasiswa = null;
$availableClasses = [];
$enrolledClasses = [];

// Check if mahasiswa ID is provided
if (!isset($_GET['id'])) {
    header('Location: manage_mahasiswa.php');
    exit();
}

$mahasiswaId = (int)$_GET['id'];

// Get mahasiswa details
try {
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'Mahasiswa'");
    $stmt->execute([$mahasiswaId]);
    $mahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$mahasiswa) {
        header('Location: manage_mahasiswa.php');
        exit();
    }
    
    // Get classes the mahasiswa is already enrolled in
    $stmt = $conn->prepare("
        SELECT cm.id as membership_id, c.id, c.class_name, c.description, u.name as dosen_name, cm.joined_at
        FROM class_members cm
        JOIN classes c ON cm.class_id = c.id
        JOIN users u ON c.dosen_id = u.id
        WHERE cm.user_id = ?
        ORDER BY c.class_name
    ");
    $stmt->execute([$mahasiswaId]);
    $enrolledClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get available classes (not enrolled yet)
    $stmt = $conn->prepare("
        SELECT c.id, c.class_name, c.description, u.name as dosen_name
        FROM classes c
        JOIN users u ON c.dosen_id = u.id
        WHERE c.id NOT IN (
            SELECT class_id FROM class_members WHERE user_id = ?
        )
        ORDER BY c.class_name
    ");
    $stmt->execute([$mahasiswaId]);
    $availableClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
} catch (PDOException $e) {
    $error = "Error: " . $e->getMessage();
}

// Handle enrollment
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'enroll') {
    $classId = (int)$_POST['class_id'];
    
    try {
        // Check if class exists
        $stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $classInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($classInfo) {
            // Check if already enrolled
            $stmt = $conn->prepare("SELECT id FROM class_members WHERE user_id = ? AND class_id = ?");
            $stmt->execute([$mahasiswaId, $classId]);
            
            if (!$stmt->fetch()) {
                // Enroll student
                $stmt = $conn->prepare("INSERT INTO class_members (user_id, class_id) VALUES (?, ?)");
                $stmt->execute([$mahasiswaId, $classId]);
                
                $message = "Mahasiswa berhasil didaftarkan ke kelas {$classInfo['class_name']}.";
                logAction($conn, $_SESSION['user_id'], "Enrolled mahasiswa ID $mahasiswaId to class ID $classId");
                
                // Redirect to refresh the page
                header("Location: manage_student_classes.php?id=$mahasiswaId&success=1");
                exit();
            } else {
                $error = "Mahasiswa sudah terdaftar di kelas ini.";
            }
        } else {
            $error = "Kelas tidak ditemukan.";
        }
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Handle unenrollment
if (isset($_GET['action']) && $_GET['action'] === 'unenroll' && isset($_GET['class_id'])) {
    $classId = (int)$_GET['class_id'];
    
    try {
        // Get class info for logging
        $stmt = $conn->prepare("SELECT class_name FROM classes WHERE id = ?");
        $stmt->execute([$classId]);
        $className = $stmt->fetch(PDO::FETCH_COLUMN);
        
        // Remove from class
        $stmt = $conn->prepare("DELETE FROM class_members WHERE user_id = ? AND class_id = ?");
        $stmt->execute([$mahasiswaId, $classId]);
        
        if ($stmt->rowCount() > 0) {
            $message = "Mahasiswa berhasil dikeluarkan dari kelas $className.";
            logAction($conn, $_SESSION['user_id'], "Unenrolled mahasiswa ID $mahasiswaId from class ID $classId");
        } else {
            $error = "Mahasiswa tidak terdaftar di kelas tersebut.";
        }
        
        // Redirect to refresh the page
        header("Location: manage_student_classes.php?id=$mahasiswaId&success=2");
        exit();
    } catch (PDOException $e) {
        $error = "Error: " . $e->getMessage();
    }
}

// Check for success messages from redirects
if (isset($_GET['success'])) {
    if ($_GET['success'] == 1) {
        $message = "Mahasiswa berhasil didaftarkan ke kelas.";
    } elseif ($_GET['success'] == 2) {
        $message = "Mahasiswa berhasil dikeluarkan dari kelas.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas Mahasiswa - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #495057;
            overflow-x: hidden;
        }
        
        .sidebar {
            background-color: #3b8adb;
            background-image: linear-gradient(180deg, #3b8adb 10%, #3b8adb 100%);
            min-height: 100vh;
            position: fixed;
            z-index: 1030;
            width: 250px;
            transition: all 0.3s ease;
        }
        
        .btn-close-sidebar {
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        
        .btn-close-sidebar:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            margin: 0.2rem 0;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #495057;
        }
        
        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            border: none;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .topbar {
            height: 4.5rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            background-color: white;
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 1;
            padding: 0 1.5rem;
        }
        
        .content-wrapper {
            padding-top: 4.5rem;
        }
        
        .class-card {
            transition: all 0.3s ease;
            cursor: pointer;
            border-radius: 10px;
            overflow: hidden;
        }
        
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 0.5rem 2rem rgba(0, 0, 0, 0.15);
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                min-height: 100vh;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                left: 0;
                width: 100%;
            }
            
            .content-wrapper {
                padding-top: 5.5rem;
            }
        }
    </style>
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white col-md-3 col-lg-2">
        <div class="sidebar-brand d-flex align-items-center justify-content-center position-relative">
            <i class="bi bi-bar-chart-line me-2"></i>
            <span>SentiSyncEd</span>
            <button class="btn-close-sidebar d-md-none position-absolute end-0 me-3 text-white bg-transparent border-0">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="manage_dosen.php" class="nav-link text-white">
                    <i class="bi bi-person-badge"></i>
                    Kelola Dosen
                </a>
            </li>
            <li>
                <a href="manage_mahasiswa.php" class="nav-link text-white">
                    <i class="bi bi-mortarboard"></i>
                    Kelola Mahasiswa
                </a>
            </li>
            <li>
                <a href="#" class="nav-link text-white">
                    <i class="bi bi-journal-text"></i>
                    Kelola Kelas
                </a>
            </li>
            <li>
                <a href="#" class="nav-link text-white">
                    <i class="bi bi-emoji-smile"></i>
                    Analisis Emosi
                </a>
            </li>
            <li>
                <a href="#" class="nav-link text-white">
                    <i class="bi bi-gear"></i>
                    Pengaturan
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar me-2 bg-white">
                    <i class="bi bi-person text-primary"></i>
                </div>
                <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Profil</a></li>
                <li><a class="dropdown-item" href="#">Pengaturan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <button class="btn btn-link d-md-none rounded-circle me-3">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-none d-md-flex">
            <h4 class="mb-0">Kelola Kelas Mahasiswa</h4>
        </div>
        <div class="d-flex">
            <div class="position-relative">
                <a class="nav-link dropdown-toggle" href="#" id="alertsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="bi bi-bell fs-5"></i>
                </a>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Page Content -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-body">
                            <div class="d-flex align-items-center mb-3">
                                <div class="avatar me-3 bg-success text-white" style="width: 50px; height: 50px; font-size: 1.5rem;">
                                    <?= substr($mahasiswa['name'], 0, 1) ?>
                                </div>
                                <div>
                                    <h4 class="mb-0"><?= htmlspecialchars($mahasiswa['name']) ?></h4>
                                    <p class="text-muted mb-0"><?= htmlspecialchars($mahasiswa['email']) ?></p>
                                </div>
                            </div>
                            <div class="d-flex">
                                <a href="manage_mahasiswa.php" class="btn btn-outline-secondary">
                                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar Mahasiswa
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success">
                    <?= htmlspecialchars($message) ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <?= htmlspecialchars($error) ?>
                </div>
            <?php endif; ?>
            
            <!-- Enrolled Classes -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold">Kelas yang Diikuti</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($enrolledClasses)): ?>
                                <div class="alert alert-info">
                                    Mahasiswa belum terdaftar di kelas manapun.
                                </div>
                            <?php else: ?>
                                <div class="table-responsive">
                                    <table class="table table-hover align-middle">
                                        <thead>
                                            <tr>
                                                <th>Nama Kelas</th>
                                                <th>Dosen</th>
                                                <th>Tanggal Bergabung</th>
                                                <th>Aksi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($enrolledClasses as $class): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($class['class_name']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($class['description']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($class['dosen_name']) ?></td>
                                                    <td><?= date('d M Y', strtotime($class['joined_at'])) ?></td>
                                                    <td>
                                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#unenrollModal<?= $class['id'] ?>">
                                                            <i class="bi bi-person-dash me-1"></i> Keluarkan
                                                        </button>
                                                    </td>
                                                </tr>
                                                
                                                <!-- Unenroll Confirmation Modal -->
                                                <div class="modal fade" id="unenrollModal<?= $class['id'] ?>" tabindex="-1" aria-labelledby="unenrollModalLabel<?= $class['id'] ?>" aria-hidden="true">
                                                    <div class="modal-dialog modal-dialog-centered">
                                                        <div class="modal-content">
                                                            <div class="modal-header">
                                                                <h5 class="modal-title" id="unenrollModalLabel<?= $class['id'] ?>">Konfirmasi Keluarkan dari Kelas</h5>
                                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                            </div>
                                                            <div class="modal-body">
                                                                <p>Anda yakin ingin mengeluarkan <strong><?= htmlspecialchars($mahasiswa['name']) ?></strong> dari kelas <strong><?= htmlspecialchars($class['class_name']) ?></strong>?</p>
                                                                <p class="text-danger"><small>Tindakan ini akan menghapus semua data keanggotaan kelas mahasiswa ini.</small></p>
                                                            </div>
                                                            <div class="modal-footer">
                                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                                                                <a href="?id=<?= $mahasiswaId ?>&action=unenroll&class_id=<?= $class['id'] ?>" class="btn btn-danger">Keluarkan</a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                </div>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Available Classes -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold">Daftarkan ke Kelas Baru</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($availableClasses)): ?>
                                <div class="alert alert-info">
                                    Tidak ada kelas tersedia untuk didaftarkan.
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($availableClasses as $class): ?>
                                        <div class="col-md-4 mb-4">
                                            <div class="card class-card h-100">
                                                <div class="card-header bg-primary text-white">
                                                    <h6 class="mb-0"><?= htmlspecialchars($class['class_name']) ?></h6>
                                                </div>
                                                <div class="card-body">
                                                    <p class="mb-2"><i class="bi bi-person-badge me-1"></i> <?= htmlspecialchars($class['dosen_name']) ?></p>
                                                    <p class="small text-muted mb-3"><?= htmlspecialchars($class['description']) ?></p>
                                                    <form method="POST" action="">
                                                        <input type="hidden" name="action" value="enroll">
                                                        <input type="hidden" name="class_id" value="<?= $class['id'] ?>">
                                                        <button type="submit" class="btn btn-sm btn-primary w-100">
                                                            <i class="bi bi-person-plus me-1"></i> Daftarkan ke Kelas Ini
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
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggleBtn = document.querySelector('.btn-link');
            const sidebar = document.querySelector('.sidebar');
            const closeSidebarBtn = document.querySelector('.btn-close-sidebar');
            
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                });
            }
        });
    </script>
</body>
</html>
