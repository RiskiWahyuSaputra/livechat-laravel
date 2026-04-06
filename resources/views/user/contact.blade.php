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
        .contact-hero {
            background: #0a1d37;
            padding: 80px 0;
            color: #fff;
            text-align: center;
        }
        .contact-card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            padding: 30px;
            height: 100%;
            background: #fff;
        }
        .contact-icon {
            width: 60px;
            height: 60px;
            background: #eef5ff;
            color: #007bff;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            margin-bottom: 20px;
        }
        .map-container {
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            height: 450px;
        }
    </style>
</head>

<body>

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
						<ul class="main-nav" style="margin: 0 auto !important; display: flex; float: none !important;">
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
			<div class="container">
				<h1 class="display-4 fw-bold text-white">Hubungi Kami</h1>
				<p class="lead text-secondary">Kami siap membantu Anda kapan saja</p>
			</div>
		</section>

		<!-- Contact Info Section -->
		<section class="py-5 bg-light">
			<div class="container">
				<div class="row g-4">
					<div class="col-md-4">
						<div class="contact-card">
							<div class="contact-icon"><i class="fas fa-map-marker-alt"></i></div>
							<h4 class="fw-bold">Alamat Kantor</h4>
							<p class="text-muted">Grand Surapati Core Blok B 9-11 & B 23-25, Jl. PHH. Mustofa No. 39, Pasirlayung, Kec. Cibeunying Kidul, Kota Bandung, Jawa Barat 40192</p>
						</div>
					</div>
					<div class="col-md-4">
						<div class="contact-card">
							<div class="contact-icon"><i class="fas fa-phone-alt"></i></div>
							<h4 class="fw-bold">Telepon</h4>
							<p class="text-muted">(022) 20510553</p>
							<p class="text-muted">WhatsApp: +62 812-3456-7890</p>
						</div>
					</div>
					<div class="col-md-4">
						<div class="contact-card">
							<div class="contact-icon"><i class="fas fa-envelope"></i></div>
							<h4 class="fw-bold">Email</h4>
							<p class="text-muted">support@brillian.id</p>
							<p class="text-muted">info@best-world.id</p>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Map and Form Section -->
		<section class="py-5">
			<div class="container">
				<div class="row g-5">
					<div class="col-lg-6">
						<h2 class="fw-bold mb-4">Lokasi Kami</h2>
						<div class="map-container">
							<iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.916123456789!2d107.643210!3d-6.891234!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e68e798f0e12345%3A0x1234567890abcdef!2sSurapati%20Core!5e0!3m2!1sen!2sid!4v1234567890123" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
						</div>
					</div>
					<div class="col-lg-6">
						<h2 class="fw-bold mb-4">Kirim Pesan</h2>
						<form class="p-4 bg-white shadow-sm rounded-4">
							<div class="mb-3">
								<label class="form-label fw-bold">Nama Lengkap</label>
								<input type="text" class="form-control rounded-3" placeholder="Masukkan nama Anda">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold">Email</label>
								<input type="email" class="form-control rounded-3" placeholder="Masukkan email Anda">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold">Subjek</label>
								<input type="text" class="form-control rounded-3" placeholder="Subjek pesan">
							</div>
							<div class="mb-3">
								<label class="form-label fw-bold">Pesan</label>
								<textarea class="form-control rounded-3" rows="5" placeholder="Tulis pesan Anda di sini..."></textarea>
							</div>
							<button type="submit" class="btn btn-primary w-100 rounded-pill py-2 fw-bold">Kirim Pesan</button>
						</form>
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