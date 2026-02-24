<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Live Chat - Bantuan</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
<body class="bg-slate-100 text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      x-data="chatApp({{ $conversation->id }}, {{ Auth::id() }}, '{{ $conversation->status }}', {{ Js::from($messages) }})">

    <!-- Header Navbar Minimalist -->
    <header class="bg-white border-b border-slate-200 px-4 py-3 flex items-center justify-between shrink-0 shadow-sm">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 flex items-center justify-center text-blue-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
            </div>
            <div>
                <h1 class="font-semibold text-slate-800 text-sm leading-tight">Layanan Pelanggan</h1>
                <!-- Indikator Status -->
                <div class="flex items-center gap-1.5 mt-0.5">
                    <span class="relative flex h-2 w-2">
                        <span class="animate-ping absolute inline-flex h-full w-full rounded-full opacity-75"
                              :class="{
                                  'bg-yellow-400': status === 'pending' || status === 'queued', 
                                  'bg-green-400': status === 'active',
                                  'bg-slate-400 hidden': status === 'closed'
                              }"></span>
                        <span class="relative inline-flex rounded-full h-2 w-2"
                              :class="{
                                  'bg-yellow-500': status === 'pending' || status === 'queued', 
                                  'bg-green-500': status === 'active',
                                  'bg-slate-400': status === 'closed'
                              }"></span>
                    </span>
                    <span class="text-xs text-slate-500 font-medium" x-text="statusText"></span>
                </div>
            </div>
        </div>
        
        <form method="POST" action="{{ route('user.logout') }}">
            @csrf
            <button type="submit" class="text-xs text-slate-500 hover:text-red-600 font-medium transition-colors px-3 py-1.5 rounded-md hover:bg-red-50">
                Akhiri Percakapan
            </button>
        </form>
    </header>

    <!-- Area Konten Utama -->
    <main class="flex-1 w-full max-w-3xl mx-auto flex flex-col bg-white border-x border-slate-200 shadow-sm relative overflow-hidden">
        
        <!-- Riwayat Pesan -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4">
            
            <!-- Pesan Pembuka Default -->
            <div class="flex justify-center mb-6">
                <span class="text-[11px] font-medium text-slate-400 bg-slate-50 px-3 py-1 rounded-full border border-slate-100">
                    Percakapan Dimulai
                </span>
            </div>

            <template x-for="msg in messages" :key="msg.id || msg.temp_id">
                <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                    
                    <!-- Pesan Sistem -->
                    <template x-if="msg.sender_type === 'system'">
                        <div class="w-full flex justify-center my-2">
                            <div class="bg-blue-50 text-blue-800 text-xs px-4 py-2 rounded-lg border border-blue-100 text-center max-w-[85%] shadow-sm">
                                <span class="block font-medium" x-text="msg.content"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Bubble Chat Normal -->
                    <template x-if="msg.sender_type !== 'system'">
                        <div class="max-w-[80%] flex flex-col" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                            <!-- Nama Pengirim (Opsional, hanya tampil untuk admin) -->
                            <span x-show="msg.sender_type !== 'user'" class="text-[11px] text-slate-400 font-medium mb-1 ml-1">Live Support</span>
                            
                            <div class="px-4 py-2.5 rounded-2xl text-[14px] leading-relaxed relative"
                                 :class="msg.sender_type === 'user' 
                                    ? 'bg-blue-600 text-white rounded-br-sm' 
                                    : 'bg-slate-100 text-slate-800 rounded-bl-sm border border-slate-200'">
                                <span x-text="msg.content"></span>
                            </div>
                            
                            <!-- Timestamp -->
                            <span class="text-[10px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>
                        </div>
                    </template>
                </div>
            </template>
            
            <!-- Elemen ini membantu scroll mentok bawah -->
            <div id="scroll-anchor" class="h-1"></div>
        </div>

        <!-- Sticky Status Footer (Typing Indicator & Closed Notice) -->
        <div class="shrink-0 bg-white">
            <!-- Typing Indicator Ringan -->
            <div x-show="isTyping" x-cloak class="px-5 py-2 flex items-center gap-2 bg-slate-50/50">
                <span class="text-xs italic text-slate-500 font-medium">Agen sedang merespon</span>
                <div class="flex gap-1">
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>

            <!-- Closed chat block -->
            <div x-show="status === 'closed'" x-cloak class="bg-slate-100 text-slate-600 text-xs text-center p-3 border-t border-slate-200 font-medium">
                Sesi pertanyaan ini telah ditutup oleh agen.
            </div>

            <!-- Form Input Bawah -->
            <form @submit.prevent="sendMessage" x-show="status !== 'closed'" class="border-t border-slate-200 p-3 bg-white flex items-end gap-2">
                <textarea x-model="newMessage" 
                          @input="sendTypingEvent"
                          @keydown.enter.prevent="if(!event.shiftKey) sendMessage()"
                          :disabled="isSending"
                          placeholder="Ketik balasan Anda..." 
                          class="flex-1 max-h-32 min-h-[44px] bg-slate-100 border-transparent focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl px-4 py-2.5 text-sm transition-colors resize-none overflow-y-auto"
                          rows="1"></textarea>
                          
                <button type="submit" 
                        :disabled="!newMessage.trim() || isSending"
                        class="shrink-0 w-11 h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all">
                    <svg class="w-5 h-5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
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
                typingTimeout: null,

                init() {
                    this.scrollToBottom();
                    this.listenForEvents();
                },

                get statusText() {
                    if (this.status === 'pending') return 'Mencari Agen Tersedia...';
                    if (this.status === 'queued') return 'Menunggu Antrean';
                    if (this.status === 'active') return 'Terhubung dengan Agen';
                    return 'Percakapan Selesai';
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
