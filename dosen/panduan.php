<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <?php 
    $page_title = 'Panduan Penggunaan';
    include 'includes/head.php'; 
    ?>
    <style>
        /* Main Content */
        .main-content {
            margin-left: 14rem;
            padding: 2rem;
            min-height: 100vh;
            background-color: #f8f9fc;
            padding-top: 4rem;
            transition: all 0.3s;
        }
        
        @media (max-width: 991.98px) {
            .main-content {
                margin-left: 0;
                padding: 1rem;
                padding-top: 5rem;
            }
        }
        
        /* Guide Sections */
        .guide-section {
            background: #fff;
            border-radius: 0.5rem;
            box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
            padding: 2rem;
            margin-bottom: 2rem;
            border: 1px solid #e3e6f0;
            transition: transform 0.2s, box-shadow 0.2s;
        }
        
        .guide-section:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.1);
        }
        
        .guide-section h2 {
            color: #4e73df;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #4e73df;
            display: inline-block;
            font-size: 1.6rem;
        }
        
        .guide-step {
            display: flex;
            margin-bottom: 2rem;
            position: relative;
            padding-left: 3rem;
        }
        
        .step-number {
            position: absolute;
            left: 0;
            top: 0;
            width: 2.25rem;
            height: 2.25rem;
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 1.1rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            transition: all 0.2s;
        }
        
        .guide-step:hover .step-number {
            transform: scale(1.1);
            box-shadow: 0 4px 8px rgba(0,0,0,0.15);
        }
        
        .step-content {
            flex: 1;
            padding-left: 1rem;
        }
        
        .step-content h4 {
            color: #4e73df;
            margin-bottom: 1rem;
            font-weight: 600;
        }
        
        .step-content ol, 
        .step-content ul {
            padding-left: 1.5rem;
            margin-bottom: 1.25rem;
        }
        
        .step-content li {
            margin-bottom: 0.5rem;
            color: #5a5c69;
        }
        
        /* Image Styling */
        .guide-image {
            max-width: 100%;
            height: auto;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
            border: 1px solid #e3e6f0;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .guide-image:hover {
            transform: translateY(-2px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        
        .image-caption {
            text-align: center;
            font-style: italic;
            color: #6c757d;
            margin-top: -0.5rem;
            margin-bottom: 1.5rem;
            font-size: 0.9rem;
        }
        
        .image-container {
            background-color: #f8f9fc;
            padding: 1.5rem;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            text-align: center;
            border: 1px dashed #dee2e6;
        }
        
        .image-placeholder {
            width: 100%;
            max-width: 600px;
            height: 300px;
            background-color: #e9ecef;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #6c757d;
            border-radius: 0.25rem;
            font-style: italic;
        }
        
        .alert {
            border-left: 4px solid #4e73df;
            border-radius: 0.25rem;
        }
        
        .alert-warning {
            border-left-color: #f6c23e;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            transition: all 0.2s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
        }
        
        .page-title {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            font-weight: 600;
            font-family: 'Poppins', sans-serif;
        }
        
        @media (max-width: 767.98px) {
            .guide-section {
                padding: 1.25rem;
            }
            
            .guide-step {
                padding-left: 2.5rem;
            }
            
            .step-number {
                width: 2rem;
                height: 2rem;
                font-size: 1rem;
            }
        }
    </style>
</head>
<body>
    <!-- Mobile Navbar -->
    <div class="mobile-navbar d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center">
            <button class="btn btn-light me-2" id="sidebarToggle">
                <i class="bi bi-list"></i>
            </button>
            <h4 class="text-white mb-0">SentiSyncEd</h4>
        </div>
        
        <!-- Profile Dropdown for Mobile -->
        <div class="dropdown">
            <button class="btn btn-light dropdown-toggle d-flex align-items-center" type="button" id="mobileProfileDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-1"></i>
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($dosen['name'] ?? 'Dosen'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                <li><a class="dropdown-item" href="profile.php"><i class="bi bi-person me-2"></i>Profil Saya</a></li>
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <aside class="sidebar" id="sidebar">
        <?php include 'sidebar.php'; ?>
    </aside>

    <!-- User Dropdown in Content Area -->
    <div class="user-dropdown dropdown d-none d-lg-block">
        <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="bi bi-person-circle me-2"></i>
            <?php echo htmlspecialchars($dosen['name'] ?? 'Dosen'); ?>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
            <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
        </ul>
    </div>

    <div class="main-content">
        <!-- User Dropdown in Content Area -->
        <div class="user-dropdown dropdown d-none d-lg-block">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <?php echo htmlspecialchars($dosen['name'] ?? 'Dosen'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>

        <div class="container-fluid">
            <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                <h1 class="h2"><i class="bi bi-question-circle me-2"></i>Panduan Penggunaan SentiSyncEd</h1>
                <a href="generate_panduan_pdf.php" class="btn btn-primary d-flex align-items-center" id="downloadPdfTop">
                    <i class="bi bi-download me-2"></i> Unduh Panduan (PDF)
                </a>
            </div>
            <div class="row">
                <div class="col-12">
                    <div class="alert alert-info d-flex flex-column flex-md-row align-items-start align-items-md-center justify-content-between mb-4">
                <div class="d-flex align-items-center mb-2 mb-md-0">
                    <i class="bi bi-info-circle-fill me-2"></i>
                    <span class="fw-medium">Panduan ini akan membantu Anda menggunakan platform SentiSyncEd sebagai Dosen. Simak langkah-langkahnya dengan seksama.</span>
                </div>
            </div>

                    <!-- Login dan Dashboard -->
                    <div class="guide-section">
                        <h2><i class="bi bi-door-open me-2"></i>Login dan Dashboard</h2>
                        <p class="text-muted mb-4">Pelajari cara mengakses dan menavigasi dashboard SentiSyncEd dengan mudah.</p>
                        
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Login ke Akun Anda</h4>
                                <p>Masuk ke SentiSyncEd menggunakan kredensial yang telah diberikan:</p>
                                <ol>
                                    <li>Buka halaman login SentiSyncEd</li>
                                    <li>Pilih peran sebagai <strong>Dosen</strong></li>
                                    <li>Masukkan email dan password Anda</li>
                                    <li>Klik tombol <strong>Login</strong></li>
                                </ol>
                                
                                <!-- Login Image -->
                                <div class="image-container">
                                    <img src="../assets/images/guide/login.jpeg" alt="Tampilan Halaman Login SentiSyncEd" class="guide-image">
                                    <p class="image-caption">Gambar 1: Tampilan Halaman Login SentiSyncEd</p>
                                </div>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Navigasi Dashboard</h4>
                                <p>Setelah login, Anda akan diarahkan ke halaman dashboard yang menampilkan:</p>
                                <ul>
                                    <li>Ringkasan kelas yang Anda ajar</li>
                                    <li>Statistik emosi mahasiswa</li>
                                    <li>Notifikasi terbaru</li>
                                    <li>Akses cepat ke fitur utama</li>
                                </ul>
                                
                                <!-- Dashboard Image -->
                                <div class="image-container">
                                    <img src="../assets/images/guide/dashboard.jpeg" alt="Tampilan Dashboard Dosen" class="guide-image">
                                    <p class="image-caption">Gambar 2: Tampilan Dashboard Dosen</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Manajemen Kelas -->
                    <div class="guide-section">
                        <h2><i class="bi bi-journal-bookmark me-2"></i>Manajemen Kelas</h2>
                        <p class="text-muted mb-4">Kelola kelas dan sesi pembelajaran Anda dengan fitur manajemen kelas yang lengkap.</p>
                        
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Membuka Sesi Kelas</h4>
                                <p>Untuk memulai sesi kelas:</p>
                                <ol>
                                    <li>Pilih menu <strong>Kelas Saya</strong> di sidebar</li>
                                    <li>Pilih kelas yang ingin Anda buka sesinya</li>
                                    <li>Klik tombol <strong>Buka Sesi</strong></li>
                                    <li>Atur durasi sesi jika diperlukan</li>
                                    <li>Klik <strong>Mulai Sesi</strong></li>
                                </ol>
                                
                                <!-- Class Session Image -->
                                <div class="image-container">
                                    <img src="../assets/images/guide/class-session.jpeg" alt="Tampilan Pembukaan Sesi Kelas" class="guide-image">
                                    <p class="image-caption">Gambar 3: Tampilan Pembukaan Sesi Kelas</p>
                                </div>
                                <div class="alert alert-warning mt-3">
                                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                                    Pastikan koneksi internet stabil sebelum memulai sesi.
                                </div>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Mengelola Anggota Kelas</h4>
                                <p>Anda dapat mengelola anggota kelas dengan:</p>
                                <ul>
                                    <li>Menambahkan/menghapus mahasiswa</li>
                                    <li>Mengundang mahasiswa menggunakan kode kelas</li>
                                    <li>Melihat daftar kehadiran</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Monitor Emosi Real-time -->
                    <div class="guide-section">
                        <h2><i class="bi bi-graph-up me-2"></i>Monitor Emosi Real-time</h2>
                        <p class="text-muted mb-4">Pantau kondisi emosional mahasiswa secara real-time selama sesi pembelajaran berlangsung.</p>
                        
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Memantau Emosi Mahasiswa</h4>
                                <p>Selama sesi berlangsung:</p>
                                <ol>
                                    <li>Buka halaman <strong>Monitor Emosi</strong></li>
                                    <li>Pantau grafik emosi yang diperbarui secara real-time</li>
                                    <li>Identifikasi perubahan emosi mahasiswa</li>
                                    <li>Gunakan filter untuk melihat berdasarkan waktu atau mahasiswa tertentu</li>
                                </ol>
                                
                                <!-- Emotion Monitoring Image -->
                                <div class="image-container">
                                    <img src="../assets/images/guide/emotion-monitor.jpeg" alt="Tampilan Monitor Emosi Real-time" class="guide-image">
                                    <p class="image-caption">Gambar 4: Tampilan Monitor Emosi Real-time</p>
                                </div>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Menangani Emosi Negatif</h4>
                                <p>Sistem akan memberikan notifikasi jika terdeteksi emosi negatif yang tinggi:</p>
                                <ul>
                                    <li>Notifikasi akan muncul di layar</li>
                                    <li>Anda dapat melihat detail mahasiswa yang membutuhkan perhatian</li>
                                    <li>Berikan catatan dukungan atau tindakan lanjutan</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- Manajemen Catatan Dukungan -->
                    <div class="guide-section">
                        <h2><i class="bi bi-chat-square-text me-2"></i>Catatan Dukungan</h2>
                        <p class="text-muted mb-4">Kelola catatan dukungan untuk memberikan bimbingan dan perhatian kepada mahasiswa.</p>
                        
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Membaca Catatan Mahasiswa</h4>
                                <p>Untuk melihat catatan mahasiswa:</p>
                                <ol>
                                    <li>Buka menu <strong>Catatan Dukungan</strong></li>
                                    <li>Pilih mahasiswa yang ingin dilihat catatannya</li>
                                    <li>Baca catatan yang telah ditulis mahasiswa</li>
                                </ol>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Memberikan Tanggapan</h4>
                                <p>Anda dapat menanggapi catatan mahasiswa:</p>
                                <ol>
                                    <li>Klik pada catatan yang ingin ditanggapi</li>
                                    <li>Klik tombol <strong>Beri Tanggapan</strong></li>
                                    <li>Tulis tanggapan Anda</li>
                                    <li>Klik <strong>Kirim</strong> untuk mengirim tanggapan</li>
                                </ol>
                            </div>
                        </div>
                    </div>

                    <!-- Laporan dan Analisis -->
                    <div class="guide-section">
                        <h2><i class="bi bi-file-earmark-bar-graph me-2"></i>Laporan dan Analisis</h2>
                        
                        <div class="guide-step">
                            <div class="step-number">1</div>
                            <div class="step-content">
                                <h4>Membuat Laporan</h4>
                                <p>Untuk membuat laporan:</p>
                                <ol>
                                    <li>Buka halaman <strong>Laporan</strong></li>
                                    <li>Pilih rentang tanggal yang diinginkan</li>
                                    <li>Pilih kelas atau semua kelas</li>
                                    <li>Klik <strong>Generate Laporan</strong></li>
                                    <li>Unduh dalam format PDF atau Excel</li>
                                </ol>
                                
                                <!-- Report Generation Image -->
                                <div class="image-container">
                                    <img src="../assets/images/guide/report-generation.jpeg" alt="Tampilan Pembuatan Laporan" class="guide-image">
                                    <p class="image-caption">Gambar 5: Tampilan Pembuatan Laporan</p>
                                </div>
                            </div>
                        </div>

                        <div class="guide-step">
                            <div class="step-number">2</div>
                            <div class="step-content">
                                <h4>Menganalisis Data</h4>
                                <p>Gunakan fitur analisis untuk:</p>
                                <ul>
                                    <li>Melihat tren emosi dari waktu ke waktu</li>
                                    <li>Membandingkan data antar kelas</li>
                                    <li>Mengidentifikasi pola emosi yang muncul</li>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <!-- FAQ Section -->
                    <div class="guide-section">
                        <h2><i class="bi bi-question-circle-fill me-2"></i>Pertanyaan yang Sering Diajukan</h2>
                        
                        <div class="accordion mb-4" id="faqAccordion">
                            <div class="accordion-item">
                                <h3 class="accordion-header" id="headingOne">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                        Bagaimana cara menambahkan mahasiswa ke dalam kelas?
                                    </button>
                                </h3>
                                <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Untuk menambahkan mahasiswa ke dalam kelas:</p>
                                        <ol>
                                            <li>Buka halaman <strong>Kelas Saya</strong></li>
                                            <li>Pilih kelas yang diinginkan</li>
                                            <li>Klik tab <strong>Anggota Kelas</strong></li>
                                            <li>Klik tombol <strong>Tambah Mahasiswa</strong></li>
                                            <li>Masukkan email mahasiswa atau kode kelas</li>
                                            <li>Klik <strong>Tambahkan</strong></li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h3 class="accordion-header" id="headingTwo">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                        Apa yang harus dilakukan jika ada notifikasi emosi negatif tinggi?
                                    </button>
                                </h3>
                                <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Jika Anda menerima notifikasi emosi negatif tinggi:</p>
                                        <ol>
                                            <li>Buka notifikasi untuk melihat detail mahasiswa</li>
                                            <li>Periksa aktivitas terakhir mahasiswa tersebut</li>
                                            <li>Kirim pesan dukungan melalui fitur Catatan Dukungan</li>
                                            <li>Jika diperlukan, hubungi mahasiswa secara langsung</li>
                                            <li>Laporkan ke bagian konseling jika masalah berlanjut</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>

                            <div class="accordion-item">
                                <h3 class="accordion-header" id="headingThree">
                                    <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                        Bagaimana cara mengekspor data laporan?
                                    </button>
                                </h3>
                                <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body">
                                        <p>Untuk mengekspor data laporan:</p>
                                        <ol>
                                            <li>Buka halaman <strong>Laporan</strong></li>
                                            <li>Atur filter sesuai kebutuhan (tanggal, kelas, dll)</li>
                                            <li>Klik tombol <strong>Generate Laporan</strong></li>
                                            <li>Pilih format ekspor (PDF atau Excel)</li>
                                            <li>Klik <strong>Unduh</strong> untuk menyimpan file</li>
                                        </ol>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Download Button at Bottom -->
                    <div class="text-center my-5">
                        <a href="generate_panduan_pdf.php" class="btn btn-primary" id="downloadPdfBottom">
                            <i class="bi bi-download me-2"></i> Unduh Panduan (PDF)
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Copyright Footer -->
    <footer class="py-3 text-center text-muted border-top" style="background-color: #f8f9fa; margin-top: 3rem;">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Padding to prevent content from being hidden behind fixed footer -->
    <div style="height: 60px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            
            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('show');
                    overlay.style.display = sidebar.classList.contains('show') ? 'block' : 'none';
                    document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
                });
            }
            
            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('show');
                    overlay.style.display = 'none';
                    document.body.style.overflow = '';
                });
            }
            
            // Close sidebar when clicking on a nav link
            const navLinks = document.querySelectorAll('.sidebar .nav-link');
            navLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) {
                        sidebar.classList.remove('show');
                        overlay.style.display = 'none';
                        document.body.style.overflow = '';
                    }
                });
            });
            
            // Add active class to current nav item
            const currentLocation = location.href;
            const menuItems = document.querySelectorAll('.sidebar .nav-link');
            menuItems.forEach(item => {
                if (item.href === currentLocation) {
                    item.classList.add('active');
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/@popperjs/core@2.11.6/dist/umd/popper.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</body>
</html>
