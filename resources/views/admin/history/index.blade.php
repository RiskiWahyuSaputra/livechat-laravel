<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Riwayat Chat</title>
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
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex items-center justify-between shrink-0 z-30 shadow-sm">
            <div>
                <h1 class="font-black text-slate-900 text-2xl tracking-tighter uppercase">Riwayat & Arsip</h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Data percakapan yang telah selesai</p>
            </div>

            <div class="flex items-center gap-4">
                 <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 px-4 py-2 rounded-2xl">
                    <div class="w-8 h-8 rounded-lg bg-[#0a1d37] flex items-center justify-center font-bold text-white text-xs">
                        {{ strtoupper(substr(auth('admin')->user()->username, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] font-black text-slate-400 uppercase leading-none mb-1">Login Sebagai</p>
                        <p class="text-xs font-bold text-slate-900 leading-none">{{ auth('admin')->user()->username }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-slate-50/50">
            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <!-- Filters & Search -->
                <div class="p-8 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6">
                    <form action="{{ route('admin.history.index') }}" method="GET" class="relative w-full md:w-auto">
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari nama pelanggan..." 
                               class="bg-slate-50 border-2 border-transparent focus:border-[#0a1d37]/20 focus:ring-4 focus:ring-[#0a1d37]/5 rounded-2xl px-12 py-3.5 text-sm font-bold w-full md:w-96 transition-all outline-none">
                        <svg class="w-5 h-5 absolute left-4 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Pelanggan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Agen Penanganan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Kategori</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Diselesaikan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($archives as $chat)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-11 h-11 rounded-xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-base shadow-lg shadow-slate-200 group-hover:scale-105 transition-transform">
                                            {{ strtoupper(substr($chat->customer->name ?? '?', 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 leading-tight">{{ $chat->customer->name ?? 'Pelanggan Dihapus' }}</p>
                                            <p class="text-[10px] font-semibold text-slate-400 mt-0.5">{{ $chat->customer->contact ?? '-' }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-lg bg-slate-100 text-slate-600 text-[11px] font-bold">
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>
                                        {{ $chat->admin->username ?? 'Sistem' }}
                                    </span>
                                </td>
                                <td class="px-8 py-5">
                                    @if($chat->problem_category)
                                        <span class="px-3 py-1 rounded-full bg-blue-50 text-blue-600 border border-blue-100 text-[10px] font-black uppercase tracking-widest">{{ $chat->problem_category }}</span>
                                    @else
                                        <span class="text-xs text-slate-400">-</span>
                                    @endif
                                </td>
                                <td class="px-8 py-5">
                                    <p class="text-xs font-bold text-slate-700">{{ $chat->deleted_at->translatedFormat('d F Y') }}</p>
                                    <p class="text-[10px] font-bold text-slate-400">{{ $chat->deleted_at->format('H:i') }} WIB</p>
                                </td>
                                <td class="px-8 py-5 text-right">
                                    <a href="{{ route('admin.history.show', $chat->id) }}" class="inline-flex items-center justify-center p-2.5 bg-slate-100 hover:bg-[#0a1d37] text-slate-400 hover:text-white rounded-xl transition-colors shrink-0" title="Lihat History">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"></path></svg>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="px-8 py-20 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-3xl mx-auto flex items-center justify-center mb-4 text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"></path></svg>
                                    </div>
                                    <h4 class="text-slate-800 font-black text-lg">Belum Ada Riwayat</h4>
                                    <p class="text-slate-400 text-sm font-bold">Arsip percakapan yang selesai akan muncul di sini.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-8 border-t border-slate-50 bg-slate-50/30">
                    {{ $archives->links() }}
                </div>
            </div>
        </main>
    </div>

</body>
</html>
