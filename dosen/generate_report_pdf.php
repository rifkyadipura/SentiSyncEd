<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get parameters
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$start_date = isset($_GET['start_date']) ? $_GET['start_date'] : '';
$end_date = isset($_GET['end_date']) ? $_GET['end_date'] : '';

if ($class_id <= 0 || empty($start_date) || empty($end_date)) {
    die('Missing required parameters');
}

// Get class info
$stmt = $conn->prepare("SELECT class_name FROM classes WHERE id = ? AND dosen_id = ?");
$stmt->execute([$class_id, $_SESSION['user_id']]);
$class = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$class) {
    die('Class not found or you do not have permission to access it');
}

// Get emotion data
$stmt = $conn->prepare("
    SELECT e.*, u.name as student_name, cs.start_time, cs.end_time
    FROM emotions e
    JOIN users u ON e.user_id = u.id
    JOIN class_sessions cs ON e.class_session_id = cs.id
    WHERE cs.class_id = ?
    AND e.timestamp BETWEEN ? AND ?
    ORDER BY e.timestamp DESC
");
$stmt->execute([$class_id, $start_date . ' 00:00:00', $end_date . ' 23:59:59']);
$emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Count emotions by type
$emotion_counts = [
    'Senang' => 0,
    'Stres' => 0,
    'Lelah' => 0,
    'Netral' => 0
];

foreach ($emotions as $emotion) {
    if (isset($emotion_counts[$emotion['emotion']])) {
        $emotion_counts[$emotion['emotion']]++;
    }
}

// Set the content type to HTML
header('Content-Type: text/html; charset=utf-8');

// Generate HTML report instead
$filename = 'Laporan_Emosi_' . preg_replace('/[^A-Za-z0-9]/', '_', $class['class_name']) . '_' . date('Y-m-d') . '.html';

// Calculate total emotions
$total_emotions = count($emotions);

// Start building HTML content
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Laporan Emosi Kelas: <?php echo htmlspecialchars($class['class_name']); ?></title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 1000px;
            margin: 0 auto;
            padding: 20px;
        }
        h1, h2, h3 {
            color: #4A90E2;
        }
        h1 {
            text-align: center;
            margin-bottom: 5px;
        }
        .period {
            text-align: center;
            margin-bottom: 30px;
            color: #666;
        }
        .summary-box {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 5px;
            padding: 20px;
            margin-bottom: 30px;
        }
        .summary-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 10px;
        }
        .summary-item {
            display: flex;
            align-items: center;
        }
        .summary-item span:first-child {
            font-weight: bold;
            width: 80px;
        }
        .total {
            margin-top: 15px;
            font-weight: bold;
            font-size: 1.1em;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #dee2e6;
            padding: 10px;
            text-align: left;
        }
        th {
            background-color: #4A90E2;
            color: white;
        }
        tr:nth-child(even) {
            background-color: #f8f9fa;
        }
        .print-button {
            display: flex;
            justify-content: center;
            gap: 15px;
            margin: 20px 0;
        }
        .print-button button {
            background-color: #4A90E2;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
        }
        .print-button button:hover {
            background-color: #357ABD;
        }
        .back-button {
            background-color: #6c757d;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        .back-button:hover {
            background-color: #5a6268;
            color: white;
            text-decoration: none;
        }
        @media print {
            .print-button {
                display: none;
            }
            body {
                padding: 0;
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="print-button">
        <button onclick="window.print()">Cetak Laporan</button>
        <a href="laporan.php?class_id=<?php echo $class_id; ?>&start_date=<?php echo $start_date; ?>&end_date=<?php echo $end_date; ?>" class="back-button">Kembali ke Halaman Laporan</a>
    </div>

    <h1>Laporan Emosi Kelas: <?php echo htmlspecialchars($class['class_name']); ?></h1>
    <p class="period">Periode: <?php echo date('d/m/Y', strtotime($start_date)); ?> - <?php echo date('d/m/Y', strtotime($end_date)); ?></p>
    
    <h2>Ringkasan Emosi</h2>
    <div class="summary-box">
        <div class="summary-grid">
            <div class="summary-item">
                <span>Senang:</span>
                <span><?php echo $emotion_counts['Senang']; ?></span>
            </div>
            <div class="summary-item">
                <span>Netral:</span>
                <span><?php echo $emotion_counts['Netral']; ?></span>
            </div>
            <div class="summary-item">
                <span>Stres:</span>
                <span><?php echo $emotion_counts['Stres']; ?></span>
            </div>
            <div class="summary-item">
                <span>Lelah:</span>
                <span><?php echo $emotion_counts['Lelah']; ?></span>
            </div>
        </div>
        <div class="total">Total Emosi Tercatat: <?php echo $total_emotions; ?></div>
    </div>
    
    <h2>Detail Emosi</h2>
    <table>
        <thead>
            <tr>
                <th>Mahasiswa</th>
                <th>Emosi</th>
                <th>Waktu</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($emotions as $emotion): ?>
            <tr>
                <td><?php echo htmlspecialchars($emotion['student_name']); ?></td>
                <td><?php echo htmlspecialchars($emotion['emotion']); ?></td>
                <td><?php echo date('d/m/Y H:i', strtotime($emotion['timestamp'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <script>
        // Auto-print when page loads
        window.onload = function() {
            // Uncomment the line below if you want the report to automatically open the print dialog
            // window.print();
        };
    </script>
</body>
</html>
