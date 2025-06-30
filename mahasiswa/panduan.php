<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Pastikan user sudah login dan berperan sebagai Mahasiswa
if (!isLoggedIn() || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$page_title = 'Panduan Penggunaan';
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $page_title ?> - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
        /* Base Layout */
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
        
        /* Mobile Navbar */
        .mobile-navbar {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            padding: 0.75rem 1.5rem;
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1030;
            box-shadow: 0 0.15rem 1.75rem 0 rgba(58, 59, 69, 0.15);
            display: none;
        }
        @media (max-width: 991.98px) {
            .mobile-navbar {
                display: flex;
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
        
        .guide-section h3 {
            color: #4e73df;
            font-weight: 600;
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #eaecf4;
        }
        
        .guide-section p {
            color: #5a5c69;
            line-height: 1.6;
            margin-bottom: 1.25rem;
        }
        .guide-step {
            display: flex;
            margin-bottom: 2rem;
            position: relative;
            padding-left: 3rem;
        }
        /* Step Number Styling */
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
        /* Step Content */
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
        /* Images and Media */
        .step-image {
            max-width: 100%;
            border-radius: 0.5rem;
            margin: 1.5rem 0;
            box-shadow: 0 0.25rem 0.5rem rgba(0,0,0,0.1);
            border: 1px solid #e3e6f0;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        
        .step-image:hover {
            transform: translateY(-3px);
            box-shadow: 0 0.5rem 1rem rgba(0,0,0,0.15);
        }
        /* Icons and Visual Elements */
        .feature-icon {
            font-size: 2.25rem;
            margin-bottom: 1.25rem;
            color: #4e73df;
            display: inline-block;
            background: rgba(78, 115, 223, 0.1);
            width: 4rem;
            height: 4rem;
            line-height: 4rem;
            text-align: center;
            border-radius: 50%;
            transition: all 0.3s;
        }
        
        .guide-section:hover .feature-icon {
            background: rgba(78, 115, 223, 0.2);
            transform: rotate(5deg) scale(1.05);
        }
        /* Headings */
        h1, h2, h3, h4, h5, h6 {
            font-family: 'Poppins', sans-serif;
            font-weight: 600;
        }
        
        h1 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
        }
        
        h2 {
            color: #2c3e50;
            margin: 2.5rem 0 1.5rem;
            padding-bottom: 0.75rem;
            border-bottom: 3px solid #4e73df;
            display: inline-block;
            font-size: 1.6rem;
        }
        h3 {
            color: #34495e;
            margin-top: 1.5rem;
        }
        /* Notes and Callouts */
        .note {
            background-color: #f8f9fc;
            border-left: 4px solid #4e73df;
            padding: 1.25rem;
            margin: 1.5rem 0;
            border-radius: 0 0.35rem 0.35rem 0;
            position: relative;
            transition: all 0.3s;
        }
        
        .note:before {
            content: '\f05a';
            font-family: 'bootstrap-icons';
            position: absolute;
            left: 1rem;
            top: 1rem;
            color: #4e73df;
            font-size: 1.25rem;
        }
        
        .note p {
            margin-left: 1.5rem;
            margin-bottom: 0;
        }
        
        /* Buttons */
        .btn-primary {
            background: linear-gradient(135deg, #4e73df 0%, #224abe 100%);
            border: none;
            padding: 0.5rem 1.5rem;
            font-weight: 500;
            transition: all 0.3s;
        }
        
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(78, 115, 223, 0.3);
        }
        
        /* Responsive Adjustments */
        @media (max-width: 767.98px) {
            .guide-section {
                padding: 1.5rem;
                margin-bottom: 1.5rem;
            }
            
            .step-content {
                padding-left: 0.5rem;
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
<body class="bg-light">
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
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Overlay for mobile sidebar -->
    <div class="overlay" id="overlay"></div>

    <!-- Sidebar -->
    <div class="sidebar" id="sidebar">
        <div class="sidebar-header text-center py-4 border-bottom" style="border-color: rgba(255,255,255,0.15) !important;">
            <h2 class="mb-0" style="color:#fff; font-weight:700; font-size:24px;">SentiSyncEd</h2>
        </div>
        <nav class="nav flex-column py-3">
            <a href="dashboard_mahasiswa.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-house me-2"></i> Dashboard
            </a>
            <a href="pilih_kelas.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-journal me-2"></i> Pilih Kelas
            </a>
            <a href="kelas_saya.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-book me-2"></i> Kelas Saya
            </a>
            <a href="input_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-emoji-smile me-2"></i> Input Emosi
            </a>
            <a href="tulis_curhat.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-chat-dots me-2"></i> Tulis Curhat
            </a>
            <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white" style="font-size: 1.1rem;">
                <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
            </a>
            <a href="panduan.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-question-circle me-2"></i> Panduan Penggunaan
            </a>
        </nav>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- User Dropdown in Content Area -->
        <div class="user-dropdown dropdown d-none d-lg-block">
            <button class="btn dropdown-toggle d-flex align-items-center" type="button" id="userDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="bi bi-person-circle me-2"></i>
                <?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?>
            </button>
            <ul class="dropdown-menu dropdown-menu-end shadow" aria-labelledby="userDropdown">
                <li><a class="dropdown-item" href="edit_profile.php"><i class="bi bi-pencil-square me-2"></i>Edit Profil</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1"><i class="bi bi-box-arrow-right me-2"></i>Logout</a></li>
            </ul>
        </div>

        <div class="container-fluid">
            <div class="row">
                <main class="col-12 px-0">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-4 border-bottom">
                    <h1 class="h2"><i class="bi bi-question-circle me-2"></i>Panduan Penggunaan SentiSyncEd</h1>
                    <button id="downloadPdfTop" class="btn btn-primary d-flex align-items-center">
                        <i class="bi bi-file-earmark-pdf me-2"></i>Download PDF
                    </button>
                </div>
                
                <!-- Guide Content -->
                <div id="guide-content">

                <div class="guide-section">
                    <h2><i class="bi bi-journals me-2"></i>Panduan Lengkap Mahasiswa</h2>
                    <p class="lead">Selamat datang di SentiSyncEd! Berikut adalah panduan lengkap untuk membantu Anda menggunakan platform ini dengan maksimal.</p>
                    
                    <div class="note">
                        <i class="bi bi-info-circle-fill me-2"></i>
                        <strong>Tips:</strong> Gunakan menu di sebelah kiri untuk berpindah antar fitur dengan mudah.
                    </div>
                </div>

                <div class="guide-section">
                    <h3><i class="bi bi-1-circle me-2"></i>Memilih Kelas</h3>
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Mengakses Menu Pilih Kelas</h4>
                            <p>Klik menu <strong>Pilih Kelas</strong> di sidebar untuk melihat daftar kelas yang tersedia.</p>
                            <p>Pilih kelas yang ingin Anda ikuti dengan menekan tombol <span class="badge bg-primary">Daftar</span>.</p>
                            <img src="../assets/images/guide/pilih-kelas.jpeg" alt="Panduan Pilih Kelas" class="step-image img-fluid">
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Melihat Kelas yang Diikuti</h4>
                            <p>Setelah mendaftar, Anda dapat melihat daftar kelas yang sedang Anda ikuti di menu <strong>Kelas Saya</strong>.</p>
                            <p>Di sini Anda bisa melihat jadwal, dosen pengampu, dan akses ke kelas tersebut.</p>
                            <img src="../assets/images/guide/kelas-saya.jpeg" alt="Panduan Kelas Saya" class="step-image img-fluid">
                        </div>
                    </div>
                </div>

                <div class="guide-section">
                    <h3><i class="bi bi-2-circle me-2"></i>Menginputkan Emosi</h3>
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Mengisi Form Input Emosi</h4>
                            <p>Pergi ke menu <strong>Input Emosi</strong> sebelum atau sesudah kelas dimulai.</p>
                            <p>Pilih kelas dari dropdown, pilih emosi yang sesuai, dan isi keterangan jika diperlukan.</p>
                            <img src="../assets/images/guide/input-emosi.jpeg" alt="Panduan Input Emosi" class="step-image img-fluid">
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Memahami Emosi yang Tersedia</h4>
                            <ul class="list-unstyled">
                                <li><i class="bi bi-emoji-smile-fill text-success me-2"></i>Senang - Merasa bahagia dan bersemangat</li>
                                <li><i class="bi bi-emoji-dizzy-fill text-danger me-2"></i>Stres - Merasa cemas, tertekan, atau kewalahan</li>
                                <li><i class="bi bi-emoji-angry-fill text-danger me-2"></i>Lelah - Merasa kehabisan energi secara fisik atau mental</li>
                                <li><i class="bi bi-emoji-neutral-fill text-warning me-2"></i>Netral - Merasa biasa saja tanpa emosi yang dominan</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="guide-section">
                    <h3><i class="bi bi-4-circle me-2"></i>Menulis Curhat</h3>
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Membuat Curhat Baru</h4>
                            <p>Pergi ke menu <strong>Tulis Curhat</strong> untuk mengekspresikan perasaan atau pikiran Anda.</p>
                            <p>Isi judul dan isi curhat, lalu klik <button class="btn btn-sm btn-primary">Simpan Curhat</button>.</p>
                            <div class="note">
                                <i class="bi bi-shield-lock-fill me-2"></i>
                                <strong>Privasi Terjaga:</strong> Curhat Anda bersifat pribadi dan hanya dapat dibaca oleh dosen wali Anda.
                            </div>
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Melihat Riwayat Curhat</h4>
                            <p>Di halaman yang sama, Anda bisa melihat daftar curhat yang pernah Anda tulis.</p>
                            <p>Anda dapat melihat status balasan dari dosen wali Anda.</p>
                        </div>
                    </div>
                </div>

                      <div class="guide-section">
                    <h3><i class="bi bi-4-circle me-2"></i>Menulis Curhat</h3>
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Membuat Curhat Baru</h4>
                            <p>Pergi ke menu <strong>Tulis Curhat</strong> untuk mengekspresikan perasaan atau pikiran Anda.</p>
                            <p>Isi judul dan isi curhat, lalu klik <button class="btn btn-sm btn-primary">Simpan Curhat</button>.</p>
                            <div class="note">
                                <i class="bi bi-shield-lock-fill me-2"></i>
                                <strong>Privasi Terjaga:</strong> Curhat Anda bersifat pribadi dan hanya dapat dibaca oleh dosen wali Anda.
                            </div>
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Melihat Riwayat Curhat</h4>
                            <p>Di halaman yang sama, Anda bisa melihat daftar curhat yang pernah Anda tulis.</p>
                            <p>Anda dapat melihat status balasan dari dosen wali Anda.</p>
                        </div>
                    </div>
                </div>          <div class="guide-section">
                    <h3><i class="bi bi-3-circle me-2"></i>Melihat Grafik Emosi</h3>
                    <div class="guide-step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h4>Mengakses Grafik Emosi</h4>
                            <p>Klik menu <strong>Grafik Emosi</strong> untuk melihat perkembangan emosi Anda dalam bentuk grafik.</p>
                            <p>Anda dapat memfilter berdasarkan rentang tanggal tertentu untuk melihat pola emosi Anda.</p>
                            <img src="../assets/images/guide/grafik-emosi.jpeg" alt="Panduan Grafik Emosi" class="step-image img-fluid">
                        </div>
                    </div>

                    <div class="guide-step">
                        <div class="step-number">2</div>
                        <div class="step-content">
                            <h4>Memahami Grafik</h4>
                            <ul>
                                <li>Garis biru menunjukkan tren emosi Anda dari waktu ke waktu</li>
                                <li>Titik-titik pada grafik menunjukkan catatan emosi yang telah Anda input</li>
                                <li>Gunakan filter tanggal untuk melihat periode tertentu</li>
                            </ul>
                        </div>
                    </div>
                </div>

                <div class="guide-section">
                    <h3><i class="bi bi-question-circle-fill me-2"></i>Pertanyaan yang Sering Diajukan</h3>
                    
                    <div class="accordion mb-4" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="true" aria-controls="collapseOne">
                                    Apakah data emosi saya aman?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse show" aria-labelledby="headingOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Ya, data emosi Anda disimpan dengan aman dan hanya dapat diakses oleh Anda dan dosen wali Anda. Kami menggunakan enkripsi untuk melindungi data pribadi Anda.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Bisakah saya mengubah input emosi yang sudah dikirim?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="headingTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Input emosi tidak dapat diubah setelah dikirim. Namun, Anda dapat menambahkan catatan emosi baru untuk memperbarui kondisi terkini Anda.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Siapa yang bisa melihat curhat saya?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="headingThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Curhat Anda hanya dapat dibaca oleh Anda dan dosen wali Anda. Data ini bersifat rahasia dan tidak akan dibagikan ke pihak lain tanpa izin Anda.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="text-center mt-5 mb-4">
                    <button id="downloadPdf" class="btn btn-primary mb-3">
                        <i class="bi bi-download me-2"></i>Download Panduan (PDF)
                    </button>
                    <p class="text-muted">Atau simpan panduan ini untuk dibaca offline</p>
                    
                    <div class="mt-4 pt-4 border-top">
                        <h4>Masih membutuhkan bantuan?</h4>
                        <p>Hubungi dosen wali Anda atau tim dukungan SentiSyncEd untuk pertanyaan lebih lanjut.</p>
                        <a href="mailto:support@sentisynced.edu" class="btn btn-outline-primary">
                            <i class="bi bi-envelope me-2"></i>Hubungi Dukungan
                        </a>
                    </div>
                </div>
                </main>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Toggle sidebar on mobile
        document.addEventListener('DOMContentLoaded', function() {
            const sidebar = document.getElementById('sidebar');
            const sidebarToggle = document.getElementById('sidebarToggle');
            const overlay = document.getElementById('overlay');
            const mobileNavToggle = document.getElementById('mobileNavToggle');

            if (sidebarToggle) {
                sidebarToggle.addEventListener('click', function() {
                    sidebar.classList.toggle('active');
                    overlay.classList.toggle('active');
                });
            }

            if (overlay) {
                overlay.addEventListener('click', function() {
                    sidebar.classList.remove('active');
                    overlay.classList.remove('active');
                });
            }

            // Function to handle PDF download
            function downloadPDF(buttonId) {
                try {
                    const btn = document.getElementById(buttonId);
                    if (!btn) return;
                    
                    const originalText = btn.innerHTML;
                    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Menyiapkan PDF...';
                    btn.disabled = true;
                    
                    // Redirect to the server-side PDF generator
                    window.location.href = 'generate_panduan_pdf.php';
                    
                    // Reset button after a short delay
                    setTimeout(() => {
                        btn.innerHTML = originalText;
                        btn.disabled = false;
                    }, 3000);
                    
                } catch (error) {
                    console.error('Error in downloadPDF:', error);
                    alert('Terjadi kesalahan: ' + error.message);
                    const btn = document.getElementById(buttonId);
                    if (btn) {
                        btn.innerHTML = 'Download PDF';
                        btn.disabled = false;
                    }
                }
            }

            // Add event listeners to download buttons
            const downloadButtons = ['downloadPdf', 'downloadPdfTop'];
            downloadButtons.forEach(buttonId => {
                const button = document.getElementById(buttonId);
                if (button) {
                    button.addEventListener('click', function() {
                        downloadPDF(buttonId);
                    });
                }
            });
        });
    </script>
</body>
</html>
