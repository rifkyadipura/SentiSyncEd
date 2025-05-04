<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get support notes
$stmt = $conn->prepare("
    SELECT sn.*, u.name as student_name
    FROM support_notes sn
    JOIN users u ON sn.user_id = u.id
    WHERE u.role = 'Mahasiswa'
    ORDER BY sn.timestamp DESC
");
$stmt->execute();
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
                                    <option value="">-- Pilih Kelas --</option>
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
                                        <th>Waktu</th>
                                        <th>Pesan</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($supportNotes as $note): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($note['student_name']); ?></td>
                                        <td><?php echo date('d/m/Y H:i', strtotime($note['timestamp'])); ?></td>
                                        <td><?php echo nl2br(htmlspecialchars($note['message'])); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
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
