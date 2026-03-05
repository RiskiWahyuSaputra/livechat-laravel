@extends('layouts.admin_template')

@section('title', 'Chat Workspace')

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

    .msg_card_body {
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
        0% {
            background-color: #e2e5e7;
        }

        50% {
            background-color: #f1f3f5;
        }

        100% {
            background-color: #e2e5e7;
        }
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
        0% {
            background-color: #2a2a2a;
        }

        50% {
            background-color: #3a3a3a;
        }

        100% {
            background-color: #2a2a2a;
        }
    }

    @keyframes pulse-danger {
        0% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0.7);
        }

        70% {
            transform: scale(1);
            box-shadow: 0 0 0 10px rgba(220, 53, 69, 0);
        }

        100% {
            transform: scale(0.95);
            box-shadow: 0 0 0 0 rgba(220, 53, 69, 0);
        }
    }

    .pulse-animation {
        animation: pulse-danger 2s infinite;
    }
</style>
@endpush

@section('content')
<div x-data="adminChat({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }})">
    <div class="row chat-window">
        <!-- Chat User List -->
        <div class="chat-cont-left d-flex transition-all"
            :class="{
                 'col-lg-5 col-xl-4': !sidebarCollapsed,
                 'd-none': sidebarCollapsed,
                 'mobile-hide': selectedChat
             }">
            <div class="card mb-0 contacts_card flex-fill">
                <div class="chat-header">
                    <div>
                        <h6>Percakapan</h6>
                        <p x-text="filteredChats.length + ' Aktif & Antrean'"></p>
                        <!-- Debug info -->
                        <p class="text-xs text-muted" x-show="chats.length > 0" x-text="'Total: ' + chats.length + ' chats'"></p>
                    </div>
                    <div class="d-flex gap-2">
                        <button @click="sortBy = sortBy === 'recent' ? 'oldest' : 'recent'; fetchChats()"
                            class="btn btn-sm btn-outline-secondary"
                            :title="sortBy === 'recent' ? 'Terbaru - Klik untuk urutkan terlama' : 'Terlama - Klik untuk urutkan terbaru'">
                            <i class="fe" :class="sortBy === 'recent' ? 'fe-arrow-up' : 'fe-arrow-down'"></i>
                        </button>
                        <button @click="fetchChats()" class="btn btn-sm btn-outline-secondary" title="Refresh">
                            <i class="fe fe-refresh-cw"></i>
                        </button>
                    </div>
                </div>
                <div class="chat-search">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="search_btn"><i class="fe fe-search"></i></span>
                        </div>
                        <input type="text" x-model="searchQuery" @input.debounce.500ms="fetchChats()" placeholder="Cari nama, kontak, atau pesan..." class="form-control search-chat">
                    </div>
                </div>

                <div class="card-body contacts_body chat-users-list chat-scroll">
                    <!-- Antrean Section -->
                    <div class="chat-header inner-chat-header pt-0">
                        <div>
                            <h6>Permintaan Baru</h6>
                        </div>
                    </div>
                    <div class="mb-3">
                        <template x-for="chat in filteredChats.filter(c => ['pending', 'queued'].includes(c.status))" :key="chat.id">
                            <a href="javascript:void(0);" @click="selectChat(chat)" class="media d-flex" :class="selectedChat && selectedChat.id === chat.id ? 'active' : ''">
                                <div class="media-img-wrap flex-shrink-0">
                                    <div class="avatar avatar-online">
                                        <div class="avatar-title rounded-circle bg-danger text-white">
                                            <span x-text="chat.customer.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-body flex-grow-1">
                                    <div>
                                        <div class="user-name" x-text="chat.customer.name"></div>
                                        <div class="user-last-chat font-weight-bold"
                                            :class="isLongWaiting(chat.last_message_at) ? 'text-white bg-danger px-2 py-1 rounded-pill pulse-animation d-inline-block mt-1' : 'text-danger'"
                                            x-text="chat.status === 'queued' ? 'Antrean #' + chat.queue_position : 'Baru'"
                                            :style="isLongWaiting(chat.last_message_at) ? 'font-size: 0.75rem;' : ''"></div>
                                        <div class="user-last-chat text-muted" style="font-size: 0.75em;">Mulai: <span x-text="formatShortDateTime(chat.created_at)"></span></div>
                                    </div>
                                    <div>
                                        <div class="last-chat-time" x-text="formatTime(chat.last_message_at)"></div>
                                    </div>
                                </div>
                            </a>
                        </template>
                        <div x-show="filteredChats.filter(c => ['pending', 'queued'].includes(c.status)).length === 0" class="text-center p-3 text-muted small">
                            Tidak ada antrean.
                        </div>
                    </div>

                    <!-- Aktif Section -->
                    <div class="chat-header inner-chat-header">
                        <div>
                            <h6>Sedang Dibantu</h6>
                        </div>
                    </div>
                    <div>
                        <template x-for="chat in filteredChats.filter(c => c.status === 'active')" :key="chat.id">
                            <a href="javascript:void(0);" @click="selectChat(chat)" class="media d-flex" :class="selectedChat && selectedChat.id === chat.id ? 'active' : ''">
                                <div class="media-img-wrap flex-shrink-0">
                                    <div class="avatar" :class="chat.customer.is_online ? 'avatar-online' : 'avatar-away'">
                                        <div class="avatar-title rounded-circle bg-primary text-white">
                                            <span x-text="chat.customer.name.charAt(0).toUpperCase()"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="media-body flex-grow-1">
                                    <div>
                                        <div class="user-name" x-text="chat.customer.name"></div>
                                        <div class="user-last-chat" x-text="chat.admin_id === adminId ? 'Anda membantu' : 'Oleh ' + (chat.admin ? chat.admin.username : 'agen')"></div>
                                        <div class="user-last-chat text-muted" style="font-size: 0.75em;">Mulai: <span x-text="formatShortDateTime(chat.created_at)"></span></div>
                                    </div>
                                    <div>
                                        <div class="last-chat-time" x-text="formatTime(chat.last_message_at)"></div>
                                    </div>
                                </div>
                            </a>
                        </template>
                        <div x-show="filteredChats.filter(c => c.status === 'active').length === 0" class="text-center p-3 text-muted small">
                            Tidak ada chat aktif.
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Chat Content -->
        <div class="chat-cont-right transition-all"
            :class="{
                 'col-lg-7 col-xl-8': !sidebarCollapsed,
                 'col-lg-12 col-xl-12': sidebarCollapsed,
                 'mobile-show': selectedChat
             }">
            <div class="card mb-0 w-100 h-100" x-show="selectedChat" x-cloak>
                <div class="h-100 d-flex flex-column">
                    <div class="card-header msg_head px-3 py-2">
                        <div class="d-flex bd-highlight align-items-center w-100">
                            <a href="javascript:void(0)" class="back-user-list me-3 d-lg-none"
                                :class="darkMode ? 'text-white' : 'text-dark'"
                                @click="selectedChat = null">
                                <i class="fas fa-arrow-left fa-lg"></i>
                            </a>
                            <a href="javascript:void(0)" class="me-3 d-none d-lg-block text-secondary" @click="sidebarCollapsed = !sidebarCollapsed" title="Toggle Sidebar">
                                <i class="fas fa-bars fa-lg"></i>
                            </a>
                            <div class="img_cont flex-shrink-0">
                                <div class="avatar avatar-sm">
                                    <div class="avatar-title rounded-circle bg-primary text-white">
                                        <span x-text="selectedChat ? selectedChat.customer.name.charAt(0).toUpperCase() : ''"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="user_info ms-2 flex-grow-1 overflow-hidden">
                                <span class="text-truncate d-block" x-text="selectedChat ? selectedChat.customer.name : ''"></span>
                                <p class="mb-0 small" :class="selectedChat && selectedChat.customer.is_online ? 'text-success' : 'text-muted'" x-text="selectedChat && selectedChat.customer.is_online ? 'Online' : 'Offline'"></p>
                            </div>
                            <div class="chat-options ms-auto flex-shrink-0">
                                <ul class="d-flex align-items-center list-unstyled mb-0">
                                    <template x-if="selectedChat && ['pending', 'queued'].includes(selectedChat.status)">
                                        <li class="ms-2">
                                            <button class="btn btn-sm btn-primary px-3" @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                                <span x-text="isClaiming ? 'Claiming...' : 'Claim Chat'"></span>
                                            </button>
                                        </li>
                                    </template>
                                    <template x-if="selectedChat && selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                        <li class="d-flex ms-2">
                                            <button class="btn btn-sm btn-outline-info me-1" @click="showHandoverModal = true" title="Oper Chat"><i class="fe fe-repeat"></i></button>
                                            <button class="btn btn-sm btn-outline-success me-1" @click="confirmCloseChat()" :disabled="isSubmitting" title="Selesaikan"><i class="fe fe-check"></i></button>
                                            <button class="btn btn-sm btn-outline-danger" @click="blockUser(selectedChat.id)" title="Blokir"><i class="fe fe-slash"></i></button>
                                        </li>
                                    </template>
                                </ul>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-0 flex-grow-1 position-relative" style="min-height: 0;">
                        <!-- Skeleton Loader overlay -->
                        <div x-show="!iframeLoaded && selectedChat"
                            class="skeleton-loader-container position-absolute w-100 h-100 bg-white"
                            style="z-index: 10; padding: 20px; pointer-events: none;">
                            <div class="skeleton-text w-75 mb-3"></div>
                            <div class="skeleton-text w-50"></div>
                        </div>
                        <iframe :src="selectedChat ? '/admin/conversation/' + selectedChat.id : 'about:blank'"
                            class="w-100 h-100"
                            style="border: none; display: block;"
                            @load="iframeLoaded = true"></iframe>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" :class="showHandoverModal ? 'show d-block' : ''" tabindex="-1" x-show="showHandoverModal" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Oper Percakapan</h5>
                    <button type="button" class="btn-close" @click="showHandoverModal = false"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Pilih Admin</label>
                        <select x-model="handoverToAdminId" class="form-select">
                            <option value="">-- Pilih Admin --</option>
                            @foreach($otherAdmins as $other)
                            <option value="{{ $other->id }}">{{ $other->username }} ({{ ucfirst($other->status) }})</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Catatan Internal (Opsional)</label>
                        <textarea x-model="handoverNote" class="form-control" rows="3" placeholder="Pesan untuk agen selanjutnya..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showHandoverModal = false">Batal</button>
                    <button type="button" class="btn btn-primary" @click="handoverChat()" :disabled="!handoverToAdminId || isSubmitting">Oper</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('adminChat', (adminId, initPending, initActive) => ({
            adminId: adminId,
            chats: [...initPending, ...initActive],
            currentTime: Date.now(),
            sidebarCollapsed: false,
            selectedChat: null,
            searchQuery: '',
            sortBy: 'recent',
            isClaiming: false,
            isSubmitting: false,
            showHandoverModal: false,
            handoverToAdminId: '',
            handoverNote: '',
            audioUnlocked: false,
            notificationSound: null,
            iframeLoaded: false,

            init() {
                // Update currentTime every minute for relative time reactivity
                setInterval(() => {
                    this.currentTime = Date.now();
                }, 60000);

                // Auto-refresh chat list every 5 seconds
                setInterval(() => {
                    console.log('🔄 Auto-refresh chat list...');
                    this.fetchChats();
                }, 5000);

                // Aktifkan audio saat ada interaksi pertama dari user (diklik/ketik)
                const unlockAudio = () => {
                    if (!this.notificationSound) {
                        this.notificationSound = new Audio('{{ asset("sounds/notification.mp3") }}');
                        this.notificationSound.volume = 0; // Mute for dummy play
                        this.notificationSound.play().then(() => {
                            this.notificationSound.pause();
                            this.notificationSound.currentTime = 0;
                            this.notificationSound.volume = 1;
                            this.audioUnlocked = true;
                            console.log("🔊 Audio unlocked");
                        }).catch(e => console.log(e));
                    }
                    document.removeEventListener('click', unlockAudio);
                    document.removeEventListener('keydown', unlockAudio);
                };
                document.addEventListener('click', unlockAudio);
                document.addEventListener('keydown', unlockAudio);

                // Menunggu window.Echo siap (Vite memuat secara asinkron)
                let echoCheckRetry = 0;
                const maxRetries = 10;
                const echoCheckInterval = setInterval(() => {
                    echoCheckRetry++;
                    if (window.Echo) {
                        console.log('✅ Connecting to admin.dashboard channel...');
                        window.Echo.private('admin.dashboard')
                            .listen('.conversation.status.changed', (e) => {
                                console.log('🔔 Status Changed Received:', e);
                                console.log('📝 Conversation ID:', e.conversation_id, 'Status:', e.status);

                                // Update status offline secara reaktif dari data broadcast
                                if (e.customer) {
                                    // 1. Update di chat yang terpilih (Header)
                                    if (this.selectedChat && this.selectedChat.customer && this.selectedChat.customer.id === e.customer.id) {
                                        this.selectedChat.customer.is_online = e.customer.is_online;
                                        // Paksa refresh teks status jika Alpine tidak mendeteksi perubahan properti dalam
                                        this.$nextTick(() => {
                                            this.selectedChat = {
                                                ...this.selectedChat
                                            };
                                        });
                                    }

                                    // 2. Update di daftar chat di kiri
                                    this.chats.forEach(c => {
                                        if (c.customer && c.customer.id === e.customer.id) {
                                            c.customer.is_online = e.customer.is_online;
                                        }
                                    });
                                }

                                if (this.selectedChat && this.selectedChat.id === e.conversation_id && e.status === 'closed') {
                                    this.selectedChat.status = 'closed';

                                    // Refresh list setelah jeda agar chat pindah ke history
                                    setTimeout(() => {
                                        this.fetchChats();
                                    }, 1000);
                                } else {
                                    this.fetchChats();
                                }

                                // Play sound if it's a new or queued request
                                if (['pending', 'queued'].includes(e.status)) {
                                    this.playNotification();
                                }
                            });
                        clearInterval(echoCheckInterval);
                    } else if (echoCheckRetry >= maxRetries) {
                        console.error('❌ Laravel Echo not found after max retries.');
                        clearInterval(echoCheckInterval);
                    }
                }, 500);
            },

            playNotification() {
                if (this.audioUnlocked && this.notificationSound) {
                    this.notificationSound.currentTime = 0;
                    this.notificationSound.play().catch(e => console.log("Playback failed:", e));
                } else {
                    const audio = new Audio('{{ asset("sounds/notification.mp3") }}');
                    audio.play().catch(e => {
                        console.log("Audio play blocked. Click anywhere on the page first to unlock sound.");
                    });
                }
            },

            async fetchChats() {
                console.log('🔄 Fetching chats...');
                try {
                    const searchParam = this.searchQuery.trim() ? `&search=${encodeURIComponent(this.searchQuery.trim())}` : '';
                    const res = await fetch(`/admin/chat?ajax=1&sort=${this.sortBy}${searchParam}`);
                    const data = await res.json();
                    console.log('📋 Chats received:', data);
                    this.chats = [...data.pending, ...data.active];
                    console.log('✅ Total chats loaded:', this.chats.length);
                } catch (e) {
                    console.error('Failed to fetch chats', e);
                }
            },

            get filteredChats() {
                let result = this.chats;

                // Sorting is handled by server now, but keep client-side sorting as fallback
                if (this.sortBy === 'recent') {
                    result = [...result].sort((a, b) => {
                        const dateA = new Date(a.last_message_at || a.created_at);
                        const dateB = new Date(b.last_message_at || b.created_at);
                        return dateB - dateA; // Newest first
                    });
                } else if (this.sortBy === 'oldest') {
                    result = [...result].sort((a, b) => {
                        const dateA = new Date(a.last_message_at || a.created_at);
                        const dateB = new Date(b.last_message_at || b.created_at);
                        return dateA - dateB; // Oldest first
                    });
                }

                return result;
            },

            formatTime(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta'
                };
                return date.toLocaleString('id-ID', options);
            },

            isLongWaiting(datetimeString) {
                if (!datetimeString) return false;
                // Force reactivity by referencing this.currentTime
                const now = this.currentTime;
                // Safely parse ISO string
                const diff = now - new Date(datetimeString.replace(/-/g, '/')).getTime();
                return diff > 3 * 60 * 1000; // 3 minutes
            },

            formatShortDateTime(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                const options = {
                    year: '2-digit',
                    month: '2-digit',
                    day: '2-digit',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta'
                };
                return date.toLocaleString('id-ID', options);
            },

            formatFullDateTime(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                const options = {
                    year: 'numeric',
                    month: 'long',
                    day: 'numeric',
                    hour: '2-digit',
                    minute: '2-digit',
                    timeZone: 'Asia/Jakarta'
                };
                // Using 'id-ID' for Indonesian locale. Ensure the browser supports it.
                return date.toLocaleString('id-ID', options);
            },
            selectChat(chat) {
                if (!this.selectedChat || this.selectedChat.id !== chat.id) {
                    this.iframeLoaded = false;
                }
                this.selectedChat = chat;
            },

            async claimChat(conversationId) {
                this.isClaiming = true;
                try {
                    const res = await fetch(`/admin/conversation/${conversationId}/claim`, {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Gagal mengambil chat');

                    // Update lists without reload
                    await this.fetchChats();

                    // If we just claimed it, find it in the new list and select it
                    const claimed = this.chats.find(c => c.id === conversationId);
                    if (claimed) {
                        this.selectChat(claimed);
                    }
                } catch (error) {
                    Toast.fire({
                        icon: 'error',
                        title: error.message
                    });
                } finally {
                    this.isClaiming = false;
                }
            },

            async confirmCloseChat() {
                Swal.fire({
                    title: 'Selesaikan Percakapan?',
                    text: 'Percakapan ini akan ditutup dan dipindahkan ke riwayat.',
                    icon: 'question',
                    showCancelButton: true,
                    confirmButtonColor: '#28a745',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Selesaikan',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        await this.closeChat();
                    }
                });
            },

            async closeChat() {
                this.isSubmitting = true;
                try {
                    const res = await fetch(`/admin/conversation/${this.selectedChat.id}/close`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        }
                    });
                    if (!res.ok) throw new Error('Gagal menyelesaikan chat');

                    Toast.fire({
                        icon: 'success',
                        title: 'Percakapan diselesaikan'
                    });

                    // Reset selection and refresh list
                    this.selectedChat = null;
                    await this.fetchChats();
                } catch (e) {
                    Toast.fire({
                        icon: 'error',
                        title: e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async handoverChat() {
                this.isSubmitting = true;
                try {
                    const res = await fetch(`/admin/conversation/${this.selectedChat.id}/handover`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}'
                        },
                        body: JSON.stringify({
                            to_admin_id: this.handoverToAdminId,
                            internal_note: this.handoverNote
                        })
                    });
                    if (!res.ok) throw new Error('Gagal mengoper chat');
                    window.location.reload();
                } catch (e) {
                    Toast.fire({
                        icon: 'error',
                        title: e.message
                    });
                } finally {
                    this.isSubmitting = false;
                }
            },

            async blockUser(conversationId) {
                Swal.fire({
                    title: 'Blokir pelanggan?',
                    text: 'Anda yakin ingin memblokir pelanggan ini?',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Blokir!',
                    cancelButtonText: 'Batal'
                }).then(async (result) => {
                    if (result.isConfirmed) {
                        try {
                            const res = await fetch(`/admin/conversation/${conversationId}/block`, {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                                }
                            });
                            if (!res.ok) throw new Error('Gagal blokir pelanggan');
                            window.location.reload();
                        } catch (e) {
                            Toast.fire({
                                icon: 'error',
                                title: e.message
                            });
                        }
                    }
                });
            }
        }));
    });
</script>
@endpush