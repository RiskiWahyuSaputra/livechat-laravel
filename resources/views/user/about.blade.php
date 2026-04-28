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
        @import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;800;900&display=swap');
        body { font-family:'Inter',sans-serif; }
        .about-hero { background:linear-gradient(135deg,rgba(10,29,55,.92),rgba(0,90,200,.85)), url('{{ asset('images/gedung.png') }}') center/cover; padding:140px 0 100px; color:#fff; text-align:center; position:relative; overflow:hidden; }
        .about-hero::after { content:''; position:absolute; bottom:0; left:0; right:0; height:80px; background:linear-gradient(to top,#f8fbff,transparent); }
        .hero-badge { background:rgba(255,255,255,.15); border:1px solid rgba(255,255,255,.3); backdrop-filter:blur(10px); border-radius:50px; padding:8px 20px; font-size:.85rem; font-weight:600; display:inline-block; margin-bottom:20px; }
        .about-hero h1 { font-size:3.2rem; font-weight:900; text-shadow:0 2px 20px rgba(0,0,0,.3); }
        .about-hero p { color:rgba(255,255,255,.8); font-size:1.1rem; }

        .vision-mission-section { padding:90px 0; background:#f8fbff; }
        .card-custom { border:none; border-radius:24px; box-shadow:0 15px 40px rgba(0,123,255,.08); transition:transform .35s,box-shadow .35s; height:100%; border:1px solid rgba(0,123,255,.08); }
        .card-custom:hover { transform:translateY(-12px); box-shadow:0 25px 60px rgba(0,123,255,.15); }
        .card-icon-wrap { width:64px; height:64px; border-radius:18px; background:linear-gradient(135deg,#007bff,#0056b3); display:flex; align-items:center; justify-content:center; font-size:1.6rem; color:#fff; margin-bottom:20px; box-shadow:0 8px 20px rgba(0,123,255,.3); }

        .section-title { color:#0a1d37; font-weight:800; margin-bottom:30px; position:relative; display:inline-block; }
        .section-title::after { content:''; position:absolute; bottom:-10px; left:50%; transform:translateX(-50%); width:50px; height:4px; background:linear-gradient(90deg,#007bff,#0056b3); border-radius:2px; }

        .value-icon { font-size:3rem; color:#007bff; margin-bottom:20px; }
        .value-card { padding:35px 25px; border-radius:20px; text-align:center; transition:all .35s; border:1px solid rgba(0,123,255,.08); }
        .value-card:hover { background:#fff; box-shadow:0 20px 50px rgba(0,123,255,.12); transform:translateY(-8px); }
        .value-icon-wrap { width:80px; height:80px; border-radius:50%; margin:0 auto 20px; display:flex; align-items:center; justify-content:center; font-size:2rem; }

        .achievement-section { padding:80px 0; background:linear-gradient(135deg,#0a1d37,#1a3a6e); position:relative; overflow:hidden; }
        .achievement-section::before { content:''; position:absolute; top:-100px; right:-100px; width:350px; height:350px; border-radius:50%; background:rgba(255,255,255,.04); }
        .achieve-card { text-align:center; padding:30px 15px; }
        .achieve-num { font-size:3rem; font-weight:900; background:linear-gradient(135deg,#fff,#90cdf4); -webkit-background-clip:text; -webkit-text-fill-color:transparent; line-height:1; }
        .achieve-label { color:rgba(255,255,255,.7); margin-top:8px; font-size:.9rem; }

        .shimmer-badge { background:linear-gradient(90deg,#e0f0ff 25%,#b8daff 50%,#e0f0ff 75%); background-size:200% 100%; animation:shimmer 2.5s infinite; border-radius:50px; padding:6px 18px; font-size:.8rem; font-weight:600; color:#004aad; display:inline-block; margin-bottom:14px; }
        @keyframes shimmer { 0%{background-position:-200% 0} 100%{background-position:200% 0} }

        .progress-wrap { left:30px !important; right:auto !important; }
        .header { transition:all .8s cubic-bezier(.4,0,.2,1) !important; }
        .header.fixed { background:rgba(255,255,255,.95) !important; backdrop-filter:blur(10px); box-shadow:0 4px 30px rgba(0,0,0,.05) !important; }
        /* WhatsApp FAB — matches chat FAB size & position */
        .whatsapp-fab { position:fixed; bottom:104px; right:24px; width:64px; height:64px; background:#25D366; border-radius:50% !important; display:flex; align-items:center; justify-content:center; color:#fff; font-size:1.6rem; text-decoration:none; z-index:9998; box-shadow:0 25px 50px -12px rgba(37,211,102,.4); transition:all .3s; animation:waPulse 2s infinite; }
        @media(min-width:768px){ .whatsapp-fab { bottom:112px; right:32px; } }
        .whatsapp-fab:hover { background:#128C7E !important; color:#fff; transform:scale(1.1); }
        @keyframes waPulse { 0%,100%{box-shadow:0 25px 50px -12px rgba(37,211,102,.4)} 50%{box-shadow:0 25px 50px -12px rgba(37,211,102,.6)} }
    </style>
</head>

<body x-data="chatWidget()" x-init="initWidget()" class="antialiased">

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
				<div class="hero-badge">🌟 Perusahaan Direct Selling Syariah</div>
				<h1 class="display-3 fw-bold" style="color:white">Tentang <span style="color:#90cdf4;">BRILLIAN BIZ</span></h1>
				<p class="lead">Membangun Ekosistem Bisnis Syariah Terbesar di Indonesia</p>
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

		<!-- Achievement Stats -->
		<section class="achievement-section">
			<div class="container">
				<div class="row g-4 justify-content-center">
					<div class="col-6 col-md-3"><div class="achieve-card"><div class="achieve-num" data-count="10000">0</div><div class="achieve-label">Mitra Aktif</div></div></div>
					<div class="col-6 col-md-3"><div class="achieve-card"><div class="achieve-num" data-count="8">0</div><div class="achieve-label">Tahun Berpengalaman</div></div></div>
					<div class="col-6 col-md-3"><div class="achieve-card"><div class="achieve-num" data-count="50">0</div><div class="achieve-label">Produk Unggulan</div></div></div>
					<div class="col-6 col-md-3"><div class="achieve-card"><div class="achieve-num" data-count="34">0</div><div class="achieve-label">Provinsi</div></div></div>
				</div>
			</div>
		</section>

		<!-- Vision & Mission -->
		<section class="vision-mission-section">
			<div class="container">
				<div class="text-center mb-5 aos" data-aos="fade-up">
					<div class="shimmer-badge">🎯 Arah & Tujuan</div>
					<h2 class="section-title">Visi &amp; Misi</h2>
				</div>
				<div class="row g-4">
					<div class="col-md-6 aos" data-aos="fade-right">
						<div class="card card-custom p-4">
							<div class="card-body">
								<div class="card-icon-wrap"><i class="fas fa-eye"></i></div>
								<h3 class="h4 fw-bold text-primary mb-3">Visi Kami</h3>
								<p style="color:#555;line-height:1.8;">Menjadi perusahaan Direct Selling terdepan yang menyediakan platform bagi masyarakat untuk meraih kesuksesan dan kemandirian finansial melalui produk berkualitas dan model bisnis berkelanjutan.</p>
							</div>
						</div>
					</div>
					<div class="col-md-6 aos" data-aos="fade-left">
						<div class="card card-custom p-4">
							<div class="card-body">
								<div class="card-icon-wrap" style="background:linear-gradient(135deg,#28a745,#20c997);"><i class="fas fa-rocket"></i></div>
								<h3 class="h4 fw-bold text-primary mb-3">Misi Kami</h3>
								<ul class="list-unstyled" style="color:#555;">
									<li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Memasarkan produk berkualitas tinggi yang esensial bagi masyarakat.</li>
									<li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Menyediakan peluang bisnis yang inklusif bagi semua kalangan.</li>
									<li class="mb-3"><i class="fas fa-check-circle text-success me-2"></i>Menjalankan program pemasaran sesuai prinsip syariah DSN-MUI.</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>

		<!-- Core Values -->
		<section class="py-5" style="background:#f8fbff;">
			<div class="container">
				<div class="text-center mb-5 aos" data-aos="fade-up">
					<div class="shimmer-badge">💎 Fondasi Kami</div>
					<h2 class="section-title">Nilai-Nilai Kami</h2>
				</div>
				<div class="row text-center g-4">
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="0">
						<div class="value-card">
							<div class="value-icon-wrap" style="background:linear-gradient(135deg,#e0f0ff,#b8daff);"><i class="fas fa-star-and-crescent" style="color:#007bff;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Integritas Syariah</h4>
							<p class="text-muted">Berkomitmen penuh pada sistem Penjualan Langsung Berjenjang Syariah (PLBS).</p>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="150">
						<div class="value-card">
							<div class="value-icon-wrap" style="background:linear-gradient(135deg,#d4edda,#a8d5b5);"><i class="fas fa-users" style="color:#28a745;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Inklusivitas</h4>
							<p class="text-muted">Bisnis untuk semua orang tanpa memandang latar belakang pendidikan atau ekonomi.</p>
						</div>
					</div>
					<div class="col-md-4 aos" data-aos="fade-up" data-aos-delay="300">
						<div class="value-card">
							<div class="value-icon-wrap" style="background:linear-gradient(135deg,#fff3cd,#ffd580);"><i class="fas fa-handshake" style="color:#fd7e14;"></i></div>
							<h4 class="fw-bold" style="color:#0a1d37;">Pemberdayaan</h4>
							<p class="text-muted">Membantu mitra meraih sukses melalui edukasi dan sistem yang terstruktur.</p>
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
    <script>
    // Counter animation for about page
    function animateCount(el) {
        const t = +el.getAttribute('data-count'), d = 1800, s = t/(d/16); let c=0;
        const ti = setInterval(()=>{ c+=s; if(c>=t){c=t;clearInterval(ti);} el.textContent=t>=1000?Math.floor(c).toLocaleString('id-ID')+'+':Math.floor(c)+'+'; },16);
    }
    const obs = new IntersectionObserver(e=>e.forEach(x=>{if(x.isIntersecting){animateCount(x.target);obs.unobserve(x.target);}}),{threshold:.5});
    document.querySelectorAll('.achieve-num[data-count]').forEach(el=>obs.observe(el));
    </script>
</body>
</html>