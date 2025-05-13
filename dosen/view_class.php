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
    
    // Function to load emotion alerts for this class
    function loadEmotionAlerts(showModalIfAlerts = false) {
        const classId = <?php echo $class_id; ?>;
        const activeSessionId = <?php echo $active_session ? $active_session['id'] : 'null'; ?>;
        
        // Show loading when modal is visible
        if ($('#emotionAlertModal').hasClass('show')) {
            $('#emotionAlertLoading').show();
            $('#emotionAlertContent').hide();
            $('#emotionAlertEmpty').hide();
        }
        
        // Make AJAX request to get alerts
        $.ajax({
            url: '../check_emotion_alerts.php',
            type: 'GET',
            data: { class_session_id: activeSessionId },
            dataType: 'json',
            success: function(response) {
                $('#emotionAlertLoading').hide();
                
                if (response.status === 'success' && response.alerts && response.alerts.length > 0) {
                    // We have alerts to display
                    let html = '';
                    let newAlertsExist = false;
                    
                    response.alerts.forEach(alert => {
                        const timestamp = new Date(alert.latest_timestamp);
                        const formattedTime = timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                        const formattedDate = timestamp.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
                        
                        let severityClass = '';
                        let severityText = '';
                        
                        if (alert.negative_percentage >= 60) {
                            severityClass = 'danger';
                            severityText = 'Tinggi';
                        } else if (alert.negative_percentage >= 40) {
                            severityClass = 'warning';
                            severityText = 'Sedang';
                        } else {
                            severityClass = 'info';
                            severityText = 'Rendah';
                        }
                        
                        // Create a unique key for this alert
                        const alertKey = `${activeSessionId}-${alert.class_id}`;
                        
                        // Get the stored values or set defaults
                        const lastTimestamp = viewedAlerts.timestamps[alertKey] || 0;
                        const lastPercentage = viewedAlerts.percentages[alertKey] || 0;
                        const lastShown = viewedAlerts.lastShown[alertKey] || 0;
                        // Track dominant emotion change
                        const lastStressCount = viewedAlerts.stressCount?.[alertKey] || 0;
                        const lastTiredCount = viewedAlerts.tiredCount?.[alertKey] || 0;
                        const currentTimestamp = new Date(alert.latest_timestamp).getTime();
                        const currentTime = new Date().getTime();
                        
                        // Debug information
                        console.log('Alert check:', {
                            alertKey,
                            lastTimestamp,
                            currentTimestamp,
                            isNewer: currentTimestamp > lastTimestamp,
                            lastPercentage,
                            currentPercentage: alert.negative_percentage,
                            percentageIncrease: alert.negative_percentage - lastPercentage,
                            lastShown,
                            timeSinceLastShown: (currentTime - lastShown) / 1000 + ' seconds'
                        });
                        
                        // Check if dominant emotion has changed
                        const currentStressCount = parseInt(alert.stress_count) || 0;
                        const currentTiredCount = parseInt(alert.tired_count) || 0;
                        const currentDominantEmotion = currentStressCount >= currentTiredCount ? 'stress' : 'tired';
                        const previousDominantEmotion = lastStressCount >= lastTiredCount ? 'stress' : 'tired';
                        const dominantEmotionChanged = currentDominantEmotion !== previousDominantEmotion;
                        
                        // Debug dominant emotion change
                        console.log('Dominant emotion check:', {
                            currentStressCount,
                            currentTiredCount,
                            lastStressCount,
                            lastTiredCount,
                            currentDominantEmotion,
                            previousDominantEmotion,
                            dominantEmotionChanged
                        });
                        
                        // An alert is considered new if ANY of these are true:
                        // 1. We've never seen it before (lastTimestamp === 0)
                        // 2. The timestamp is newer than the last one we've seen (new data)
                        // 3. The percentage has increased by at least 5%
                        // 4. The dominant emotion type has changed (from stress to tired or vice versa)
                        const isNewAlert = 
                            lastTimestamp === 0 || 
                            currentTimestamp > lastTimestamp || 
                            alert.negative_percentage >= (lastPercentage + 5) ||
                            (dominantEmotionChanged && lastTimestamp > 0);
                        
                        // Only show the modal if we haven't shown it recently (in the last 5 minutes)
                        // or if there's genuinely new data
                        const shouldShowModal = 
                            isNewAlert && (
                                lastShown === 0 || 
                                currentTime - lastShown > 5 * 60 * 1000 // 5 minutes in milliseconds
                            );
                        
                        if (shouldShowModal) {
                            newAlertsExist = true;
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
                            </div>
                        `;
                    });
                    
                    $('#emotionAlertContent').html(html).show();
                    
                    // Show modal automatically if there are NEW alerts and showModalIfAlerts is true
                    if (showModalIfAlerts && newAlertsExist && !$('#emotionAlertModal').hasClass('show')) {
                        console.log('Showing modal for new alerts');
                        $('#emotionAlertModal').modal('show');
                        
                        // Update lastShown timestamp for all alerts
                        response.alerts.forEach(alert => {
                            const alertKey = `${activeSessionId}-${alert.class_id}`;
                            viewedAlerts.lastShown[alertKey] = new Date().getTime();
                            viewedAlerts.timestamps[alertKey] = new Date(alert.latest_timestamp).getTime();
                            viewedAlerts.percentages[alertKey] = alert.negative_percentage;
                            // Store stress and tired counts
                            viewedAlerts.stressCount[alertKey] = parseInt(alert.stress_count) || 0;
                            viewedAlerts.tiredCount[alertKey] = parseInt(alert.tired_count) || 0;
                        });
                        
                        // Save to localStorage
                        saveViewedAlerts();
                        
                        // Hanya buat dan putar suara alert jika benar-benar ada alert baru
                        // dan jika alert belum pernah ditampilkan sebelumnya
                        if (newAlertsExist) {
                            console.log('Memainkan suara notifikasi untuk alert baru');
                            
                            // Buat elemen audio secara dinamis
                            const audioElement = document.createElement('audio');
                            audioElement.id = 'emotionAlertSound';
                            audioElement.style.display = 'none';
                            const sourceElement = document.createElement('source');
                            sourceElement.src = '../assets/notification.mp3';
                            sourceElement.type = 'audio/mpeg';
                            audioElement.appendChild(sourceElement);
                            document.body.appendChild(audioElement);
                            
                            // Putar suara
                            audioElement.play().catch(e => console.log('Error playing sound:', e));
                            
                            // Hapus elemen audio setelah selesai diputar
                            audioElement.onended = function() {
                                if (document.body.contains(audioElement)) {
                                    document.body.removeChild(audioElement);
                                }
                            };
                        }
                    }
                } else {
                    // No alerts
                    $('#emotionAlertEmpty').show();
                }
            },
            error: function(xhr, status, error) {
                $('#emotionAlertLoading').hide();
                $('#emotionAlertContent').html('<div class="alert alert-danger">Terjadi kesalahan saat memuat data peringatan: ' + (error || 'Unknown error') + '</div>').show();
                console.error('Error loading emotion alerts:', error);
            }
        });
    }
    
    // Initialize when modal is shown
    $(document).ready(function() {
        // When modal is shown, load alerts and mark them as viewed
        $('#emotionAlertModal').on('show.bs.modal', function () {
            loadEmotionAlerts();
        });
        
        // When modal is hidden, update the viewed alerts
        $('#emotionAlertModal').on('hidden.bs.modal', function () {
            console.log('Modal closed, updating viewed alerts');
            // Update all viewed alerts when modal is closed
            const activeSessionId = <?php echo $active_session ? $active_session['id'] : 'null'; ?>;
            if (activeSessionId) {
                // Process all alerts in the modal
                $('#emotionAlertContent .alert').each(function() {
                    // Extract class name to create a unique key
                    const className = $(this).find('h6').text().trim();
                    const alertKey = `${activeSessionId}-${<?php echo $class_id; ?>}`;
                    
                    // Update the lastShown timestamp to now
                    viewedAlerts.lastShown[alertKey] = new Date().getTime();
                    
                    // Extract and update percentage
                    const percentageText = $(this).find('strong').text();
                    const percentage = parseFloat(percentageText);
                    if (!isNaN(percentage)) {
                        viewedAlerts.percentages[alertKey] = Math.max(viewedAlerts.percentages[alertKey] || 0, percentage);
                    }
                    
                    // Extract and update timestamp
                    const dateTimeText = $(this).find('.small.text-muted').text();
                    if (dateTimeText) {
                        try {
                            const dateParts = dateTimeText.split(' ')[0].split('/');
                            const timeParts = dateTimeText.split(' ')[1].split(':');
                            const timestamp = new Date(
                                dateParts[2], // year
                                dateParts[1] - 1, // month (0-indexed)
                                dateParts[0], // day
                                timeParts[0], // hour
                                timeParts[1] // minute
                            ).getTime();
                            viewedAlerts.timestamps[alertKey] = Math.max(viewedAlerts.timestamps[alertKey] || 0, timestamp);
                        } catch (e) {
                            console.error('Error parsing date:', e);
                        }
                    }
                });
                
                // Save to localStorage
                saveViewedAlerts();
                console.log('Saved viewed alerts:', viewedAlerts);
            }
        });
        
        <?php if ($active_session): ?>
        // For active sessions, check for alerts periodically
        setInterval(function() {
            // Check for alerts, and show modal only if there are new alerts
            loadEmotionAlerts(true);
        }, 60000); // Check every 60 seconds
        
        // Initial check when page loads
        setTimeout(function() {
            loadEmotionAlerts(true);
        }, 5000); // Check after 5 seconds of page load
        <?php endif; ?>
    });
    </script>
</body>
</html>
