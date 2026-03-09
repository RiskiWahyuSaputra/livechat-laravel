<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/best-logo-1.png') }}">
    <script>
        window.broadcastingAuth = "{{ url('/broadcasting/auth') }}";
    </script>
    <title>Dashboard User - BEST CORPORATION</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- AOS Animation -->
    <link href="https://unpkg.com/aos@2.3.1/dist/aos.css" rel="stylesheet">
    <style>
        html { scroll-behavior: smooth; }
        body { font-family: 'Plus Jakarta Sans', sans-serif; }
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        .glass-nav {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
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
        /* Page Load Animation */
        .fade-in-down {
            animation: fadeInDown 0.8s ease-out both;
        }
        @keyframes fadeInDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body x-data="chatWidget()" x-init="initWidget()" class="bg-[#f8fafc] text-slate-800 font-sans antialiased flex flex-col relative overflow-x-hidden">

    <!-- Blobs Background -->
    <div class="blob top-[-10%] left-[-10%] animate-pulse"></div>
    <div class="blob bottom-[20%] right-[-10%] bg-blue-500/5 animate-pulse" style="animation-delay: 1s"></div>

    <!-- Header -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between shrink-0 sticky top-0 z-30 shadow-sm fade-in-down">
        <div class="flex items-center gap-3 md:gap-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1 border border-slate-100 shadow-sm transform hover:scale-110 transition-transform">
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="font-black text-slate-900 text-sm md:text-base tracking-tighter leading-none">BEST <span class="text-red-600">CORP</span></h1>
                    <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1 block">CUSTOMER SUPPORT</span>
                </div>
            </div>
        </div>
        
        <div class="flex items-center gap-4">
            <!-- Nav Links Hidden on Mobile -->
            <div class="hidden lg:flex items-center gap-6 mr-6">
                <a href="#home" class="text-xs font-bold text-slate-500 hover:text-red-600 uppercase tracking-widest transition-colors relative group">
                    Beranda
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-red-600 transition-all group-hover:w-full"></span>
                </a>
                <a href="#solusi" class="text-xs font-bold text-slate-500 hover:text-red-600 uppercase tracking-widest transition-colors relative group">
                    Solusi
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-red-600 transition-all group-hover:w-full"></span>
                </a>
                <a href="#produk" class="text-xs font-bold text-slate-500 hover:text-red-600 uppercase tracking-widest transition-colors relative group">
                    Produk
                    <span class="absolute -bottom-1 left-0 w-0 h-0.5 bg-red-600 transition-all group-hover:w-full"></span>
                </a>
            </div>

            <!-- Profile Section (Reactive) -->
            <div x-show="isAuthenticated" 
                 x-cloak 
                 @login-success.window="isAuthenticated = true; user.name = $event.detail.name; user.initial = $event.detail.name.charAt(0).toUpperCase()"
                 class="flex items-center gap-4 relative"
                 x-data="{ open: false }">
                <div @click="open = !open" class="flex items-center gap-2 md:gap-3 p-1 md:p-1.5 md:pr-3 rounded-2xl transition-all border border-transparent hover:bg-slate-50 cursor-pointer">
                    <div class="relative">
                        <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-[#0a1d37] flex items-center justify-center font-bold text-white shadow-md border-2 border-white text-sm">
                            <span x-text="user.initial"></span>
                        </div>
                    </div>
                    <div class="text-left hidden sm:block">
                        <p class="text-xs font-bold text-slate-900 leading-none mb-1" x-text="user.name"></p>
                        <div class="flex items-center gap-1">
                            <div class="w-1.5 h-1.5 rounded-full bg-emerald-500"></div>
                            <p class="text-[9px] md:text-[10px] text-slate-500 font-bold leading-none uppercase tracking-tighter">Online</p>
                        </div>
                    </div>
                    <svg class="w-4 h-4 text-slate-400 transition-transform" :class="open ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>

                <!-- Dropdown Menu -->
                <div x-show="open" @click.away="open = false" x-cloak
                     x-transition:enter="transition ease-out duration-100"
                     x-transition:enter-start="opacity-0 scale-95"
                     x-transition:enter-end="opacity-100 scale-100"
                     x-transition:leave="transition ease-in duration-75"
                     x-transition:leave-start="opacity-100 scale-100"
                     x-transition:leave-end="opacity-0 scale-95"
                     class="absolute right-0 top-full mt-2 w-48 bg-white rounded-2xl shadow-xl border border-slate-100 py-2 z-50">
                    
                    <a href="{{ route('chat.logout') }}" class="w-full text-left px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-50 flex items-center gap-2 transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                        </svg>
                        Logout Sesi
                    </a>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 w-full max-w-7xl mx-auto">
        <!-- Hero Section -->
        <section id="home" class="py-16 md:py-24 px-4 md:px-6">
            <div class="grid lg:grid-cols-2 gap-12 items-center">
                <div class="space-y-8" data-aos="fade-right" data-aos-duration="1000">
                    <div class="inline-flex items-center gap-2 px-4 py-2 rounded-full bg-red-50 border border-red-100 text-red-600">
                        <span class="text-xs font-bold uppercase tracking-wider">Ekosistem Bisnis Digital</span>
                    </div>
                    <h2 class="text-4xl md:text-6xl font-extrabold text-[#0a1d37] leading-[1.1] tracking-tight">
                        Wujudkan <span class="text-red-600">Kebebasan</span> Finansial Anda.
                    </h2>
                    <p class="text-lg text-slate-500 font-medium leading-relaxed max-w-xl">
                        Selamat datang di portal dukungan BEST CORPORATION. Kami menyediakan ekosistem bisnis syariah untuk membantu masyarakat meraih keberkahan dan kesuksesan finansial.
                    </p>
                    <div class="flex flex-wrap gap-4">
                        <button @click="isOpen = true" class="px-8 py-4 bg-red-600 text-white rounded-2xl font-bold shadow-xl shadow-red-600/20 hover:bg-red-700 transition-all transform hover:-translate-y-1 active:scale-95">
                            Bantuan Langsung
                        </button>
                        <a href="#solusi" class="px-8 py-4 bg-white text-slate-900 border border-slate-200 rounded-2xl font-bold hover:bg-slate-50 transition-all transform hover:-translate-y-1">
                            Lihat Solusi
                        </a>
                    </div>
                </div>
                <div class="relative hidden lg:block" data-aos="fade-left" data-aos-duration="1200">
                    <img src="https://images.unsplash.com/photo-1522071820081-009f0129c71c?auto=format&fit=crop&q=80&w=800" alt="Support" class="rounded-[2.5rem] shadow-2xl border-4 border-white transition-transform hover:scale-[1.02] duration-500">
                    <div class="absolute -bottom-6 -left-6 bg-white p-6 rounded-3xl shadow-xl border border-slate-100 max-w-[200px]" data-aos="zoom-in" data-aos-delay="600">
                        <p class="text-xs font-bold text-red-600 uppercase mb-2">CS Online</p>
                        <p class="text-sm font-bold text-[#0a1d37]">Siap melayani pertanyaan Anda 24/7.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- Stats -->
        <div class="px-4 md:px-6 pb-20 grid grid-cols-2 md:grid-cols-4 gap-4 md:gap-8">
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm text-center transform hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="100">
                <p class="text-2xl font-black text-[#0a1d37]">100K+</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Mitra Aktif</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm text-center transform hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="200">
                <p class="text-2xl font-black text-[#0a1d37]">50+</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Produk</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm text-center transform hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="300">
                <p class="text-2xl font-black text-[#0a1d37]">24/7</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Support</p>
            </div>
            <div class="bg-white p-6 rounded-3xl border border-slate-100 shadow-sm text-center transform hover:-translate-y-2 transition-transform duration-300" data-aos="fade-up" data-aos-delay="400">
                <p class="text-2xl font-black text-[#0a1d37]">100%</p>
                <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-1">Amanah</p>
            </div>
        </div>

        <!-- Solutions Section -->
        <section id="solusi" class="py-20 px-4 md:px-6 bg-white rounded-[3rem] shadow-sm border border-slate-100 mb-20 overflow-hidden">
            <div class="text-center max-w-3xl mx-auto mb-16 space-y-4" data-aos="fade-up">
                <h3 class="text-3xl md:text-4xl font-extrabold text-[#0a1d37] tracking-tight">Solusi Bisnis <span class="text-red-600">Terintegrasi</span></h3>
                <p class="text-slate-500 font-medium text-sm">Kami menyediakan semua instrumen yang Anda butuhkan untuk memulai bisnis dengan mudah.</p>
            </div>
            <div class="grid md:grid-cols-3 gap-8">
                <div class="space-y-4 p-6 rounded-3xl hover:bg-slate-50 transition-colors group" data-aos="fade-up" data-aos-delay="100">
                    <div class="w-12 h-12 bg-red-50 text-red-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 012-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>
                    </div>
                    <h4 class="text-lg font-bold text-[#0a1d37]">Dashboard Digital</h4>
                    <p class="text-sm text-slate-500 leading-relaxed">Kelola poin, reward, dan jaringan Anda melalui sistem dashboard yang canggih dan transparan.</p>
                </div>
                <div class="space-y-4 p-6 rounded-3xl hover:bg-slate-50 transition-colors group" data-aos="fade-up" data-aos-delay="200">
                    <div class="w-12 h-12 bg-blue-50 text-blue-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
                    </div>
                    <h4 class="text-lg font-bold text-[#0a1d37]">Bimbingan Mentor</h4>
                    <p class="text-sm text-slate-500 leading-relaxed">Dapatkan pelatihan rutin dari para leader yang telah sukses meraih reward motor, mobil, hingga rumah.</p>
                </div>
                <div class="space-y-4 p-6 rounded-3xl hover:bg-slate-50 transition-colors group" data-aos="fade-up" data-aos-delay="300">
                    <div class="w-12 h-12 bg-emerald-50 text-emerald-600 rounded-2xl flex items-center justify-center group-hover:scale-110 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                    </div>
                    <h4 class="text-lg font-bold text-[#0a1d37]">Produk Inovatif</h4>
                    <p class="text-sm text-slate-500 leading-relaxed">Akses ke produk pupuk organik, aditif bahan bakar, dan suplemen kesehatan yang sudah teruji kualitasnya.</p>
                </div>
            </div>
        </section>

        <!-- CTA Section -->
        <section id="produk" class="mb-20 px-4 md:px-6" data-aos="zoom-in-up">
            <div class="bg-[#0a1d37] rounded-[3rem] p-8 md:p-16 text-center space-y-8 relative overflow-hidden group">
                <div class="absolute top-0 right-0 w-64 h-64 bg-red-600/10 rounded-full blur-3xl -mr-20 -mt-20 group-hover:scale-150 transition-transform duration-1000"></div>
                <h3 class="text-3xl md:text-5xl font-black text-white">Butuh Bantuan Lebih Lanjut?</h3>
                <p class="text-slate-400 max-w-2xl mx-auto font-medium">Tim Live Support kami siap membantu Anda mengenai pendaftaran, kendala sistem, atau informasi produk.</p>
                <button @click="toggleChat" class="bg-red-600 text-white px-10 py-4 rounded-2xl font-bold shadow-xl shadow-red-600/30 hover:bg-red-700 transition-all transform hover:scale-105 active:scale-95 inline-flex items-center gap-3">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    Chat Sekarang
                </button>
            </div>
        </section>
    </main>

    <footer class="bg-white border-t border-slate-200 py-12 px-4 md:px-6" data-aos="fade-in">
        <div class="max-w-7xl mx-auto text-center">
            <div class="flex items-center justify-center gap-3 mb-6">
                <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-8 h-8 object-contain">
                <span class="text-xl font-black tracking-tighter text-slate-900">BEST <span class="text-red-600">CORP</span></span>
            </div>
            <p class="text-slate-400 text-xs font-bold uppercase tracking-[0.3em] mb-4">PT. Bandung Ekosistem Teknologi</p>
            <div class="flex justify-center gap-6 mb-8 text-slate-400">
                <a href="#" class="hover:text-red-600 transition-colors transform hover:scale-125 transition-transform">FB</a>
                <a href="#" class="hover:text-red-600 transition-colors transform hover:scale-125 transition-transform">IG</a>
                <a href="#" class="hover:text-red-600 transition-colors transform hover:scale-125 transition-transform">YT</a>
            </div>
            <p class="text-[10px] text-slate-300 font-bold uppercase tracking-widest">&copy; 2026 BEST CORPORATION. All Rights Reserved.</p>
        </div>
    </footer>

    <!-- Scripts -->
    <!-- Footer Hidden Logout Form -->
    <form id="global-logout-form" action="{{ route('chat.logout') }}" method="POST" class="hidden">
        @csrf
    </form>

    <script src="https://unpkg.com/aos@2.3.1/dist/aos.js"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            AOS.init({
                once: false,
                mirror: true,
                duration: 800,
                easing: 'ease-out-quad',
            });
        });
    </script>

    <!-- Chat Widget Container -->
    <div class="fixed bottom-6 right-6 md:bottom-8 md:right-8 z-50 flex flex-col items-end">
        
        <!-- Chat Popup Window -->
        <div x-show="isOpen" x-cloak
             x-transition:enter="transition ease-out duration-300 transform origin-bottom-right"
             x-transition:enter-start="opacity-0 scale-50 translate-y-4"
             x-transition:enter-end="opacity-100 scale-100 translate-y-0"
             x-transition:leave="transition ease-in duration-200 transform origin-bottom-right"
             x-transition:leave-start="opacity-100 scale-100 translate-y-0"
             x-transition:leave-end="opacity-0 scale-50 translate-y-4"
             class="bg-white w-[340px] sm:w-[380px] h-[500px] max-h-[75vh] rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden mb-4 relative"
             style="display: none;">
            
            <!-- Loading Overlay -->
            <div x-show="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
                <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-red-600"></div>
            </div>

            <!-- Header -->
            <header class="bg-white px-4 py-3 flex items-center justify-between shrink-0 shadow-sm relative border-b border-slate-100">
                <div class="absolute top-0 left-0 right-0 h-1 bg-red-600"></div>
                <div class="flex items-center gap-3 mt-1">
                    <div class="w-10 h-10 rounded-xl bg-[#0a1d37] flex items-center justify-center shadow-md">
                        <span class="font-black text-white text-lg">CS</span>
                    </div>
                    <div>
                        <h3 class="font-black text-[#0a1d37] text-sm leading-tight">Layanan Pelanggan</h3>
                        <div class="flex items-center gap-1.5 text-[10px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                            <span class="flex items-center gap-1 shrink-0"
                                :class="{
                                    'text-red-500': status === 'pending' || status === 'queued',
                                    'text-emerald-500': status === 'active',
                                    'text-slate-400': status === 'closed'
                                }">
                                <div class="w-1.5 h-1.5 rounded-full"
                                    :class="{
                                        'bg-red-500 animate-pulse': status === 'pending' || status === 'queued',
                                        'bg-emerald-500': status === 'active',
                                        'bg-slate-400': status === 'closed'
                                    }"></div>
                                <span x-text="statusText"></span>
                            </span>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Messages Area (Show if authenticated) -->
            <div x-show="isAuthenticated" id="widget-messages-container" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 relative">
                <div class="text-center mb-4">
                    <span class="text-slate-400 font-medium text-[10px] text-center w-full inline-block">Percakapan Dimulai</span>
                </div>

                <template x-for="(msg, index) in messages" :key="msg.id || msg.temp_id">
                    <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                        
                        <!-- System Message / Reminder -->
                        <template x-if="msg.sender_type === 'system'">
                            <div class="w-full flex flex-col items-center my-4 px-2" data-aos="fade-up">
                                <div class="bg-amber-50/80 backdrop-blur-sm border border-amber-200/60 rounded-2xl px-4 py-2 shadow-sm max-w-[95%] text-center transition-all hover:bg-amber-50">
                                    <div class="flex flex-col">
                                        <span class="text-[9px] font-black text-amber-700 uppercase tracking-[0.15em] mb-0.5">Pemberitahuan Sistem</span>
                                        <span class="text-[11px] text-amber-800/90 font-bold leading-normal" x-text="msg.content"></span>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Normal Text Bubble -->
                        <template x-if="msg.sender_type !== 'system'">
                            <div class="max-w-[88%] flex flex-col min-w-0" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                                <span x-show="msg.sender_type !== 'user'" class="text-[10px] text-slate-400 font-medium mb-0.5 ml-1">Live Support</span>

                                <div class="px-3 py-2 md:px-3.5 md:py-2.5 rounded-2xl text-[13px] leading-relaxed shadow-sm relative overflow-hidden min-w-0 max-w-full"
                                    :class="msg.sender_type === 'user' 
                                        ? 'bg-red-600 text-white rounded-br-sm' 
                                        : 'bg-white text-slate-800 rounded-bl-sm border border-slate-200'">

                                    <!-- Pesan Teks -->
                                    <template x-if="!msg.message_type || msg.message_type === 'text'">
                                        <div class="break-words">
                                            <div x-html="msg.content"></div>
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

                                <!-- Bot Categories Inline (Hanya muncul jika ini pesan bot terakhir dan fase bot adalah awaiting_category) -->
                                <template x-if="msg.sender_id == 0 && botPhase === 'awaiting_category' && index === messages.length - 1">
                                    <div class="mt-2 flex flex-wrap gap-1.5 w-full">
                                        <template x-for="cat in botCategories" :key="cat">
                                            <button @click="selectCategory(cat)" 
                                                    class="px-2.5 py-1.5 bg-white hover:bg-red-50 text-red-600 border border-red-200 hover:border-red-300 rounded-xl text-[10px] font-bold transition-all shadow-sm flex-1 min-w-[120px] text-center">
                                                <span x-text="cat"></span>
                                            </button>
                                        </template>
                                    </div>
                                </template>
                            </div>
                        </template>

                    </div>
                </template>
                <div id="widget-scroll-anchor" class="h-1"></div>
            </div>

            <!-- Registration Form (Show if NOT authenticated) -->
            <div x-show="!isAuthenticated" class="flex-1 overflow-y-auto p-6 bg-slate-50 flex flex-col justify-center">
                <div class="text-center mb-6">
                    <div class="w-12 h-12 bg-red-100 text-red-600 rounded-full flex items-center justify-center mx-auto mb-3">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <h4 class="font-bold text-slate-900">Mulai Percakapan</h4>
                    <p class="text-xs text-slate-500 mt-1">Silakan isi data diri Anda untuk terhubung dengan tim Support kami.</p>
                </div>

                <form @submit.prevent="submitRegistration" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-red-500">*</span></label>
                        <input type="text" x-model="regForm.name" required class="w-full bg-white border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 rounded-xl px-3 py-2 text-sm transition-colors outline-none" placeholder="Masukkan nama Anda">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Email / No. HP <span class="text-red-500">*</span></label>
                        <input type="text" x-model="regForm.contact" required class="w-full bg-white border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 rounded-xl px-3 py-2 text-sm transition-colors outline-none" placeholder="Email atau nomor telepon">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Asal / Instansi <span class="text-red-500">*</span></label>
                        <input type="text" x-model="regForm.origin" required class="w-full bg-white border border-slate-200 focus:border-red-500 focus:ring-2 focus:ring-red-200 rounded-xl px-3 py-2 text-sm transition-colors outline-none" placeholder="Nama perusahaan atau asal Anda">
                    </div>

                    <button type="submit" :disabled="isLoading" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2.5 rounded-xl text-sm transition-colors shadow-md shadow-red-600/20 disabled:opacity-50 disabled:cursor-not-allowed mt-2 flex justify-center items-center gap-2">
                        <span x-show="!isLoading">Mulai Chat</span>
                        <div x-show="isLoading" class="w-4 h-4 rounded-full border-2 border-white/30 border-t-white animate-spin"></div>
                    </button>
                    
                    <div x-show="regError" x-text="regError" class="text-xs text-red-500 text-center font-medium"></div>
                </form>
            </div>

            <!-- Typing Indicator & Footer -->
            <div x-show="isAuthenticated" class="shrink-0 bg-white">
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

                <form @submit.prevent="sendMessage" x-show="status !== 'closed'" class="border-t border-slate-200 p-2.5 bg-white flex items-end gap-2 relative">
                    <button type="button" 
                            @click="$refs.fileInput.click()"
                            class="shrink-0 w-10 h-10 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 focus:outline-none transition-all"
                            title="Unggah Gambar atau File">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                    </button>
                    <input type="file" x-ref="fileInput" class="hidden" @change="uploadFile">

                    <textarea x-model="newMessage" 
                            x-ref="messageInput"
                            @input="sendTypingEvent"
                            @keydown.enter.prevent="if(!event.shiftKey) sendMessage()"
                            :disabled="isSending || isLoading"
                            placeholder="Ketik balasan Anda..." 
                            class="flex-1 max-h-24 min-h-[40px] bg-slate-100 border-transparent focus:bg-white focus:border-red-500 focus:ring-2 focus:ring-red-200 rounded-xl px-3 py-2 text-sm transition-colors resize-none overflow-y-auto"
                            rows="1"></textarea>
                    <button type="submit" 
                            :disabled="!newMessage.trim() || isSending || isLoading"
                            class="shrink-0 w-10 h-10 rounded-xl bg-red-600 text-white flex items-center justify-center hover:bg-red-700 focus:ring-2 focus:ring-red-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                        <svg class="w-4 h-4 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                    </button>
                </form>
            </div>
        </div>

        <!-- Float Button (FAB) -->
        <button @click="toggleChat" 
           class="w-14 h-14 md:w-16 md:h-16 rounded-full bg-red-600 flex items-center justify-center text-white shadow-xl shadow-red-600/30 hover:bg-red-700 transition-all transform hover:scale-105 active:scale-95 z-50 relative group">
            
            <svg x-show="!isOpen" class="w-7 h-7 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            <svg x-show="isOpen" style="display: none;" class="w-7 h-7 md:w-8 md:h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
            
            <!-- Unread Badge -->
            <div x-show="unreadCount > 0 && !isOpen" class="absolute -top-1 -right-1 flex h-6 w-6 items-center justify-center rounded-full bg-[#0a1d37] text-[10px] font-black text-white shadow-sm border-2 border-white">
                <span x-text="unreadCount"></span>
            </div>
        </button>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatWidget', () => ({
                isOpen: false,
                isLoading: false,
                isInitialized: false,
                isAuthenticated: {{ $isAuthenticated ? 'true' : 'false' }},
                csrfToken: '{{ csrf_token() }}',
                user: {
                    name: '{{ Auth::check() ? Auth::user()->name : "" }}',
                    initial: '{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : "" }}'
                },
                
                // Form Data
                regForm: {
                    name: '',
                    contact: '',
                    origin: ''
                },
                regError: '',

                conversationId: null,
                userId: null,
                status: 'pending',
                botPhase: 'off',
                messages: [],
                newMessage: '',
                isSending: false,
                isTyping: false,
                typingMessage: 'Agen sedang merespon',
                typingTimeout: null,
                unreadCount: 0,
                botCategories: ['Pendaftaran & Aktivasi', 'Dukungan Teknis', 'Masalah Pembayaran', 'Komplain / Keluhan', 'Lain-lain'],

                // Inactivity Timer (30 Menit)
                lastActivity: Date.now(),
                inactivityTimeout: 30 * 60 * 1000, 
                checkInterval: 60 * 1000, 

                get statusText() {
                    if (this.status === 'pending') return 'Menunggu Agen';
                    if (this.status === 'queued') return 'Dalam Antrean';
                    if (this.status === 'active') return 'Terhubung';
                    return 'Sesi Ditutup';
                },

                initWidget() {
                    console.log("🕒 Inactivity Timer diaktifkan. Batas waktu: 30 Menit.");
                    
                    // Background Sync (Setiap 30 detik) jika chat terbuka
                    setInterval(() => {
                        if (this.isOpen && this.isAuthenticated && this.isInitialized) {
                            console.log("🔄 Background syncing chat data...");
                            this.fetchChatData();
                        }
                    }, 30000);

                    // Pantau aktivitas user (gerakan mouse, klik, ketik)
                    ['mousedown', 'mousemove', 'keydown', 'scroll', 'touchstart'].forEach(event => {
                        window.addEventListener(event, () => {
                            if (this.isAuthenticated) {
                                this.lastActivity = Date.now();
                            }
                        }, { passive: true });
                    });

                    // Interval untuk mengecek ketidakaktifan
                    setInterval(() => {
                        if (this.isAuthenticated) {
                            const now = Date.now();
                            const diff = now - this.lastActivity;
                            
                            if (diff > this.inactivityTimeout) {
                                this.handleTimeout();
                            }
                        }
                    }, this.checkInterval);
                },

                async handleTimeout() {
                    console.log("⚠️ Sesi berakhir. Mengeluarkan user secara total...");
                    
                    // Hapus cookie guest secara proaktif
                    document.cookie = "guest_chat_token=; expires=Thu, 01 Jan 1970 00:00:00 UTC; path=/;";
                    
                    // Gunakan redirect GET ke rute logout untuk membersihkan sesi dan heading
                    window.location.href = '{{ route('chat.logout') }}';
                },

                async toggleChat() {
                    this.isOpen = !this.isOpen;
                    if (this.isOpen) {
                        this.unreadCount = 0;
                        if (!this.isAuthenticated) return;
                        if (!this.isInitialized) await this.fetchChatData();
                        else this.scrollToBottom();
                    }
                },

                async submitRegistration() {
                    this.isLoading = true;
                    this.regError = '';
                    
                    try {
                        const response = await fetch('{{ route('chat.register') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify(this.regForm)
                        });

                        const contentType = response.headers.get("content-type");
                        if (!contentType || !contentType.includes("application/json")) {
                            throw new Error("Server mengalami masalah internal (500).");
                        }

                        const data = await response.json();
                        
                        if (response.ok && data.success) {
                            if (data.csrf_token) this.csrfToken = data.csrf_token;
                            this.isAuthenticated = true;
                            
                            // Update User Data reaktif
                            if (data.user) {
                                this.user.name = data.user.name;
                                this.user.initial = data.user.name.charAt(0).toUpperCase();
                                
                                // Dispatch event to update header profile immediately if it's listening
                                window.dispatchEvent(new CustomEvent('login-success', { detail: data.user }));
                            }
                            
                            this.regForm = { name: '', contact: '', origin: '' };
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

                async fetchChatData() {
                    this.isLoading = true;
                    try {
                        const response = await fetch('{{ route('chat.init') }}', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' }
                        });
                        
                        if (response.status === 401) {
                            this.isAuthenticated = false;
                            this.isInitialized = false;
                            return;
                        }

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Failed to init');

                        if (data.csrf_token) this.csrfToken = data.csrf_token;
                        this.conversationId = data.conversation.id;
                        this.userId = data.user_id;
                        this.status = data.status;
                        this.botPhase = data.bot_phase || data.conversation.bot_phase || 'off';

                        // Update User Data jika ada dalam response
                        if (data.user) {
                            this.user.name = data.user.name;
                            this.user.initial = data.user.name.charAt(0).toUpperCase();
                        }
                        
                        this.messages = data.messages.map(m => ({
                            id: m.id,
                            sender_id: m.sender_id,
                            sender_type: m.sender_type,
                            message_type: m.message_type,
                            content: m.content,
                            created_at: m.created_at
                        }));

                        this.isInitialized = true;
                        this.listenForEvents();
                        
                        this.$nextTick(() => { this.scrollToBottom(); });
                    } catch (e) {
                        console.error('Failed to init chat', e);
                    } finally {
                        this.isLoading = false;
                    }
                },

                listenForEvents() {
                    if (typeof window.Echo === 'undefined' || !this.conversationId) return;

                    // Personal User Channel for Global Events (Logout/Blocked)
                    if (this.userId) {
                        window.Echo.private(`user.${this.userId}`)
                            .listen('.user.logged.out', (e) => {
                                setTimeout(() => {
                                    this.handleTimeout();
                                }, 3000);
                            });
                    }

                    window.Echo.private(`conversation.${this.conversationId}`)
                        .listen('.message.sent', (e) => {
                            this.lastActivity = Date.now();
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
                                created_at: new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            
                            if (this.isOpen) this.scrollToBottom();
                            else this.unreadCount++;
                        })
                        .listen('.conversation.status.changed', (e) => {
                            this.status = e.status;
                            if (e.bot_phase) this.botPhase = e.bot_phase;

                            if (e.status === 'closed') {
                                setTimeout(() => {
                                    this.handleTimeout();
                                }, 3000);
                            }
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
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isSending) return;

                    const content = this.newMessage;
                    this.newMessage = ''; 
                    this.isSending = true;

                    const tempId = Date.now();
                    const now = new Date();
                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'user',
                        message_type: 'text',
                        content: content,
                        created_at: now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
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

                            // Tambahkan balasan bot jika ada di response JSON
                            if (data.bot_replies && data.bot_replies.length > 0) {
                                data.bot_replies.forEach(botMsg => {
                                    // Cek agar tidak duplikat dengan broadcast
                                    if (!this.messages.some(m => m.id === botMsg.id)) {
                                        this.messages.push(botMsg);
                                    }
                                });
                            }

                            if (data.bot_phase) this.botPhase = data.bot_phase;
                            
                        } else {
                            this.messages = this.messages.filter(m => m.temp_id !== tempId);
                            alert('Gagal mengirim: ' + (data.error || data.message || 'Server Error ' + response.status));
                        }

                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                    } finally {
                        this.isSending = false;
                        this.sendTypingEvent(false);
                        this.$nextTick(() => {
                            if (this.$refs && this.$refs.messageInput) this.$refs.messageInput.focus();
                        });
                    }
                },

                async uploadFile(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.isSending = true;
                    const tempId = Date.now();
                    const now = new Date();
                    
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
                        created_at: now.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
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
                        if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);

                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1 && data.success) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].message_type = data.message.message_type;
                            this.messages[msgIndex].content = data.message.content;
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        e.target.value = '';
                    }
                },

                async selectCategory(category) {
                    if (this.isSending || this.botPhase !== 'awaiting_category') return;
                    this.newMessage = category;
                    await this.sendMessage();
                    this.botPhase = 'awaiting_explanation';
                },

                sendTypingEvent(isTyping = true) {
                    if (this.status !== 'active') return;

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
</html>
