<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title>SentiSyncEd - Platform Monitoring Emosi</title>
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="css/style.css">
    <link rel="stylesheet" href="css/footer.css">
    <style>
        /* Global Styles */
        :root {
            --primary-color: #4A90E2;
            --primary-dark: #357ABD;
            --text-color: #2c3e50;
            --text-light: #666;
            --bg-light: #f8f9fa;
            --white: #ffffff;
            --shadow-sm: 0 5px 15px rgba(0, 0, 0, 0.1);
            --transition: all 0.3s ease;
            --header-height: 70px;
            --mobile-breakpoint: 768px;
            --tablet-breakpoint: 1024px;
        }
        
        /* Base Styles */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        html {
            font-size: 16px;
            scroll-behavior: smooth;
        }
        
        @media (max-width: 768px) {
            html {
                font-size: 14px;
            }
        }

        /* Typography */
        body {
            font-family: 'Open Sans', sans-serif;
            color: var(--text-color);
            line-height: 1.6;
            overflow-x: hidden;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        
        main {
            flex: 1;
            width: 100%;
        }

        h1, h2, h3, h4, h5, h6 {
            font-weight: 700;
            line-height: 1.3;
        }

        .section-title {
            font-size: 2rem;
            margin: 0 auto 2.5rem;
            text-align: center;
            position: relative;
            display: inline-block;
            width: 100%;
            padding: 0 1rem;
        }
        
        @media (min-width: 768px) {
            .section-title {
                font-size: 2.5rem;
                margin-bottom: 3rem;
            }
        }

        .section-title:after {
            content: '';
            position: absolute;
            width: 80px;
            height: 4px;
            background: var(--primary-color);
            bottom: -15px;
            left: 50%;
            transform: translateX(-50%);
            border-radius: 2px;
        }

        .container {
            width: 100%;
            max-width: 1200px;
            margin: 0 auto;
            padding: 0 1.5rem;
        }
        
        @media (min-width: 768px) {
            .container {
                padding: 0 2rem;
            }
        }

        /* Button Styles */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            padding: 0.8rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            text-decoration: none;
            transition: var(--transition);
            border: none;
            cursor: pointer;
            font-size: 1rem;
        }

        .btn-primary {
            background-color: var(--white);
            color: var(--primary-color);
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }

        .btn-outline {
            background: transparent;
            border: 2px solid var(--white);
            color: var(--white);
        }

        .btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 7px 20px rgba(0, 0, 0, 0.15);
        }

        /* Card Styles */
        .card {
            background: var(--white);
            border-radius: 15px;
            padding: 2rem;
            box-shadow: var(--shadow-sm);
            transition: var(--transition);
            height: 100%;
        }

        .card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .card-icon {
            font-size: 2.5rem;
            color: var(--primary-color);
            margin-bottom: 1.5rem;
        }

        /* Hero Section */
        .hero-section {
            position: relative;
            overflow: hidden;
        }

        /* About Section */
        .about-section {
            padding: 6rem 0;
            background-color: var(--bg-light);
        }

        .about-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4rem;
            align-items: center;
        }

        .about-image {
            position: relative;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
        }

        .about-image img {
            width: 100%;
            height: auto;
            display: block;
            transition: var(--transition);
        }

        .about-image:hover img {
            transform: scale(1.03);
        }

        .about-content {
            padding: 2rem 0;
        }

        .about-features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-top: 2rem;
        }

        .feature-box {
            background: rgba(74, 144, 226, 0.1);
            padding: 1.5rem;
            border-radius: 10px;
            border-left: 4px solid var(--primary-color);
            transition: var(--transition);
        }

        .feature-box:hover {
            transform: translateY(-3px);
            background: rgba(74, 144, 226, 0.15);
        }

        /* Timeline Section */
        .timeline-section {
            padding: 6rem 0;
            background-color: var(--white);
        }

        .timeline {
            position: relative;
            max-width: 900px;
            margin: 0 auto;
            padding: 2rem 0;
        }

        .timeline::before {
            content: '';
            position: absolute;
            width: 2px;
            background-color: var(--primary-color);
            top: 0;
            bottom: 0;
            left: 50%;
            margin-left: -1px;
        }

        .timeline-item {
            padding: 10px 40px;
            position: relative;
            width: 50%;
            box-sizing: border-box;
            margin-bottom: 2rem;
        }

        .timeline-item:nth-child(odd) {
            left: 0;
        }

        .timeline-item:nth-child(even) {
            left: 50%;
        }

        .timeline-content {
            padding: 2rem;
            background: var(--white);
            border-radius: 10px;
            box-shadow: var(--shadow-sm);
            position: relative;
            transition: var(--transition);
        }

        .timeline-content:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }

        .timeline-number {
            position: absolute;
            width: 40px;
            height: 40px;
            background: var(--primary-color);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: bold;
            top: 20px;
            right: -60px;
            z-index: 1;
        }

        .timeline-item:nth-child(even) .timeline-number {
            left: -60px;
            right: auto;
        }

        /* Profile Section */
        .profile-section {
            padding: 6rem 0;
            background-color: var(--bg-light);
        }

        .profile-card {
            max-width: 800px;
            margin: 0 auto;
            background: var(--white);
            border-radius: 15px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            display: flex;
            transition: var(--transition);
        }

        .profile-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(0, 0, 0, 0.1);
        }

        .profile-image {
            width: 300px;
            background: #f0f4f8;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }

        .profile-image-inner {
            width: 200px;
            height: 200px;
            border-radius: 50%;
            background: var(--primary-color);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 3rem;
            font-weight: bold;
            overflow: hidden;
        }

        .profile-info {
            flex: 1;
            padding: 3rem;
        }

        .profile-name {
            font-size: 1.8rem;
            margin: 0 0 0.5rem 0;
            color: var(--text-color);
        }

        .profile-title {
            color: var(--primary-color);
            font-style: italic;
            margin-bottom: 1.5rem;
            display: block;
        }

        .social-links {
            display: flex;
            gap: 1rem;
            margin-top: 2rem;
        }

        .social-link {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: var(--bg-light);
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--primary-color);
            text-decoration: none;
            transition: var(--transition);
        }

        .social-link:hover {
            background: var(--primary-color);
            color: white;
            transform: translateY(-3px);
        }

        /* Responsive Styles */
        @media (max-width: 992px) {
            .about-grid {
                grid-template-columns: 1fr;
                text-align: center;
            }

            .about-features {
                grid-template-columns: 1fr;
            }

            .profile-card {
                flex-direction: column;
                max-width: 500px;
            }

            .profile-image {
                width: 100%;
                padding: 2rem 0;
            }
        }

        @media (max-width: 768px) {
            .timeline::before {
                left: 40px;
            }

            .timeline-item {
                width: 100%;
                padding-left: 70px;
                padding-right: 25px;
            }

            .timeline-item:nth-child(even) {
                left: 0;
            }

            .timeline-number {
                left: 20px !important;
                right: auto !important;
            }
        }

        @media (max-width: 576px) {
            .section-title {
                font-size: 2rem;
            }

            .profile-info {
                padding: 2rem 1.5rem;
            }
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, #4A90E2 0%, #357ABD 100%);
            color: white;
            min-height: 100vh;
            display: flex;
            align-items: center;
            position: relative;
            overflow: hidden;
            width: 100%;
            padding: 2rem 0;
        }
        
        @media (min-width: 768px) {
            .hero-section {
                padding: 6rem 0;
            }
        }

        .hero-content {
            max-width: 1200px;
            width: 100%;
            margin: 0 auto;
            padding: 2rem;
            text-align: center;
            position: relative;
            z-index: 2;
        }

        .hero-title {
            font-size: 3.5rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            animation: fadeInUp 1s ease;
        }

        .hero-description {
            font-size: 1.5rem;
            margin-bottom: 2.5rem;
            opacity: 0.9;
            animation: fadeInUp 1s ease 0.2s;
        }

        .hero-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            animation: fadeInUp 1s ease 0.4s;
        }

        .btn-primary {
            background-color: white;
            color: #4A90E2;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-secondary {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 50px;
            font-weight: 600;
            transition: transform 0.3s, box-shadow 0.3s;
        }

        .btn-primary:hover, .btn-secondary:hover {
            transform: translateY(-3px);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        }

        .features {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-top: 4rem;
            animation: fadeInUp 1s ease 0.6s;
        }

        .feature-card {
            background: rgba(255, 255, 255, 0.1);
            padding: 2rem;
            border-radius: 15px;
            text-align: center;
            backdrop-filter: blur(10px);
            transition: transform 0.3s;
        }

        .feature-card:hover {
            transform: translateY(-10px);
        }

        .feature-icon {
            font-size: 2.5rem;
            margin-bottom: 1rem;
        }

        .feature-title {
            font-size: 1.2rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
        }

        .feature-description {
            font-size: 0.9rem;
            opacity: 0.8;
        }

        .wave {
            position: absolute;
            bottom: 0;
            left: 0;
            width: 100%;
            height: 100px;
            background: url('data:image/svg+xml,<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1440 320"><path fill="%23ffffff" fill-opacity="1" d="M0,96L48,112C96,128,192,160,288,160C384,160,480,128,576,112C672,96,768,96,864,112C960,128,1056,160,1152,160C1248,160,1344,128,1392,112L1440,96L1440,320L1392,320C1344,320,1248,320,1152,320C1056,320,960,320,864,320C768,320,672,320,576,320C480,320,384,320,288,320C192,320,96,320,48,320L0,320Z"></path></svg>');
            background-size: cover;
            background-repeat: no-repeat;
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.5rem;
            }

            .hero-description {
                font-size: 1.2rem;
            }

            .hero-buttons {
                flex-direction: column;
                gap: 1rem;
            }

            .features {
                grid-template-columns: 1fr;
                padding: 0 1rem;
            }
        }
    </style>
</head>
<body>
    <div class="hero-section">
        <div class="hero-content">
            <h1 class="hero-title">Selamat Datang di SentiSyncEd</h1>
            <p class="hero-description">Platform Monitoring Emosi Mahasiswa dan Dosen</p>
            
            <div class="hero-buttons">
                <a href="login.php" class="btn-primary">Login</a>
                <a href="register.php" class="btn-secondary">Daftar Sekarang</a>
            </div>

            <div class="features">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-chart-line"></i>
                    </div>
                    <h3 class="feature-title">Monitoring Real-time</h3>
                    <p class="feature-description">Pantau emosi secara real-time dengan visualisasi yang interaktif</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-comments"></i>
                    </div>
                    <h3 class="feature-title">Sistem Curhat</h3>
                    <p class="feature-description">Bagikan perasaan dan dapatkan dukungan yang diperlukan</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-file-alt"></i>
                    </div>
                    <h3 class="feature-title">Laporan Refleksi</h3>
                    <p class="feature-description">Generate laporan detail untuk analisis mendalam</p>
                </div>
            </div>
        </div>
        <div class="wave"></div>
    </div>
    
    <!-- Tentang Aplikasi Section -->
    <section class="about-section" style="padding: 5rem 2rem; background-color: #f8f9fa;">
        <div class="container" style="max-width: 1200px; margin: 0 auto;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #2c3e50;">Tentang SentiSyncEd</h2>
            <div style="background: white; border-radius: 15px; padding: 2.5rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05);">
                <p style="font-size: 1.1rem; line-height: 1.8; color: #555; margin-bottom: 2rem;">
                    SentiSyncEd adalah platform inovatif yang dirancang untuk membantu dosen memantau kondisi emosional mahasiswa selama proses pembelajaran berlangsung. Dengan antarmuka yang intuitif, aplikasi ini memungkinkan pengguna untuk melacak, menganalisis, dan merespon perubahan emosi secara real-time, menciptakan lingkungan belajar yang lebih peka dan mendukung.
                </p>
                <div class="about-cards" style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 1.5rem; margin: 2rem 0;">
                    <div class="about-card" style="background: #f0f7ff; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #4A90E2; transition: all 0.3s ease; margin-bottom: 1rem;">
                        <h3 style="color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem;">Tujuan</h3>
                        <p style="color: #666; line-height: 1.6; margin: 0;">
                            Meningkatkan kualitas pembelajaran dengan memahami kondisi emosional mahasiswa dan memberikan dukungan yang tepat waktu.
                        </p>
                    </div>
                    <div class="about-card" style="background: #f0f7ff; padding: 1.5rem; border-radius: 10px; border-left: 4px solid #4A90E2; transition: all 0.3s ease; margin-bottom: 1rem;">
                        <h3 style="color: #2c3e50; margin-bottom: 1rem; font-size: 1.3rem;">Manfaat</h3>
                        <p style="color: #666; line-height: 1.6; margin: 0;">
                            Dosen dapat dengan cepat mengidentifikasi mahasiswa yang membutuhkan perhatian khusus berdasarkan data emosi yang terekam.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Timeline Section -->
    <section class="timeline-section" style="padding: 5rem 2rem; background-color: #fff;">
        <div class="container" style="max-width: 1000px; margin: 0 auto; position: relative;">
            <h2 style="text-align: center; font-size: 2.5rem; margin-bottom: 3rem; color: #2c3e50;">Alur Kerja Aplikasi</h2>
            
            <div style="position: relative; padding-left: 50px;">
                <!-- Timeline line -->
                <div style="position: absolute; left: 25px; top: 0; bottom: 0; width: 2px; background: #4A90E2;"></div>
                
                <!-- Timeline Item 1 -->
                <div style="position: relative; margin-bottom: 3rem;">
                    <div style="position: absolute; left: -50px; top: 0; width: 50px; height: 50px; background: #4A90E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">1</div>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #2c3e50; margin-top: 0;">Pendaftaran & Login</h3>
                        <p style="color: #666; line-height: 1.6;">
                            Dosen dan mahasiswa melakukan pendaftaran akun dan login ke dalam sistem sesuai dengan peran masing-masing.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 2 -->
                <div style="position: relative; margin-bottom: 3rem;">
                    <div style="position: absolute; left: -50px; top: 0; width: 50px; height: 50px; background: #4A90E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">2</div>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #2c3e50; margin-top: 0;">Manajemen Kelas</h3>
                        <p style="color: #666; line-height: 1.6;">
                            Dosen membuat kelas dan mengundang mahasiswa untuk bergabung. Setiap kelas dapat memiliki beberapa sesi pembelajaran.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 3 -->
                <div style="position: relative; margin-bottom: 3rem;">
                    <div style="position: absolute; left: -50px; top: 0; width: 50px; height: 50px; background: #4A90E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">3</div>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #2c3e50; margin-top: 0;">Pelaporan Emosi</h3>
                        <p style="color: #666; line-height: 1.6;">
                            Mahasiswa melaporkan kondisi emosional mereka selama sesi kelas berlangsung melalui antarmuka yang mudah digunakan.
                        </p>
                    </div>
                </div>
                
                <!-- Timeline Item 4 -->
                <div style="position: relative;">
                    <div style="position: absolute; left: -50px; top: 0; width: 50px; height: 50px; background: #4A90E2; border-radius: 50%; display: flex; align-items: center; justify-content: center; color: white; font-weight: bold; font-size: 1.2rem;">4</div>
                    <div style="background: #f8f9fa; padding: 1.5rem; border-radius: 10px; box-shadow: 0 3px 10px rgba(0,0,0,0.05);">
                        <h3 style="color: #2c3e50; margin-top: 0;">Monitoring & Analisis</h3>
                        <p style="color: #666; line-height: 1.6;">
                            Dosen dapat memantau kondisi emosional mahasiswa secara real-time dan menerima notifikasi jika terdeteksi emosi negatif yang memerlukan perhatian.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Biografi Section -->
    <section class="biography-section" style="padding: 5rem 2rem; background-color: #f8f9fa;">
        <div class="container" style="max-width: 1000px; margin: 0 auto; text-align: center;">
            <h2 style="font-size: 2.5rem; margin-bottom: 3rem; color: #2c3e50;">Tentang Pengembang</h2>
            
            <div style="background: white; border-radius: 15px; padding: 3rem; box-shadow: 0 5px 15px rgba(0,0,0,0.05); display: inline-block; max-width: 800px;">
                <div style="width: 150px; height: 150px; border-radius: 50%; background: #e0e0e0; margin: 0 auto 2rem; overflow: hidden;">
                    <!-- Placeholder for profile image -->
                    <img src="assets/images/profile/foto-diri.jpg" alt="Foto Diri" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                </div>
                <h3 style="color: #2c3e50; font-size: 1.8rem; margin-bottom: 1rem;">Rifky Najra Adipura</h3>
                <p style="color: #666; font-style: italic; margin-bottom: 1.5rem;">Pengembang & Pendiri SentiSyncEd</p>
                
                <div style="max-width: 700px; margin: 0 auto 2rem;">
                    <p style="color: #555; line-height: 1.8; margin-bottom: 1.5rem;">
                        Seorang pengembang perangkat lunak yang bersemangat dalam menciptakan solusi teknologi untuk pendidikan. 
                        Dengan latar belakang di bidang teknologi pendidikan, saya berkomitmen untuk mengembangkan alat yang 
                        dapat meningkatkan kualitas pembelajaran dan kesejahteraan mahasiswa.
                    </p>
                    <p style="color: #555; line-height: 1.8;">
                        SentiSyncEd lahir dari keinginan untuk menjembatani kesenjangan komunikasi emosional antara dosen 
                        dan mahasiswa, menciptakan lingkungan belajar yang lebih peka dan mendukung.
                    </p>
                </div>
                
                <div style="display: flex; justify-content: center; gap: 1rem; margin-top: 2rem; flex-wrap: wrap;">
                <a href="https://github.com/rifkyadipura" 
                target="_blank" rel="noopener noreferrer" 
                style="color: #4A90E2; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-github" style="font-size: 1.2rem;"></i> GitHub
                </a>
                <span style="color: #ddd;">•</span>
                <a href="https://www.linkedin.com/in/rifkynajraadipura" 
                target="_blank" rel="noopener noreferrer" 
                style="color: #4A90E2; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fab fa-linkedin" style="font-size: 1.2rem;"></i> LinkedIn
                </a>
                <span style="color: #ddd;">•</span>
                <a href="mailto:rifkyadipura@gmail.com" 
                target="_blank" rel="noopener noreferrer" 
                style="color: #4A90E2; text-decoration: none; display: inline-flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-envelope" style="font-size: 1.2rem;"></i> Email
                </a>
            </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="site-footer" style="background: linear-gradient(135deg, #1a2a3a 0%, #1a2533 100%); color: #ecf0f1; padding: 4rem 0 0; position: relative; overflow: hidden;">
        <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 3rem; margin-bottom: 3rem;">
                <!-- About Column -->
                <div class="footer-about">
                    <div style="display: flex; align-items: center; margin-bottom: 1.5rem;">
                        <i class="fas fa-graduation-cap" style="font-size: 2rem; color: #4A90E2; margin-right: 0.8rem;"></i>
                        <h3 style="margin: 0; font-size: 1.8rem; color: #fff; font-weight: 700;">SentiSyncEd</h3>
                    </div>
                    <p style="line-height: 1.7; opacity: 0.8; margin-bottom: 1.5rem;">
                        Platform inovatif untuk memantau dan memahami kondisi emosional mahasiswa selama proses pembelajaran berlangsung.
                    </p>
                </div>

                <!-- Contact Info -->
                <div class="footer-contact">
                    <h4 style="color: #fff; margin-top: 0; margin-bottom: 1.5rem; font-size: 1.2rem; position: relative; padding-bottom: 0.8rem;">
                        <span style="display: inline-block; position: relative;">Hubungi Kami
                            <span style="position: absolute; bottom: -8px; left: 0; width: 40px; height: 2px; background: #4A90E2;"></span>
                        </span>
                    </h4>
                    <ul style="list-style: none; padding: 0; margin: 0;">
                        <li style="margin-bottom: 1.2rem; display: flex; align-items: flex-start;">
                            <i class="fas fa-map-marker-alt" style="color: #4A90E2; margin-right: 12px; margin-top: 4px;"></i>
                            <span style="opacity: 0.9; line-height: 1.6;">
                                Jl. Sariasih No.54, Sarijadi, Kec. Sukasari, Kota Bandung, Jawa Barat 40151
                            </span>
                        </li>
                        <li style="margin-bottom: 1.2rem; display: flex; align-items: center;">
                            <i class="fas fa-envelope" style="color: #4A90E2; margin-right: 12px;"></i>
                            <a href="mailto:info@sentisynced.com" style="color: #ecf0f1; text-decoration: none; opacity: 0.9; transition: opacity 0.3s ease;">
                                rifkyadipura@gmail.com
                            </a>
                        </li>
                        <li style="margin-bottom: 1.2rem; display: flex; align-items: center;">
                            <i class="fas fa-phone-alt" style="color: #4A90E2; margin-right: 12px;"></i>
                            <a href="tel:+622112345678" style="color: #ecf0f1; text-decoration: none; opacity: 0.9; transition: opacity 0.3s ease;">
                                +62 896-5714-0789
                            </a>
                        </li>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Copyright -->
        <div style="background: rgba(0, 0, 0, 0.2); padding: 1.5rem 0; text-align: center;">
            <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0 2rem;">
                <p style="margin: 0; opacity: 0.7; font-size: 0.9rem;">
                    &copy; <?php echo date('Y'); ?> SentiSyncEd. All Rights Reserved.
                </p>
                <div style="margin-top: 1rem;">
                    <a href="https://policies.google.com/privacy" target="_blank" rel="noopener noreferrer"
                    style="color: #ecf0f1; text-decoration: none; font-size: 0.8rem; opacity: 0.7; margin: 0 0.5rem; transition: opacity 0.3s ease;">
                        Kebijakan Privasi
                    </a>
                    <span style="opacity: 0.3;">|</span>
                    <a href="https://policies.google.com/terms" target="_blank" rel="noopener noreferrer"
                    style="color: #ecf0f1; text-decoration: none; font-size: 0.8rem; opacity: 0.7; margin: 0 0.5rem; transition: opacity 0.3s ease;">
                        Syarat & Ketentuan
                    </a>
                </div>
            </div>
        </div>
    </footer>

    <style>
        /* Responsive Base Styles */
        /* About Cards Responsive */
        @media (max-width: 767px) {
            .about-cards {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
                margin: 1.5rem 0 !important;
                padding: 0 1rem;
            }
            
            .about-card {
                margin-bottom: 1rem !important;
                padding: 1.25rem !important;
            }
            
            .about-card h3 {
                font-size: 1.2rem !important;
                margin-bottom: 0.75rem !important;
            }
            
            .about-card p {
                font-size: 0.95rem !important;
                line-height: 1.5 !important;
            }
        }
        
        .about-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 768px) {
            /* Adjust section padding for mobile */
            section {
                padding: 3rem 0 !important;
            }
            
            /* Adjust section titles */
            .section-title {
                font-size: 1.8rem !important;
                margin-bottom: 2rem !important;
            }
            
            /* Adjust hero section */
            .hero-title {
                font-size: 2.2rem !important;
                line-height: 1.2 !important;
            }
            
            .hero-description {
                font-size: 1.1rem !important;
                margin-bottom: 2rem !important;
            }
            
            /* Adjust timeline */
            .timeline-item {
                width: 100% !important;
                padding: 1.5rem !important;
                margin-bottom: 2rem !important;
            }
            
            .timeline-item:not(:last-child)::after {
                display: none !important;
            }
            
            /* Adjust cards */
            .card {
                margin-bottom: 1.5rem !important;
            }
            
            /* Adjust buttons */
            .btn {
                padding: 0.7rem 1.5rem !important;
                font-size: 0.9rem !important;
            }
        }
        
        /* Tablet Styles */
        @media (min-width: 769px) and (max-width: 1024px) {
            .container {
                padding: 0 1.5rem !important;
            }
            
            .section-title {
                font-size: 2.2rem !important;
            }
            
            .card {
                margin-bottom: 1.5rem !important;
            }
        }
        
        /* Hover Effects */
        @media (hover: hover) {
            .site-footer a:not(.social-links a):hover {
                color: #4A90E2 !important;
                transform: translateX(5px);
            }

            .social-links a:hover {
                background: #4A90E2 !important;
                transform: translateY(-3px) !important;
                box-shadow: 0 5px 15px rgba(74, 144, 226, 0.3);
            }
        }

        /* Responsive Footer */
        @media (max-width: 768px) {
            .site-footer {
                text-align: center;
                padding: 3rem 0 0 !important;
            }
            
            .footer-about, 
            .footer-links, 
            .footer-contact,
            .footer-newsletter {
                margin-bottom: 2.5rem;
                padding: 0 1rem;
            }
            
            .footer-links ul,
            .footer-contact ul {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            
            .footer-links li,
            .footer-contact li {
                justify-content: center;
                text-align: center;
                margin-bottom: 1rem;
            }
            
            .footer-newsletter form {
                flex-direction: column;
                gap: 0.8rem;
            }
            
            .footer-newsletter input,
            .footer-newsletter button {
                width: 100% !important;
                max-width: 100% !important;
            }
            
            .footer-about {
                margin-top: 1rem;
            }
        }
        
        /* About Cards Responsive */
        .about-cards {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            width: 100%;
        }
        
        .about-card {
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .about-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 20px rgba(0,0,0,0.1);
        }
        
        @media (max-width: 767px) {
            .about-cards {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .about-card {
                margin-bottom: 1rem;
            }
        }
        
        /* About Cards Responsive */
        @media (max-width: 767px) {
            .about-cards {
                grid-template-columns: 1fr !important;
                gap: 1rem !important;
                margin-top: 1.5rem !important;
            }
            
            .about-card {
                margin-bottom: 0 !important;
            }
        }
        
        .about-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
        
        /* Ensure images are responsive */
        img {
            max-width: 100%;
            height: auto;
            display: block;
        }
        
        /* Touch targets for mobile */
        @media (max-width: 768px) {
            a, button, [role="button"], input, textarea, select, label {
                min-height: 44px;
                min-width: 44px;
            }
        }
    </style>
</body>
</html>
