@extends('layouts.admin_template')

@section('title', 'Chat Workspace')

@push('styles')
<style>
    .chat-window {
        height: calc(100vh - 150px);
        margin: 0;
    }
    .chat-cont-left, .chat-cont-right, .chat-cont-profile {
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
    [x-cloak] { display: none !important; }

    /* Mobile Responsive Logic */
    @media (max-width: 991.98px) {
        .chat-cont-left, .chat-cont-right {
            display: none !important; /* Hide both by default on mobile */
        }
        .chat-window {
            height: calc(100vh - 120px);
            position: relative;
        }
        .chat-cont-left:not(.mobile-hide) {
            display: flex !important;
            width: 100%;
            height: 100%;
        }
        .chat-cont-right.mobile-show {
            display: flex !important;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: 1000;
            background: white;
        }
        .chat-cont-right .card {
            border-radius: 0;
            height: 100% !important;
        }
    }

    /* Skeleton Loading CSS */
    @keyframes skeleton-pulse {
        0% { background-color: #e2e5e7; }
        50% { background-color: #f1f3f5; }
        100% { background-color: #e2e5e7; }
    }
    .skeleton-avatar {
        width: 40px; height: 40px; border-radius: 50%;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }
    .skeleton-text {
        height: 60px; border-radius: 15px;
        animation: skeleton-pulse 1.5s infinite ease-in-out;
    }
    body.dark-mode .skeleton-loader-container { background-color: #121212 !important; }
    body.dark-mode .skeleton-avatar, body.dark-mode .skeleton-text {
        animation: skeleton-pulse-dark 1.5s infinite ease-in-out;
    }
    @keyframes skeleton-pulse-dark {
        0% { background-color: #2a2a2a; }
        50% { background-color: #3a3a3a; }
        100% { background-color: #2a2a2a; }
    }
</style>
@endpush

@section('content')
<div x-data="adminChat({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }})">
    <div class="row chat-window">
        <!-- Chat User List -->
        <div class="col-lg-5 col-xl-4 chat-cont-left d-flex" :class="selectedChat ? 'mobile-hide' : ''">
            <div class="card mb-0 contacts_card flex-fill">
                <div class="chat-header">
                    <div>
                        <h6>Percakapan</h6>
                        <p x-text="filteredChats.length + ' Aktif & Antrean'"></p>
                    </div>
                </div>
                <div class="chat-search">
                    <div class="input-group">
                        <div class="input-group-prepend">
                            <span class="search_btn"><i class="fe fe-search"></i></span>
                        </div>
                        <input type="text" x-model="searchQuery" placeholder="Cari nama atau kontak..." class="form-control search-chat">
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
                                        <div class="user-last-chat text-danger font-weight-bold" x-text="chat.status === 'queued' ? 'Antrean #' + chat.queue_position : 'Baru'"></div>
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
        <div class="col-lg-7 col-xl-8 chat-cont-right d-flex" :class="selectedChat ? 'mobile-show' : ''">
            <div class="card mb-0 w-100 h-100">
                <div class="h-100 d-flex flex-column" x-show="selectedChat">
                    <div class="card-header msg_head">
                        <div class="d-flex bd-highlight align-items-center">
                            <a href="javascript:void(0)" class="back-user-list me-2 d-lg-none" @click="selectedChat = null">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                            <div class="img_cont">
                                <div class="avatar avatar-sm">
                                    <div class="avatar-title rounded-circle bg-primary text-white">
                                        <span x-text="selectedChat ? selectedChat.customer.name.charAt(0).toUpperCase() : ''"></span>
                                    </div>
                                </div>
                            </div>
                            <div class="user_info ms-2">
                                <span x-text="selectedChat ? selectedChat.customer.name : ''"></span>
                                <p class="mb-0" :class="selectedChat && selectedChat.customer.is_online ? 'text-success' : 'text-muted'" x-text="selectedChat && selectedChat.customer.is_online ? 'Online' : 'Offline'"></p>
                            </div>
                        </div>
                        <div class="chat-options">
                            <ul class="d-flex align-items-center list-unstyled mb-0">
                                <template x-if="selectedChat && ['pending', 'queued'].includes(selectedChat.status)">
                                    <li class="me-2">
                                        <button class="btn btn-sm btn-primary" @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                            <span x-text="isClaiming ? 'Claiming...' : 'Claim Chat'"></span>
                                        </button>
                                    </li>
                                </template>
                                <template x-if="selectedChat && selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                    <li class="d-flex">
                                        <button class="btn btn-sm btn-outline-info me-1" @click="showHandoverModal = true" title="Oper Chat"><i class="fe fe-repeat"></i></button>
                                        <button class="btn btn-sm btn-outline-success me-1" @click="showCloseModal = true" title="Selesaikan"><i class="fe fe-check"></i></button>
                                        <button class="btn btn-sm btn-outline-danger" @click="blockUser(selectedChat.id)" title="Blokir"><i class="fe fe-slash"></i></button>
                                    </li>
                                </template>
                            </ul>
                        </div>
                    </div>

                    <div class="card-body p-0 flex-grow-1 position-relative">
                        <!-- Skeleton Loader overlay -->
                        <div x-show="!iframeLoaded && selectedChat" class="skeleton-loader-container position-absolute w-100 h-100 bg-white" style="z-index: 10; padding: 20px;">
                            <div class="d-flex mb-4">
                                <div class="skeleton-avatar me-3"></div>
                                <div class="skeleton-text w-50"></div>
                            </div>
                            <div class="d-flex mb-4 justify-content-end">
                                <div class="skeleton-text w-50 me-3"></div>
                                <div class="skeleton-avatar"></div>
                            </div>
                            <div class="d-flex mb-4">
                                <div class="skeleton-avatar me-3"></div>
                                <div class="skeleton-text w-75"></div>
                            </div>
                        </div>
                        <iframe :src="selectedChat ? '/admin/conversation/' + selectedChat.id : 'about:blank'" @load="iframeLoaded = true"></iframe>
                    </div>
                </div>
                
              
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" :class="showCloseModal ? 'show d-block' : ''" tabindex="-1" x-show="showCloseModal" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Selesaikan Percakapan</h5>
                    <button type="button" class="btn-close" @click="showCloseModal = false"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label">Kategori Masalah</label>
                    <select x-model="closeCategory" class="form-select">
                        <option value="">-- Pilih Kategori --</option>
                        <option value="Info Produk">Info Produk</option>
                        <option value="Dukungan Teknis">Dukungan Teknis</option>
                        <option value="Pembayaran">Pembayaran</option>
                        <option value="Komplain">Komplain</option>
                        <option value="Lainnya">Lainnya</option>
                    </select>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" @click="showCloseModal = false">Batal</button>
                    <button type="button" class="btn btn-primary" @click="closeChat()" :disabled="!closeCategory || isSubmitting">Selesaikan</button>
                </div>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showCloseModal || showHandoverModal ? 'show d-block' : ''" x-show="showCloseModal || showHandoverModal" x-cloak></div>

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
            selectedChat: null,
            searchQuery: '',
            isClaiming: false,
            isSubmitting: false,
            showCloseModal: false,
            showHandoverModal: false,
            closeCategory: '',
            handoverToAdminId: '',
            handoverNote: '',
            audioUnlocked: false,
            notificationSound: null,
            iframeLoaded: false,

            init() {
                // Aktifkan audio saat ada interaksi pertama dari user (diklik/ketik)
                const unlockAudio = () => {
                    if (!this.notificationSound) {
                        this.notificationSound = new Audio('{{ asset('sounds/notification.mp3') }}');
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
                // Gunakan setTimeout agar Echo sudah terinisialisasi dari app.js (Vite)
                setTimeout(() => {
                    if (window.Echo) {
                        console.log('✅ Connecting to admin.dashboard channel...');
                        window.Echo.private('admin.dashboard')
                            .listen('.conversation.status.changed', (e) => {
                                console.log('🔔 New Request Received:', e);
                                this.fetchChats();
                                
                                // Play sound if it's a new or queued request
                                if (['pending', 'queued'].includes(e.status)) {
                                    this.playNotification();
                                }
                            });
                    } else {
                        console.error('❌ Laravel Echo not found. Check app.js compilation.');
                    }
                }, 1000);
            },

            playNotification() {
                if (this.audioUnlocked && this.notificationSound) {
                    this.notificationSound.currentTime = 0;
                    this.notificationSound.play().catch(e => console.log("Playback failed:", e));
                } else {
                    const audio = new Audio('{{ asset('sounds/notification.mp3') }}');
                    audio.play().catch(e => {
                        console.log("Audio play blocked. Click anywhere on the page first to unlock sound.");
                    });
                }
            },

            async fetchChats() {
                try {
                    const res = await fetch('/admin/chat?ajax=1');
                    const data = await res.json();
                    this.chats = [...data.pending, ...data.active];
                } catch (e) { console.error('Failed to fetch chats', e); }
            },

            get filteredChats() {
                if (!this.searchQuery.trim()) return this.chats;
                const query = this.searchQuery.toLowerCase();
                return this.chats.filter(chat => 
                    chat.customer.name.toLowerCase().includes(query) || 
                    (chat.customer.contact && chat.customer.contact.toLowerCase().includes(query))
                );
            },

            formatTime(datetimeString) {
                if (!datetimeString) return '';
                const date = new Date(datetimeString);
                return date.toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'});
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
                        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' }
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
                    Toast.fire({ icon: 'error', title: error.message }); 
                }
                finally { this.isClaiming = false; }
            },

            async closeChat() {
                this.isSubmitting = true;
                try {
                    const res = await fetch(`/admin/conversation/${this.selectedChat.id}/close`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ problem_category: this.closeCategory })
                    });
                    if (!res.ok) throw new Error('Gagal menyelesaikan chat');
                    window.location.reload();
                } catch (e) { 
                    Toast.fire({ icon: 'error', title: e.message }); 
                }
                finally { this.isSubmitting = false; }
            },

            async handoverChat() {
                this.isSubmitting = true;
                try {
                    const res = await fetch(`/admin/conversation/${this.selectedChat.id}/handover`, {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
                        body: JSON.stringify({ 
                            to_admin_id: this.handoverToAdminId,
                            internal_note: this.handoverNote 
                        })
                    });
                    if (!res.ok) throw new Error('Gagal mengoper chat');
                    window.location.reload();
                } catch (e) { 
                    Toast.fire({ icon: 'error', title: e.message }); 
                }
                finally { this.isSubmitting = false; }
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
                                headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
                            });
                            if (!res.ok) throw new Error('Gagal blokir pelanggan');
                            window.location.reload();
                        } catch (e) { 
                            Toast.fire({ icon: 'error', title: e.message }); 
                        }
                    }
                });
            }
        }));
    });
</script>
@endpush
