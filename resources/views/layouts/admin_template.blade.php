<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <script>
        window.broadcastingAuth = "{{ url('/broadcasting/auth') }}";
    </script>
    <title>@yield('title', 'Admin Dashboard') | LiveChat BEST</title>

    <!-- Favicons -->
    <link rel="shortcut icon" href="{{ asset('images/best-logo-1.png') }}">

    <!-- PWA Manifest -->
    <link rel="manifest" href="{{ asset('manifest-admin.json') }}">
    <meta name="theme-color" content="#ffffff">
    <link rel="apple-touch-icon" href="{{ asset('images/best-logo-1.png') }}">

    <!-- Vite Assets (for Laravel Echo/Reverb) -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- Select 2 -->
    <link rel="stylesheet" href="{{ asset('admin/assets/css/select2.min.css') }}">

    <!-- Bootstrap CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/bootstrap/css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/bootstrap-tagsinput/css/bootstrap-tagsinput.css') }}">

    <!-- Fontawesome CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/fontawesome/css/fontawesome.min.css') }}">
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/fontawesome/css/all.min.css') }}">

    <!-- Datatable CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets/css/dataTables.bootstrap4.min.css') }}">

    <!-- Feather CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets/plugins/feather/feather.css') }}">

    <!-- Main CSS -->
    <link rel="stylesheet" href="{{ asset('admin/assets/css/admin.css') }}">

    <!-- Daterangepicker CSS -->
    <link rel="stylesheet" type="text/css" href="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.css" />

    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

    <style>
        [x-cloak] {
            display: none !important;
        }

        /* Fix Global Header Mobile */
        @media (max-width: 991.98px) {
            .header {
                display: flex !important;
                align-items: center !important;
                justify-content: space-between !important;
                padding: 0 15px !important;
                height: 60px !important;
                background-color: #fff !important;
            }

            body.dark-mode .header {
                background-color: #1e1e1e !important;
            }

            .header-left {
                position: static !important;
                width: auto !important;
                flex: 0 0 auto !important;
                display: flex !important;
                align-items: center !important;
                padding: 0 !important;
                float: none !important;
            }

            .header-left .logo.logo-small {
                display: block !important;
            }

            .header-left .logo img {
                max-height: 35px !important;
                width: auto !important;
            }

            .header-split {
                display: flex !important;
                flex: 1 !important;
                justify-content: flex-end !important;
                align-items: center !important;
                margin: 0 !important;
                padding: 0 !important;
                float: none !important;
            }

            .user-menu {
                display: flex !important;
                align-items: center !important;
                list-style: none !important;
                padding: 0 !important;
                margin: 0 !important;
                float: none !important;
            }

            .user-menu>li {
                margin-left: 12px !important;
                display: flex !important;
                align-items: center !important;
                float: none !important;
            }

            .mobile_btn {
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                width: 36px !important;
                height: 36px !important;
                background: transparent !important;
                border-radius: 8px !important;
                color: #333 !important;
                order: 10 !important;
                /* Move to right */
                margin-left: 10px !important;
                z-index: 1060 !important;
                position: static !important;
                padding: 0 !important;
                float: none !important;
            }

            body.dark-mode .mobile_btn {
                color: #fff !important;
            }

            /* Custom Burger Icon Stylings */
            .burger-icon {
                width: 22px;
                height: 16px;
                position: relative;
                display: flex;
                flex-direction: column;
                justify-content: space-between;
                cursor: pointer;
            }

            .burger-icon span {
                display: block;
                height: 2px;
                width: 100%;
                background-color: currentColor;
                border-radius: 2px;
                transition: all 0.3s cubic-bezier(0.645, 0.045, 0.355, 1);
            }

            /* Animation */
            .slide-nav .burger-icon span:nth-child(1) {
                transform: translateY(7px) rotate(45deg);
            }

            .slide-nav .burger-icon span:nth-child(2) {
                opacity: 0;
                transform: translateX(10px);
            }

            .slide-nav .burger-icon span:nth-child(3) {
                transform: translateY(-7px) rotate(-45deg);
            }

            .sidebar {
                margin-left: -260px;
                transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
                position: fixed;
                visibility: hidden;
                z-index: 11000 !important;
                width: 260px !important;
                top: 0 !important;
                bottom: 0 !important;
                height: 100% !important;
            }

            .sidebar-inner {
                height: 100% !important;
                display: flex;
                flex-direction: column;
            }

            #sidebar-menu {
                flex: 1;
                overflow-y: auto;
            }

            .slide-nav .sidebar {
                margin-left: 0;
                visibility: visible;
            }

            .sidebar-overlay {
                display: none;
                background-color: rgba(0, 0, 0, 0.6);
                height: 100%;
                left: 0;
                position: fixed;
                top: 0;
                width: 100%;
                z-index: 10900;
                /* Di bawah sidebar tapi di atas chat */
                opacity: 0;
                transition: opacity 0.4s ease;
            }

            .sidebar-overlay.opened {
                display: block;
                opacity: 1;
            }

            /* Hide unnecessary elements on mobile */
            .search-bar {
                display: none !important;
            }

            .user-content {
                display: none !important;
            }

            .nav-item .btn-light {
                background: #f8f9fa !important;
                border-radius: 8px !important;
                width: 36px !important;
                height: 36px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
                padding: 0 !important;
                border: 1px solid #eee !important;
            }

            body.dark-mode .nav-item .btn-light {
                background: #252525 !important;
                border-color: #444 !important;
                color: #fff !important;
            }

            .user-img {
                width: 36px !important;
                height: 36px !important;
                margin-right: 0 !important;
                background: transparent !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .user-img .w-10.h-10 {
                width: 36px !important;
                height: 36px !important;
                font-size: 14px !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }

            .animate-circle {
                display: none !important;
            }

            .user-menu>li.me-3 {
                margin-right: 0 !important;
            }
        }

        /* Sidebar UI Overrides for Desktop */
        @media (min-width: 992px) {
            body:not(.mini-sidebar) .sidebar {
                width: 230px !important;
            }

            body:not(.mini-sidebar) .page-wrapper {
                margin-left: 230px !important;
            }

            body:not(.mini-sidebar) .header-left {
                width: 230px !important;
            }

            .mini-sidebar .sidebar {
                width: 60px !important;
            }

            .mini-sidebar .page-wrapper {
                margin-left: 60px !important;
            }

            .mini-sidebar .header-left {
                width: 60px !important;
            }
        }

        .sidebar {
            position: fixed;
            top: 0;
            bottom: 0;
            left: 0;
            width: 230px;
            z-index: 1001;
            transition: all 0.2s ease-in-out;
            overflow-y: auto !important;
            height: 100vh;
            /* Hide scrollbar for Chrome, Safari and Opera */
            scrollbar-width: none;
            /* Firefox */
            -ms-overflow-style: none;
            /* IE and Edge */
        }

        .sidebar::-webkit-scrollbar {
            display: none;
            /* Chrome, Safari and Opera */
        }

        .sidebar-logo {
            display: flex !important;
            justify-content: center;
            align-items: center;
            padding: 20px 0 !important;
            width: 100%;
        }

        .sidebar-logo img.logo {
            max-height: 60px;
            width: auto;
        }

        .sidebar-menu ul {
            padding-bottom: 120px !important;
            /* Bertambah agar benar-benar aman */
        }

        .sidebar-menu ul li a {
            display: flex;
            align-items: center;
        }

        .sidebar-menu ul li a i {
            font-size: 18px;
            margin-right: 12px;
            width: 24px;
            text-align: center;
        }

        /* Dark Mode Overrides */
        body.dark-mode {
            background-color: #121212;
            color: #e0e0e0;
        }

        body.dark-mode .main-wrapper,
        body.dark-mode .page-wrapper,
        body.dark-mode .content {
            background-color: #121212;
        }

        body.dark-mode .header {
            background-color: #1e1e1e;
            border-bottom-color: #333;
        }

        body.dark-mode .sidebar {
            background-color: #1e1e1e;
            border-right-color: #333;
        }

        body.dark-mode .card {
            background-color: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .card-header {
            background-color: #252525;
            border-bottom-color: #333;
        }

        body.dark-mode .table,
        body.dark-mode .table td,
        body.dark-mode .table th {
            color: #e0e0e0;
            border-color: #333;
        }

        body.dark-mode .table-hover tbody tr:hover {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.05);
        }

        body.dark-mode .form-control,
        body.dark-mode .select2-container--default .select2-selection--single {
            background-color: #252525;
            border-color: #444;
            color: #e0e0e0;
        }

        body.dark-mode .form-control:focus {
            background-color: #2a2a2a;
            color: #fff;
        }

        body.dark-mode .sidebar-menu ul li a {
            color: #a0a0a0;
        }

        body.dark-mode .sidebar-menu ul li a:hover,
        body.dark-mode .sidebar-menu ul li.active a {
            color: #fff;
            background-color: rgba(255, 255, 255, 0.1);
        }

        body.dark-mode .page-title,
        body.dark-mode .card-title {
            color: #fff;
        }

        body.dark-mode .text-muted {
            color: #888 !important;
        }

        body.dark-mode .modal-content {
            background-color: #1e1e1e;
            color: #e0e0e0;
            border-color: #333;
        }

        body.dark-mode .modal-header,
        body.dark-mode .modal-footer {
            border-color: #333;
        }

        body.dark-mode .close-btn,
        body.dark-mode .btn-close {
            filter: invert(1) grayscale(100%) brightness(200%);
        }
    </style>

    @stack('styles')
</head>

<body x-data="{ darkMode: localStorage.getItem('darkMode') === 'true' }" :class="{ 'dark-mode': darkMode }"
    x-init="$watch('darkMode', val => localStorage.setItem('darkMode', val))">
    <div class="main-wrapper">

        <!-- Header -->
        <div class="header">
            <div class="header-left">
                <a href="{{ route('admin.dashboard') }}" class="logo">
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" width="30" height="30">
                </a>
                <a href="{{ route('admin.dashboard') }}" class=" logo-small">
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" width="30" height="30">
                </a>
            </div>
            <a class="mobile_btn" id="mobile_btn" href="javascript:void(0);">
                <div class="burger-icon">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>
            </a>
            <div class="header-split">
                <div class="page-headers">
                    <div class="search-bar">
                        <span><i class="fe fe-search"></i></span>
                        <input type="text" placeholder="Search" class="form-control">
                    </div>
                </div>
                <ul class="nav user-menu">

                    <!-- Dark Mode Toggle -->
                    <li class="nav-item d-flex align-items-center me-3">
                        <button class="btn btn-sm btn-light border" @click="darkMode = !darkMode"
                            title="Toggle Dark Mode">
                            <i class="fe" :class="darkMode ? 'fe-sun text-warning' : 'fe-moon text-dark'"></i>
                        </button>
                    </li>

                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                            <span class="user-img">
                                <div
                                    class="w-10 h-10 rounded-circle bg-primary d-flex align-items-center justify-content-center text-white font-weight-bold">
                                    {{ strtoupper(substr(auth('admin')->user()->username, 0, 1)) }}
                                </div>
                                <span class="animate-circle"></span>
                            </span>
                            <span class="user-content">
                                <span class="user-name">{{ auth('admin')->user()->username }}</span>
                                <span class="user-details">Administrator</span>
                            </span>
                        </a>
                        <div class="dropdown-menu menu-drop-user">
                            <div class="profilemenu ">
                                <div class="user-detials">
                                    <a href="javascript:void(0);">
                                        <span class="profile-content">
                                            <span>{{ auth('admin')->user()->username }}</span>
                                            <span>{{ auth('admin')->user()->email }}</span>
                                        </span>
                                    </a>
                                </div>
                                <div class="subscription-logout">
                                    <form method="POST" action="{{ route('admin.logout') }}">
                                        @csrf
                                        <a href="javascript:void(0);" onclick="this.closest('form').submit();">Log
                                            Out</a>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </li>
                    <!-- /User Menu -->
                </ul>
            </div>

        </div>
        <!-- /Header -->

        <!-- Sidebar -->
        <div class="sidebar" id="sidebar">
            <div class="sidebar-header">
                <div class="sidebar-logo">
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ asset('images/best-logo-1.png') }}" class="img-fluid logo" alt="Logo BEST">
                    </a>
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ asset('images/best-logo-1.png') }}" class="img-fluid logo-small" alt="Logo BEST">
                    </a>
                </div>
            </div>

            <div class="sidebar-inner slimscroll">
                <div id="sidebar-menu" class="sidebar-menu">
                    <ul>
                        <li class="menu-title m-0">
                            <h6>Home</h6>
                        </li>
                        <li class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}">
                            <a href="{{ route('admin.dashboard') }}"><i class="fe fe-home"></i>
                                <span>Dashboard</span></a>
                        </li>

                        <li class="menu-title">
                            <h6>Communication</h6>
                        </li>
                        @if(auth('admin')->user()->hasPermission('view_chat'))
                            <li class="{{ request()->routeIs('admin.chat') ? 'active' : '' }}">
                                <a href="{{ route('admin.chat') }}"><i class="fe fe-message-square"></i>
                                    <span>Chat</span></a>
                            </li>
                            <li class="{{ request()->routeIs('admin.internal-chat.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.internal-chat.index') }}"><i class="fe fe-send"></i>
                                    <span>Chat Internal</span></a>
                            </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('view_history'))
                            <li class="{{ request()->routeIs('admin.history.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.history.index') }}"><i class="fe fe-clock"></i> <span>Riwayat
                                        Arsip</span></a>
                            </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('manage_quick_replies'))
                            <li class="{{ request()->routeIs('admin.quick-replies.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.quick-replies.index') }}"><i class="fe fe-zap"></i> <span>Balasan
                                        Cepat</span></a>
                            </li>
                            <li class="{{ request()->routeIs('admin.bot-menus.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.bot-menus.index') }}"><i class="fe fe-list"></i> <span>Alur Chat</span></a>
                            </li>
                        @endif

                        <li class="menu-title">
                            <h6>Management</h6>
                        </li>
                        @if(auth('admin')->user()->hasPermission('manage_customers'))
                            <li class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.customers.index') }}"><i class="fe fe-users"></i> <span>Data
                                        Pelanggan</span></a>
                            </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('manage_roles'))
                            <li class="{{ request()->routeIs('admin.admins.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.admins.index') }}"><i class="fe fe-shield"></i> <span>Hak
                                        Akses</span></a>
                            </li>
                            <li class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.roles.index') }}"><i class="fe fe-list"></i> <span>Daftar
                                        Role</span></a>
                            </li>
                        @endif

                        @if(auth('admin')->user()->is_superadmin)
                            <li class="{{ request()->routeIs('admin.settings.*') ? 'active' : '' }}">
                                <a href="{{ route('admin.settings.index') }}"><i class="fe fe-settings"></i>
                                    <span>Pengaturan</span></a>
                            </li>
                        @endif

                        <li class="menu-title">
                            <h6>Account</h6>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}" id="logout-form-sidebar">
                                @csrf
                                <a href="javascript:void(0);"
                                    onclick="document.getElementById('logout-form-sidebar').submit();"><i
                                        class="fe fe-log-out text-danger"></i> <span
                                        class="text-danger">Logout</span></a>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
        <!-- /Sidebar -->

        <div class="page-wrapper">
            <div class="content">
                @yield('content')
            </div>
        </div>
    </div>

    <!-- jQuery -->
    <script src="{{ asset('admin/assets/js/jquery-3.6.0.min.js') }}"></script>

    <!-- Select 2 JS-->
    <script src="{{ asset('admin/assets/js/select2.min.js') }}"></script>

    <!-- Bootstrap Core JS -->
    <script src="{{ asset('admin/assets/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>

    <!-- Feather Icon JS -->
    <script src="{{ asset('admin/assets/js/feather.min.js') }}"></script>

    <!-- Datatable JS -->
    <script src="{{ asset('admin/assets/js/jquery.dataTables.min.js') }}"></script>
    <script src="{{ asset('admin/assets/js/dataTables.bootstrap4.min.js') }}"></script>

    <!-- Slimscroll JS -->
    <script src="{{ asset('admin/assets/plugins/slimscroll/jquery.slimscroll.min.js') }}"></script>

    <!-- Sweetalert 2 -->
    <script src="{{ asset('admin/assets/plugins/sweetalert/sweetalert2.all.min.js') }}"></script>
    <script src="{{ asset('admin/assets/plugins/sweetalert/sweetalerts.min.js') }}"></script>

    <!-- Custom JS -->
    <script src="{{ asset('admin/assets/js/admin.js') }}"></script>

    <!-- Daterangepicker JS -->
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/moment/min/moment.min.js"></script>
    <script type="text/javascript" src="https://cdn.jsdelivr.net/npm/daterangepicker/daterangepicker.min.js"></script>

    <script>
        // GLOBAL FIX UNTUK SIDEBAR DI MOBILE
        document.addEventListener('DOMContentLoaded', function () {
            const mobileBtn = document.getElementById('mobile_btn');
            const mainWrapper = document.querySelector('.main-wrapper');

            // Tambahkan overlay jika belum ada
            let overlay = document.querySelector('.sidebar-overlay');
            if (!overlay) {
                overlay = document.createElement('div');
                overlay.className = 'sidebar-overlay';
                document.body.appendChild(overlay);
            }

            const closeSidebar = () => {
                mainWrapper.classList.remove('slide-nav');
                overlay.classList.remove('opened');
                document.documentElement.classList.remove('menu-opened');
            };

            if (mobileBtn) {
                mobileBtn.addEventListener('click', function (e) {
                    e.preventDefault();
                    e.stopPropagation();
                    const isOpen = mainWrapper.classList.contains('slide-nav');

                    if (isOpen) {
                        closeSidebar();
                    } else {
                        mainWrapper.classList.add('slide-nav');
                        overlay.classList.add('opened');
                        document.documentElement.classList.add('menu-opened');
                    }
                });
            }

            // Klik overlay untuk menutup sidebar
            overlay.addEventListener('click', closeSidebar);

            // Tutup sidebar saat menu diklik
            const sidebarLinks = document.querySelectorAll('.sidebar-menu a');
            sidebarLinks.forEach(link => {
                link.addEventListener('click', () => {
                    if (window.innerWidth < 992) closeSidebar();
                });
            });
        });

        // Global Toast Notification Setup
        const Toast = Swal.mixin({
            toast: true,
            position: 'top-end',
            showConfirmButton: false,
            timer: 3000,
            timerProgressBar: true,
            didOpen: (toast) => {
                toast.addEventListener('mouseenter', Swal.stopTimer)
                toast.addEventListener('mouseleave', Swal.resumeTimer)
            }
        });

        // Trigger Toast for session flash messages
        @if(Session::has('success'))
            Toast.fire({
                icon: 'success',
                title: '{{ Session::get("success") }}'
            });
        @endif

        @if(Session::has('error'))
            Toast.fire({
                icon: 'error',
                title: '{{ Session::get("error") }}'
            });
        @endif

        // Pengecekan cookie agent_session untuk ditampilkan di console
        @if(request()->cookie('agent_session'))
            console.log("%c[Agent Session] Cookie ditemukan: {{ request()->cookie('agent_session') }}", "color: #28a745; font-weight: bold;");
            @if(auth('admin')->user()->is_superadmin)
                console.log("%cRole: Superadmin - Sesi berlaku selama 1 minggu.", "color: #17a2b8;");
            @else
                console.log("%cRole: Agent - Sesi berlaku selama 30 menit.", "color: #17a2b8;");
            @endif
        @else
            console.log("%c[Agent Session] Cookie tidak ditemukan atau sudah kadaluarsa.", "color: #dc3545; font-weight: bold;");
        @endif
    </script>

    @stack('scripts')
</body>

</html>