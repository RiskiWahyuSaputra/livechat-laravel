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
    <nav class="flex-1 px-3 space-y-2">
        <a href="{{ route('admin.dashboard') }}" 
           class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.dashboard') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z"></path>
            </svg>
            <span class="font-bold text-sm hidden lg:block">Dashboard</span>
        </a>

        <a href="{{ route('admin.chat') }}" 
           class="flex items-center gap-4 px-4 py-3.5 rounded-2xl transition-all group {{ request()->routeIs('admin.chat') ? 'bg-red-600 text-white shadow-lg shadow-red-900/40' : 'text-slate-400 hover:bg-slate-800 hover:text-white' }}">
            <svg class="w-6 h-6 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"></path>
            </svg>
            <span class="font-bold text-sm hidden lg:block">Chat</span>
        </a>
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
