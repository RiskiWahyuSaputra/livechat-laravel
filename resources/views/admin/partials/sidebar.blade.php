<!-- Global Navigation Sidebar -->
<aside class="w-20 lg:w-64 h-full bg-[#0a1d37] flex flex-col shrink-0 transition-all duration-300 z-40">
    <!-- Logo Section -->
    <div class="p-4 lg:p-6 mb-4 flex items-center gap-3">
        <div class="w-12 h-12 bg-white rounded-xl flex items-center justify-center p-1 shrink-0">
            <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-full h-full object-contain">
        </div>
        <div class="hidden lg:flex flex-col">
            <h1 class="font-black text-white text-lg tracking-widest leading-none">BEST</h1>
            <div class="h-[2px] bg-red-600 w-full rounded-full my-0.5"></div>
            <span class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Helpdesk Admin</span>
        </div>
    </div>

    <!-- Navigation Menu -->
    <nav class="flex-1 px-3 space-y-2 overflow-y-auto">
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.dashboard') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span class="font-bold text-sm hidden lg:block">Dashboard</span>
        </a>

        @if(auth('admin')->user()->hasPermission('view_chat'))
        <a href="{{ route('admin.chat') }}" 
           class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.chat') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            <span class="font-bold text-sm hidden lg:block">Chat</span>
        </a>
        @endif

        @if(auth('admin')->user()->hasPermission('view_history'))
        <a href="{{ route('admin.history.index') }}" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.history.*') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
            <span class="font-bold text-sm hidden lg:block">Riwayat & Arsip</span>
        </a>
        @endif

        @if(auth('admin')->user()->hasPermission('manage_quick_replies'))
        <a href="{{ route('admin.quick-replies.index') }}" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.quick-replies.*') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
            <span class="font-bold text-sm hidden lg:block">Balasan Cepat</span>
        </a>
        @endif

        @if(auth('admin')->user()->hasPermission('manage_customers'))
        <a href="{{ route('admin.customers.index') }}" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.customers.*') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"></path></svg>
            <span class="font-bold text-sm hidden lg:block">Data Pelanggan</span>
        </a>
        @endif

        @if(auth('admin')->user()->hasPermission('manage_roles'))
        <a href="{{ route('admin.roles.index') }}" class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.roles.*') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
            <span class="font-bold text-sm hidden lg:block">Manajemen Role</span>
        </a>
        @endif
    </nav>

    <!-- Footer Sidebar -->
    <div class="p-4 mt-auto border-t border-slate-800">
        <form method="POST" action="{{ route('admin.logout') }}">
            @csrf
            <button type="submit" class="w-full flex items-center gap-4 px-4 py-3 rounded-2xl text-slate-400 hover:text-red-500 hover:bg-red-500/10 transition-all">
                <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path>
                </svg>
                <span class="font-bold text-sm hidden lg:block">Logout</span>
            </button>
        </form>
    </div>
</aside>
