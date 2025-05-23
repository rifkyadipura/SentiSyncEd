<?php
$current_file = basename($_SERVER['PHP_SELF']);
function is_active($files) {
    global $current_file;
    if (is_array($files)) {
        return in_array($current_file, $files) ? 'active' : '';
    } else {
        return $current_file === $files ? 'active' : '';
    }
}
?>
<aside class="sidebar" style="background: #4A90E2; color: #fff; height: 100vh; width: 250px; position: fixed; top: 0; left: 0; z-index: 100;">
    <div class="sidebar-header text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.15) !important;">
        <h2 class="mb-0" style="color:#fff; font-weight:700; font-size:24px;">SentiSyncEd</h2>
    </div>
    <nav class="nav flex-column py-3">
        <a href="dashboard_dosen.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('dashboard_dosen.php'); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-house me-2"></i> Dashboard
        </a>
        <a href="kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active(['kelas.php','create_class.php','view_class.php']); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-mortarboard me-2"></i> Kelas
        </a>
        <a href="laporan.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('laporan.php'); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-file-earmark-text me-2"></i> Laporan
        </a>
        <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('grafik_emosi.php'); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
        </a>
        <a href="daftar_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('daftar_curhat.php'); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-chat-dots me-2"></i> Daftar Curhat
        </a>
        <a href="../login.php?logout=1" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('logout.php'); ?>" style="font-size: 1.1rem;">
            <i class="bi bi-box-arrow-right me-2"></i> Logout
        </a>
    </nav>
    <style>
        .sidebar .nav-link {
            color: #e3eaf3;
            border-radius: 8px;
            margin-bottom: 4px;
            transition: background 0.2s, color 0.2s;
        }
        .sidebar .nav-link.active, .sidebar .nav-link:hover {
            background: rgba(255,255,255,0.15) !important;
            color: #fff !important;
        }
        .sidebar .nav-link i {
            font-size: 1.2rem;
        }
    </style>
</aside>