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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      x-data="adminChat({{ $conversation->id }}, {{ $admin->id }}, {{ Js::from($messages) }}, '{{ $conversation->status }}', {{ $conversation->admin_id }})">

    <!-- Messages List -->
    <main id="messages-container" class="flex-1 overflow-y-auto p-4 space-y-4">
        <template x-for="msg in messages" :key="msg.id || msg.temp_id">
            <div class="flex flex-col w-full" :class="msg.message_type === 'whisper' ? 'items-center' : (msg.sender_type === 'admin' ? 'items-end' : 'items-start')">
                
                <!-- System Message -->
                <template x-if="msg.sender_type === 'system'">
                    <div class="w-full flex justify-center my-2">
                        <div class="bg-red-50 text-red-600 font-medium text-[11px] px-3 py-1.5 rounded-full border border-red-100 text-center shadow-sm max-w-[85%]">
                            <span x-html="formatMessage(msg.content)"></span>
                        </div>
                    </div>
                </template>

                <!-- Normal Message OR Whisper -->
                <template x-if="msg.sender_type !== 'system'">
                    <div class="max-w-[85%] flex flex-col relative" :class="msg.message_type === 'whisper' ? 'items-center text-center' : (msg.sender_type === 'admin' ? 'items-end' : 'items-start')">
                        
                        <!-- Header text untuk whisper -->
                        <span x-show="msg.message_type === 'whisper'" class="text-[10px] font-bold text-amber-600 tracking-wider mb-1 flex items-center justify-center gap-1">
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                            INTERNAL NOTE
                        </span>

                        <!-- Label Sender -->
                        <span x-show="msg.sender_type === 'user'" class="text-[11px] text-slate-400 font-medium mb-1 ml-1 text-left">Pelanggan</span>
                        <span x-show="msg.sender_type === 'admin' && msg.message_type !== 'whisper'" class="text-[11px] text-slate-400 font-medium mb-1 mr-1 text-right">Anda</span>

                        <!-- Bubble Box -->
                        <div class="px-5 py-3 text-[15px] leading-relaxed relative max-w-[450px] mx-auto shadow-sm break-words overflow-hidden"
                             :class="{
                                 'bg-blue-600 text-white rounded-2xl rounded-br-sm border border-blue-700': msg.sender_type === 'admin' && msg.message_type !== 'whisper', 
                                 'bg-white text-slate-800 rounded-2xl rounded-bl-sm border border-slate-200 shadow-sm': msg.sender_type === 'user',
                                 'bg-amber-100 text-amber-950 border-dashed border-2 border-amber-300 rounded-2xl w-fit': msg.message_type === 'whisper'
                             }">
                            <span x-html="formatMessage(msg.content)"></span>
                        </div>
                        
                        <!-- Timestamp -->
                        <span class="text-[10px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>
                    </div>
                </template>
            </div>
        </template>
        <!-- Anchor for auto-scroll -->
        <div id="scroll-anchor" class="h-1"></div>
    </main>

    <!-- Sticky Footer (Status & Input) -->
    <div class="shrink-0 bg-white border-t border-slate-200">
        
        <!-- Typing Indicator -->
        <div x-show="isTyping" x-cloak class="px-5 py-2 flex items-center gap-2 bg-slate-50/50">
            <span class="text-xs italic text-slate-500 font-medium">Pengguna sedang mengetik</span>
            <div class="flex gap-1">
                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                <div class="w-1.5 h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
            </div>
        </div>

        <!-- Cannot Reply Notice (If Read Only or Closed) -->
        <div x-show="!canReply && status !== 'pending' && status !== 'queued'" x-cloak class="bg-slate-100 text-slate-600 py-3 text-center text-sm font-medium">
            <span x-show="status === 'closed'">Sesi obrolan ini telah ditutup.</span>
            <span x-show="status === 'active' && adminId !== sessionAdminId">Mode Membaca (Read-Only)</span>
        </div>

        <!-- Input Area (Form) -->
        <form class="p-3 bg-white transition-opacity" :class="(!canReply) ? 'opacity-50' : ''" @submit.prevent="sendMessage" x-show="status === 'pending' || status === 'queued' || canReply" x-cloak>
            
            <!-- Type Toggle & Quick Replies -->
            <div class="flex flex-col gap-2 mb-3">
                <!-- Quick Replies Pills -->
                <div class="flex items-center gap-2 overflow-x-auto pb-1" x-show="messageType === 'text'" x-transition 
                     style="scrollbar-width: thin; scrollbar-color: #cbd5e1 transparent;">
                    <span class="text-[10px] font-bold text-slate-400 uppercase tracking-widest mr-1 shrink-0">Balasan Cepat:</span>
                    <template x-for="(reply, index) in quickReplies" :key="index">
                        <button type="button" 
                                @click="insertQuickReply(reply)"
                                class="shrink-0 px-3 py-1.5 bg-slate-100 hover:bg-blue-50 text-slate-600 hover:text-blue-700 border border-slate-200 hover:border-blue-200 rounded-full text-[11px] font-semibold transition-all shadow-sm max-w-[200px] truncate text-left"
                                :title="reply">
                            <span x-text="reply"></span>
                        </button>
                    </template>
                </div>
            </div>
            
            <!-- Input Textarea & Submit Button -->
            <div class="flex items-end gap-2 relative">
                
                <!-- Slash Command Dropdown -->
                <div x-show="showDropdown && filteredQuickReplies.length > 0" 
                     x-ref="quickReplyDropdown"
                     x-transition.opacity.duration.200ms
                     @click.away="showDropdown = false"
                     class="absolute bottom-full left-12 mb-2 w-80 max-h-48 overflow-y-auto bg-white border border-slate-200 shadow-xl rounded-xl z-50 py-1"
                     style="display: none;">
                    <div class="px-3 py-1.5 text-xs font-bold text-slate-400 uppercase tracking-widest border-b border-slate-100 flex justify-between items-center">
                        <span>Pilih Balasan Cepat</span>
                        <span class="text-[10px] bg-slate-100 px-1.5 py-0.5 rounded text-slate-500">↑↓ + Enter</span>
                    </div>
                    <template x-for="(reply, index) in filteredQuickReplies" :key="index">
                        <button type="button" 
                                @click="insertQuickReply(reply); showDropdown = false;"
                                @mouseenter="selectedIndex = index"
                                class="w-full text-left px-4 py-2 text-[13px] transition-colors block"
                                :class="selectedIndex === index ? 'bg-blue-50 text-blue-700 font-medium' : 'text-slate-700 hover:bg-slate-50'">
                            <span x-text="reply"></span>
                        </button>
                    </template>
                </div>

                <!-- Internal Note Toggle Button -->
                <button type="button" 
                        @click="messageType = messageType === 'text' ? 'whisper' : 'text'"
                        class="absolute left-3 bottom-2.5 w-8 h-8 rounded-full flex items-center justify-center transition-colors z-10"
                        :class="messageType === 'whisper' ? 'bg-amber-100 text-amber-600 hover:bg-amber-200' : 'bg-slate-100 text-slate-400 hover:bg-slate-200 hover:text-slate-600'"
                        :title="messageType === 'whisper' ? 'Mode Catatan Internal Aktif' : 'Nyalakan Mode Catatan Internal'">
                    <svg class="w-4 h-4" x-show="messageType === 'text'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"></path></svg>
                    <svg class="w-4 h-4" x-show="messageType === 'whisper'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                </button>

                <textarea x-model="newMessage" x-ref="messageInput"
                          :placeholder="(!canReply) ? 'Menunggu obrolan diklaim...' : (messageType === 'whisper' ? 'Ketik catatan internal...' : 'Ketik balasan Anda ke pelanggan...')" 
                          @input="handleInput"
                          @keydown="handleKeydown"
                          :disabled="isSending || !canReply"
                          class="flex-1 pl-12 max-h-32 min-h-[44px] border-transparent focus:ring-2 rounded-xl px-4 py-2 text-[13px] transition-colors resize-none overflow-y-auto"
                          :class="messageType === 'whisper' ? 'bg-amber-50 focus:bg-white focus:border-amber-400 focus:ring-amber-200 text-amber-900 placeholder:text-amber-300' : 'bg-slate-100 focus:bg-white focus:border-blue-500 focus:ring-blue-200 text-slate-800 placeholder:text-slate-400'"
                          rows="1"></textarea>
                       
                <button type="submit" 
                        :disabled="!newMessage.trim() || isSending || !canReply"
                        class="shrink-0 font-semibold px-4 h-11 rounded-xl text-white flex items-center justify-center transition-all disabled:opacity-50 disabled:cursor-not-allowed text-sm"
                        :class="messageType === 'whisper' ? 'bg-amber-500 hover:bg-amber-600 shadow-md shadow-amber-500/20' : 'bg-blue-600 hover:bg-blue-700 shadow-md shadow-blue-600/20'">
                    Kirim
                </button>
            </div>
        </form>
    </div>

    <!-- Script Alpine.js Tidak Perlu Berubah Banyak -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminChat', (conversationId, adminId, initialMessages, initialStatus, sessionAdminId) => ({
                conversationId: conversationId,
                adminId: adminId, 
                sessionAdminId: sessionAdminId, 
                messages: initialMessages,
                status: initialStatus,
                
                newMessage: '',
                messageType: 'text', 
                quickReplies: {!! json_encode($quickReplies ?? []) !!},
                isSending: false,
                isTyping: false,
                typingTimeout: null,
                showDropdown: false,
                selectedIndex: 0,
                
                get filteredQuickReplies() {
                    if (!this.newMessage.startsWith('/')) return [];
                    const search = this.newMessage.slice(1).toLowerCase();
                    return this.quickReplies.filter(qr => qr.toLowerCase().includes(search));
                },

                handleInput(e) {
                    if (this.newMessage.startsWith('/')) {
                        this.showDropdown = true;
                        if (this.selectedIndex >= this.filteredQuickReplies.length) {
                            this.selectedIndex = 0;
                        }
                    } else {
                        this.showDropdown = false;
                    }
                    this.sendTypingEvent(true);
                },

                handleKeydown(e) {
                    if (this.showDropdown && this.filteredQuickReplies.length > 0) {
                        if (e.key === 'ArrowDown') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex + 1) % this.filteredQuickReplies.length;
                            this.scrollToSelected();
                        } else if (e.key === 'ArrowUp') {
                            e.preventDefault();
                            this.selectedIndex = (this.selectedIndex - 1 + this.filteredQuickReplies.length) % this.filteredQuickReplies.length;
                            this.scrollToSelected();
                        } else if (e.key === 'Enter' && !e.shiftKey) {
                            e.preventDefault();
                            this.insertQuickReply(this.filteredQuickReplies[this.selectedIndex]);
                            this.showDropdown = false;
                        } else if (e.key === 'Escape') {
                            this.showDropdown = false;
                        }
                    } else if (e.key === 'Enter' && !e.shiftKey) {
                        e.preventDefault();
                        this.sendMessage();
                    }
                },

                scrollToSelected() {
                    this.$nextTick(() => {
                        if (this.$refs.quickReplyDropdown) {
                            const buttons = this.$refs.quickReplyDropdown.querySelectorAll('button');
                            if (buttons[this.selectedIndex]) {
                                buttons[this.selectedIndex].scrollIntoView({ block: 'nearest' });
                            }
                        }
                    });
                },

                init() {
                    this.scrollToBottom();
                    this.listenForEvents();
                },

                get canReply() {
                    return this.status === 'active' && this.adminId == this.sessionAdminId;
                },

                formatMessage(text) {
                    let safeText = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    return safeText.replace(/\n/g, '<br>');
                },

                listenForEvents() {
                    if (!window.Echo) return;

                    window.Echo.private(`conversation.${this.conversationId}`)
                        .listen('.message.sent', (e) => {
                            const alreadyExists = this.messages.some(m => m.id === e.id);
                            if (alreadyExists) return;

                            if (e.sender_id == this.adminId && e.sender_type === 'admin') return;

                            this.messages.push({
                                id: e.id,
                                sender_type: e.sender_type,
                                message_type: e.message_type,
                                content: e.content,
                                created_at: new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.scrollToBottom();
                        })
                        .listen('.conversation.status.changed', (e) => {
                            this.status = e.status;
                            this.sessionAdminId = e.admin_id;
                        })
                        .listen('.typing', (e) => {
                            if (e.sender_type === 'user') {
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
                    const type = this.messageType;
                    this.newMessage = ''; 
                    this.isSending = true;

                    const tempId = Date.now();
                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'admin',
                        message_type: type,
                        content: content,
                        created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                    });
                    this.scrollToBottom();

                    try {
                        const response = await fetch('{{ route('admin.chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                conversation_id: this.conversationId,
                                message_type: type,
                                content: content
                            })
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);
                        
                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1) {
                            this.messages[msgIndex].id = data.message.id;
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        this.sendTypingEvent(false); 
                    }
                },

                async insertQuickReply(text) {
                    this.messageType = 'text';
                    this.newMessage = text;
                    await this.sendMessage();
                },

                sendTypingEvent(isTyping = true) {
                    if (!this.canReply || this.messageType === 'whisper') return;

                    fetch('{{ route('admin.chat.typing') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
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
