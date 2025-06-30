<?php
session_start();
require_once '../koneksi.php';
require_once '../vendor/autoload.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

// Create new PDF document
$pdf = new TCPDF(PDF_PAGE_ORIENTATION, PDF_UNIT, PDF_PAGE_FORMAT, true, 'UTF-8', false);

// Set document information
$pdf->SetCreator('SentiSyncEd');
$pdf->SetAuthor('SentiSyncEd');
$pdf->SetTitle('Panduan Penggunaan SentiSyncEd - Mahasiswa');
$pdf->SetSubject('Panduan Penggunaan');
$pdf->SetKeywords('SentiSyncEd, Panduan, Mahasiswa');

// Set default header data
$pdf->SetHeaderData('', 0, 'Panduan Penggunaan SentiSyncEd', 'Untuk Mahasiswa');

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
$pdf->Cell(0, 5, 'Untuk Mahasiswa', 0, 1, 'C');
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
$inputEmosiImage = getImageAsBase64($basePath . '/input-emosi.jpeg');
$grafikEmosiImage = getImageAsBase64($basePath . '/grafik-emosi.jpeg');

// Content
$content = '
<style>
    h1 { font-size: 16pt; color: #2c3e50; margin: 5px 0; page-break-before: always; }
    h2 { font-size: 14pt; color: #2c3e50; margin: 15px 0 5px 0; }
    h3 { font-size: 12pt; color: #2c3e50; margin: 10px 0 5px 0; }
    p { margin: 4px 0; }
    ol, ul { margin: 4px 0; padding-left: 20px; }
    li { margin: 3px 0; }
    .step { font-weight: bold; color: #4e73df; }
    .note { 
        background-color: #f8f9fc; 
        border-left: 4px solid #4e73df;
        padding: 10px;
        margin: 10px 0;
        font-style: italic;
        font-size: 9pt;
        border-radius: 0 4px 4px 0;
    }
    .warning {
        background-color: #fff3cd;
        border-left: 4px solid #ffc107;
        padding: 10px;
        margin: 10px 0;
        font-size: 9pt;
        border-radius: 0 4px 4px 0;
    }
    .img-container {
        text-align: center;
        margin: 15px 0;
        page-break-inside: avoid;
    }
    .img-container img {
        max-width: 100%;
        height: auto;
        border: 1px solid #ddd;
        border-radius: 4px;
    }
    .img-caption {
        font-size: 9pt;
        color: #666;
        margin-top: 5px;
        font-style: italic;
    }
</style>';

// Add table of contents
$content .= '
<h1 style="margin-top: 5px; page-break-before: avoid !important;">Daftar Isi</h1>
<ol>
    <li>Login dan Dashboard</li>
    <li>Mengisi Catatan Perasaan</li>
    <li>Melihat Riwayat Catatan</li>
    <li>Pertanyaan yang Sering Diajukan</li>
</ol>';

// Add login and dashboard section
$content .= '
<h1 style="page-break-before: avoid !important;">1. Login dan Dashboard</h1>

<h2>1.1 Login ke Akun Anda</h2>
<p>Masuk ke SentiSyncEd menggunakan kredensial yang telah diberikan:</p>
<ol>
    <li>Buka halaman login SentiSyncEd</li>
    <li>Pilih peran sebagai <strong>Mahasiswa</strong></li>
    <li>Masukkan NIM dan password Anda</li>
    <li>Klik tombol <strong>Login</strong></li>
</ol>';

// Add login image if available
if (!empty($loginImage)) {
    $content .= '
    <div class="img-container">
        <img src="' . $loginImage . '" alt="Tampilan Halaman Login Mahasiswa">
        <div class="img-caption">Gambar 1.1: Tampilan Halaman Login Mahasiswa</div>
    </div>';
}

$content .= '
<div class="note">Pastikan kredensial yang Anda masukkan sudah benar dan sesuai dengan yang diberikan oleh kampus.</div>

<h2>1.2 Navigasi Dashboard</h2>
<p>Setelah login, Anda akan diarahkan ke halaman dashboard yang menampilkan:</p>
<ul>
    <li>Kelas yang sedang aktif</li>
    <li>Statistik catatan perasaan Anda</li>
    <li>Notifikasi terbaru</li>
    <li>Akses cepat ke fitur utama</li>
</ul>';

// Add dashboard image if available
if (!empty($dashboardImage)) {
    $content .= '
    <div class="img-container">
        <img src="' . $dashboardImage . '" alt="Tampilan Dashboard Mahasiswa">
        <div class="img-caption">Gambar 1.2: Tampilan Dashboard Mahasiswa</div>
    </div>';
}

// Add catatan perasaan section
$content .= '
<h1>2. Mengisi Catatan Perasaan</h1>

<h2>2.1 Membuat Catatan Baru</h2>
<p>Untuk mengisi catatan perasaan harian:</p>
<ol>
    <li>Klik tombol <strong>Buat Catatan</strong> di menu sidebar</li>
    <li>Pilih emosi yang sedang Anda rasakan</li>
    <li>Isi deskripsi perasaan Anda (opsional)</li>
    <li>Klik tombol <strong>Simpan</strong></li>
</ol>';

// Add input emosi image if available
if (!empty($inputEmosiImage)) {
    $content .= '
    <div class="img-container">
        <img src="' . $inputEmosiImage . '" alt="Tampilan Halaman Input Emosi">
        <div class="img-caption">Gambar 2.1: Tampilan Halaman Input Emosi</div>
    </div>';
}

$content .= '
<div class="note">
    <strong>Tips:</strong> Anda dapat mengisi catatan perasaan kapan saja selama sesi kelas berlangsung.
</div>';

// Add riwayat catatan section
$content .= '
<h1>3. Melihat Riwayat Catatan</h1>

<h2>3.1 Melacak Perkembangan</h2>
<p>Anda dapat melihat riwayat catatan perasaan Anda:</p>
<ol>
    <li>Buka menu <strong>Riwayat</strong> di sidebar</li>
    <li>Pilih rentang tanggal yang diinginkan</li>
    <li>Lihat grafik perkembangan emosi Anda</li>
    <li>Klik pada tanggal tertentu untuk melihat detail catatan</li>
</ol>';

// Add grafik emosi image if available
if (!empty($grafikEmosiImage)) {
    $content .= '
    <div class="img-container">
        <img src="' . $grafikEmosiImage . '" alt="Tampilan Grafik Perkembangan Emosi">
        <div class="img-caption">Gambar 3.1: Tampilan Grafik Perkembangan Emosi</div>
    </div>';
}

$content .= '
<div class="warning">
    <strong>Perhatian:</strong> Data riwayat hanya tersedia untuk periode tertentu sesuai kebijakan kampus.
</div>';

// Add FAQ section
$content .= '
<h1>4. Pertanyaan yang Sering Diajukan</h1>

<h3>1. Berapa kali saya bisa mengisi catatan perasaan dalam sehari?</h3>
<p>Anda dapat mengisi catatan perasaan satu kali per sesi kelas. Jika Anda memiliki beberapa kelas dalam sehari, pastikan mengisi catatan untuk setiap sesi.</p>

<h3>2. Apakah dosen saya bisa melihat isi catatan saya?</h3>
<p>Ya, dosen Anda dapat melihat ringkasan emosi dan komentar yang Anda berikan, namun identitas Anda akan disamarkan untuk menjaga privasi.</p>

<h3>3. Bagaimana jika saya lupa mengisi catatan?</h3>
<p>Anda tidak dapat mengisi catatan untuk hari yang telah lewat. Pastikan untuk mengisi catatan tepat waktu saat sesi kelas berlangsung.</p>

<h3>4. Apakah data saya aman?</h3>
<p>Ya, data Anda dilindungi dengan enkripsi yang kuat dan hanya dapat diakses oleh pihak yang berwenang sesuai dengan kebijakan privasi kami.</p>';

// Extract Table of Contents (Daftar Isi) section
$tocStart = strpos($content, '<h1 style="margin-top: 5px; page-break-before: avoid !important;">Daftar Isi</h1>');
$tocEnd = strpos($content, '<h1 style="page-break-before: avoid !important;">');
$tocContent = substr($content, $tocStart, $tocEnd - $tocStart);

// Remove Table of Contents from main content
$mainContent = str_replace($tocContent, '', $content);

// Write Table of Contents on the first page
$pdf->writeHTML($tocContent, true, false, true, false, '');

// Split remaining content by main sections
$sections = explode('<h1', $mainContent);

// Process each section
foreach ($sections as $i => $section) {
    if (trim($section) === '') continue;
    
    // Skip the first section if it's empty
    if ($i === 0 && trim(strip_tags($section)) === '') continue;
    
    // Add a new page only if this is not the first section and we're not at the start of a new page
    if ($i > 0 && $pdf->getY() > 30) {
        $pdf->AddPage();
    }
    
    // Add the h1 tag back (it was removed by explode)
    $sectionContent = ($i > 0 ? '<h1' : '') . $section;
    
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
$pdf->Output('Panduan_SentiSyncEd_Mahasiswa.pdf', 'D');
?>
