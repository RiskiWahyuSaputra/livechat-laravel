<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Admin Workspace</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
        
        .pulse-red { animation: pulse-red 2s infinite; }
        @keyframes pulse-red {
            0% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0.4); }
            70% { box-shadow: 0 0 0 6px rgba(220, 38, 38, 0); }
            100% { box-shadow: 0 0 0 0 rgba(220, 38, 38, 0); }
        }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex overflow-hidden" 
      x-data="adminDashboard({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }})">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Navbar dengan Identitas Best Corporation -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between shrink-0 z-30 shadow-sm">
            <div class="flex items-center gap-3 md:gap-4">
                <!-- Mobile Toggle -->
                <button @click="showSidebar = !showSidebar" class="lg:hidden p-2 hover:bg-slate-100 rounded-xl transition-colors">
                    <svg class="w-6 h-6 text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"></path></svg>
                </button>

                <div class="flex flex-col">
                    <h2 class="font-black text-[#0a1d37] text-lg tracking-tighter uppercase">Chat Workspace</h2>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-widest">Manage active conversations</p>
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

    <div class="flex-1 flex overflow-hidden relative">
        
        <!-- Sidebar: Tema Navy -->
        <aside class="absolute lg:relative z-20 w-full xs:w-[320px] md:w-[340px] h-full bg-white border-r border-slate-200 flex flex-col shrink-0 transition-transform duration-300 transform lg:translate-x-0 shadow-xl lg:shadow-none"
               :class="showSidebar ? 'translate-x-0' : '-translate-x-full'">
            
            <div class="px-6 py-6 shrink-0 bg-white">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="font-black text-[#0a1d37] text-xl tracking-tighter">Percakapan</h2>
                    <span class="bg-red-100 text-red-600 text-[10px] font-black px-2.5 py-1 rounded-lg uppercase" x-text="filteredChats.length"></span>
                </div>
                
                <div class="relative">
                    <input type="text" x-model="searchQuery" placeholder="Cari nama, kontak, atau instansi..." 
                           class="w-full bg-slate-50 border-2 border-slate-100 rounded-2xl px-10 py-3 text-sm focus:ring-2 focus:ring-red-500/10 focus:border-red-500/20 placeholder:text-slate-400 font-bold transition-all">
                    <svg class="w-4 h-4 absolute left-4 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <div class="flex-1 overflow-y-auto px-4 pb-6 space-y-6">
                <!-- Section: Antrean -->
                <div>
                    <div class="px-2 mb-3 flex items-center gap-2">
                        <span class="text-[10px] font-black text-red-600 uppercase tracking-[0.2em]">Permintaan Baru</span>
                        <div class="h-0.5 bg-red-50 flex-1"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <!-- Empty State: Antrean -->
                        <div x-show="filteredChats.filter(c => ['pending', 'queued'].includes(c.status)).length === 0" x-cloak
                             class="py-8 px-4 text-center border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50">
                            <div class="w-12 h-12 bg-white rounded-2xl mx-auto flex items-center justify-center mb-3 shadow-sm text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                            </div>
                            <p class="text-xs font-bold text-slate-500">Belum ada antrean masuk.</p>
                            <span class="text-[10px] text-slate-400 font-medium">Anda bisa bersantai sejenak! ☕</span>
                        </div>

                        <template x-for="chat in filteredChats.filter(c => ['pending', 'queued'].includes(c.status))" :key="chat.id">
                            <div @click="selectChat(chat)" 
                                 class="p-4 rounded-3xl cursor-pointer transition-all duration-300 group relative border-2 border-transparent"
                                 :class="selectedChat && selectedChat.id === chat.id ? 'bg-red-50/50 border-red-500/30' : 'bg-white hover:bg-slate-50 border-slate-50'">
                                <div class="flex items-center gap-4 relative z-10">
                                    <div class="w-12 h-12 rounded-2xl bg-[#0a1d37] flex items-center justify-center font-black text-white shrink-0 pulse-red">
                                        <span x-text="chat.user.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <div class="flex justify-between items-start mb-0.5">
                                            <p class="font-black text-[14px] text-slate-800 truncate" x-text="chat.user.name"></p>
                                            <span class="text-[10px] font-black text-red-600" x-text="formatTime(chat.last_message_at)"></span>
                                        </div>
                                        <p class="text-[11px] font-bold text-red-500 uppercase tracking-tighter" x-text="chat.status === 'queued' ? 'Antrean #' + chat.queue_position : 'Baru'"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Section: Aktif -->
                <div>
                    <div class="px-2 mb-3 flex items-center gap-2">
                        <span class="text-[10px] font-black text-[#0a1d37] uppercase tracking-[0.2em]">Sedang Dibantu</span>
                        <div class="h-0.5 bg-slate-100 flex-1"></div>
                    </div>
                    
                    <div class="space-y-2">
                        <!-- Empty State: Aktif -->
                        <div x-show="filteredChats.filter(c => c.status === 'active').length === 0" x-cloak
                             class="py-8 px-4 text-center border-2 border-dashed border-slate-200 rounded-3xl bg-slate-50/50">
                            <div class="w-12 h-12 bg-white rounded-2xl mx-auto flex items-center justify-center mb-3 shadow-sm text-slate-400">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            </div>
                            <p class="text-xs font-bold text-slate-500">Belum ada progres.</p>
                            <span class="text-[10px] text-slate-400 font-medium">Bantu pelanggan di antrean!</span>
                        </div>

                        <template x-for="chat in filteredChats.filter(c => c.status === 'active')" :key="chat.id">
                            <div @click="selectChat(chat)" 
                                 class="p-4 rounded-3xl cursor-pointer transition-all duration-300 group border-2"
                                 :class="selectedChat && selectedChat.id === chat.id ? 'bg-[#0a1d37] text-white border-[#0a1d37] shadow-xl shadow-slate-200' : 'bg-white border-slate-50 hover:border-slate-200'">
                                <div class="flex items-center gap-4">
                                    <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black shrink-0 border-2"
                                         :class="selectedChat && selectedChat.id === chat.id 
                                            ? 'bg-red-600 border-red-500 text-white' 
                                            : (chat.admin_id === adminId ? 'bg-[#0a1d37] border-slate-700 text-white' : 'bg-slate-100 border-slate-200 text-slate-400')">
                                        <span x-text="chat.user.name.charAt(0).toUpperCase()"></span>
                                    </div>
                                    <div class="flex-1 overflow-hidden">
                                        <div class="flex justify-between items-start mb-0.5">
                                            <p class="font-black text-[14px] truncate" :class="selectedChat && selectedChat.id === chat.id ? 'text-white' : 'text-slate-800'" x-text="chat.user.name"></p>
                                            <span class="text-[10px] font-bold" :class="selectedChat && selectedChat.id === chat.id ? 'text-slate-400' : 'text-slate-400'" x-text="formatTime(chat.last_message_at)"></span>
                                        </div>
                                        <p class="text-[11px] font-medium truncate" :class="selectedChat && selectedChat.id === chat.id ? 'text-slate-300' : 'text-slate-500'" x-text="getPreviewText(chat)"></p>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Panel: Area Chat -->
        <main class="flex-1 flex flex-col bg-slate-50 relative overflow-hidden z-10">
            
            <!-- Empty State -->
            <template x-if="!selectedChat">
                <div class="flex-1 flex flex-col items-center justify-center p-6 text-center">
                    <div class="w-24 h-24 md:w-40 md:h-40 bg-white rounded-[40px] shadow-2xl border border-slate-100 flex items-center justify-center mb-8 relative">
                        <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-2/3 h-2/3 object-contain opacity-20 grayscale">
                        <div class="absolute inset-0 flex items-center justify-center">
                        </div>
                    </div>
                    <h3 class="text-2xl font-black text-slate-800 mb-2 tracking-tighter uppercase">Support Workspace</h3>
                    <p class="text-slate-500 max-w-xs text-sm font-bold">Pilih percakapan untuk memberikan layanan terbaik hari ini.</p>
                </div>
            </template>

            <!-- Active Chat Interface -->
            <template x-if="selectedChat">
                <div class="w-full h-full flex flex-col bg-white">
                    
                    <!-- Chat Header -->
                    <div class="bg-white border-b border-slate-100 px-4 md:px-8 py-4 flex items-center justify-between shrink-0 shadow-sm relative">
                        <!-- Red Accent Bar -->
                        <div class="absolute top-0 left-0 right-0 h-1 bg-red-600"></div>

                        <div class="flex items-center gap-3 md:gap-4 overflow-hidden mt-1">
                            <div class="w-10 h-10 md:w-12 md:h-12 rounded-2xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-base md:text-xl shrink-0 shadow-lg shadow-slate-200">
                                <span x-text="selectedChat.user.name.charAt(0).toUpperCase()"></span>
                            </div>
                            <div class="overflow-hidden">
                                <h3 class="font-black text-slate-900 text-base md:text-xl leading-tight truncate" x-text="selectedChat.user.name"></h3>
                                <div class="flex items-center gap-2 text-[10px] md:text-xs font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                                    <span class="flex items-center gap-1.5 shrink-0 text-red-600">
                                        <div class="w-2 h-2 rounded-full bg-red-600 animate-pulse"></div>
                                        <span x-text="selectedChat.status"></span>
                                    </span>
                                    <span class="hidden xs:inline text-slate-200">|</span>
                                    <span class="truncate hidden xs:inline" x-text="(selectedChat.user.contact || '') + ' - ' + (selectedChat.user.origin || '')"></span>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Header Actions: Red Theme -->
                        <div class="flex items-center gap-1 md:gap-3">
                            <template x-if="['pending', 'queued'].includes(selectedChat.status)">
                                <button class="bg-red-600 hover:bg-red-700 text-white shadow-xl shadow-red-200 text-xs md:text-sm font-black px-4 md:px-8 py-2.5 md:py-3 rounded-2xl transition-all hover:scale-105 active:scale-95"
                                        @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                    <span x-text="isClaiming ? 'PROSES...' : 'AMBIL CHAT'"></span>
                                </button>
                            </template>

                            <template x-if="selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                <div class="flex items-center gap-1 md:gap-2">
                                    <button class="flex items-center gap-1.5 px-3 py-2 md:py-2.5 rounded-2xl text-xs md:text-sm font-black bg-blue-50 text-blue-600 hover:bg-blue-100 transition-all shadow-sm"
                                            @click="showUserInfo = !showUserInfo">
                                        <svg class="w-4 h-4" :class="showUserInfo ? 'rotate-180 transition-transform' : 'transition-transform'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                        <span class="hidden md:inline">INFO</span>
                                    </button>
                                    
                                    <div class="w-px h-6 bg-slate-200 mx-1 hidden md:block"></div>

                                    <button class="flex items-center gap-2 px-3 md:px-5 py-2 md:py-2.5 rounded-2xl text-xs md:text-sm font-black bg-[#0a1d37] text-white hover:bg-slate-800 transition-all shadow-lg shadow-slate-200"
                                            @click="showHandoverModal = true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        <span class="hidden md:inline">OPER</span>
                                    </button>
                                    <button class="flex items-center gap-2 px-3 md:px-5 py-2 md:py-2.5 rounded-2xl text-xs md:text-sm font-black bg-red-600 text-white hover:bg-red-700 transition-all shadow-lg shadow-red-200"
                                            @click="showCloseModal = true">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"></path></svg>
                                        <span class="hidden md:inline">SELESAI</span>
                                    </button>
                                    
                                    <div class="w-px h-6 bg-slate-200 mx-1 hidden md:block"></div>

                                    <button class="w-10 h-10 flex items-center justify-center rounded-2xl text-slate-400 hover:text-red-600 hover:bg-red-50 transition-all"
                                            @click="blockUser(selectedChat.id)">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Middle Section: Iframe & User Info Panel -->
                    <div class="flex-1 flex overflow-hidden relative">
                        <!-- Chat Iframe -->
                        <div class="flex-1 bg-slate-50 relative">
                            <iframe class="w-full h-full border-none" :src="'/admin/conversation/' + selectedChat.id"></iframe>
                        </div>

                        <!-- User Info Sidebar Panel -->
                        <div x-show="showUserInfo" 
                             x-collapse.horizontal.duration.300ms
                             class="w-full sm:w-80 md:w-96 bg-white border-l border-slate-200 shrink-0 overflow-y-auto hidden sm:block shadow-[-10px_0_15px_-3px_rgba(0,0,0,0.05)] z-20"
                             style="display: none;">
                            <div class="p-6">
                                <div class="flex items-center justify-between mb-6">
                                    <h4 class="font-black text-[#0a1d37] text-lg uppercase tracking-tighter">Profil Pelanggan</h4>
                                    <button @click="showUserInfo = false" class="p-2 text-slate-400 hover:text-red-500 hover:bg-red-50 rounded-xl transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M6 18L18 6M6 6l12 12"></path></svg>
                                    </button>
                                </div>

                                <div class="flex flex-col items-center text-center mb-6">
                                    <div class="relative mb-4 ring-4 ring-slate-50 rounded-3xl">
                                        <div class="w-24 h-24 rounded-3xl bg-gradient-to-br from-[#0a1d37] to-slate-700 flex items-center justify-center font-black text-white text-4xl shadow-lg shadow-slate-200">
                                            <span x-text="selectedChat.user.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                        <div class="absolute -bottom-2 -right-2 w-7 h-7 rounded-xl border-4 border-white flex items-center justify-center"
                                             :class="selectedChat.user.is_online ? 'bg-emerald-500' : 'bg-slate-300'">
                                        </div>
                                    </div>
                                    <h3 class="font-black text-xl text-slate-900 leading-tight mb-1" x-text="selectedChat.user.name"></h3>
                                    <p class="text-xs font-bold text-slate-500" x-text="selectedChat.user.contact"></p>
                                    <p class="text-[10px] font-bold text-slate-400 mt-0.5 rounded-md bg-slate-50 px-2 py-1 inline-block" x-text="selectedChat.user.origin"></p>
                                </div>

                                <div class="space-y-4">
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100">
                                        <p class="text-[10px] font-black text-[#0a1d37] uppercase tracking-widest mb-1">Status Akun</p>
                                        <div class="flex items-center gap-2">
                                            <span class="px-2.5 py-1 rounded-lg text-xs font-bold uppercase tracking-wide"
                                                  :class="selectedChat.user.is_blocked ? 'bg-red-100 text-red-600' : 'bg-emerald-100 text-emerald-700'"
                                                  x-text="selectedChat.user.is_blocked ? 'DIBLOKIR' : 'AKTIF'"></span>
                                        </div>
                                    </div>

                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm shrink-0">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-[#0a1d37] uppercase tracking-widest mb-0.5">Bergabung Sejak</p>
                                            <p class="text-sm font-bold text-slate-700" x-text="formatDate(selectedChat.user.created_at)"></p>
                                        </div>
                                    </div>
                                    
                                    <div class="bg-slate-50 p-4 rounded-2xl border border-slate-100 flex items-start gap-4">
                                        <div class="w-10 h-10 rounded-xl bg-white flex items-center justify-center shadow-sm shrink-0">
                                            <svg class="w-5 h-5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V8a2 2 0 00-2-2h-5m-4 0V5a2 2 0 114 0v1m-4 0a2 2 0 104 0m-5 8a2 2 0 100-4 2 2 0 000 4zm0 0c1.306 0 2.417.835 2.83 2M9 14a3.001 3.001 0 00-2.83 2M15 11h3m-3 4h2"></path></svg>
                                        </div>
                                        <div>
                                            <p class="text-[10px] font-black text-[#0a1d37] uppercase tracking-widest mb-0.5">ID Pelanggan</p>
                                            <p class="text-sm font-bold text-slate-700" x-text="'USR-' + String(selectedChat.user.id).padStart(4, '0')"></p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </template>
        </main>
    </div>

    <!-- Modals dengan Tema Best Corp -->
    <div x-show="showCloseModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#0a1d37]/60 backdrop-blur-md" @click="showCloseModal = false"></div>
        <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-md overflow-hidden relative z-10 p-8 border border-white" x-transition>
            <div class="w-16 h-16 rounded-3xl bg-red-50 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            </div>
            <h3 class="text-3xl font-black text-[#0a1d37] mb-2 tracking-tighter">SELESAIKAN CHAT</h3>
            <p class="text-slate-500 font-bold text-sm mb-8">Pilih kategori kesimpulan sesi ini.</p>
            
            <div class="grid grid-cols-1 gap-3 mb-8">
                <template x-for="cat in ['Info Produk', 'Dukungan Teknis', 'Pembayaran', 'Komplain', 'Lainnya']">
                    <button @click="closeCategory = cat" 
                            class="px-6 py-4 rounded-2xl border-2 font-black text-sm transition-all text-left flex justify-between items-center"
                            :class="closeCategory === cat ? 'bg-[#0a1d37] border-[#0a1d37] text-white shadow-xl shadow-slate-200' : 'bg-slate-50 border-transparent text-slate-700 hover:border-slate-200'">
                        <span x-text="cat"></span>
                        <div x-show="closeCategory === cat" class="w-2 h-2 rounded-full bg-red-500"></div>
                    </button>
                </template>
            </div>
            
            <div class="flex flex-col gap-3">
                <button class="w-full py-5 bg-red-600 text-white rounded-3xl font-black shadow-xl shadow-red-200 hover:bg-red-700 transition-all" 
                        @click="closeChat()" :disabled="!closeCategory">SIMPAN & TUTUP</button>
                <button class="w-full py-2 text-slate-400 font-bold text-xs" @click="showCloseModal = false">BATALKAN</button>
            </div>
        </div>
    </div>

    <!-- Handover Modal -->
    <div x-show="showHandoverModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
        <div class="absolute inset-0 bg-[#0a1d37]/60 backdrop-blur-md" @click="showHandoverModal = false"></div>
        <div class="bg-white rounded-[3rem] shadow-2xl w-full max-w-md overflow-hidden relative z-10 p-8 border border-white" x-transition>
            <div class="w-16 h-16 rounded-3xl bg-slate-100 flex items-center justify-center mb-6">
                <svg class="w-10 h-10 text-[#0a1d37]" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            </div>
            <h3 class="text-3xl font-black text-[#0a1d37] mb-2 tracking-tighter uppercase">Oper Bantuan</h3>
            <p class="text-slate-500 font-bold text-sm mb-8">Alihkan ke rekan agen yang sedang tersedia.</p>
            
            <select x-model="handoverToAdminId" 
                    class="w-full px-6 py-5 bg-slate-50 border-2 border-transparent rounded-2xl text-sm font-black text-slate-700 mb-8 focus:border-red-500 appearance-none cursor-pointer">
                <option value="" disabled>-- Pilih Rekan Tim --</option>
                @foreach($otherAdmins as $other)
                    <option value="{{ $other->id }}">{{ $other->username }} ({{ ucfirst($other->status) }})</option>
                @endforeach
            </select>
            
            <div class="flex flex-col gap-3">
                <button class="w-full py-5 bg-[#0a1d37] text-white rounded-3xl font-black shadow-xl shadow-slate-300 hover:bg-slate-800 transition-all" 
                        @click="handoverChat()" :disabled="!handoverToAdminId">OPER SEKARANG</button>
                <button class="w-full py-2 text-slate-400 font-bold text-xs" @click="showHandoverModal = false">BATALKAN</button>
            </div>
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
                showUserInfo: false,
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
                    window.addEventListener('resize', () => {
                        if (window.innerWidth >= 1024) this.showSidebar = true;
                    });
                },

                get filteredChats() {
                    if (!this.searchQuery.trim()) return this.chats;
                    const query = this.searchQuery.toLowerCase();
                    return this.chats.filter(chat => 
                        (chat.user.name || '').toLowerCase().includes(query) || 
                        (chat.user.contact || '').toLowerCase().includes(query) ||
                        (chat.user.origin || '').toLowerCase().includes(query)
                    );
                },

                get adminStatusText() {
                    if (this.adminStatus === 'online') return 'Online';
                    if (this.adminStatus === 'busy') return 'Istirahat';
                    return 'Offline';
                },

                playNotif() { this.notifSound.play().catch(e => {}); },

                formatTime(datetimeString) {
                    if (!datetimeString) return '';
                    const date = new Date(datetimeString);
                    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                formatDate(datetimeString) {
                    if (!datetimeString) return '-';
                    const date = new Date(datetimeString);
                    return date.toLocaleDateString('id-ID', {day: 'numeric', month: 'long', year: 'numeric'});
                },

                getPreviewText(chat) {
                    if (chat.status === 'pending') return 'Menunggu respon...';
                    if (chat.status === 'queued') return `Antrean: #${chat.queue_position}`;
                    if (chat.status === 'active' && chat.admin_id !== this.adminId) {
                        return `Oleh ${chat.admin ? chat.admin.username : 'agen lain'}`;
                    }
                    return 'Sesi aktif';
                },

                selectChat(chat) {
                    this.selectedChat = chat;
                    this.showUserInfo = false; // tutup info panel saat ganti chat
                    if (window.innerWidth < 1024) this.showSidebar = false;
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
    </div>
</body>
</html>
