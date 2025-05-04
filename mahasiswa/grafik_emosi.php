<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

// Get user's emotions
$emotions = getUserEmotions($_SESSION['user_id']);

// Get emotion counts for pie chart
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
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Emosi - SentiSyncEd</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../css/style.css">
    <link rel="stylesheet" href="../css/footer.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
        .main-content {
            flex: 1;
            padding: 2rem;
            background: #f9f9f9;
        }
        .chart-container {
            background: white;
            padding: 2rem;
            border-radius: 15px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
            margin-bottom: 2rem;
        }
        .chart-container h2 {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .charts-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 2rem;
        }
        @media (min-width: 1200px) {
            .charts-grid {
                grid-template-columns: 1fr 1fr;
            }
        }
        .chart-wrapper {
            position: relative;
            height: 400px;
            margin-bottom: 1rem;
        }
        .no-data {
            text-align: center;
            color: #666;
            padding: 2rem;
            background: #f8f9fa;
            border-radius: 8px;
            margin-top: 1rem;
        }
        .chart-legend {
            display: flex;
            justify-content: center;
            gap: 1rem;
            flex-wrap: wrap;
            margin-top: 1rem;
        }
        .legend-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: #f8f9fa;
            border-radius: 20px;
            font-size: 0.9rem;
            color: #666;
        }
        .legend-color {
            width: 12px;
            height: 12px;
            border-radius: 50%;
        }
    </style>
</head>
<body>
    <div class="dashboard">
        <div class="sidebar">
            <h3>
                <i class="fas fa-graduation-cap"></i>
                Menu Mahasiswa
            </h3>
            <ul class="sidebar-menu">
                <li>
                    <a href="dashboard_mahasiswa.php">
                        <i class="fas fa-home"></i>
                        Dashboard
                    </a>
                </li>
                <li>
                    <a href="input_emosi.php">
                        <i class="fas fa-smile"></i>
                        Input Emosi
                    </a>
                </li>
                <li>
                    <a href="tulis_curhat.php">
                        <i class="fas fa-comment-dots"></i>
                        Tulis Curhat
                    </a>
                </li>
                <li>
                    <a href="grafik_emosi.php" class="active">
                        <i class="fas fa-chart-line"></i>
                        Grafik Emosi
                    </a>
                </li>
                <li>
                    <a href="pilih_kelas.php">
                        <i class="fas fa-chalkboard"></i>
                        Pilih Kelas
                    </a>
                </li>
                <li>
                    <a href="kelas_saya.php">
                        <i class="fas fa-book"></i>
                        Kelas Saya
                    </a>
                </li>
                <li>
                    <a href="../login.php?logout=1">
                        <i class="fas fa-sign-out-alt"></i>
                        Logout
                    </a>
                </li>
            </ul>
        </div>
        
        <div class="main-content">
            <?php if (empty($emotions)): ?>
                <div class="no-data">
                    <i class="fas fa-info-circle"></i>
                    Belum ada data emosi. Silakan input emosi terlebih dahulu.
                </div>
            <?php else: ?>
                <div class="charts-grid">
                    <div class="chart-container">
                        <h2>
                            <i class="fas fa-chart-line"></i>
                            Tren Emosi
                        </h2>
                        <div class="chart-wrapper">
                            <canvas id="emotionTrendChart"></canvas>
                        </div>
                    </div>

                    <div class="chart-container">
                        <h2>
                            <i class="fas fa-chart-pie"></i>
                            Distribusi Emosi
                        </h2>
                        <div class="chart-wrapper">
                            <canvas id="emotionPieChart"></canvas>
                        </div>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>

    <script>
    <?php if (!empty($emotions)): ?>
        // Prepare data for line chart
        const emotionData = <?php echo json_encode($emotions); ?>;
        const emotionCounts = <?php echo json_encode($emotion_counts); ?>;

        // Line Chart
        new Chart(document.getElementById('emotionTrendChart'), {
            type: 'line',
            data: {
                labels: emotionData.map(item => new Date(item.timestamp).toLocaleDateString()),
                datasets: [{
                    label: 'Emosi',
                    data: emotionData.map(item => {
                        switch(item.emotion) {
                            case 'Senang': return 4;
                            case 'Netral': return 3;
                            case 'Lelah': return 2;
                            case 'Stres': return 1;
                            default: return 0;
                        }
                    }),
                    borderColor: '#4A90E2',
                    tension: 0.1,
                    fill: false
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                scales: {
                    y: {
                        beginAtZero: true,
                        max: 4,
                        ticks: {
                            callback: function(value) {
                                switch(value) {
                                    case 4: return 'Senang';
                                    case 3: return 'Netral';
                                    case 2: return 'Lelah';
                                    case 1: return 'Stres';
                                    default: return '';
                                }
                            }
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Pie Chart
        new Chart(document.getElementById('emotionPieChart'), {
            type: 'pie',
            data: {
                labels: Object.keys(emotionCounts),
                datasets: [{
                    data: Object.values(emotionCounts),
                    backgroundColor: [
                        '#4CAF50', // Senang - Green
                        '#f44336', // Stres - Red
                        '#FFA726', // Lelah - Orange
                        '#42A5F5'  // Netral - Blue
                    ]
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom'
                    }
                }
            }
        });
    <?php endif; ?>
    </script>
</body>
</html>
