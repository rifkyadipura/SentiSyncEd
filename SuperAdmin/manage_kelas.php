<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Hanya SuperAdmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error   = '';

// Helper logging
function log_sa(PDO $conn, string $msg): void {
    if (!empty($_SESSION['user_id'])) {
        $stmt = $conn->prepare('INSERT INTO logs (user_id, action) VALUES (?, ?)');
        $stmt->execute([$_SESSION['user_id'], $msg]);
    }
}

// ---------- PROSES POST (ADD / UPDATE) ----------
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    try {
        if ($action === 'add') {
            $name     = trim($_POST['class_name'] ?? '');
            $desc     = trim($_POST['description'] ?? '');
            $dosen_id = (int)($_POST['dosen_id'] ?? 0);
            if ($name === '' || $dosen_id === 0) {
                throw new Exception('Nama kelas dan dosen wajib diisi.');
            }
            $stmt = $conn->prepare('INSERT INTO classes (class_name, description, dosen_id) VALUES (?,?,?)');
            $stmt->execute([$name, $desc, $dosen_id]);
            $message = 'Kelas berhasil ditambahkan.';
            log_sa($conn, "Menambah kelas: $name");
        } elseif ($action === 'update') {
            $id       = (int)($_POST['class_id'] ?? 0);
            $name     = trim($_POST['class_name'] ?? '');
            $desc     = trim($_POST['description'] ?? '');
            $dosen_id = (int)($_POST['dosen_id'] ?? 0);
            if ($id === 0 || $name === '' || $dosen_id === 0) {
                throw new Exception('Data tidak lengkap.');
            }
            $stmt = $conn->prepare('UPDATE classes SET class_name=?, description=?, dosen_id=? WHERE id=?');
            $stmt->execute([$name, $desc, $dosen_id, $id]);
            $message = 'Kelas berhasil diperbarui.';
            log_sa($conn, "Memperbarui kelas: $name");
        }
    } catch (Exception $e) {
        $error = $e->getMessage();
    }
}

// ---------- PROSES GET (DELETE / END_SESSION) ----------
if (isset($_GET['action']) && isset($_GET['class_id'])) {
    $class_id = (int)$_GET['class_id'];
    if ($class_id > 0) {
        if ($_GET['action'] === 'delete') {
            try {
                $conn->beginTransaction();
                $name = $conn->query("SELECT class_name FROM classes WHERE id=$class_id")->fetchColumn();
                $conn->prepare('DELETE FROM classes WHERE id=?')->execute([$class_id]);
                $conn->commit();
                $message = 'Kelas berhasil dihapus.';
                log_sa($conn, "Menghapus kelas: $name");
            } catch (Exception $e) {
                $conn->rollBack();
                $error = 'Gagal menghapus kelas (mungkin ada relasi aktif).';
            }
        } elseif ($_GET['action'] === 'end_session') {
            $stmt = $conn->prepare("UPDATE class_sessions SET status='ended', end_time=NOW() WHERE class_id=? AND status='active'");
            $stmt->execute([$class_id]);
            $message = 'Sesi aktif kelas telah diakhiri.';
            log_sa($conn, "Mengakhiri sesi kelas ID: $class_id");
        }
    }
}

// ---------- DATA UNTUK TABEL ----------
$dosenList = $conn->query("SELECT id, name FROM users WHERE role='Dosen' ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);

$sql = "SELECT c.*, u.name AS dosen_name,
       (SELECT COUNT(*) FROM class_members cm WHERE cm.class_id=c.id) AS total_students,
       (SELECT COUNT(*) FROM class_sessions cs WHERE cs.class_id=c.id) AS total_sessions,
       (SELECT COUNT(*) FROM class_sessions cs WHERE cs.class_id=c.id AND cs.status='active') AS active_sessions
FROM classes c
JOIN users u ON u.id = c.dosen_id
ORDER BY c.class_name";
$classes = $conn->query($sql)->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Kelas - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/superadmin_common.css" rel="stylesheet">
</head>
<body>
<!-- Sidebar -->
 <div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white col-md-3 col-lg-2">
        <div class="sidebar-brand d-flex align-items-center justify-content-center position-relative">
            <i class="bi bi-bar-chart-line me-2"></i>
            <span>SentiSyncEd</span>
            <button class="btn-close-sidebar d-md-none position-absolute end-0 me-3 text-white bg-transparent border-0">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item"><a href="dashboard_admin.php" class="nav-link text-white"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
        <li><a href="manage_dosen.php" class="nav-link text-white"><i class="bi bi-person-badge"></i>Kelola Dosen</a></li>
        <li><a href="manage_mahasiswa.php" class="nav-link text-white"><i class="bi bi-mortarboard"></i>Kelola Mahasiswa</a></li>
        <li><a href="manage_kelas.php" class="nav-link active"><i class="bi bi-journal-text"></i>Kelola Kelas</a></li>
        <li><a href="analisis_emosi.php" class="nav-link text-white"><i class="bi bi-emoji-smile"></i>Analisis Emosi</a></li>
    </ul>
    <hr>
    <div class="dropdown">
        <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
            <div class="avatar me-2 bg-white"><i class="bi bi-person text-primary"></i></div>
            <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
        </a>
        <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
            <li><a class="dropdown-item" href="#">Profil</a></li>
            <li><a class="dropdown-item" href="#">Pengaturan</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1">Logout</a></li>
        </ul>
    </div>
</div>
<!-- Topbar -->
<div class="topbar d-flex align-items-center justify-content-between">
    <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
    <div class="d-none d-md-flex">
        <h4 class="mb-0">Kelola Kelas</h4>
    </div>
    <!-- Brand text only on mobile -->
    <div class="d-flex d-md-none">
        <span class="fw-semibold text-primary">SentiSyncEd</span>
    </div>
</div>
<!-- Main -->
<div class="main-content">
    <div class="content-wrapper">
        <?php if($message): ?><div class="alert alert-success alert-dismissible fade show" role="alert"><?= htmlspecialchars($message) ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <?php if($error):   ?><div class="alert alert-danger alert-dismissible fade show" role="alert"><?= htmlspecialchars($error)   ?><button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div><?php endif; ?>
        <div class="d-flex justify-content-end mb-3"><button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#tambahKelasModal"><i class="bi bi-plus"></i> Tambah Kelas</button></div>
        <div class="card">
            <div class="card-header"><h5 class="m-0">Daftar Kelas</h5></div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead class="table-light"><tr><th>Nama Kelas</th><th>Dosen</th><th>Siswa</th><th>Sesi</th><th>Status</th><th>Aksi</th></tr></thead>
                        <tbody>
                        <?php foreach($classes as $c): ?>
                            <tr>
                                <td><strong><?= htmlspecialchars($c['class_name']); ?></strong><br><small class="text-muted"><?= htmlspecialchars($c['description']); ?></small></td>
                                <td><?= htmlspecialchars($c['dosen_name']); ?></td>
                                <td><span class="badge bg-primary"><?= $c['total_students']; ?> Mahasiswa</span></td>
                                <td><span class="badge bg-info text-dark"><?= $c['total_sessions']; ?> Sesi</span></td>
                                <td>
                                    <?php if($c['active_sessions']>0): ?>
                                        <span class="badge badge-active">Aktif</span>
                                    <?php else: ?>
                                        <span class="badge badge-inactive">Tidak Aktif</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <button class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#editKelasModal" 
                                                data-id="<?= $c['id']; ?>" data-name="<?= htmlspecialchars($c['class_name']); ?>"
                                                data-desc="<?= htmlspecialchars($c['description']); ?>" data-dosen="<?= $c['dosen_id']; ?>">
                                            <i class="bi bi-pencil"></i>
                                        </button>
                                        <?php if($c['active_sessions']>0): ?>
                                            <a href="?action=end_session&class_id=<?= $c['id']; ?>" class="btn btn-sm btn-outline-warning" title="Akhiri Sesi" onclick="return confirm('Akhiri semua sesi aktif kelas ini?')"><i class="bi bi-stop-circle"></i></a>
                                        <?php endif; ?>
                                        <a href="?action=delete&class_id=<?= $c['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus kelas beserta data terkait?')"><i class="bi bi-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        <?php if(empty($classes)): ?><tr><td colspan="6" class="text-center">Belum ada kelas.</td></tr><?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal Tambah -->
<div class="modal fade" id="tambahKelasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST">
      <div class="modal-header"><h5 class="modal-title">Tambah Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="add">
        <div class="mb-3"><label class="form-label">Nama Kelas</label><input type="text" class="form-control" name="class_name" required></div>
        <div class="mb-3"><label class="form-label">Dosen Pengampu</label><select name="dosen_id" class="form-select" required><option value="">Pilih Dosen</option><?php foreach($dosenList as $d){echo '<option value="'.$d['id'].'">'.htmlspecialchars($d['name']).'</option>'; }?></select></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Simpan</button></div>
  </form></div>
</div>

<!-- Modal Edit -->
<div class="modal fade" id="editKelasModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><form class="modal-content" method="POST">
      <div class="modal-header"><h5 class="modal-title">Edit Kelas</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
      <div class="modal-body">
        <input type="hidden" name="action" value="update">
        <input type="hidden" name="class_id" id="edit_class_id">
        <div class="mb-3"><label class="form-label">Nama Kelas</label><input type="text" class="form-control" name="class_name" id="edit_class_name" required></div>
        <div class="mb-3"><label class="form-label">Dosen Pengampu</label><select name="dosen_id" id="edit_dosen_id" class="form-select" required><?php foreach($dosenList as $d){echo '<option value="'.$d['id'].'">'.htmlspecialchars($d['name']).'</option>'; }?></select></div>
        <div class="mb-3"><label class="form-label">Deskripsi</label><textarea name="description" id="edit_description" class="form-control" rows="3"></textarea></div>
      </div>
      <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button><button type="submit" class="btn btn-primary">Perbarui</button></div>
  </form></div>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded',()=>{
    const sidebar      = document.querySelector('.sidebar');
    const sidebarToggle= document.getElementById('sidebarToggle');
    const closeBtn     = document.querySelector('.btn-close-sidebar');

    if(sidebarToggle){
        sidebarToggle.addEventListener('click',()=>sidebar.classList.toggle('show'));
    }
    if(closeBtn){
        closeBtn.addEventListener('click',()=>sidebar.classList.remove('show'));
    }
    // Tutup sidebar saat klik di luar pada mobile
    document.addEventListener('click',e=>{
        if(window.innerWidth<=768 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)){
            sidebar.classList.remove('show');
        }
    });

    // isi modal edit
    const editModal=document.getElementById('editKelasModal');
    if(editModal){
        editModal.addEventListener('show.bs.modal',e=>{
            const btn=e.relatedTarget;
            document.getElementById('edit_class_id').value=btn.dataset.id;
            document.getElementById('edit_class_name').value=btn.dataset.name;
            document.getElementById('edit_description').value=btn.dataset.desc;
            document.getElementById('edit_dosen_id').value=btn.dataset.dosen;
        });
    }
});
</script>
</body>
</html>
