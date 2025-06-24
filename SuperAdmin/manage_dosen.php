<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Verify SuperAdmin access
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !isset($_POST['edit_id'])) {
    $name = isset($_POST['name']) ? htmlspecialchars(trim($_POST['name']), ENT_QUOTES, 'UTF-8') : '';
    $email = isset($_POST['email']) ? filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL) : '';
    $password = $_POST['password'] ?? '';
    
    // Validate input
    if (empty($name) || empty($email) || empty($password)) {
        $error = 'Semua field harus diisi';
    } else {
        try {
            // Check if email already exists
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            if ($stmt->fetch()) {
                $error = 'Email sudah terdaftar';
            } else {
                // Insert new dosen
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Dosen')");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                $message = "Dosen berhasil didaftarkan!";
                logAction($conn, $_SESSION['user_id'], "Registered new dosen: $email");
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// ---------- ACTION HANDLERS ---------- //
// 1. Ajax request for detail
if (isset(
        $_GET['action']) && $_GET['action'] === 'detail' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    $stmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? AND role = 'Dosen'");
    $stmt->execute([$id]);
    $dosen = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($dosen) {
        // get classes taught
        $kelasStmt = $conn->prepare("SELECT id, class_name FROM classes WHERE dosen_id = ? ORDER BY class_name");
        $kelasStmt->execute([$id]);
        $dosen['classes'] = $kelasStmt->fetchAll(PDO::FETCH_ASSOC);
    }
    header('Content-Type: application/json');
    echo json_encode($dosen ?: []);
    exit();
}

// 2. Delete lecturer
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int) $_GET['id'];
    try {
        $check = $conn->prepare("SELECT COUNT(*) FROM classes WHERE dosen_id = ?");
        $check->execute([$id]);
        if ($check->fetchColumn() > 0) {
            $error = 'Dosen masih memiliki kelas terdaftar dan tidak dapat dihapus';
        } else {
            $del = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'Dosen'");
            $del->execute([$id]);
            $message = 'Dosen berhasil dihapus';
            logAction($conn, $_SESSION['user_id'], "Deleted dosen #$id");
        }
    } catch (PDOException $e) {
        $error = 'Gagal menghapus dosen: ' . $e->getMessage();
    }
}

// 3. Update lecturer (from edit modal)
if (isset($_POST['edit_id'])) {
    $editId  = (int) $_POST['edit_id'];
    $name    = htmlspecialchars(trim($_POST['edit_name'] ?? ''), ENT_QUOTES, 'UTF-8');
    $email   = filter_input(INPUT_POST, 'edit_email', FILTER_SANITIZE_EMAIL);
    $pwdRaw  = $_POST['edit_password'] ?? '';
    try {
        $upd = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'Dosen'");
        $upd->execute([$name, $email, $editId]);
        if (!empty($pwdRaw)) {
            $hashed = password_hash($pwdRaw, PASSWORD_DEFAULT);
            $pwd = $conn->prepare("UPDATE users SET password = ? WHERE id = ?");
            $pwd->execute([$hashed, $editId]);
        }
        $message = 'Data dosen berhasil diperbarui';
        logAction($conn, $_SESSION['user_id'], "Updated dosen #$editId");
    } catch (PDOException $e) {
        $error = 'Gagal memperbarui dosen: ' . $e->getMessage();
    }
}

// ---------- FETCH LIST ---------- //
// Ambil daftar dosen beserta jumlah kelas yang mereka ampu
$stmt = $conn->query("SELECT u.id, u.name, u.email, COUNT(c.id) AS total_kelas FROM users u LEFT JOIN classes c ON c.dosen_id = u.id WHERE u.role = 'Dosen' GROUP BY u.id ORDER BY u.name");
$dosenList = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Dosen - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
            color: #495057;
            overflow-x: hidden;
        }
        
        .sidebar {
            background-color: #3b8adb;
            background-image: linear-gradient(180deg, #3b8adb 10%, #3b8adb 100%);
            min-height: 100vh;
            position: fixed;
            z-index: 1030;
            width: 250px;
            transition: all 0.3s ease;
        }
        
        .btn-close-sidebar {
            font-size: 1.5rem;
            cursor: pointer;
            opacity: 0.8;
            transition: all 0.2s ease;
        }
        
        .btn-close-sidebar:hover {
            opacity: 1;
            transform: scale(1.1);
        }
        
        .sidebar .nav-link {
            color: rgba(255, 255, 255, 0.8);
            padding: 1rem;
            font-weight: 500;
            border-radius: 0.5rem;
            margin: 0.2rem 0;
        }
        
        .sidebar .nav-link:hover, .sidebar .nav-link.active {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }
        
        .sidebar .nav-link i {
            margin-right: 0.5rem;
        }
        
        .sidebar-brand {
            padding: 1.5rem 1rem;
            color: white;
            font-weight: 700;
            font-size: 1.2rem;
            text-align: center;
        }
        
        .main-content {
            margin-left: 250px;
            padding: 2rem;
        }
        
        .card {
            border: none;
            border-radius: 0.75rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            margin-bottom: 1.5rem;
        }
        
        .card-header {
            background-color: #fff;
            border-bottom: 1px solid #e3e6f0;
            padding: 1rem 1.25rem;
            font-weight: 600;
        }
        
        .table-responsive {
            overflow-x: auto;
        }
        
        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background-color: #e9ecef;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 600;
            color: #495057;
        }
        
        .dropdown-menu {
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            border: none;
        }
        
        .dropdown-item {
            padding: 0.5rem 1rem;
        }
        
        .dropdown-item:hover {
            background-color: #f8f9fa;
        }
        
        .topbar {
            height: 4.5rem;
            box-shadow: 0 0.15rem 1.75rem rgba(33, 40, 50, 0.15);
            background-color: white;
            position: fixed;
            top: 0;
            right: 0;
            left: 250px;
            z-index: 1;
            padding: 0 1.5rem;
        }
        
        .content-wrapper {
            padding-top: 4.5rem;
        }
        
        @media (max-width: 768px) {
            .sidebar {
                width: 100%;
                position: fixed;
                min-height: 100vh;
                transform: translateX(-100%);
            }
            
            .sidebar.show {
                transform: translateX(0);
            }
            
            .main-content {
                margin-left: 0;
                width: 100%;
            }
            
            .topbar {
                left: 0;
                width: 100%;
            }
            
            .content-wrapper {
                padding-top: 5.5rem;
            }
        }
    </style>
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
            <li class="nav-item">
                <a href="dashboard_admin.php" class="nav-link text-white">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="manage_dosen.php" class="nav-link active">
                    <i class="bi bi-person-badge"></i>
                    Kelola Dosen
                </a>
            </li>
            <li>
                <a href="manage_mahasiswa.php" class="nav-link text-white">
                    <i class="bi bi-mortarboard"></i>
                    Kelola Mahasiswa
                </a>
            </li>
            <li>
                <a href="manage_kelas.php" class="nav-link text-white">
                    <i class="bi bi-journal-text"></i>
                    Kelola Kelas
                </a>
            </li>
            <li>
                <a href="analisis_emosi.php" class="nav-link text-white">
                    <i class="bi bi-emoji-smile"></i>
                    Analisis Emosi
                </a>
            </li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar me-2 bg-white">
                    <i class="bi bi-person text-primary"></i>
                </div>
                <strong><?= htmlspecialchars($_SESSION['name']) ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="edit_profile.php">Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <button class="btn btn-link d-md-none rounded-circle me-3">
            <i class="bi bi-list"></i>
        </button>
        <div class="d-none d-md-flex">
            <h4 class="mb-0">Kelola Dosen</h4>
        </div>
        <!-- Brand text only on mobile -->
        <div class="d-flex d-md-none">
            <span class="fw-semibold text-primary">SentiSyncEd</span>
        </div>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Page Content -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold">Tambah Dosen Baru</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($message) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                    <?= htmlspecialchars($error) ?>
                                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="name" class="form-label">Nama Dosen</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                        <div class="invalid-feedback">
                                            Nama dosen wajib diisi
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" required>
                                        <div class="invalid-feedback">
                                            Email wajib diisi dengan format yang benar
                                        </div>
                                    </div>

                                    <div class="col-md-4 mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <input type="password" class="form-control" id="password" name="password" required>
                                        <div class="invalid-feedback">
                                            Password wajib diisi
                                        </div>
                                    </div>
                                </div>

                                <div class="d-flex">
                                    <button type="submit" class="btn btn-primary me-2">
                                        <i class="bi bi-person-plus-fill me-1"></i> Daftarkan Dosen
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Dosen -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold">Daftar Dosen</h5>
                            <div class="d-flex">
                                <input type="text" id="searchDosen" class="form-control form-control-sm me-2" placeholder="Cari dosen...">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($dosenList as $dosen): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-2 bg-primary text-white">
                                                            <?= substr($dosen['name'], 0, 1) ?>
                                                        </div>
                                                        <?= htmlspecialchars($dosen['name']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($dosen['email']) ?></td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item view-detail" href="#" data-id="<?= $dosen['id'] ?>"><i class="bi bi-eye me-1"></i> Lihat Detail</a></li>
                                                            <li><a class="dropdown-item edit-dosen" href="#" data-id="<?= $dosen['id'] ?>"><i class="bi bi-pencil me-1"></i> Edit</a></li>
                                                            <li><a class="dropdown-item text-danger delete-dosen" href="manage_dosen.php?action=delete&id=<?= $dosen['id'] ?>" onclick="return confirm('Yakin ingin menghapus dosen ini?');"><i class="bi bi-trash me-1"></i> Hapus</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($dosenList)): ?>
                                            <tr>
                                                <td colspan="3" class="text-center py-3">Belum ada dosen yang terdaftar</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Form validation
            (function () {
                'use strict'
                var forms = document.querySelectorAll('.needs-validation')
                Array.prototype.slice.call(forms).forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        form.classList.add('was-validated')
                    }, false)
                })
            })();
            
            // Toggle sidebar on small screens
            document.querySelector('.btn-link').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.toggle('show');
            });
            
            // Close sidebar with the close button
            document.querySelector('.btn-close-sidebar').addEventListener('click', function() {
                document.querySelector('.sidebar').classList.remove('show');
            });
            
            // Close sidebar when clicking outside on mobile
            document.addEventListener('click', function(event) {
                const sidebar = document.querySelector('.sidebar');
                const toggleBtn = document.querySelector('.btn-link');
                
                if (window.innerWidth <= 768 && 
                    sidebar.classList.contains('show') && 
                    !sidebar.contains(event.target) && 
                    !toggleBtn.contains(event.target)) {
                    sidebar.classList.remove('show');
                }
            });
            
            // Simple search functionality for the dosen table
            document.getElementById('searchDosen')?.addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                const rows = document.querySelectorAll('tbody tr');
                
                rows.forEach(row => {
                    const name = row.cells[0]?.textContent.toLowerCase() || '';
                    const email = row.cells[1]?.textContent.toLowerCase() || '';
                    
                    if (name.includes(searchText) || email.includes(searchText)) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                });
            });
        });
    // ---- Modal detail & edit handler ---- //
</script>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Detail Dosen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <p><strong>Nama:</strong> <span id="detailName"></span></p>
        <p><strong>Email:</strong> <span id="detailEmail"></span></p>
        <p><strong>Tanggal Registrasi:</strong> <span id="detailCreated"></span></p>
        <hr>
        <h6>Kelas yang Diampu</h6>
        <ul id="detailClasses" class="list-group"></ul>
      </div>
    </div>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <form method="POST" class="needs-validation" novalidate>
        <div class="modal-header">
          <h5 class="modal-title">Edit Dosen</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <input type="hidden" name="edit_id" id="editId">
          <div class="mb-3">
            <label class="form-label">Nama</label>
            <input type="text" class="form-control" name="edit_name" id="editName" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Email</label>
            <input type="email" class="form-control" name="edit_email" id="editEmail" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Password Baru (opsional)</label>
            <input type="password" class="form-control" name="edit_password" id="editPassword">
          </div>
        </div>
        <div class="modal-footer">
          <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
        </div>
      </form>
    </div>
  </div>
</div>

<script>
// Event handlers for detail & edit dropdown actions
 document.addEventListener('DOMContentLoaded', function(){
   const detailModal = new bootstrap.Modal(document.getElementById('detailModal'));
   const editModal   = new bootstrap.Modal(document.getElementById('editModal'));

   document.querySelectorAll('.view-detail').forEach(function(el){
      el.addEventListener('click', function(e){
         e.preventDefault();
         const id = this.dataset.id;
         fetch('manage_dosen.php?action=detail&id='+id)
           .then(r=>r.json())
           .then(function(d){
              document.getElementById('detailName').textContent    = d.name;
              document.getElementById('detailEmail').textContent   = d.email;
              document.getElementById('detailCreated').textContent = d.created_at;
              const list = document.getElementById('detailClasses');
              list.innerHTML = '';
              if (d.classes && d.classes.length){
                 d.classes.forEach(function(c){
                    const li = document.createElement('li');
                    li.className = 'list-group-item';
                    li.textContent = c.class_name;
                    list.appendChild(li);
                 });
              } else {
                 list.innerHTML = '<li class="list-group-item">Belum ada kelas</li>';
              }
              detailModal.show();
           });
      });
   });

   document.querySelectorAll('.edit-dosen').forEach(function(el){
      el.addEventListener('click', function(e){
         e.preventDefault();
         const id = this.dataset.id;
         fetch('manage_dosen.php?action=detail&id='+id)
           .then(r=>r.json())
           .then(function(d){
              document.getElementById('editId').value    = d.id;
              document.getElementById('editName').value  = d.name;
              document.getElementById('editEmail').value = d.email;
              document.getElementById('editPassword').value = '';
              editModal.show();
           });
      });
   });
 });
</script>

</body>
</html>
