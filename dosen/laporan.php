<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get classes for filtering
$stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE dosen_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected class and date range
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : date('Y-m-d', strtotime('-1 month'));
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : date('Y-m-d');

// Get emotion data if filters are set
$emotions = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("
        SELECT e.*, u.name as student_name, cs.start_time, cs.end_time
        FROM emotions e
        JOIN users u ON e.user_id = u.id
        JOIN class_sessions cs ON e.class_session_id = cs.id
        WHERE cs.class_id = ?
        AND e.timestamp BETWEEN ? AND ?
        ORDER BY e.timestamp DESC
    ");
    $stmt->execute([$class_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
    $emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
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
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Dosen'); ?></span>
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
            <?php echo htmlspecialchars($_SESSION['name'] ?? 'Dosen'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>

    <div class="content-wrapper">
        <div class="container-fluid px-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="page-title">Laporan Refleksi</h1>
            </div>
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span>Pilih Kelas</span>
                        </div>
                        <div class="card-body">
                            <form method="GET" class="row g-3">
                                <div class="col-md-4">
                                    <label for="class_id" class="form-label">Pilih Kelas</label>
                                    <select name="class_id" id="class_id" class="form-select" required>
                                        <option value="">Pilih Kelas...</option>
                                        <?php foreach ($classes as $class): ?>
                                        <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($class['class_name']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-3">
                                    <label for="start_date" class="form-label">Tanggal Mulai</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo $start_date; ?>" required>
                                </div>
                                <div class="col-md-3">
                                    <label for="end_date" class="form-label">Tanggal Selesai</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo $end_date; ?>" required>
                                </div>
                                <div class="col-md-2">
                                    <label class="form-label">&nbsp;</label>
                                    <button type="submit" class="btn btn-primary w-100">Tampilkan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <?php if ($class_id > 0 && !empty($emotions)): ?>
            <div class="row">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                            <span>Hasil Laporan</span>
                            <button onclick="generatePDF()" class="btn btn-light btn-sm">
                                <i class="bi bi-download me-1"></i> Download PDF
                            </button>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Mahasiswa</th>
                                            <th>Emosi</th>
                                            <th>Waktu</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($emotions as $emotion): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($emotion['student_name']); ?></td>
                                            <td><?php echo htmlspecialchars($emotion['emotion']); ?></td>
                                            <td><?php echo date('d/m/Y H:i', strtotime($emotion['timestamp'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php elseif ($class_id > 0): ?>
            <div class="alert alert-info mt-4">
                Tidak ada data emosi untuk periode yang dipilih.
            </div>
            <?php endif; ?>
            
        <!-- Copyright Footer -->
        <footer class="py-3 text-center text-muted border-top" style="position: fixed; bottom: 0; width: 100%; background-color: #f8f9fa; z-index: 1000;">
            <div class="container">
                <p class="mb-0">&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</p>
            </div>
        </footer>
        
        <!-- Padding to prevent content from being hidden behind fixed footer -->
        <div style="height: 60px;"></div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <script>
        // Function to generate PDF report
        function generatePDF() {
            // Get current filter values
            const classId = document.getElementById('class_id').value;
            const startDate = document.getElementById('start_date').value;
            const endDate = document.getElementById('end_date').value;
            
            if (!classId || !startDate || !endDate) {
                alert('Mohon pilih kelas dan rentang tanggal terlebih dahulu');
                return;
            }
            
            // Redirect to PDF generation script with parameters
            window.location.href = `generate_report_pdf.php?class_id=${classId}&start_date=${startDate}&end_date=${endDate}`;
        }
        
        // Mobile sidebar toggle functionality
        document.addEventListener('DOMContentLoaded', function() {
            const sidebarToggle = document.getElementById('sidebarToggle');
            const sidebar = document.getElementById('sidebar');
            const overlay = document.getElementById('overlay');
            
            // Toggle sidebar on button click
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.classList.toggle('show');
                });
            }
            
            // Close sidebar when clicking on overlay
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.classList.remove('show');
                });
            }
            
            // Close sidebar on window resize if width > 992px
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
