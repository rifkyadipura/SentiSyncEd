<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Require login and check role
requireLogin();
if (getUserRole() !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get classes the student is enrolled in
$enrolled_classes = getEnrolledClasses($user_id);

// Get selected class ID (0 means all classes)
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get user's emotions based on class selection
$emotions = getUserEmotionsByClass($user_id, $class_id);

// Get emotion counts for pie chart
$emotion_counts = [
    'Senang' => 0,
    'Stres' => 0,
    'Lelah' => 0,
    'Netral' => 0
];

// Get emotion counts by date for line chart
$emotion_by_date = [];

// Get emotion counts by class for bar chart
$emotion_by_class = [];
$class_names = [];

foreach ($emotions as $emotion) {
    // Count emotions for pie chart
    if (isset($emotion_counts[$emotion['emotion']])) {
        $emotion_counts[$emotion['emotion']]++;
    }
    
    // Process data for line chart
    $date = date('Y-m-d', strtotime($emotion['timestamp']));
    if (!isset($emotion_by_date[$date])) {
        $emotion_by_date[$date] = [
            'Senang' => 0,
            'Stres' => 0,
            'Lelah' => 0,
            'Netral' => 0
        ];
    }
    $emotion_by_date[$date][$emotion['emotion']]++;
    
    // Process data for bar chart by class
    $class_name = $emotion['class_name'] ?? 'Tidak ada kelas';
    if (!in_array($class_name, $class_names)) {
        $class_names[] = $class_name;
    }
    
    if (!isset($emotion_by_class[$class_name])) {
        $emotion_by_class[$class_name] = [
            'Senang' => 0,
            'Stres' => 0,
            'Lelah' => 0,
            'Netral' => 0
        ];
    }
    $emotion_by_class[$class_name][$emotion['emotion']]++;
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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
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
        .chart-title {
            color: #4A90E2;
            margin-bottom: 1.5rem;
            font-size: 1.8rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        .chart-type-selector {
            margin-bottom: 20px;
        }
        .chart-type-selector .btn {
            padding: 8px 16px;
            font-weight: 600;
        }
        .class-filter {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .form-select {
            border: 2px solid #e1e1e1;
            padding: 10px;
            border-radius: 8px;
            transition: all 0.3s ease;
        }
        .form-select:focus {
            border-color: #4A90E2;
            box-shadow: 0 0 0 0.25rem rgba(74, 144, 226, 0.25);
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
            <div class="chart-container">
                <h2 class="chart-title">
                    <i class="fas fa-chart-line"></i>
                    Grafik Emosi Saya
                </h2>
                
                <!-- Class Selection Form -->
                <div class="class-filter">
                    <form method="GET" action="" class="row g-3">
                        <div class="col-md-6">
                            <label for="class_id" class="form-label">Pilih Kelas:</label>
                            <select name="class_id" id="class_id" class="form-select" onchange="this.form.submit()">
                                <option value="0" <?php echo ($class_id == 0) ? 'selected' : ''; ?>>Semua Kelas</option>
                                <?php foreach ($enrolled_classes as $class): ?>
                                <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($class['class_name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </form>
                </div>
                
                <?php if (empty($emotions)): ?>
                <div class="no-data">
                    <p>Belum ada data emosi yang direkam. Silakan input emosi terlebih dahulu.</p>
                </div>
                <?php else: ?>
                <!-- Chart Type Selection -->
                <div class="chart-type-selector">
                    <div class="btn-group" role="group" aria-label="Chart type selector">
                        <button type="button" class="btn btn-primary active" id="lineChartBtn">Grafik Garis</button>
                        <button type="button" class="btn btn-primary" id="pieChartBtn">Grafik Lingkaran</button>
                        <button type="button" class="btn btn-primary" id="barChartBtn">Grafik Batang</button>
                    </div>
                </div>
                
                <!-- Charts -->
                <div class="charts-container mt-4">
                    <div class="chart-wrapper" id="lineChartContainer">
                        <canvas id="emotionLineChart"></canvas>
                    </div>
                    <div class="chart-wrapper" id="pieChartContainer" style="display: none;">
                        <canvas id="emotionPieChart"></canvas>
                    </div>
                    <div class="chart-wrapper" id="barChartContainer" style="display: none;">
                        <canvas id="emotionBarChart"></canvas>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <footer class="copyright-footer">
        <span>&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</span>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    <?php if (!empty($emotions)): ?>
        // Prepare data for charts
        const emotionData = <?php echo json_encode($emotions); ?>;
        const emotionCounts = <?php echo json_encode($emotion_counts); ?>;
        const emotionByDate = <?php echo json_encode($emotion_by_date); ?>;
        const emotionByClass = <?php echo json_encode($emotion_by_class); ?>;
        const classNames = <?php echo json_encode($class_names); ?>;
        
        // Color mapping for emotions
        const emotionColors = {
            'Senang': '#4CAF50', // Green
            'Stres': '#f44336',  // Red
            'Lelah': '#FFA726',  // Orange
            'Netral': '#42A5F5'  // Blue
        };
        
        // Chart instances
        let lineChart, pieChart, barChart;
        
        // Initialize charts when DOM is loaded
        document.addEventListener('DOMContentLoaded', function() {
            // Chart switching functionality
            const lineChartBtn = document.getElementById('lineChartBtn');
            const pieChartBtn = document.getElementById('pieChartBtn');
            const barChartBtn = document.getElementById('barChartBtn');
            
            const lineChartContainer = document.getElementById('lineChartContainer');
            const pieChartContainer = document.getElementById('pieChartContainer');
            const barChartContainer = document.getElementById('barChartContainer');
            
            // Button click handlers
            lineChartBtn.addEventListener('click', function() {
                // Show line chart, hide others
                lineChartContainer.style.display = 'block';
                pieChartContainer.style.display = 'none';
                barChartContainer.style.display = 'none';
                
                // Update active button
                lineChartBtn.classList.add('active');
                pieChartBtn.classList.remove('active');
                barChartBtn.classList.remove('active');
                
                // Initialize line chart if not already done
                if (!lineChart) {
                    initLineChart();
                }
            });
            
            pieChartBtn.addEventListener('click', function() {
                // Show pie chart, hide others
                lineChartContainer.style.display = 'none';
                pieChartContainer.style.display = 'block';
                barChartContainer.style.display = 'none';
                
                // Update active button
                lineChartBtn.classList.remove('active');
                pieChartBtn.classList.add('active');
                barChartBtn.classList.remove('active');
                
                // Initialize pie chart if not already done
                if (!pieChart) {
                    initPieChart();
                }
            });
            
            barChartBtn.addEventListener('click', function() {
                // Show bar chart, hide others
                lineChartContainer.style.display = 'none';
                pieChartContainer.style.display = 'none';
                barChartContainer.style.display = 'block';
                
                // Update active button
                lineChartBtn.classList.remove('active');
                pieChartBtn.classList.remove('active');
                barChartBtn.classList.add('active');
                
                // Initialize bar chart if not already done
                if (!barChart) {
                    initBarChart();
                }
            });
            
            // Initialize line chart by default
            initLineChart();
        });
        
        // Initialize Line Chart
        function initLineChart() {
            const ctx = document.getElementById('emotionLineChart').getContext('2d');
            
            // Process data for line chart
            const dates = Object.keys(emotionByDate).sort();
            const datasets = [
                {
                    label: 'Senang',
                    data: dates.map(date => emotionByDate[date]['Senang']),
                    borderColor: emotionColors['Senang'],
                    backgroundColor: emotionColors['Senang'] + '33', // Add transparency
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Stres',
                    data: dates.map(date => emotionByDate[date]['Stres']),
                    borderColor: emotionColors['Stres'],
                    backgroundColor: emotionColors['Stres'] + '33',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Lelah',
                    data: dates.map(date => emotionByDate[date]['Lelah']),
                    borderColor: emotionColors['Lelah'],
                    backgroundColor: emotionColors['Lelah'] + '33',
                    fill: false,
                    tension: 0.1
                },
                {
                    label: 'Netral',
                    data: dates.map(date => emotionByDate[date]['Netral']),
                    borderColor: emotionColors['Netral'],
                    backgroundColor: emotionColors['Netral'] + '33',
                    fill: false,
                    tension: 0.1
                }
            ];
            
            lineChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dates.map(date => {
                        const d = new Date(date);
                        return d.toLocaleDateString('id-ID');
                    }),
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Tren Emosi Berdasarkan Tanggal',
                            font: {
                                size: 16
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Tanggal'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Emosi'
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize Pie Chart
        function initPieChart() {
            const ctx = document.getElementById('emotionPieChart').getContext('2d');
            
            pieChart = new Chart(ctx, {
                type: 'pie',
                data: {
                    labels: Object.keys(emotionCounts),
                    datasets: [{
                        data: Object.values(emotionCounts),
                        backgroundColor: [
                            emotionColors['Senang'],
                            emotionColors['Stres'],
                            emotionColors['Lelah'],
                            emotionColors['Netral']
                        ]
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribusi Emosi Keseluruhan',
                            font: {
                                size: 16
                            }
                        },
                        legend: {
                            position: 'bottom'
                        },
                        tooltip: {
                            callbacks: {
                                label: function(context) {
                                    const label = context.label || '';
                                    const value = context.raw || 0;
                                    const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                    const percentage = Math.round((value / total) * 100);
                                    return `${label}: ${value} (${percentage}%)`;
                                }
                            }
                        }
                    }
                }
            });
        }
        
        // Initialize Bar Chart
        function initBarChart() {
            const ctx = document.getElementById('emotionBarChart').getContext('2d');
            
            // Process data for bar chart
            const datasets = [
                {
                    label: 'Senang',
                    data: classNames.map(className => emotionByClass[className]['Senang']),
                    backgroundColor: emotionColors['Senang'],
                },
                {
                    label: 'Stres',
                    data: classNames.map(className => emotionByClass[className]['Stres']),
                    backgroundColor: emotionColors['Stres'],
                },
                {
                    label: 'Lelah',
                    data: classNames.map(className => emotionByClass[className]['Lelah']),
                    backgroundColor: emotionColors['Lelah'],
                },
                {
                    label: 'Netral',
                    data: classNames.map(className => emotionByClass[className]['Netral']),
                    backgroundColor: emotionColors['Netral'],
                }
            ];
            
            barChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: classNames,
                    datasets: datasets
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        title: {
                            display: true,
                            text: 'Distribusi Emosi Berdasarkan Kelas',
                            font: {
                                size: 16
                            }
                        },
                        tooltip: {
                            mode: 'index',
                            intersect: false
                        }
                    },
                    scales: {
                        x: {
                            title: {
                                display: true,
                                text: 'Kelas'
                            }
                        },
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Jumlah Emosi'
                            },
                            stacked: false
                        }
                    }
                }
            });
        }
    <?php endif; ?>
    </script>
</body>
</html>
