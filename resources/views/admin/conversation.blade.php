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

        /* ── Scrollbar ── */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* ── Date Separator ── */
        .date-separator {
            display: flex;
            align-items: center;
            gap: 10px;
            margin: 16px 0 10px;
        }
        .date-separator::before, .date-separator::after {
            content: ''; flex: 1; height: 1px; background: #e2e8f0;
        }
        .date-separator span {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            white-space: nowrap; letter-spacing: 0.04em; text-transform: uppercase;
        }

        /* ── Message Row ── */
        .msg-row {
            display: flex; flex-direction: column;
            max-width: 72%; margin-bottom: 2px;
        }
        .msg-row.from-admin { align-self: flex-end; align-items: flex-end; }
        .msg-row.from-user  { align-self: flex-start; align-items: flex-start; }
        .msg-row.from-system { align-self: center; align-items: center; max-width: 90%; }
        .msg-row.from-whisper { align-self: center; align-items: center; max-width: 80%; }

        /* ── Sender Label ── */
        .sender-label {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            margin-bottom: 4px; letter-spacing: 0.02em;
        }
        .from-admin .sender-label { color: #818cf8; }
        .from-user  .sender-label { color: #64748b; }

        /* ── Bubble ── */
        .bubble {
            padding: 10px 14px; border-radius: 18px;
            font-size: 14px; line-height: 1.55;
            word-break: break-word; max-width: 100%; position: relative;
        }
        .bubble-admin {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            box-shadow: 0 2px 12px rgba(99,102,241,0.25);
        }
        .bubble-user {
            background: #ffffff; color: #1e293b;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            border: 1px solid #f1f5f9;
        }
        .bubble-whisper {
            background: #fffbeb; color: #78350f;
            border: 1.5px dashed #fcd34d; border-radius: 14px; font-size: 13px;
        }
        .bubble-system {
            background: #fffbeb; color: #92400e;
            border: 1px solid #fde68a; border-radius: 12px;
            font-size: 11px; font-weight: 600; padding: 10px 20px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.04);
            max-width: 85%; line-height: 1.5;
            text-align: center;
        }
        .msg-row.from-system {
            justify-content: center;
            padding: 15px 0;
            width: 100%;
        }

        /* ── Timestamp ── */
        .msg-time { font-size: 10px; color: #94a3b8; margin-top: 4px; padding: 0 4px; }

        /* ── Whisper Header ── */
        .whisper-header {
            display: flex; align-items: center; gap: 4px;
            font-size: 10px; font-weight: 700; color: #d97706;
            text-transform: uppercase; letter-spacing: 0.06em; margin-bottom: 6px;
        }

        /* ── File Attachment ── */
        .file-attachment { display: flex; align-items: center; gap: 10px; min-width: 180px; }
        .file-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .bubble-admin .file-icon { background: rgba(255,255,255,0.2); }
        .bubble-user  .file-icon { background: #f1f5f9; }

        /* ── Sticky Footer ── */
        .chat-footer {
            flex-shrink: 0; background: #ffffff;
            border-top: 1px solid #e8ecf3;
            box-shadow: 0 -2px 16px rgba(0,0,0,0.05);
        }

        /* ── Typing Indicator ── */
        .typing-bar {
            padding: 6px 16px; display: flex; align-items: center; gap: 8px;
            background: #f8fafc; border-bottom: 1px solid #f1f5f9;
        }
        .typing-bar span { font-size: 12px; color: #64748b; font-style: italic; font-weight: 500; }
        .typing-dots { display: flex; gap: 3px; align-items: center; }
        .typing-dot {
            width: 5px; height: 5px; border-radius: 50%; background: #94a3b8;
            animation: bounce-dot 1.2s infinite ease-in-out;
        }
        .typing-dot:nth-child(2) { animation-delay: 0.2s; }
        .typing-dot:nth-child(3) { animation-delay: 0.4s; }
        @keyframes bounce-dot {
            0%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-5px); }
        }

        /* ── Cannot Reply Notice ── */
        .no-reply-bar {
            background: #f8fafc; color: #64748b;
            padding: 10px 16px; text-align: center; font-size: 13px; font-weight: 500;
        }

        /* ── Input Form ── */
        .input-form { padding: 10px 14px 12px; }

        /* Quick Replies */
        .quick-replies-bar {
            display: flex; align-items: center; gap: 8px;
            margin-bottom: 10px; overflow-x: auto; padding-bottom: 2px;
        }
        .quick-replies-bar::-webkit-scrollbar { height: 3px; }
        .qr-label {
            font-size: 10px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.07em;
            white-space: nowrap; flex-shrink: 0;
        }
        .qr-chip {
            flex-shrink: 0; padding: 5px 12px;
            background: #f1f5f9; border: 1px solid #e2e8f0;
            border-radius: 20px; font-size: 12px; font-weight: 500;
            color: #475569; cursor: pointer; transition: all 0.15s;
            white-space: nowrap; max-width: 200px;
            overflow: hidden; text-overflow: ellipsis;
        }
        .qr-chip:hover { background: #eef2ff; border-color: #a5b4fc; color: #4338ca; }

        /* Input row */
        .input-row {
            display: flex; align-items: center; gap: 5px; /* Diubah dari flex-end ke center */
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 16px; padding: 6px 6px 6px 10px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-row:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
            background: #fff;
        }
        .input-row.whisper-mode { border-color: #fcd34d; background: #fffdf0; }
        .input-row.whisper-mode:focus-within { border-color: #f59e0b; box-shadow: 0 0 0 3px rgba(245,158,11,0.12); }

        /* Icon buttons */
        .input-icon-btn {
            width: 32px; height: 32px; border-radius: 10px; border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s; flex-shrink: 0;
            background: transparent; color: #94a3b8;
            padding: 0;
        }
        .input-icon-btn:hover { background: #f1f5f9; color: #475569; }
        .input-icon-btn.active-whisper { background: #fef3c7; color: #d97706; }
        .input-icon-btn.active-whisper:hover { background: #fde68a; }

        /* Textarea */
        .msg-textarea {
            flex: 1; border: none; background: transparent; resize: none;
            font-size: 14px; line-height: 1.5; color: #1e293b;
            padding: 4px 2px; min-height: 32px; max-height: 128px;
            overflow-y: auto; outline: none;
        }
        .msg-textarea::placeholder { color: #94a3b8; }
        .msg-textarea.whisper-mode { color: #78350f; }
        .msg-textarea.whisper-mode::placeholder { color: #d97706; opacity: 0.6; }

        /* Send button */
        .send-btn {
            flex-shrink: 0; height: 36px; padding: 0 16px;
            border-radius: 12px; border: none; font-size: 13px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            transition: all 0.15s; white-space: nowrap;
        }
        .send-btn.normal {
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }
        .send-btn.normal:hover { box-shadow: 0 4px 14px rgba(99,102,241,0.4); transform: translateY(-1px); }
        .send-btn.whisper {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            color: #fff; box-shadow: 0 2px 8px rgba(245,158,11,0.3);
        }
        .send-btn.whisper:hover { box-shadow: 0 4px 14px rgba(245,158,11,0.4); transform: translateY(-1px); }
        .send-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; box-shadow: none !important; }

        /* Slash dropdown */
        .slash-dropdown {
            position: absolute; bottom: calc(100% + 8px); left: 0; right: 0;
            background: #fff; border: 1px solid #e2e8f0;
            border-radius: 14px; box-shadow: 0 8px 32px rgba(0,0,0,0.12);
            z-index: 50; overflow: hidden; max-height: 200px; overflow-y: auto;
        }
        .slash-dropdown-header {
            padding: 8px 14px; font-size: 10px; font-weight: 700; color: #94a3b8;
            text-transform: uppercase; letter-spacing: 0.06em;
            border-bottom: 1px solid #f1f5f9;
            display: flex; justify-content: space-between; align-items: center;
        }
        .slash-dropdown-item {
            width: 100%; text-align: left; padding: 9px 14px; font-size: 13px;
            border: none; background: transparent; cursor: pointer; display: block;
            color: #374151; transition: background 0.1s;
        }
        .slash-dropdown-item:hover, .slash-dropdown-item.selected {
            background: #eef2ff; color: #4338ca; font-weight: 500;
        }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden"
      style="background:#f0f2f8; margin:0;"
      x-data="adminChat({{ $conversation->id }}, {{ $admin->id }}, {{ Js::from($messages) }}, '{{ $conversation->status }}', {{ $conversation->admin_id }})">

    <!-- MESSAGES AREA -->
    <main id="messages-container" style="flex:1; overflow-y:auto; padding:20px 16px; display:flex; flex-direction:column; gap:2px;">
        <template x-for="(msg, index) in messages" :key="msg.id || msg.temp_id">
            <div style="display:flex; flex-direction:column; width:100%;">
                <!-- Date Separator -->
                <template x-if="shouldShowDateSeparator(msg.created_at, index)">
                    <div class="date-separator">
                        <span x-text="formatDateSeparator(msg.created_at)"></span>
                    </div>
                </template>

                <!-- System Message -->
                <template x-if="msg.sender_type === 'system'">
                    <div class="msg-row from-system">
                        <div class="bubble bubble-system">
                            <span x-html="formatMessage(msg.content)"></span>
                        </div>
                    </div>
                </template>

                <!-- Whisper / Internal Note -->
                <template x-if="msg.sender_type !== 'system' && msg.message_type === 'whisper'">
                    <div class="msg-row from-whisper">
                        <div class="whisper-header">
                            <svg width="12" height="12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            Catatan Internal
                        </div>
                        <div class="bubble bubble-whisper"><span x-html="formatMessage(msg.content)"></span></div>
                        <span class="msg-time" x-text="timeAgo(msg.created_at)"></span>
                    </div>
                </template>

                <!-- Normal Message -->
                <template x-if="msg.sender_type !== 'system' && msg.message_type !== 'whisper'">
                    <div class="msg-row" :class="msg.sender_type === 'admin' ? 'from-admin' : 'from-user'">

                        <div class="sender-label" x-text="msg.sender_type === 'user' ? 'Pelanggan' : (msg.sender_id == 0 ? 'Bot Assistant' : 'Anda')"></div>

                        <!-- Image -->
                        <template x-if="msg.message_type === 'image'">
                            <div class="bubble" :class="msg.sender_type === 'admin' ? 'bubble-admin' : 'bubble-user'" style="padding:6px;">
                                <img :src="msg.content" style="border-radius:12px; max-width:100%; max-height:240px; display:block; cursor:pointer;" class="hover:opacity-90 transition-opacity" @click="window.open(msg.content, '_blank')">
                            </div>
                        </template>

                        <!-- File -->
                        <template x-if="msg.message_type === 'file'">
                            <div class="bubble" :class="msg.sender_type === 'admin' ? 'bubble-admin' : 'bubble-user'">
                                <div class="file-attachment">
                                    <div class="file-icon">
                                        <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                    </div>
                                    <div style="flex:1; min-width:0;">
                                        <p style="margin:0 0 4px; font-weight:600; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="msg.content.split('/').pop()"></p>
                                        <a :href="msg.content" target="_blank"
                                           style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;"
                                           :style="msg.sender_type === 'admin' ? 'color:rgba(255,255,255,0.85)' : 'color:#6366f1'">
                                            Unduh File ↓
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </template>

                        <!-- Text -->
                        <template x-if="msg.message_type === 'text'">
                            <div class="bubble" :class="msg.sender_type === 'admin' ? 'bubble-admin' : 'bubble-user'">
                                <span x-html="formatMessage(msg.content)"></span>
                            </div>
                        </template>

                        <span class="msg-time"
                            x-text="timeAgo(msg.created_at)"
                            :title="new Date(msg.created_at).toLocaleString('id-ID', {day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit',timeZone:'Asia/Jakarta'})">
                        </span>
                    </div>
                </template>
            </div>
        </template>
        <div id="scroll-anchor" style="height:4px;"></div>
    </main>

    <!-- STICKY FOOTER -->
    <div class="chat-footer">

        <!-- Typing Indicator -->
        <div class="typing-bar" x-show="isTyping" x-cloak>
            <div class="typing-dots">
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
                <div class="typing-dot"></div>
            </div>
            <span>Pelanggan sedang mengetik...</span>
        </div>

        <!-- Cannot Reply Notice -->
        <div class="no-reply-bar" x-show="!canReply && status !== 'pending' && status !== 'queued' && status !== 'closed'" x-cloak>
            <span x-show="status === 'active' && adminId !== sessionAdminId">👁 Mode Membaca (Read-Only)</span>
        </div>

        <!-- Input Form -->
        <form class="input-form"
              method="POST" action="{{ route('admin.chat.send') }}"
              :class="(!canReply) ? 'opacity-50 pointer-events-none' : ''"
              @submit.prevent="sendMessage"
              x-show="status === 'pending' || status === 'queued' || canReply" x-cloak>

            <!-- Quick Replies -->
            <div class="quick-replies-bar" x-show="messageType === 'text'" x-transition>
                <span class="qr-label">Balasan:</span>
                <template x-for="(reply, index) in quickReplies" :key="index">
                    <button type="button" class="qr-chip" @click="insertQuickReply(reply)" :title="reply">
                        <span x-text="reply"></span>
                    </button>
                </template>
            </div>

            <!-- Input Row -->
            <div class="input-row" :class="messageType === 'whisper' ? 'whisper-mode' : ''" style="position:relative;">

                <!-- Slash dropdown -->
                <div x-show="showDropdown && filteredQuickReplies.length > 0"
                     x-ref="quickReplyDropdown"
                     x-transition.opacity.duration.150ms
                     @click.away="showDropdown = false"
                     class="slash-dropdown">
                    <div class="slash-dropdown-header">
                        <span>Balasan Cepat</span>
                        <span style="background:#f1f5f9; padding:2px 6px; border-radius:4px; font-size:10px;">↑↓ Enter</span>
                    </div>
                    <template x-for="(reply, index) in filteredQuickReplies" :key="index">
                        <button type="button" class="slash-dropdown-item"
                                :class="selectedIndex === index ? 'selected' : ''"
                                @click="insertQuickReply(reply); showDropdown = false;"
                                @mouseenter="selectedIndex = index">
                            <span x-text="reply"></span>
                        </button>
                    </template>
                </div>

                <!-- Internal Note Toggle -->
                <button type="button" @click="messageType = messageType === 'text' ? 'whisper' : 'text'"
                        class="input-icon-btn" :class="messageType === 'whisper' ? 'active-whisper' : ''"
                        :title="messageType === 'whisper' ? 'Catatan Internal Aktif — klik untuk matikan' : 'Catatan Internal'">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="messageType === 'text'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.828 10.172a4 4 0 00-5.656 0l-4 4a4 4 0 105.656 5.656l1.102-1.101m-.758-4.899a4 4 0 005.656 0l4-4a4 4 0 00-5.656-5.656l-1.1 1.1"/></svg>
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24" x-show="messageType === 'whisper'"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                </button>

                <!-- File Upload -->
                <button type="button" @click="$refs.fileInput.click()" class="input-icon-btn" title="Lampirkan Gambar / File">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </button>
                <input type="file" x-ref="fileInput" style="display:none;" @change="uploadFile">

                <!-- Textarea -->
                <textarea x-model="newMessage" x-ref="messageInput"
                          :placeholder="(!canReply) ? 'Menunggu chat diklaim...' : (messageType === 'whisper' ? '🔒 Tulis catatan internal...' : 'Ketik pesan ke pelanggan...')"
                          @input="handleInput" @keydown="handleKeydown"
                          :disabled="!canReply"
                          class="msg-textarea" :class="messageType === 'whisper' ? 'whisper-mode' : ''"
                          rows="1"></textarea>

                <!-- Send Button -->
                <button type="submit"
                        :disabled="!newMessage.trim() || isSending || !canReply"
                        class="send-btn" :class="messageType === 'whisper' ? 'whisper' : 'normal'">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span x-text="messageType === 'whisper' ? 'Catat' : 'Kirim'"></span>
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
                prevDate: null, // To track date for separators
                
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
                    // Update relative timestamps every minute
                    setInterval(() => {
                        this.$nextTick(() => { 
                            // Re-evaluating x-text="timeAgo(...)" will automatically update timestamps
                        });
                    }, 60 * 1000); // Every minute
                },

                get canReply() {
                    if (this.status === 'closed') return true;
                    return this.status === 'active' && this.adminId == this.sessionAdminId;
                },

                formatMessage(text) {
                    if (!text) return '';
                    
                    const badge = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>';
                    
                    if (String(text).includes(badge)) {
                        let parts = String(text).split(badge);
                        let safeParts = parts.map(p => String(p).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
                        return safeParts.join(badge).replace(/\n/g, '<br>');
                    }

                    let safeText = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    return safeText.replace(/\n/g, '<br>');
                },

                // New function for date separators
                shouldShowDateSeparator(messageDateString, index) {
                    if (index === 0) {
                        this.prevDate = null; // Reset for first message
                        return true;
                    }
                    const messageDate = new Date(messageDateString);
                    const prevMessageDate = new Date(this.messages[index - 1].created_at);
                    
                    // Compare dates (ignoring time)
                    const showSeparator = messageDate.toDateString() !== prevMessageDate.toDateString();
                    return showSeparator;
                },

                formatDateSeparator(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    const options = { day: '2-digit', month: 'long', year: 'numeric', timeZone: 'Asia/Jakarta' };

                    if (date.toDateString() === now.toDateString()) {
                        return 'Hari ini';
                    }
                    const yesterday = new Date(now);
                    yesterday.setDate(now.getDate() - 1);
                    if (date.toDateString() === yesterday.toDateString()) {
                        return 'Kemarin';
                    }
                    return date.toLocaleString('id-ID', options);
                },


                timeAgo(datetimeString) {
                    if (!datetimeString) return '';
                    const date = new Date(datetimeString);
                    const now = new Date();
                    const seconds = Math.floor((now - date) / 1000);
                    const minutes = Math.floor(seconds / 60);
                    const hours = Math.floor(minutes / 60);
                    const days = Math.floor(hours / 24);

                    const optionsTime = { hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' };
                    const optionsDateTime = { day: '2-digit', month: 'long', year: 'numeric', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta' };

                    if (seconds < 60) {
                        return 'Baru saja';
                    } else if (minutes < 60) {
                        return `${minutes} menit lalu`;
                    } else if (hours < 24) {
                        return `${hours} jam lalu`;
                    } else if (days < 2) {
                        return `Kemarin, ${date.toLocaleString('id-ID', optionsTime)}`;
                    } else if (days < 7) {
                        return `${days} hari lalu`;
                    } else if (now.getFullYear() === date.getFullYear()) {
                        return date.toLocaleString('id-ID', {day: '2-digit', month: 'long', hour: '2-digit', minute: '2-digit', timeZone: 'Asia/Jakarta'});
                    }
                    return date.toLocaleString('id-ID', optionsDateTime);
                },

                listenForEvents() {
                    if (!this.conversationId) return;

                    let retries = 0;
                    const maxRetries = 20;

                    const checkEcho = setInterval(() => {
                        if (typeof window.Echo !== 'undefined') {
                            clearInterval(checkEcho);

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
                                        created_at: e.created_at // Store raw ISO string
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
                        } else {
                            retries++;
                            if (retries >= maxRetries) {
                                clearInterval(checkEcho);
                                console.warn('Echo initialization timed out.');
                            }
                        }
                    }, 500);
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
                        created_at: new Date().toISOString()
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('message_type', type);
                        formData.append('content', content);

                        const response = await fetch('{{ route('admin.chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);
                        
                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].message_type = data.message.message_type;
                            this.messages[msgIndex].content = data.message.content;
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        this.sendTypingEvent(false); 
                        this.$nextTick(() => {
                            if (this.$refs && this.$refs.messageInput) {
                                this.$refs.messageInput.focus();
                            }
                        });
                    }
                },

                async uploadFile(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.isSending = true;
                    const tempId = Date.now();
                    
                    // Preview (if image)
                    let previewUrl = '';
                    let tempType = 'file';
                    if (file.type.startsWith('image/')) {
                        previewUrl = URL.createObjectURL(file);
                        tempType = 'image';
                    }

                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'admin',
                        message_type: tempType,
                        content: previewUrl || file.name,
                        created_at: new Date().toISOString()
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('message_type', 'file'); // Will be corrected by server
                        formData.append('file', file);

                        const response = await fetch('{{ route('admin.chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);

                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].message_type = data.message.message_type;
                            this.messages[msgIndex].content = data.message.content;
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        e.target.value = ''; // Reset input
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
