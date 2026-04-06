<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo-brilian-min2.png') }}">
	<title>Tentang Kami - BRILLIAN BIZ</title>

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
        .about-hero {
            background: linear-gradient(rgba(10, 29, 55, 0.8), rgba(10, 29, 55, 0.8)), url('{{ asset('images/gedung.png') }}');
            background-size: cover;
            background-position: center;
            padding: 120px 0;
            color: #fff;
            text-align: center;
        }
        .vision-mission-section {
            padding: 80px 0;
            background: #f8fbff;
        }
        .card-custom {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.05);
            transition: transform 0.3s;
            height: 100%;
        }
        .card-custom:hover {
            transform: translateY(-10px);
        }
        .section-title {
            color: #0a1d37;
            font-weight: 800;
            margin-bottom: 30px;
            position: relative;
            display: inline-block;
        }
        .section-title:after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 50px;
            height: 4px;
            background: #007bff;
            border-radius: 2px;
        }
        .value-icon {
            font-size: 3rem;
            color: #007bff;
            margin-bottom: 20px;
        }
    </style>
</head>

<body>

	<div class="main-wrapper">
		<!-- Header (Reused from Dashboard) -->
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
							<li class="active">
								<a href="{{ route('user.about') }}">Tentang Kami</a>
							</li>
                            <li>
								<a href="{{ route('user.contact') }}">Kontak</a>
							</li>
						</ul>
					</div>
				</nav>
			</div>
		</header>

		<!-- Hero Section -->
		<section class="about-hero">
			<div class="container">
				<h1 class="display-4 fw-bold text-white">Tentang BRILLIAN BIZ</h1>
				<p class="lead text-secondary">Membangun Ekosistem Bisnis Syariah Terbesar di Indonesia</p>
			</div>
		</section>

		<!-- Company Profile Section -->
		<section class="py-5">
			<div class="container">
				<div class="row align-items-center">
					<div class="col-lg-6">
						<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid mb-4" alt="Company Image" style="max-height: 300px;">
					</div>
					<div class="col-lg-6">
						<h2 class="section-title text-start mb-4">Profil Perusahaan</h2>
						<p class="text-muted">BRILLIAN BIZ (PT Bandung Eco Sinergi Teknologi) adalah perusahaan penjualan langsung (Direct Selling) yang berkomitmen untuk membantu masyarakat mencapai kebebasan finansial melalui sistem bisnis yang adil dan sesuai dengan prinsip syariah.</p>
						<p class="text-muted">Kami hadir sebagai solusi bagi mereka yang ingin memiliki bisnis sendiri dengan modal terjangkau namun memiliki potensi penghasilan yang luar biasa. Produk-produk kami telah teruji kualitasnya dan sangat dibutuhkan oleh masyarakat luas.</p>
					</div>
				</div>
			</div>
		</section>

		<!-- Vision & Mission -->
		<section class="vision-mission-section">
			<div class="container">
				<div class="text-center mb-5">
					<h2 class="section-title">Visi & Misi</h2>
				</div>
				<div class="row g-4">
					<div class="col-md-6">
						<div class="card card-custom p-4">
							<div class="card-body">
								<h3 class="h4 fw-bold text-primary mb-3">Visi Kami</h3>
								<p>Menjadi perusahaan Direct Selling terdepan yang menyediakan platform bagi masyarakat untuk meraih kesuksesan dan kemandirian finansial melalui produk berkualitas dan model bisnis berkelanjutan.</p>
							</div>
						</div>
					</div>
					<div class="col-md-6">
						<div class="card card-custom p-4">
							<div class="card-body">
								<h3 class="h4 fw-bold text-primary mb-3">Misi Kami</h3>
								<ul class="list-unstyled">
									<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Memasarkan produk berkualitas tinggi yang esensial bagi masyarakat.</li>
									<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Menyediakan peluang bisnis yang inklusif bagi semua kalangan.</li>
									<li class="mb-2"><i class="fas fa-check-circle text-success me-2"></i> Menjalankan program pemasaran sesuai dengan prinsip syariah DSN-MUI.</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Core Values -->
		<section class="py-5">
			<div class="container">
				<div class="text-center mb-5">
					<h2 class="section-title">Nilai-Nilai Kami</h2>
				</div>
				<div class="row text-center g-4">
					<div class="col-md-4">
						<div class="value-icon"><i class="fas fa-star-and-crescent"></i></div>
						<h4 class="fw-bold">Integritas Syariah</h4>
						<p class="text-muted">Berkomitmen penuh pada sistem Penjualan Langsung Berjenjang Syariah (PLBS).</p>
					</div>
					<div class="col-md-4">
						<div class="value-icon"><i class="fas fa-users"></i></div>
						<h4 class="fw-bold">Inklusivitas</h4>
						<p class="text-muted">Bisnis untuk semua orang tanpa memandang latar belakang pendidikan atau ekonomi.</p>
					</div>
					<div class="col-md-4">
						<div class="value-icon"><i class="fas fa-handshake"></i></div>
						<h4 class="fw-bold">Pemberdayaan</h4>
						<p class="text-muted">Membantu mitra meraih sukses melalui edukasi dan sistem yang terstruktur.</p>
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