<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/best-logo-1.png') }}">
    <script>
        window.broadcastingAuth = "{{ url('/broadcasting/auth') }}";
    </script>
    <title>Dashboard - Live Chat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        /* Sembunyikan elemen sebelum Alpine load penuh untuk mencegah loncatan layout */
        [x-cloak] { display: none !important; }
        /* Kustom scrollbar untuk gaya minimalist */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      x-data="chatApp({{ $conversation->id }}, {{ Auth::id() }}, '{{ $conversation->status }}', {{ Js::from($messages) }})">

    <!-- Header Navbar Minimalist -->
    <header class="bg-white border-b border-slate-200 px-3 md:px-4 py-2.5 md:py-3 flex items-center justify-between shrink-0 shadow-sm relative z-20">
        <!-- Red Accent Bar -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-red-600"></div>

        <div class="flex items-center gap-2 md:gap-4 overflow-hidden mt-1">
            <div class="w-9 h-9 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-sm md:text-xl shrink-0 shadow-lg shadow-slate-200">
                <span>{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            </div>
            <div class="overflow-hidden">
                <h1 class="font-black text-[#0a1d37] text-sm md:text-xl leading-tight truncate">Layanan Pelanggan</h1>
                <!-- Indikator Status -->
                <div class="flex items-center gap-1.5 text-[9px] md:text-xs font-bold text-slate-400 uppercase tracking-wider md:tracking-widest mt-0.5">
                    <span class="flex items-center gap-1 shrink-0"
                          :class="{
                              'text-red-600': status === 'pending' || status === 'queued',
                              'text-emerald-600': status === 'active',
                              'text-slate-400': status === 'closed'
                          }">
                        <div class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full"
                             :class="{
                                 'bg-red-600 animate-pulse': status === 'pending' || status === 'queued',
                                 'bg-emerald-600': status === 'active',
                                 'bg-slate-400': status === 'closed'
                             }"></div>
                        <span x-text="statusText"></span>
                    </span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="{{ route('user.logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-200 text-[10px] md:text-sm font-black px-3 md:px-8 py-2 md:py-3 rounded-xl md:rounded-2xl transition-all hover:scale-105 active:scale-95 whitespace-nowrap">
                <span class="hidden xs:inline">Akhiri Percakapan</span>
                <span class="xs:hidden">Akhiri</span>
            </button>
        </form>
    </header>

    <!-- Area Konten Utama -->
    <main class="flex-1 w-full max-w-3xl mx-auto flex flex-col bg-slate-50 border-x border-slate-200 shadow-sm relative overflow-hidden">
        
        <!-- Riwayat Pesan -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-3 md:p-4 space-y-4">
            
            <!-- Pesan Pembuka Default -->
            <div class="flex justify-center mb-6">
                <span class="text-slate-500 font-medium text-[10px] md:text-xs bg-white px-3 py-1 rounded-full border border-slate-100 shadow-sm">
                    Percakapan Dimulai
                </span>
            </div>

            <template x-for="msg in messages" :key="msg.id || msg.temp_id">
                <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                    
                    <!-- Pesan Sistem -->
                    <template x-if="msg.sender_type === 'system'">
                        <div class="w-full flex justify-center my-2 px-2">
                            <div class="bg-red-50 text-red-600 font-medium text-[10px] md:text-[11px] px-3 py-1.5 rounded-full border border-red-100 text-center shadow-sm max-w-[95%] md:max-w-[85%]">
                                <span x-html="formatMessage(msg.content)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Bubble Chat Normal -->
                    <template x-if="msg.sender_type !== 'system'">
                        <div class="max-w-[90%] md:max-w-[85%] flex flex-col" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                            <!-- Nama Pengirim -->
                            <span x-show="msg.sender_type !== 'user'" class="text-[9px] md:text-[11px] text-slate-400 font-medium mb-1 ml-1">Live Support</span>
                            <span x-show="msg.sender_type === 'user'" class="text-[9px] md:text-[11px] text-slate-400 font-medium mb-1 mr-1">Anda</span>
                            
                            <div class="px-3.5 py-2 md:px-5 md:py-3 rounded-2xl text-[13px] md:text-[15px] leading-relaxed relative break-words overflow-hidden shadow-sm"
                                 :class="msg.sender_type === 'admin' 
                                    ? 'bg-blue-600 text-white rounded-bl-sm border border-blue-700' 
                                    : 'bg-white text-slate-800 rounded-br-sm border border-slate-200'">
                                <span x-html="formatMessage(msg.content)"></span>
                            </div>
                            
                            <!-- Timestamp -->
                            <span class="text-[9px] md:text-[10px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>
                        </div>
                    </template>
                </div>
            </template>
            
            <!-- Elemen ini membantu scroll mentok bawah -->
            <div id="scroll-anchor" class="h-1"></div>
        </div>

        <!-- Sticky Status Footer (Typing Indicator & Closed Notice) -->
        <div class="shrink-0 bg-white border-t border-slate-100">
            <!-- Typing Indicator Ringan -->
            <div x-show="isTyping" x-cloak class="px-4 py-1.5 md:py-2 flex items-center gap-2 bg-slate-50/50">
                <span class="text-[10px] md:text-xs italic text-slate-500 font-medium" x-text="typingMessage"></span>
                <div class="flex gap-1">
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>

            <!-- Closed chat block -->
            <div x-show="status === 'closed'" x-cloak class="bg-slate-100 text-slate-600 py-3 md:py-4 text-center text-xs md:text-sm font-medium">
                Sesi obrolan ini telah ditutup.
            </div>

            <!-- Form Input Bawah -->
            <form @submit.prevent="sendMessage" x-show="status !== 'closed'" class="p-2 md:p-3 bg-white flex items-end gap-2">
                <textarea x-model="newMessage" 
                          @input="sendTypingEvent"
                          @keydown.enter.prevent="if(!event.shiftKey) sendMessage()"
                          :disabled="isSending"
                          placeholder="Ketik balasan Anda..." 
                          class="flex-1 max-h-32 min-h-[40px] md:min-h-[44px] bg-slate-100 border-transparent focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl px-3.5 py-2 md:py-2.5 text-[13px] md:text-sm transition-colors resize-none overflow-y-auto"
                          rows="1"></textarea>
                          
                <button type="submit" 
                        :disabled="!newMessage.trim() || isSending"
                        class="shrink-0 w-10 h-10 md:w-11 md:h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md shadow-blue-600/20">
                    <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </main>

    <!-- Logic Alpine JS Tetap Sama, Tidak Diubah -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatApp', (conversationId, userId, initialStatus, initialMessages) => ({
                conversationId: conversationId,
                userId: userId,
                status: initialStatus,
                messages: initialMessages,
                newMessage: '',
                isSending: false,
                isTyping: false,
                typingMessage: 'Agen sedang merespon',
                typingTimeout: null,

                init() {
                    this.scrollToBottom();
                    this.listenForEvents();
                },

                get statusText() {
                    if (this.status === 'pending') return 'Menunggu Agen';
                    if (this.status === 'queued') return 'Dalam Antrean';
                    if (this.status === 'active') return 'Terhubung dengan Agen';
                    return 'Sesi Ditutup';
                },

                formatMessage(text) {
                    if (!text) return '';
                    let safeText = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    return safeText.replace(/\n/g, '<br>');
                },

                listenForEvents() {
                    if (typeof window.Echo === 'undefined') return;

                    window.Echo.private(`conversation.${this.conversationId}`)
                        .listen('.message.sent', (e) => {
                            if (e.sender_id == this.userId && e.sender_type === 'user') return;
                            if (e.is_whisper) return;

                            this.messages.push({
                                id: e.id,
                                sender_type: e.sender_type,
                                content: e.content,
                                created_at: new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.scrollToBottom();
                        })
                        .listen('.conversation.status.changed', (e) => {
                            this.status = e.status;
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
                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'user',
                        content: content,
                        created_at: ''
                    });
                    this.scrollToBottom();

                    try {
                        const response = await fetch('{{ route('chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                conversation_id: this.conversationId,
                                content: content
                            })
                        });

                        const data = await response.json();
                        
                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1 && data.success) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].created_at = data.message.created_at;
                        }

                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                    } finally {
                        this.isSending = false;
                        this.sendTypingEvent(false);
                    }
                },

                sendTypingEvent(isTyping = true) {
                    if (this.status !== 'active') return;

                    fetch('{{ route('chat.typing') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
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
                        const container = document.getElementById('messages-container');
                        // Use scroll into view on the anchor for better consistency
                        const anchor = document.getElementById('scroll-anchor');
                        if (anchor) anchor.scrollIntoView({behavior: 'smooth', block: 'end'});
                        else if (container) container.scrollTop = container.scrollHeight;
                    }, 50);
                }
            }));
        });
    </script>
</body>
</html>
