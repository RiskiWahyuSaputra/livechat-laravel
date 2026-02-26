<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Admin Dashboard</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex overflow-hidden"
      x-data="adminDashboard({{ $admin->id }})">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar dengan Identitas Best Corporation -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between shrink-0 z-30 shadow-sm relative">
            <div class="absolute top-0 left-0 right-0 h-1 bg-red-600"></div>

            <div class="flex items-center gap-3 md:gap-4 mt-1">
                <!-- Mobile Toggle -->
                <button @click="showSidebar = !showSidebar" class="lg:hidden p-2 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <div class="flex flex-col">
                    <h2 class="font-black text-[#0a1d37] text-lg tracking-tighter uppercase">Statistik Pelanggan</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Ringkasan interaksi dan pertumbuhan</p>
                </div>
            </div>
            
            <!-- Profile Section dengan Aksen Navy/Red -->
            <div class="flex items-center gap-4 relative" x-data="{ showProfile: false }">
                <button @click="showProfile = !showProfile" @click.away="showProfile = false" 
                        class="flex items-center gap-2 md:gap-3 hover:bg-slate-50 p-1 md:p-1.5 md:pr-3 rounded-2xl transition-all border border-transparent hover:border-slate-200">
                    <div class="relative">
                        <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-[#0a1d37] flex items-center justify-center font-bold text-white shadow-md border-2 border-white text-sm">
                            {{ strtoupper(substr($admin->username, 0, 1)) }}
                        </div>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-white"
                              :class="{
                                  'bg-emerald-500': adminStatus === 'online',
                                  'bg-rose-500': adminStatus === 'busy',
                                  'bg-slate-400': adminStatus === 'offline'
                              }"></span>
                    </div>
                    <div class="text-left hidden md:block">
                        <p class="text-xs font-bold text-slate-900 leading-none mb-1">{{ $admin->username }}</p>
                        <p class="text-[10px] text-slate-500 font-semibold leading-none uppercase" x-text="adminStatusText"></p>
                    </div>
                </button>

                <!-- Profile Dropdown -->
                <div x-show="showProfile" x-cloak 
                     x-transition:enter="transition ease-out duration-200"
                     class="absolute right-0 top-full mt-2 w-64 md:w-72 bg-white rounded-[2rem] shadow-2xl border border-slate-200 py-3 text-slate-800 z-50 overflow-hidden">
                    <div class="px-6 py-4 border-b border-slate-100 bg-[#0a1d37] mb-2 rounded-t-[1.8rem] text-white">
                        <p class="text-[9px] font-black text-red-500 uppercase tracking-[0.3em] mb-3">Administrator Access</p>
                        <div class="flex items-center gap-4">
                             <div class="w-14 h-14 rounded-2xl bg-red-600 flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-red-900/40">
                                {{ strtoupper(substr($admin->username, 0, 1)) }}
                            </div>
                            <div class="overflow-hidden">
                                <p class="font-black text-white text-lg truncate">{{ $admin->username }}</p>
                                <p class="text-xs text-slate-300 font-medium truncate">{{ $admin->email }}</p>
                            </div>
                        </div>
                    </div>
                    <div class="px-3 space-y-1">
                        <p class="px-4 py-2 text-[10px] font-bold text-slate-400 uppercase tracking-widest">Status Kehadiran</p>
                        <button @click="adminStatus = 'online'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-emerald-50 transition-all group">
                            <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                            <span class="text-sm font-bold text-slate-700">Online / Tersedia</span>
                        </button>
                        <button @click="adminStatus = 'busy'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-rose-50 transition-all group">
                            <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                            <span class="text-sm font-bold text-slate-700">Sibuk / Istirahat</span>
                        </button>
                        <button @click="adminStatus = 'offline'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-slate-100 transition-all group">
                            <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                            <span class="text-sm font-bold text-slate-700">Offline</span>
                        </button>
                    </div>
                    <div class="mt-3 px-3 pt-3 border-t border-slate-100">
                        <form method="POST" action="{{ route('admin.logout') }}">
                            @csrf
                            <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-red-600 hover:bg-red-50 transition-all font-black text-sm">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                                Logout Sesi
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-slate-50/50">
            <!-- Alert Success -->
            @if(session('success'))
                <div x-data="{ show: true }" x-show="show" x-init="setTimeout(() => show = false, 3000)" 
                     class="mb-6 p-4 bg-emerald-50 border border-emerald-100 rounded-3xl flex items-center gap-3 text-emerald-700 shadow-sm animate-fade-in">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <p class="text-sm font-bold">{{ session('success') }}</p>
                </div>
            @endif

            <!-- Stats Cards -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
                <!-- Total Users -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-[#0a1d37] opacity-[0.03] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-blue-50 rounded-2xl flex items-center justify-center text-blue-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Global</span>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Total Pelanggan</p>
                    <h3 class="text-3xl font-black text-slate-900 leading-none">{{ $stats['total_users'] }}</h3>
                </div>

                <!-- Online Users -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-emerald-500 opacity-[0.03] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-emerald-50 rounded-2xl flex items-center justify-center text-emerald-600 relative">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5.636 18.364a9 9 0 010-12.728m12.728 0a9 9 0 010 12.728m-9.9-2.829a5 5 0 010-7.07m7.072 0a5 5 0 010 7.07M13 12a1 1 0 11-2 0 1 1 0 012 0z"></path></svg>
                            <span class="absolute top-0 right-0 w-3 h-3 bg-emerald-500 border-2 border-white rounded-full"></span>
                        </div>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Real-time</span>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Online Sekarang</p>
                    <h3 class="text-3xl font-black text-emerald-600 leading-none">{{ $stats['online_users'] }}</h3>
                </div>

                <!-- Today Users -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-red-600 opacity-[0.03] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-red-50 rounded-2xl flex items-center justify-center text-red-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Daily</span>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pelanggan Hari Ini</p>
                    <h3 class="text-3xl font-black text-red-600 leading-none">{{ $stats['today_users'] }}</h3>
                </div>

                <!-- Yesterday Users -->
                <div class="bg-white p-6 rounded-[2rem] border border-slate-100 shadow-sm relative overflow-hidden group">
                    <div class="absolute -right-4 -bottom-4 w-24 h-24 bg-slate-400 opacity-[0.03] rounded-full group-hover:scale-110 transition-transform duration-500"></div>
                    <div class="flex items-center justify-between mb-4">
                        <div class="w-12 h-12 bg-slate-100 rounded-2xl flex items-center justify-center text-slate-600">
                            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                        </div>
                        <span class="text-[10px] font-black text-slate-300 uppercase tracking-widest">Archive</span>
                    </div>
                    <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-1">Pelanggan Kemarin</p>
                    <h3 class="text-3xl font-black text-slate-600 leading-none">{{ $stats['yesterday_users'] }}</h3>
                </div>
            </div>

            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <!-- Filters & Search -->
                <div class="p-8 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ !request('filter') ? 'bg-red-600 text-white shadow-lg shadow-red-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Semua</a>
                        <a href="{{ route('admin.dashboard', ['filter' => 'online']) }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ request('filter') == 'online' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Online</a>
                        <a href="{{ route('admin.dashboard', ['filter' => 'today']) }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ request('filter') == 'today' ? 'bg-red-600 text-white shadow-lg shadow-red-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Hari Ini</a>
                        <a href="{{ route('admin.dashboard', ['filter' => 'yesterday']) }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ request('filter') == 'yesterday' ? 'bg-[#0a1d37] text-white shadow-lg shadow-slate-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Kemarin</a>
                    </div>

                    <form action="{{ route('admin.dashboard') }}" method="GET" class="relative">
                        @if(request('filter'))
                            <input type="hidden" name="filter" value="{{ request('filter') }}">
                        @endif
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama atau kontak..." 
                               class="bg-slate-50 border-2 border-transparent focus:border-red-500/20 focus:ring-4 focus:ring-red-500/5 rounded-2xl px-12 py-3.5 text-sm font-bold w-full md:w-80 transition-all outline-none">
                        <svg class="w-5 h-5 absolute left-4 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Pelanggan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Kontak & Instansi</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-center">Status</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Bergabung Pada</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($customers as $customer)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-11 h-11 rounded-xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-base shadow-lg shadow-slate-200 group-hover:scale-105 transition-transform">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 leading-tight">{{ $customer->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ID: CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="space-y-1">
                                        <div class="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            {{ $customer->contact }}
                                        </div>
                                        <div class="flex items-center gap-2 text-slate-400 font-medium text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            {{ $customer->origin }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="flex justify-center">
                                        @if($customer->conversations()->where('status', 'active')->exists())
                                            <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest border border-emerald-100 flex items-center gap-1.5">
                                                <span class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse"></span>
                                                Sedang Chat
                                            </span>
                                        @else
                                            <span class="px-3 py-1 rounded-full bg-slate-100 text-slate-400 text-[10px] font-black uppercase tracking-widest border border-slate-200">Idle / Selesai</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-xs font-bold text-slate-700">{{ $customer->created_at->translatedFormat('d F Y') }}</p>
                                    <p class="text-[10px] font-bold text-slate-400">{{ $customer->created_at->diffForHumans() }}</p>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <form action="{{ route('admin.user.destroy', $user->id) }}" method="POST" onsubmit="return confirm('Hapus user ini secara permanen? Tindakan ini tidak dapat dibatalkan.');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-xl transition-all" title="Hapus User">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                                            </svg>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-3xl mx-auto flex items-center justify-center mb-4 text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <h4 class="text-slate-800 font-black text-lg">Tidak Ada Pelanggan</h4>
                                    <p class="text-slate-400 text-sm font-bold">Data yang Anda cari tidak ditemukan dalam sistem kami.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-8 border-t border-slate-50 bg-slate-50/30">
                    {{ $customers->links() }}
                </div>
            </div>
        </main>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminDashboard', (adminId) => ({
                adminId: adminId,
                showSidebar: window.innerWidth >= 1024,
                adminStatus: '{{ $admin->status }}',

                init() {
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.showSidebar = true;
                    });
                },

                get adminStatusText() {
                    if (this.adminStatus === 'online') return 'Online';
                    if (this.adminStatus === 'busy') return 'Istirahat';
                    return 'Offline';
                },

                async updateStatus() {
                    fetch('{{ route('admin.status.update') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ status: this.adminStatus })
                    });
                }
            }));
        });
    </script>
</body>
</html>
