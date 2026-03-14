@extends('layouts.admin_template')

@section('title', 'Chat Internal Staff')

@push('styles')
<style>
    .chat-window {
        height: calc(100vh - 150px);
        margin: 0;
    }

    .chat-cont-left,
    .chat-cont-right {
        height: 100%;
        display: flex;
    }

    .contacts_body {
        height: 100%;
        overflow-y: auto;
    }

    [x-cloak] {
        display: none !important;
    }

    .sidebar-top-panel {
        background: #fff;
        border: 1px solid #eef0f5;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        padding: 16px;
    }
    body.dark-mode .sidebar-top-panel {
        background: #1e1e2d;
        border-color: #2a2a3d;
    }

    .chat-list-panel {
        background: #fff;
        border: 1px solid #eef0f5;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    body.dark-mode .chat-list-panel {
        background: #1e1e2d;
        border-color: #2a2a3d;
    }

    .chat-item {
        display: flex;
        align-items: center;
        padding: 11px 14px;
        gap: 12px;
        cursor: pointer;
        border-bottom: 1px solid #f3f4f6;
        text-decoration: none;
        transition: background 0.15s;
    }
    .chat-item:hover { background: #f9fafb; }
    .chat-item.is-selected { background: #eef2ff; }
    body.dark-mode .chat-item:hover { background: #25253a; }
    body.dark-mode .chat-item.is-selected { background: #1e1e4a; }
    
    .avatar-circle {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #fff;
        font-weight: bold;
        background: #6366f1;
    }

    .status-dot {
        width: 10px;
        height: 10px;
        border-radius: 50%;
        border: 2px solid #fff;
        position: absolute;
        bottom: 2px;
        right: 2px;
    }
    .status-online { background: #22c55e; }
    .status-offline { background: #9ca3af; }
    .status-busy { background: #ef4444; }

    @media (max-width: 991.98px) {
        .chat-cont-left.mobile-hide { display: none !important; }
        .chat-cont-right.mobile-hide { display: none !important; }
        .chat-cont-right.mobile-show {
            display: flex !important;
            position: fixed;
            top: 60px;
            left: 0;
            right: 0;
            bottom: 0;
            z-index: 1050;
            background: #fff;
        }
    }
</style>
@endpush

@section('content')
<div x-data="internalChatWorkspace({{ Js::from($conversations) }}, {{ Js::from($admins) }})">
    <div class="row chat-window">
        <!-- Sidebar Kiri -->
        <div class="chat-cont-left flex-column col-md-4 col-lg-4 col-xl-3" :class="selectedConversation ? 'mobile-hide' : ''">
            <div class="sidebar-top-panel mb-2">
                <h6 class="mb-0">Staff Chat</h6>
                <p class="text-muted small mb-0">Hubungi sesama agent atau admin</p>
            </div>

            <div class="chat-list-panel flex-grow-1 d-flex flex-column">
                <!-- Tab Menu -->
                <ul class="nav nav-tabs nav-justified border-bottom">
                    <li class="nav-item">
                        <a class="nav-link" :class="activeTab === 'recent' ? 'active' : ''" @click="activeTab = 'recent'" href="javascript:void(0)">Recent</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" :class="activeTab === 'staff' ? 'active' : ''" @click="activeTab = 'staff'" href="javascript:void(0)">Staff</a>
                    </li>
                </ul>

                <div class="contacts_body">
                    <!-- Recent Conversations -->
                    <template x-if="activeTab === 'recent'">
                        <div>
                            <template x-for="conv in conversations" :key="conv.id">
                                <a href="javascript:void(0)" class="chat-item" 
                                   :class="selectedConversation && selectedConversation.id === conv.id ? 'is-selected' : ''"
                                   @click="selectConversation(conv)">
                                    <div class="position-relative">
                                        <div class="avatar-circle" x-text="getOtherUser(conv).username.charAt(0).toUpperCase()"></div>
                                        <div class="status-dot" :class="'status-' + getOtherUser(conv).status"></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="d-flex justify-content-between">
                                            <span class="fw-bold" x-text="getOtherUser(conv).username"></span>
                                            <span class="text-muted small" x-text="formatTime(conv.last_message_at)"></span>
                                        </div>
                                        <div class="text-muted small text-truncate" style="max-width: 150px;" x-text="conv.messages.length ? conv.messages[0].content : 'No messages yet'"></div>
                                    </div>
                                </a>
                            </template>
                            <div x-show="conversations.length === 0" class="p-4 text-center text-muted">
                                Belum ada obrolan.
                            </div>
                        </div>
                    </template>

                    <!-- Staff List -->
                    <template x-if="activeTab === 'staff'">
                        <div>
                            <template x-for="staff in admins" :key="staff.id">
                                <a href="javascript:void(0)" class="chat-item" @click="startChat(staff.id)">
                                    <div class="position-relative">
                                        <div class="avatar-circle" x-text="staff.username.charAt(0).toUpperCase()"></div>
                                        <div class="status-dot" :class="'status-' + staff.status"></div>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-bold" x-text="staff.username"></div>
                                        <div class="text-muted small" x-text="staff.role || 'Staff'"></div>
                                    </div>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </div>
        </div>

        <!-- Chat Window Kanan -->
        <div class="chat-cont-right flex-grow-1 col-md-8 col-lg-8 col-xl-9" :class="selectedConversation ? 'mobile-show' : 'mobile-hide'">
            <div class="card mb-0 w-100 h-100">
                <template x-if="selectedConversation">
                    <div class="d-flex flex-column h-100">
                        <div class="card-header msg_head px-3 py-2">
                            <div class="d-flex align-items-center">
                                <a href="javascript:void(0)" class="me-3 d-lg-none text-dark" @click="selectedConversation = null">
                                    <i class="fas fa-arrow-left"></i>
                                </a>
                                <div class="avatar-circle avatar-sm me-2" x-text="getOtherUser(selectedConversation).username.charAt(0).toUpperCase()"></div>
                                <div>
                                    <h6 class="mb-0" x-text="getOtherUser(selectedConversation).username"></h6>
                                    <small :class="getOtherUser(selectedConversation).status === 'online' ? 'text-success' : 'text-muted'" x-text="getOtherUser(selectedConversation).status"></small>
                                </div>
                            </div>
                        </div>
                        <div class="card-body p-0 flex-grow-1">
                            <iframe :src="'/admin/internal-chat/conversation/' + selectedConversation.id" class="w-100 h-100" style="border:none;"></iframe>
                        </div>
                    </div>
                </template>
                <template x-if="!selectedConversation">
                    <div class="h-100 d-flex flex-column align-items-center justify-content-center text-muted">
                        <i class="fe fe-send display-4 mb-3"></i>
                        <h5>Pilih staff untuk mulai mengobrol</h5>
                    </div>
                </template>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('internalChatWorkspace', (initialConversations, staffList) => ({
            conversations: initialConversations,
            admins: staffList,
            selectedConversation: null,
            activeTab: 'recent',
            currentAdminId: {{ auth('admin')->id() }},

            init() {
                this.listenForConversations();
            },

            listenForConversations() {
                let retry = 0;
                const checkEcho = setInterval(() => {
                    if (window.Echo) {
                        clearInterval(checkEcho);
                        window.Echo.private(`internal-chat.${this.currentAdminId}`)
                            .listen('.internal.message.sent', (e) => {
                                // Find conversation in list
                                const convIndex = this.conversations.findIndex(c => c.id == e.conversation_id);
                                if (convIndex !== -1) {
                                    // Update snippet and last_message_at
                                    this.conversations[convIndex].last_message_at = e.created_at;
                                    this.conversations[convIndex].messages = [{ content: e.content }];
                                    
                                    // Move to top
                                    const conv = this.conversations.splice(convIndex, 1)[0];
                                    this.conversations.unshift(conv);
                                } else {
                                    // Refresh list to get new conversation
                                    this.fetchConversations();
                                }
                            });
                    } else if (retry++ > 20) clearInterval(checkEcho);
                }, 500);
            },

            async fetchConversations() {
                // Simplified refresh - could be better with an API endpoint
                window.location.reload();
            },

            getOtherUser(conv) {
                return conv.user_one_id == this.currentAdminId ? conv.user_two : conv.user_one;
            },

            selectConversation(conv) {
                this.selectedConversation = conv;
            },

            async startChat(adminId) {
                try {
                    const res = await fetch('{{ route('admin.internal-chat.start') }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ admin_id: adminId })
                    });
                    const data = await res.json();
                    if (data.conversation_id) {
                        // Refresh conversations and select the new one
                        window.location.reload();
                    }
                } catch (error) {
                    console.error(error);
                }
            },

            formatTime(date) {
                if (!date) return '';
                const d = new Date(date);
                return d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
        }));
    });
</script>
@endpush
