@extends('layouts.admin_template')

@section('title', 'Agent Chat')

@push('styles')
<style>
    .chat-window {
        height: calc(100vh - 150px);
        margin: 0;
    }

    .chat-cont-left,
    .chat-cont-right,
    .chat-cont-profile {
        height: 100%;
        display: flex;
    }

    .msg_card_body,
    .contacts_body {
        height: 100%;
        overflow-y: auto;
    }

    iframe {
        width: 100%;
        height: 100%;
        border: none;
    }

    [x-cloak] {
        display: none !important;
    }

    /* Mobile Responsive Logic */
    @media (max-width: 991.98px) {

        .chat-cont-left,
        .chat-cont-right {
            display: none !important;
        }

        .chat-window {
            height: calc(100vh - 100px);
            /* Adjust to give room */
            position: relative;
            margin: 0;
            padding: 0;
        }

        .chat-cont-left:not(.mobile-hide) {
            display: flex !important;
            width: 100%;
            height: 100%;
        }

        .chat-cont-right.mobile-show {
            display: flex !important;
            position: fixed !important;
            top: 60px !important;
            left: 0 !important;
            right: 0 !important;
            bottom: 0 !important;
            width: 100% !important;
            height: calc(100vh - 60px) !important;
            z-index: 1050 !important;
            /* Di bawah sidebar overlay (10900) */
            background: #ffffff !important;
            opacity: 1 !important;
            visibility: visible !important;
            margin: 0 !important;
            padding: 0 !important;
            transform: none !important;
        }

        body.dark-mode .chat-cont-right.mobile-show {
            background: #1e1e1e !important;
        }

        .chat-cont-right .card {
            border-radius: 0 !important;
            height: 100% !important;
            width: 100% !important;
            border: none !important;
            margin: 0 !important;
            display: flex !important;
            flex-direction: column !important;
        }

        .card-body {
            height: 100%;
            overflow: hidden;
        }
    }

    /* Skeleton Loading CSS */
    @keyframes skeleton-pulse {
        0% { background-color: #e2e5e7; }
        50% { background-color: #f1f3f5; }
        100% { background-color: #e2e5e7; }
    }

    .skeleton-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }

    .skeleton-text {
        height: 60px;
        border-radius: 15px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }

    body.dark-mode .skeleton-loader-container {
        background-color: #121212 !important;
    }

    body.dark-mode .skeleton-avatar,
    body.dark-mode .skeleton-text {
        animation: skeleton-pulse-dark 1.5s infinite ease-in-out;
    }

    @keyframes skeleton-pulse-dark {
        0% { background-color: #2a2a2a; }
        50% { background-color: #3a3a3a; }
        100% { background-color: #2a2a2a; }
    }

    /* =============================================
       SIDEBAR REDESIGN
    ============================================= */

    .sidebar-top-panel {
        background: #fff;
        border: 1px solid #eef0f5;
        border-radius: 12px;
        box-shadow: 0 1px 6px rgba(0,0,0,0.04);
        overflow: hidden;
    }
    body.dark-mode .sidebar-top-panel {
        background: #1e1e2d;
        border-color: #2a2a3d;
    }

    .sidebar-header {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 16px 16px 10px;
    }
    .sidebar-header h6 {
        font-size: 1.1rem;
        font-weight: 700;
        color: #111827;
        margin: 0;
        letter-spacing: -0.3px;
    }
    .sidebar-header .subtitle {
        font-size: 0.78rem;
        color: #6b7280;
        margin: 2px 0 0;
    }
    body.dark-mode .sidebar-header h6 { color: #f0f0f0; }
    body.dark-mode .sidebar-header .subtitle { color: #9ca3af; }
    .sidebar-header-actions {
        display: flex;
        gap: 6px;
    }
    .sidebar-header-actions .btn {
        width: 32px;
        height: 32px;
        padding: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 8px;
        border: 1px solid #e5e7eb;
        background: #f9fafb;
        color: #6b7280;
        font-size: 0.85rem;
        transition: all 0.15s;
    }
    .sidebar-header-actions .btn:hover {
        background: #f3f4f6;
        color: #374151;
        border-color: #d1d5db;
    }

    .sidebar-search {
        padding: 0 12px 10px;
    }
    .sidebar-search .input-group {
        background: #f3f4f6;
        border-radius: 10px;
        overflow: hidden;
        border: 1.5px solid transparent;
        transition: border-color 0.2s;
        display: flex;
        align-items: center;
    }
    .sidebar-search .input-group:focus-within {
        border-color: #6366f1;
        background: #fff;
    }
    .sidebar-search .input-group-text {
        background: transparent;
        border: none;
        color: #9ca3af;
        padding: 0 10px 0 14px;
        display: flex;
        align-items: center;
    }
    .sidebar-search input {
        background: transparent;
        border: none;
        padding: 9px 4px;
        font-size: 0.875rem;
        color: #374151;
        box-shadow: none !important;
        flex: 1;
    }
    .sidebar-search input::placeholder { color: #9ca3af; }
    .sidebar-search .clear-btn {
        background: transparent;
        border: none;
        color: #9ca3af;
        padding: 0 14px 0 4px;
        cursor: pointer;
        font-size: 1rem;
        display: flex;
        align-items: center;
        justify-content: center;
        height: 100%;
    }
    .sidebar-search .clear-btn:hover { color: #374151; }

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
    .chat-item:last-child { border-bottom: none; }
    .chat-item:hover { background: #f9fafb; }
    .chat-item.is-selected { background: #eef2ff; }
    body.dark-mode .chat-item:hover { background: #25253a; }
    body.dark-mode .chat-item.is-selected { background: #1e1e4a; }
    body.dark-mode .chat-item { border-bottom-color: #2a2a3d; }

    .ci-avatar {
        position: relative;
        flex-shrink: 0;
    }
    .ci-avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1rem;
        font-weight: 700;
        color: #fff;
    }
    .ci-avatar-circle.agent-bg { background: linear-gradient(135deg, #6366f1, #8b5cf6); }
    
    .ci-status-dot {
        position: absolute;
        bottom: 1px;
        right: 1px;
        width: 11px;
        height: 11px;
        border-radius: 50%;
        border: 2px solid #fff;
    }
    .ci-status-dot.online  { background: #22c55e; }
    .ci-status-dot.offline { background: #9ca3af; }
    body.dark-mode .ci-status-dot { border-color: #1e1e2d; }

    .ci-content { flex: 1; min-width: 0; }
    .ci-row1 {
        display: flex;
        align-items: baseline;
        justify-content: space-between;
        gap: 6px;
        margin-bottom: 3px;
    }
    .ci-name {
        font-size: 0.9rem;
        font-weight: 600;
        color: #111827;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.dark-mode .ci-name { color: #f0f0f0; }
    .ci-time {
        font-size: 0.72rem;
        color: #9ca3af;
        white-space: nowrap;
        flex-shrink: 0;
    }
    .ci-row2 {
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .ci-preview {
        font-size: 0.8rem;
        color: #6b7280;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    body.dark-mode .ci-preview { color: #9ca3af; }

    .chat-empty-state {
        padding: 36px 16px;
        text-align: center;
        color: #9ca3af;
    }
    .chat-empty-state i {
        font-size: 2rem;
        display: block;
        margin-bottom: 8px;
        opacity: 0.4;
    }
</style>
@endpush

@section('content')
<div x-data="agentChat({{ $admin->id }}, {{ Js::from($conversations) }}, {{ Js::from($otherAdmins) }})">
    <div class="row chat-window">
        <div class="chat-cont-left flex-column transition-all"
            x-show="!sidebarCollapsed"
            :class="{
                 'mobile-hide d-none d-lg-flex': selectedChat,
                 'd-flex col-md-4 col-lg-5 col-xl-4': !sidebarCollapsed
             }">
            <!-- TOP PANEL -->
            <div class="sidebar-top-panel mb-2 flex-shrink-0">
                <div class="sidebar-header">
                    <div>
                        <h6>Chat Agent</h6>
                        <p class="subtitle" x-text="filteredChats.length + ' percakapan'"></p>
                    </div>
                    <div class="sidebar-header-actions">
                        <button class="btn" @click="showStartChatModal = true" title="Chat Baru">
                            <i class="fe fe-plus"></i>
                        </button>
                        <button class="btn" @click="fetchChats()" title="Refresh">
                            <i class="fe fe-refresh-cw"></i>
                        </button>
                    </div>
                </div>

                <div class="sidebar-search">
                    <div class="input-group">
                        <span class="input-group-text"><i class="fe fe-search" style="font-size:0.85rem;"></i></span>
                        <input type="text" x-model="searchQuery"
                            placeholder="Cari agen..."
                            class="form-control">
                        <span class="clear-btn" x-show="searchQuery.length > 0"
                            @click="searchQuery = ''" title="Hapus">
                            <i class="fe fe-x"></i>
                        </span>
                    </div>
                </div>
            </div>

            <!-- CHAT LIST PANEL -->
            <div class="chat-list-panel flex-grow-1 d-flex flex-column" style="overflow: hidden;">
                <div style="overflow-y: auto; flex: 1;">
                    <template x-for="chat in filteredChats" :key="chat.id">
                        <a href="javascript:void(0);" @click="selectChat(chat)"
                            class="chat-item"
                            :class="selectedChat && selectedChat.id === chat.id ? 'is-selected' : ''">

                            <div class="ci-avatar">
                                <div class="ci-avatar-circle agent-bg">
                                    <span x-text="getInitial(getOtherAdmin(chat).username)"></span>
                                </div>
                                <span class="ci-status-dot"
                                    :class="getOtherAdmin(chat).status === 'online' ? 'online' : 'offline'"></span>
                            </div>

                            <div class="ci-content">
                                <div class="ci-row1">
                                    <span class="ci-name" x-text="getOtherAdmin(chat).username"></span>
                                    <span class="ci-time" x-text="formatShortDateTime(chat.last_message_at || chat.created_at)"></span>
                                </div>
                                <div class="ci-row2">
                                    <span class="ci-preview" x-text="getPreview(chat)"></span>
                                </div>
                            </div>
                        </a>
                    </template>

                    <div x-show="filteredChats.length === 0" class="chat-empty-state">
                        <i class="fe fe-message-circle"></i>
                        <p>Belum ada percakapan.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- RIGHT PANEL -->
        <div class="chat-cont-right transition-all flex-grow-1"
            :class="{
                     'col-md-8 col-lg-7 col-xl-8': !sidebarCollapsed,
                     'col-12': sidebarCollapsed,
                     'd-none d-lg-flex': !selectedChat && !sidebarCollapsed,
                     'mobile-show d-flex': selectedChat || sidebarCollapsed
                 }">
            <div class="card mb-0 w-100 h-100" x-show="selectedChat" x-cloak>
                <div class="h-100 d-flex flex-column">
                    <div class="card-header msg_head px-3 py-2">
                        <div class="d-flex bd-highlight align-items-center w-100">
                            <a href="javascript:void(0)" class="back-user-list me-3 d-lg-none text-dark"
                                @click="selectedChat = null">
                                <i class="fas fa-arrow-left fa-lg"></i>
                            </a>
                            <a href="javascript:void(0)" class="me-3 d-none d-lg-block text-secondary" @click="sidebarCollapsed = !sidebarCollapsed">
                                <i class="fas fa-bars fa-lg"></i>
                            </a>
                            <div class="img_cont flex-shrink-0">
                                <div class="avatar avatar-sm">
                                    <div class="avatar-title rounded-circle bg-primary text-white">
                                        <span x-text="selectedChat ? getInitial(getOtherAdmin(selectedChat).username) : ''"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="user_info ms-2 flex-grow-1 overflow-hidden">
                                <span class="text-truncate d-block" x-text="selectedChat ? getOtherAdmin(selectedChat).username : ''"></span>
                                <p class="mb-0 small" :class="selectedChat && getOtherAdmin(selectedChat).status === 'online' ? 'text-success' : 'text-muted'" 
                                   x-text="selectedChat && getOtherAdmin(selectedChat).status === 'online' ? 'Online' : 'Offline'"></p>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 flex-grow-1 position-relative" style="min-height: 0;">
                        <div x-show="!iframeLoaded && selectedChat"
                            class="skeleton-loader-container position-absolute w-100 h-100 bg-white"
                            style="z-index: 10; padding: 20px;">
                            <div class="skeleton-text w-75 mb-3"></div>
                            <div class="skeleton-text w-50"></div>
                        </div>
                        <iframe :src="selectedChat ? '/admin/agent-chat/conversation/' + selectedChat.id : 'about:blank'"
                            class="w-100 h-100"
                            @load="iframeLoaded = true"></iframe>
                    </div>
                </div>
            </div>
            
          

    <!-- Start Chat Modal -->
    <div class="modal fade" :class="showStartChatModal ? 'show d-block' : ''" tabindex="-1" x-show="showStartChatModal" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Mulai Chat dengan Agen</h5>
                    <button type="button" class="btn-close" @click="showStartChatModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="list-group">
                        <template x-for="other in otherAdmins" :key="other.id">
                            <button type="button" class="list-group-item list-group-item-action d-flex align-items-center" @click="startChat(other.id)">
                                <div class="avatar avatar-xs me-2">
                                    <div class="avatar-title rounded-circle bg-secondary text-white" x-text="getInitial(other.username)"></div>
                                </div>
                                <span x-text="other.username"></span>
                                <span class="ms-auto badge" :class="other.status === 'online' ? 'bg-success' : 'bg-secondary'" x-text="other.status"></span>
                            </button>
                        </template>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('agentChat', (adminId, initialConversations, otherAdmins) => ({
            adminId: adminId,
            chats: initialConversations,
            otherAdmins: otherAdmins,
            selectedChat: null,
            searchQuery: '',
            sidebarCollapsed: false,
            iframeLoaded: false,
            showStartChatModal: false,

            init() {
                setInterval(() => {
                    this.fetchChats();
                }, 5000);
                
                let echoCheckInterval = setInterval(() => {
                    if (window.Echo) {
                        window.Echo.private('admin.dashboard')
                            .listen('.admin_message.sent', (e) => {
                                this.fetchChats();
                            });
                        clearInterval(echoCheckInterval);
                    }
                }, 500);
            },

            async fetchChats() {
                try {
                    const res = await fetch('/admin/agent-chat?ajax=1', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    });
                    const data = await res.json();
                    this.chats = data.conversations;
                    this.otherAdmins = data.other_admins;
                    
                    if (this.selectedChat) {
                        const updated = this.chats.find(c => c.id === this.selectedChat.id);
                        if (updated) {
                            this.selectedChat = {...this.selectedChat, ...updated};
                        }
                    }
                } catch (e) {
                    console.error('Failed to fetch chats', e);
                }
            },

            get filteredChats() {
                if (!this.searchQuery.trim()) return this.chats;
                const query = this.searchQuery.toLowerCase();
                return this.chats.filter(c => {
                    const other = this.getOtherAdmin(c);
                    return other.username.toLowerCase().includes(query);
                });
            },

            getOtherAdmin(chat) {
                return chat.admin_1_id === this.adminId ? chat.admin2 : chat.admin1;
            },

            getInitial(name) {
                return (name || '?').charAt(0).toUpperCase();
            },

            getPreview(chat) {
                if (!chat.messages || chat.messages.length === 0) return 'Belum ada pesan';
                const msg = chat.messages[0];
                if (msg.message_type === 'image') return '📷 Foto';
                if (msg.message_type === 'file') return '📄 Dokumen';
                return msg.content || 'Belum ada pesan';
            },

            selectChat(chat) {
                if (!this.selectedChat || this.selectedChat.id !== chat.id) {
                    this.iframeLoaded = false;
                }
                this.selectedChat = chat;
            },

            async startChat(targetAdminId) {
                try {
                    const res = await fetch('/admin/agent-chat/start', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({ admin_id: targetAdminId })
                    });
                    const data = await res.json();
                    if (data.success) {
                        this.showStartChatModal = false;
                        await this.fetchChats();
                        const newChat = this.chats.find(c => c.id === data.conversation.id);
                        if (newChat) this.selectChat(newChat);
                    }
                } catch (e) {
                    console.error('Failed to start chat', e);
                }
            },

            formatShortDateTime(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                return date.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
            }
        }));
    });
</script>
@endpush
