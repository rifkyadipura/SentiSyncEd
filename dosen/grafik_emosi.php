<?php
session_start();
require_once '../koneksi.php';
require_once '../fungsi_helper.php';

// Check if user is logged in and is a Dosen
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Dosen') {
    header('Location: ../login.php');
    exit();
}

// Get all student emotions
$stmt = $conn->query("
    SELECT e.*, u.name as student_name 
    FROM emotions e 
    JOIN users u ON e.user_id = u.id 
    WHERE u.role = 'Mahasiswa'
    ORDER BY e.timestamp DESC
");
$emotions = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Check for students with negative emotions
$stmt = $conn->query("
    SELECT DISTINCT u.name, u.id
    FROM users u
    JOIN emotions e ON u.id = e.user_id
    WHERE u.role = 'Mahasiswa'
    AND e.emotion IN ('Stres', 'Lelah')
    AND e.timestamp >= DATE_SUB(NOW(), INTERVAL 24 HOUR)
    GROUP BY u.id
    HAVING COUNT(*) >= 3
");
$studentsAtRisk = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <?php include 'includes/head.php'; ?>
</head>
<body>
    <div class="sidebar">
        <?php include 'sidebar.php'; ?>
    </div>
    
    <div class="content-wrapper">
        <h1 class="page-title">Grafik Emosi Mahasiswa</h1>
        
        <?php if (!empty($studentsAtRisk)): ?>
        <div class="alert alert-warning mt-4">
            <h4>Peringatan!</h4>
            <p>Mahasiswa yang memerlukan perhatian:</p>
            <ul>
                <?php foreach ($studentsAtRisk as $student): ?>
                <li><?php echo htmlspecialchars($student['name']); ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        
        <div class="card mt-4">
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="emotionChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    const ctx = document.getElementById('emotionChart').getContext('2d');
    const emotionData = <?php echo json_encode($emotions); ?>;
    
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
    const datasets = Object.values(studentData).map((student, index) => ({
        label: student.name,
        data: student.data.map(item => ({
            x: new Date(item.timestamp),
            y: item.emotion === 'Senang' ? 4 :
               item.emotion === 'Netral' ? 3 :
               item.emotion === 'Lelah' ? 2 : 1
        })),
        borderColor: `hsl(${index * 360 / Object.keys(studentData).length}, 70%, 50%)`,
        tension: 0.1
    }));
    
    new Chart(ctx, {
        type: 'line',
        data: {
            datasets: datasets
        },
        options: {
            responsive: true,
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day'
                    }
                },
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
            }
        }
    });
    </script>
</body>
</html>
