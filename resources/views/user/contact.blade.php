<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo-brilian-min2.png') }}">
	<title>Kontak Kami - BRILLIAN BIZ</title>

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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
        body { font-family:'Inter',sans-serif; }

        .contact-hero { background:linear-gradient(135deg,#0a1d37 0%,#1a3a6e 60%,#007bff 100%); padding:130px 0 90px; color:#fff; text-align:center; position:relative; overflow:hidden; }
        .contact-hero::before { content:''; position:absolute; top:-80px; right:-80px; width:350px; height:350px; border-radius:50%; background:rgba(255,255,255,.04); animation:blobSpin 20s linear infinite; }
        .contact-hero::after { content:''; position:absolute; bottom:-60px; left:-60px; width:250px; height:250px; border-radius:50%; background:rgba(255,255,255,.03); animation:blobSpin 25s linear infinite reverse; }
        @keyframes blobSpin { from{transform:rotate(0deg)} to{transform:rotate(360deg)} }
        .hero-badge { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); backdrop-filter:blur(10px); border-radius:50px; padding:8px 20px; font-size:.85rem; font-weight:600; display:inline-block; margin-bottom:20px; }
        .contact-hero h1 { font-size:3.2rem; font-weight:900; text-shadow:0 2px 20px rgba(0,0,0,.3); }

        .contact-card { border:none; border-radius:24px; padding:40px 30px; height:100%; background:#fff; position:relative; overflow:hidden; box-shadow:0 10px 40px rgba(0,123,255,.07); border:1px solid rgba(0,123,255,.08); transition:transform .35s,box-shadow .35s; }
        .contact-card:hover { transform:translateY(-12px); box-shadow:0 25px 60px rgba(0,123,255,.15); }
        .contact-card::before { content:''; position:absolute; top:-30px; right:-30px; width:100px; height:100px; border-radius:50%; background:rgba(0,123,255,.05); transition:all .5s; }
        .contact-card:hover::before { transform:scale(3); }
        .contact-icon { width:70px; height:70px; border-radius:20px; display:flex; align-items:center; justify-content:center; font-size:1.6rem; margin-bottom:24px; box-shadow:0 8px 20px rgba(0,0,0,.15); }

        .map-container { border-radius:24px; overflow:hidden; box-shadow:0 15px 50px rgba(0,123,255,.1); height:420px; border:1px solid rgba(0,123,255,.08); }

        .contact-form { background:#fff; border-radius:24px; padding:40px; box-shadow:0 15px 50px rgba(0,123,255,.08); border:1px solid rgba(0,123,255,.08); }
        .contact-form .form-control { border-radius:12px; border:1.5px solid rgba(0,123,255,.15); padding:12px 18px; font-size:.95rem; transition:border-color .3s,box-shadow .3s; }
        .contact-form .form-control:focus { border-color:#007bff; box-shadow:0 0 0 4px rgba(0,123,255,.1); }
        .contact-form label { font-weight:600; color:#0a1d37; margin-bottom:6px; }
        .btn-submit { background:linear-gradient(135deg,#007bff,#0056b3); color:#fff; border:none; border-radius:50px; padding:14px 40px; font-weight:700; font-size:1rem; width:100%; transition:all .3s; box-shadow:0 5px 20px rgba(0,123,255,.3); }
        .btn-submit:hover { transform:translateY(-3px); box-shadow:0 10px 30px rgba(0,123,255,.4); }

        .shimmer-badge { background:linear-gradient(90deg,#e0f0ff 25%,#b8daff 50%,#e0f0ff 75%); background-size:200% 100%; animation:shimmer 2.5s infinite; border-radius:50px; padding:6px 18px; font-size:.8rem; font-weight:600; color:#004aad; display:inline-block; margin-bottom:14px; }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }

        .social-link { width:44px; height:44px; border-radius:50%; background:#f0f7ff; display:inline-flex; align-items:center; justify-content:center; color:#007bff; font-size:1.1rem; transition:all .3s; text-decoration:none; margin:4px; }
        .social-link:hover { background:#007bff; color:#fff; transform:translateY(-4px); box-shadow:0 8px 20px rgba(0,123,255,.3); }

        .progress-wrap { left:30px !important; right:auto !important; }
        .header { transition:all .8s cubic-bezier(.4,0,.2,1) !important; }
        .header.fixed { background:rgba(255,255,255,.95) !important; backdrop-filter:blur(10px); box-shadow:0 4px 30px rgba(0,0,0,.05) !important; }
        /* WhatsApp FAB — matches chat FAB size & position */
        .whatsapp-fab { position:fixed; bottom:104px; right:24px; width:64px; height:64px; background:#25D366; border-radius:50% !important; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.6rem; text-decoration:none; z-index:9998; box-shadow:0 25px 50px -12px rgba(37,211,102,.4); transition:all .3s; animation:waPulse 2s infinite; }
        @media(min-width:768px){ .whatsapp-fab { bottom:112px; right:32px; } }
        .whatsapp-fab:hover { background:#128C7E !important; color:#fff; transform:scale(1.1); }
        @keyframes waPulse { 0%,100%{box-shadow:0 25px 50px -12px rgba(37,211,102,.4)} 50%{box-shadow:0 25px 50px -12px rgba(37,211,102,.6)} }
        /* Blue chat FAB — must be above WhatsApp FAB */
        .chat-widget-container { z-index: 9999 !important; }
        /* Force circular shape — Bootstrap template overrides Tailwind rounded-full */
        .rounded-full { border-radius: 9999px !important; }

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

	<div class="main-wrapper">
		<!-- Header -->
		<header class="header">
			<div class="container">
				<nav class="navbar navbar-expand-lg header-nav">
					<div class="navbar-header">
						<a id="mobile_btn" href="javascript:void(0);">
							<span class="bar-icon"><span></span><span></span><span></span></span>
						</a>
						<a href="{{ route('user.home') }}" class="navbar-brand logo">
							<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Logo" style="max-height: 45px;">
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
							<li>
								<a href="{{ route('user.home') }}">Beranda</a>
							</li>
							<li>
								<a href="{{ route('user.about') }}">Tentang Kami</a>
							</li>
                            <li class="active">
								<a href="{{ route('user.contact') }}">Kontak</a>
							</li>
						</ul>
					</div>
				</nav>
			</div>
		</header>

		<!-- Hero Section -->
		<section class="contact-hero">
			<div class="container" style="position:relative;z-index:2;">
				<div class="hero-badge">📞 Hubungi Tim Kami</div>
				<h1 class="display-3 fw-bold" style="color:white">Hubungi <span style="color:#90cdf4;">Kami</span></h1>
				<p style="color:rgba(255,255,255,.8);font-size:1.1rem;margin-top:10px;">Kami siap membantu Anda kapan saja — respons cepat, solusi tepat</p>
			</div>
		</section>

		<!-- Contact Info Section -->
		<section class="py-5" style="background:#f8fbff;">
			<div class="container">
				<div class="text-center mb-5 aos" data-aos="fade-up">
					<div class="shimmer-badge">📍 Info Kontak</div>
					<h2 style="color:#0a1d37;font-weight:800;">Cara Menghubungi Kami</h2>
					<p class="text-muted">Pilih metode kontak yang paling nyaman untuk Anda</p>
				</div>
				<div class="row g-4">
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="0">
						<div class="contact-card">
							<div class="contact-icon" style="background:linear-gradient(135deg,#007bff,#0056b3);"><i class="fas fa-map-marker-alt" style="color:#fff;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Alamat Kantor</h4>
							<p class="text-muted" style="line-height:1.7;">Grand Surapati Core Blok B 9-11 &amp; B 23-25, Jl. PHH. Mustofa No. 39, Pasirlayung, Kota Bandung, Jawa Barat 40192</p>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="150">
						<div class="contact-card">
							<div class="contact-icon" style="background:linear-gradient(135deg,#28a745,#20c997);"><i class="fas fa-phone-alt" style="color:#fff;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Telepon</h4>
							<p class="text-muted mb-2"><strong>Kantor:</strong> (022) 20510553</p>
							<p class="text-muted"><strong>WhatsApp:</strong> +62 812-3456-7890</p>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="300">
						<div class="contact-card">
							<div class="contact-icon" style="background:linear-gradient(135deg,#fd7e14,#dc3545);"><i class="fas fa-envelope" style="color:#fff;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Email</h4>
							<p class="text-muted mb-2">support@brillian.id</p>
							<p class="text-muted">info@best-world.id</p>
							<div class="mt-3">
								<a href="#" class="social-link"><i class="fab fa-facebook"></i></a>
								<a href="#" class="social-link"><i class="fab fa-instagram"></i></a>
								<a href="#" class="social-link"><i class="fab fa-twitter"></i></a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Map and Form Section -->
		<section class="py-5">
			<div class="container">
				<div class="row g-5">
					<div class="col-lg-6 aos" data-aos="fade-right">
						<div class="shimmer-badge">🗺️ Temukan Kami</div>
						<h2 class="fw-bold mb-4" style="color:#0a1d37;">Lokasi Kami</h2>
						<div class="map-container">
							<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.916123456789!2d107.643210!3d-6.891234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e798f0e12345%3A0x1234567890abcdef!2sSurapati%20Core!5e0!3m2!1sen!2sid!4v1234567890123" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
						</div>
					</div>
					<div class="col-lg-6 aos" data-aos="fade-left">
						<div class="shimmer-badge">✉️ Kirim Pesan</div>
						<h2 class="fw-bold mb-4" style="color:#0a1d37;">Hubungi Tim Kami</h2>
						<div class="contact-form">
							<div class="mb-4">
								<label>Nama Lengkap</label>
								<input type="text" class="form-control" id="contact-name" placeholder="Masukkan nama Anda">
							</div>
							<div class="mb-4">
								<label>Email</label>
								<input type="email" class="form-control" id="contact-email" placeholder="Masukkan email Anda">
							</div>
							<div class="mb-4">
								<label>Subjek</label>
								<input type="text" class="form-control" id="contact-subject" placeholder="Subjek pesan">
							</div>
							<div class="mb-4">
								<label>Pesan</label>
								<textarea class="form-control" id="contact-message" rows="4" placeholder="Tulis pesan Anda di sini..."></textarea>
							</div>
							<button type="button" class="btn-submit" id="submit-contact-btn">
								<i class="feather-send me-2"></i>Kirim Pesan
							</button>
						</div>
					</div>
				</div>
			</div>
		</section>

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
									<li><a href="{{ route('user.home') }}#produk">Produk</a></li>
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
	<a class="whatsapp-fab" href="https://wa.me/6283179191601" target="_blank" title="Chat via WhatsApp">
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
	<!-- Aos -->
	<script src="{{ asset('template/assets/plugins/aos/aos.js') }}"></script>
	<!-- Top JS -->
	<script src="{{ asset('template/assets/js/backToTop.js') }}"></script>
	<!-- Custom JS -->
	<script src="{{ asset('template/assets/js/script.js') }}"></script>

    @stack('scripts')
</body>
</html>