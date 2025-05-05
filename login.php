<?php
require_once 'koneksi.php';
require_once 'fungsi_helper.php';

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = sanitizeInput($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['name'] = $user['name'];
        $_SESSION['role'] = $user['role'];
        
        logAction($conn, $user['id'], 'Login successful');
        
        // Redirect based on role
        switch ($user['role']) {
            case 'SuperAdmin':
                header('Location: SuperAdmin/dashboard_admin.php');
                break;
            case 'Mahasiswa':
                header('Location: mahasiswa/dashboard_mahasiswa.php');
                break;
            case 'Dosen':
                header('Location: dosen/dashboard_dosen.php');
                break;
        }
        exit();
    } else {
        $error = 'Email atau password salah';
        logAction($conn, 0, 'Failed login attempt: ' . $email);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - SentiSyncEd</title>
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

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            padding: 2.5rem;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            animation: slideUp 0.5s ease;
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header img {
            width: 80px;
            height: 80px;
            margin-bottom: 1rem;
        }

        .login-header h2 {
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
            top: 50%;
            transform: translateY(-50%);
            color: #4A90E2;
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

        .btn-login {
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
        }

        .btn-login:hover {
            background: #357ABD;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(53, 122, 189, 0.3);
        }

        .login-footer {
            text-align: center;
            margin-top: 2rem;
        }

        .login-footer a {
            color: #4A90E2;
            text-decoration: none;
            font-weight: 600;
            transition: color 0.3s ease;
        }

        .login-footer a:hover {
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

        .alert-error {
            background-color: #ffe6e6;
            border: 1px solid #ff9999;
            color: #cc0000;
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
            .login-container {
                padding: 2rem;
            }

            .login-header h2 {
                font-size: 1.75rem;
            }
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-header">
            <i class="fas fa-graduation-cap fa-3x" style="color: #4A90E2;"></i>
            <h2>Login SentiSyncEd</h2>
            <p style="color: #666;">Masuk ke akun Anda</p>
        </div>
        
        <?php if ($error): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <form method="POST" action="login.php">
            <div class="form-group">
                <i class="fas fa-envelope"></i>
                <input type="email" id="email" name="email" placeholder="Email" required>
            </div>
            
            <div class="form-group">
                <i class="fas fa-lock"></i>
                <input type="password" id="password" name="password" placeholder="Password" required>
            </div>
            
            <button type="submit" class="btn-login">
                <i class="fas fa-sign-in-alt"></i> Login
            </button>
        </form>
        
        <div class="login-footer">
            <p>
                <a href="index.php"><i class="fas fa-home"></i> Kembali ke Halaman Utama</a>
            </p>
            <p style="margin-top: 1rem;">
                Belum punya akun? <a href="register.php">Daftar sebagai Mahasiswa</a>
            </p>
        </div>
    </div>
    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>
</body>
</html>
