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
<html lang="id">
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
                <a href="dashboard_dosen.php" class="btn btn-outline-primary mb-3"><i class="bi bi-arrow-left me-2"></i> Kembali ke Dashboard</a>
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
                                <form method="POST" action="" class="mt-2">
                                    <input type="hidden" name="session_id" value="<?php echo $active_session['id']; ?>">
                                    <button type="submit" name="end_session" class="btn btn-danger" onclick="return confirm('Yakin ingin mengakhiri sesi kelas?')">
                                        <i class="fas fa-stop-circle"></i> Akhiri Sesi
                                    </button>
                                </form>
                            </div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
