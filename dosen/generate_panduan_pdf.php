<?php
session_start();
require_once '../koneksi.php';
require_once '../vendor/autoload.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('SentiSyncEd');
$pdf->SetAuthor('SentiSyncEd');
$pdf->SetTitle('Panduan Penggunaan SentiSyncEd - Dosen');
$pdf->SetSubject('Panduan Penggunaan');
$pdf->SetKeywords('SentiSyncEd, Panduan, Dosen');

// Set default header data
$pdf->SetHeaderData('', 0, 'Panduan Penggunaan SentiSyncEd', 'Untuk Dosen');

// Set header and footer fonts
$pdf->setHeaderFont(Array(PDF_FONT_NAME_MAIN, '', PDF_FONT_SIZE_MAIN));
$pdf->setFooterFont(Array(PDF_FONT_NAME_DATA, '', PDF_FONT_SIZE_DATA));

// Set default monospaced font
$pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

// Set margins
$pdf->SetMargins(15, 25, 15);
$pdf->SetHeaderMargin(10);
$pdf->SetFooterMargin(10);

// Set auto page breaks
$pdf->SetAutoPageBreak(TRUE, 25);

// Set image scale factor
$pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

// Add a page
$pdf->AddPage();

// Set font
$pdf->SetFont('helvetica', '', 10);

// Add logo
$image_file = '../assets/img/logo.png';
if (file_exists($image_file)) {
    $pdf->Image($image_file, 15, 10, 30, '', 'PNG', '', 'T', false, 300, '', false, false, 0, false, false, false);
}

// Title and date
$pdf->SetFont('helvetica', 'B', 16);
$pdf->Cell(0, 5, 'Panduan Penggunaan SentiSyncEd', 0, 1, 'C');
$pdf->SetFont('helvetica', 'B', 14);
$pdf->Cell(0, 5, 'Untuk Dosen', 0, 1, 'C');
$pdf->SetFont('helvetica', 'I', 10);
$pdf->Cell(0, 5, 'Dibuat pada: ' . date('d F Y'), 0, 1, 'R');
$pdf->Ln(5);

// Define base path for images
$basePath = realpath(dirname(__FILE__) . '/../assets/images/guide/');

// Function to get image as base64
function getImageAsBase64($path) {
    if (file_exists($path)) {
        $type = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        return 'data:image/' . $type . ';base64,' . base64_encode($data);
    }
    return '';
}

// Get all images as base64
$loginImage = getImageAsBase64($basePath . '/login.jpeg');
$dashboardImage = getImageAsBase64($basePath . '/dashboard.jpeg');
$kelasSayaImage = getImageAsBase64($basePath . '/kelas-saya.jpeg');
$classSessionImage = getImageAsBase64($basePath . '/class-session.jpeg');
$emotionMonitorImage = getImageAsBase64($basePath . '/emotion-monitor.jpeg');
$grafikEmosiImage = getImageAsBase64($basePath . '/grafik-emosi.jpeg');
$reportImage = getImageAsBase64($basePath . '/report-generation.jpeg');

// Content
$content = '
<style>
    h1 { font-size: 16pt; color: #2c3e50; margin: 5px 0; }
    h2 { font-size: 14pt; color: #2c3e50; margin: 5px 0; }
    h3 { font-size: 12pt; color: #2c3e50; margin: 5px 0; }
    p { margin: 4px 0; }
    ol, ul { margin: 4px 0; padding-left: 20px; }
    li { margin: 3px 0; }
    .step { font-weight: bold; color: #4e73df; }
    .note { 
        background-color: #f8f9fc; 
        border-left: 4px solid #4e73df;
        padding: 6px;
        margin: 8px 0;
        font-style: italic;
        font-size: 9pt;
    }
    .warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 6px;
        margin: 8px 0;
        font-size: 9pt;
    }
</style>

<h1 style="margin-top: 5px;">Daftar Isi</h1>
<ol>
    <li>Login dan Dashboard</li>
    <li>Manajemen Kelas</li>
    <li>Monitor Emosi Real-time</li>
    <li>Catatan Dukungan</li>
    <li>Laporan dan Analisis</li>
</ol>

<h1>1. Login dan Dashboard</h1>

<h2>1.1 Login ke Akun Anda</h2>
<p>Masuk ke SentiSyncEd menggunakan kredensial yang telah diberikan:</p>
<ol>
    <li>Buka halaman login SentiSyncEd</li>
    <li>Pilih peran sebagai <strong>Dosen</strong></li>
    <li>Masukkan email dan password Anda</li>
    <li>Klik tombol <strong>Login</strong></li>
</ol>

<img src="' . $loginImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Halaman Login">

<div class="note">Pastikan kredensial yang Anda masukkan sudah benar dan sesuai dengan yang diberikan oleh administrator.</div>

<h2>1.2 Navigasi Dashboard</h2>
<p>Setelah login, Anda akan diarahkan ke halaman dashboard yang menampilkan:</p>
<ul>
    <li>Ringkasan kelas yang Anda ajar</li>
    <li>Statistik emosi mahasiswa</li>
    <li>Notifikasi terbaru</li>
    <li>Akses cepat ke fitur utama</li>
</ul>

<img src="' . $dashboardImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Dashboard Dosen">

<pagebreak />
<h1>2. Manajemen Kelas</h1>

<h2>2.1 Membuka Sesi Kelas</h2>
<p>Untuk memulai sesi kelas:</p>
<ol>
    <li>Pilih menu <strong>Kelas Saya</strong> di sidebar</li>
    <li>Pilih kelas yang ingin Anda buka sesinya</li>
    <li>Klik tombol <strong>Buka Sesi</strong></li>
    <li>Atur durasi sesi jika diperlukan</li>
    <li>Klik <strong>Mulai Sesi</strong></li>
</ol>

<img src="' . $kelasSayaImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Kelas Saya">
<img src="' . $classSessionImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Pembukaan Sesi Kelas">

<div class="warning">Pastikan koneksi internet stabil sebelum memulai sesi.</div>

<h2>2.2 Mengelola Anggota Kelas</h2>
<p>Anda dapat mengelola anggota kelas dengan:</p>
<ul>
    <li>Menambahkan/menghapus mahasiswa</li>
    <li>Mengundang mahasiswa menggunakan kode kelas</li>
    <li>Melihat daftar kehadiran</li>
</ul>

<pagebreak />
<h1>3. Monitor Emosi Real-time</h1>

<h2>3.1 Memantau Emosi Mahasiswa</h2>
<p>Selama sesi berlangsung:</p>
<ol>
    <li>Buka halaman <strong>Monitor Emosi</strong></li>
    <li>Pantau grafik emosi yang diperbarui secara real-time</li>
    <li>Identifikasi perubahan emosi mahasiswa</li>
    <li>Gunakan filter untuk melihat berdasarkan waktu atau mahasiswa tertentu</li>
</ol>

<img src="' . $emotionMonitorImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Monitor Emosi">
<img src="' . $grafikEmosiImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Grafik Emosi">

<h2>3.2 Menangani Emosi Negatif</h2>
<p>Sistem akan memberikan notifikasi jika terdeteksi emosi negatif yang tinggi:</p>
<ul>
    <li>Notifikasi akan muncul di layar</li>
    <li>Anda dapat melihat detail mahasiswa yang membutuhkan perhatian</li>
    <li>Berikan catatan dukungan atau tindakan lanjutan</li>
</ul>

<pagebreak />

<pagebreak />
<h1>4. Catatan Dukungan</h1>

<h2>4.1 Membaca Catatan Mahasiswa</h2>
<p>Untuk melihat catatan mahasiswa:</p>
<ol>
    <li>Buka menu <strong>Catatan Dukungan</strong></li>
    <li>Pilih mahasiswa yang ingin dilihat catatannya</li>
    <li>Baca catatan yang telah ditulis mahasiswa</li>
</ol>

<div class="note">Gambar: Akses menu Catatan Dukungan melalui sidebar kiri</div>

<h2>4.2 Memberikan Tanggapan</h2>
<p>Anda dapat menanggapi catatan mahasiswa:</p>
<ol>
    <li>Klik pada catatan yang ingin ditanggapi</li>
    <li>Klik tombol <strong>Beri Tanggapan</strong></li>
    <li>Tulis tanggapan Anda</li>
    <li>Klik <strong>Kirim</strong> untuk mengirim tanggapan</li>
</ol>

<pagebreak />

<pagebreak />
<h1>5. Laporan dan Analisis</h1>

<h2>5.1 Membuat Laporan</h2>
<p>Untuk membuat laporan:</p>
<ol>
    <li>Buka halaman <strong>Laporan</strong></li>
    <li>Pilih rentang tanggal yang diinginkan</li>
    <li>Pilih kelas atau semua kelas</li>
    <li>Klik <strong>Generate Laporan</strong></li>
    <li>Unduh dalam format PDF atau Excel</li>
</ol>

<img src="' . $reportImage . '" style="max-width: 100%; border: 1px solid #ddd; margin: 10px 0;" alt="Tampilan Pembuatan Laporan">

<h2>5.2 Menganalisis Data</h2>
<p>Gunakan fitur analisis untuk:</p>
<ul>
    <li>Melihat tren emosi dari waktu ke waktu</li>
    <li>Membandingkan data antar kelas</li>
    <li>Mengidentifikasi pola emosi yang muncul</li>
</ul>

<pagebreak />

<pagebreak />
<h1>FAQ (Pertanyaan yang Sering Diajukan)</h1>

<h3>1. Bagaimana cara menambahkan mahasiswa ke dalam kelas?</h3>
<ol>
    <li>Buka halaman <strong>Kelas Saya</strong></li>
    <li>Pilih kelas yang diinginkan</li>
    <li>Klik tab <strong>Anggota Kelas</strong></li>
    <li>Klik tombol <strong>Tambah Mahasiswa</strong></li>
    <li>Masukkan email mahasiswa atau kode kelas</li>
    <li>Klik <strong>Tambahkan</strong></li>
</ol>

<h3>2. Apa yang harus dilakukan jika ada notifikasi emosi negatif tinggi?</h3>
<ol>
    <li>Buka notifikasi untuk melihat detail mahasiswa</li>
    <li>Periksa aktivitas terakhir mahasiswa tersebut</li>
    <li>Kirim pesan dukungan melalui fitur Catatan Dukungan</li>
    <li>Jika diperlukan, hubungi mahasiswa secara langsung</li>
    <li>Laporkan ke bagian konseling jika masalah berlanjut</li>
</ol>

<h3>3. Bagaimana cara mengekspor data laporan?</h3>
<ol>
    <li>Buka halaman <strong>Laporan</strong></li>
    <li>Atur filter sesuai kebutuhan (tanggal, kelas, dll)</li>
    <li>Klik tombol <strong>Generate Laporan</strong></li>
    <li>Pilih format ekspor (PDF atau Excel)</li>
    <li>Klik <strong>Unduh</strong> untuk menyimpan file</li>
</ol>
';

// Split content by sections
$sections = explode('<h1>', $content);

// Process first section (before first h1)
if (count($sections) > 0) {
    $pdf->writeHTML($sections[0], true, false, true, false, '');
}

// Process remaining sections
for ($i = 1; $i < count($sections); $i++) {
    // Skip if section is empty
    if (trim($sections[$i]) === '') continue;
    
    // Add page break before each main section (except the first one)
    $pdf->AddPage();
    
    // Add the section header back (it was removed by explode)
    $sectionContent = '<h1>' . $sections[$i];
    
    // Write the section content
    $pdf->writeHTML($sectionContent, true, false, true, false, '');
    
    // Add some space after the section
    $pdf->Ln(5);
}

// Add footer to all pages except the last one if it's empty
$pdf->setPageMark();
$pdf->SetY(-15);
$pdf->SetFont('helvetica', 'I', 8);

// Only add footer if there's content on the page
if ($pdf->getPage() > 0) {
    $pdf->Cell(0, 10, 'Halaman '.$pdf->getAliasNumPage().' dari '.$pdf->getAliasNbPages(), 0, false, 'C', 0, '', 0, false, 'T', 'M');
}

// Remove the last page if it's empty
$lastPage = $pdf->getPage();
if ($pdf->getY() < 30) {  // If there's less than 30 units of content on the last page
    $pdf->deletePage($lastPage);
}

// Close and output PDF document
$pdf->Output('Panduan_SentiSyncEd_Dosen.pdf', 'D');
?>
