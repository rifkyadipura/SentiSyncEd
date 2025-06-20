<?php
require_once '../koneksi.php';

// Pengecekan login
if (!isLoggedIn()) {
    header('Location: ../login.php');
    exit();
}

// Pastikan hanya dosen yang bisa mengakses
if ($_SESSION['role'] !== 'Dosen') {
    header('Location: ../index.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error = '';
$success = '';

// Ambil data user saat ini
$stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$user) {
    header('Location: dashboard_dosen.php');
    exit();
}

// Proses form jika ada data yang dikirim
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validasi input
    if (empty($name) || empty($email)) {
        $error = 'Nama dan email tidak boleh kosong';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid';
    } else {
        try {
            // Cek apakah email sudah digunakan oleh user lain
            $stmt = $conn->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $user_id]);
            if ($stmt->fetch()) {
                $error = 'Email sudah digunakan oleh akun lain';
            } else {
                // Jika ada perubahan password
                if (!empty($current_password)) {
                    // Verifikasi password saat ini
                    $stmt = $conn->prepare("SELECT password FROM users WHERE id = ?");
                    $stmt->execute([$user_id]);
                    $current_user = $stmt->fetch();
                    
                    if (!password_verify($current_password, $current_user['password'])) {
                        $error = 'Password saat ini salah';
                    } elseif ($new_password !== $confirm_password) {
                        $error = 'Konfirmasi password baru tidak cocok';
                    } elseif (strlen($new_password) < 8) {
                        $error = 'Password baru minimal 8 karakter';
                    } else {
                        // Update data dengan password baru
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, password = ? WHERE id = ?");
                        $stmt->execute([$name, $email, $hashed_password, $user_id]);
                        $success = 'Profil berhasil diperbarui';
                        
                        // Update session
                        $_SESSION['name'] = $name;
                        $_SESSION['email'] = $email;
                    }
                } else {
                    // Update data tanpa mengubah password
                    $stmt = $conn->prepare("UPDATE users SET name = ?, email = ? WHERE id = ?");
                    $stmt->execute([$name, $email, $user_id]);
                    $success = 'Profil berhasil diperbarui';
                    
                    // Update session
                    $_SESSION['name'] = $name;
                    $_SESSION['email'] = $email;
                }
                
                // Refresh data user
                $stmt = $conn->prepare("SELECT id, name, email FROM users WHERE id = ?");
                $stmt->execute([$user_id]);
                $user = $stmt->fetch(PDO::FETCH_ASSOC);
            }
        } catch (PDOException $e) {
            $error = 'Terjadi kesalahan. Silakan coba lagi.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Profil Dosen - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background-color: #f8f9fa;
            min-height: 100vh;
            display: flex;
            align-items: center;
            padding: 1rem;
        }
        .profile-container {
            width: 100%;
            max-width: 700px;
            margin: 2rem auto;
            padding: 2rem;
            background: #fff;
            border-radius: 15px;
            box-shadow: 0 0 20px rgba(0, 0, 0, 0.1);
        }
        .profile-header {
            text-align: center;
            margin-bottom: 2rem;
            padding-bottom: 1.5rem;
            border-bottom: 1px solid #eee;
        }
        .profile-avatar {
            width: 100px;
            height: 100px;
            border-radius: 50%;
            object-fit: cover;
            margin-bottom: 1rem;
            border: 5px solid #f1f8ff;
            background-color: #e9f7fe;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0d6efd;
            font-size: 2.5rem;
        }
        .form-label {
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #495057;
        }
        .form-control:focus {
            border-color: #86b7fe;
            box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.1);
        }
        .btn-action {
            min-width: 200px;
            padding: 10px 20px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        .btn-action i {
            margin-right: 8px;
        }
        .btn-action:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
        }
        .btn-update {
            background-color: #4e73df;
            border: none;
        }
        .btn-update:hover {
            background-color: #2e59d9;
        }
        .password-section {
            background-color: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin: 2rem 0;
            border: 1px solid #e9ecef;
        }
        .section-title {
            color: #0d6efd;
            font-weight: 600;
            margin-bottom: 1.2rem;
            font-size: 1.1rem;
        }
        @media (max-width: 768px) {
            .profile-container {
                padding: 1.5rem;
                margin: 1rem auto;
            }
            .profile-avatar {
                width: 80px;
                height: 80px;
                font-size: 2rem;
            }
            h2 {
                font-size: 1.5rem;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="profile-container">
            <div class="profile-header">
                <div class="profile-avatar mx-auto">
                    <i class="bi bi-person-fill"></i>
                </div>
                <h2 class="h4 mb-2">Edit Profil Dosen</h2>
                <p class="text-muted mb-0">Kelola informasi akun Anda</p>
            </div>

            <?php if ($error): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= htmlspecialchars($error) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i><?= htmlspecialchars($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <form method="POST" action="" class="needs-validation" novalidate>
                <div class="mb-4">
                    <label for="name" class="form-label">Nama Lengkap</label>
                    <input type="text" class="form-control form-control-lg" id="name" name="name" 
                           value="<?= htmlspecialchars($user['name']) ?>" required>
                    <div class="invalid-feedback">
                        Harap isi nama lengkap
                    </div>
                </div>
                
                <div class="mb-4">
                    <label for="email" class="form-label">Alamat Email</label>
                    <input type="email" class="form-control form-control-lg" id="email" name="email" 
                           value="<?= htmlspecialchars($user['email']) ?>" required>
                    <div class="invalid-feedback">
                        Harap isi email yang valid
                    </div>
                </div>

                <div class="password-section">
                    <div class="section-title">
                        <i class="bi bi-key-fill me-2"></i>Ganti Password
                    </div>
                    <p class="text-muted small mb-3">Kosongkan jika tidak ingin mengubah password</p>
                    
                    <div class="mb-3">
                        <label for="current_password" class="form-label">Password Saat Ini</label>
                        <input type="password" class="form-control form-control-lg" id="current_password" name="current_password">
                    </div>
                    
                    <div class="mb-3">
                        <label for="new_password" class="form-label">Password Baru</label>
                        <input type="password" class="form-control form-control-lg" id="new_password" name="new_password">
                        <div class="form-text">Minimal 8 karakter</div>
                    </div>
                    
                    <div class="mb-0">
                        <label for="confirm_password" class="form-label">Konfirmasi Password Baru</label>
                        <input type="password" class="form-control form-control-lg" id="confirm_password" name="confirm_password">
                    </div>
                </div>

                <div class="row justify-content-center mt-4 g-3">
                    <div class="col-12 col-sm-auto text-center">
                        <button type="submit" class="btn btn-primary btn-action btn-update">
                            <i class="bi bi-save"></i>Simpan Perubahan
                        </button>
                    </div>
                    <div class="col-12 col-sm-auto text-center">
                        <a href="dashboard_dosen.php" class="btn btn-outline-secondary btn-action">
                            <i class="bi bi-arrow-left"></i>Kembali
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Validasi form client-side
        (function () {
            'use strict'
            
            // Fetch all the forms we want to apply custom Bootstrap validation styles to
            var forms = document.querySelectorAll('.needs-validation')
            
            // Loop over them and prevent submission
            Array.prototype.slice.call(forms)
                .forEach(function (form) {
                    form.addEventListener('submit', function (event) {
                        if (!form.checkValidity()) {
                            event.preventDefault()
                            event.stopPropagation()
                        }
                        
                        // Validasi password
                        const currentPassword = document.getElementById('current_password').value;
                        const newPassword = document.getElementById('new_password').value;
                        const confirmPassword = document.getElementById('confirm_password').value;
                        
                        // Jika salah satu field password diisi, semua harus diisi
                        if (currentPassword || newPassword || confirmPassword) {
                            if (!currentPassword || !newPassword || !confirmPassword) {
                                event.preventDefault();
                                alert('Harap isi semua field password untuk mengubah password');
                                return false;
                            }
                            
                            if (newPassword.length < 8) {
                                event.preventDefault();
                                alert('Password baru minimal 8 karakter');
                                return false;
                            }
                            
                            if (newPassword !== confirmPassword) {
                                event.preventDefault();
                                alert('Konfirmasi password tidak cocok');
                                return false;
                            }
                        }
                        
                        form.classList.add('was-validated')
                    }, false)
                })
        })()
    </script>
</body>
</html>
