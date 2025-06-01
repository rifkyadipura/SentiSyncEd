<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <link rel="stylesheet" href="../css/styles_mahasiswa.css">
    <style>
    /* Additional styles specific to dosen pages */
    body {
        min-height: 100vh;
        position: relative;
        padding-bottom: 60px; /* Height of the footer */
    }
    
    .footer {
        position: absolute;
        bottom: 0;
        width: 100%;
        height: 60px;
        background-color: #f8f9fa;
        border-top: 1px solid #dee2e6;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1000;
    }
    
    /* Emotion Alert System Styles */
    .emotion-alert-badge {
        position: relative;
        display: inline-block;
    }
    
    .emotion-alert-badge .badge {
        position: absolute;
        top: -8px;
        right: -8px;
        border-radius: 50%;
        background-color: #dc3545;
        color: white;
        font-size: 10px;
        padding: 3px 6px;
        animation: pulse 1.5s infinite;
    }
    
    .emotion-alert-item {
        border-left: 4px solid #dc3545;
        margin-bottom: 10px;
        padding: 10px;
        background-color: #fff;
        border-radius: 5px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .emotion-alert-item:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .emotion-alert-item.new {
        animation: highlight 5s ease-out;
    }
    
    .emotion-alert-item.severity-high {
        border-left-color: #dc3545;
    }
    
    .emotion-alert-item.severity-medium {
        border-left-color: #fd7e14;
    }
    
    .emotion-alert-item.severity-low {
        border-left-color: #ffc107;
    }
    
    .emotion-alert-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 5px;
    }
    
    .emotion-alert-title {
        font-weight: 600;
        margin: 0;
    }
    
    .emotion-alert-badge-inline {
        padding: 2px 8px;
        border-radius: 20px;
        font-size: 12px;
        font-weight: 600;
    }
        .severity-high .emotion-alert-badge-inline {
            background-color: #dc3545;
            color: white;
        }
        .severity-medium .emotion-alert-badge-inline {
            background-color: #fd7e14;
            color: white;
        }
        .severity-low .emotion-alert-badge-inline {
            background-color: #ffc107;
            color: #212529;
        }
        .emotion-alert-time {
            font-size: 12px;
            color: #6c757d;
        }
        .emotion-alert-details {
            margin-top: 5px;
            font-size: 14px;
        }
        .emotion-alert-students {
            font-style: italic;
            margin-top: 5px;
            font-size: 13px;
        }
        #emotionAlertSound {
            display: none;
        }
    </style>
    
    <!-- Emotion Alert System Audio -->
    <audio id="emotionAlertSound" preload="auto">
        <source src="../assets/notification.mp3" type="audio/mpeg">
    </audio>
    
    <!-- Emotion Alert System JavaScript -->
    <script>
        // Variables to track alerts
        let lastAlertCount = 0;
        let lastAlertIds = [];
        
        // Function to check for emotion alerts
        function checkEmotionAlerts() {
            $.ajax({
                url: '../check_emotion_alerts.php',
                type: 'GET',
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        updateAlertBadge(response.count);
                        
                        // Check if there are new alerts
                        const currentAlertIds = response.alerts.map(alert => alert.class_session_id);
                        const hasNewAlerts = response.count > lastAlertCount || 
                                           !currentAlertIds.every(id => lastAlertIds.includes(id));
                        
                        // Update alerts container if visible
                        if ($('#emotionAlertsContainer').is(':visible')) {
                            updateAlertsContainer(response.alerts, hasNewAlerts);
                        }
                        
                        // Play sound and show browser notification for new alerts
                        if (hasNewAlerts && response.count > 0) {
                            playAlertSound();
                            showBrowserNotification(response.alerts[0]);
                        }
                        
                        // Update tracking variables
                        lastAlertCount = response.count;
                        lastAlertIds = currentAlertIds;
                    }
                },
                error: function(xhr, status, error) {
                    console.error('Error checking emotion alerts:', error);
                }
            });
        }
        
        // Function to update alert badge
        function updateAlertBadge(count) {
            const badge = $('#emotionAlertBadge');
            if (count > 0) {
                badge.text(count).show();
            } else {
                badge.hide();
            }
        }
        
        // Function to update alerts container
        function updateAlertsContainer(alerts, hasNewAlerts) {
            const container = $('#emotionAlertsContainer');
            
            if (alerts.length === 0) {
                container.html('<p class="text-center text-muted my-4">Tidak ada peringatan emosi saat ini.</p>');
                return;
            }
            
            let html = '';
            
            alerts.forEach(alert => {
                const timestamp = new Date(alert.latest_timestamp);
                const formattedTime = timestamp.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                const formattedDate = timestamp.toLocaleDateString('id-ID', { day: '2-digit', month: '2-digit', year: 'numeric' });
                
                html += `
                    <div class="emotion-alert-item severity-${alert.severity} ${hasNewAlerts ? 'new' : ''}">
                        <div class="emotion-alert-header">
                            <h6 class="emotion-alert-title">${alert.class_name}</h6>
                            <span class="emotion-alert-badge-inline">${alert.severity_text}</span>
                        </div>
                        <div class="emotion-alert-time">${formattedDate} ${formattedTime}</div>
                        <div class="emotion-alert-details">
                            <strong>${alert.negative_percentage}%</strong> emosi negatif (${alert.negative_count} dari ${alert.total_count})
                        </div>
                        <div class="emotion-alert-students">
                            Mahasiswa: ${alert.affected_students}
                        </div>
                    </div>
                `;
            });
            
            container.html(html);
        }
        
        // Function to play alert sound
        function playAlertSound() {
            const sound = document.getElementById('emotionAlertSound');
            if (sound) {
                sound.play().catch(e => console.log('Error playing sound:', e));
            }
        }
        
        // Function to show browser notification
        function showBrowserNotification(alert) {
            // Check if browser notifications are supported and permitted
            if (!('Notification' in window)) {
                console.log('Browser does not support notifications');
                return;
            }
            
            if (Notification.permission === 'granted') {
                createNotification(alert);
            } else if (Notification.permission !== 'denied') {
                Notification.requestPermission().then(permission => {
                    if (permission === 'granted') {
                        createNotification(alert);
                    }
                });
            }
        }
        
        // Function to create notification
        function createNotification(alert) {
            const title = 'Peringatan Emosi SentiSyncEd';
            const options = {
                body: `${alert.negative_percentage}% emosi negatif terdeteksi di kelas ${alert.class_name}`,
                icon: '../assets/notification-icon.png'
            };
            
            const notification = new Notification(title, options);
            
            // Close notification after 5 seconds
            setTimeout(() => notification.close(), 5000);
            
            // Handle click on notification
            notification.onclick = function() {
                window.focus();
                $('#emotionAlertsDropdown').dropdown('show');
            };
        }
        
        // Initialize emotion alert system when document is ready
        $(document).ready(function() {
            // Request permission for browser notifications
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
            
            // Check for alerts immediately
            checkEmotionAlerts();
            
            // Set up periodic checks (every 30 seconds)
            setInterval(checkEmotionAlerts, 30000);
            
            // Toggle alerts dropdown
            $(document).on('click', '#emotionAlertsToggle', function(e) {
                e.preventDefault();
                const container = $('#emotionAlertsContainer');
                
                if (container.is(':visible')) {
                    container.hide();
                } else {
                    // Update alerts when opening dropdown
                    $.ajax({
                        url: '../check_emotion_alerts.php',
                        type: 'GET',
                        dataType: 'json',
                        success: function(response) {
                            if (response.status === 'success') {
                                updateAlertsContainer(response.alerts, false);
                                container.show();
                            }
                        }
                    });
                }
            });
            
            // Close dropdown when clicking outside
            $(document).on('click', function(e) {
                if (!$(e.target).closest('#emotionAlertsDropdown').length) {
                    $('#emotionAlertsContainer').hide();
                }
            });
        });
    </script>