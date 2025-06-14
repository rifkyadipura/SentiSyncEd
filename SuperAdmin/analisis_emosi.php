<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Hanya SuperAdmin
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'SuperAdmin') {
    header('Location: ../login.php');
    exit();
}

// Daftar kelas untuk filter
$classesStmt = $conn->query("SELECT id, class_name FROM classes ORDER BY class_name");
$classes = $classesStmt->fetchAll(PDO::FETCH_ASSOC);

// Filter kelas jika ada
$filterClassId = isset($_GET['class_id']) ? (int)$_GET['class_id'] : 0;
$whereClause   = $filterClassId ? "WHERE cs.class_id = $filterClassId" : "";

// Distribusi emosi
$stmt = $conn->query("SELECT e.emotion, COUNT(*) AS count FROM emotions e JOIN class_sessions cs ON cs.id = e.class_session_id $whereClause GROUP BY e.emotion");
$emotionDistribution = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total dan masing-masing emosi
$totalInput = array_sum(array_column($emotionDistribution, 'count'));
$emotionCounts = ['senang'=>0,'sedih'=>0,'marah'=>0,'takut'=>0,'netral'=>0];
foreach ($emotionDistribution as $row) {
    $emotionKey = strtolower($row['emotion']);
    if (isset($emotionCounts[$emotionKey])) {
        $emotionCounts[$emotionKey] = $row['count'];
    }
}

// Data untuk grafik tren per hari (7 hari terakhir)
$trendStmt = $conn->query("SELECT DATE(s.start_time) AS tgl, COUNT(*) AS cnt FROM emotions e JOIN sessions s ON s.id = e.session_id JOIN class_sessions cs ON cs.id = e.class_session_id $whereClause GROUP BY DATE(s.start_time) ORDER BY tgl DESC LIMIT 7");
$trendRows = $trendStmt->fetchAll(PDO::FETCH_ASSOC);
$trendRows = array_reverse($trendRows); // urut naik

// 20 input terbaru
$recentStmt = $conn->query("SELECT e.emotion, s.start_time AS created_at, u.name AS user_name, c.class_name FROM emotions e JOIN sessions s ON s.id = e.session_id JOIN class_sessions cs ON cs.id = e.class_session_id JOIN classes c ON c.id = cs.class_id JOIN users u ON u.id = e.user_id $whereClause ORDER BY s.start_time DESC LIMIT 20");
$recentEmotions = $recentStmt->fetchAll(PDO::FETCH_ASSOC);

// Siapkan warna default Chart.js
$chartColors = [
    'rgba(78, 115, 223, 0.8)',
    'rgba(28, 200, 138, 0.8)',
    'rgba(54, 185, 204, 0.8)',
    'rgba(246, 194, 62, 0.8)',
    'rgba(231, 74, 59, 0.8)'
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analisis Emosi - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link href="../assets/css/superadmin_common.css" rel="stylesheet">
</head>
<body>
    <!-- Sidebar -->
    <div class="sidebar d-flex flex-column flex-shrink-0 p-3 text-white col-md-3 col-lg-2">
        <div class="sidebar-brand d-flex align-items-center justify-content-center position-relative">
            <i class="bi bi-bar-chart-line me-2"></i>
            <span>SentiSyncEd</span>
            <button class="btn-close-sidebar d-md-none position-absolute end-0 me-3 text-white bg-transparent border-0"><i class="bi bi-x-lg"></i></button>
        </div>
        <hr>
        <ul class="nav nav-pills flex-column mb-auto">
            <li class="nav-item"><a href="dashboard_admin.php" class="nav-link text-white"><i class="bi bi-speedometer2"></i>Dashboard</a></li>
            <li><a href="manage_dosen.php" class="nav-link text-white"><i class="bi bi-person-badge"></i>Kelola Dosen</a></li>
            <li><a href="manage_mahasiswa.php" class="nav-link text-white"><i class="bi bi-mortarboard"></i>Kelola Mahasiswa</a></li>
            <li><a href="manage_kelas.php" class="nav-link text-white"><i class="bi bi-journal-text"></i>Kelola Kelas</a></li>
            <li><a href="analisis_emosi.php" class="nav-link active"><i class="bi bi-emoji-smile"></i>Analisis Emosi</a></li>
        </ul>
        <hr>
        <div class="dropdown">
            <a href="#" class="d-flex align-items-center text-white text-decoration-none dropdown-toggle" id="dropdownUser1" data-bs-toggle="dropdown" aria-expanded="false">
                <div class="avatar me-2 bg-white"><i class="bi bi-person text-primary"></i></div>
                <strong><?php echo htmlspecialchars($_SESSION['name']); ?></strong>
            </a>
            <ul class="dropdown-menu dropdown-menu-dark text-small shadow" aria-labelledby="dropdownUser1">
                <li><a class="dropdown-item" href="#">Profil</a></li>
                <li><a class="dropdown-item" href="#">Pengaturan</a></li>
                <li><hr class="dropdown-divider"></li>
                <li><a class="dropdown-item" href="../login.php?logout=1">Logout</a></li>
            </ul>
        </div>
    </div>

    <!-- Topbar -->
    <div class="topbar d-flex align-items-center justify-content-between">
        <button class="btn btn-link d-md-none rounded-circle me-3" id="sidebarToggle"><i class="bi bi-list"></i></button>
        <div class="d-none d-md-flex"><h4 class="mb-0">Analisis Emosi</h4></div>
        <!-- Brand text only on mobile -->
        <div class="d-flex d-md-none">
            <span class="fw-semibold text-primary">SentiSyncEd</span>
        </div>
    </div>

    <!-- Main -->
    <div class="main-content">
        <div class="content-wrapper">
            <!-- Filter Kelas -->
            <form method="GET" class="mb-3 d-flex align-items-center">
                <label class="me-2 fw-semibold">Kelas:</label>
                <select name="class_id" class="form-select w-auto" onchange="this.form.submit()">
                    <option value="0">Semua Kelas</option>
                    <?php foreach ($classes as $cls): ?>
                        <option value="<?= $cls['id'] ?>" <?= $filterClassId == $cls['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cls['class_name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>

            <!-- Stat Cards -->
            <div class="row mb-4">
                <div class="col-sm-6 col-md-3 mb-3">
                    <div class="stat-card primary p-3 text-center">
                        <div class="small">Total Input</div>
                        <h3 class="mb-0"><?= $totalInput ?></h3>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 mb-3">
                    <div class="stat-card success p-3 text-center">
                        <div class="small">Senang</div>
                        <h3 class="mb-0"><?= $emotionCounts['senang'] ?></h3>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 mb-3">
                    <div class="stat-card info p-3 text-center">
                        <div class="small">Sedih</div>
                        <h3 class="mb-0"><?= $emotionCounts['sedih'] ?></h3>
                    </div>
                </div>
                <div class="col-sm-6 col-md-3 mb-3">
                    <div class="stat-card warning p-3 text-center">
                        <div class="small">Marah</div>
                        <h3 class="mb-0"><?= $emotionCounts['marah'] ?></h3>
                    </div>
                </div>
            </div>

            <!-- Chart Donat & Batang -->
            <div class="row mb-4">
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold">Distribusi Emosi (Donat)</h6></div>
                        <div class="card-body" style="height:300px;"><canvas id="emotionChart"></canvas></div>
                    </div>
                </div>
                <div class="col-md-6 mb-4">
                    <div class="card h-100">
                        <div class="card-header d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold">Distribusi Emosi (Batang)</h6></div>
                        <div class="card-body" style="height:300px;"><canvas id="barChart"></canvas></div>
                    </div>
                </div>
            </div>

            <!-- Grafik Tren -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold">Tren Total Input Emosi (7 Hari Terakhir)</h6></div>
                <div class="card-body" style="height:300px;"><canvas id="lineChart"></canvas></div>
            </div>

            <!-- Tabel Emosi Terbaru -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center"><h6 class="m-0 font-weight-bold">20 Input Terbaru</h6></div>
                <div class="card-body table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead class="table-light">
                            <tr><th>Waktu</th><th>Mahasiswa</th><th>Kelas</th><th>Emosi</th></tr>
                        </thead>
                        <tbody>
                            <?php foreach ($recentEmotions as $log): ?>
                                <tr>
                                    <td><?= date('d-m-Y H:i', strtotime($log['created_at'])) ?></td>
                                    <td><?= htmlspecialchars($log['user_name']) ?></td>
                                    <td><?= htmlspecialchars($log['class_name']) ?></td>
                                    <td><?= htmlspecialchars($log['emotion']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
    <script>
    document.addEventListener('DOMContentLoaded',()=>{
        const sidebar      = document.querySelector('.sidebar');
        const sidebarToggle= document.getElementById('sidebarToggle');
        const closeBtn     = document.querySelector('.btn-close-sidebar');
        if(sidebarToggle){sidebarToggle.addEventListener('click',()=>sidebar.classList.toggle('show'));}
        if(closeBtn){closeBtn.addEventListener('click',()=>sidebar.classList.remove('show'));}
        document.addEventListener('click',e=>{if(window.innerWidth<=768 && sidebar.classList.contains('show') && !sidebar.contains(e.target) && !sidebarToggle.contains(e.target)){sidebar.classList.remove('show');}});

        // Chart
        const emotionData = <?php echo json_encode($emotionDistribution); ?>;
        const labels = emotionData.map(e=>e.emotion);
        const data   = emotionData.map(e=>e.count);
        const colors = <?php echo json_encode($chartColors); ?>;
        // Donut
        new Chart(document.getElementById('emotionChart'),{
            type:'doughnut',
            data:{labels:labels,datasets:[{data:data,backgroundColor:colors}]},
            options:{maintainAspectRatio:false,plugins:{legend:{position:'bottom'}}}
        });

        // Bar
        new Chart(document.getElementById('barChart'),{
            type:'bar',
            data:{labels:labels,datasets:[{data:data,backgroundColor:colors}]},
            options:{plugins:{legend:{display:false}},scales:{y:{beginAtZero:true}}}
        });

        // Line trend
        const trendData = <?php echo json_encode($trendRows); ?>;
        const trendLabels = trendData.map(r=>r.tgl);
        const trendCounts = trendData.map(r=>r.cnt);
        new Chart(document.getElementById('lineChart'),{
            type:'line',
            data:{labels:trendLabels,datasets:[{data:trendCounts,label:'Total Input',fill:false,borderColor:'rgba(78,115,223,1)',backgroundColor:'rgba(78,115,223,0.1)',borderWidth:2,pointRadius:4,pointHoverRadius:6,tension:.3}]},
            options:{
                responsive:true,
                maintainAspectRatio:false,
                plugins:{legend:{display:false},tooltip:{callbacks:{label:(ctx)=>` ${ctx.parsed.y} input`}}},
                scales:{
                    x:{grid:{display:false},ticks:{autoSkip:false,maxRotation:0}},
                    y:{beginAtZero:true,grid:{color:'rgba(0,0,0,0.05)'}}
                }
            }
        });
    });
    </script>
</body>
</html>
