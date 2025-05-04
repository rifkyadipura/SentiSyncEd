<meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Dosen - SentiSyncEd</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            background-color: #f8f9fa;
        }
        .sidebar {
            background-color: #4A90E2;
            color: #fff;
            height: 100vh;
            position: fixed;
            width: 250px;
        }
        .sidebar-header {
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
        }
        .sidebar-header h2 {
            margin: 0;
            font-weight: 700;
            font-size: 24px;
            color: #fff;
        }
        .nav-link {
            color: #e3eaf3;
            padding: 12px 20px;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
        }
        .nav-link:hover, .nav-link.active {
            color: #fff;
            background-color: rgba(255,255,255,0.12);
        }
        .nav-link i {
            margin-right: 10px;
            width: 20px;
            text-align: center;
        }
        .content-wrapper {
            margin-left: 250px;
            padding: 30px;
        }
        .card {
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        .card-header {
            background-color: #ffffff;
            border-bottom: none;
            padding: 20px 30px;
            font-weight: 600;
            font-size: 18px;
        }
        .card-body {
            padding: 30px;
        }
        .btn-primary {
            background-color: #3c4b64;
            border: none;
            padding: 12px 24px;
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 6px;
        }
        .btn-primary:hover {
            background-color: #2c3a50;
        }
        .page-title {
            font-weight: 700;
            margin-bottom: 25px;
            color: #343a40;
        }
        .stat-card {
            background-color: #ffffff;
            border: none;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            padding: 25px;
            margin-bottom: 20px;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
        }
        .stat-card i {
            font-size: 24px;
            margin-bottom: 15px;
            color: #3c4b64;
        }
        .stat-card h3 {
            font-size: 28px;
            font-weight: 700;
            margin: 0;
            color: #343a40;
        }
        .stat-card p {
            margin: 0;
            color: #6c757d;
            font-size: 16px;
        }
        .class-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .class-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.15);
        }
        .class-card .card-title {
            font-weight: 600;
            color: #343a40;
        }
        .class-card .card-text {
            color: #6c757d;
        }
    </style>