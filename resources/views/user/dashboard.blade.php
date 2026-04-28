<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo-brilian-min2.png') }}">
    <script>
        window.broadcastingAuth = "{{ url('/broadcasting/auth') }}";
    </script>
	<title>Dashboard User - BRILLIAN BIZ</title>

	<!-- Favicon -->
	
	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/css/bootstrap.min.css') }}">

	<!-- Fontawesome CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/fontawesome/css/fontawesome.min.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/fontawesome/css/all.min.css') }}">

	<!-- Fearther CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/css/feather.css') }}">

	<!-- select CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/select2/css/select2.min.css') }}">
		
	<!-- Owl carousel CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/css/owl.carousel.min.css') }}">
	
	<!-- Aos CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/aos/aos.css') }}">

	<!-- Main CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/css/style.css') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] { display: none !important; }
        html, body {
            max-width: 100%;
            overflow-x: hidden;
        }
        .main-wrapper {
            overflow-x: clip;
        }
        .chat-widget-container {
            z-index: 9999;
        }
        /* Ensure circle shape */
        .rounded-full {
            border-radius: 9999px !important;
        }
        /* Move Scroll Up to Left */
        .progress-wrap {
            left: 30px !important;
            right: auto !important;
        }

        /* Custom styles for feature-box hover */
        .feature-box {
            position: relative;
            overflow: hidden;
        }
        .feature-icon {
            height: 114px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .feature-icon span {
            transition: all 0.4s ease-in-out;
        }
        .feature-box:hover .feature-icon span {
            position: absolute;
            top: 15px;
            right: 15px;
            width: 40px;
            height: 40px;
            margin: 0;
            background: rgba(255, 255, 255, 0.9);
            z-index: 10;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .feature-icon span img {
            transition: all 0.4s ease-in-out;
        }
        .feature-box:hover .feature-icon span img {
            width: 20px;
            height: auto;
        }

        /* Remove dark overlay on hover */
        .feature-box:hover .feature-overlay:before {
            display: none !important;
        }

        /* Badge-like style for text on hover */
        .feature-box h5 {
            transition: all 0.4s ease-in-out;
            position: relative;
            z-index: 2;
            padding: 5px 10px;
            border-radius: 5px;
            display: inline-block;
        }
        .feature-box:hover h5 {
            background: rgba(0, 0, 0, 0.6);
            color: #fff !important;
            text-shadow: 1px 1px 2px rgba(0,0,0,0.5);
        }

        /* Hero Section Qontak-style refinements */
        .hero-section {
            background: linear-gradient(135deg, #f8fbff 0%, #ffffff 100%);
            padding: 100px 0;
            overflow: hidden;
        }
        .hero-title {
            font-size: 3.5rem;
            font-weight: 800;
            line-height: 1.2;
            margin-bottom: 1.5rem;
            color: #0a1d37;
        }
        .hero-description {
            font-size: 1.15rem;
            line-height: 1.6;
            color: #5d6d7e;
            margin-bottom: 2.5rem;
        }
        .hero-cta-group {
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        .hero-visual-container {
            position: relative;
            padding: 20px;
        }
        .hero-main-card {
            background: #fff;
            border-radius: 24px;
            box-shadow: 0 25px 50px -12px rgba(10, 29, 55, 0.15);
            padding: 40px;
            position: relative;
            z-index: 2;
            transition: transform 0.3s ease;
            border: 1px solid rgba(255,255,255,0.8);
        }
        .hero-main-card:hover {
            transform: translateY(-10px);
        }
        .floating-element {
            position: absolute;
            border-radius: 16px;
            background: #fff;
            box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1);
            z-index: 3;
            padding: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: float 4s ease-in-out infinite;
        }
        .floating-1 { top: -10px; right: -20px; animation-delay: 0s; }
        .floating-2 { bottom: 20px; left: -30px; animation-delay: 1.5s; }
        
        @keyframes float {
            0%, 100% { transform: translateY(0); }
            50% { transform: translateY(-15px); }
        }
        
        .hero-bg-blob {
            position: absolute;
            top: 50%;
            left: 50%;
            transform: translate(-50%, -50%);
            width: 500px;
            height: 500px;
            background: radial-gradient(circle, rgba(76, 111, 255, 0.1) 0%, rgba(255,255,255,0) 70%);
            z-index: 1;
        }

        @media (max-width: 991px) {
            .hero-title { font-size: 2.5rem; }
            .hero-section { padding: 60px 0; }
            .hero-visual-container {
                overflow: hidden;
            }
            .hero-bg-blob {
                width: 320px;
                height: 320px;
                max-width: 100%;
            }
            .floating-1 {
                right: 0;
            }
            .floating-2 {
                left: 0;
            }
            .bg-blob-1,
            .bg-blob-2 {
                width: 220px;
                height: 220px;
            }
            .bg-blob-1 {
                right: -80px;
            }
            .bg-blob-2 {
                left: -80px;
            }
        }

        /* Remove blue background on navbar user profile hover */
        .header-navbar-rht .logged-item .nav-link:hover {
            background: transparent !important;
            color: inherit !important;
        }
        .header-navbar-rht .logged-item .nav-link:hover .user-name {
            color: #0a1d37 !important; /* Matches original title color */
        }

        /* Decorative Background Blobs */
        .bg-blob-1 {
            position: absolute;
            top: -100px;
            right: -100px;
            width: 400px;
            height: 400px;
            background: radial-gradient(circle, rgba(0, 123, 255, 0.05) 0%, transparent 70%);
            z-index: -1;
            animation: move 20s infinite alternate;
        }
        .bg-blob-2 {
            position: absolute;
            top: 500px;
            left: -100px;
            width: 300px;
            height: 300px;
            background: radial-gradient(circle, rgba(220, 38, 38, 0.03) 0%, transparent 70%);
            z-index: -1;
            animation: move 25s infinite alternate-reverse;
        }
        @keyframes move {
            from { transform: translate(0, 0); }
            to { transform: translate(50px, 100px); }
        }

        /* Glow effect for Feature Boxes */
        .feature-box {
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }
        .feature-box:hover {
            box-shadow: 0 15px 30px rgba(0, 123, 255, 0.1) !important;
            border-color: rgba(0, 123, 255, 0.2);
            transform: translateY(-8px);
        }

        /* Animation for user greeting in navbar */
        .user-greeting-pill {
            animation: slideInRight 0.5s ease-out;
            background: #f0f7ff;
            padding: 5px 15px;
            border-radius: 50px;
            border: 1px solid rgba(0, 123, 255, 0.1);
        }
        @keyframes slideInRight {
            from { opacity: 0; transform: translateX(20px); }
            to { opacity: 1; transform: translateX(0); }
        }

        /* Smooth Navbar Entrance on Scroll */
        .header {
            transition: all 0.8s cubic-bezier(0.4, 0, 0.2, 1) !important;
            transform: translateY(0);
        }
        .header.fixed {
            animation: slideDown 0.8s cubic-bezier(0.4, 0, 0.2, 1);
            background: rgba(255, 255, 255, 0.95) !important;
            backdrop-filter: blur(10px);
            box-shadow: 0 4px 30px rgba(0, 0, 0, 0.05) !important;
        }
        @keyframes slideDown {
            from { transform: translateY(-100%); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* ===== STATS SECTION ===== */
        .stats-section { padding: 70px 0; background: linear-gradient(135deg,#0a1d37 0%,#1a3a6e 100%); position:relative; overflow:hidden; }
        .stats-section::before { content:''; position:absolute; top:-80px; right:-80px; width:300px; height:300px; border-radius:50%; background:rgba(255,255,255,.04); }
        .stats-section::after { content:''; position:absolute; bottom:-60px; left:-60px; width:200px; height:200px; border-radius:50%; background:rgba(255,255,255,.03); }
        .stat-card { text-align:center; padding:30px 20px; border-radius:20px; background:rgba(255,255,255,.07); border:1px solid rgba(255,255,255,.12); backdrop-filter:blur(10px); transition:transform .3s,box-shadow .3s; }
        .stat-card:hover { transform:translateY(-8px); box-shadow:0 20px 40px rgba(0,0,0,.3); }
        .stat-number { font-size:3rem; font-weight:900; color:#fff; line-height:1; background:linear-gradient(135deg,#fff,#90cdf4); -webkit-background-clip:text; -webkit-text-fill-color:transparent; }
        .stat-label { color:rgba(255,255,255,.7); font-size:.9rem; margin-top:8px; letter-spacing:.5px; }
        .stat-icon { font-size:2rem; margin-bottom:15px; color:rgba(255,255,255,.5); }

        /* ===== TESTIMONIAL SECTION ===== */
        .testimonial-section { padding:80px 0; background:#f8fbff; }
        .testimonial-card { background:#fff; border-radius:20px; padding:35px 30px; box-shadow:0 10px 40px rgba(0,123,255,.07); border:1px solid rgba(0,123,255,.08); position:relative; height:100%; transition:transform .3s,box-shadow .3s; }
        .testimonial-card:hover { transform:translateY(-8px); box-shadow:0 20px 50px rgba(0,123,255,.13); }
        .testimonial-card::before { content:'\201C'; position:absolute; top:20px; left:25px; font-size:5rem; color:#007bff; opacity:.12; font-family:Georgia,serif; line-height:1; }
        .testimonial-avatar { width:52px; height:52px; border-radius:50%; background:linear-gradient(135deg,#007bff,#0056b3); display:flex; align-items:center; justify-content:center; color:#fff; font-weight:800; font-size:1.1rem; flex-shrink:0; }
        .testimonial-stars { color:#f59e0b; font-size:.85rem; margin-bottom:10px; }
        .testi-text { color:#555; font-size:.95rem; line-height:1.7; font-style:italic; margin-bottom:20px; }

        /* ===== FAQ SECTION ===== */
        .faq-section { padding:80px 0; background:#fff; }
        .faq-section .accordion-button { font-weight:700; color:#0a1d37 !important; background:#f8fbff !important; border-radius:12px !important; }
        .faq-section .accordion-button:not(.collapsed) { background:linear-gradient(135deg,#007bff,#0056b3) !important; color:#fff !important; box-shadow:0 4px 15px rgba(0,123,255,.3); }
        .faq-section .accordion-item { border:1px solid rgba(0,123,255,.1); border-radius:14px !important; margin-bottom:12px; overflow:visible !important; }
        .faq-section .accordion-body { color:#333 !important; line-height:1.8 !important; background:#fff !important; padding:20px 24px !important; font-size:.95rem !important; display:block !important; }
        .faq-section .accordion-collapse { overflow:visible !important; }
        .faq-section .accordion-button::after { filter:invert(0); }
        .faq-section .accordion-button:not(.collapsed)::after { filter:invert(1); }

        /* WhatsApp FAB — matches chat FAB size & position */
        .whatsapp-fab { position:fixed; bottom:104px; right:24px; width:64px; height:64px; background:#25D366; border-radius:50% !important; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.6rem; text-decoration:none; z-index:9998; box-shadow:0 25px 50px -12px rgba(37,211,102,.4); transition:all .3s; animation:waPulse 2s infinite; }
        @media(min-width:768px){ .whatsapp-fab { bottom:112px; right:32px; } }
        .whatsapp-fab:hover { background:#128C7E !important; color:#fff; transform:scale(1.1); }
        @keyframes waPulse { 0%,100%{box-shadow:0 25px 50px -12px rgba(37,211,102,.4)} 50%{box-shadow:0 25px 50px -12px rgba(37,211,102,.6)} }

        /* ===== CTA BANNER ===== */
        .cta-section { padding:80px 0; background:linear-gradient(135deg,#007bff 0%,#0a1d37 100%); position:relative; overflow:hidden; }
        .cta-section::before { content:''; position:absolute; top:-100px; right:-100px; width:400px; height:400px; border-radius:50%; background:rgba(255,255,255,.05); animation:spin 20s linear infinite; }
        .cta-section::after { content:''; position:absolute; bottom:-80px; left:-80px; width:300px; height:300px; border-radius:50%; background:rgba(255,255,255,.04); animation:spin 25s linear infinite reverse; }
        @keyframes spin { from { transform:rotate(0deg); } to { transform:rotate(360deg); } }
        .cta-btn-white { background:#fff; color:#007bff; border:none; padding:14px 35px; border-radius:50px; font-weight:700; font-size:1rem; transition:all .3s; box-shadow:0 5px 20px rgba(0,0,0,.15); }
        .cta-btn-white:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,0,0,.25); color:#0056b3; }
        .cta-btn-outline { background:transparent; color:#fff; border:2px solid rgba(255,255,255,.6); padding:14px 35px; border-radius:50px; font-weight:700; font-size:1rem; transition:all .3s; }
        .cta-btn-outline:hover { background:rgba(255,255,255,.15); border-color:#fff; transform:translateY(-3px); color:#fff; }

        /* ===== ENHANCED ANIMATIONS ===== */
        @keyframes pulse-ring { 0% { transform:scale(.9); opacity:.7; } 70% { transform:scale(1.1); opacity:0; } 100% { transform:scale(.9); opacity:0; } }
        .pulse-dot { position:relative; display:inline-block; }
        .pulse-dot::before { content:''; position:absolute; inset:-4px; border-radius:50%; background:rgba(40,167,69,.4); animation:pulse-ring 2s ease-out infinite; }
        @keyframes shimmer { 0% { background-position:-200% 0; } 100% { background-position:200% 0; } }
        .shimmer-badge { background:linear-gradient(90deg,#e0f0ff 25%,#b8daff 50%,#e0f0ff 75%); background-size:200% 100%; animation:shimmer 2.5s infinite; border-radius:50px; padding:6px 16px; font-size:.8rem; font-weight:600; color:#004aad; display:inline-block; margin-bottom:16px; }
        .work-box { transition:transform .35s,box-shadow .35s; border-radius:16px; }
        .work-box:hover { transform:translateY(-10px); box-shadow:0 20px 40px rgba(0,123,255,.12); }

        /* Keep centered nav on desktop without breaking the mobile drawer layout */
        @media (min-width: 992px) {
            .user-main-nav {
                margin: 0 auto !important;
                display: flex !important;
                float: none !important;
            }
        }

        @media (max-width: 991.98px) {
            .header {
                position: relative;
                z-index: 11000;
            }

            .header .main-menu-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                bottom: 0;
                width: min(280px, 85vw);
                max-width: 280px;
                height: 100vh;
                overflow-y: auto;
                background: #fff;
                z-index: 11001;
                transform: translateX(-100%);
                transition: transform 0.3s ease;
                box-shadow: 0 12px 36px rgba(15, 23, 42, 0.18);
            }

            .header .main-nav.user-main-nav {
                display: block !important;
                margin: 0 !important;
            }

            html.menu-opened .header .main-menu-wrapper,
            .main-wrapper.slide-nav .header .main-menu-wrapper {
                transform: translateX(0);
            }

            .header .menu-header {
                display: flex;
                position: sticky;
                top: 0;
                background: #fff;
                z-index: 2;
                border-bottom: 1px solid #eef2f7;
            }

            .header .main-nav.user-main-nav > li {
                display: block;
                border-bottom: 1px solid #e5e7eb;
            }

            .header .main-nav.user-main-nav > li > a {
                display: block;
                padding: 15px 20px !important;
                color: #0f172a;
            }

            .sidebar-overlay {
                z-index: 10990;
            }

            .whatsapp-fab,
            .chat-widget-container {
                z-index: 9998 !important;
            }
        }

    </style>
</head>

<body x-data="chatWidget()" x-init="initWidget()" class="antialiased">

    <!-- Animated Background Blobs -->
    <div class="bg-blob-1"></div>
    <div class="bg-blob-2"></div>

	<div class="main-wrapper">
	
		<!-- Header -->
		<header class="header">
			<div class="container">
				<nav class="navbar navbar-expand-lg header-nav">
					<div class="navbar-header">
						<a id="mobile_btn" href="javascript:void(0);">
							<span class="bar-icon">
								<span></span>
								<span></span>
								<span></span>
							</span>
						</a>
						<a href="{{ route('user.home') }}" class="navbar-brand logo">
							<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Logo" style="max-height: 45px;">
						</a>
						<a href="{{ route('user.home') }}" class="navbar-brand logo-small">
							<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Logo" style="max-height: 35px;">
						</a>
					</div>
					<div class="main-menu-wrapper">
						<div class="menu-header">
							<a href="{{ route('user.home') }}" class="menu-logo">
								<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Logo" style="max-height: 45px;">
							</a>
							<a id="menu_close" class="menu-close" href="javascript:void(0);"> <i class="fas fa-times"></i></a>
						</div>
						<ul class="main-nav user-main-nav">
							<li class="active">
								<a href="{{ route('user.home') }}">Beranda</a>
							</li>
							<li>
								<a href="{{ route('user.about') }}">Tentang Kami</a>
							</li>
                            <li>
								<a href="{{ route('user.contact') }}">Kontak</a>
							</li>
						</ul>
					</div>
                    
                    <!-- Navbar Profile (Dynamic with Alpine.js) -->
                    <ul class="nav header-navbar-rht" x-show="isAuthenticated" x-cloak>
                        <li class="nav-item dropdown has-arrow logged-item">
                            <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                                <span class="user-img">
                                    <div class="w-10 h-10 rounded-circle bg-primary d-flex align-items-center justify-content-center text-white font-bold" 
                                         style="width: 40px; height: 40px; border-radius: 50% !important; background: #007bff !important; color: white !important; font-weight: bold !important;"
                                         x-text="user.initial">
                                    </div>
                                </span>
                                <span class="user-content ms-2 d-none d-md-inline-block user-greeting-pill">
                                    <span class="user-name fw-bold" style="font-size: 14px; display: block; line-height: 1.2;">
                                        👋 <span x-text="user.name"></span>
                                    </span>
                                    <span class="user-details text-muted small" style="font-size: 11px; display: block;" x-text="user.origin"></span>
                                </span>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end">
                                <div class="user-header">
                                    <div class="avatar avatar-sm">
                                        <div class="w-8 h-8 rounded-circle bg-primary d-flex align-items-center justify-content-center text-white font-bold" 
                                             style="width: 32px; height: 32px; border-radius: 50% !important; background: #007bff !important; color: white !important; font-weight: bold !important; font-size: 12px;"
                                             x-text="user.initial">
                                        </div>
                                    </div>
                                    <div class="user-text">
                                        <h6 x-text="user.name"></h6>
                                        <p class="text-muted mb-0" x-text="user.origin"></p>
                                    </div>
                                </div>
                                <a class="dropdown-item" href="{{ route('chat.logout') }}">Logout</a>
                            </div>
                        </li>
                    </ul>
				</nav>
			</div>
		</header>
		<!-- /Header -->
		
		<!-- Hero Section -->
		<section class="hero-section">			
			<div class="container">
				<div class="row align-items-center">
					<!-- Text Content -->
					<div class="col-lg-6 aos" data-aos="fade-up">
						<div class="hero-content">
							<div class="shimmer-badge">✨ Platform Bisnis Syariah Terpercaya</div>
						<h1 class="hero-title">Wujudkan <span class="text-primary">Kebebasan</span> Finansial Anda.</h1>
							<p class="hero-description">Selamat datang di portal dukungan BRILLIAN BIZ. Kami menyediakan ekosistem bisnis syariah terpercaya untuk mendukung pertumbuhan finansial Anda.</p>
							<div class="hero-cta-group">
								<button class="btn btn-primary btn-lg rounded-pill px-4 py-3" type="button" @click="isOpen = true">
									<i class="feather-message-square me-2"></i>Mulai Chat Sekarang
								</button>
								<a href="#produk" class="btn btn-outline-primary btn-lg rounded-pill px-4 py-3">
									Lihat Produk <i class="feather-arrow-right ms-2"></i>
								</a>
							</div>
						</div>
					</div>
					
					<!-- Visual Content -->
					<div class="col-lg-6 aos" data-aos="fade-left">
						<div class="hero-visual-container">
							<div class="hero-bg-blob"></div>
							
							<!-- Main Visual Card -->
							<div class="hero-main-card">
								<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Brillian Biz" style="max-height: 250px; width: 100%; object-fit: contain;">
							</div>
							
							<!-- Floating Elements -->
							<div class="floating-element floating-1">
								<div class="w-10 h-10 rounded-circle bg-success flex items-center justify-center text-white" style="width: 40px; height: 40px; border-radius: 50% !important; background: #28a745 !important; display: flex !important; justify-content: center; align-items: center;">
									<i class="fas fa-check"></i>
								</div>
								<div class="text-[11px] font-bold text-slate-700" style="font-size: 11px; font-weight: bold;">Terverifikasi</div>
							</div>
							
							<div class="floating-element floating-2">
								<div class="w-10 h-10 rounded-circle bg-primary flex items-center justify-center text-white" style="width: 40px; height: 40px; border-radius: 50% !important; background: #007bff !important; display: flex !important; justify-content: center; align-items: center;">
									<i class="fas fa-users"></i>
								</div>
								<div>
									<div class="text-[10px] text-slate-400 leading-tight" style="font-size: 10px; color: #6c757d;">Bergabunglah</div>
									<div class="text-[12px] font-bold text-slate-800" style="font-size: 12px; font-weight: bold;">10,000+ Mitra</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- /Hero Section -->

		<!-- Stats Section -->
		<section class="stats-section">
			<div class="container">
				<div class="row g-4 justify-content-center">
					<div class="col-6 col-md-3 aos" data-aos="fade-up" data-aos-delay="0">
						<div class="stat-card">
							<div class="stat-icon"><i class="fas fa-users"></i></div>
							<div class="stat-number" data-count="10000">0</div>
							<div class="stat-label">Mitra Aktif</div>
						</div>
					</div>
					<div class="col-6 col-md-3 aos" data-aos="fade-up" data-aos-delay="100">
						<div class="stat-card">
							<div class="stat-icon"><i class="fas fa-box-open"></i></div>
							<div class="stat-number" data-count="50">0</div>
							<div class="stat-label">Produk Unggulan</div>
						</div>
					</div>
					<div class="col-6 col-md-3 aos" data-aos="fade-up" data-aos-delay="200">
						<div class="stat-card">
							<div class="stat-icon"><i class="fas fa-map-marked-alt"></i></div>
							<div class="stat-number" data-count="34">0</div>
							<div class="stat-label">Provinsi Terjangkau</div>
						</div>
					</div>
					<div class="col-6 col-md-3 aos" data-aos="fade-up" data-aos-delay="300">
						<div class="stat-card">
							<div class="stat-icon"><i class="fas fa-award"></i></div>
							<div class="stat-number" data-count="12">0</div>
							<div class="stat-label">Penghargaan</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- /Stats Section -->
		
		<!-- Feature Section -->
		<section class="feature-section" id="produk">			
			<div class="container">			
				<div class="section-heading">
					<div class="row">
						<div class="col-md-6 aos" data-aos="fade-up">
							<h2>Kategori Unggulan</h2>
							<p>Temukan solusi terbaik untuk Anda</p>
						</div>
						<div class="col-md-6 text-md-end aos" data-aos="fade-up">
							<a href="javascript:void(0);" class="btn btn-primary btn-view">Lihat Semua<i class="feather-arrow-right-circle"></i></a>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-6 col-lg-3">
						<a href="javascript:void(0);" class="feature-box aos" data-aos="fade-up">
							<div class="feature-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/feature-icon-01.svg') }}" alt="img">
								</span>
							</div>
							<h5>Pertanian</h5>
							<div class="feature-overlay">
								<img src="{{ asset('images/produk-pertanian.png') }}" alt="img">
							</div>
						</a>
					</div>
					<div class="col-md-6 col-lg-3">
						<a href="javascript:void(0);" class="feature-box aos" data-aos="fade-up">
							<div class="feature-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/feature-icon-02.svg') }}" alt="img">
								</span>
							</div>
							<h5>Otomotif</h5>
							<div class="feature-overlay">
								<img src="{{ asset('images/produk-otomotif.png') }}" alt="img">
							</div>
						</a>
					</div>
					<div class="col-md-6 col-lg-3">
						<a href="javascript:void(0);" class="feature-box aos" data-aos="fade-up">
							<div class="feature-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/feature-icon-03.svg') }}" alt="img">
								</span>
							</div>
							<h5>Kesehatan</h5>
							<div class="feature-overlay">
								<img src="{{ asset('images/produk-kesehatan.png') }}" alt="img">
							</div>
						</a>
					</div>
					<div class="col-md-6 col-lg-3">
						<a href="javascript:void(0);" class="feature-box aos" data-aos="fade-up">
							<div class="feature-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/feature-icon-04.svg') }}" alt="img">
								</span>
							</div>
							<h5>Kecantikan</h5>
							<div class="feature-overlay">
								<img src="{{ asset('images/produk-kecantikan.png') }}" alt="img">
							</div>
						</a>
					</div>
				</div>
			</div>
		</section>
		<!-- /Feature Section -->
		
		<!-- Service Section -->
		<section class="service-section" id="solusi">			
			<div class="container">
				<div class="section-heading">
					<div class="row">
						<div class="col-md-6 aos" data-aos="fade-up">						
							<h2>Solusi Bisnis</h2>
							<p>Ekosistem bisnis syariah terintegrasi</p>
						</div>
						<div class="col-md-6 text-md-end aos" data-aos="fade-up">
							<div class="owl-nav mynav"></div>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-12">
						<div class="owl-carousel service-slider">
							<div class="service-widget aos" data-aos="fade-up">
								<div class="service-img">
									<a href="javascript:void(0);">
										<img class="img-fluid serv-img" alt="Service Image" src="{{ asset('images/seminar.png') }}">
									</a>
								</div>
								<div class="service-content">
									<h3 class="title">
										<a href="javascript:void(0);">Pendampingan Bisnis</a>
									</h3>
									<p><i class="feather-map-pin"></i>Seluruh Indonesia</p>
								</div>
							</div>
							<div class="service-widget aos" data-aos="fade-up">
								<div class="service-img">
									<a href="javascript:void(0);">
										<img class="img-fluid serv-img" alt="Service Image" src="{{ asset('images/produk-best2.png') }}">
									</a>
								</div>
								<div class="service-content">
									<h3 class="title">
										<a href="javascript:void(0);">Produk Berkualitas</a>
									</h3>
									<p><i class="feather-map-pin"></i>Teruji & Terbukti</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- /Service Section -->

		<!-- Work Section -->
		<section class="work-section pt-0">		
			<div class="container">
				<div class="row">
					<div class="col-md-12 text-center">
						<div class="section-heading aos" data-aos="fade-up">
							<h2>Cara Kerja</h2>
							<p>Langkah mudah memulai perubahan</p>
						</div>
					</div>
				</div>
				<div class="row">
					<div class="col-md-4">
						<div class="work-box aos" data-aos="fade-up">
							<div class="work-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/work-icon.svg') }}" alt="img">
								</span>
							</div>
							<h5>Daftar Mitra</h5>
							<p>Bergabunglah dengan komunitas bisnis kami yang berkembang pesat.</p>
							<h4>01</h4>
						</div>						
					</div>
					<div class="col-md-4">
						<div class="work-box aos" data-aos="fade-up">
							<div class="work-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/find-icon.svg') }}" alt="img">
								</span>
							</div>
							<h5>Pilih Produk</h5>
							<p>Gunakan dan pasarkan produk-produk unggulan dari BRILLIAN BIZ.</p>
							<h4>02</h4>
						</div>						
					</div>
					<div class="col-md-4">
						<div class="work-box aos" data-aos="fade-up">
							<div class="work-icon">
								<span>
									<img src="{{ asset('template/assets/img/icons/place-icon.svg') }}" alt="img">
								</span>
							</div>
							<h5>Raih Reward</h5>
							<p>Dapatkan manfaat finansial dan reward prestasi yang luar biasa.</p>
							<h4>03</h4>
						</div>						
					</div>
				</div>
			</div>
		</section>
		<!-- /Work Section -->

		<!-- Testimonial Section -->
		<section class="testimonial-section">
			<div class="container">
				<div class="text-center mb-5 aos" data-aos="fade-up">
					<div class="shimmer-badge">💬 Kata Mereka</div>
					<h2 style="color:#0a1d37;font-weight:800;">Testimoni Mitra Kami</h2>
					<p class="text-muted">Ribuan mitra telah merasakan manfaat bergabung bersama BRILLIAN BIZ</p>
				</div>
				<div class="row g-4">
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="0">
						<div class="testimonial-card">
							<div class="testimonial-stars">★★★★★</div>
							<p class="testi-text">"Bergabung dengan BRILLIAN BIZ adalah keputusan terbaik saya. Dalam 6 bulan, penghasilan saya meningkat 3x lipat!"</p>
							<div class="d-flex align-items-center gap-3">
								<div class="testimonial-avatar">AS</div>
								<div><strong style="color:#0a1d37;">Andi Saputra</strong><br><small class="text-muted">Mitra Platinum, Jakarta</small></div>
							</div>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="150">
						<div class="testimonial-card">
							<div class="testimonial-stars">★★★★★</div>
							<p class="testi-text">"Produknya berkualitas tinggi dan sistem bisnisnya transparan. Saya sangat merekomendasikan BRILLIAN BIZ!"</p>
							<div class="d-flex align-items-center gap-3">
								<div class="testimonial-avatar" style="background:linear-gradient(135deg,#28a745,#20c997);">SR</div>
								<div><strong style="color:#0a1d37;">Sari Rahayu</strong><br><small class="text-muted">Mitra Gold, Bandung</small></div>
							</div>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="300">
						<div class="testimonial-card">
							<div class="testimonial-stars">★★★★★</div>
							<p class="testi-text">"Dukungan tim BRILLIAN BIZ luar biasa. Setiap pertanyaan langsung dijawab via LiveChat, sangat responsif!"</p>
							<div class="d-flex align-items-center gap-3">
								<div class="testimonial-avatar" style="background:linear-gradient(135deg,#fd7e14,#dc3545);">BW</div>
								<div><strong style="color:#0a1d37;">Budi Wijaya</strong><br><small class="text-muted">Mitra Silver, Surabaya</small></div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- /Testimonial Section -->

		<!-- FAQ Section -->
		<section class="faq-section">
			<div class="container">
				<div class="row justify-content-center">
					<div class="col-lg-8">
						<div class="text-center mb-5 aos" data-aos="fade-up">
							<div class="shimmer-badge">❓ Pertanyaan Umum</div>
							<h2 style="color:#0a1d37;font-weight:800;">FAQ</h2>
							<p class="text-muted">Temukan jawaban atas pertanyaan yang sering ditanyakan</p>
						</div>

						<style>
							.faq-custom-item { background:#fff; border-radius:16px; margin-bottom:14px; box-shadow:0 4px 20px rgba(0,123,255,.07); border:1.5px solid rgba(0,123,255,.1); overflow:hidden; transition:box-shadow .3s,border-color .3s; }
							.faq-custom-item:hover { box-shadow:0 8px 30px rgba(0,123,255,.13); border-color:rgba(0,123,255,.25); }
							.faq-custom-header { display:flex !important; flex-direction:row !important; align-items:center !important; gap:16px; padding:20px 24px; cursor:pointer; transition:background .3s; user-select:none; }
							.faq-custom-header.active { background:linear-gradient(135deg,#007bff,#0056b3) !important; }
							.faq-custom-badge { width:36px; height:36px; border-radius:10px; display:flex !important; align-items:center; justify-content:center; font-weight:900; font-size:.85rem; flex-shrink:0; transition:all .3s; background:#e8f0fe; color:#007bff; }
							.faq-custom-header.active .faq-custom-badge { background:rgba(255,255,255,.2) !important; color:#fff !important; }
							.faq-custom-title { flex:1; font-weight:700; font-size:1rem; color:#0a1d37; transition:color .3s; }
							.faq-custom-header.active .faq-custom-title { color:#fff; }
							.faq-custom-icon { width:30px; height:30px; border-radius:50%; display:flex !important; align-items:center; justify-content:center; flex-shrink:0; background:#f0f7ff; transition:all .3s; }
							.faq-custom-header.active .faq-custom-icon { background:rgba(255,255,255,.2); }
							.faq-custom-icon i { font-size:.7rem; color:#007bff; transition:transform .4s cubic-bezier(.4,0,.2,1),color .3s; }
							.faq-custom-header.active .faq-custom-icon i { color:#fff; transform:rotate(180deg); }
							.faq-custom-body { max-height:0; overflow:hidden; transition:max-height .4s cubic-bezier(.4,0,.2,1),padding .3s; }
							.faq-custom-body.open { max-height:200px; }
							.faq-custom-body-inner { padding:0 24px 22px 76px; color:#555; font-size:.95rem; line-height:1.8; }
						</style>

						<div class="faq-custom-item aos" data-aos="fade-up" data-aos-delay="0">
							<div class="faq-custom-header active" onclick="toggleFaq(this)">
								<div class="faq-custom-badge">01</div>
								<div class="faq-custom-title">Apa itu BRILLIAN BIZ?</div>
								<div class="faq-custom-icon"><i class="fas fa-chevron-down"></i></div>
							</div>
							<div class="faq-custom-body open">
								<div class="faq-custom-body-inner">BRILLIAN BIZ (PT Bandung Eco Sinergi Teknologi) adalah perusahaan Direct Selling yang berkomitmen membantu masyarakat mencapai kebebasan finansial melalui sistem bisnis syariah yang adil dan transparan.</div>
							</div>
						</div>

						<div class="faq-custom-item aos" data-aos="fade-up" data-aos-delay="100">
							<div class="faq-custom-header" onclick="toggleFaq(this)">
								<div class="faq-custom-badge">02</div>
								<div class="faq-custom-title">Bagaimana cara bergabung sebagai mitra?</div>
								<div class="faq-custom-icon"><i class="fas fa-chevron-down"></i></div>
							</div>
							<div class="faq-custom-body">
								<div class="faq-custom-body-inner">Anda dapat bergabung dengan menghubungi tim kami melalui LiveChat di website ini atau menghubungi mitra terdekat. Proses pendaftaran mudah dan cepat.</div>
							</div>
						</div>

						<div class="faq-custom-item aos" data-aos="fade-up" data-aos-delay="200">
							<div class="faq-custom-header" onclick="toggleFaq(this)">
								<div class="faq-custom-badge">03</div>
								<div class="faq-custom-title">Apakah bisnis ini sesuai syariah?</div>
								<div class="faq-custom-icon"><i class="fas fa-chevron-down"></i></div>
							</div>
							<div class="faq-custom-body">
								<div class="faq-custom-body-inner">Ya! Sistem pemasaran kami telah sesuai dengan fatwa DSN-MUI tentang Penjualan Langsung Berjenjang Syariah (PLBS), sehingga Anda dapat berbisnis dengan tenang dan berkah.</div>
							</div>
						</div>

						<div class="faq-custom-item aos" data-aos="fade-up" data-aos-delay="300">
							<div class="faq-custom-header" onclick="toggleFaq(this)">
								<div class="faq-custom-badge">04</div>
								<div class="faq-custom-title">Berapa potensi penghasilan sebagai mitra?</div>
								<div class="faq-custom-icon"><i class="fas fa-chevron-down"></i></div>
							</div>
							<div class="faq-custom-body">
								<div class="faq-custom-body-inner">Penghasilan tidak terbatas tergantung usaha dan dedikasi Anda. Mitra aktif kami rata-rata menghasilkan tambahan income 3-10 juta per bulan, bahkan ada yang mencapai ratusan juta.</div>
							</div>
						</div>

					</div>
				</div>
			</div>
		</section>
		<!-- /FAQ Section -->

		<script>
		function toggleFaq(header) {
			const allHeaders = document.querySelectorAll('.faq-custom-header');
			const allBodies = document.querySelectorAll('.faq-custom-body');
			const body = header.nextElementSibling;
			const isActive = header.classList.contains('active');

			// Close all
			allHeaders.forEach(h => h.classList.remove('active'));
			allBodies.forEach(b => b.classList.remove('open'));

			// Open clicked if was closed
			if (!isActive) {
				header.classList.add('active');
				body.classList.add('open');
			}
		}
		</script>


		<!-- CTA Section -->
		<section class="cta-section">
			<div class="container text-center" style="position:relative;z-index:2;">
				<div class="aos" data-aos="fade-up">
					<h2 style="color:#fff;font-weight:900;font-size:2.5rem;margin-bottom:1rem;">Siap Memulai Perjalanan Anda?</h2>
					<p style="color:rgba(255,255,255,.8);font-size:1.1rem;margin-bottom:2rem;">Bergabunglah dengan 10.000+ mitra dan raih kebebasan finansial bersama BRILLIAN BIZ</p>
					<div class="d-flex gap-3 justify-content-center flex-wrap">
						<button class="cta-btn-white" x-data @click="$dispatch('open-chat')"><i class="feather-message-square me-2"></i>Chat Sekarang</button>
						<a href="{{ route('user.about') }}" class="cta-btn-outline"><i class="feather-info me-2"></i>Pelajari Lebih Lanjut</a>
					</div>
				</div>
			</div>
		</section>
		<!-- /CTA Section -->


		<!-- Footer -->
		<footer class="footer">
		
			<!-- Footer Top -->
			<div class="footer-top aos" data-aos="fade-up">
				<div class="container">
					<div class="row">
						<div class="col-lg-4 col-md-6">
							<!-- Footer Widget -->
							<div class="footer-widget">
								<div class="footer-logo">
									<a href="{{ route('user.home') }}"><img src="{{ asset('images/logo-brilian-min.png') }}" alt="logo" style="max-height: 50px;"></a>
								</div>
								<div class="footer-content">
									<p>BRILLIAN BIZ adalah perusahaan yang memasarkan produk-produk berkualitas dengan konsep direct selling atau penjualan langsung. </p>
								</div>
							</div>
							<!-- /Footer Widget -->
						</div>
						<div class="col-lg-2 col-md-6">
							<!-- Footer Widget -->
							<div class="footer-widget footer-menu">
								<h2 class="footer-title">Tautan Cepat</h2>
								<ul>
									<li><a href="{{ route('user.about') }}">Tentang Kami</a></li>
									<li><a href="{{ route('user.contact') }}">Kontak</a></li>
									<li><a href="#produk">Produk</a></li>
								</ul>
							</div>
							<!-- /Footer Widget -->
						</div>
						<div class="col-lg-3 col-md-6">
							<!-- Footer Widget -->
							<div class="footer-widget footer-contact">
								<h2 class="footer-title">Hubungi Kami</h2>
								<div class="footer-contact-info">
									<div class="footer-address">
										<p><span><i class="feather-map-pin"></i></span> Bandung, Jawa Barat, Indonesia</p>
									</div>
									<p><span><i class="feather-phone"></i></span> +62 812-3456-7890</p>
									<p class="mb-0"><span><i class="feather-mail"></i></span> support@brillian.id</p>
								</div>
							</div>
							<!-- /Footer Widget -->
						</div>
						<div class="col-lg-3 col-md-6">
							<!-- Footer Widget -->
							<div class="footer-widget">
								<h2 class="footer-title">Ikuti Kami</h2>
								<div class="social-icon">
									<ul>
										<li><a href="#" target="_blank"><i class="fa-brands fa-facebook"></i> </a></li>
										<li><a href="#" target="_blank"><i class="fab fa-twitter"></i> </a></li>
										<li><a href="#" target="_blank"><i class="fa-brands fa-instagram"></i></a></li>
									</ul>
								</div>
							</div>
							<!-- /Footer Widget -->
						</div>
					</div>
				</div>
			</div>
			<!-- /Footer Top -->
			
			<!-- Footer Bottom -->
			<div class="footer-bottom">
				<div class="container">
					<!-- Copyright -->
					<div class="copyright">
						<div class="row align-items-center">
							<div class="col-md-6">
								<div class="copyright-text">
									<p class="mb-0">Copyright &copy; 2026 BRILLIAN BIZ. All Rights Reserved.</p>
								</div>
							</div>
							<div class="col-md-6 text-md-end">
								<!-- Copyright Menu -->
								<div class="copyright-menu">
									<ul class="policy-menu">
										<li><a href="javascript:void(0);">Privacy Policy</a></li>
										<li><a href="javascript:void(0);">Terms & Conditions</a></li>
									</ul>
								</div>
								<!-- /Copyright Menu -->
							</div>
						</div>
					</div>
					<!-- /Copyright -->
				</div>
			</div>
			<!-- /Footer Bottom -->
			
		</footer>
		<!-- /Footer -->
	</div>

    @include('components.chat-widget')

	<!-- WhatsApp FAB -->
	<a id="whatsapp-fab" class="whatsapp-fab" href="https://wa.me/6283179191601" target="_blank" title="Chat via WhatsApp">
		<i class="fab fa-whatsapp"></i>
	</a>
	<!-- /WhatsApp FAB -->

	<!-- scrollToTop start -->
	<div class="progress-wrap active-progress">
		<svg class="progress-circle svg-content" width="100%" height="100%" viewBox="-1 -1 102 102">
		<path d="M50,1 a49,49 0 0,1 0,98 a49,49 0 0,1 0,-98"></path>
		</svg>
	</div>
	<!-- scrollToTop end -->

	<!-- jQuery -->
	<script src="{{ asset('template/assets/js/jquery-3.6.1.min.js') }}"></script>

	<!-- Bootstrap Core JS -->
	<script src="{{ asset('template/assets/js/bootstrap.bundle.min.js') }}"></script>

	<!-- Fearther JS -->
	<script src="{{ asset('template/assets/js/feather.min.js') }}"></script>
		
	<!-- Owl Carousel JS -->
	<script src="{{ asset('template/assets/js/owl.carousel.min.js') }}"></script>

	<!-- select JS -->
	<script src="{{ asset('template/assets/plugins/select2/js/select2.min.js') }}"></script>
	
	<!-- Aos -->
	<script src="{{ asset('template/assets/plugins/aos/aos.js') }}"></script>
	
	<!-- Top JS -->
	<script src="{{ asset('template/assets/js/backToTop.js') }}"></script>

	<!-- Custom JS -->
	<script src="{{ asset('template/assets/js/script.js') }}"></script>

    @stack('scripts')
    <script>
    // Animated counter
    function animateCounter(el) {
        const target = +el.getAttribute('data-count');
        const duration = 2000;
        const step = target / (duration / 16);
        let current = 0;
        const timer = setInterval(() => {
            current += step;
            if (current >= target) { current = target; clearInterval(timer); }
            el.textContent = target >= 1000 ? Math.floor(current).toLocaleString('id-ID') + '+' : Math.floor(current) + (target <= 50 ? '+' : '');
        }, 16);
    }
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(e => { if (e.isIntersecting) { animateCounter(e.target); observer.unobserve(e.target); } });
    }, { threshold: 0.5 });
    document.querySelectorAll('.stat-number[data-count]').forEach(el => observer.observe(el));

    // Open chat from CTA
    document.addEventListener('open-chat', () => { if (window.Alpine) { document.querySelector('[x-data]').__x.$data.isOpen = true; } });
    </script>
</body>
</html>