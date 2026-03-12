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

        /* =============================================
           SIDEBAR REDESIGN
        ============================================= */

        /* Top panel (header + search + content filters) */
        .sidebar-top-panel {
            background: #fff;
            border: 1px solid #eef0f5;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        body.dark-mode .sidebar-top-panel {
            background: #1e1e2d;
            border-color: #2a2a3d;
        }

        /* Header inside top panel */
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

        body.dark-mode .sidebar-header h6 {
            color: #f0f0f0;
        }

        body.dark-mode .sidebar-header .subtitle {
            color: #9ca3af;
        }

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

        /* Search box */
        .sidebar-search {
            padding: 0 16px 12px;
        }

        .sidebar-search .input-group {
            background: #f3f4f6;
            border-radius: 10px;
            overflow: hidden;
            border: 1.5px solid transparent;
            transition: border-color 0.2s;
            display: flex;
            align-items: center;
            /* Memastikan semua elemen (ikon kiri, input, tombol X) sejajar vertikal */
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
            /* Biar input mengisi sisa ruang */
        }

        .sidebar-search input::placeholder {
            color: #9ca3af;
        }

        .sidebar-search .clear-btn {
            background: transparent;
            border: none;
            color: #9ca3af;
            padding: 0 14px 0 4px;
            /* Tambah sedikit padding kanan agar tidak mepet edge */
            cursor: pointer;
            font-size: 1rem;
            /* Perbesar sedikit icon x */
            display: flex;
            align-items: center;
            justify-content: center;
            height: 100%;
        }

        .sidebar-search .clear-btn:hover {
            color: #374151;
        }

        /* Content filter chips */
        .content-filter-row {
            padding: 0 16px 12px;
            display: flex;
            gap: 6px;
            overflow-x: auto;
            white-space: nowrap;
        }

        .content-filter-row::-webkit-scrollbar {
            display: none;
        }

        .content-chip {
            display: inline-flex;
            align-items: center;
            gap: 4px;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.78rem;
            font-weight: 500;
            border: 1.5px solid #e5e7eb;
            background: #fff;
            color: #6b7280;
            cursor: pointer;
            transition: all 0.15s;
            white-space: nowrap;
            user-select: none;
        }

        .content-chip:hover {
            border-color: #a5b4fc;
            color: #4338ca;
            background: #eef2ff;
        }

        .content-chip.active {
            border-color: #6366f1;
            background: #eef2ff;
            color: #4338ca;
            font-weight: 600;
        }

        body.dark-mode .content-chip {
            background: #2a2a3d;
            border-color: #3a3a50;
            color: #9ca3af;
        }

        body.dark-mode .content-chip.active {
            background: #312e81;
            border-color: #6366f1;
            color: #c7d2fe;
        }

        /* ── Status Tab Strip ── */
        .status-tab-strip {
            padding: 0 12px;
            border-bottom: 1px solid #f3f4f6;
            background: #fff;
        }

        body.dark-mode .status-tab-strip {
            background: #1e1e2d;
            border-color: #2a2a3d;
        }

        .status-tab-strip .nav-tabs {
            border: none;
            display: flex;
            width: 100%;
            flex-wrap: nowrap;
        }

        .status-tab-strip .nav-item {
            flex: 1;
            display: flex;
        }

        .status-tab-strip .nav-link {
            width: 100%;
            padding: 12px 2px;
            border: none;
            border-bottom: 3px solid transparent;
            border-radius: 0;
            font-size: 0.68rem;
            font-weight: 700;
            color: #6b7280;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 3px;
            cursor: pointer;
            transition: all 0.2s;
            white-space: nowrap;
            text-transform: uppercase;
            letter-spacing: 0;
        }

        .status-tab-strip .nav-link:hover {
            color: #374151;
            background: rgba(0, 0, 0, 0.02);
        }

        .status-tab-strip .nav-link.active {
            color: #4338ca;
            border-bottom-color: #6366f1;
            background: transparent;
        }

        body.dark-mode .status-tab-strip .nav-link.active {
            color: #818cf8;
            border-bottom-color: #818cf8;
        }

        .status-tab-strip .tab-count {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 18px;
            height: 18px;
            padding: 0 5px;
            border-radius: 6px;
            font-size: 0.68rem;
            font-weight: 800;
            background: #eef2ff;
            color: #6366f1;
            line-height: 1;
            transition: all 0.2s;
        }

        .status-tab-strip .nav-link.active .tab-count {
            background: #6366f1;
            color: #fff;
        }

        .status-tab-strip .nav-link.tab-queue.active {
            color: #dc2626;
            border-bottom-color: #ef4444;
        }

        .status-tab-strip .nav-link.tab-queue.active .tab-count {
            background: #ef4444;
            color: #fff;
        }

        /* ── Chat List Container ── */
        .chat-list-panel {
            background: #fff;
            border: 1px solid #eef0f5;
            border-radius: 12px;
            box-shadow: 0 1px 6px rgba(0, 0, 0, 0.04);
            overflow: hidden;
        }

        body.dark-mode .chat-list-panel {
            background: #1e1e2d;
            border-color: #2a2a3d;
        }

        /* ── Chat Item ── */
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

        .chat-item:last-child {
            border-bottom: none;
        }

        .chat-item:hover {
            background: #f9fafb;
        }

        .chat-item.is-selected {
            background: #eef2ff;
        }

        body.dark-mode .chat-item:hover {
            background: #25253a;
        }

        body.dark-mode .chat-item.is-selected {
            background: #1e1e4a;
        }

        body.dark-mode .chat-item {
            border-bottom-color: #2a2a3d;
        }

        /* Avatar */
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

        .ci-avatar-circle.queue-bg {
            background: linear-gradient(135deg, #ef4444, #f97316);
        }

        .ci-avatar-circle.active-bg {
            background: linear-gradient(135deg, #6366f1, #8b5cf6);
        }

        .ci-status-dot {
            position: absolute;
            bottom: 1px;
            right: 1px;
            width: 11px;
            height: 11px;
            border-radius: 50%;
            border: 2px solid #fff;
        }

        .ci-status-dot.online {
            background: #22c55e;
        }

        .ci-status-dot.offline {
            background: #9ca3af;
        }

        .ci-status-dot.queue-dot {
            background: #22c55e;
        }

        body.dark-mode .ci-status-dot {
            border-color: #1e1e2d;
        }

        /* Content */
        .ci-content {
            flex: 1;
            min-width: 0;
        }

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

        body.dark-mode .ci-name {
            color: #f0f0f0;
        }

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

        .ci-badge {
            display: inline-block;
            padding: 1px 7px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
            flex-shrink: 0;
        }

        .ci-badge.queue {
            background: #fef2f2;
            color: #b91c1c;
        }

        .ci-badge.active-mine {
            background: #eef2ff;
            color: #4338ca;
        }

        .ci-badge.active-other {
            background: #f0fdf4;
            color: #166534;
        }

        /* Empty state */
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

        .chat-empty-state p {
            font-size: 0.85rem;
            margin: 0;
        }

        /* Search result styles */
        .search-category {
            padding: 5px 12px 10px;
        }

        .search-category h6 {
            font-size: 0.8rem;
            font-weight: 700;
            color: #9ca3af;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            margin-bottom: 8px;
            padding-left: 4px;
        }

        .search-category .media {
            padding: 8px 10px;
            border-radius: 10px;
            transition: background 0.15s;
            text-decoration: none;
            align-items: center;
            margin-bottom: 2px;
        }

        .search-category .media:hover {
            background: #f3f4f6;
        }

        body.dark-mode .search-category .media:hover {
            background: #25253a;
        }

        .search-category .media-img-wrap {
            margin-right: 12px;
        }

        .search-category .avatar {
            width: 36px;
            height: 36px;
        }

        .search-category .avatar-title {
            font-size: 0.9rem;
            font-weight: 600;
        }

        .search-category .user-name {
            font-size: 0.9rem;
            font-weight: 600;
            color: #1f2937;
            margin-bottom: 2px;
            line-height: 1.2;
        }

        body.dark-mode .search-category .user-name {
            color: #f9fafb;
        }

        .search-category .user-last-chat {
            font-size: 0.75rem;
            color: #6b7280;
            line-height: 1.3;
        }

        .search-time-divider {
            font-size: 0.7rem;
            font-weight: 700;
            color: #9ca3af;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            margin: 12px 0 8px;
            padding-left: 14px;
        }

        .search-snippet {
            font-size: 0.8rem;
            color: #4b5563;
            line-height: 1.4;
            margin: 3px 0;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        body.dark-mode .search-snippet {
            color: #9ca3af;
        }

        .keyword-highlight {
            font-weight: 700;
            color: #4f46e5;
            background: transparent;
            padding: 0;
        }

        body.dark-mode .keyword-highlight {
            color: #818cf8;
            background: transparent;
        }

        .chat-search-panel {
            position: sticky;
            top: 0;
            z-index: 20;
            background: inherit;
        }

        .ci-badge.offline {
            background: #e5e7eb;
            color: #6b7280;
        }

        /* New style for offline badge */
    </style>
@endpush

@section('content')
    <div x-data="adminChat({{ $admin->id }}, {{ Js::from($pendingConversations) }}, {{ Js::from($activeConversations) }}, {{ Js::from($closedConversations) }})">
        <div class="row chat-window">
            <div class="chat-cont-left flex-column transition-all" x-show="!sidebarCollapsed"
                :class="{
                    'd-none': (selectedChat && window.innerWidth < 768),
                    'd-flex col-md-4 col-lg-5 col-xl-4': !sidebarCollapsed
                }">
                <!-- ═══════════ TOP PANEL (Header + Search + Content Filters) ═══════════ -->
                <div class="sidebar-top-panel mb-2 flex-shrink-0">

                    <!-- Header -->
                    <div class="sidebar-header">
                        <div>
                            <h6>Percakapan</h6>
                            <p class="subtitle"
                                x-text="isGlobalSearchMode ? (totalSearchResultCount + ' hasil ditemukan') : (filteredChats.length + ' percakapan aktif')">
                            </p>
                        </div>
                        <div class="sidebar-header-actions">
                            <button @click="sortBy = sortBy === 'recent' ? 'oldest' : 'recent'; fetchChats()"
                                :title="sortBy === 'recent' ? 'Urutkan: Terlama' : 'Urutkan: Terbaru'">
                                <i class="fe" :class="sortBy === 'recent' ? 'fe-arrow-down' : 'fe-arrow-up'"></i>
                            </button>
                            <button @click="fetchChats()" title="Refresh">
                                <i class="fe fe-refresh-cw"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Search -->
                    <div class="sidebar-search">
                        <div class="input-group">
                            <span class="input-group-text"><i class="fe fe-search" style="font-size:0.85rem;"></i></span>
                            <input type="text" x-model="searchQuery" @input.debounce.300ms="fetchChats()"
                                placeholder="Cari nama, kontak, atau pesan..." class="form-control">
                            <span class="clear-btn" x-show="searchQuery.length > 0 || hasActiveFilter"
                                @click="clearSearch()" title="Hapus">
                                <i class="fe fe-x"></i>
                            </span>
                        </div>
                    </div>

                    <!-- Content Filter Chips -->
                    <div class="content-filter-row">
                        <span class="content-chip" :class="filters.unreadOnly ? 'active' : ''"
                            @click="toggleUnreadFilter()">
                            Belum Dibaca
                        </span>
                        <span class="content-chip" :class="filters.messageType.includes('image') ? 'active' : ''"
                            @click="toggleFilter('image')">
                            Foto
                        </span>
                        <span class="content-chip" :class="filters.messageType.includes('video') ? 'active' : ''"
                            @click="toggleFilter('video')">
                            Video
                        </span>
                        <span class="content-chip" :class="filters.messageType.includes('file') ? 'active' : ''"
                            @click="toggleFilter('file')">
                            Dokumen
                        </span>
                        <span class="content-chip" :class="filters.messageType.includes('link') ? 'active' : ''"
                            @click="toggleFilter('link')">
                            Tautan
                        </span>
                        <span class="content-chip" :class="filters.messageType.includes('audio') ? 'active' : ''"
                            @click="toggleFilter('audio')">
                            Audio
                        </span>
                    </div>

                </div> <!-- /TOP PANEL -->

                <!-- ═══════════ CHAT LIST PANEL ═══════════ -->
                <div class="chat-list-panel flex-grow-1 d-flex flex-column" style="overflow: hidden;">

                    <template x-if="!isGlobalSearchMode">
                        <div class="d-flex flex-column h-100" style="overflow: hidden;">

                            <!-- Status Tab Strip -->
                            <div class="status-tab-strip flex-shrink-0">
                                <ul class="nav nav-tabs">
                                    <li class="nav-item">
                                        <span class="nav-link" :class="statusFilter === 'all' ? 'active' : ''"
                                            @click="statusFilter = 'all'" style="cursor:pointer;">
                                            Semua
                                            <span class="tab-count"
                                                x-text="filteredChats.filter(c => c.customer.is_online).length"></span>
                                        </span>
                                    </li>
                                    <li class="nav-item">
                                        <span class="nav-link tab-queue"
                                            :class="statusFilter === 'queue' ? 'active' : ''"
                                            @click="statusFilter = 'queue'" style="cursor:pointer;">
                                            Antrean
                                            <span class="tab-count"
                                                x-text="filteredChats.filter(c => ['pending','queued'].includes(c.status) && c.customer.is_online).length"></span>
                                        </span>
                                    </li>
                                    <li class="nav-item">
                                        <span class="nav-link" :class="statusFilter === 'active' ? 'active' : ''"
                                            @click="statusFilter = 'active'" style="cursor:pointer;">
                                            Online
                                            <span class="tab-count"
                                                x-text="filteredChats.filter(c => c.status === 'active' && c.customer.is_online).length"></span>
                                        </span>
                                    </li>
                                    <li class="nav-item">
                                        <span class="nav-link" :class="statusFilter === 'offline' ? 'active' : ''"
                                            @click="statusFilter = 'offline'" style="cursor:pointer;">
                                            Offline
                                            <span class="tab-count"
                                                x-text="filteredChats.filter(c => !c.customer.is_online || c.status === 'closed').length"></span>
                                        </span>
                                    </li>
                                </ul>
                            </div>

                            <!-- Unified Chat List (scrollable) -->
                            <div style="overflow-y: auto; flex: 1;">
                                <template
                                    x-for="chat in filteredChats.filter(c => {
                                if (statusFilter === 'offline') return !c.customer.is_online || c.status === 'closed';
                                // For other filters, only show ONLINE users
                                if (!c.customer.is_online && c.status !== 'closed') return false; 
                                if (statusFilter === 'all') return c.customer.is_online;
                                if (statusFilter === 'queue') return ['pending','queued'].includes(c.status) && c.customer.is_online;
                                if (statusFilter === 'active') return c.status === 'active' && c.customer.is_online;
                                return true;
                            })"
                                    :key="chat.id">
                                    <a href="javascript:void(0);" @click="selectChat(chat)" class="chat-item"
                                        :class="selectedChat && selectedChat.id === chat.id ? 'is-selected' : ''"
                                        style="text-decoration: none;">

                                        <!-- Avatar -->
                                        <div class="ci-avatar">
                                            <div class="ci-avatar-circle"
                                                :class="['pending', 'queued'].includes(chat.status) ? 'queue-bg' : 'active-bg'">
                                                <span x-text="getInitial(chat.customer.name)"></span>
                                            </div>
                                            <span class="ci-status-dot"
                                                :class="['pending', 'queued'].includes(chat.status) ? 'queue-dot' : (chat
                                                    .customer.is_online ? 'online' : 'offline')"></span>
                                        </div>

                                        <!-- Content -->
                                        <div class="ci-content">
                                            <div class="ci-row1">
                                                <span class="ci-name" x-html="highlightText(chat.customer.name)"></span>
                                                <span class="ci-time" x-text="formatShortDateTime(chat.created_at)"></span>
                                            </div>
                                            <div class="ci-row2">
                                                <span class="ci-badge"
                                                    :class="{
                                                        'queue': ['pending', 'queued'].includes(chat.status),
                                                        'active-mine': chat.status === 'active' && chat.admin_id ===
                                                            adminId && chat.customer.is_online,
                                                        'active-other': chat.status === 'active' && chat.admin_id !==
                                                            adminId && chat.customer.is_online,
                                                        'offline': chat.status === 'active' && !chat.customer
                                                            .is_online
                                                    }"
                                                    x-text="
                                                    chat.status === 'queued' ? '🕐 Antrean #' + chat.queue_position :
                                                    (chat.status === 'pending' ? '🔔 Permintaan Baru' :
                                                    (chat.status === 'active' && !chat.customer.is_online ? '📴 Offline' :
                                                    (chat.admin_id === adminId ? '✦ Anda membantu' : '↗ Oleh ' + (chat.admin ? chat.admin.username : 'agen'))))
                                                "></span>
                                            </div>
                                        </div>
                                    </a>
                                </template>

                                <!-- Empty state -->
                                <div x-show="filteredChats.filter(c => statusFilter === 'all' ? true : (statusFilter === 'queue' ? ['pending','queued'].includes(c.status) : (statusFilter === 'active' ? c.status === 'active' : (statusFilter === 'offline' ? !c.customer.is_online : true)))).length === 0"
                                    class="chat-empty-state">
                                    <i class="fe fe-message-circle"></i>
                                    <p
                                        x-text="statusFilter === 'queue' ? 'Tidak ada antrean saat ini.' : (statusFilter === 'active' ? 'Tidak ada chat aktif.' : (statusFilter === 'offline' ? 'Tidak ada user offline.' : 'Belum ada percakapan.'))">
                                    </p>
                                </div>
                            </div>

                        </div>
                    </template>

                    <template x-if="isGlobalSearchMode">
                        <div>
                            <div class="search-category">
                                <h6 class="mb-2">Kontak</h6>
                                <template x-for="contact in searchResults.contacts" :key="`contact-${contact.id}`">
                                    <a href="javascript:void(0);" class="media d-flex"
                                        @click="openContactResult(contact)">
                                        <div class="media-img-wrap flex-shrink-0">
                                            <div class="avatar"
                                                :class="contact.is_online ? 'avatar-online' : 'avatar-away'">
                                                <div class="avatar-title rounded-circle bg-info text-white">
                                                    <span x-text="getInitial(contact.name)"></span>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="media-body flex-grow-1">
                                            <div class="user-name" x-html="highlightText(contact.name)"></div>
                                            <div class="user-last-chat" x-html="highlightText(contact.contact || '')">
                                            </div>
                                        </div>
                                    </a>
                                </template>
                                <div x-show="searchResults.contacts.length === 0" class="text-muted small">Tidak ada
                                    kontak cocok.</div>
                            </div>

                            <div class="search-category"
                                x-show="searchResults.messages && searchResults.messages.length > 0">
                                <h6 class="mb-2">Pesan</h6>
                                <template x-for="timeGroup in searchResults.messages"
                                    :key="`time-${timeGroup.time_group}`">
                                    <div>
                                        <div class="search-time-divider" x-text="timeGroup.time_group_label"></div>
                                        <template x-for="message in timeGroup.messages" :key="`message-${message.id}`">
                                            <a href="javascript:void(0);" class="media d-flex"
                                                @click="openMessageResult(message)">
                                                <div class="media-img-wrap flex-shrink-0">
                                                    <div class="avatar avatar-away">
                                                        <div class="avatar-title rounded-circle bg-secondary text-white">
                                                            <span x-text="getInitial(message.customer_name)"></span>
                                                        </div>
                                                    </div>
                                                </div>
                                                <div class="media-body flex-grow-1">
                                                    <div class="user-name"
                                                        x-html="highlightText(message.customer_name || '')"></div>
                                                    <div class="search-snippet"
                                                        x-html="highlightText(message.snippet || '')"></div>
                                                    <div class="user-last-chat text-muted" style="font-size: 0.75em;"
                                                        x-text="formatShortDateTime(message.created_at)"></div>
                                                </div>
                                            </a>
                                        </template>
                                    </div>
                                </template>
                            </div>

                            <div x-show="totalSearchResultCount === 0" class="text-center p-3 text-muted small"
                                style="margin-top: 20px;">
                                Tidak ada hasil pencarian.
                            </div>
                        </div>
                    </template>
                </div>
            </div>

            <div class="chat-cont-right transition-all flex-grow-1"
                :class="{
                    'col-md-8 col-lg-7 col-xl-8': !sidebarCollapsed,
                    'col-12': sidebarCollapsed,
                    'd-none d-md-flex': !selectedChat && !sidebarCollapsed,
                    'd-flex': selectedChat || sidebarCollapsed
                }">
                <div class="card mb-0 w-100 h-100" x-show="selectedChat" x-cloak>
                    <div class="h-100 d-flex flex-column">
                        <div class="card-header msg_head px-3 py-2">
                            <div class="d-flex bd-highlight align-items-center w-100">
                                <a href="javascript:void(0)" class="back-user-list me-3 d-lg-none"
                                    :class="darkMode ? 'text-white' : 'text-dark'" @click="selectedChat = null">
                                    <i class="fas fa-arrow-left fa-lg"></i>
                                </a>
                                <a href="javascript:void(0)" class="me-3 d-none d-lg-block text-secondary"
                                    @click="sidebarCollapsed = !sidebarCollapsed" title="Toggle Sidebar">
                                    <i class="fas fa-bars fa-lg"></i>
                                </a>
                                <div class="img_cont flex-shrink-0">
                                    <div class="avatar avatar-sm">
                                        <div class="avatar-title rounded-circle bg-primary text-white">
                                            <span
                                                x-text="selectedChat ? selectedChat.customer.name.charAt(0).toUpperCase() : ''"></span>
                                        </div>
                                    </div>
                                </div>
                                <div class="user_info ms-2 flex-grow-1 overflow-hidden">
                                    <span class="text-truncate d-block"
                                        x-text="selectedChat ? selectedChat.customer.name : ''"></span>
                                    <p class="mb-0 small"
                                        :class="selectedChat && selectedChat.customer.is_online ? 'text-success' : 'text-muted'"
                                        x-text="selectedChat && selectedChat.customer.is_online ? 'Online' : 'Offline'">
                                    </p>
                                </div>
                                <div class="chat-options ms-auto flex-shrink-0">
                                    <ul class="d-flex align-items-center list-unstyled mb-0">
                                        <template
                                            x-if="selectedChat && ['pending', 'queued'].includes(selectedChat.status)">
                                            <li class="ms-2">
                                                <button class="btn btn-sm btn-primary px-3"
                                                    @click="claimChat(selectedChat.id)" :disabled="isClaiming">
                                                    <span x-text="isClaiming ? 'Mengambil...' : 'Klaim Chat'"></span>
                                                </button>
                                            </li>
                                        </template>
                                        <template
                                            x-if="selectedChat && selectedChat.status === 'active' && selectedChat.admin_id === adminId">
                                            <li class="d-flex ms-2">
                                                <button class="btn btn-sm btn-outline-info me-1"
                                                    @click="showHandoverModal = true" title="Oper Chat"><i
                                                        class="fe fe-repeat"></i></button>
                                                <button class="btn btn-sm btn-outline-success me-1"
                                                    @click="confirmCloseChat()" :disabled="isSubmitting"
                                                    title="Selesaikan"><i class="fe fe-check"></i></button>
                                                <button class="btn btn-sm btn-outline-danger"
                                                    @click="blockUser(selectedChat.id)" title="Blokir"><i
                                                        class="fe fe-slash"></i></button>
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
                                class="w-100 h-100" style="border: none; display: block;"
                                @load="iframeLoaded = true"></iframe>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modals -->
        <div class="modal fade" :class="showHandoverModal ? 'show d-block' : ''" tabindex="-1"
            x-show="showHandoverModal" x-cloak>
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
                                @foreach ($otherAdmins as $other)
                                    <option value="{{ $other->id }}">{{ $other->username }}
                                        ({{ ucfirst($other->status) }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Catatan Internal (Opsional)</label>
                            <textarea x-model="handoverNote" class="form-control" rows="3" placeholder="Pesan untuk agen selanjutnya..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary"
                            @click="showHandoverModal = false">Batal</button>
                        <button type="button" class="btn btn-primary" @click="handoverChat()"
                            :disabled="!handoverToAdminId || isSubmitting">Oper</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('adminChat', (adminId, initPending, initActive, initClosed) => ({
                adminId: adminId,
                chats: [...initPending, ...initActive, ...initClosed],
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
                searchResults: {
                    contacts: [],
                    groups: [],
                    messages: [],
                },
                statusFilter: 'all',
                filters: {
                    messageType: [],
                    unreadOnly: false,
                },

                clearSearch() {
                    this.searchQuery = '';
                    this.filters.messageType = [];
                    this.filters.unreadOnly = false;
                    this.statusFilter = 'all';
                    this.fetchChats();
                },

                toggleFilter(type) {
                    const index = this.filters.messageType.indexOf(type);
                    if (index > -1) {
                        this.filters.messageType.splice(index, 1);
                    } else {
                        this.filters.messageType.push(type);
                    }
                    this.fetchChats();
                },

                toggleUnreadFilter() {
                    this.filters.unreadOnly = !this.filters.unreadOnly;
                    this.fetchChats();
                },

                emptySearchResults() {
                    return {
                        contacts: [],
                        groups: [],
                        messages: [],
                    };
                },

                escapeHtml(text) {
                    return String(text ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                escapeRegex(str) {
                    return str.replace(/[.*+?^${}()|[\]\\]/g, '\\$&');
                },

                highlightText(text) {
                    const safeText = this.escapeHtml(text);
                    const keyword = this.searchQuery.trim();

                    if (!keyword) {
                        return safeText;
                    }

                    const regex = new RegExp(`(${this.escapeRegex(keyword)})`, 'gi');
                    return safeText.replace(regex, '<span class="keyword-highlight">$1</span>');
                },

                getInitial(name) {
                    const normalized = String(name ?? '').trim();
                    return normalized ? normalized.charAt(0).toUpperCase() : '?';
                },

                async openConversationResult(conversationId) {
                    let chat = this.chats.find(c => c.id === conversationId);

                    if (!chat) {
                        await this.fetchChats();
                        chat = this.chats.find(c => c.id === conversationId);
                    }

                    if (chat) {
                        this.selectChat(chat);
                    }
                },

                async openContactResult(contact) {
                    let chat = this.chats.find(c => c.customer && c.customer.id === contact.id);

                    if (!chat) {
                        await this.fetchChats();
                        chat = this.chats.find(c => c.customer && c.customer.id === contact.id);
                    }

                    if (chat) {
                        this.selectChat(chat);
                    }
                },

                async openMessageResult(message) {
                    await this.openConversationResult(message.conversation_id);
                },

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
                            this.notificationSound = new Audio(
                                '{{ asset('sounds/notification.mp3') }}');
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
                                    console.log('📝 Conversation ID:', e.conversation_id,
                                        'Status:', e.status);

                                    // Update status offline secara reaktif dari data broadcast
                                    if (e.customer) {
                                        // 1. Update di chat yang terpilih (Header)
                                        if (this.selectedChat && this.selectedChat
                                            .customer && this.selectedChat.customer.id === e
                                            .customer.id) {
                                            this.selectedChat.customer.is_online = e
                                                .customer.is_online;
                                            // Paksa refresh teks status jika Alpine tidak mendeteksi perubahan properti dalam
                                            this.$nextTick(() => {
                                                this.selectedChat = {
                                                    ...this.selectedChat
                                                };
                                            });
                                        }

                                        // 2. Update di daftar chat di kiri
                                        this.chats.forEach(c => {
                                            if (c.customer && c.customer.id === e
                                                .customer.id) {
                                                c.customer.is_online = e.customer
                                                    .is_online;
                                            }
                                        });
                                    }

                                    if (this.selectedChat && this.selectedChat.id === e
                                        .conversation_id && e.status === 'closed') {
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
                        const audio = new Audio('{{ asset('sounds/notification.mp3') }}');
                        audio.play().catch(e => {
                            console.log(
                                "Audio play blocked. Click anywhere on the page first to unlock sound."
                                );
                        });
                    }
                },

                async fetchChats() {
                    console.log('🔄 Fetching chats...');
                    try {
                        const params = new URLSearchParams({
                            ajax: '1',
                            sort: this.sortBy,
                        });

                        const keyword = this.searchQuery.trim();
                        if (keyword) {
                            params.set('search', keyword);
                        }

                        if (this.filters.messageType.length > 0) {
                            params.set('quick_filters', this.filters.messageType.join(','));
                        }

                        if (this.filters.unreadOnly) {
                            params.set('unread_only', '1');
                        }

                        const res = await fetch(`/admin/chat?${params.toString()}`);
                        if (!res.ok) {
                            throw new Error('Gagal mengambil data pencarian chat');
                        }

                        const data = await res.json();
                        this.chats = [...(data.pending || []), ...(data.active || []), ...(data
                            .closed || [])];
                        this.searchResults = data.search_results || this.emptySearchResults();

                        if (this.selectedChat) {
                            const updatedSelected = this.chats.find(chat => chat.id === this
                                .selectedChat.id);
                            if (updatedSelected) {
                                this.selectedChat = {
                                    ...this.selectedChat,
                                    ...updatedSelected,
                                };
                            }
                        }
                    } catch (e) {
                        console.error('Failed to fetch chats', e);
                    }
                },

                get hasActiveFilter() {
                    return this.filters.messageType.length > 0 || this.filters.unreadOnly;
                },

                get isGlobalSearchMode() {
                    return this.searchQuery.trim().length > 0 || this.hasActiveFilter;
                },

                get totalSearchResultCount() {
                    const messageCount = (this.searchResults.messages || []).reduce((total,
                        group) => {
                            return total + (group.messages ? group.messages.length : 0);
                        }, 0);

                    return (this.searchResults.contacts || []).length +
                        (this.searchResults.groups || []).length +
                        messageCount;
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
                        if (!res.ok) {
                            const errData = await res.json().catch(() => ({}));
                            throw new Error(errData.error || errData.message ||
                                `Gagal mengambil chat (HTTP ${res.status})`);
                        }

                        // Update lists without reload
                        await this.fetchChats();

                        // Switch to active tab so the admin can see their new chat
                        this.statusFilter = 'active';

                        // If we just claimed it, find it in the new list and select it
                        const claimed = this.chats.find(c => c.id === conversationId);
                        if (claimed) {
                            this.selectChat(claimed);
                        }

                        Toast.fire({
                            icon: 'success',
                            title: 'Chat berhasil diambil'
                        });
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
                        const res = await fetch(
                        `/admin/conversation/${this.selectedChat.id}/close`, {
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
                        const res = await fetch(
                            `/admin/conversation/${this.selectedChat.id}/handover`, {
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
                                const res = await fetch(
                                    `/admin/conversation/${conversationId}/block`, {
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
