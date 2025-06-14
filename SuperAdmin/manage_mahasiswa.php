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

// Handle form submission for adding new mahasiswa
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];
    
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
                // Insert new mahasiswa
                $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Mahasiswa')");
                $stmt->execute([$name, $email, $hashedPassword]);
                
                $message = "Mahasiswa berhasil didaftarkan!";
                logAction($conn, $_SESSION['user_id'], "Registered new mahasiswa: $email");
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Handle mahasiswa deletion
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $mahasiswaId = (int)$_GET['id'];
    
    try {
        // Get mahasiswa info for logging
        $stmt = $conn->prepare("SELECT name, email FROM users WHERE id = ? AND role = 'Mahasiswa'");
        $stmt->execute([$mahasiswaId]);
        $mahasiswaInfo = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if ($mahasiswaInfo) {
            // Begin transaction
            $conn->beginTransaction();
            
            // Delete from class_members
            $stmt = $conn->prepare("DELETE FROM class_members WHERE user_id = ?");
            $stmt->execute([$mahasiswaId]);
            
            // Delete from emotions
            $stmt = $conn->prepare("DELETE FROM emotions WHERE user_id = ?");
            $stmt->execute([$mahasiswaId]);
            
            // Delete from support_notes
            $stmt = $conn->prepare("DELETE FROM support_notes WHERE user_id = ?");
            $stmt->execute([$mahasiswaId]);
            
            // Delete the user
            $stmt = $conn->prepare("DELETE FROM users WHERE id = ? AND role = 'Mahasiswa'");
            $stmt->execute([$mahasiswaId]);
            
            // Commit transaction
            $conn->commit();
            
            $message = "Mahasiswa {$mahasiswaInfo['name']} berhasil dihapus.";
            logAction($conn, $_SESSION['user_id'], "Deleted mahasiswa: {$mahasiswaInfo['email']}");
        } else {
            $error = "Mahasiswa tidak ditemukan.";
        }
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($conn->inTransaction()) {
            $conn->rollBack();
        }
        $error = "Error: " . $e->getMessage();
    }
}

// Handle mahasiswa update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update') {
    $mahasiswaId = (int)$_POST['mahasiswa_id'];
    $name = filter_input(INPUT_POST, 'edit_name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'edit_email', FILTER_SANITIZE_EMAIL);
    $password = $_POST['edit_password'];
    
    // Validate input
    if (empty($name) || empty($email)) {
        $error = 'Nama dan email wajib diisi';
    } else {
        try {
            // Check if email already exists for other users
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $mahasiswaId]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan oleh pengguna lain';
            } else {
                // Update mahasiswa
                if (!empty($password)) {
                    // Update with new password
                    $hashedPassword = password_hash($password, PASSWORD_DEFAULT);
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ? AND role = 'Mahasiswa'");
                    $stmt->execute([$name, $email, $hashedPassword, $mahasiswaId]);
                } else {
                    // Update without changing password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ? AND role = 'Mahasiswa'");
                    $stmt->execute([$name, $email, $mahasiswaId]);
                }
                
                $message = "Data mahasiswa berhasil diperbarui!";
                logAction($conn, $_SESSION['user_id'], "Updated mahasiswa: $email");
            }
        } catch (PDOException $e) {
            $error = "Error: " . $e->getMessage();
        }
    }
}

// Get list of all mahasiswa
$stmt = $conn->query("SELECT u.id, u.name, u.email, u.created_at, COUNT(cm.id) as class_count 
                      FROM users u 
                      LEFT JOIN class_members cm ON u.id = cm.user_id 
                      WHERE u.role = 'Mahasiswa' 
                      GROUP BY u.id 
                      ORDER BY u.name");
$mahasiswaList = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get mahasiswa details for editing if requested
$editMahasiswa = null;
if (isset($_GET['action']) && $_GET['action'] === 'edit' && isset($_GET['id'])) {
    $mahasiswaId = (int)$_GET['id'];
    $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ? AND role = 'Mahasiswa'");
    $stmt->execute([$mahasiswaId]);
    $editMahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
}

// Get mahasiswa details and class enrollment for viewing if requested
$viewMahasiswa = null;
$mahasiswaClasses = [];
if (isset($_GET['action']) && $_GET['action'] === 'view' && isset($_GET['id'])) {
    $mahasiswaId = (int)$_GET['id'];
    
    // Get mahasiswa details
    $stmt = $conn->prepare("SELECT id, name, email, created_at FROM users WHERE id = ? AND role = 'Mahasiswa'");
    $stmt->execute([$mahasiswaId]);
    $viewMahasiswa = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if ($viewMahasiswa) {
        // Get classes the mahasiswa is enrolled in
        $stmt = $conn->prepare("
            SELECT c.id, c.class_name, c.description, u.name as dosen_name, cm.joined_at
            FROM class_members cm
            JOIN classes c ON cm.class_id = c.id
            JOIN users u ON c.dosen_id = u.id
            WHERE cm.user_id = ?
            ORDER BY cm.joined_at DESC
        ");
        $stmt->execute([$mahasiswaId]);
        $mahasiswaClasses = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Get emotion statistics
        $stmt = $conn->prepare("
            SELECT emotion, COUNT(*) as count
            FROM emotions
            WHERE user_id = ?
            GROUP BY emotion
        ");
        $stmt->execute([$mahasiswaId]);
        $emotionStats = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Add emotion stats to viewMahasiswa
        $viewMahasiswa['emotions'] = $emotionStats;
        
        // Get total emotions count
        $stmt = $conn->prepare("SELECT COUNT(*) as total FROM emotions WHERE user_id = ?");
        $stmt->execute([$mahasiswaId]);
        $viewMahasiswa['total_emotions'] = $stmt->fetch(PDO::FETCH_ASSOC)['total'];
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Kelola Mahasiswa - SentiSyncEd</title>
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
        
        .badge-class {
            background-color: #e0f7fa;
            color: #0288d1;
            font-weight: 500;
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
                <a href="manage_dosen.php" class="nav-link text-white">
                    <i class="bi bi-person-badge"></i>
                    Kelola Dosen
                </a>
            </li>
            <li>
                <a href="manage_mahasiswa.php" class="nav-link active">
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
                <li><a class="dropdown-item" href="#">Profil</a></li>
                <li><a class="dropdown-item" href="#">Pengaturan</a></li>
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
            <h4 class="mb-0">Kelola Mahasiswa</h4>
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
                            <h5 class="m-0 font-weight-bold">Tambah Mahasiswa Baru</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($message): ?>
                                <div class="alert alert-success">
                                    <?= htmlspecialchars($message) ?>
                                </div>
                            <?php endif; ?>
                            
                            <?php if ($error): ?>
                                <div class="alert alert-danger">
                                    <?= htmlspecialchars($error) ?>
                                </div>
                            <?php endif; ?>

                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="add">
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="name" class="form-label">Nama Mahasiswa</label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                        <div class="invalid-feedback">
                                            Nama mahasiswa wajib diisi
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
                                        <i class="bi bi-person-plus-fill me-1"></i> Daftarkan Mahasiswa
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Daftar Mahasiswa -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header d-flex justify-content-between align-items-center">
                            <h5 class="m-0 font-weight-bold">Daftar Mahasiswa</h5>
                            <div class="d-flex">
                                <input type="text" id="searchMahasiswa" class="form-control form-control-sm me-2" placeholder="Cari mahasiswa...">
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover align-middle">
                                    <thead>
                                        <tr>
                                            <th>Nama</th>
                                            <th>Email</th>
                                            <th>Tanggal Daftar</th>
                                            <th>Kelas</th>
                                            <th>Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($mahasiswaList as $mahasiswa): ?>
                                            <tr>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <div class="avatar me-2 bg-success text-white">
                                                            <?= substr($mahasiswa['name'], 0, 1) ?>
                                                        </div>
                                                        <?= htmlspecialchars($mahasiswa['name']) ?>
                                                    </div>
                                                </td>
                                                <td><?= htmlspecialchars($mahasiswa['email']) ?></td>
                                                <td><?= date('d M Y', strtotime($mahasiswa['created_at'])) ?></td>
                                                <td>
                                                    <span class="badge badge-class"><?= $mahasiswa['class_count'] ?> kelas</span>
                                                </td>
                                                <td>
                                                    <div class="dropdown">
                                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                                                            <i class="bi bi-three-dots-vertical"></i>
                                                        </button>
                                                        <ul class="dropdown-menu">
                                                            <li><a class="dropdown-item" href="?action=view&id=<?= $mahasiswa['id'] ?>"><i class="bi bi-eye me-1"></i> Lihat Detail</a></li>
                                                            <li><a class="dropdown-item" href="?action=edit&id=<?= $mahasiswa['id'] ?>"><i class="bi bi-pencil me-1"></i> Edit</a></li>
                                                            <li><a class="dropdown-item" href="manage_student_classes.php?id=<?= $mahasiswa['id'] ?>"><i class="bi bi-journal-plus me-1"></i> Kelola Kelas</a></li>
                                                            <li><a class="dropdown-item text-danger" href="#" data-bs-toggle="modal" data-bs-target="#deleteModal<?= $mahasiswa['id'] ?>"><i class="bi bi-trash me-1"></i> Hapus</a></li>
                                                        </ul>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                        <?php if (empty($mahasiswaList)): ?>
                                            <tr>
                                                <td colspan="5" class="text-center py-3">Belum ada mahasiswa yang terdaftar</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($editMahasiswa): ?>
            <!-- Edit Mahasiswa Modal -->
            <div class="modal fade show" id="editMahasiswaModal" tabindex="-1" aria-labelledby="editMahasiswaModalLabel" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="editMahasiswaModalLabel">Edit Mahasiswa</h5>
                            <a href="manage_mahasiswa.php" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <form method="POST" class="needs-validation" novalidate>
                                <input type="hidden" name="action" value="update">
                                <input type="hidden" name="mahasiswa_id" value="<?= $editMahasiswa['id'] ?>">
                                
                                <div class="mb-3">
                                    <label for="edit_name" class="form-label">Nama Mahasiswa</label>
                                    <input type="text" class="form-control" id="edit_name" name="edit_name" value="<?= htmlspecialchars($editMahasiswa['name']) ?>" required>
                                    <div class="invalid-feedback">
                                        Nama mahasiswa wajib diisi
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="edit_email" name="edit_email" value="<?= htmlspecialchars($editMahasiswa['email']) ?>" required>
                                    <div class="invalid-feedback">
                                        Email wajib diisi dengan format yang benar
                                    </div>
                                </div>
                                
                                <div class="mb-3">
                                    <label for="edit_password" class="form-label">Password Baru (kosongkan jika tidak ingin mengubah)</label>
                                    <input type="password" class="form-control" id="edit_password" name="edit_password">
                                </div>
                                
                                <div class="d-flex justify-content-end">
                                    <a href="manage_mahasiswa.php" class="btn btn-secondary me-2">Batal</a>
                                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($viewMahasiswa): ?>
            <!-- View Mahasiswa Details Modal -->
            <div class="modal fade show" id="viewMahasiswaModal" tabindex="-1" aria-labelledby="viewMahasiswaModalLabel" style="display: block; background: rgba(0,0,0,0.5);" aria-modal="true">
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="viewMahasiswaModalLabel">Detail Mahasiswa</h5>
                            <a href="manage_mahasiswa.php" class="btn-close" aria-label="Close"></a>
                        </div>
                        <div class="modal-body">
                            <div class="row mb-4">
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Informasi Mahasiswa</h6>
                                    <table class="table table-borderless">
                                        <tr>
                                            <td width="40%">Nama</td>
                                            <td><strong><?= htmlspecialchars($viewMahasiswa['name']) ?></strong></td>
                                        </tr>
                                        <tr>
                                            <td>Email</td>
                                            <td><?= htmlspecialchars($viewMahasiswa['email']) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Tanggal Daftar</td>
                                            <td><?= date('d M Y H:i', strtotime($viewMahasiswa['created_at'])) ?></td>
                                        </tr>
                                        <tr>
                                            <td>Jumlah Kelas</td>
                                            <td><span class="badge bg-primary"><?= count($mahasiswaClasses) ?> kelas</span></td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="fw-bold">Statistik Emosi</h6>
                                    <?php if (!empty($viewMahasiswa['emotions'])): ?>
                                        <div class="card border-0 bg-light">
                                            <div class="card-body p-3">
                                                <?php 
                                                $emotionLabels = [];
                                                $emotionData = [];
                                                $emotionColors = [
                                                    'Senang' => '#36b9cc',
                                                    'Stres' => '#e74a3b',
                                                    'Lelah' => '#f6c23e',
                                                    'Netral' => '#858796'
                                                ];
                                                
                                                foreach ($viewMahasiswa['emotions'] as $emotion) {
                                                    $emotionLabels[] = $emotion['emotion'];
                                                    $emotionData[] = $emotion['count'];
                                                }
                                                ?>
                                                <div class="d-flex justify-content-between mb-2">
                                                    <div>Total Emosi Tercatat:</div>
                                                    <div><strong><?= $viewMahasiswa['total_emotions'] ?></strong></div>
                                                </div>
                                                <?php foreach ($viewMahasiswa['emotions'] as $emotion): ?>
                                                    <?php 
                                                    $percentage = ($viewMahasiswa['total_emotions'] > 0) 
                                                        ? round(($emotion['count'] / $viewMahasiswa['total_emotions']) * 100) 
                                                        : 0;
                                                    $color = isset($emotionColors[$emotion['emotion']]) ? $emotionColors[$emotion['emotion']] : '#858796';
                                                    ?>
                                                    <div class="mb-1"><?= $emotion['emotion'] ?></div>
                                                    <div class="progress mb-2" style="height: 10px;">
                                                        <div class="progress-bar" role="progressbar" style="width: <?= $percentage ?>%; background-color: <?= $color ?>" 
                                                            aria-valuenow="<?= $percentage ?>" aria-valuemin="0" aria-valuemax="100">
                                                        </div>
                                                    </div>
                                                    <div class="small text-end mb-2"><?= $emotion['count'] ?> (<?= $percentage ?>%)</div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php else: ?>
                                        <div class="alert alert-info">Belum ada data emosi tercatat</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <h6 class="fw-bold mb-3">Kelas yang Diikuti</h6>
                            <?php if (!empty($mahasiswaClasses)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Nama Kelas</th>
                                                <th>Dosen</th>
                                                <th>Tanggal Bergabung</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($mahasiswaClasses as $class): ?>
                                                <tr>
                                                    <td>
                                                        <div class="fw-bold"><?= htmlspecialchars($class['class_name']) ?></div>
                                                        <div class="small text-muted"><?= htmlspecialchars($class['description']) ?></div>
                                                    </td>
                                                    <td><?= htmlspecialchars($class['dosen_name']) ?></td>
                                                    <td><?= date('d M Y', strtotime($class['joined_at'])) ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">Mahasiswa belum terdaftar di kelas manapun</div>
                            <?php endif; ?>
                            
                            <div class="d-flex justify-content-end mt-3">
                                <a href="manage_mahasiswa.php" class="btn btn-secondary">Tutup</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <!-- Delete Confirmation Modals -->
            <?php foreach ($mahasiswaList as $mahasiswa): ?>
            <div class="modal fade" id="deleteModal<?= $mahasiswa['id'] ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?= $mahasiswa['id'] ?>" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title" id="deleteModalLabel<?= $mahasiswa['id'] ?>">Konfirmasi Hapus</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body">
                            <p>Anda yakin ingin menghapus mahasiswa <strong><?= htmlspecialchars($mahasiswa['name']) ?></strong>?</p>
                            <p class="text-danger"><small>Tindakan ini akan menghapus semua data terkait mahasiswa ini termasuk keanggotaan kelas, data emosi, dan catatan dukungan.</small></p>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                            <a href="?action=delete&id=<?= $mahasiswa['id'] ?>" class="btn btn-danger">Hapus</a>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Mobile sidebar toggle
            const sidebarToggleBtn = document.querySelector('.btn-link');
            const sidebar = document.querySelector('.sidebar');
            const closeSidebarBtn = document.querySelector('.btn-close-sidebar');
            
            if (sidebarToggleBtn) {
                sidebarToggleBtn.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                });
            }
            
            if (closeSidebarBtn) {
                closeSidebarBtn.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                });
            }
            
            // Form validation
            const forms = document.querySelectorAll('.needs-validation');
            Array.from(forms).forEach(form => {
                form.addEventListener('submit', event => {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    form.classList.add('was-validated');
                }, false);
            });
            
            // Search functionality
            const searchInput = document.getElementById('searchMahasiswa');
            if (searchInput) {
                searchInput.addEventListener('keyup', function() {
                    const searchTerm = this.value.toLowerCase();
                    const tableRows = document.querySelectorAll('tbody tr');
                    
                    tableRows.forEach(row => {
                        const text = row.textContent.toLowerCase();
                        if (text.includes(searchTerm)) {
                            row.style.display = '';
                        } else {
                            row.style.display = 'none';
                        }
                    });
                });
            }
        });
    </script>
</body>
</html>
