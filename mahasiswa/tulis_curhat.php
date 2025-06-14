<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Get classes the student is enrolled in
try {
    $stmt = $conn->prepare("
        SELECT c.id, c.class_name
        FROM classes c
        JOIN class_members cm ON c.id = cm.class_id
        WHERE cm.user_id = ?
        ORDER BY c.class_name
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil daftar kelas.";
    $classes = [];
}

// Handle support note submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);
    $class_id = filter_input(INPUT_POST, 'class_id', FILTER_SANITIZE_NUMBER_INT);
    $user_id = $_SESSION['user_id'];
    
    // Validate class selection
    if (empty($class_id)) {
        $error_message = "Silakan pilih kelas terlebih dahulu.";
    } else {
        try {
            // Begin transaction for data consistency
            $conn->beginTransaction();
            
            // First, ensure we have a valid session in the sessions table
            $sessionId = null;
            
            // 1. Check if there's already a valid session in the sessions table
            $sessionStmt = $conn->prepare("SELECT id FROM sessions ORDER BY id DESC LIMIT 1");
            $sessionStmt->execute();
            $sessionRow = $sessionStmt->fetch(PDO::FETCH_ASSOC);
            
            if ($sessionRow) {
                // Use existing session
                $sessionId = $sessionRow['id'];
            } else {
                // Create a new session if none exists
                $sessionStmt = $conn->prepare("INSERT INTO sessions (start_time) VALUES (NOW())");
                $sessionStmt->execute();
                $sessionId = $conn->lastInsertId();
            }
            
            // Check if class_id column exists in support_notes table
            $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
            $stmt->execute();
            $class_id_exists = $stmt->rowCount() > 0;
            
            if ($class_id_exists) {
                $stmt = $conn->prepare("INSERT INTO support_notes (user_id, class_id, message, session_id) VALUES (?, ?, ?, ?)");
                $stmt->execute([$user_id, $class_id, $message, $sessionId]);
            } else {
                // Fallback if class_id column doesn't exist
                $stmt = $conn->prepare("INSERT INTO support_notes (user_id, message, session_id) VALUES (?, ?, ?)");
                $stmt->execute([$user_id, $message, $sessionId]);
            }
            
            // Commit the transaction
            $conn->commit();
            
            logAction($conn, $user_id, "Added support note for class ID: $class_id");
            $success_message = "Curhat berhasil disimpan!";
        } catch (PDOException $e) {
            // Rollback transaction on error
            if ($conn->inTransaction()) {
                $conn->rollBack();
            }
            $error_message = "Terjadi kesalahan saat menyimpan curhat: " . $e->getMessage();
        }
    }
}

// Get previous notes with class information
try {
    // Check if class_id column exists in support_notes table
    $stmt = $conn->prepare("SHOW COLUMNS FROM support_notes LIKE 'class_id'");
    $stmt->execute();
    $class_id_exists = $stmt->rowCount() > 0;
    
    if ($class_id_exists) {
        $stmt = $conn->prepare("
            SELECT sn.message, sn.timestamp, c.class_name
            FROM support_notes sn
            LEFT JOIN classes c ON sn.class_id = c.id
            WHERE sn.user_id = ? 
            ORDER BY sn.timestamp DESC 
            LIMIT 5
        ");
    } else {
        $stmt = $conn->prepare("
            SELECT sn.message, sn.timestamp, NULL as class_name
            FROM support_notes sn
            WHERE sn.user_id = ? 
            ORDER BY sn.timestamp DESC 
            LIMIT 5
        ");
    }
    
    $stmt->execute([$_SESSION['user_id']]);
    $previous_notes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = "Terjadi kesalahan saat mengambil riwayat curhat: " . $e->getMessage();
    $previous_notes = [];
}
?>

<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Tulis Curhat - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
        .note-card {
            border-left: 4px solid #4A90E2;
            transition: all 0.3s ease;
        }
        
        .note-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        
        textarea.form-control {
            min-height: 150px;
            resize: vertical;
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
            <a href="input_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-emoji-smile me-2"></i> Input Emosi
            </a>
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
            </a>
            <a href="tulis_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-chat-dots me-2"></i> Tulis Curhat
            </a>
            <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
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
            <h1 class="page-title mb-4">Tulis Curhat</h1>

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
                <div class="col-lg-8 col-md-12 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Tulis Curhat Anda</h5>
                        </div>
                        <div class="card-body">
                            <form method="POST" action="" id="curhatForm">
                                <div class="mb-3">
                                    <label for="class_id" class="form-label">Pilih Kelas:</label>
                                    <select name="class_id" id="class_id" class="form-select" required onchange="showMessageInput()">
                                        <option value="">-- Pilih Kelas --</option>
                                        <?php foreach ($classes as $class): ?>
                                            <option value="<?php echo $class['id']; ?>"><?php echo htmlspecialchars($class['class_name']); ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3" id="messageInputContainer" style="display: none;">
                                    <label for="message" class="form-label">Bagaimana perasaan Anda? Ceritakan di sini:</label>
                                    <textarea name="message" id="message" class="form-control" required placeholder="Tuliskan curhat Anda..."></textarea>
                                </div>
                                <div class="text-end">
                                    <button type="submit" class="btn btn-primary" id="submitButton" style="display: none;">
                                        <i class="bi bi-send me-2"></i>
                                        Kirim Curhat
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-4 col-md-12 mb-4">
                    <?php if (!empty($previous_notes)): ?>
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0">Riwayat Curhat Terakhir</h5>
                            </div>
                            <div class="card-body p-0">
                                <div class="list-group list-group-flush">
                                    <?php foreach ($previous_notes as $note): ?>
                                        <div class="list-group-item note-card p-3">
                                            <div class="d-flex justify-content-between align-items-center mb-2">
                                                <small class="text-muted">
                                                    <i class="bi bi-clock me-1"></i>
                                                    <?php echo date('d/m/Y H:i', strtotime($note['timestamp'])); ?>
                                                </small>
                                                <?php if (!empty($note['class_name'])): ?>
                                                    <span class="badge bg-primary"><?php echo htmlspecialchars($note['class_name']); ?></span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-0"><?php echo htmlspecialchars($note['message']); ?></p>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    <?php else: ?>
                        <div class="card">
                            <div class="card-body text-center py-5">
                                <i class="bi bi-chat-square-text text-muted" style="font-size: 3rem;"></i>
                                <p class="mt-3 text-muted">Belum ada riwayat curhat</p>
                            </div>
                        </div>
                    <?php endif; ?>
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
            
            // Function to show/hide message input based on class selection
            function showMessageInput() {
                var classSelect = document.getElementById('class_id');
                var messageContainer = document.getElementById('messageInputContainer');
                var submitButton = document.getElementById('submitButton');
                
                if (classSelect.value !== '') {
                    messageContainer.style.display = 'block';
                    submitButton.style.display = 'block';
                } else {
                    messageContainer.style.display = 'none';
                    submitButton.style.display = 'none';
                }
            }
            
            // Initialize the form state
            showMessageInput();
            
            // Add event listener to the class select
            const classSelect = document.getElementById('class_id');
            if (classSelect) {
                classSelect.addEventListener('change', showMessageInput);
            }
        });
    </script>
</body>
</html>
