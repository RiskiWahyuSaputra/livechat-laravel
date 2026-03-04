<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/best-logo-1.png') }}">
    <title>BEST CORPORATION - Solusi Ekosistem Bisnis Digital & Syariah</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
        }
        .text-gradient {
            background: linear-gradient(135deg, #0a1d37 0%, #dc2626 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .fab-pulse {
            animation: fab-pulse-animation 2s infinite;
        }
        @keyframes fab-pulse-animation {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.5); }
            70% { box-shadow: 0 0 0 15px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
        .blob {
            position: absolute;
            width: 500px;
            height: 500px;
            background: rgba(220, 38, 38, 0.05);
            filter: blur(80px);
            border-radius: 50%;
            z-index: -1;
        }
    </style>
</head>
<body class="bg-white text-slate-900 antialiased overflow-x-hidden">

    <!-- Blobs Background -->
    <div class="blob top-[-10%] left-[-10%]"></div>
    <div class="blob bottom-[-10%] right-[-10%] bg-blue-500/5"></div>

    <!-- Navigation -->
    <nav class="fixed top-0 left-0 right-0 z-50 glass-nav border-b border-slate-100">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-20">
                <div class="flex items-center gap-3">
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-10 h-10 object-contain">
                    <div>
                        <span class="text-xl font-extrabold tracking-tighter text-slate-900">BEST <span class="text-red-600">CORP</span></span>
                    </div>
                </div>
                
                <div class="hidden md:flex items-center gap-8">
                    <a href="#home" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">Beranda</a>
                    <a href="#solusi" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">Solusi</a>
                    <a href="#produk" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">Produk</a>
                    <a href="#tentang" class="text-sm font-bold text-slate-600 hover:text-red-600 transition-colors">Tentang Kami</a>
                </div>

                <div class="flex items-center gap-4">
                    <a href="{{ route('admin.login') }}" class="text-sm font-bold text-slate-600 hover:text-slate-900">Admin Login</a>
                    <a href="{{ route('chat.init') }}" class="bg-red-600 hover:bg-red-700 text-white px-6 py-2.5 rounded-full text-sm font-bold shadow-lg shadow-red-600/20 transition-all transform hover:scale-105 active:scale-95">
                        Mulai Chat
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section id="home" class="pt-40 pb-20 px-4">
        <div class="max-w-7xl mx-auto grid md:grid-cols-2 gap-12 items-center">
            <div class="space-y-8">
                <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-50 border border-red-100 text-red-600">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-red-400 opacity-75"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2 bg-red-500"></span>
                    </span>
                    <span class="text-xs font-bold uppercase tracking-wider">Ekosistem Bisnis Masa Depan</span>
                </div>
                
                <h1 class="text-5xl md:text-7xl font-extrabold text-[#0a1d37] leading-[1.1] tracking-tight">
                    Solusi Cerdas untuk <span class="text-red-600">Pertumbuhan</span> Bisnis Anda.
                </h1>
                
                <p class="text-lg text-slate-500 font-medium leading-relaxed max-w-xl">
                    BEST CORPORATION menghadirkan ekosistem digital dan produk inovatif yang dirancang untuk membantu masyarakat meraih kebebasan finansial melalui sistem syariah yang berkah.
                </p>

                <div class="flex flex-wrap gap-4">
                    <a href="{{ route('chat.init') }}" class="px-8 py-4 bg-[#0a1d37] text-white rounded-2xl font-bold shadow-xl hover:shadow-2xl transition-all transform hover:-translate-y-1">
                        Konsultasi Gratis
                    </a>
                    <a href="#solusi" class="px-8 py-4 bg-white text-slate-900 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all transform hover:-translate-y-1">
                        Pelajari Selengkapnya
                    </a>
                </div>

                <div class="flex items-center gap-8 pt-8 border-t border-slate-100">
                    <div>
                        <p class="text-2xl font-extrabold text-slate-900">100K+</p>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Mitra Aktif</p>
                    </div>
                    <div class="w-px h-8 bg-slate-100"></div>
                    <div>
                        <p class="text-2xl font-extrabold text-slate-900">50+</p>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Produk Unggulan</p>
                    </div>
                    <div class="w-px h-8 bg-slate-100"></div>
                    <div>
                        <p class="text-2xl font-extrabold text-slate-900">24/7</p>
                        <p class="text-xs font-bold text-slate-400 uppercase tracking-widest mt-1">Layanan Bantuan</p>
                    </div>
                </div>
            </div>

            <div class="relative">
                <div class="relative z-10 rounded-[2.5rem] overflow-hidden shadow-2xl shadow-red-600/10 border-8 border-white">
                    <img src="https://images.unsplash.com/photo-1552664730-d307ca884978?auto=format&fit=crop&q=80&w=1200" alt="Team Best Corp" class="w-full h-full object-cover">
                </div>
                <!-- Floating Card -->
                <div class="absolute -bottom-6 -left-6 z-20 bg-white p-6 rounded-3xl shadow-2xl border border-slate-100 max-w-[240px] animate-bounce-slow">
                    <div class="flex items-center gap-4 mb-3">
                        <div class="w-10 h-10 rounded-xl bg-emerald-100 text-emerald-600 flex items-center justify-center">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <p class="text-sm font-bold text-slate-900">Sistem Syariah Terverifikasi</p>
                    </div>
                    <p class="text-xs text-slate-500 leading-relaxed">Membangun ekonomi umat dengan cara yang benar dan transparan.</p>
                </div>
            </div>
        </div>
    </section>

    <!-- Solutions Section -->
    <section id="solusi" class="py-24 bg-slate-50">
        <div class="max-w-7xl mx-auto px-4">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4">
                <h2 class="text-xs font-bold text-red-600 uppercase tracking-[0.3em]">Mengapa Memilih Kami</h2>
                <h3 class="text-4xl md:text-5xl font-extrabold text-[#0a1d37] tracking-tight">Ekosistem Lengkap untuk Kesuksesan Anda.</h3>
                <p class="text-slate-500 font-medium">Kami menyediakan semua instrumen yang Anda butuhkan untuk memulai dan mengembangkan bisnis dengan dukungan penuh.</p>
            </div>

            <div class="grid md:grid-cols-3 gap-8">
                <!-- Card 1 -->
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-transparent hover:border-red-100 transition-all group hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-red-50 text-red-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                    </div>
                    <h4 class="text-xl font-extrabold text-[#0a1d37] mb-4">Akses Cepat & Digital</h4>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6">Kelola seluruh bisnis Anda dari genggaman tangan dengan aplikasi dashboard yang modern dan informatif.</p>
                    <ul class="space-y-3 text-sm font-bold text-slate-600">
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-red-600"></div> Real-time Monitoring</li>
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-red-600"></div> Integrasi WhatsApp</li>
                    </ul>
                </div>

                <!-- Card 2 -->
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-transparent hover:border-red-100 transition-all group hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-blue-50 text-blue-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h4 class="text-xl font-extrabold text-[#0a1d37] mb-4">Komunitas Terbuka</h4>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6">Bergabunglah dengan ratusan ribu mitra lainnya dan dapatkan bimbingan intensif dari para mentor berpengalaman.</p>
                    <ul class="space-y-3 text-sm font-bold text-slate-600">
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-blue-600"></div> Webinar Mingguan</li>
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-blue-600"></div> Grup Support 24/7</li>
                    </ul>
                </div>

                <!-- Card 3 -->
                <div class="bg-white p-8 rounded-[2rem] shadow-xl shadow-slate-200/50 border border-transparent hover:border-red-100 transition-all group hover:-translate-y-2">
                    <div class="w-14 h-14 rounded-2xl bg-emerald-50 text-emerald-600 flex items-center justify-center mb-6 group-hover:scale-110 transition-transform">
                        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h4 class="text-xl font-extrabold text-[#0a1d37] mb-4">Produk Berkualitas</h4>
                    <p class="text-slate-500 text-sm leading-relaxed mb-6">Produk yang kami pasarkan telah melalui riset mendalam, memiliki sertifikasi BPOM/MUI, dan sangat diminati pasar.</p>
                    <ul class="space-y-3 text-sm font-bold text-slate-600">
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div> Standar Industri</li>
                        <li class="flex items-center gap-2"><div class="w-1.5 h-1.5 rounded-full bg-emerald-600"></div> Sertifikasi Lengkap</li>
                    </ul>
                </div>
            </div>
        </div>
    </section>

    <!-- Products CTA -->
    <section id="produk" class="py-24">
        <div class="max-w-7xl mx-auto px-4 bg-[#0a1d37] rounded-[3rem] p-12 md:p-20 relative overflow-hidden">
            <div class="absolute top-0 right-0 w-1/2 h-full bg-gradient-to-l from-red-600/20 to-transparent pointer-events-none"></div>
            
            <div class="grid md:grid-cols-2 gap-12 items-center relative z-10">
                <div class="space-y-8">
                    <h3 class="text-4xl md:text-5xl font-extrabold text-white leading-tight">Mulai Perjalanan Kesuksesan Anda Hari Ini.</h3>
                    <p class="text-slate-300 text-lg">Jangan tunda lagi untuk meraih kehidupan yang lebih baik. Kami siap mendampingi setiap langkah Anda.</p>
                    <div class="flex flex-wrap gap-4">
                        <a href="{{ route('chat.init') }}" class="px-8 py-4 bg-red-600 text-white rounded-2xl font-bold shadow-xl hover:bg-red-700 transition-all transform hover:scale-105">
                            Hubungi CS Sekarang
                        </a>
                        <a href="#tentang" class="px-8 py-4 bg-white/10 text-white border border-white/20 rounded-2xl font-bold hover:bg-white/20 transition-all">
                            Tentang BEST CORP
                        </a>
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-4">
                    <div class="space-y-4">
                        <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                            <p class="text-3xl font-bold text-white mb-1">98%</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Kepuasan Mitra</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                            <p class="text-3xl font-bold text-white mb-1">24/7</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Chat Support</p>
                        </div>
                    </div>
                    <div class="space-y-4 mt-8">
                        <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                            <p class="text-3xl font-bold text-white mb-1">A+</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Kualitas Produk</p>
                        </div>
                        <div class="bg-white/5 backdrop-blur-md p-6 rounded-3xl border border-white/10">
                            <p class="text-3xl font-bold text-white mb-1">Secure</p>
                            <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">Keamanan Sistem</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer id="tentang" class="bg-slate-50 pt-24 pb-12">
        <div class="max-w-7xl mx-auto px-4">
            <div class="grid md:grid-cols-4 gap-12 mb-16">
                <div class="col-span-2 space-y-6">
                    <div class="flex items-center gap-3">
                        <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-10 h-10 object-contain">
                        <span class="text-2xl font-extrabold tracking-tighter text-slate-900">BEST <span class="text-red-600">CORP</span></span>
                    </div>
                    <p class="text-slate-500 font-medium max-w-sm">
                        PT. Bandung Ekosistem Teknologi adalah perusahaan yang bergerak di bidang penjualan langsung yang memasarkan produk-produk berkualitas dari karya anak bangsa.
                    </p>
                    <div class="flex gap-4">
                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors cursor-pointer"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/></svg></div>
                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors cursor-pointer"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/></svg></div>
                        <div class="w-10 h-10 rounded-full bg-slate-200 flex items-center justify-center text-slate-600 hover:bg-red-600 hover:text-white transition-colors cursor-pointer"><svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24"><path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/></svg></div>
                    </div>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-slate-900 uppercase tracking-widest mb-6">Navigasi</h5>
                    <ul class="space-y-4 text-sm font-medium text-slate-500">
                        <li><a href="#home" class="hover:text-red-600 transition-colors">Beranda</a></li>
                        <li><a href="#solusi" class="hover:text-red-600 transition-colors">Solusi Bisnis</a></li>
                        <li><a href="#produk" class="hover:text-red-600 transition-colors">Produk Unggulan</a></li>
                        <li><a href="{{ route('chat.init') }}" class="hover:text-red-600 transition-colors">Live Chat</a></li>
                    </ul>
                </div>
                <div>
                    <h5 class="text-sm font-bold text-slate-900 uppercase tracking-widest mb-6">Kontak</h5>
                    <ul class="space-y-4 text-sm font-medium text-slate-500">
                        <li class="flex items-start gap-3">
                            <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>
                            <span>Jl. Surapati No. 189, Bandung, Jawa Barat, Indonesia.</span>
                        </li>
                        <li class="flex items-center gap-3">
                            <svg class="w-5 h-5 text-red-600 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span>support@best-corp.id</span>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="pt-8 border-t border-slate-200 text-center">
                <p class="text-xs font-bold text-slate-400 uppercase tracking-widest">
                    &copy; 2026 PT. Bandung Ekosistem Teknologi. All Rights Reserved.
                </p>
            </div>
        </div>
    </footer>

    <!-- Floating Action Button (FAB) -->
    <a href="{{ route('chat.init') }}" 
       class="fixed bottom-8 right-8 w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white shadow-2xl shadow-red-600/40 hover:bg-red-700 transition-all transform hover:scale-110 active:scale-95 z-[60] fab-pulse group">
        <svg class="w-8 h-8 group-hover:rotate-12 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <!-- Tooltip -->
        <span class="absolute right-20 bg-[#0a1d37] text-white text-[10px] font-bold py-2 px-4 rounded-xl opacity-0 group-hover:opacity-100 transition-opacity whitespace-nowrap shadow-xl pointer-events-none uppercase tracking-widest">
            Chat dengan CS Kami
        </span>
    </a>

</body>
</html>
