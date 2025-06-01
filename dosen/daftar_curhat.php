<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get dosen info
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'Dosen'");
$stmt->execute([$_SESSION['user_id']]);
$dosen = $stmt->fetch(PDO::FETCH_ASSOC);

// Get classes taught by this teacher
$dosen_id = $_SESSION['user_id'];
$stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE dosen_id = ? ORDER BY class_name");
$stmt->execute([$dosen_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check if class_id column exists in support_notes table
try {
    $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
    $stmt->execute();
    $class_id_exists = $stmt->rowCount() > 0;
} catch (PDOException $e) {
    $class_id_exists = false;
}

// Filter by class if selected
$selected_class_id = filter_input(INPUT_GET, 'kelas_id', FILTER_SANITIZE_NUMBER_INT);
$where_clause = "WHERE u.role = 'Mahasiswa'";
$params = [];

if ($class_id_exists && !empty($selected_class_id)) {
    $where_clause .= " AND sn.class_id = ?";
    $params[] = $selected_class_id;
}

// Get support notes with class information
if ($class_id_exists) {
    $stmt = $conn->prepare("
        SELECT sn.*, u.name as student_name, c.class_name
        FROM support_notes sn
        JOIN users u ON sn.user_id = u.id
        LEFT JOIN classes c ON sn.class_id = c.id
        $where_clause
        ORDER BY sn.timestamp DESC
    ");
} else {
    $stmt = $conn->prepare("
        SELECT sn.*, u.name as student_name, NULL as class_name
        FROM support_notes sn
        JOIN users u ON sn.user_id = u.id
        $where_clause
        ORDER BY sn.timestamp DESC
    ");
}
$stmt->execute($params);
$supportNotes = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <style>
        /* Mobile styles */
        .mobile-navbar {
            background-color: #4A90E2;
            padding: 10px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1020;
            display: flex;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            display: none;
        }
        
        .overlay.show {
            display: block;
        }
        
        /* Mobile sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
                transition: transform 0.3s ease;
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }
            
            .mobile-navbar {
                display: flex;
            }
        }
        
        /* Desktop view */
        @media (min-width: 992px) {
            .content-wrapper {
                margin-left: 250px;
            }
            
            .sidebar {
                transform: translateX(0);
            }
            
            .mobile-navbar {
                display: none;
            }
        }
        
        .footer {
            background-color: #f8f9fa;
            padding: 15px 0;
            text-align: center;
            position: absolute;
            bottom: 0;
            width: 100%;
            border-top: 1px solid #e9ecef;
        }
        
        body {
            min-height: 100vh;
            position: relative;
            padding-bottom: 60px;
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
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($dosen['name'] ?? 'Dosen'); ?></span>
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
    <aside class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </aside>

    <!-- User Dropdown in Content Area -->
    <div class="user-dropdown dropdown d-none d-lg-block">
        <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($dosen['name'] ?? 'Dosen'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>

    <div class="content-wrapper">
        <h1 class="page-title">Daftar Curhat Mahasiswa</h1>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">Pilih Kelas</div>
                    <div class="card-body">
                        <form method="GET" action="">
                            <div class="mb-3">
                                <label for="kelas_id" class="form-label">Pilih Kelas</label>
                                <select name="kelas_id" id="kelas_id" class="form-select" onchange="this.form.submit()">
                                    <option value="">-- Semua Kelas --</option>
                                    <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo ($selected_class_id == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white">Data Curhat Mahasiswa</div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Mahasiswa</th>
                                        <th>Kelas</th>
                                        <th>Waktu</th>
                                        <th>Pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($supportNotes)): ?>
                                    <tr>
                                        <td colspan="4" class="text-center">Tidak ada data curhat untuk ditampilkan</td>
                                    </tr>
                                    <?php else: ?>
                                        <?php foreach ($supportNotes as $note): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($note['student_name']); ?></td>
                                            <td>
                                                <?php if (!empty($note['class_name'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($note['class_name']); ?></span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Tidak ada kelas</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($note['timestamp'])); ?></td>
                                            <td><?php echo nl2br(htmlspecialchars($note['message'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
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
        });
    </script>
</body>
</html>
