<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

// Prepare dashboard data
$user_id = $_SESSION['user_id'];
$positiveCount     = getPositiveEmotionsCount($user_id);
$negativeCount     = getNegativeEmotionsCount($user_id);
$supportNotesCount = getSupportNotesCount($user_id);
$classCount        = count(getEnrolledClasses($user_id));

// Emotion distribution counts
$emotionCounts = [
    'Senang' => 0,
    'Stres'  => 0,
    'Lelah'  => 0,
    'Netral' => 0
];
try {
    $stmt = $conn->prepare("SELECT emotion, COUNT(*) AS cnt FROM emotions WHERE user_id = ? GROUP BY emotion");
    $stmt->execute([$user_id]);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        $emotion = $row['emotion'];
        $emotionCounts[$emotion] = (int)$row['cnt'];
    }
} catch (PDOException $e) {
    // silently fail, keep zeros
}

$recentNotes = getRecentSupportNotes($user_id, 5);

// Hitung persentase kehadiran (menggunakan data sesi & emosi)
try {
    // Total sesi dari semua kelas yang diikuti
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT cs.id) AS total_sessions
                            FROM class_sessions cs
                            JOIN classes c ON c.id = cs.class_id
                            JOIN class_members cm ON cm.class_id = c.id
                            WHERE cm.user_id = ?");
    $stmt->execute([$user_id]);
    $totalSessions = (int)($stmt->fetch()['total_sessions'] ?? 0);

    // Sesi yang dihadiri (memiliki minimal satu catatan emosi)
    $stmt = $conn->prepare("SELECT COUNT(DISTINCT class_session_id) AS attended
                            FROM emotions
                            WHERE user_id = ? AND class_session_id IS NOT NULL");
    $stmt->execute([$user_id]);
    $attendedSessions = (int)($stmt->fetch()['attended'] ?? 0);

    $attendancePercentage = $totalSessions ? round($attendedSessions / $totalSessions * 100, 1) : 0;
} catch (PDOException $e) {
    $attendancePercentage = 0;
}

// Tips kesehatan mental sederhana
$tips = [
    'Tarik napas dalam-dalam dan fokus pada hal yang bisa Anda kendalikan.',
    'Luangkan waktu sejenak untuk bersyukur setiap hari.',
    'Bergeraklah—jalan kaki singkat dapat meningkatkan suasana hati.',
    'Jangan ragu untuk meminta bantuan ketika Anda membutuhkannya.',
    'Tidur cukup adalah fondasi kesehatan mental yang baik.'
];
$randomTip = $tips[array_rand($tips)];

// Emosi terakhir mahasiswa
$lastEmotion = getLastEmotion($user_id);
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
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
            <a href="dashboard_mahasiswa.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-house me-2"></i> Dashboard
            </a>
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
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
            <a href="panduan.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
            <i class="bi bi-question-circle me-2"></i> Panduan Penggunaan
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
            <h1 class="page-title mb-4">Dashboard Mahasiswa</h1>

            <!-- Dashboard content here -->
            <div class="row">
                <div class="col-lg-8 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Aktivitas Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <p>Selamat datang di dashboard mahasiswa SentiSyncEd. Gunakan menu di sebelah kiri untuk navigasi.</p>
                            <div class="mt-3 p-3 bg-light rounded border-start border-4 border-primary fade-in">
                                <h6 class="fw-bold mb-2"><i class="bi bi-heart-pulse-fill me-1 text-primary"></i> Tips Kesehatan Mental</h6>
                                <p class="mb-0 fst-italic">“<?php echo htmlspecialchars($randomTip); ?>”</p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 mb-4">
                    <div class="card h-100">
                        <div class="card-header">
                            <h5 class="mb-0">Emosi Terakhir</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($lastEmotion): ?>
                                <p><strong>Emosi:</strong> <?php echo htmlspecialchars($lastEmotion['emotion']); ?></p>
                                <p><strong>Waktu:</strong> <?php echo timeAgo($lastEmotion['timestamp']); ?></p>
                            <?php else: ?>
                                <p>Belum ada data emosi.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistic Cards -->
            <div class="row g-4 mb-4">
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card text-center p-4">
                        <i class="bi bi-emoji-smile text-success"></i>
                        <h3><?php echo $positiveCount; ?></h3>
                        <p>Emosi Positif</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card text-center p-4">
                        <i class="bi bi-emoji-frown text-danger"></i>
                        <h3><?php echo $negativeCount; ?></h3>
                        <p>Emosi Negatif</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card text-center p-4">
                        <i class="bi bi-journal-bookmark text-primary"></i>
                        <h3><?php echo $classCount; ?></h3>
                        <p>Kelas Terdaftar</p>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3">
                    <div class="stat-card text-center p-4">
                        <i class="bi bi-chat-dots text-warning"></i>
                        <h3><?php echo $supportNotesCount; ?></h3>
                        <p>Curhat Terkirim</p>
                    </div>
                </div>
            </div>

            <!-- Emotion Overview & Account Info -->
            <div class="row g-4 mb-4">
                <div class="col-lg-8">
                    <div class="card h-100">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="mb-0"><i class="bi bi-pie-chart me-2"></i>Ringkasan Emosi</h5>
                        </div>
                        <div class="card-body">
                            <div class="chart-wrapper" style="height:350px;">
                                <canvas id="emotionDoughnut"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4">
                    <div class="card mb-4 fade-in">
                        <div class="card-header">
                            <h5 class="mb-0">Ringkasan Kehadiran</h5>
                        </div>
                        <div class="card-body text-center">
                            <h2 class="display-6 mb-1"><?php echo $attendancePercentage; ?>%</h2>
                            <p class="mb-2 text-muted">Persentase Kehadiran Anda</p>
                            <div class="progress" style="height:10px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $attendancePercentage; ?>%;" aria-valuenow="<?php echo $attendancePercentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                            </div>
                        </div>
                    </div>
                    <div class="card h-100 d-none">
                        <div class="card-header">
                            <h5 class="mb-0">Tips Kesehatan Mental</h5>
                        </div>
                        <div class="card-body">
                            <div class="d-flex align-items-start" style="gap:10px;">
                                <i class="bi bi-emoji-smile text-primary fs-3"></i>
                                <blockquote class="blockquote mb-0 flex-grow-1" style="font-size:0.95rem;">
                                “<?php echo htmlspecialchars($randomTip); ?>”
                            </blockquote>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Support Notes -->
            <?php if (!empty($recentNotes)): ?>
            <div class="row g-4 mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex align-items-center">
                            <h5 class="mb-0"><i class="bi bi-chat-left-text me-2"></i>Curhat Terbaru</h5>
                        </div>
                        <div class="card-body">
                            <ul class="list-group list-group-flush">
                                <?php foreach ($recentNotes as $note): ?>
                                <li class="list-group-item">
                                    <strong><?php echo htmlspecialchars($note['sender_name']); ?>:</strong>
                                    <?php echo nl2br(htmlspecialchars($note['message'])); ?>
                                    <span class="text-muted small d-block"><?php echo timeAgo($note['timestamp']); ?></span>
                                </li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
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
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

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

            /* Initialize Emotion Doughnut Chart */
            const emotionData = <?php echo json_encode(array_values($emotionCounts)); ?>;
            const emotionLabels = ['Senang', 'Stres', 'Lelah', 'Netral'];
            if (document.getElementById('emotionDoughnut')) {
                new Chart(document.getElementById('emotionDoughnut').getContext('2d'), {
                    type: 'doughnut',
                    data: {
                        labels: emotionLabels,
                        datasets: [{
                            data: emotionData,
                            backgroundColor: ['#28a745', '#dc3545', '#fd7e14', '#6c757d'],
                            borderWidth: 1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { position: 'bottom' }
                        }
                    }
                });
            }

        }); // end DOMContentLoaded
    </script>
</body>

</html>