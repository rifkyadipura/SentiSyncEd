<?php
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Mahasiswa
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Mahasiswa') {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get classes the student is enrolled in
try {
    $stmt = $conn->prepare("SELECT c.id, c.class_name FROM classes c JOIN class_members cm ON c.id = cm.class_id WHERE cm.user_id = ? ORDER BY c.class_name");
    $stmt->execute([$_SESSION['user_id']]);
    $enrolled_classes = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $enrolled_classes = [];
}

// Get selected class ID (0 means all classes)
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;

// Get user's emotions based on class selection
$emotions = [];
try {
    if ($class_id > 0) {
        $stmt = $conn->prepare("SELECT e.emotion, e.timestamp, c.class_name FROM emotions e JOIN class_sessions cs ON e.class_session_id = cs.id JOIN classes c ON cs.class_id = c.id WHERE e.user_id = ? AND c.id = ? ORDER BY e.timestamp DESC");
        $stmt->execute([$user_id, $class_id]);
    } else {
        $stmt = $conn->prepare("SELECT e.emotion, e.timestamp, c.class_name FROM emotions e JOIN class_sessions cs ON e.class_session_id = cs.id JOIN classes c ON cs.class_id = c.id WHERE e.user_id = ? ORDER BY e.timestamp DESC");
        $stmt->execute([$user_id]);
    }
    $emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Handle error
}

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
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik Emosi - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&amp;display=swap"
        rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
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
                <span class="d-none d-sm-inline"><?php echo htmlspecialchars($_SESSION['name'] ?? 'Mahasiswa'); ?></span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="mobileProfileDropdown">
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
            <a href="grafik_emosi.php" class="nav-link d-flex align-items-center px-4 py-2 text-white active" style="font-size: 1.1rem;">
                <i class="bi bi-bar-chart-line me-2"></i> Grafik Emosi
            </a>
        </nav>
    </div>

    <!-- Main Content -->
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
    
    <div class="content-wrapper">
        <div class="container-fluid px-0">
            <h1 class="page-title mb-4">Grafik Emosi</h1>

            <!-- Class Selection Form -->
            <div class="card mb-4">
                <div class="card-body">
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
            </div>
            
            <?php if (empty($emotions)): ?>
            <div class="alert alert-info">
                <p class="mb-0">Belum ada data emosi yang direkam. Silakan input emosi terlebih dahulu.</p>
            </div>
            <?php else: ?>
            <!-- Chart Type Selection -->
            <div class="card mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="card-title mb-0 d-flex align-items-center">
                        <i class="bi bi-graph-up me-2 text-primary"></i>
                        Grafik Emosi Saya
                    </h5>
                </div>
                <div class="card-body">
                    <div class="chart-type-selector mb-4">
                        <div class="btn-group" role="group" aria-label="Chart type selector">
                            <button type="button" class="btn btn-primary active" id="lineChartBtn">Grafik Garis</button>
                            <button type="button" class="btn btn-primary" id="pieChartBtn">Grafik Lingkaran</button>
                            <button type="button" class="btn btn-primary" id="barChartBtn">Grafik Batang</button>
                        </div>
                    </div>
                    
                    <!-- Charts -->
                    <div class="charts-container">
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
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Copyright Footer -->
    <footer class="py-3 text-center text-muted border-top" style="position: fixed; bottom: 0; width: 100%; background-color: #f8f9fa; z-index: 1000;">
        <div class="container">
            <p class="mb-0">&copy; <?php echo date('Y'); ?> Rifky Najra Adipura. All rights reserved.</p>
        </div>
    </footer>
    
    <!-- Padding to prevent content from being hidden behind fixed footer -->
    <div style="height: 60px;"></div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            // Toggle sidebar
            $('#sidebarToggle').click(function() {
                $('#sidebar').toggleClass('show');
                $('#overlay').toggleClass('show');
            });

            // Close sidebar when overlay is clicked
            $('#overlay').click(function() {
                $('#sidebar').removeClass('show');
                $('#overlay').removeClass('show');
            });
        });
    </script>
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
