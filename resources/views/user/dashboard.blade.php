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
    </style>
</head>

<body x-data="chatWidget()" x-init="initWidget()">

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
						<ul class="main-nav">
							<li class="active">
								<a href="{{ route('user.home') }}">Beranda</a>
							</li>
							<li>
								<a href="#solusi">Solusi</a>
							</li>
                            <li>
								<a href="#produk">Produk</a>
							</li>
							<li class="has-submenu">
								<a href="javascript:void(0);">Pages <i class="fas fa-chevron-down"></i></a>
								<ul class="submenu">
									<li><a href="javascript:void(0);">About Us</a></li>
									<li><a href="javascript:void(0);">Contact Us</a></li>
								</ul>
							</li>
						</ul>
					</div>
					<ul class="nav header-navbar-rht" x-show="isInitialized" x-cloak>
                        <template x-if="!isAuthenticated || user.name === 'Guest'">
                            <li class="nav-item">
                                <a class="nav-link header-reg" href="javascript:void(0);" @click="isOpen = true; showRegForm = true">Register Chat</a>
                            </li>
                        </template>
                        <template x-if="isAuthenticated && user.name !== 'Guest'">
                            <li class="nav-item dropdown has-arrow account-item">
                                <a href="#" class="dropdown-toggle nav-link" data-bs-toggle="dropdown">
                                    <div class="user-infos">
                                        <span class="user-img">
                                            <div class="w-8 h-8 rounded-circle bg-[#0a1d37] flex items-center justify-center font-bold text-white shadow-md border-2 border-white text-sm" style="width: 35px; height: 35px; display: flex; align-items: center; justify-content: center; border-radius: 50%;">
                                                <span x-text="user.initial"></span>
                                            </div>
                                        </span>
                                        <div class="user-info">
                                            <h6 x-text="user.name"></h6>
                                            <p>Customer</p>
                                        </div>
                                    </div>
                                </a>
                                <div class="dropdown-menu dropdown-menu-end emp">
                                    <form method="POST" action="{{ route('chat.logout') }}">
                                        @csrf
                                        <button type="submit" class="dropdown-item">
                                            <i class="feather-log-out me-2"></i> Logout
                                        </button>
                                    </form>
                                </div>
                            </li>
                        </template>
					</ul>
				</nav>
			</div>
		</header>
		<!-- /Header -->
		
		<!-- Hero Section -->
		<section class="hero-section">			
			<div class="container">
				<div class="home-banner">
					<div class="row align-items-center w-100">
						<div class="col-lg-7 col-md-10 mx-auto">
							<div class="section-search aos" data-aos="fade-up">
								<h1>Wujudkan <span class="text-primary">Kebebasan</span> Finansial Anda.</h1>
								<p>Selamat datang di portal dukungan BRILLIAN BIZ. Kami menyediakan ekosistem bisnis syariah.</p>
								<div class="search-box">
                                    <div class="search-btn w-100">
                                        <button class="btn btn-primary w-100" type="button" @click="isOpen = true"><i class="feather-message-square me-2"></i>Chat dengan Kami</button>
                                    </div>
								</div>
							</div>
						</div>
						<div class="col-lg-5">
							<div class="banner-imgs">
								<div class="banner-1 shape-1">
									<img class="img-fluid" alt="banner" src="{{ asset('images/logo-biz.png') }}">
								</div>
								<div class="banner-2 shape-3">
									<img class="img-fluid" alt="banner" src="{{ asset('images/seminar.png') }}">
								</div>
								<div class="banner-3 shape-3">
									<img class="img-fluid" alt="banner" src="{{ asset('images/produk-best.png') }}">
								</div>
								<div class="banner-4 shape-2">
									<img class="img-responsive" alt="banner" src="{{ asset('images/gedung.png') }}">
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
									<li><a href="javascript:void(0);">Tentang Kami</a></li>
									<li><a href="javascript:void(0);">Kontak</a></li>
									<li><a href="javascript:void(0);">Produk</a></li>
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

    <!-- Chat Widget Container -->
    <div class="fixed bottom-6 left-6 md:bottom-8 md:left-8 z-50 flex flex-col items-start chat-widget-container">
        
        <!-- Chat Popup Window -->
        <div x-show="isOpen" x-cloak
             x-transition:enter="transition ease-out duration-300 transform origin-bottom-left"
             x-transition:enter-start="opacity-0 scale-50 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform origin-bottom-left"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-50 translate-y-4"
             class="bg-white w-[340px] sm:w-[380px] h-[500px] max-h-[75vh] rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden mb-4 relative"
             style="display: none;">
            
            <!-- Loading Overlay -->
            <div x-show="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
            </div>

            <!-- Header -->
            <header class="bg-white px-3 py-2 flex items-center justify-between shrink-0 shadow-sm relative border-b border-slate-100" style="background: white !important;">
                <div class="absolute top-0 left-0 right-0 h-1 bg-blue-600"></div>
                <div class="flex items-center gap-2.5 mt-0.5">
                    <div class="w-8 h-8 rounded-lg bg-[#0a1d37] flex items-center justify-center shadow-sm">
                        <span class="font-black text-white text-base">CS</span>
                    </div>
                    <div>
                        <h3 class="font-black text-[#0a1d37] text-xs leading-tight">Layanan Pelanggan</h3>
                        <div class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                            <span class="flex items-center gap-1 shrink-0"
                                :class="{
                                    'text-blue-500': status === 'pending' || status === 'queued',
                                    'text-emerald-500': status === 'active',
                                    'text-slate-400': status === 'closed'
                                }">
                                <div class="w-1.5 h-1.5 rounded-full"
                                    :class="{
                                        'bg-blue-500 animate-pulse': status === 'pending' || status === 'queued',
                                        'bg-emerald-500': status === 'active',
                                        'bg-slate-400': status === 'closed'
                                    }"></div>
                                <span x-text="statusText"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Messages Area (Show if chatting and NOT in registration form) -->
            <div x-show="isChatting && !showRegForm" id="widget-messages-container" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 relative">
                <div class="flex justify-between items-center mb-4">
                    <button @click="isChatting = false" class="text-[10px] font-bold text-blue-600 hover:underline flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Menu Utama
                    </button>
                    <span class="text-slate-400 font-medium text-[10px]">Percakapan Dimulai</span>
                </div>

                <template x-for="(msg, index) in messages" :key="msg.id || msg.temp_id">
                    <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                        
                        <!-- System Message -->
                        <template x-if="msg.sender_type === 'system'">
                            <div class="w-full flex justify-center my-2">
                                <div class="bg-blue-50 text-blue-800 text-[10px] px-3 py-1.5 rounded-lg border border-blue-100 text-center max-w-[85%] shadow-sm">
                                    <span class="block font-medium" x-text="msg.content"></span>
                                </div>
                            </div>
                        </template>

                        <!-- Normal Text Bubble -->
                        <template x-if="msg.sender_type !== 'system'">
                            <div class="max-w-[88%] flex flex-col min-w-0" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                                <span x-show="msg.sender_type !== 'user'" class="text-[10px] text-slate-400 font-medium mb-0.5 ml-1">Live Support</span>

                                <div class="px-3 py-2 md:px-3.5 md:py-2.5 rounded-2xl text-[13px] leading-relaxed shadow-sm relative overflow-hidden min-w-0 max-w-full"
                                    :class="msg.sender_type === 'user' 
                                        ? 'bg-blue-600 text-white rounded-br-sm' 
                                        : 'bg-white text-slate-800 rounded-bl-sm border border-slate-200'">

                                    <!-- Pesan Teks -->
                                    <template x-if="!msg.message_type || msg.message_type === 'text'">
                                        <div class="break-words">
                                            <div x-html="formatMessage(msg.content)"></div>
                                        </div>
                                    </template>

                                    <!-- Pesan Gambar -->
                                    <template x-if="msg.message_type === 'image'">
                                        <div class="max-w-full">
                                            <div class="space-y-2">
                                                <img :src="msg.content" 
                                                     class="rounded-lg max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity min-h-[50px] bg-slate-100 object-cover" 
                                                     @click="window.open(msg.content, '_blank')"
                                                     x-on:error="$el.src='https://placehold.co/200x150?text=Gambar+Gagal+Dimuat'">
                                            </div>
                                        </div>
                                    </template>

                                    <!-- Pesan File -->
                                    <template x-if="msg.message_type === 'file'">
                                        <div class="w-full min-w-0">
                                            <div class="flex items-center gap-2 min-w-0">
                                                <div class="w-8 h-8 rounded-lg bg-slate-100/20 flex items-center justify-center text-current shrink-0 border border-white/10">
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                                </div>
                                                <div class="flex-1 min-w-0">
                                                    <p class="text-[11px] font-bold truncate leading-tight mb-1" x-text="msg.content.split('/').pop()"></p>
                                                    <a :href="msg.content" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider hover:opacity-80" :class="msg.sender_type === 'user' ? 'text-white underline' : 'text-blue-600 underline'">
                                                        <span>Unduh</span>
                                                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                    </a>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                </div>
                                <span class="text-[9px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>

                                <!-- Bot Categories Inline -->
                                <template x-if="msg.sender_id == 0 && index === messages.length - 1">
                                    <div class="mt-2 flex flex-wrap gap-1.5 w-full">
                                        <!-- Phase: awaiting_category -->
                                        <template x-if="botPhase === 'awaiting_category'">
                                            <template x-for="cat in botCategories" :key="cat">
                                                <button @click="selectCategory(cat)" 
                                                        class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 min-w-[100px] text-center">
                                                    <span x-text="cat"></span>
                                                </button>
                                            </template>
                                        </template>

                                        <!-- Phase: awaiting_cs_type -->
                                        <template x-if="botPhase === 'awaiting_cs_type'">
                                            <div class="flex flex-wrap gap-1.5 w-full">
                                                <button @click="newMessage = 'Customer service'; sendMessage()" 
                                                        class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 text-center">
                                                    Customer service
                                                </button>
                                                <button @click="newMessage = 'CS Voucher'; sendMessage()" 
                                                        class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 text-center">
                                                    CS Voucher
                                                </button>
                                            </div>
                                        </template>

                                        <!-- Phase: awaiting_submenu (Dynamic) -->
                                        <template x-if="botPhase === 'awaiting_submenu'">
                                            <div class="flex flex-wrap gap-1.5 w-full">
                                                <template x-for="child in botSubmenus" :key="child.id">
                                                    <button @click="handleSubmenuClick(child)" 
                                                            class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 min-w-[100px] text-center">
                                                        <span x-text="child.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Phase: awaiting_main_menu -->
                                        <template x-if="botPhase === 'awaiting_main_menu'">
                                            <div class="flex flex-wrap gap-1.5 w-full">
                                                <template x-for="item in chat_main_menu" :key="item.id">
                                                    <button @click="handleMenuClick(item.id)" 
                                                            class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 min-w-[100px] text-center">
                                                        <span x-text="item.label"></span>
                                                    </button>
                                                </template>
                                            </div>
                                        </template>

                                        <!-- Phase: offer_agent_transfer -->
                                        <template x-if="botPhase === 'offer_agent_transfer'">
                                            <div class="flex flex-col sm:flex-row gap-1.5 w-full">
                                                <button @click="newMessage = 'LANJUT'; sendMessage()" 
                                                        class="px-2 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 text-center flex items-center justify-center gap-1.5">
                                                    <i class="fas fa-comment-dots"></i> Tanya Lagi
                                                </button>
                                                <button @click="newMessage = 'AGENT'; sendMessage()" 
                                                        class="px-2 py-1.5 bg-blue-600 hover:bg-blue-700 text-white border border-blue-700 rounded-full text-[9px] font-bold transition-all shadow-sm flex-1 text-center flex items-center justify-center gap-1.5">                                                    <i class="fas fa-headset"></i> Hubungi Agent
                                                </button>
                                            </div>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </template>
                <div id="widget-scroll-anchor" class="h-1"></div>
            </div>

            <!-- Registration & Greeting -->
            <div x-show="!isChatting || showRegForm" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col">
                <!-- Step 1: Greeting & Buttons -->
                <div x-show="!showRegForm" class="flex-1 flex flex-col justify-center">
                    <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-6">
                        <div class="flex items-start gap-3">
                            <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center shrink-0">
                                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            </div>
                            <div>
                                <p class="text-sm text-slate-700 leading-relaxed" x-text="chat_greeting"></p>
                            </div>
                        </div>
                    </div>

                    <!-- Loading state saat menu belum siap -->
                    <div x-show="chat_main_menu.length === 0 && !isInitialized" class="flex items-center justify-center py-6">
                        <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                    </div>

                    <!-- Debug info -->
                    <div class="text-xs text-slate-500 mb-2">
                        Debug: isInitialized: <span x-text="isInitialized"></span>, 
                        menu length: <span x-text="chat_main_menu.length"></span>,
                        showRegForm: <span x-text="showRegForm"></span>
                    </div>

                    <div class="grid grid-cols-1 gap-1.5">
                        <template x-for="item in chat_main_menu" :key="item.id">
                            <button @click="handleMenuClick(item.id)" 
                                    class="w-full text-left px-3.5 py-2.5 bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-600 border border-slate-200 hover:border-blue-300 rounded-2xl text-[12px] font-bold transition-all shadow-sm flex items-center justify-between group">
                                <span x-text="item.label"></span>
                                <svg class="w-3.5 h-3.5 opacity-0 group-hover:opacity-100 transition-opacity" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                            </button>
                        </template>
                        <div x-show="chat_main_menu.length === 0" class="text-xs text-blue-500">
                            Menu belum dimuat atau kosong
                        </div>
                    </div>
                </div>

                <!-- Step 2: Data Entry -->
                <div x-show="showRegForm" x-cloak class="flex-1 flex flex-col justify-center">
                    <button x-show="!isAuthenticated" @click="showRegForm = false" class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-4 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Kembali ke Menu
                    </button>
                    <!-- Tombol Batal untuk kembali membatalkan ke CS jika sudah Auth -->
                    <button x-show="isAuthenticated" @click="cancelRegistration" class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-4 transition-colors">
                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                        Batal
                    </button>
                    
                    <div class="text-center mb-6">
                        <h4 class="font-bold text-slate-900">Lengkapi Data Diri</h4>
                        <p class="text-xs text-slate-500 mt-1">Satu langkah lagi untuk terhubung dengan kami.</p>
                    </div>

                    <form @submit.prevent="submitRegistration" class="space-y-4">
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-blue-500">*</span></label>
                            <input type="text" x-model="regForm.name" required class="form-control" placeholder="Masukkan nama Anda" style="border-radius: 12px;">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">No. Handphone <span class="text-blue-500">*</span></label>
                            <input type="text" x-model="regForm.contact" required class="form-control" placeholder="Contoh: 08123456789" style="border-radius: 12px;">
                        </div>
                        <div>
                            <label class="block text-xs font-semibold text-slate-700 mb-1">Asal / Instansi <span class="text-blue-500">*</span></label>
                            <input type="text" x-model="regForm.origin" required class="form-control" placeholder="Nama perusahaan atau asal Anda" style="border-radius: 12px;">
                        </div>

                        <button type="submit" :disabled="isLoading" class="btn btn-primary w-100 py-2 mt-2" style="border-radius: 12px; font-weight: bold;">
                            <span x-show="!isLoading">Mulai Chat</span>
                            <div x-show="isLoading" class="spinner-border spinner-border-sm" role="status"></div>
                        </button>
                        
                        <div x-show="regError" x-text="regError" class="text-xs text-danger text-center font-medium mt-2"></div>
                    </form>
                </div>
            </div>

            <!-- Typing Indicator & Footer -->
            <div x-show="isChatting && !showRegForm" class="shrink-0 bg-white">
                <div x-show="isTyping" x-cloak class="px-4 py-1.5 flex items-center gap-2 bg-slate-50/80 border-t border-slate-100">
                    <span class="text-[10px] italic text-slate-400 font-medium" x-text="typingMessage"></span>
                    <div class="flex gap-1">
                        <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                        <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                        <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                    </div>
                </div>

                <div x-show="status === 'closed'" x-cloak class="bg-slate-100 text-slate-500 text-xs text-center p-2.5 border-t border-slate-200 font-medium">
                    Sesi pertanyaan ini telah ditutup oleh agen.
                </div>

                <form @submit.prevent="sendMessage" 
                      x-show="status !== 'closed'" class="border-t border-slate-200 p-2.5 bg-white flex items-end gap-2 relative">
                    <button type="button" 
                            @click="$refs.fileInput.click()"
                            class="btn btn-light shrink-0 w-10 h-10 d-flex align-items-center justify-center"
                            title="Unggah Gambar atau File" style="border-radius: 12px;">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    <input type="file" x-ref="fileInput" class="hidden" @change="uploadFile">

                    <textarea x-model="newMessage" 
                            x-ref="messageInput"
                            @input="sendTypingEvent"
                            @keydown.enter.prevent="if(!event.shiftKey) sendMessage()"
                            placeholder="Ketik balasan Anda..." 
                            class="form-control flex-1 resize-none"
                            style="border-radius: 12px; background: #f8f9fa; border: none;"
                            rows="1"></textarea>
                    <button type="submit" 
                            :disabled="!newMessage.trim() || isSending || isLoading"
                            class="btn btn-primary shrink-0 w-10 h-10 d-flex align-items-center justify-center" style="border-radius: 12px;">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Float Button (FAB) -->
        <button @click="toggleChat" 
           class="fixed bottom-8 left-8 w-16 h-16 rounded-full bg-blue-600 fab-pulse flex items-center justify-center text-white shadow-2xl shadow-blue-600/40 hover:bg-blue-700 transition-all transform hover:scale-110 active:scale-95 z-[60] group"
           style="border-radius: 50% !important;"
           :aria-label="isOpen ? 'Tutup Chat' : 'Buka Chat'">
            <svg x-show="!isOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <svg x-show="isOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            <!-- Unread Badge -->
            <div x-show="unreadCount > 0 && !isOpen" class="absolute -top-2 -right-2 bg-blue-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold border-2 border-white">
                <span x-text="unreadCount"></span>
            </div>
        </button>
    </div>

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

    <script>
        document.addEventListener('alpine:init', () => {
            console.log('Alpine.js initialized');
            Alpine.data('chatWidget', () => ({
                open: false,
                isOpen: false,
                isLoading: false,
                isInitialized: false,
                isAuthenticated: {{ $isAuthenticated ? 'true' : 'false' }},
                csrfToken: '{{ csrf_token() }}',
                user: {
                    name: @json(Auth::check() ? Auth::user()->name : ($isAuthenticated ? 'Guest' : '')),
                    initial: '{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : ($isAuthenticated ? 'G' : '') }}'
                },

                // Form Data
                regForm: {
                    name: '',
                    contact: '',
                    origin: ''
                },
                selectedOption: null,
                showRegForm: false,
                regError: '',
                chat_greeting: '{!! addslashes(\App\Models\Setting::get("bot_greeting_message", "Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?")) !!}',
                chat_main_menu: [],

                // Chat Data
                messages: [],
                newMessage: '',
                isSending: false,
                conversationId: null,
                userId: null,
                status: 'pending',
                unreadCount: 0,
                isTyping: false,
                isChatting: false,
                typingMessage: '',
                typingTimeout: null,

                // Bot Settings
                botPhase: 'off',
                botCategories: ['Pertanyaan Umum', 'Masalah Teknis', 'Layanan Produk', 'Lainnya'],
                botSubmenus: [],

                initWidget() {
                    console.log('initWidget called');
                    this.fetchChatData();
                    
                    // Polling sederhana untuk pesan baru jika Echo tidak tersedia
                    setInterval(() => {
                        if (this.isAuthenticated && !window.Echo) {
                            this.fetchChatData();
                        }
                    }, 30000);
                },

                toggleChat() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen) {
                        this.unreadCount = 0;
                        this.$nextTick(() => {
                            this.scrollToBottom();
                            if (this.$refs.messageInput) this.$refs.messageInput.focus();
                        });
                    }
                },

                formatMessage(content) {
                    if (!content) return '';
                    return content.replace(/\n/g, '<br>');
                },

                get statusText() {
                    switch(this.status) {
                        case 'pending': return 'Menunggu';
                        case 'queued': return 'Dalam Antrian';
                        case 'active': return 'Terhubung';
                        case 'closed': return 'Selesai';
                        default: return 'Online';
                    }
                },

                async handleMenuClick(id) {
                    console.log("Menu clicked:", id);
                    this.isLoading = true;
                    try {
                        this.selectedOption = id;
                        const menu = this.chat_main_menu.find(m => m.id == id);
                        if (!menu) {
                            console.error("Menu not found for ID:", id);
                            return;
                        }
                        console.log("Menu action type:", menu.action_type);
                        
                        const actionType = menu.action_type || 'connect_cs';
                        
                        // Aktifkan mode chat agar bubble pesan terlihat
                        this.isChatting = true;
                        
                        if (actionType === 'submenu') {
                            this.messages.push({
                                id: 'local-user-' + Date.now(),
                                sender_type: 'user',
                                content: "Saya memilih: " + menu.label,
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });

                            setTimeout(() => {
                                this.messages.push({
                                    id: 'local-bot-' + Date.now(),
                                    sender_id: 0,
                                    sender_type: 'admin',
                                    content: menu.message_response || "Pilih layanan yang Anda inginkan:",
                                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                                });

                                this.botSubmenus = menu.submenus || [];
                                this.botPhase = 'awaiting_submenu';
                                this.scrollToBottom();
                            }, 400);
                            
                        } else if (actionType === 'link') {
                            this.messages.push({
                                id: 'local-user-' + Date.now(),
                                sender_type: 'user',
                                content: "Saya ingin melihat: " + menu.label,
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });

                            setTimeout(() => {
                                let content = menu.message_response || "Memproses permintaan Anda...";
                                if (menu.action_value) {
                                    const isYoutube = menu.action_value.toLowerCase().includes('youtube.com') || menu.action_value.toLowerCase().includes('youtu.be');
                                    if (isYoutube) {
                                        content += `<div class="mt-2"><a href="${menu.action_value}" target="_blank" class="btn btn-danger btn-sm rounded-pill px-3"><i class="fab fa-youtube me-1"></i> Buka YouTube</a></div>`;
                                    } else {
                                        content += `<div class="mt-2"><a href="${menu.action_value}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-3"><i class="fas fa-external-link-alt me-1"></i> Lihat Detail</a></div>`;
                                    }
                                }

                                this.messages.push({
                                    id: 'local-bot-' + Date.now(),
                                    sender_id: 0,
                                    sender_type: 'admin',
                                    content: content,
                                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                                });

                                this.messages.push({
                                    id: 'local-bot-menu-' + Date.now(),
                                    sender_id: 0,
                                    sender_type: 'admin',
                                    content: "Ada lagi yang bisa kami bantu?",
                                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                                });
                                this.botPhase = 'awaiting_main_menu';
                                this.scrollToBottom();
                            }, 400);
                        } else {
                            // Default: connect_cs
                            await this.handleConnectCS(menu);
                        }
                    } catch (e) {
                        console.error("Menu click error:", e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                async handleConnectCS(menu) {
                    if (!this.conversationId) {
                        try {
                            if (menu.label.toLowerCase().includes('voucher')) {
                                this.showRegForm = true;
                                this.isAuthenticated = false;
                            } else {
                                this.isLoading = true;
                                const response = await fetch('{{ route('chat.registerAnonymous') }}', {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'X-CSRF-TOKEN': this.csrfToken,
                                        'Accept': 'application/json'
                                    },
                                    body: JSON.stringify({
                                        selected_option: menu.id
                                    })
                                });

                                const data = await response.json();
                                if (response.ok && data.success) {
                                    this.isAuthenticated = true;
                                    if (data.user) {
                                        this.user.name = data.user.name;
                                        this.user.initial = data.user.name.charAt(0).toUpperCase();
                                    }
                                    if (data.bot_phase) {
                                        this.botPhase = data.bot_phase;
                                    }
                                    await this.fetchChatData();
                                } else {
                                    this.showRegForm = true;
                                }
                            }
                        } catch (e) {
                            console.error(e);
                            this.showRegForm = true;
                        } finally {
                            this.isLoading = false;
                        }
                    } else {
                        // Already authenticated, send as regular message
                        this.newMessage = menu.label;
                        await this.sendMessage();
                    }
                },

                async handleSubmenuClick(child) {
                    this.selectedOption = child.id;
                    
                    if (this.conversationId) {
                         // Authenticated, send real message
                         this.newMessage = child.label;
                         this.sendMessage();
                         return;
                    }

                    // Not authenticated yet
                    this.messages.push({
                        id: 'local-user-' + Date.now(),
                        sender_type: 'user',
                        content: child.label,
                        created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                    });

                    setTimeout(() => {
                        if (child.action_type === 'connect_cs') {
                             this.handleConnectCS(child);
                        } else if (child.action_type === 'link') {
                             // Show link locally
                             this.messages.push({
                                id: 'local-bot-' + Date.now(),
                                sender_id: 0,
                                sender_type: 'admin',
                                content: "Silakan buka tautan berikut: " + child.action_value,
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.scrollToBottom();
                        }
                    }, 500);
                },

                async submitRegistration() {
                    this.isLoading = true;
                    this.regError = '';
                    try {
                        const response = await fetch('{{ route('chat.register') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Content-Type': 'application/json',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                ...this.regForm,
                                selected_option: this.selectedOption
                            })
                        });
                        
                        const data = await response.json();
                        if (data.success) {
                            this.showRegForm = false;
                            this.isAuthenticated = true;
                            await this.fetchChatData();
                        } else {
                            this.regError = data.message || 'Terjadi kesalahan validasi data.';
                        }
                    } catch (error) {
                        console.error("Registration Error:", error);
                        this.regError = error.message || 'Gagal terhubung ke server.';
                    } finally {
                        this.isLoading = false;
                    }
                },

                cancelRegistration() {
                    this.showRegForm = false;
                    this.regError = '';
                },

                async fetchChatData() {
                    console.log('fetchChatData called');
                    this.isLoading = true;
                    try {
                        const response = await fetch('{{ route('chat.init') }}', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' }
                        });
                        
                        const data = await response.json();
                        console.log('Chat data received:', data);
                        if (data.csrf_token) this.csrfToken = data.csrf_token;

                        if (data.chat_greeting) this.chat_greeting = data.chat_greeting;
                        if (data.chat_main_menu) {
                            this.chat_main_menu = data.chat_main_menu;
                            console.log('Chat main menu set:', this.chat_main_menu);
                        }

                        if (data.conversation) {
                            this.conversationId = data.conversation.id;
                            this.userId = data.user_id;
                            this.status = data.status;
                            this.botPhase = data.bot_phase || data.conversation.bot_phase || 'off';
                            if (data.bot_submenus) this.botSubmenus = data.bot_submenus;
                            this.isAuthenticated = true;

                            if (data.user) {
                                this.user.name = data.user.name;
                                this.user.initial = data.user.name ? data.user.name.charAt(0).toUpperCase() : 'G';
                            }
                            
                            this.messages = data.messages.map(m => ({
                                id: m.id,
                                sender_id: m.sender_id,
                                sender_type: m.sender_type,
                                message_type: m.message_type,
                                content: m.content,
                                created_at: m.created_at
                            }));

                            if (this.messages.length > 0 && this.user.name !== 'Guest') {
                                // Jika ada pesan dan bukan guest, anggap sedang chatting
                                this.isChatting = true;
                            } else if (data.status === 'active' || data.status === 'pending' || data.status === 'queued') {
                                // Jika ada percakapan aktif di server, tampilkan area chatting
                                this.isChatting = true;
                            } else {
                                // Selain itu tampilkan menu utama
                                this.isChatting = false;
                            }

                            this.listenForEvents();
                        } else {
                            this.isAuthenticated = false;
                        }

                        this.isInitialized = true;
                        this.$nextTick(() => { this.scrollToBottom(); });
                    } catch (e) {
                        console.error('Failed to init chat', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                listenForEvents() {
                    if (typeof window.Echo === 'undefined' || !this.conversationId) return;

                    try {
                        if (this.userId) {
                            window.Echo.private(`user.${this.userId}`)
                                .listen('.user.logged.out', (e) => {
                                    location.reload();
                                });
                        }

                        window.Echo.private(`conversation.${this.conversationId}`)
                            .listen('.message.sent', (e) => {
                                const alreadyExists = this.messages.some(m => m.id === e.id);
                                if (alreadyExists) return;

                                if (e.sender_id == this.userId && e.sender_type === 'user') return;
                                if (e.is_whisper) return;

                                this.messages.push({
                                    id: e.id,
                                    sender_id: e.sender_id,
                                    sender_type: e.sender_type,
                                    message_type: e.message_type,
                                    content: e.content,
                                    created_at: e.created_at ? new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                                });
                                
                                if (this.isOpen) this.scrollToBottom();
                                else this.unreadCount++;
                            })
                            .listen('.conversation.status.changed', (e) => {
                                this.status = e.status;
                                if (e.bot_phase) this.botPhase = e.bot_phase;
                            })
                            .listen('.typing', (e) => {
                                if (e.sender_type === 'admin') {
                                    this.isTyping = e.is_typing;
                                    this.typingMessage = (e.sender_role === 'super_admin') ? 'Admin sedang merespon' : 'Agent sedang merespon';
                                    clearTimeout(this.typingTimeout);
                                    if (this.isTyping) {
                                        this.typingTimeout = setTimeout(() => { this.isTyping = false; }, 3000);
                                    }
                                }
                            });
                    } catch (err) {
                        console.error('Error setting up Echo listeners:', err);
                    }
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isSending) return;

                    const content = this.newMessage;
                    this.newMessage = ''; 

                    if (!this.conversationId) {
                        this.messages.push({
                            id: 'local-msg-' + Date.now(),
                            sender_type: 'user',
                            content: content,
                            created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        });

                        const matchedMenu = this.chat_main_menu.find(m => 
                            content.toLowerCase().includes(m.label.toLowerCase()) || 
                            m.label.toLowerCase().includes(content.toLowerCase())
                        );

                        if (matchedMenu) {
                            this.handleMenuClick(matchedMenu.id);
                        } else {
                            setTimeout(() => {
                                this.messages.push({
                                    id: 'local-bot-err-' + Date.now(),
                                    sender_id: 0,
                                    sender_type: 'admin',
                                    content: "Silakan pilih salah satu menu di atas atau hubungi tim Support kami.",
                                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                                });
                                this.scrollToBottom();
                            }, 500);
                        }
                        
                        this.scrollToBottom();
                        return;
                    }

                    this.isSending = true;
                    const tempId = Date.now();
                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'user',
                        message_type: 'text',
                        content: content,
                        created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('content', content);

                        const response = await fetch('{{ route('chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        
                        if (response.ok && data.success) {
                            const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                            if (msgIndex !== -1) {
                                this.messages[msgIndex].id = data.message.id;
                                this.messages[msgIndex].message_type = data.message.message_type;
                                this.messages[msgIndex].content = data.message.content;
                            }

                            if (data.bot_replies && data.bot_replies.length > 0) {
                                data.bot_replies.forEach(botMsg => {
                                    if (!this.messages.some(m => m.id === botMsg.id)) {
                                        this.messages.push(botMsg);
                                    }
                                });
                            }

                            if (data.bot_phase) {
                                this.botPhase = data.bot_phase;
                                if (data.bot_phase === 'require_registration') {
                                    this.showRegForm = true;
                                }
                            }
                            
                        } else {
                            this.messages = this.messages.filter(m => m.temp_id !== tempId);
                            alert('Gagal mengirim: ' + (data.error || data.message || 'Server Error'));
                        }

                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                    } finally {
                        this.isSending = false;
                        this.sendTypingEvent(false);
                    }
                },

                async uploadFile(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.isSending = true;
                    const tempId = Date.now();
                    
                    let previewUrl = '';
                    let tempType = 'file';
                    if (file.type.startsWith('image/')) {
                        previewUrl = URL.createObjectURL(file);
                        tempType = 'image';
                    }

                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'user',
                        message_type: tempType,
                        content: previewUrl || file.name,
                        created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('file', file);

                        const response = await fetch('{{ route('chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': this.csrfToken,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (data.success) {
                            const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                            if (msgIndex !== -1) {
                                this.messages[msgIndex].id = data.message.id;
                                this.messages[msgIndex].message_type = data.message.message_type;
                                this.messages[msgIndex].content = data.message.content;
                            }
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert('Gagal unggah file');
                    } finally {
                        this.isSending = false;
                        e.target.value = '';
                    }
                },

                async selectCategory(category) {
                    if (this.isSending || this.botPhase !== 'awaiting_category') return;
                    this.newMessage = category;
                    await this.sendMessage();
                },

                sendTypingEvent(isTyping = true) {
                    if (!this.conversationId || this.status !== 'active') return;

                    fetch('{{ route('chat.typing') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            conversation_id: this.conversationId,
                            is_typing: isTyping ? this.newMessage.length > 0 : false
                        })
                    });
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const anchor = document.getElementById('widget-scroll-anchor');
                        if (anchor) anchor.scrollIntoView({behavior: 'smooth', block: 'end'});
                    }, 50);
                }
            }));
        });
    </script>
</body>
</html>}));
        });
    </script>
</body>
</html>