<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - LiveChat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-100 text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      x-data="adminDashboard({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }})">

    <!-- Top Navbar -->
    <header class="bg-slate-900 text-white px-6 py-3 flex items-center justify-between shrink-0 shadow-md relative z-20">
        <div class="flex items-center gap-3">
            <div class="w-8 h-8 bg-blue-600 rounded-lg flex items-center justify-center">
                <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
            </div>
            <div>
                <h1 class="font-bold text-sm tracking-wide">LiveChat Workspace</h1>
                <span class="text-[10px] uppercase font-bold text-blue-300 tracking-wider">Role: {{ $admin->role }}</span>
            </div>
        </div>
        
        <div class="flex items-center gap-4 relative" x-data="{ showProfile: false }">
            <!-- Profile Trigger -->
            <button @click="showProfile = !showProfile" @click.away="showProfile = false" 
                    class="flex items-center gap-3 hover:bg-slate-800 px-3 py-1.5 rounded-xl transition-all border border-transparent hover:border-slate-700">
                <div class="relative">
                    <div class="w-8 h-8 rounded-full bg-blue-500 flex items-center justify-center font-bold text-sm border-2 border-slate-700">
                        {{ strtoupper(substr($admin->username, 0, 1)) }}
                    </div>
                    <!-- Indicator Status Kecil -->
                    <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 rounded-full border-2 border-slate-900"
                          :class="{
                              'bg-emerald-500': adminStatus === 'online',
                              'bg-rose-500': adminStatus === 'busy',
                              'bg-slate-500': adminStatus === 'offline'
                          }"></span>
                </div>
                <div class="text-left hidden sm:block">
                    <p class="text-xs font-bold leading-none mb-1">{{ $admin->username }}</p>
                    <p class="text-[10px] text-slate-400 font-medium leading-none uppercase tracking-tighter" x-text="adminStatusText"></p>
                </div>
                <svg class="w-4 h-4 text-slate-500 transition-transform" :class="showProfile ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path></svg>
            </button>

            <!-- Profile Popup / Dropdown -->
            <div x-show="showProfile" x-cloak 
                 x-transition:enter="transition ease-out duration-100"
                 x-transition:enter-start="transform opacity-0 scale-95"
                 x-transition:enter-end="transform opacity-100 scale-100"
                 class="absolute right-0 top-full mt-2 w-64 bg-white rounded-2xl shadow-2xl border border-slate-200 py-2 text-slate-800 z-50 overflow-hidden">
                
                <!-- Info Header -->
                <div class="px-5 py-4 border-b border-slate-100 bg-slate-50/50">
                    <div class="flex items-center gap-3 mb-3">
                         <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center font-bold text-xl text-white shadow-lg shadow-blue-500/20">
                            {{ strtoupper(substr($admin->username, 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-bold text-slate-800 truncate max-w-[130px]">{{ $admin->username }}</p>
                            <p class="text-[11px] font-bold text-blue-600 uppercase tracking-widest">{{ $admin->role }}</p>
                        </div>
                    </div>
                    <p class="text-[11px] text-slate-500 font-medium truncate">{{ $admin->email }}</p>
                </div>

                <!-- Status Selector -->
                <div class="p-2">
                    <p class="px-3 pt-2 pb-1 text-[10px] font-bold text-slate-400 uppercase tracking-wider">Update Status Anda</p>
                    
                    <button @click="adminStatus = 'online'; updateStatus(); showProfile = false" 
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-emerald-50 transition-colors group">
                        <span class="w-2.5 h-2.5 rounded-full bg-emerald-500 shadow-[0_0_8px_rgba(16,185,129,0.4)]"></span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-emerald-700">Online</span>
                        <svg x-show="adminStatus === 'online'" class="w-4 h-4 ml-auto text-emerald-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>

                    <button @click="adminStatus = 'busy'; updateStatus(); showProfile = false" 
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-rose-50 transition-colors group">
                        <span class="w-2.5 h-2.5 rounded-full bg-rose-500 shadow-[0_0_8px_rgba(244,63,94,0.4)]"></span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-rose-700">Sibuk / Jeda</span>
                        <svg x-show="adminStatus === 'busy'" class="w-4 h-4 ml-auto text-rose-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>

                    <button @click="adminStatus = 'offline'; updateStatus(); showProfile = false" 
                            class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl hover:bg-slate-100 transition-colors group">
                        <span class="w-2.5 h-2.5 rounded-full bg-slate-500"></span>
                        <span class="text-sm font-semibold text-slate-700 group-hover:text-slate-900">Offline</span>
                        <svg x-show="adminStatus === 'offline'" class="w-4 h-4 ml-auto text-slate-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                    </button>
                </div>

                <!-- Footer / Logout -->
                <div class="mt-1 p-2 border-t border-slate-100">
                    <form method="POST" action="{{ route('admin.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-3 py-2.5 rounded-xl text-rose-600 hover:bg-rose-50 transition-colors font-bold text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Keluar dari Sesi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <div class="flex-1 flex overflow-hidden">
        
        <!-- Sidebar Daftar Chat -->
        <aside class="w-80 bg-white border-r border-slate-200 flex flex-col shrink-0 z-10 shadow-[4px_0_24px_rgba(0,0,0,0.02)] relative">
            
            <div class="px-5 py-4 border-b border-slate-100 flex items-center justify-between shrink-0">
                <h2 class="font-bold text-slate-700 text-sm uppercase tracking-wider">Daftar Percakapan</h2>
                <span class="bg-blue-100 text-blue-700 text-[10px] font-bold px-2 py-0.5 rounded-full" x-text="chats.length"></span>
            </div>
            
            <div class="flex-1 overflow-y-auto w-full">
                <!-- Empty State -->
                <template x-if="chats.length === 0">
                    <div class="p-8 text-center flex flex-col items-center justify-center h-full text-slate-400">
                        <svg class="w-12 h-12 mb-3 text-slate-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4"></path></svg>
                        <p class="text-[13px] font-medium">Kotak masuk kosong</p>
                        <p class="text-[11px] mt-1">Belum ada pelanggan menunggu.</p>
                    </div>
                </template>
                
                <!-- List Chat -->
                <div class="divide-y divide-slate-50">
                    <template x-for="chat in chats" :key="chat.id">
                        <div class="p-4 cursor-pointer hover:bg-slate-50 transition-colors border-l-4 group"
                             :class="selectedChat && selectedChat.id === chat.id 
                                ? 'bg-blue-50/50 border-blue-500 shadow-[inset_0_1px_4px_rgba(0,0,0,0.02)]' 
                                : 'border-transparent hover:border-slate-300'"
                             @click="selectChat(chat)">
                            
                            <div class="flex justify-between items-start mb-1">
                                <span class="font-semibold text-sm text-slate-800 flex items-center gap-1.5 truncate max-w-[70%]">
                                    <span class="w-2 h-2 rounded-full shrink-0" 
                                          :class="{
                                              'bg-amber-400 shadow-[0_0_8px_rgba(251,191,36,0.5)]': chat.status === 'pending',
                                              'bg-purple-500': chat.status === 'queued',
                                              'bg-emerald-500': chat.status === 'active'
                                          }"></span>
                                    <span class="truncate" x-text="chat.user.name"></span>
                                </span>
                                <span class="text-[10px] font-medium text-slate-400 mt-0.5 shrink-0" x-text="formatTime(chat.last_message_at)"></span>
                            </div>
                            
                            <div class="text-[12px] text-slate-500 truncate" x-text="getPreviewText(chat)"></div>
                        </div>
                    </template>
                </div>
            </div>
        </aside>

        <!-- Main Panel -->
        <div class="flex-1 flex flex-col bg-slate-50 relative">
            
            <!-- Default Welcome Screen -->
            <template x-if="!selectedChat">
                <div class="flex-1 flex flex-col items-center justify-center text-slate-400 px-6 text-center">
                    <div class="w-16 h-16 bg-white rounded-2xl shadow-sm border border-slate-100 flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                    </div>
                    <h3 class="text-lg font-semibold text-slate-600 mb-1">Pilih Obrolan Tiket</h3>
                    <p class="text-sm">Klik salah satu obrolan dari daftar di sebelah kiri untuk mulai merespon.</p>
                </div>
            </template>

            <!-- Active Chat Viewer -->
            <template x-if="selectedChat">
                <div class="w-full h-full flex flex-col">
                    
                    <!-- Chat Header Banner -->
                    <div class="bg-white border-b border-slate-200 px-6 py-4 flex items-center justify-between shrink-0 shadow-sm z-10 hover:bg-slate-50/50 transition-colors">
                        <div>
                            <div class="flex items-center gap-2 mb-0.5">
                                <h3 class="font-bold text-slate-800 text-[15px]" x-text="selectedChat.user.name"></h3>
                                <span class="px-2 py-0.5 text-[10px] font-bold rounded-md bg-slate-100 uppercase tracking-wide text-slate-500 border border-slate-200" x-text="'#' + selectedChat.id"></span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-slate-500">
                                <span class="flex items-center gap-1">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                                    <span x-text="selectedChat.user.email"></span>
                                </span>
                                <span>&bull;</span>
                                <span class="capitalize font-medium flex items-center gap-1"
                                      :class="{
                                          'text-amber-600': selectedChat.status === 'pending',
                                          'text-purple-600': selectedChat.status === 'queued',
                                          'text-emerald-600': selectedChat.status === 'active',
                                          'text-slate-400': selectedChat.status === 'closed'
                                      }">
                                      <span x-text="selectedChat.status"></span>
                                </span>
                            </div>
                        </div>
                        
                        <!-- Panel Aksi Chat Kanan Atas -->
                        <div class="flex gap-2">
                            <!-- Klaim -->
                            <template x-if="['pending', 'queued'].includes(selectedChat.status)">
                                <button class="bg-blue-600 hover:bg-blue-700 text-white shadow-md shadow-blue-500/20 text-sm font-semibold px-5 py-2 rounded-xl transition-all disabled:opacity-50 flex items-center gap-2"
                                        @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                    <span x-text="isClaiming ? 'Mengklaim...' : 'Ambil Alih Obrolan'"></span>
                                </button>
                            </template>

                            <!-- Menu Oper & Akhiri -->
                            <template x-if="selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                <div class="flex bg-slate-100 p-1 rounded-xl border border-slate-200">
                                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] font-semibold text-amber-600 hover:bg-amber-100 transition-colors"
                                            @click="showHandoverModal = true" title="Transfer obrolan ke tim lain">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                                        Oper
                                    </button>
                                    
                                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] font-semibold text-rose-600 hover:bg-rose-100 transition-colors"
                                            @click="showCloseModal = true" title="Tandai selesai">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>
                                        Selesai
                                    </button>
                                    
                                    <div class="w-px bg-slate-200 mx-1 my-1"></div>

                                    <button class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-[13px] font-bold text-slate-500 hover:text-slate-800 hover:bg-slate-200 transition-colors"
                                            @click="blockUser(selectedChat.id)" title="Blokir Pengguna (Spam)">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </div>

                    <!-- Iframe Chat -->
                    <iframe class="w-full h-full border-none bg-transparent" :src="'/admin/conversation/' + selectedChat.id"></iframe>
                </div>
            </template>
        </div>
    </div>

    <!-- Modal Akhiri Chat -->
    <div x-show="showCloseModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden" x-transition>
            <div class="px-6 py-5 border-b border-slate-100">
                <h3 class="text-lg font-bold text-slate-800">Tutup Obrolan</h3>
                <p class="text-[13px] text-slate-500 mt-1 leading-relaxed">Pilih kategori kesimpulan akhir untuk laporan metrik bulanan.</p>
            </div>
            
            <div class="p-6">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-2">Kategori Masalah</label>
                <select x-model="closeCategory" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[14px] text-slate-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 focus:bg-white transition-all cursor-pointer">
                    <option value="" disabled>-- Pilih Kategori --</option>
                    <option value="Tanya Harga">Tanya Harga / Produk</option>
                    <option value="Komplain">Keluhan / Komplain</option>
                    <option value="Dukungan Teknis">Kendala Teknis</option>
                    <option value="Lainnya">Lain-lain</option>
                </select>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors" @click="showCloseModal = false">Batal</button>
                <button class="px-4 py-2 text-sm font-semibold text-white bg-blue-600 border border-blue-600 hover:bg-blue-700 rounded-lg shadow-sm disabled:opacity-50 disabled:cursor-not-allowed" @click="closeChat()" :disabled="!closeCategory">Selesai & Tutup</button>
            </div>
        </div>
    </div>

    <!-- Modal Oper Chat -->
    <div x-show="showHandoverModal" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/40 backdrop-blur-sm p-4">
        <div @click.stop class="bg-white rounded-2xl shadow-xl w-full max-w-sm overflow-hidden" x-transition>
            
            <div class="px-6 py-5 border-b border-slate-100 flex items-start gap-3">
                <div class="w-10 h-10 rounded-full bg-amber-100 flex items-center justify-center shrink-0">
                    <svg class="w-5 h-5 text-amber-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
                </div>
                <div>
                    <h3 class="text-lg font-bold text-slate-800">Transfer Obrolan</h3>
                    <p class="text-[13px] text-slate-500 mt-0.5 leading-relaxed">Teruskan pelanggan ini ke kawan agen yang lain.</p>
                </div>
            </div>
            
            <div class="p-6">
                <label class="block text-[11px] font-bold text-slate-400 uppercase tracking-wide mb-2">Pilih Agen Penerima</label>
                <select x-model="handoverToAdminId" 
                        class="w-full px-4 py-2.5 bg-slate-50 border border-slate-200 rounded-xl text-[14px] text-slate-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-amber-500 focus:bg-white transition-all cursor-pointer">
                    <option value="" disabled>-- Pilih Agen Tujuan --</option>
                    @forelse($otherAdmins as $other)
                        <option value="{{ $other->id }}">{{ $other->username }} ({{ ucfirst($other->status) }})</option>
                    @empty
                        <option value="" disabled>Tidak ada rekan kerja online saat ini.</option>
                    @endforelse
                </select>
                <p class="text-[11px] text-amber-600 mt-3 font-medium flex gap-1.5 bg-amber-50 p-2 rounded-lg border border-amber-100">
                    <svg class="w-3.5 h-3.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    Setelah dialihkan, Anda hanya dapat memantau chat ini (Read-Only).
                </p>
            </div>
            
            <div class="px-6 py-4 bg-slate-50 border-t border-slate-100 flex justify-end gap-3">
                <button class="px-4 py-2 text-sm font-semibold text-slate-600 hover:text-slate-800 hover:bg-slate-200 rounded-lg transition-colors" @click="showHandoverModal = false">Batal</button>
                <button class="px-5 py-2 text-sm font-semibold text-white bg-amber-500 border border-amber-500 hover:bg-amber-600 rounded-lg shadow-sm shadow-amber-500/20 disabled:opacity-50 disabled:cursor-not-allowed transition-all" @click="handoverChat()" :disabled="!handoverToAdminId || isSubmitting">Transfer Chat</button>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminDashboard', (adminId, initPending, initActive) => ({
                adminId: adminId,
                chats: [...initPending, ...initActive],
                selectedChat: null,
                adminStatus: '{{ $admin->status }}',
                isClaiming: false,
                isSubmitting: false,
                showCloseModal: false,
                showHandoverModal: false,
                closeCategory: '',
                handoverToAdminId: '',
                notifSound: new Audio('https://actions.google.com/sounds/v1/alarms/beep_short.ogg'), // Suara Ting!

                init() {
                    // Putar bunyi jika disetel dari refresh sebelumnya
                    if (localStorage.getItem('play_chat_notif') === 'true') {
                        this.playNotif();
                        localStorage.removeItem('play_chat_notif');
                    }

                    // Subscribe ke general admin dashboard channel untuk notifikasi antrean masuk
                    if (window.Echo) {
                        window.Echo.private('admin.dashboard')
                            .listen('.conversation.status.changed', (e) => {
                                this.handleStatusChange(e);
                            });
                    }
                },

                get adminStatusText() {
                    if (this.adminStatus === 'online') return 'Sedang Online';
                    if (this.adminStatus === 'busy') return 'Sibuk / Jeda';
                    return 'Offline';
                },

                playNotif() {
                    this.notifSound.play().catch(e => console.log('Autoplay audio diblokir browser', e));
                },

                formatTime(datetimeString) {
                    if (!datetimeString) return '';
                    const date = new Date(datetimeString);
                    return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
                },

                getPreviewText(chat) {
                    if (chat.status === 'pending') return 'Ada chat baru, tunggu diklaim.';
                    if (chat.status === 'queued') return `Antrean #${chat.queue_position}`;
                    if (chat.status === 'active' && chat.admin_id !== this.adminId) {
                        return `🔒 Ditangani oleh: ${chat.admin ? chat.admin.username : 'Agen Lain'}`;
                    }
                    return 'Sedang aktif...';
                },

                selectChat(chat) {
                    this.selectedChat = chat;
                },

                handleStatusChange(e) {
                    const idx = this.chats.findIndex(c => c.id === e.conversation_id);
                    
                    if (idx !== -1) {
                        // Update status existing chat
                        this.chats[idx].status = e.status;
                        this.chats[idx].admin_id = e.admin_id;
                        this.chats[idx].queue_position = e.queue_position;
                        if (e.changed_by) {
                            this.chats[idx].admin = { username: e.changed_by };
                        }
                        
                        // Hapus chat hanya kalau benar-benar ditutup
                        if (e.status === 'closed') {
                             this.chats.splice(idx, 1);
                             if (this.selectedChat && this.selectedChat.id === e.conversation_id) {
                                 this.selectedChat = null;
                             }
                        }
                    } else {
                        // Obrolan Baru Masuk / Diover
                        if (['pending', 'queued', 'active'].includes(e.status)) {
                            // Set flag play notif untuk reload
                            if (e.status === 'pending' || e.status === 'queued') {
                                localStorage.setItem('play_chat_notif', 'true');
                            }
                            // Refresh halaman untuk memuat chat/user data baru
                            window.location.reload(); 
                        }
                    }
                },

                async updateStatus() {
                    try {
                        fetch('{{ route('admin.status.update') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ status: this.adminStatus })
                        });
                    } catch (e) {}
                },

                async claimChat(conversationId) {
                    this.isClaiming = true;
                    try {
                        const res = await fetch(`/admin/conversation/${conversationId}/claim`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });
                        const data = await res.json();
                        
                        if (!res.ok) throw new Error(data.error);
                        
                        // Update UI local
                        const chat = this.chats.find(c => c.id === conversationId);
                        if(chat) {
                            chat.status = 'active';
                            chat.admin_id = this.adminId;
                        }

                    } catch (error) {
                        alert(error.message || 'Gagal klaim chat (Mungkin keduluan admin lain).');
                        window.location.reload();
                    } finally {
                        this.isClaiming = false;
                    }
                },

                async closeChat() {
                    try {
                        this.isSubmitting = true;
                        await fetch(`/admin/conversation/${this.selectedChat.id}/close`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ problem_category: this.closeCategory })
                        });
                        this.showCloseModal = false;
                        window.location.reload();
                    } catch (error) {
                        alert('Gagal menutup chat.');
                        this.isSubmitting = false;
                    }
                },

                async handoverChat() {
                    try {
                        this.isSubmitting = true;
                        const res = await fetch(`/admin/conversation/${this.selectedChat.id}/handover`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({ to_admin_id: this.handoverToAdminId })
                        });
                        
                        const data = await res.json();
                        if (!res.ok) throw new Error(data.error || 'Server error');

                        alert('Chat berhasil diover!');
                        this.showHandoverModal = false;
                        
                        // Secara lokal obrolan sudah bukan punya kita 
                        window.location.reload();
                    } catch (error) {
                        alert('Gagal mengoper chat: ' + error.message);
                        this.isSubmitting = false;
                    }
                },

                async blockUser(conversationId) {
                    if (!confirm('Yakin ingin memblokir user ini selamanya?')) return;
                    
                    try {
                        await fetch(`/admin/conversation/${conversationId}/block`, {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            }
                        });
                        window.location.reload();
                    } catch (error) {
                        alert('Gagal block user.');
                    }
                }
            }));
        });
    </script>
</body>
</html>
