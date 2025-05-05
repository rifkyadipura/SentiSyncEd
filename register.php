<?php
require_once 'koneksi.php';
require_once 'fungsi_helper.php';

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitizeInput($_POST['name']);
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($name) || empty($email) || empty($password) || empty($confirm_password)) {
        $error = 'Semua field harus diisi';
    } elseif ($password !== $confirm_password) {
        $error = 'Password tidak cocok';
    } elseif (strlen($password) < 6) {
        $error = 'Password minimal 6 karakter';
    } else {
        // Check if email already exists
        $stmt = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $error = 'Email sudah terdaftar';
        } else {
            // Insert new user
            try {
                $stmt = $conn->prepare("INSERT INTO users (name, email, password, role) VALUES (?, ?, ?, 'Mahasiswa')");
                // Use password_hash for secure storage
                $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                $stmt->execute([$name, $email, $hashed_password]);
                
                $userId = $conn->lastInsertId();
                logAction($conn, $userId, 'User registered');
                
                $success = 'Registrasi berhasil! Silakan login.';
                
                // Redirect to login page after 2 seconds
                header("refresh:2;url=login.php");
            } catch (PDOException $e) {
                $error = 'Terjadi kesalahan saat registrasi';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Registrasi Mahasiswa - SentiSyncEd</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .register-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 500px;
            animation: slideUp 0.5s ease;
        }

        .register-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .register-header h2 {
            color: #4A90E2;
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group i {
            position: absolute;
            left: 15px;
            top: 15px;
            color: #4A90E2;
        }

        .form-group .password-requirements i {
            position: static;
            margin-right: 5px;
        }

        .form-group input {
            width: 100%;
            padding: 12px 20px 12px 45px;
            border: 2px solid #e1e1e1;
            border-radius: 50px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box; /* Ensures padding doesn't add to width */
        }

        .form-group input:focus {
            border-color: #4A90E2;
            box-shadow: 0 0 0 3px rgba(74, 144, 226, 0.1);
            outline: none;
        }

        .password-requirements {
            font-size: 0.85rem;
            color: #666;
            margin-top: 0.5rem;
            padding-left: 45px;
        }

        .btn-register {
            width: 100%;
            padding: 12px;
            background: #4A90E2;
            color: white;
            border: none;
            border-radius: 50px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-register:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(53, 122, 189, 0.3);
        }

        .register-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .register-footer a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .register-footer a:hover {
            color: #357ABD;
        }

        .alert {
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .alert i {
            font-size: 1.2rem;
        }

        .alert-error {
            background-color: #ffe6e6;
            border: 1px solid #ff9999;
            color: #cc0000;
        }

        .alert-success {
            background-color: #e6ffe6;
            border: 1px solid #99ff99;
            color: #006600;
        }

        @keyframes slideUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 480px) {
            .register-container {
                padding: 2rem;
            }

            .register-header h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="register-container">
        <div class="register-header">
            <i class="fas fa-user-plus fa-3x" style="color: #4A90E2; margin-bottom: 1rem;"></i>
            <h2>Registrasi Mahasiswa</h2>
            <p style="color: #666;">Buat akun baru untuk memulai</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <?php if ($success): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $success; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="register.php">
            <div class="form-group">
                <i class="fas fa-user"></i>
                <input type="text" id="name" name="name" placeholder="Nama Lengkap" required 
                       value="<?php echo isset($_POST['name']) ? htmlspecialchars($_POST['name']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Email" required 
                       value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required minlength="6">
                <div class="password-requirements">
                    <i class="fas fa-info-circle"></i> Password minimal 6 karakter
                </div>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="confirm_password" name="confirm_password" 
                       placeholder="Konfirmasi Password" required minlength="6">
            </div>
            
            <button type="submit" class="btn-register">
                <i class="fas fa-user-plus"></i> Daftar Sekarang
            </button>
        </form>
        
        <div class="register-footer">
            <p>
                <a href="login.php"><i class="fas fa-sign-in-alt"></i> Sudah punya akun? Login di sini</a>
            </p>
            <p style="margin-top: 1rem;">
                <a href="index.php"><i class="fas fa-home"></i> Kembali ke Halaman Utama</a>
            </p>
        </div>
    </div>
    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
