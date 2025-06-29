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
    <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('grafik_emosi.php'); ?>" style="font-size: 1.1rem;">
        <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
    </a>
    <a href="daftar_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('daftar_curhat.php'); ?>" style="font-size: 1.1rem;">
        <i class="bi bi-chat-dots me-2"></i> Daftar Curhat
    </a>
    <a href="laporan.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('laporan.php'); ?>" style="font-size: 1.1rem;">
        <i class="bi bi-file-earmark-text me-2"></i> Laporan
    </a>
    <a href="panduan.php" class="nav-link d-flex align-items-center px-4 py-2 text-white <?php echo is_active('panduan.php'); ?>" style="font-size: 1.1rem;">
        <i class="bi bi-question-circle me-2"></i> Panduan Penggunaan
    </a>
</nav>