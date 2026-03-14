<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="shortcut icon" href="{{ asset('images/best-logo-1.png') }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
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
        .msg-row.from-self { align-self: flex-end; align-items: flex-end; }
        .msg-row.from-other { align-self: flex-start; align-items: flex-start; }

        /* ── Sender Label ── */
        .sender-label {
            font-size: 11px; font-weight: 600; color: #94a3b8;
            margin-bottom: 4px; letter-spacing: 0.02em;
        }
        .from-self .sender-label { color: #818cf8; }
        .from-other .sender-label { color: #64748b; }

        /* ── Bubble ── */
        .bubble {
            padding: 10px 14px; border-radius: 18px;
            font-size: 14px; line-height: 1.55;
            word-break: break-word; max-width: 100%; position: relative;
        }
        .bubble-self {
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

        /* ── Timestamp ── */
        .msg-time { font-size: 10px; color: #94a3b8; margin-top: 4px; padding: 0 4px; }

        /* ── Sticky Footer ── */
        .chat-footer {
            flex-shrink: 0; background: #ffffff;
            border-top: 1px solid #e8ecf3;
            box-shadow: 0 -2px 16px rgba(0,0,0,0.05);
        }

        /* ── Input Form ── */
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
        .send-btn:hover { box-shadow: 0 4px 14px rgba(99,102,241,0.4); transform: translateY(-1px); }
        .send-btn:disabled { opacity: 0.45; cursor: not-allowed; transform: none !important; box-shadow: none !important; }
    </style>
</head>
<body class="text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden"
      style="background:#f0f2f8; margin:0;"
      x-data="internalChat({{ $conversation->id }}, {{ $admin->id }}, {{ Js::from($messages) }}, {{ $otherUser->id }})">

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

                <!-- Normal Message -->
                <div class="msg-row" :class="msg.sender_id == adminId ? 'from-self' : 'from-other'">
                    <div class="sender-label" x-text="msg.sender_id == adminId ? 'Anda' : '{{ $otherUser->username }}'"></div>
                    
                    <div class="bubble" :class="msg.sender_id == adminId ? 'bubble-self' : 'bubble-other'">
                        <span x-html="formatMessage(msg.content)"></span>
                    </div>

                    <span class="msg-time"
                        x-text="timeAgo(msg.created_at)"
                        :title="new Date(msg.created_at).toLocaleString('id-ID', {day:'2-digit',month:'long',year:'numeric',hour:'2-digit',minute:'2-digit',timeZone:'Asia/Jakarta'})">
                    </span>
                </div>
            </div>
        </template>
        <div id="scroll-anchor" style="height:4px;"></div>
    </main>

    <!-- STICKY FOOTER -->
    <div class="chat-footer">
        <form class="input-form" @submit.prevent="sendMessage">
            <div class="input-row">
                <textarea x-model="newMessage" @keydown.enter.prevent="sendMessage"
                          placeholder="Ketik pesan internal..."
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
            Alpine.data('internalChat', (conversationId, adminId, initialMessages, otherUserId) => ({
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
                    let retry = 0;
                    const checkEcho = setInterval(() => {
                        if (window.Echo) {
                            clearInterval(checkEcho);
                            window.Echo.private(`internal-chat.${this.adminId}`)
                                .listen('.internal.message.sent', (e) => {
                                    if (e.conversation_id == this.conversationId && e.sender_id != this.adminId) {
                                        this.messages.push({
                                            id: e.id,
                                            sender_id: e.sender_id,
                                            content: e.content,
                                            created_at: e.created_at
                                        });
                                        this.scrollToBottom();
                                    }
                                });
                        } else if (retry++ > 20) clearInterval(checkEcho);
                    }, 500);
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isSending) return;

                    const content = this.newMessage;
                    this.newMessage = '';
                    this.isSending = true;

                    const tempId = Date.now();
                    this.messages.push({
                        temp_id: tempId,
                        sender_id: this.adminId,
                        content: content,
                        created_at: new Date().toISOString()
                    });
                    this.scrollToBottom();

                    try {
                        const response = await fetch('{{ route('admin.internal-chat.send') }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}'
                            },
                            body: JSON.stringify({
                                internal_conversation_id: this.conversationId,
                                content: content
                            })
                        });
                        const data = await response.json();
                        if (data.success) {
                            const index = this.messages.findIndex(m => m.temp_id === tempId);
                            this.messages[index].id = data.message.id;
                        }
                    } catch (error) {
                        console.error(error);
                    } finally {
                        this.isSending = false;
                    }
                },

                formatMessage(text) {
                    if (!text) return '';
                    return text.replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;').replace(/\n/g, '<br>');
                },

                shouldShowDateSeparator(date, index) {
                    if (index === 0) return true;
                    return new Date(date).toDateString() !== new Date(this.messages[index-1].created_at).toDateString();
                },

                formatDateSeparator(date) {
                    const d = new Date(date);
                    if (d.toDateString() === new Date().toDateString()) return 'Hari ini';
                    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'long', year: 'numeric' });
                },

                timeAgo(date) {
                    const d = new Date(date);
                    return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const el = document.getElementById('scroll-anchor');
                        if (el) el.scrollIntoView({ behavior: 'smooth' });
                    }, 100);
                }
            }));
        });
    </script>
</body>
</html>
