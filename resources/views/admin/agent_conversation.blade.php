<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }

        /* Scrollbar */
        ::-webkit-scrollbar { width: 5px; height: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #d1d5db; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #9ca3af; }

        /* Date Separator */
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

        /* Message Row */
        .msg-row {
            display: flex; flex-direction: column;
            max-width: 72%; margin-bottom: 2px;
        }
        .msg-row.from-me { align-self: flex-end; align-items: flex-end; }
        .msg-row.from-other  { align-self: flex-start; align-items: flex-start; }

        /* Sender Label */
        .sender-label {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            margin-bottom: 4px; letter-spacing: 0.02em;
        }
        .from-me .sender-label { color: #818cf8; }
        .from-other .sender-label { color: #64748b; }

        /* Bubble */
        .bubble {
            padding: 10px 14px; border-radius: 18px;
            font-size: 14px; line-height: 1.55;
            word-break: break-word; max-width: 100%; position: relative;
        }
        .bubble-me {
            background: linear-gradient(135deg, #6366f1 0%, #4f46e5 100%);
            color: #fff;
            border-radius: 18px 18px 4px 18px;
            box-shadow: 0 2px 12px rgba(99,102,241,0.25);
        }
        .bubble-other {
            background: #ffffff; color: #1e293b;
            border-radius: 18px 18px 18px 4px;
            box-shadow: 0 1px 4px rgba(0,0,0,0.07);
            border: 1px solid #f1f5f9;
        }

        /* Timestamp */
        .msg-time { font-size: 10px; color: #94a3b8; margin-top: 4px; padding: 0 4px; }

        /* File Attachment */
        .file-attachment { display: flex; align-items: center; gap: 10px; min-width: 180px; }
        .file-icon {
            width: 38px; height: 38px; border-radius: 10px;
            display: flex; align-items: center; justify-content: center; flex-shrink: 0;
        }
        .bubble-me .file-icon { background: rgba(255,255,255,0.2); }
        .bubble-other .file-icon { background: #f1f5f9; }

        /* Footer */
        .chat-footer {
            flex-shrink: 0; background: #ffffff;
            border-top: 1px solid #e8ecf3;
            box-shadow: 0 -2px 16px rgba(0,0,0,0.05);
        }

        /* Input Form */
        .input-form { padding: 10px 14px 12px; }

        .input-row {
            display: flex; align-items: center; gap: 5px;
            background: #f8fafc; border: 1.5px solid #e2e8f0;
            border-radius: 16px; padding: 6px 6px 6px 10px;
            transition: border-color 0.2s, box-shadow 0.2s;
        }
        .input-row:focus-within {
            border-color: #6366f1;
            box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
            background: #fff;
        }

        /* Icon buttons */
        .input-icon-btn {
            width: 32px; height: 32px; border-radius: 10px; border: none;
            display: flex; align-items: center; justify-content: center;
            cursor: pointer; transition: all 0.15s; flex-shrink: 0;
            background: transparent; color: #94a3b8;
            padding: 0;
        }
        .input-icon-btn:hover { background: #f1f5f9; color: #475569; }

        .msg-textarea {
            flex: 1; border: none; background: transparent; resize: none;
            font-size: 14px; line-height: 1.5; color: #1e293b;
            padding: 4px 2px; min-height: 32px; max-height: 128px;
            overflow-y: auto; outline: none;
        }
        .msg-textarea::placeholder { color: #94a3b8; }

        .send-btn {
            flex-shrink: 0; height: 36px; padding: 0 16px;
            border-radius: 12px; border: none; font-size: 13px; font-weight: 600;
            cursor: pointer; display: flex; align-items: center; gap: 6px;
            transition: all 0.15s; white-space: nowrap;
            background: linear-gradient(135deg, #6366f1, #4f46e5);
            color: #fff; box-shadow: 0 2px 8px rgba(99,102,241,0.3);
        }
        .send-btn:hover:not(:disabled) { box-shadow: 0 4px 14px rgba(99,102,241,0.4); transform: translateY(-1px); }
        .send-btn:disabled { opacity: 0.45; cursor: not-allowed; }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden"
      style="background:#f0f2f8; margin:0;"
      x-data="agentConversation({{ $conversation->id }}, {{ $admin->id }}, {{ Js::from($messages) }})">

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

                <div class="msg-row" :class="msg.sender_id == adminId ? 'from-me' : 'from-other'">
                    <div class="sender-label" x-text="msg.sender_id == adminId ? 'Anda' : (msg.sender ? msg.sender.username : 'Agen')"></div>

                    <!-- Image -->
                    <template x-if="msg.message_type === 'image'">
                        <div class="bubble" :class="msg.sender_id == adminId ? 'bubble-me' : 'bubble-other'" style="padding:6px;">
                            <template x-if="!String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                <img :src="msg.content" style="border-radius:12px; max-width:100%; max-height:240px; display:block; cursor:zoom-in;" class="hover:opacity-90 transition-opacity" @click="openLightbox(msg.content)">
                            </template>
                            <template x-if="String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                <div style="padding:10px 12px; border-radius:12px; background:#fffbeb; color:#92400e; font-size:12px; line-height:1.5; border:1px solid #fcd34d;">
                                    Media gambar dari WhatsApp diterima, tetapi gateway belum mengirim URL file gambar ke panel web.
                                </div>
                            </template>
                        </div>
                    </template>

                    <!-- File -->
                    <template x-if="msg.message_type === 'file'">
                        <div class="bubble" :class="msg.sender_id == adminId ? 'bubble-me' : 'bubble-other'">
                            <template x-if="String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                <div style="padding:10px 12px; border-radius:12px; background:#fffbeb; color:#92400e; font-size:12px; line-height:1.5; border:1px solid #fcd34d;">
                                    Media file dari WhatsApp diterima, tetapi gateway belum mengirim URL file ke panel web.
                                </div>
                            </template>
                            <template x-if="!String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                            <div class="file-attachment">
                                <div class="file-icon">
                                    <svg width="20" height="20" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                                </div>
                                <div style="flex:1; min-width:0;">
                                    <p style="margin:0 0 4px; font-weight:600; font-size:13px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" x-text="msg.content.split('/').pop()"></p>
                                    <a :href="msg.content" target="_blank"
                                       style="font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:0.04em;"
                                       :style="msg.sender_id == adminId ? 'color:rgba(255,255,255,0.85)' : 'color:#6366f1'">
                                        Unduh File ↓
                                    </a>
                                </div>
                            </div>
                            </template>
                        </div>
                    </template>

                    <!-- Text -->
                    <template x-if="msg.message_type === 'text' || !msg.message_type">
                        <div class="bubble" :class="msg.sender_id == adminId ? 'bubble-me' : 'bubble-other'">
                            <span x-html="formatMessage(msg.content)"></span>
                        </div>
                    </template>

                    <span class="msg-time" x-text="formatTime(msg.created_at)"></span>
                </div>
            </div>
        </template>
        <div id="scroll-anchor" style="height:4px;"></div>
    </main>

    <!-- FOOTER -->
    <div class="chat-footer">
        <form class="input-form" @submit.prevent="sendMessage">
            <div class="input-row">
                <!-- File Upload -->
                <button type="button" @click="$refs.fileInput.click()" class="input-icon-btn" title="Lampirkan Gambar / File">
                    <svg width="16" height="16" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                </button>
                <input type="file" x-ref="fileInput" style="display:none;" @change="uploadFile">

                <textarea x-model="newMessage" x-ref="messageInput"
                          @input="resizeComposer()"
                          @keydown.enter="if(!event.shiftKey) { event.preventDefault(); sendMessage(); } else { $nextTick(() => resizeComposer()); }"
                          placeholder="Ketik pesan ke agen..."
                          class="msg-textarea" rows="1"></textarea>

                <button type="submit" :disabled="!newMessage.trim() || isSending" class="send-btn">
                    <svg width="14" height="14" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Kirim</span>
                </button>
            </div>
        </form>
    </div>

    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('agentConversation', (conversationId, adminId, initialMessages) => ({
                conversationId: conversationId,
                adminId: adminId,
                messages: initialMessages,
                newMessage: '',
                isSending: false,

                init() {
                    this.scrollToBottom();
                    this.listenForEvents();
                },

                listenForEvents() {
                    let echoCheckInterval = setInterval(() => {
                        if (window.Echo) {
                            window.Echo.private(`admin_conversation.${this.conversationId}`)
                                .listen('.admin_message.sent', (e) => {
                                    const msg = e.id ? e : e.message;
                                    if (msg.sender_id == this.adminId) return;
                                    this.messages.push(msg);
                                    this.scrollToBottom();
                                });
                            clearInterval(echoCheckInterval);
                        }
                    }, 500);
                },

                formatMessage(text) {
                    if (!text) return '';
                    return String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/\n/g, '<br>');
                },

                shouldShowDateSeparator(dateString, index) {
                    if (index === 0) return true;
                    const date = new Date(dateString).toDateString();
                    const prevDate = new Date(this.messages[index-1].created_at).toDateString();
                    return date !== prevDate;
                },

                formatDateSeparator(dateString) {
                    const date = new Date(dateString);
                    const now = new Date();
                    if (date.toDateString() === now.toDateString()) return 'Hari ini';
                    return date.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                },

                formatTime(dateString) {
                    return new Date(dateString).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isSending) return;

                    const content = this.newMessage;
                    this.newMessage = '';
                    this.$nextTick(() => this.resizeComposer());
                    this.isSending = true;

                    const tempId = Date.now();
                    this.messages.push({
                        temp_id: tempId,
                        sender_id: this.adminId,
                        message_type: 'text',
                        content: content,
                        created_at: new Date().toISOString()
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('content', content);
                        formData.append('message_type', 'text');

                        const response = await fetch('/admin/agent-chat/send', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });
                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Gagal mengirim pesan');
                        
                        const index = this.messages.findIndex(m => m.temp_id === tempId);
                        if (index !== -1) {
                            this.messages[index] = data.message;
                        }
                    } catch (e) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(e.message);
                    } finally {
                        this.isSending = false;
                    }
                },

                resizeComposer() {
                    if (!this.$refs.messageInput) return;
                    this.$refs.messageInput.style.height = 'auto';
                    this.$refs.messageInput.style.height = `${this.$refs.messageInput.scrollHeight}px`;
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
                        sender_id: this.adminId,
                        message_type: tempType,
                        content: previewUrl || file.name,
                        created_at: new Date().toISOString()
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('file', file);

                        const response = await fetch('/admin/agent-chat/send', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || data.message || 'Gagal mengunggah file');

                        const index = this.messages.findIndex(m => m.temp_id === tempId);
                        if (index !== -1) {
                            this.messages[index] = data.message;
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        e.target.value = ''; // Reset input
                    }
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const container = document.getElementById('messages-container');
                        container.scrollTop = container.scrollHeight;
                    }, 50);
                }
            }));
        });
    </script>

    @include('partials.image-lightbox')
</body>
</html>
