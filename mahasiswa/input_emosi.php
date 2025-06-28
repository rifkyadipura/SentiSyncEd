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

// Get active class sessions for classes where the student is enrolled
try {
    $stmt = $conn->prepare("
        SELECT cs.id as class_session_id, cs.start_time, c.class_name, u.name as dosen_name
        FROM class_sessions cs
        JOIN classes c ON cs.class_id = c.id
        JOIN users u ON c.dosen_id = u.id
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ? 
        AND cs.status = 'active'
        AND cs.end_time IS NULL
        ORDER BY cs.start_time DESC
    ");
    $stmt->execute([$user_id]);
    $active_sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil sesi kelas.";
    $active_sessions = [];
}

// Handle emotion submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $emotion = filter_input(INPUT_POST, 'emotion', FILTER_SANITIZE_STRING);
    $class_session_id = filter_input(INPUT_POST, 'class_session_id', FILTER_SANITIZE_NUMBER_INT);
    
    // Verify session exists and is active for this student
    $stmt = $conn->prepare("
        SELECT cs.id FROM class_sessions cs
        JOIN class_members cm ON cs.class_id = cm.class_id
        WHERE cs.id = ? AND cm.user_id = ? AND cs.status = 'active' AND cs.end_time IS NULL
    ");
    $stmt->execute([$class_session_id, $user_id]);
    
    if ($stmt->rowCount() > 0) {
        try {
            // Begin transaction
            $conn->beginTransaction();
            
            // First, check if we already have a session for this class_session_id
            $sessionStmt = $conn->prepare("SELECT id FROM sessions WHERE id = (SELECT MIN(session_id) FROM emotions WHERE class_session_id = ? LIMIT 1)");
            $sessionStmt->execute([$class_session_id]);
            $sessionId = $sessionStmt->fetchColumn();
            
            // If no session exists, create one
            if (!$sessionId) {
                // Get the start_time from class_sessions to use for the session
                $timeStmt = $conn->prepare("SELECT start_time FROM class_sessions WHERE id = ?");
                $timeStmt->execute([$class_session_id]);
                $startTime = $timeStmt->fetchColumn() ?: date('Y-m-d H:i:s');
                
                // Create a new session
                $sessionStmt = $conn->prepare("INSERT INTO sessions (start_time) VALUES (?)");
                $sessionStmt->execute([$startTime]);
                $sessionId = $conn->lastInsertId();
            }
            
            // Now insert the emotion with the valid session_id
            $stmt = $conn->prepare("INSERT INTO emotions (user_id, emotion, class_session_id, session_id) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $emotion, $class_session_id, $sessionId]);
            
            // Commit transaction
            $conn->commit();
            
            logAction($conn, $user_id, "Recorded emotion: " . $emotion . " for class_session_id: " . $class_session_id);
            $success_message = "Emosi berhasil disimpan!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "Terjadi kesalahan saat menyimpan emosi: " . $e->getMessage();
        }
    } else {
        $error_message = "Sesi kelas tidak valid atau sudah berakhir.";
    }
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Emosi - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
        .session-card {
            cursor: pointer;
            transition: all 0.3s ease;
            border: 2px solid transparent;
        }
        
        .session-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.1);
        }
        
        .emotion-content {
            border: 2px solid #e9ecef;
            transition: all 0.3s ease;
            cursor: pointer;
        }
        
        .emotion-option input[type="radio"]:checked + .emotion-content {
            border-color: #28a745;
            background-color: #f8f9fa;
        }
        
        .emotion-content:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
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
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
            </a>
            <a href="input_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
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
            <h1 class="page-title mb-4">Input Emosi</h1>

            <!-- Alert messages -->
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

            <!-- Main content -->
            <div class="row">
                <div class="col-12">
                    <div class="card mb-4">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Pilih Kelas yang Sedang Aktif</h5>
                        </div>
                        <div class="card-body">
                            <?php if (empty($active_sessions)): ?>
                                <div class="text-center py-5">
                                    <i class="bi bi-info-circle text-primary" style="font-size: 3rem;"></i>
                                    <p class="mt-3">Tidak ada sesi kelas yang aktif saat ini.</p>
                                    <p>Silakan tunggu dosen membuka sesi kelas.</p>
                                </div>
                            <?php else: ?>
                                <div class="row">
                                    <?php foreach ($active_sessions as $session): ?>
                                        <div class="col-md-4 col-sm-6 mb-3">
                                            <div class="card h-100 session-card clickable" id="session-<?php echo $session['class_session_id']; ?>" data-session-id="<?php echo $session['class_session_id']; ?>">
                                                <div class="card-body">
                                                    <h5 class="card-title"><?php echo htmlspecialchars($session['class_name']); ?></h5>
                                                    <p class="card-text"><strong>Dosen:</strong> <?php echo htmlspecialchars($session['dosen_name']); ?></p>
                                                    <p class="card-text"><strong>Mulai:</strong> <?php echo date('d/m/Y H:i', strtotime($session['start_time'])); ?></p>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>

                                <div id="emotion-form-container" style="display: none;" class="mt-4">
                                    <div class="card mb-4">
                                        <div class="card-header bg-light">
                                            <h5 class="mb-0">Kelas yang Dipilih</h5>
                                        </div>
                                        <div class="card-body">
                                            <h5 id="selected-class-name" class="mb-2"></h5>
                                            <p><strong>Dosen:</strong> <span id="selected-dosen-name"></span></p>
                                            <div class="badge bg-primary p-2">
                                                <i class="bi bi-clock me-1"></i> 
                                                <span id="selected-stopwatch">00:00:00</span>
                                            </div>
                                        </div>
                                    </div>

                                    <div class="card">
                                        <div class="card-header">
                                            <h5 class="mb-0">Bagaimana perasaan Anda saat ini?</h5>
                                        </div>
                                        <div class="card-body">
                                            <form method="POST" action="">
                                                <input type="hidden" name="class_session_id" id="class_session_id" value="">
                                                
                                                <div class="row justify-content-center text-center">
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <label class="emotion-option w-100">
                                                            <input type="radio" name="emotion" value="Senang" required class="d-none">
                                                            <div class="emotion-content p-3 rounded h-100">
                                                                <span class="emotion-emoji fs-1">😊</span>
                                                                <span class="emotion-label d-block mt-2">Senang</span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <label class="emotion-option w-100">
                                                            <input type="radio" name="emotion" value="Stres" required class="d-none">
                                                            <div class="emotion-content p-3 rounded h-100">
                                                                <span class="emotion-emoji fs-1">😰</span>
                                                                <span class="emotion-label d-block mt-2">Stres</span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <label class="emotion-option w-100">
                                                            <input type="radio" name="emotion" value="Lelah" required class="d-none">
                                                            <div class="emotion-content p-3 rounded h-100">
                                                                <span class="emotion-emoji fs-1">😫</span>
                                                                <span class="emotion-label d-block mt-2">Lelah</span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                    <div class="col-md-3 col-sm-6 mb-3">
                                                        <label class="emotion-option w-100">
                                                            <input type="radio" name="emotion" value="Netral" required class="d-none">
                                                            <div class="emotion-content p-3 rounded h-100">
                                                                <span class="emotion-emoji fs-1">😐</span>
                                                                <span class="emotion-label d-block mt-2">Netral</span>
                                                            </div>
                                                        </label>
                                                    </div>
                                                </div>
                                                
                                                <div class="text-center mt-4">
                                                    <button type="submit" class="btn btn-primary px-4 py-2">
                                                        <i class="bi bi-save me-2"></i>
                                                        Simpan Emosi
                                                    </button>
                                                </div>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
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

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Toggle sidebar on mobile
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

            // Store session data for easy access
            const sessionData = {};
            <?php foreach ($active_sessions as $session): ?>
                sessionData[<?php echo $session['class_session_id']; ?>] = {
                    className: "<?php echo addslashes(htmlspecialchars($session['class_name'])); ?>",
                    dosenName: "<?php echo addslashes(htmlspecialchars($session['dosen_name'])); ?>",
                    startTime: "<?php echo $session['start_time']; ?>"
                };
            <?php endforeach; ?>
            
            // Handle session card clicks
            const sessionCards = document.querySelectorAll('.session-card.clickable');
            sessionCards.forEach(card => {
                card.addEventListener('click', function() {
                    // Get session ID from data attribute
                    const sessionId = this.getAttribute('data-session-id');
                    
                    // Reset all cards to default style
                    sessionCards.forEach(c => {
                        c.classList.remove('border-primary', 'bg-light');
                    });
                    
                    // Highlight selected card
                    this.classList.add('border-primary', 'bg-light');
                    
                    // Set the hidden input value
                    document.getElementById('class_session_id').value = sessionId;
                    
                    // Update selected class info
                    const sessionInfo = sessionData[sessionId];
                    document.getElementById('selected-class-name').textContent = sessionInfo.className;
                    document.getElementById('selected-dosen-name').textContent = sessionInfo.dosenName;
                    
                    // Initialize stopwatch
                    const stopwatch = document.getElementById('selected-stopwatch');
                    const startTime = new Date(sessionInfo.startTime);
                    
                    // Clear any existing interval
                    if (window.stopwatchInterval) {
                        clearInterval(window.stopwatchInterval);
                    }
                    
                    function updateStopwatch() {
                        const now = new Date();
                        const diff = Math.floor((now - startTime) / 1000); // difference in seconds
                        
                        const hours = Math.floor(diff / 3600);
                        const minutes = Math.floor((diff % 3600) / 60);
                        const seconds = diff % 60;
                        
                        // Format as HH:MM:SS
                        const formattedTime = 
                            String(hours).padStart(2, '0') + ':' + 
                            String(minutes).padStart(2, '0') + ':' + 
                            String(seconds).padStart(2, '0');
                        
                        stopwatch.textContent = formattedTime;
                    }
                    
                    // Update immediately and then every second
                    updateStopwatch();
                    window.stopwatchInterval = setInterval(updateStopwatch, 1000);
                    
                    // Show emotion form
                    document.getElementById('emotion-form-container').style.display = 'block';
                    
                    // Smooth scroll to emotion form
                    document.getElementById('emotion-form-container').scrollIntoView({ behavior: 'smooth' });
                });
            });

            // Emotion option selection styling
            const emotionOptions = document.querySelectorAll('.emotion-option input');
            emotionOptions.forEach(option => {
                option.addEventListener('change', function() {
                    // Reset all options
                    document.querySelectorAll('.emotion-content').forEach(content => {
                        content.classList.remove('border-success', 'bg-light');
                    });
                    
                    // Highlight selected option
                    if (this.checked) {
                        this.parentElement.querySelector('.emotion-content').classList.add('border-success', 'bg-light');
                    }
                });
            });
        });
    </script>
</body>
</html>
