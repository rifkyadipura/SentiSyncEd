<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get class ID from URL
$class_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get class details
$stmt = $conn->prepare("SELECT * FROM classes WHERE id = ? AND dosen_id = ?");
$stmt->execute([$class_id, $_SESSION['user_id']]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

// If class not found or doesn't belong to current dosen
if (!$class) {
    header('Location: dashboard_dosen.php');
    exit();
}

// Handle session actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['start_session'])) {
        // Start new session
        $stmt = $conn->prepare("INSERT INTO class_sessions (class_id, start_time, created_by) VALUES (?, NOW(), ?)");
        $stmt->execute([$class_id, $_SESSION['user_id']]);
        logAction($conn, $_SESSION['user_id'], "Started class session for class ID: " . $class_id);
        header("Location: view_class.php?id=" . $class_id);
        exit();
    } elseif (isset($_POST['end_session'])) {
        $session_id = filter_input(INPUT_POST, 'session_id', FILTER_SANITIZE_NUMBER_INT);
        // End session
        $stmt = $conn->prepare("UPDATE class_sessions SET end_time = NOW(), status = 'ended' WHERE id = ? AND class_id = ?");
        $stmt->execute([$session_id, $class_id]);
        logAction($conn, $_SESSION['user_id'], "Ended class session ID: " . $session_id);
        header("Location: view_class.php?id=" . $class_id);
        exit();
    }
}

// Get active session if any
$stmt = $conn->prepare("
    SELECT * FROM class_sessions 
    WHERE class_id = ? AND status = 'active' 
    AND start_time <= NOW() 
    AND (end_time IS NULL OR end_time >= NOW())
");
$stmt->execute([$class_id]);
$active_session = $stmt->fetch(PDO::FETCH_ASSOC);

// Get all sessions for this class
$stmt = $conn->prepare("
    SELECT cs.*, 
           (SELECT COUNT(*) FROM emotions e WHERE e.class_session_id = cs.id) as emotion_count
    FROM class_sessions cs
    WHERE cs.class_id = ?
    ORDER BY cs.start_time DESC
    LIMIT 10
");
$stmt->execute([$class_id]);
$past_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get class members (students)
$stmt = $conn->prepare("
    SELECT u.id, u.name, u.email, cm.joined_at
    FROM class_members cm
    JOIN users u ON cm.user_id = u.id
    WHERE cm.class_id = ?
    ORDER BY u.name
");
$stmt->execute([$class_id]);
$members = $stmt->fetchAll(PDO::FETCH_ASSOC);
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
        <h1 class="page-title">Detail Kelas</h1>
        <div class="row mb-4">
            <div class="col-12">
                <a href="kelas.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left me-2"></i> Kembali ke Kelas</a>
            </div>
        </div>
        <div class="row">
            <div class="col-lg-8">
                <div class="card class-info">
                    <div class="card-header">Informasi Kelas</div>
                    <div class="card-body">
                        <h2><?php echo htmlspecialchars($class['class_name']); ?></h2>
                        <p><strong>Deskripsi Kelas:</strong> <?php echo nl2br(htmlspecialchars($class['description'])); ?></p>
                        <small class="text-muted">Dibuat pada: <?php echo date('d/m/Y H:i', strtotime($class['created_at'])); ?></small>
                    </div>
                </div>
            </div>
            <div class="col-lg-4">
                <div class="card">
                    <div class="card-header">Statistik Kelas</div>
                    <div class="card-body text-center">
                        <h5>Jumlah Mahasiswa</h5>
                        <h3><?php echo count($members); ?></h3>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Sesi Kelas</div>
                    <div class="card-body">
                        <?php if ($active_session): ?>
                            <div class="alert alert-success">
                                <h6>Sesi Aktif</h6>
                                <p>Dimulai: <?php echo date('d/m/Y H:i', strtotime($active_session['start_time'])); ?></p>
                                <p>Stopwatch: <span id="stopwatch">00:00:00</span></p>
                                <div class="d-flex gap-2 mt-2">
                                    <form method="POST" action="" class="me-2">
                                        <input type="hidden" name="session_id" value="<?php echo $active_session['id']; ?>">
                                        <button type="submit" name="end_session" class="btn btn-danger" onclick="return confirm('Yakin ingin mengakhiri sesi kelas?')">
                                            <i class="bi bi-stop-circle"></i> Akhiri Sesi
                                        </button>
                                    </form>
                                    <button type="button" class="btn btn-warning" data-bs-toggle="modal" data-bs-target="#emotionAlertModal">
                                        <i class="bi bi-exclamation-triangle"></i> Peringatan Emosi
                                    </button>
                                </div>
                            </div>
                            <script>
                            document.addEventListener('DOMContentLoaded', function() {
                                const startTime = new Date("<?= $active_session['start_time'] ?>");
                                function updateStopwatch() {
                                    const now = new Date();
                                    let diff = Math.floor((now - startTime) / 1000);
                                    const hrs = String(Math.floor(diff / 3600)).padStart(2, '0');
                                    diff %= 3600;
                                    const mins = String(Math.floor(diff / 60)).padStart(2, '0');
                                    const secs = String(diff % 60).padStart(2, '0');
                                    document.getElementById('stopwatch').textContent = `${hrs}:${mins}:${secs}`;
                                }
                                updateStopwatch();
                                setInterval(updateStopwatch, 1000);
                            });
                            </script>
                        <?php else: ?>
                            <form method="POST" action="">
                                <button type="submit" name="start_session" class="btn btn-primary">
                                    <i class="fas fa-play-circle"></i> Mulai Sesi Baru
                                </button>
                            </form>
                        <?php endif; ?>

                        <?php if (!empty($past_sessions)): ?>
                            <div class="mt-3">
                                <h6>Riwayat Sesi (10 Terakhir)</h6>
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Mulai</th>
                                                <th>Selesai</th>
                                                <th>Status</th>
                                                <th>Jumlah Emosi</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($past_sessions as $session): ?>
                                            <tr>
                                                <td><?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></td>
                                                <td>
                                                    <?php 
                                                    echo $session['end_time'] 
                                                        ? date('d/m/Y H:i', strtotime($session['end_time']))
                                                        : '-';
                                                    ?>
                                                </td>
                                                <td>
                                                    <span class="badge <?php echo $session['status'] === 'active' ? 'bg-success' : 'bg-secondary'; ?>">
                                                        <?php echo $session['status'] === 'active' ? 'Aktif' : 'Selesai'; ?>
                                                    </span>
                                                </td>
                                                <td><?php echo $session['emotion_count']; ?></td>
                                            </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">Daftar Mahasiswa</div>
                    <div class="card-body">
                        <?php if (empty($members)): ?>
                            <p class="text-center">Belum ada mahasiswa yang bergabung dengan kelas ini.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Tanggal Bergabung</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($members as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['email']); ?></td>
                                            <td><?php echo date('d/m/Y', strtotime($member['joined_at'])); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Emotion Alert Modal -->
    <div class="modal fade" id="emotionAlertModal" tabindex="-1" aria-labelledby="emotionAlertModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-warning text-dark">
                    <h5 class="modal-title" id="emotionAlertModalLabel">
                        <i class="bi bi-exclamation-triangle me-2"></i> Peringatan Emosi
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Loading indicator -->
                    <div id="emotionAlertLoading" class="text-center py-4">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <p class="mt-2">Memuat data peringatan emosi...</p>
                    </div>
                    
                    <!-- Alert content will be loaded here -->
                    <div id="emotionAlertContent" style="display: none;"></div>
                    
                    <!-- Empty state -->
                    <div id="emotionAlertEmpty" class="text-center py-4" style="display: none;">
                        <i class="bi bi-emoji-smile fs-1 text-muted"></i>
                        <p class="mt-2">Tidak ada peringatan emosi saat ini.</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="grafik_emosi.php?class_id=<?php echo $class_id; ?>" class="btn btn-primary">
                        <i class="bi bi-graph-up"></i> Lihat Grafik Emosi
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Audio akan dibuat secara dinamis saat alert muncul -->
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
    // Store viewed alerts to prevent showing the same alerts repeatedly
    let viewedAlerts = JSON.parse(localStorage.getItem('emotionAlerts')) || {
        timestamps: {},
        percentages: {},
        lastShown: {},
        stressCount: {},
        tiredCount: {}
    };

    // Function to save viewed alerts to localStorage
    function saveViewedAlerts() {
        localStorage.setItem('emotionAlerts', JSON.stringify(viewedAlerts));
    }

    // Variabel global untuk melacak waktu terakhir peringatan ditampilkan
    let lastAlertShownTime = 0;
    let lastAlertData = null;

    // Mendapatkan activeSessionId dari PHP
    const activeSessionId = <?php echo $active_session ? (int)$active_session['id'] : 'null'; ?>;
    const classId = <?php echo (int)$class_id; ?>;

    // Fungsi load emotion alerts
    function loadEmotionAlerts(showModalIfAlerts = false) {
        if (!activeSessionId) {
            console.log("Tidak ada sesi aktif, tidak melakukan pengecekan alert.");
            return;
        }

        if ($('#emotionAlertModal').hasClass('show')) {
            $('#emotionAlertLoading').show();
        }
        $('#emotionAlertEmpty').hide();
        $('#emotionAlertContent').hide();

        console.log('Checking for emotion alerts at ' + new Date().toLocaleTimeString());

        // Pertama cek viewed alerts
        $.ajax({
            url: 'check_alert_viewed.php',
            type: 'GET',
            data: { class_session_id: activeSessionId },
            dataType: 'json',
            success: function(viewedData) {
                // Ambil alerts terbaru
                $.ajax({
                    url: '../check_emotion_alerts.php',
                    type: 'GET',
                    data: { class_session_id: activeSessionId },
                    dataType: 'json',
                    success: function(response) {
                        $('#emotionAlertLoading').hide();

                        if (response.status === 'success' && response.alerts && response.alerts.length > 0) {
                            let html = '';
                            let newAlertsExist = false;
                            const viewedAlertsArr = (viewedData.status === 'success' && viewedData.viewed_alerts) ? viewedData.viewed_alerts : [];

                            // Bandingkan alert terbaru dengan alert terakhir yang disimpan
                            let hasNewData = false;
                            if (lastAlertData) {
                                if (JSON.stringify(lastAlertData) !== JSON.stringify(response.alerts)) {
                                    hasNewData = true;
                                    console.log('Alert data has changed');
                                }
                            } else {
                                hasNewData = true;
                                console.log('First alert data received');
                            }
                            lastAlertData = response.alerts;

                            response.alerts.forEach(alert => {
                                const timestamp = new Date(alert.latest_timestamp);
                                const formattedTime = timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                                const formattedDate = timestamp.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

                                let severityClass = 'warning';
                                let severityText = 'Perhatian';
                                if (alert.severity === 'high') {
                                    severityClass = 'danger';
                                    severityText = 'Kritis';
                                } else if (alert.severity === 'medium') {
                                    severityClass = 'warning';
                                    severityText = 'Sedang';
                                } else {
                                    severityClass = 'info';
                                    severityText = 'Rendah';
                                }

                                // Cek apakah alert sudah dilihat
                                const isViewed = viewedAlertsArr.includes(alert.latest_timestamp);

                                if (!isViewed) {
                                    newAlertsExist = true;
                                    console.log('New alert found: ' + alert.latest_timestamp);
                                }

                                html += `
                                    <div class="alert alert-${severityClass} mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">${alert.class_name}</h6>
                                            <span class="badge bg-${severityClass}">${severityText}</span>
                                        </div>
                                        <div class="small text-muted mb-2">${formattedDate} ${formattedTime}</div>
                                        <div>
                                            <strong>${alert.negative_percentage}%</strong> emosi negatif (${alert.negative_count} dari ${alert.total_count})
                                        </div>
                                        <div class="mt-1">
                                            <strong>Emosi dominan:</strong> ${parseInt(alert.stress_count) >= parseInt(alert.tired_count) ? 'Stres' : 'Lelah'} (${parseInt(alert.stress_count) >= parseInt(alert.tired_count) ? alert.stress_count : alert.tired_count} dari ${alert.negative_count})
                                        </div>
                                        <div class="mt-2 small fst-italic">
                                            Mahasiswa: ${alert.affected_students}
                                        </div>
                                        <div class="mt-3 p-2 bg-light border rounded">
                                            <strong>Rekomendasi untuk Dosen:</strong><br>
                                            <span class="text-dark">${getEmotionRecommendation(alert.negative_percentage)}</span>
                                        </div>
                                    </div>
                                `;
                            });

                            $('#emotionAlertContent').html(html).show();

                            // Tampilkan modal dan suara jika perlu
                            const currentTime = new Date().getTime();
                            const timeSinceLastAlert = currentTime - lastAlertShownTime;
                            const shouldShowModal = showModalIfAlerts && newAlertsExist && !$('#emotionAlertModal').hasClass('show') && (timeSinceLastAlert > 60000 || lastAlertShownTime === 0);

                            console.log('Should show modal: ' + shouldShowModal);
                            console.log('Time since last alert: ' + (timeSinceLastAlert / 1000) + ' seconds');

                            if (shouldShowModal) {
                                lastAlertShownTime = currentTime;
                                $('#emotionAlertModal').modal('show');

                                // Simpan viewed alert ke database
                                response.alerts.forEach(alert => {
                                    if (!viewedAlertsArr.includes(alert.latest_timestamp)) {
                                        $.ajax({
                                            url: 'save_alert_view.php',
                                            type: 'POST',
                                            data: { 
                                                class_session_id: activeSessionId,
                                                alert_timestamp: alert.latest_timestamp
                                            },
                                            dataType: 'json'
                                        });
                                    }
                                });

                                // Buat dan putar suara alert hanya jika modal ditampilkan
                                // Gunakan teknik yang berbeda untuk memainkan suara
                                try {
                                    // Hapus audio lama jika ada
                                    const existingAudio = document.getElementById('emotionAlertSound');
                                    if (existingAudio) {
                                        document.body.removeChild(existingAudio);
                                    }
                                    
                                    // Buat audio baru dengan preload
                                    const audioElement = document.createElement('audio');
                                    audioElement.id = 'emotionAlertSound';
                                    audioElement.preload = 'auto';
                                    audioElement.style.display = 'none';
                                    audioElement.volume = 0.5; // Kurangi volume
                                    
                                    const sourceElement = document.createElement('source');
                                    sourceElement.src = '../assets/notification.mp3';
                                    sourceElement.type = 'audio/mpeg';
                                    audioElement.appendChild(sourceElement);
                                    document.body.appendChild(audioElement);
                                    
                                    // Gunakan Promise untuk memainkan suara
                                    const playPromise = audioElement.play();
                                    
                                    if (playPromise !== undefined) {
                                        playPromise.then(() => {
                                            console.log('Suara notifikasi berhasil diputar');
                                        }).catch(e => {
                                            console.log('Error playing sound:', e);
                                            // Jika gagal, coba lagi dengan event click
                                            document.addEventListener('click', function playAudioOnce() {
                                                audioElement.play();
                                                document.removeEventListener('click', playAudioOnce);
                                            }, { once: true });
                                        });
                                    }
                                    
                                    // Hapus elemen audio setelah selesai diputar
                                    audioElement.onended = function() {
                                        if (document.body.contains(audioElement)) {
                                            document.body.removeChild(audioElement);
                                        }
                                    };
                                } catch (e) {
                                    console.error('Error creating audio element:', e);
                                }
                            }
                        } else {
                            $('#emotionAlertEmpty').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#emotionAlertLoading').hide();
                        $('#emotionAlertContent').html('<div class="alert alert-danger">Terjadi kesalahan saat memuat data peringatan: ' + (error || 'Unknown error') + '</div>').show();
                        console.error('Error loading emotion alerts:', error);
                    }
                });
            },
            error: function(xhr, status, error) {
                console.error('Error checking viewed alerts:', error);
                // Jika gagal cek viewed alerts, tetap ambil alerts tanpa cek viewed
                $.ajax({
                    url: '../check_emotion_alerts.php',
                    type: 'GET',
                    data: { class_session_id: activeSessionId },
                    dataType: 'json',
                    success: function(response) {
                        $('#emotionAlertLoading').hide();

                        if (response.status === 'success' && response.alerts && response.alerts.length > 0) {
                            let html = '';
                            response.alerts.forEach(alert => {
                                const timestamp = new Date(alert.latest_timestamp);
                                const formattedTime = timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                                const formattedDate = timestamp.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });

                                let severityClass = 'warning';
                                let severityText = 'Perhatian';
                                if (alert.severity === 'high') {
                                    severityClass = 'danger';
                                    severityText = 'Kritis';
                                } else if (alert.severity === 'medium') {
                                    severityClass = 'warning';
                                    severityText = 'Sedang';
                                } else {
                                    severityClass = 'info';
                                    severityText = 'Rendah';
                                }

                                html += `
                                    <div class="alert alert-${severityClass} mb-3">
                                        <div class="d-flex justify-content-between align-items-center mb-2">
                                            <h6 class="mb-0">${alert.class_name}</h6>
                                            <span class="badge bg-${severityClass}">${severityText}</span>
                                        </div>
                                        <div class="small text-muted mb-2">${formattedDate} ${formattedTime}</div>
                                        <div>
                                            <strong>${alert.negative_percentage}%</strong> emosi negatif (${alert.negative_count} dari ${alert.total_count})
                                        </div>
                                        <div class="mt-1">
                                            <strong>Emosi dominan:</strong> ${parseInt(alert.stress_count) >= parseInt(alert.tired_count) ? 'Stres' : 'Lelah'} (${parseInt(alert.stress_count) >= parseInt(alert.tired_count) ? alert.stress_count : alert.tired_count} dari ${alert.negative_count})
                                        </div>
                                        <div class="mt-2 small fst-italic">
                                            Mahasiswa: ${alert.affected_students}
                                        </div>
                                        <div class="mt-3 p-2 bg-light border rounded">
                                            <strong>Rekomendasi untuk Dosen:</strong><br>
                                            <span class="text-dark">${getEmotionRecommendation(alert.negative_percentage)}</span>
                                        </div>
                                    </div>
                                `;
                            });

                            $('#emotionAlertContent').html(html).show();
                            // Tidak otomatis memunculkan modal dan suara saat error pengecekan viewed alerts
                        } else {
                            $('#emotionAlertEmpty').show();
                        }
                    },
                    error: function(xhr, status, error) {
                        $('#emotionAlertLoading').hide();
                        $('#emotionAlertContent').html('<div class="alert alert-danger">Terjadi kesalahan saat memuat data peringatan: ' + (error || 'Unknown error') + '</div>').show();
                    }
                });
            }
        });
    }

    // Fungsi rekomendasi berdasarkan persentase emosi negatif
    function getEmotionRecommendation(percentage) {
        if (percentage >= 60) {
            return "Kondisi kelas sangat tidak kondusif. Segera lakukan jeda, diskusi terbuka, atau pertimbangkan konseling.";
        } else if (percentage >= 40) {
            return "Segera ajak mahasiswa berdiskusi, tanyakan kendala, dan berikan dukungan emosional.";
        } else if (percentage >= 20) {
            return "Perhatikan kondisi kelas, lakukan ice breaking atau tanyakan perasaan mahasiswa.";
        } else {
            return "Kondisi kelas cukup baik. Tetap pantau dan jaga komunikasi dengan mahasiswa.";
        }
    }

    $(document).ready(function() {
        // Saat modal dibuka
        $('#emotionAlertModal').on('show.bs.modal', function () {
            loadEmotionAlerts(false);
        });

        // Saat modal ditutup
        $('#emotionAlertModal').on('hidden.bs.modal', function () {
            console.log('Modal closed');
            // Simpan viewed alert ke DB lewat AJAX (jika perlu)

            if (!activeSessionId) return;

            $.ajax({
                url: '../check_emotion_alerts.php',
                type: 'GET',
                data: { class_session_id: activeSessionId },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success' && response.alerts && response.alerts.length > 0) {
                        response.alerts.forEach(alert => {
                            $.ajax({
                                url: 'save_alert_view.php',
                                type: 'POST',
                                data: { 
                                    class_session_id: activeSessionId,
                                    alert_timestamp: alert.latest_timestamp
                                },
                                dataType: 'json'
                            });
                        });
                    }
                }
            });
        });

        <?php if ($active_session): ?>
        // Cek alert pertama kali 5 detik setelah load page
        setTimeout(function() {
            console.log('Running initial emotion alert check');
            loadEmotionAlerts(true);
        }, 5000);

        // Cek alert setiap 60 detik
        setInterval(function() {
            console.log('Running scheduled emotion alert check');
            loadEmotionAlerts(true);
        }, 60000);
        <?php endif; ?>
    });
</script>

</html>
