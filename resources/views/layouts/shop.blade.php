<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/logo-brilian-min2.png') }}">
	<title>@yield('title', 'BRILLIAN BIZ')</title>

	<!-- Bootstrap CSS -->
	<link rel="stylesheet" href="{{ asset('template/assets/css/bootstrap.min.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/fontawesome/css/fontawesome.min.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/fontawesome/css/all.min.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/css/feather.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/plugins/aos/aos.css') }}">
	<link rel="stylesheet" href="{{ asset('template/assets/css/style.css') }}">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        .hero-title { font-size: 3.5rem; font-weight: 800; color: #0a1d37; }
        .hero-main-card { background: #fff; border-radius: 24px; box-shadow: 0 25px 50px -12px rgba(10, 29, 55, 0.15); transition: transform 0.3s ease; border: 1px solid rgba(255,255,255,0.8); }
        .hero-main-card:hover { transform: translateY(-10px); }
        /* Minimalist Breadcrumb Styles */
        .breadcrumb-container {
            display: flex;
            align-items: center;
            padding: 1.5rem 0;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
        }
        .breadcrumb-list {
            display: flex;
            align-items: center;
            list-style: none !important;
            padding: 0;
            margin: 0;
            gap: 0.75rem;
        }
        .breadcrumb-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            list-style: none !important;
        }
        .breadcrumb-item::before {
            display: none !important;
            content: none !important;
        }
        .breadcrumb-link {
            text-decoration: none;
            color: #1e293b; /* Slate 800 - Abu-abu sangat gelap */
            font-weight: 500;
            font-size: 0.9375rem;
            transition: color 0.2s ease-in-out;
            cursor: pointer;
        }
        .breadcrumb-link:hover {
            color: #3b82f6; /* Blue 500 - Biru terang */
        }
        .breadcrumb-current {
            color: #94a3b8; /* Slate 400 - Abu-abu pudar */
            font-size: 0.9375rem;
            font-weight: 400;
            pointer-events: none;
        }
        .breadcrumb-home-icon {
            color: #94a3b8; /* Neutral Gray */
            transition: color 0.2s;
        }
        .breadcrumb-link:hover .breadcrumb-home-icon {
            color: #3b82f6;
        }
        .breadcrumb-separator {
            color: #cbd5e1; /* Slate 300 - Abu-abu netral kecil */
            flex-shrink: 0;
        }
        .category-display-title { font-size: 2.2rem; font-weight: 800; color: #0a1d37; letter-spacing: -0.5px; }
        .category-icon-wrap { width: 45px; height: 45px; background: #f0f7ff; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 1.2rem; border: 1px solid rgba(0, 123, 255, 0.1); }
        .title-separator { display: flex; gap: 4px; margin-top: 15px; }
        .title-separator .bar-long { width: 60px; height: 4px; background: #007bff; border-radius: 10px; }
        .title-separator .bar-short { width: 15px; height: 4px; background: #007bff; border-radius: 10px; opacity: 0.3; }
        .shimmer-badge { background: linear-gradient(90deg,#e0f0ff 25%,#b8daff 50%,#e0f0ff 75%); background-size: 200% 100%; animation: shimmer 2.5s infinite; border-radius: 50px; padding: 6px 16px; font-size: .8rem; font-weight: 600; color: #004aad; display: inline-block; }
        @keyframes shimmer { 0% { background-position: -200% 0; } 100% { background-position: 200% 0; } }
    </style>
</head>
<body class="antialiased">
	<div class="main-wrapper">
		<!-- Header -->
		<header class="header">
			<div class="container">
				<nav class="navbar navbar-expand-lg header-nav">
					<div class="navbar-header">
						<a href="{{ route('user.home') }}" class="navbar-brand logo">
							<img src="{{ asset('images/logo-brilian-min.png') }}" class="img-fluid" alt="Logo" style="max-height: 45px;">
						</a>
					</div>
					<div class="main-menu-wrapper">
						<ul class="main-nav">
							<li><a href="{{ route('user.home') }}">Beranda</a></li>
							<li><a href="{{ route('user.about') }}">Tentang Kami</a></li>
                            <li><a href="{{ route('user.contact') }}">Kontak</a></li>
						</ul>
					</div>
				</nav>
			</div>
		</header>
		
		<div class="container">
			@yield('content')
		</div>

		<!-- Footer -->
		<footer class="footer mt-5">
			<div class="footer-bottom">
				<div class="container text-center">
					<p class="mb-0 py-4">Copyright &copy; 2026 BRILLIAN BIZ. All Rights Reserved.</p>
				</div>
			</div>
		</footer>
	</div>

	<!-- JS -->
	<script src="{{ asset('template/assets/js/jquery-3.6.1.min.js') }}"></script>
	<script src="{{ asset('template/assets/js/bootstrap.bundle.min.js') }}"></script>
	<script src="{{ asset('template/assets/plugins/aos/aos.js') }}"></script>
	<script>AOS.init();</script>
</body>
</html>
