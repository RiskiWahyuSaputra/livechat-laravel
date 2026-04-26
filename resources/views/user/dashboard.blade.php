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
						<ul class="main-nav" style="margin: 0 auto !important; display: flex; float: none !important;">
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
</body>
</html>