<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

$dosen_id = $_SESSION['user_id'];

// Get dosen info
$stmt = $conn->prepare("SELECT name FROM users WHERE id = ? AND role = 'Dosen'");
$stmt->execute([$_SESSION['user_id']]);
$dosen = $stmt->fetch(PDO::FETCH_ASSOC);

// Get classes for this dosen
$stmt = $conn->prepare("SELECT id, class_name FROM classes WHERE dosen_id = ? ORDER BY class_name");
$stmt->execute([$dosen_id]);
$classes = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Get selected class and session
$class_id = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$session_id = isset($_GET['session_id']) ? (int)$_GET['session_id'] : 0;

// Get sessions for the selected class
$sessions = [];
if ($class_id > 0) {
    $stmt = $conn->prepare("
        SELECT id, DATE_FORMAT(start_time, '%d/%m/%Y %H:%i') as formatted_start_time, 
               DATE_FORMAT(end_time, '%d/%m/%Y %H:%i') as formatted_end_time,
               status
        FROM class_sessions 
        WHERE class_id = ? 
        ORDER BY start_time DESC
    ");
    $stmt->execute([$class_id]);
    $sessions = $stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Get emotions data for the selected session
$emotions = [];
$studentsAtRisk = [];
$class_name = '';
$session_info = [];
$emotion_summary = [];
$student_emotion_summary = [];

if ($session_id > 0) {
    // Get class name
    $stmt = $conn->prepare("SELECT class_name FROM classes WHERE id = ?");
    $stmt->execute([$class_id]);
    $class_name = $stmt->fetchColumn();
    
    // Get session info
    $stmt = $conn->prepare("
        SELECT 
            DATE_FORMAT(start_time, '%d/%m/%Y %H:%i') as formatted_start_time,
            DATE_FORMAT(end_time, '%d/%m/%Y %H:%i') as formatted_end_time,
            status
        FROM class_sessions 
        WHERE id = ?
    ");
    $stmt->execute([$session_id]);
    $session_info = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // Get emotions for this session
    $stmt = $conn->prepare("
        SELECT e.*, u.name as student_name, u.id as student_id
        FROM emotions e 
        JOIN users u ON e.user_id = u.id 
        WHERE e.class_session_id = ? 
        ORDER BY e.timestamp ASC
    ");
    $stmt->execute([$session_id]);
    $emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get emotion summary for pie chart
    $stmt = $conn->prepare("
        SELECT 
            e.emotion,
            COUNT(*) as count
        FROM emotions e
        WHERE e.class_session_id = ?
        GROUP BY e.emotion
        ORDER BY count DESC
    ");
    $stmt->execute([$session_id]);
    $emotion_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Get student emotion summary for bar chart
    $stmt = $conn->prepare("
        SELECT 
            u.name as student_name,
            COUNT(CASE WHEN e.emotion = 'Senang' THEN 1 END) as happy_count,
            COUNT(CASE WHEN e.emotion = 'Netral' THEN 1 END) as neutral_count,
            COUNT(CASE WHEN e.emotion = 'Lelah' THEN 1 END) as tired_count,
            COUNT(CASE WHEN e.emotion = 'Stres' THEN 1 END) as stress_count,
            COUNT(*) as total_emotions
        FROM users u
        JOIN emotions e ON u.id = e.user_id
        WHERE e.class_session_id = ?
        AND u.role = 'Mahasiswa'
        GROUP BY u.id, u.name
        ORDER BY u.name
    ");
    $stmt->execute([$session_id]);
    $student_emotion_summary = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Check for students with negative emotions in this session
    $stmt = $conn->prepare("
        SELECT 
            u.id as student_id,
            u.name as student_name,
            COUNT(CASE WHEN e.emotion = 'Stres' THEN 1 END) as stress_count,
            COUNT(CASE WHEN e.emotion = 'Lelah' THEN 1 END) as tired_count,
            COUNT(*) as total_emotions
        FROM users u
        JOIN emotions e ON u.id = e.user_id
        WHERE e.class_session_id = ?
        AND u.role = 'Mahasiswa'
        GROUP BY u.id
        HAVING (stress_count + tired_count) / total_emotions >= 0.6
    ");
    $stmt->execute([$session_id]);
    $studentsAtRisk = $stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-date-fns"></script>
    <style>
        .filter-card {
            margin-bottom: 25px;
        }
        .chart-container {
            position: relative;
            height: 60vh;
            width: 100%;
        }
        .session-info {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }
        .session-info h3 {
            margin-top: 0;
            color: #4A90E2;
        }
        .session-info p {
            margin-bottom: 5px;
        }
        .alert-warning {
            border-left: 4px solid #ffc107;
        }
        .student-risk-level {
            display: inline-block;
            padding: 3px 8px;
            border-radius: 4px;
            font-size: 0.85em;
            margin-left: 8px;
            background-color: #ffc107;
            color: #212529;
        }
        
        /* Mobile styles */
        .mobile-navbar {
            background-color: #4A90E2;
            padding: 10px 15px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            z-index: 1020;
            display: flex;
        }
        
        .overlay {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(0, 0, 0, 0.5);
            z-index: 1025;
            display: none;
        }
        
        .overlay.active {
            display: block;
        }
        
        /* Mobile sidebar */
        @media (max-width: 991.98px) {
            .sidebar {
                transform: translateX(-100%);
            }
            
            .sidebar.active {
                transform: translateX(0);
            }
            
            .content-wrapper {
                margin-left: 0;
                padding-top: 70px;
            }
            
            .mobile-navbar {
                display: flex;
            }
        }
        
        /* Desktop view */
        @media (min-width: 992px) {
            .content-wrapper {
                margin-left: 250px;
            }
            
            .sidebar {
                transform: translateX(0);
            }
            
            .mobile-navbar {
                display: none;
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

    <div class="content-wrapper">
        <h1 class="page-title">Grafik Emosi Mahasiswa</h1>
        
        <div class="card filter-card shadow-sm">
            <div class="card-header bg-primary text-white">Pilih Kelas dan Sesi</div>
            <div class="card-body">
                <form method="GET" action="" class="row g-3">
                    <div class="col-md-5">
                        <label for="class_id" class="form-label">Pilih Kelas</label>
                        <select name="class_id" id="class_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">Pilih Kelas...</option>
                            <?php foreach ($classes as $class): ?>
                            <option value="<?php echo $class['id']; ?>" <?php echo ($class_id == $class['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($class['class_name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <?php if ($class_id > 0): ?>
                    <div class="col-md-7">
                        <label for="session_id" class="form-label">Pilih Sesi</label>
                        <select name="session_id" id="session_id" class="form-select" required onchange="this.form.submit()">
                            <option value="">Pilih Sesi...</option>
                            <?php foreach ($sessions as $session): ?>
                            <option value="<?php echo $session['id']; ?>" <?php echo ($session_id == $session['id']) ? 'selected' : ''; ?>>
                                <?php echo $session['formatted_start_time']; ?>
                                <?php if ($session['formatted_end_time']): ?> - <?php echo $session['formatted_end_time']; ?><?php endif; ?>
                                (<?php echo $session['status'] === 'active' ? 'Aktif' : 'Selesai'; ?>)
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <?php endif; ?>
                </form>
            </div>
        </div>
        
        <?php if ($session_id > 0): ?>
            <div class="session-info">
                <h3><?php echo htmlspecialchars($class_name); ?></h3>
                <p><strong>Waktu Mulai:</strong> <?php echo $session_info['formatted_start_time']; ?></p>
                <?php if ($session_info['formatted_end_time']): ?>
                <p><strong>Waktu Selesai:</strong> <?php echo $session_info['formatted_end_time']; ?></p>
                <?php endif; ?>
                <p><strong>Status:</strong> <?php echo $session_info['status'] === 'active' ? 'Aktif' : 'Selesai'; ?></p>
            </div>
            
            <?php if (!empty($studentsAtRisk)): ?>
            <div class="alert alert-warning mt-4">
                <h4><i class="bi bi-exclamation-triangle-fill me-2"></i>Peringatan!</h4>
                <p>Mahasiswa yang memerlukan perhatian (60%+ emosi negatif):</p>
                <ul>
                    <?php foreach ($studentsAtRisk as $student): ?>
                    <li>
                        <?php echo htmlspecialchars($student['student_name']); ?>
                        <span class="student-risk-level">
                            <?php 
                            $negativePercentage = round(($student['stress_count'] + $student['tired_count']) / $student['total_emotions'] * 100);
                            echo $negativePercentage . '% negatif';
                            ?>
                        </span>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($emotions)): ?>
            <!-- Chart Type Selector -->
            <div class="card mt-4">
                <div class="card-header bg-primary text-white">Pilih Jenis Grafik</div>
                <div class="card-body">
                    <div class="btn-group w-100" role="group" aria-label="Chart type selector">
                        <button type="button" class="btn btn-primary active" id="lineChartBtn">Grafik Garis</button>
                        <button type="button" class="btn btn-primary" id="pieChartBtn">Grafik Lingkaran</button>
                        <button type="button" class="btn btn-primary" id="barChartBtn">Grafik Batang</button>
                    </div>
                </div>
            </div>
            
            <!-- Line Chart -->
            <div class="card mt-4 shadow-sm" id="lineChartCard">
                <div class="card-header bg-primary text-white">Grafik Garis Emosi</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="emotionLineChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Pie Chart -->
            <div class="card mt-4 shadow-sm" id="pieChartCard" style="display: none;">
                <div class="card-header bg-primary text-white">Distribusi Emosi (Grafik Lingkaran)</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="emotionPieChart"></canvas>
                    </div>
                </div>
            </div>
            
            <!-- Bar Chart -->
            <div class="card mt-4 shadow-sm" id="barChartCard" style="display: none;">
                <div class="card-header bg-primary text-white">Emosi per Mahasiswa (Grafik Batang)</div>
                <div class="card-body">
                    <div class="chart-container">
                        <canvas id="emotionBarChart"></canvas>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle-fill me-2"></i>
                Tidak ada data emosi untuk sesi kelas ini.
            </div>
            <?php endif; ?>
        <?php endif; ?>
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
    
    <?php if (!empty($emotions)): ?>
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Mobile sidebar toggle functionality
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        
        if (sidebarToggle && sidebar && overlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
            });
        }
        
        // Chart switching functionality
        const lineChartBtn = document.getElementById('lineChartBtn');
        const pieChartBtn = document.getElementById('pieChartBtn');
        const barChartBtn = document.getElementById('barChartBtn');
        
        const lineChartCard = document.getElementById('lineChartCard');
        const pieChartCard = document.getElementById('pieChartCard');
        const barChartCard = document.getElementById('barChartCard');
        
        lineChartBtn.addEventListener('click', function() {
            lineChartCard.style.display = 'block';
            pieChartCard.style.display = 'none';
            barChartCard.style.display = 'none';
            
            lineChartBtn.classList.add('active');
            pieChartBtn.classList.remove('active');
            barChartBtn.classList.remove('active');
        });
        
        pieChartBtn.addEventListener('click', function() {
            lineChartCard.style.display = 'none';
            pieChartCard.style.display = 'block';
            barChartCard.style.display = 'none';
            
            lineChartBtn.classList.remove('active');
            pieChartBtn.classList.add('active');
            barChartBtn.classList.remove('active');
        });
        
        barChartBtn.addEventListener('click', function() {
            lineChartCard.style.display = 'none';
            pieChartCard.style.display = 'none';
            barChartCard.style.display = 'block';
            
            lineChartBtn.classList.remove('active');
            pieChartBtn.classList.remove('active');
            barChartBtn.classList.add('active');
        });
        
        // Get data from PHP
        const emotionData = <?php echo json_encode($emotions); ?>;
        const emotionSummary = <?php echo json_encode($emotion_summary); ?>;
        const studentEmotionSummary = <?php echo json_encode($student_emotion_summary); ?>;
        
        // Colors for emotions
        const emotionColors = {
            'Senang': 'rgba(46, 204, 113, 0.8)',
            'Netral': 'rgba(52, 152, 219, 0.8)',
            'Lelah': 'rgba(243, 156, 18, 0.8)',
            'Stres': 'rgba(231, 76, 60, 0.8)'
        };
        
        // 1. LINE CHART - Time-based emotion tracking
        const lineCtx = document.getElementById('emotionLineChart').getContext('2d');
        
        // Group data by student
        const studentData = {};
        emotionData.forEach(item => {
            if (!studentData[item.student_name]) {
                studentData[item.student_name] = {
                    name: item.student_name,
                    data: []
                };
            }
            studentData[item.student_name].data.push({
                timestamp: item.timestamp,
                emotion: item.emotion
            });
        });
        
        // Create datasets for each student
        const lineDatasets = Object.values(studentData).map((student, index) => ({
            label: student.name,
            data: student.data.map(item => ({
                x: new Date(item.timestamp),
                y: item.emotion === 'Senang' ? 4 :
                   item.emotion === 'Netral' ? 3 :
                   item.emotion === 'Lelah' ? 2 : 1
            })),
            borderColor: `hsl(${index * 360 / Object.keys(studentData).length}, 70%, 50%)`,
            backgroundColor: `hsla(${index * 360 / Object.keys(studentData).length}, 70%, 50%, 0.1)`,
            tension: 0.2,
            pointRadius: 5,
            pointHoverRadius: 8
        }));
        
        new Chart(lineCtx, {
            type: 'line',
            data: {
                datasets: lineDatasets
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Emosi Mahasiswa Selama Sesi Kelas',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.parsed.y;
                                let emotion = '';
                                switch(value) {
                                    case 4: emotion = 'Senang'; break;
                                    case 3: emotion = 'Netral'; break;
                                    case 2: emotion = 'Lelah'; break;
                                    case 1: emotion = 'Stres'; break;
                                }
                                return `${label}: ${emotion}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        type: 'time',
                        time: {
                            unit: 'minute',
                            displayFormats: {
                                minute: 'HH:mm'
                            }
                        },
                        title: {
                            display: true,
                            text: 'Waktu'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 4,
                        ticks: {
                            stepSize: 1,
                            callback: function(value) {
                                switch(value) {
                                    case 4: return 'Senang';
                                    case 3: return 'Netral';
                                    case 2: return 'Lelah';
                                    case 1: return 'Stres';
                                    default: return '';
                                }
                            }
                        },
                        title: {
                            display: true,
                            text: 'Emosi'
                        }
                    }
                }
            }
        });
        
        // 2. PIE CHART - Overall emotion distribution
        const pieCtx = document.getElementById('emotionPieChart').getContext('2d');
        
        new Chart(pieCtx, {
            type: 'pie',
            data: {
                labels: emotionSummary.map(item => item.emotion),
                datasets: [{
                    data: emotionSummary.map(item => item.count),
                    backgroundColor: emotionSummary.map(item => emotionColors[item.emotion]),
                    borderWidth: 1
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'right',
                    },
                    title: {
                        display: true,
                        text: 'Distribusi Emosi Seluruh Mahasiswa',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.label || '';
                                const value = context.raw;
                                const total = context.dataset.data.reduce((a, b) => a + b, 0);
                                const percentage = Math.round((value / total) * 100);
                                return `${label}: ${value} (${percentage}%)`;
                            }
                        }
                    }
                }
            }
        });
        
        // 3. BAR CHART - Emotion breakdown by student
        const barCtx = document.getElementById('emotionBarChart').getContext('2d');
        
        new Chart(barCtx, {
            type: 'bar',
            data: {
                labels: studentEmotionSummary.map(item => item.student_name),
                datasets: [
                    {
                        label: 'Senang',
                        data: studentEmotionSummary.map(item => item.happy_count),
                        backgroundColor: emotionColors['Senang'],
                        borderColor: 'rgba(46, 204, 113, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Netral',
                        data: studentEmotionSummary.map(item => item.neutral_count),
                        backgroundColor: emotionColors['Netral'],
                        borderColor: 'rgba(52, 152, 219, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Lelah',
                        data: studentEmotionSummary.map(item => item.tired_count),
                        backgroundColor: emotionColors['Lelah'],
                        borderColor: 'rgba(243, 156, 18, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Stres',
                        data: studentEmotionSummary.map(item => item.stress_count),
                        backgroundColor: emotionColors['Stres'],
                        borderColor: 'rgba(231, 76, 60, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    title: {
                        display: true,
                        text: 'Distribusi Emosi per Mahasiswa',
                        font: {
                            size: 16
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(context) {
                                const label = context.dataset.label || '';
                                const value = context.raw;
                                return `${label}: ${value}`;
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Mahasiswa'
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
    });
    </script>
    <?php endif; ?>
    
    <script>
    // Sidebar toggle functionality
    document.addEventListener('DOMContentLoaded', function() {
        const sidebarToggle = document.getElementById('sidebarToggle');
        const sidebar = document.getElementById('sidebar');
        const overlay = document.getElementById('overlay');
        const contentWrapper = document.querySelector('.content-wrapper');
        
        if (sidebarToggle && sidebar && overlay) {
            sidebarToggle.addEventListener('click', function() {
                sidebar.classList.toggle('active');
                overlay.classList.toggle('active');
                
                // Adjust content margin when sidebar is toggled
                if (window.innerWidth > 768) {
                    if (sidebar.classList.contains('active')) {
                        contentWrapper.style.marginLeft = '0';
                    } else {
                        contentWrapper.style.marginLeft = '250px';
                    }
                }
            });
            
            overlay.addEventListener('click', function() {
                sidebar.classList.remove('active');
                overlay.classList.remove('active');
                
                // Restore content margin when sidebar is closed on mobile
                if (window.innerWidth > 768) {
                    contentWrapper.style.marginLeft = '250px';
                }
            });
        }
        
        // Responsive content adjustment on window resize
        window.addEventListener('resize', function() {
            if (contentWrapper) {
                if (window.innerWidth <= 768) {
                    contentWrapper.style.marginLeft = '0';
                } else {
                    if (!sidebar || !sidebar.classList.contains('active')) {
                        contentWrapper.style.marginLeft = '250px';
                    }
                }
            }
        });
        
        // Initialize chart button styling
        const chartButtons = document.querySelectorAll('.chart-type-selector .btn');
        chartButtons.forEach(button => {
            button.addEventListener('click', function() {
                chartButtons.forEach(btn => btn.classList.remove('active'));
                this.classList.add('active');
            });
        });
        
        // Check for emotion alerts every 60 seconds
        setInterval(function() {
            checkEmotionAlerts();
        }, 60000);
        
        // Initial check after 5 seconds
        setTimeout(function() {
            checkEmotionAlerts();
        }, 5000);
    });
    
    // Function to check for new emotion alerts
    function checkEmotionAlerts() {
        // This is a placeholder for the actual emotion alert checking functionality
        console.log('Checking for emotion alerts...');
    }
    </script>
</body>
</html>
