<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=0">
    <title>@yield('title', 'Admin Dashboard') | LiveChat BEST</title>

    <!-- Favicons -->
    <link rel="shortcut icon" href="{{ asset('images/best-logo-1.png') }}">
    
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
    
    <!-- Alpine.js -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <style>
        [x-cloak] { display: none !important; }
    </style>
    
    @stack('styles')
</head>

<body>
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
                <i class="fas fa-align-left"></i>
            </a>
            <div class="header-split">
                <div class="page-headers">
                    <div class="search-bar">
						<span><i class="fe fe-search"></i></span>
						<input type="text" placeholder="Search" class="form-control">
					</div>
                </div>
                <ul class="nav user-menu">
                    
                    
                    <!-- User Menu -->
                    <li class="nav-item dropdown">
                        <a href="javascript:void(0)" class="user-link  nav-link" data-bs-toggle="dropdown">
                            <span class="user-img">
                                <div class="w-10 h-10 rounded-circle bg-primary d-flex align-items-center justify-content-center text-white font-weight-bold">
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
                                        <a href="javascript:void(0);" onclick="this.closest('form').submit();">Log Out</a>
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
                        <img src="{{ asset('images/best-logo-1.png') }}" class="img-fluid logo" alt="">
                    </a>
                    <a href="{{ route('admin.dashboard') }}">
                        <img src="{{ asset('images/best-logo-1.png') }}" class="img-fluid logo-small" alt="">
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
                            <a href="{{ route('admin.dashboard') }}"><span>Dashboard</span></a>
                        </li>
                        
                        <li class="menu-title">
                            <h6>Communication</h6>
                        </li>
                        @if(auth('admin')->user()->hasPermission('view_chat'))
                        <li class="{{ request()->routeIs('admin.chat') ? 'active' : '' }}">
                            <a href="{{ route('admin.chat') }}"><span>Chat</span></a>
                        </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('view_history'))
                        <li class="{{ request()->routeIs('admin.history.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.history.index') }}"><span>Riwayat Arsip</span></a>
                        </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('manage_quick_replies'))
                        <li class="{{ request()->routeIs('admin.quick-replies.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.quick-replies.index') }}"><span>Balasan Cepat</span></a>
                        </li>
                        @endif

                        <li class="menu-title">
                            <h6>Management</h6>
                        </li>
                        @if(auth('admin')->user()->hasPermission('manage_customers'))
                        <li class="{{ request()->routeIs('admin.customers.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.customers.index') }}"><span>Data Pelanggan</span></a>
                        </li>
                        @endif

                        @if(auth('admin')->user()->hasPermission('manage_roles'))
                        <li class="{{ request()->routeIs('admin.roles.*') ? 'active' : '' }}">
                            <a href="{{ route('admin.roles.index') }}"><span>Hak Akses</span></a>
                        </li>
                        @endif

                        <li class="menu-title">
                            <h6>Account</h6>
                        </li>
                        <li>
                            <form method="POST" action="{{ route('admin.logout') }}" id="logout-form-sidebar">
                                @csrf
                                <a href="javascript:void(0);" onclick="document.getElementById('logout-form-sidebar').submit();"><span>Logout</span></a>
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
    
    @stack('scripts')
</body>
</html>
