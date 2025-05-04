<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Mahasiswa - SentiSyncEd</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/footer.css">
    <style>
        .dashboard {
            display: flex;
            min-height: 100vh;
        }
        .sidebar {
            width: 250px;
            background: white;
            padding: 2rem;
            box-shadow: 2px 0 5px rgba(0, 0, 0, 0.1);
        }
        .sidebar h3 {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .sidebar-menu {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .sidebar-menu li {
            margin-bottom: 0.5rem;
        }
        .sidebar-menu a {
            display: flex;
            align-items: center;
            gap: 0.8rem;
            padding: 0.8rem 1rem;
            color: #666;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .sidebar-menu a:hover {
            background: #f0f7ff;
            color: #4A90E2;
        }
        .sidebar-menu a.active {
            background: #4A90E2;
            color: white;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>
                <i class="fas fa-graduation-cap"></i>
                Dashboard Mahasiswa
            </h3>
            <nav>
                <ul class="sidebar-menu">
                    <li>
                        <a href="dashboard_mahasiswa.php" class="active">
                            <i class="fas fa-home"></i>
                            <span>Dashboard</span>
                        </a>
                    </li>
                    <li>
                        <a href="input_emosi.php">
                            <i class="fas fa-smile"></i>
                            <span>Input Emosi</span>
                        </a>
                    </li>
                    <li>
                        <a href="tulis_curhat.php">
                            <i class="fas fa-comment-dots"></i>
                            <span>Tulis Curhat</span>
                        </a>
                    </li>
                    <li>
                        <a href="grafik_emosi.php">
                            <i class="fas fa-chart-line"></i>
                            <span>Grafik Emosi</span>
                        </a>
                    </li>
                    <li>
                        <a href="pilih_kelas.php">
                            <i class="fas fa-chalkboard"></i>
                            <span>Pilih Kelas</span>
                        </a>
                    </li>
                    <li>
                        <a href="kelas_saya.php">
                            <i class="fas fa-book"></i>
                            <span>Kelas Saya</span>
                        </a>
                    </li>
                    <li>
                        <a href="../login.php?logout=1">
                            <i class="fas fa-sign-out-alt"></i>
                            <span>Logout</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
    </div>
</body>
</html>
