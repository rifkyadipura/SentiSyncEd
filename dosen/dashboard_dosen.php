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

// Get class count
$stmt = $conn->prepare("SELECT COUNT(*) as class_count FROM classes WHERE dosen_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$class_count = $stmt->fetch(PDO::FETCH_ASSOC)['class_count'];

// Get total students count
$stmt = $conn->prepare("SELECT COUNT(DISTINCT cm.user_id) as student_count 
                        FROM class_members cm 
                        JOIN classes c ON cm.class_id = c.id 
                        WHERE c.dosen_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$student_count = $stmt->fetch(PDO::FETCH_ASSOC)['student_count'];

// Get active sessions count
$stmt = $conn->prepare("SELECT COUNT(*) as active_sessions 
                        FROM class_sessions cs 
                        JOIN classes c ON cs.class_id = c.id 
                        WHERE c.dosen_id = ? AND cs.status = 'active'");
$stmt->execute([$_SESSION['user_id']]);
$active_sessions = $stmt->fetch(PDO::FETCH_ASSOC)['active_sessions'];

// Get recent classes (limit to 5)
$stmt = $conn->prepare("SELECT c.*, 
                        (SELECT COUNT(*) FROM class_members cm WHERE cm.class_id = c.id) as member_count,
                        (SELECT COUNT(*) FROM class_sessions cs WHERE cs.class_id = c.id AND cs.status = 'active') as has_active_session
                        FROM classes c 
                        WHERE c.dosen_id = ? 
                        ORDER BY c.created_at DESC 
                        LIMIT 5");
$stmt->execute([$_SESSION['user_id']]);
$recent_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get emotion summary for all classes
$stmt = $conn->prepare("SELECT 
                        SUM(CASE WHEN e.emotion = 'Senang' THEN 1 ELSE 0 END) as happy_count,
                        SUM(CASE WHEN e.emotion = 'Stres' THEN 1 ELSE 0 END) as stress_count,
                        SUM(CASE WHEN e.emotion = 'Lelah' THEN 1 ELSE 0 END) as tired_count,
                        SUM(CASE WHEN e.emotion = 'Netral' THEN 1 ELSE 0 END) as neutral_count,
                        COUNT(*) as total_count
                        FROM emotions e
                        JOIN class_sessions cs ON e.class_session_id = cs.id
                        JOIN classes c ON cs.class_id = c.id
                        WHERE c.dosen_id = ? AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)");
$stmt->execute([$_SESSION['user_id']]);
$emotion_summary = $stmt->fetch(PDO::FETCH_ASSOC);

// Get recent support notes (curhat)
$stmt = $conn->prepare("SELECT sn.*, u.name as student_name, c.class_name
                        FROM support_notes sn
                        JOIN users u ON sn.user_id = u.id
                        JOIN classes c ON sn.class_id = c.id
                        WHERE c.dosen_id = ?
                        ORDER BY sn.timestamp DESC
                        LIMIT 3");
$stmt->execute([$_SESSION['user_id']]);
$recent_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get emotion alerts
$stmt = $conn->prepare("SELECT 
                        cs.id as class_session_id,
                        c.class_name,
                        COUNT(CASE WHEN e.emotion IN ('Stres', 'Lelah') THEN 1 END) as negative_count,
                        COUNT(e.id) as total_count,
                        (COUNT(CASE WHEN e.emotion IN ('Stres', 'Lelah') THEN 1 END) * 100.0 / COUNT(e.id)) as negative_percentage
                        FROM emotions e
                        JOIN class_sessions cs ON e.class_session_id = cs.id
                        JOIN classes c ON cs.class_id = c.id
                        WHERE c.dosen_id = ? AND cs.status = 'active'
                        AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 30 MINUTE)
                        GROUP BY cs.id, c.class_name
                        HAVING negative_percentage >= 20 AND total_count >= 3
                        ORDER BY negative_percentage DESC
                        LIMIT 3");
$stmt->execute([$_SESSION['user_id']]);
$emotion_alerts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content-wrapper">
        <h1 class="page-title">Dashboard Dosen</h1>
        
        <!-- Welcome and Stats Row -->
        <div class="row mb-4">
            <div class="col-lg-6">
                <div class="card welcome-card p-4 h-100">
                    <h2>Selamat Datang, <?php echo htmlspecialchars($dosen['name']); ?>!</h2>
                    <p class="mt-2">Ini adalah dashboard dosen SentiSyncEd. Anda dapat mengelola kelas dan melihat laporan emosi mahasiswa di sini.</p>
                    <div class="d-flex gap-2 mt-3">
                        <a href="create_class.php" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Buat Kelas Baru
                        </a>
                        <a href="grafik_emosi.php" class="btn btn-outline-primary">
                            <i class="bi bi-graph-up me-1"></i> Lihat Grafik Emosi
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">Statistik</div>
                    <div class="card-body">
                        <div class="row text-center">
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded bg-light">
                                    <h5>Jumlah Kelas</h5>
                                    <h3><?php echo $class_count; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded bg-light">
                                    <h5>Total Mahasiswa</h5>
                                    <h3><?php echo $student_count; ?></h3>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="p-3 rounded bg-light">
                                    <h5>Sesi Aktif</h5>
                                    <h3><?php echo $active_sessions; ?></h3>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Emotion Alerts -->
        <?php if (!empty($emotion_alerts)): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="card border-warning">
                    <div class="card-header bg-warning text-dark">
                        <i class="bi bi-exclamation-triangle me-2"></i> Peringatan Emosi Terbaru
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <?php foreach ($emotion_alerts as $alert): ?>
                            <div class="col-md-4 mb-3">
                                <div class="alert alert-warning mb-0">
                                    <h6 class="alert-heading"><?php echo htmlspecialchars($alert['class_name']); ?></h6>
                                    <p class="mb-1">
                                        <strong><?php echo round($alert['negative_percentage'], 1); ?>%</strong> emosi negatif
                                        (<?php echo $alert['negative_count']; ?> dari <?php echo $alert['total_count']; ?>)
                                    </p>
                                    <div class="mt-2">
                                        <a href="view_class.php?id=<?php echo $alert['class_session_id']; ?>" class="btn btn-sm btn-warning">
                                            Lihat Detail
                                        </a>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
        
        <!-- Main Content Row -->
        <div class="row mb-4">
            <!-- Recent Classes -->
            <div class="col-lg-8 mb-4 mb-lg-0">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>Kelas Anda</span>
                        <a href="kelas.php" class="btn btn-sm btn-light">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_classes)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-journal-x fs-1 text-muted"></i>
                                <p class="mt-2">Belum ada kelas yang dibuat.</p>
                                <a href="create_class.php" class="btn btn-primary mt-2">
                                    <i class="bi bi-plus-circle me-1"></i> Buat Kelas Baru
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Nama Kelas</th>
                                            <th>Jumlah Mahasiswa</th>
                                            <th>Status</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_classes as $class): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($class['class_name']); ?></td>
                                            <td><?php echo $class['member_count']; ?> mahasiswa</td>
                                            <td>
                                                <?php if ($class['has_active_session'] > 0): ?>
                                                    <span class="badge bg-success">Sesi Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge bg-secondary">Tidak Ada Sesi</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <a href="view_class.php?id=<?php echo $class['id']; ?>" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-eye"></i>
                                                </a>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Emotion Summary -->
            <div class="col-lg-4">
                <div class="card h-100">
                    <div class="card-header bg-primary text-white">Ringkasan Emosi (7 Hari Terakhir)</div>
                    <div class="card-body p-3">
                        <?php if (isset($emotion_summary) && !empty($emotion_summary['total_count'])): ?>
                            <?php 
                            $total = max(1, $emotion_summary['total_count']); // Mencegah pembagian dengan nol
                            $happy_percent = round(($emotion_summary['happy_count'] / $total) * 100, 1);
                            $stress_percent = round(($emotion_summary['stress_count'] / $total) * 100, 1);
                            $tired_percent = round(($emotion_summary['tired_count'] / $total) * 100, 1);
                            $neutral_percent = round(($emotion_summary['neutral_count'] / $total) * 100, 1);
                            ?>
                            
                            <!-- Visualisasi Emosi dengan Progress Bar -->
                            <div class="mb-4">
                                <!-- Senang -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="bi bi-circle-fill text-success me-1"></i> Senang</span>
                                        <span class="badge bg-light text-dark"><?php echo $happy_percent; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-success" role="progressbar" style="width: <?php echo $happy_percent; ?>%" 
                                            aria-valuenow="<?php echo $happy_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <!-- Stres -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="bi bi-circle-fill text-danger me-1"></i> Stres</span>
                                        <span class="badge bg-light text-dark"><?php echo $stress_percent; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-danger" role="progressbar" style="width: <?php echo $stress_percent; ?>%" 
                                            aria-valuenow="<?php echo $stress_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <!-- Lelah -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="bi bi-circle-fill text-warning me-1"></i> Lelah</span>
                                        <span class="badge bg-light text-dark"><?php echo $tired_percent; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-warning" role="progressbar" style="width: <?php echo $tired_percent; ?>%" 
                                            aria-valuenow="<?php echo $tired_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                                
                                <!-- Netral -->
                                <div class="mb-3">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span><i class="bi bi-circle-fill text-secondary me-1"></i> Netral</span>
                                        <span class="badge bg-light text-dark"><?php echo $neutral_percent; ?>%</span>
                                    </div>
                                    <div class="progress" style="height: 10px;">
                                        <div class="progress-bar bg-secondary" role="progressbar" style="width: <?php echo $neutral_percent; ?>%" 
                                            aria-valuenow="<?php echo $neutral_percent; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Informasi Tambahan -->
                            <div class="alert alert-info mb-0">
                                <small>
                                    <i class="bi bi-info-circle me-1"></i> Total data emosi: <strong><?php echo $emotion_summary['total_count']; ?></strong> dalam 7 hari terakhir
                                </small>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-emoji-smile fs-1 text-muted"></i>
                                <p class="mt-2">Belum ada data emosi yang tercatat.</p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Recent Support Notes -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header bg-primary text-white d-flex justify-content-between align-items-center">
                        <span>Curhat Mahasiswa Terbaru</span>
                        <a href="daftar_curhat.php" class="btn btn-sm btn-light">Lihat Semua</a>
                    </div>
                    <div class="card-body">
                        <?php if (empty($recent_notes)): ?>
                            <div class="text-center py-4">
                                <i class="bi bi-chat-square-text fs-1 text-muted"></i>
                                <p class="mt-2">Belum ada curhat dari mahasiswa.</p>
                            </div>
                        <?php else: ?>
                            <div class="list-group">
                                <?php foreach ($recent_notes as $note): ?>
                                <div class="list-group-item list-group-item-action">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1"><?php echo htmlspecialchars($note['student_name']); ?></h6>
                                        <small class="text-muted"><?php echo date('d/m/Y H:i', strtotime($note['timestamp'])); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars(substr($note['message'], 0, 150) . (strlen($note['message']) > 150 ? '...' : ''))); ?></p>
                                    <div class="d-flex justify-content-between align-items-center mt-2">
                                        <small class="text-muted">Kelas: <?php echo htmlspecialchars($note['class_name']); ?></small>
                                        <a href="daftar_curhat.php" class="btn btn-sm btn-outline-primary">Lihat Detail</a>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <?php if (isset($emotion_summary) && !empty($emotion_summary['total_count'])): ?>
    <script>
    // Pastikan Chart.js sudah dimuat sebelum menjalankan kode ini
    document.addEventListener('DOMContentLoaded', function() {
        // Cek apakah elemen canvas ada
        const emotionCanvas = document.getElementById('emotionChart');
        if (emotionCanvas) {
            // Cek apakah Chart.js tersedia
            if (typeof Chart !== 'undefined') {
                try {
                    const emotionCtx = emotionCanvas.getContext('2d');
                    // Hapus chart lama jika ada untuk mencegah duplikasi
                    if (window.emotionChart) {
                        window.emotionChart.destroy();
                    }
                    
                    // Buat chart baru
                    window.emotionChart = new Chart(emotionCtx, {
                        type: 'pie',
                        data: {
                            labels: ['Senang', 'Stres', 'Lelah', 'Netral'],
                            datasets: [{
                                data: [
                                    <?php echo intval($emotion_summary['happy_count']); ?>,
                                    <?php echo intval($emotion_summary['stress_count']); ?>,
                                    <?php echo intval($emotion_summary['tired_count']); ?>,
                                    <?php echo intval($emotion_summary['neutral_count']); ?>
                                ],
                                backgroundColor: [
                                    '#28a745', // green for happy
                                    '#dc3545', // red for stress
                                    '#ffc107', // yellow for tired
                                    '#6c757d'  // gray for neutral
                                ],
                                borderWidth: 1
                            }]
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: true,
                            cutout: '50%',
                            plugins: {
                                legend: {
                                    display: false // Sembunyikan legend karena kita sudah punya daftar manual di bawah
                                },
                                tooltip: {
                                    enabled: true
                                }
                            },
                            animation: {
                                animateScale: true,
                                animateRotate: true
                            }
                        }
                    });
                } catch (error) {
                    console.error('Error creating emotion chart:', error);
                }
            } else {
                console.error('Chart.js is not loaded');
            }
        } else {
            console.error('Emotion chart canvas not found');
        }
    });
    </script>
    <?php endif; ?>
</body>
</html>
