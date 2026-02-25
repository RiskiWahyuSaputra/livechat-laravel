<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ruang Kerja Admin - LiveChat</title>
    <link rel="icon" type="image/x-icon" href="{{ asset('images/best-logo-1.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        
        .pulse-amber { animation: pulse-amber 2s infinite; }
        @keyframes pulse-amber {
            0% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(245, 158, 11, 0); }
            100% { box-shadow: 0 0 0 0 rgba(245, 158, 11, 0); }
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      x-data="adminDashboard({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }})">

    <!-- Modern Top Navbar -->
    <header class="bg-white/80 backdrop-blur-md border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between shrink-0 z-30 shadow-sm">
        <div class="flex items-center gap-3 md:gap-4">
            <!-- Mobile Toggle -->
            <button @click="showSidebar = !showSidebar" class="lg:hidden p-2 hover:bg-slate-100 rounded-xl transition-colors">
                <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
            </button>

            <div class="flex items-center gap-2 md:gap-3">
                <div class="w-8 h-8 md:w-10 md:h-10 bg-indigo-600 rounded-lg md:rounded-xl flex items-center justify-center shadow-lg shadow-indigo-200 rotate-3">
                    <svg class="w-5 h-5 md:w-6 md:h-6 text-white -rotate-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path></svg>
                </div>
                <div class="hidden xs:block">
                    <h1 class="font-bold text-slate-900 text-sm md:text-base tracking-tight leading-none">Studio Bantuan</h1>
                    <span class="text-[9px] md:text-[11px] font-bold text-slate-500 uppercase tracking-widest mt-1 block">Operasional</span>
                </div>
            </div>
        </div>
        
        <!-- Profile Section -->
        <div class="flex items-center gap-4 relative" x-data="{ showProfile: false }">
            <button @click="showProfile = !showProfile" @click.away="showProfile = false" 
                    class="flex items-center gap-2 md:gap-3 hover:bg-slate-50 p-1 md:p-1.5 md:pr-3 rounded-2xl transition-all">
                <div class="relative">
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-gradient-to-tr from-indigo-600 to-violet-500 flex items-center justify-center font-bold text-white shadow-md border-2 border-white text-sm md:text-base">
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
                 class="absolute right-0 top-full mt-2 w-64 md:w-72 bg-white rounded-3xl shadow-2xl border border-slate-200 py-3 text-slate-800 z-50">
                <div class="px-6 py-4 border-b border-slate-100 bg-slate-50/50 mb-2 rounded-t-3xl">
                    <p class="text-[10px] font-bold text-indigo-600 uppercase tracking-widest mb-3">Administrator</p>
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-gradient-to-br from-indigo-600 to-violet-600 flex items-center justify-center font-bold text-xl text-white shadow-lg">
                            {{ strtoupper(substr($admin->username, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="font-bold text-slate-900 truncate">{{ $admin->username }}</p>
                            <p class="text-[11px] text-slate-500 font-medium truncate">{{ $admin->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="px-3 space-y-1">
                    <button @click="adminStatus = 'online'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-emerald-50 transition-all group">
                        <span class="w-2 h-2 rounded-full bg-emerald-500"></span>
                        <span class="text-sm font-bold text-slate-700">Tersedia</span>
                    </button>
                    <button @click="adminStatus = 'busy'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-rose-50 transition-all group">
                        <span class="w-2 h-2 rounded-full bg-rose-500"></span>
                        <span class="text-sm font-bold text-slate-700">Sibuk</span>
                    </button>
                    <button @click="adminStatus = 'offline'; updateStatus(); showProfile = false" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl hover:bg-slate-100 transition-all group">
                        <span class="w-2 h-2 rounded-full bg-slate-400"></span>
                        <span class="text-sm font-bold text-slate-700">Offline</span>
                    </button>
                </div>
                <div class="mt-3 px-3 pt-3 border-t border-slate-100">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-2xl text-rose-600 hover:bg-rose-50 transition-all font-bold text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Keluar Sesi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- Sidebar: Advanced Chat List (Responsive) -->
        <aside class="absolute lg:relative z-20 w-full xs:w-[320px] md:w-[340px] h-full bg-white border-r border-slate-200 flex flex-col shrink-0 transition-transform duration-300 transform lg:translate-x-0"
               :class="showSidebar ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="px-6 py-5 shrink-0 bg-white">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-black text-slate-900 text-lg tracking-tight">Pesan</h2>
                    <span class="bg-indigo-100 text-indigo-700 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase" x-text="filteredChats.length"></span>
                </div>
                
                <!-- Search Input (Functional) -->
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Cari pelanggan atau email..." 
                           class="w-full bg-slate-50 border-none rounded-2xl px-10 py-2.5 text-sm focus:ring-2 focus:ring-indigo-500/20 placeholder:text-slate-400 font-medium transition-all">
                    <svg class="w-4 h-4 absolute left-4 top-3 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    <!-- Clear Search -->
                    <button x-show="searchQuery" @click="searchQuery = ''" class="absolute right-3 top-2.5 text-slate-400 hover:text-slate-600">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"></path></svg>
                    </button>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto px-3 pb-6 space-y-6">
                
                <!-- Section: Permintaan Masuk -->
                <div>
                    <div class="px-3 mb-2 flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Permintaan Baru</span>
                        <div class="h-px bg-slate-100 flex-1"></div>
                    </div>
                    
                    <div class="space-y-1">
                        <template x-for="chat in filteredChats.filter(c => ['pending', 'queued'].includes(c.status))" :key="chat.id">
                            <div @click="selectChat(chat)" 
                                 class="p-3 rounded-2xl cursor-pointer transition-all duration-200 group relative"
                                 :class="selectedChat && selectedChat.id === chat.id ? 'bg-indigo-50 ring-1 ring-indigo-100' : 'hover:bg-slate-50'">
                                <div class="flex items-center gap-3 relative z-10">
                                    <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center font-bold text-amber-700 shrink-0 border-2 border-white pulse-amber">
                                        <span x-text="chat.user.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <div class="flex justify-between items-start mb-0.5">
                                            <p class="font-bold text-[13px] text-slate-800 truncate" x-text="chat.user.name"></p>
                                            <span class="text-[9px] font-bold text-amber-600" x-text="formatTime(chat.last_message_at)"></span>
                                        </div>
                                        <p class="text-[10px] font-bold text-amber-500 uppercase tracking-tighter" x-text="chat.status === 'queued' ? 'Antrean #' + chat.queue_position : 'Baru'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Section: Dukungan Aktif -->
                <div>
                    <div class="px-3 mb-2 flex items-center gap-2">
                        <span class="text-[10px] font-black text-slate-400 uppercase tracking-widest">Dukungan Aktif</span>
                        <div class="h-px bg-slate-100 flex-1"></div>
                    </div>
                    
                    <div class="space-y-1">
                        <template x-for="chat in filteredChats.filter(c => c.status === 'active')" :key="chat.id">
                            <div @click="selectChat(chat)" 
                                 class="p-3 rounded-2xl cursor-pointer transition-all duration-200 group border border-transparent"
                                 :class="selectedChat && selectedChat.id === chat.id ? 'bg-white shadow-lg ring-1 ring-indigo-500/20' : 'hover:bg-slate-50'">
                                <div class="flex items-center gap-3">
                                    <div class="w-10 h-10 rounded-full flex items-center justify-center font-bold text-white shrink-0 border-2 border-white"
                                         :class="chat.admin_id === adminId ? 'bg-indigo-500' : 'bg-slate-300'">
                                        <span x-text="chat.user.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <div class="flex justify-between items-start mb-0.5">
                                            <p class="font-bold text-[13px] text-slate-800 truncate" x-text="chat.user.name"></p>
                                            <span class="text-[9px] font-bold text-slate-400" x-text="formatTime(chat.last_message_at)"></span>
                                        </div>
                                        <p class="text-[10px] font-medium text-slate-500 truncate" x-text="getPreviewText(chat)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Panel -->
        <main class="flex-1 flex flex-col bg-slate-50 relative overflow-hidden z-10">
            
            <!-- Empty State -->
            <template x-if="!selectedChat">
                <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                    <div class="w-24 h-24 md:w-32 md:h-32 bg-white rounded-[30px] md:rounded-[40px] shadow-2xl border border-slate-100 flex items-center justify-center mb-6">
                         <svg class="w-10 h-10 md:w-12 md:h-12 text-indigo-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <h3 class="text-xl md:text-2xl font-black text-slate-800 mb-2">Pilih Percakapan</h3>
                    <p class="text-slate-500 max-w-xs text-sm font-medium">Klik salah satu pesan di samping untuk mulai membantu pelanggan.</p>
                </div>
            </template>

            <!-- Active Chat Interface -->
            <template x-if="selectedChat">
                <div class="w-full h-full flex flex-col bg-white">
                    
                    <!-- Chat Header -->
                    <div class="bg-white/90 backdrop-blur-sm border-b border-slate-100 px-4 md:px-8 py-3 md:py-4 flex items-center justify-between shrink-0 shadow-sm">
                        <div class="flex items-center gap-3 md:gap-4 overflow-hidden">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-xl bg-slate-100 flex items-center justify-center font-black text-slate-400 text-base md:text-xl border border-slate-200 shrink-0">
                                <span x-text="selectedChat.user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="font-black text-slate-900 text-sm md:text-lg leading-tight truncate" x-text="selectedChat.user.name"></h3>
                                <div class="flex items-center gap-2 text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-tighter">
                                    <span class="flex items-center gap-1 shrink-0">
                                        <div class="w-1.5 h-1.5 rounded-full" :class="selectedChat.status === 'active' ? 'bg-emerald-500' : 'bg-amber-500'"></div>
                                        <span x-text="selectedChat.status"></span>
                                    </span>
                                    <span class="hidden xs:inline">&bull;</span>
                                    <span class="truncate hidden xs:inline" x-text="selectedChat.user.email"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Actions -->
                        <div class="flex items-center gap-1 md:gap-2">
                            <template x-if="['pending', 'queued'].includes(selectedChat.status)">
                                <button class="bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-200 text-[11px] md:text-sm font-black px-3 md:px-6 py-2 md:py-2.5 rounded-xl transition-all"
                                        @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                    <span x-text="isClaiming ? '...' : 'Terima'"></span>
                                </button>
                            </template>

                            <template x-if="selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                <div class="flex items-center gap-1 md:gap-2">
                                    <button class="p-2 md:px-4 md:py-2 rounded-xl text-amber-600 hover:bg-amber-50 transition-all"
                                            @click="showHandoverModal = true" title="Oper">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                    </button>
                                    <button class="p-2 md:px-4 md:py-2 rounded-xl text-indigo-600 hover:bg-indigo-50 transition-all"
                                            @click="showCloseModal = true" title="Selesai">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                    </button>
                                    <button class="p-2 md:px-3 md:py-2 rounded-xl text-slate-400 hover:text-rose-600 hover:bg-rose-50 transition-all"
                                            @click="blockUser(selectedChat.id)" title="Blokir">
                                        <svg class="w-4 h-4 md:w-5 md:h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Chat Iframe -->
                    <div class="flex-1 bg-slate-50">
                        <iframe class="w-full h-full border-none" :src="'/admin/conversation/' + selectedChat.id"></iframe>
                    </div>
                </div>
            </template>
        </main>
    </div>

    <!-- Modals (Tetap Sama, dengan penyesuaian responsive p-4) -->
    <!-- [Modal Akhiri & Oper dihapus dari ringkasan untuk singkatnya, tapi tetap ada di file asli dengan class responsive] -->
    <div x-show="showCloseModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" @click="showCloseModal = false"></div>
        <div class="bg-white rounded-[28px] shadow-2xl w-full max-w-sm overflow-hidden relative z-10 p-6 md:p-8 border border-white" x-transition>
            <h3 class="text-xl font-black text-slate-900 mb-2">Selesaikan Chat</h3>
            <p class="text-slate-500 text-xs mb-6">Pilih kategori penyelesaian untuk menutup sesi ini.</p>
            <div class="space-y-2 mb-6">
                <template x-for="cat in ['Info Produk', 'Dukungan Teknis', 'Pembayaran', 'Komplain', 'Lainnya']">
                    <button @click="closeCategory = cat" class="w-full text-left px-4 py-3 rounded-xl border-2 font-bold text-sm transition-all"
                            :class="closeCategory === cat ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-slate-50 border-transparent text-slate-700'">
                        <span x-text="cat"></span>
                    </button>
                </template>
            </div>
            <button class="w-full py-3.5 bg-slate-900 text-white rounded-xl font-black mb-2" @click="closeChat()" :disabled="!closeCategory">Selesaikan Sesi</button>
            <button class="w-full py-2 text-slate-400 font-bold text-xs" @click="showCloseModal = false">Batal</button>
        </div>
    </div>

    <div x-show="showHandoverModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-slate-900/40 backdrop-blur-md" @click="showHandoverModal = false"></div>
        <div class="bg-white rounded-[28px] shadow-2xl w-full max-w-sm overflow-hidden relative z-10 p-6 md:p-8 border border-white" x-transition>
            <h3 class="text-xl font-black text-slate-900 mb-2">Oper Chat</h3>
            <p class="text-slate-500 text-xs mb-6">Teruskan pelanggan ini ke rekan tim lain.</p>
            <select x-model="handoverToAdminId" class="w-full px-4 py-3 bg-slate-50 border-none rounded-xl text-sm font-bold text-slate-700 mb-6 focus:ring-2 focus:ring-indigo-500">
                <option value="" disabled>-- Pilih Rekan Tim --</option>
                @foreach($otherAdmins as $other)
                    <option value="{{ $other->id }}">{{ $other->username }}</option>
                @endforeach
            </select>
            <button class="w-full py-3.5 bg-amber-500 text-white rounded-xl font-black mb-2" @click="handoverChat()" :disabled="!handoverToAdminId">Oper Sekarang</button>
            <button class="w-full py-2 text-slate-400 font-bold text-xs" @click="showHandoverModal = false">Batal</button>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminDashboard', (adminId, initPending, initActive) => ({
                adminId: adminId,
                chats: [...initPending, ...initActive],
                selectedChat: null,
                searchQuery: '',
                showSidebar: window.innerWidth >= 1024,
                adminStatus: '{{ $admin->status }}',
                isClaiming: false,
                isSubmitting: false,
                showCloseModal: false,
                showHandoverModal: false,
                closeCategory: '',
                handoverToAdminId: '',
                notifSound: new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg'),

                init() {
                    if (localStorage.getItem('play_chat_notif') === 'true') {
                        this.playNotif();
                        localStorage.removeItem('play_chat_notif');
                    }
                    if (window.Echo) {
                        window.Echo.private('admin.dashboard')
                            .listen('.conversation.status.changed', (e) => {
                                this.handleStatusChange(e);
                            });
                    }
                    // Handle window resize
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.showSidebar = true;
                    });
                },

                get filteredChats() {
                    if (!this.searchQuery.trim()) return this.chats;
                    const query = this.searchQuery.toLowerCase();
                    return this.chats.filter(chat => 
                        chat.user.name.toLowerCase().includes(query) || 
                        chat.user.email.toLowerCase().includes(query) ||
                        chat.id.toString().includes(query)
                    );
                },

                get adminStatusText() {
                    if (this.adminStatus === 'online') return 'Tersedia';
                    if (this.adminStatus === 'busy') return 'Istirahat';
                    return 'Offline';
                },

                playNotif() { this.notifSound.play().catch(e => {}); },

                formatTime(datetimeString) {
                    if (!datetimeString) return '';
                    const date = new Date(datetimeString);
                    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                getPreviewText(chat) {
                    if (chat.status === 'pending') return 'Menunggu respon...';
                    if (chat.status === 'queued') return `Antrean #${chat.queue_position}`;
                    if (chat.status === 'active' && chat.admin_id !== this.adminId) {
                        return `Ditangani oleh ${chat.admin ? chat.admin.username : 'agen lain'}`;
                    }
                    return 'Sedang aktif';
                },

                selectChat(chat) {
                    this.selectedChat = chat;
                    // Auto-hide sidebar on mobile after selecting a chat
                    if (window.innerWidth < 1024) {
                        this.showSidebar = false;
                    }
                },

                handleStatusChange(e) {
                    const idx = this.chats.findIndex(c => c.id === e.conversation_id);
                    if (idx !== -1) {
                        this.chats[idx].status = e.status;
                        this.chats[idx].admin_id = e.admin_id;
                        this.chats[idx].queue_position = e.queue_position;
                        if (e.status === 'closed') {
                             this.chats.splice(idx, 1);
                             if (this.selectedChat && this.selectedChat.id === e.conversation_id) this.selectedChat = null;
                        }
                    } else if (['pending', 'queued'].includes(e.status)) {
                        localStorage.setItem('play_chat_notif', 'true');
                        window.location.reload(); 
                    }
                },

                async updateStatus() {
                    fetch('{{ route('admin.status.update') }}', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ status: this.adminStatus })
                    });
                },

                async claimChat(conversationId) {
                    this.isClaiming = true;
                    try {
                        const res = await fetch(`/admin/conversation/${conversationId}/claim`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
                        });
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.error);
                        const chat = this.chats.find(c => c.id === conversationId);
                        if(chat) { chat.status = 'active'; chat.admin_id = this.adminId; }
                    } catch (error) { alert(error.message); window.location.reload(); }
                    finally { this.isClaiming = false; }
                },

                async closeChat() {
                    try {
                        this.isSubmitting = true;
                        await fetch(`/admin/conversation/${this.selectedChat.id}/close`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ problem_category: this.closeCategory })
                        });
                        window.location.reload();
                    } catch (e) { alert('Gagal'); this.isSubmitting = false; }
                },

                async handoverChat() {
                    try {
                        this.isSubmitting = true;
                        await fetch(`/admin/conversation/${this.selectedChat.id}/handover`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                            body: JSON.stringify({ to_admin_id: this.handoverToAdminId })
                        });
                        window.location.reload();
                    } catch (e) { alert('Gagal'); this.isSubmitting = false; }
                },

                async blockUser(conversationId) {
                    if (!confirm('Blokir permanen?')) return;
                    try {
                        await fetch(`/admin/conversation/${conversationId}/block`, {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                        });
                        window.location.reload();
                    } catch (e) { alert('Gagal'); }
                }
            }));
        });
    </script>
</body>
</html>
