<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

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
</head>
<body>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>

    <div class="content-wrapper">
        <h1 class="page-title">Daftar Curhat Mahasiswa</h1>
        <div class="row mb-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">Pilih Kelas</div>
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
                <div class="card">
                    <div class="card-header">Data Curhat Mahasiswa</div>
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
