<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Dashboard User</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    <style>
        [x-cloak] { display: none !important; }
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden">

    <!-- Header similar to admin dashboard -->
    <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-4 md:px-6 py-3 flex items-center justify-between shrink-0 z-30 shadow-sm">
        <div class="flex items-center gap-3 md:gap-4">
            <div class="flex items-center gap-2 md:gap-3">
                <div class="w-10 h-10 bg-white rounded-lg flex items-center justify-center p-1 border border-slate-100 shadow-sm">
                    <img src="{{ asset('images/best-logo-1.png') }}" alt="Logo" class="w-full h-full object-contain">
                </div>
                <div>
                    <h1 class="font-black text-slate-900 text-sm md:text-base tracking-tighter leading-none">BEST <span class="text-red-600">CORP</span></h1>
                    <span class="text-[9px] md:text-[10px] font-bold text-slate-400 uppercase tracking-[0.3em] mt-1 block">CUSTOMER SUPPORT</span>
                </div>
            </div>
        </div>
        
        <!-- User Profile/Logout -->
        <div class="flex items-center gap-4 relative" x-data="{ showProfile: false }">
            <button @click="showProfile = !showProfile" @click.away="showProfile = false" 
                    class="flex items-center gap-2 md:gap-3 hover:bg-slate-50 p-1 md:p-1.5 md:pr-3 rounded-2xl transition-all border border-transparent hover:border-slate-200">
                <div class="relative">
                    <div class="w-8 h-8 md:w-9 md:h-9 rounded-xl bg-[#0a1d37] flex items-center justify-center font-bold text-white shadow-md border-2 border-white text-sm">
                        {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                    </div>
                </div>
                <div class="text-left hidden md:block">
                    <p class="text-xs font-bold text-slate-900 leading-none mb-1">{{ Auth::user()->name }}</p>
                    <p class="text-[10px] text-slate-500 font-medium leading-none uppercase">Online</p>
                </div>
            </button>

            <!-- Profile Dropdown -->
            <div x-show="showProfile" x-cloak 
                 x-transition:enter="transition ease-out duration-200"
                 class="absolute right-0 top-full mt-2 w-64 md:w-72 bg-white rounded-[2rem] shadow-2xl border border-slate-200 py-3 text-slate-800 z-50 overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 bg-[#0a1d37] mb-2 rounded-t-[1.8rem] text-white">
                    <p class="text-[9px] font-black text-red-500 uppercase tracking-[0.3em] mb-3">User Account</p>
                    <div class="flex items-center gap-4">
                         <div class="w-14 h-14 rounded-2xl bg-red-600 flex items-center justify-center font-black text-2xl text-white shadow-lg shadow-red-900/40">
                            {{ strtoupper(substr(Auth::user()->name, 0, 1)) }}
                        </div>
                        <div class="overflow-hidden">
                            <p class="font-black text-white text-lg truncate">{{ Auth::user()->name }}</p>
                            <p class="text-xs text-slate-300 font-medium truncate">{{ Auth::user()->email }}</p>
                        </div>
                    </div>
                </div>
                <div class="mt-3 px-3 pt-3 border-t border-slate-100">
                    <form method="POST" action="{{ route('user.logout') }}">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-3 rounded-2xl text-red-600 hover:bg-red-50 transition-all font-black text-sm">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>
                            Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1 flex flex-col items-center justify-center p-6 text-center">
        <h2 class="text-2xl font-black text-slate-800 mb-4">Riwayat Percakapan Anda</h2>
        
        @if ($conversations->isEmpty())
            <div class="w-full max-w-md bg-white rounded-3xl shadow-lg p-8 flex flex-col items-center justify-center border border-slate-100">
                <svg class="w-16 h-16 text-slate-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                <p class="text-slate-500 font-bold text-sm">Anda belum memiliki riwayat percakapan. Mulai obrolan baru kapan saja!</p>
            </div>
        @else
            <div class="w-full max-w-2xl bg-white rounded-3xl shadow-lg p-4 space-y-2 overflow-y-auto max-h-[calc(100vh-200px)] border border-slate-100">
                @foreach ($conversations as $conversation)
                    <a href="{{ route('chat.index') }}?conversation_id={{ $conversation->id }}" class="flex items-center gap-4 p-4 rounded-2xl hover:bg-slate-50 transition-colors cursor-pointer border border-slate-100">
                        <div class="w-12 h-12 rounded-xl bg-red-600 flex items-center justify-center font-bold text-white shrink-0 text-lg">
                            A
                        </div>
                        <div class="flex-1 text-left">
                            <p class="font-bold text-slate-800">Percakapan dengan Admin</p>
                            <p class="text-sm text-slate-500 truncate">
                                {{ $conversation->latestMessage ? $conversation->latestMessage->content : 'Belum ada pesan.' }}
                            </p>
                        </div>
                        <span class="text-xs text-slate-400">
                            {{ $conversation->updated_at->diffForHumans() }}
                        </span>
                    </a>
                @endforeach
            </div>
        @endif
    </main>

    <!-- Chat Bubble (placeholder, will be replaced by actual widget) -->
    <a href="{{ route('chat.index') }}" 
       class="fixed bottom-8 right-8 w-16 h-16 rounded-full bg-red-600 flex items-center justify-center text-white shadow-xl hover:bg-red-700 transition-all transform hover:scale-105 fab-pulse z-50">
        <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
    </a>
</body>
</html>
