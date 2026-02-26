<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Data Pelanggan</title>
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
                <h1 class="font-black text-slate-900 text-2xl tracking-tighter uppercase">Data Pelanggan</h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Manajemen profil dan akses pengguna</p>
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
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-600 px-6 py-4 rounded-2xl flex items-center justify-between">
                <div class="flex items-center gap-3 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('success') }}
                </div>
            </div>
            @endif

            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <!-- Filters & Search -->
                <div class="p-8 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6">
                    <div class="flex items-center gap-2">
                        <a href="{{ route('admin.customers.index') }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ !request('status') ? 'bg-[#0a1d37] text-white shadow-lg shadow-slate-300' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Semua</a>
                        <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ request('status') == 'active' ? 'bg-emerald-500 text-white shadow-lg shadow-emerald-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Aktif</a>
                        <a href="{{ route('admin.customers.index', ['status' => 'blocked']) }}" 
                           class="px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all {{ request('status') == 'blocked' ? 'bg-red-600 text-white shadow-lg shadow-red-200' : 'bg-slate-50 text-slate-500 hover:bg-slate-100' }}">Diblokir</a>
                    </div>

                    <form action="{{ route('admin.customers.index') }}" method="GET" class="relative w-full md:w-auto">
                        @if(request('status'))
                            <input type="hidden" name="status" value="{{ request('status') }}">
                        @endif
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari pelanggan..." 
                               class="bg-slate-50 border-2 border-transparent focus:border-[#0a1d37]/20 focus:ring-4 focus:ring-[#0a1d37]/5 rounded-2xl px-12 py-3.5 text-sm font-bold w-full md:w-80 transition-all outline-none">
                        <svg class="w-5 h-5 absolute left-4 top-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                    </form>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Pelanggan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Kontak & Instansi</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-center">Status Akses</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($customers as $customer)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-5">
                                    <div class="flex items-center gap-4">
                                        <div class="w-11 h-11 rounded-xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-base shadow-lg shadow-slate-200 group-hover:scale-105 transition-transform"
                                             class="{{ $customer->is_blocked ? 'opacity-50 grayscale' : '' }}">
                                            {{ strtoupper(substr($customer->name, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 leading-tight {{ $customer->is_blocked ? 'line-through text-slate-400' : '' }}">{{ $customer->name }}</p>
                                            <p class="text-[10px] font-bold text-slate-400 uppercase tracking-wider">ID: CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5">
                                    <div class="space-y-1 {{ $customer->is_blocked ? 'opacity-60' : '' }}">
                                        <div class="flex items-center gap-2 text-slate-700 font-bold text-xs">
                                            <svg class="w-3.5 h-3.5 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                                            {{ $customer->contact }}
                                        </div>
                                        <div class="flex items-center gap-2 text-slate-400 font-medium text-[11px]">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"></path></svg>
                                            {{ $customer->origin }}
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 text-center">
                                    @if($customer->is_blocked)
                                        <span class="px-3 py-1 rounded-full bg-red-50 text-red-600 text-[10px] font-black uppercase tracking-widest border border-red-100 flex items-center gap-1.5 justify-center w-fit mx-auto">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"></path></svg>
                                            Diblokir
                                        </span>
                                    @else
                                        <span class="px-3 py-1 rounded-full bg-emerald-50 text-emerald-600 text-[10px] font-black uppercase tracking-widest border border-emerald-100 flex items-center gap-1.5 justify-center w-fit mx-auto">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                                            Aktif
                                        </span>
                                    @endif
                                </td>
                                <td class="px-8 py-5 align-middle text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <!-- Toggle Block/Unblock Form -->
                                        <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin {{ $customer->is_blocked ? 'mengaktifkan' : 'memblokir' }} pelanggan ini?');">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_blocked" value="{{ $customer->is_blocked ? 0 : 1 }}">
                                            <button type="submit" class="p-2.5 rounded-xl transition-colors shrink-0 {{ $customer->is_blocked ? 'bg-emerald-50 text-emerald-600 hover:bg-emerald-100' : 'bg-orange-50 text-orange-600 hover:bg-orange-100' }}" title="{{ $customer->is_blocked ? 'Buka Blokir' : 'Blokir' }}">
                                                @if($customer->is_blocked)
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M8 11V7a4 4 0 118 0m-4 8v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2z"></path></svg>
                                                @else
                                                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                                @endif
                                            </button>
                                        </form>

                                        <!-- Delete Form -->
                                        <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" class="inline-block" onsubmit="return confirm('PERINGATAN! Yakin ingin menghapus seluruh data pelanggan ini beserta chat history-nya?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2.5 bg-red-50 hover:bg-red-100 text-red-600 rounded-xl transition-colors shrink-0" title="Hapus Permanen">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="px-8 py-20 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-3xl mx-auto flex items-center justify-center mb-4 text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"></path></svg>
                                    </div>
                                    <h4 class="text-slate-800 font-black text-lg">Tidak Ada Pelanggan</h4>
                                    <p class="text-slate-400 text-sm font-bold">Data yang Anda cari tidak ditemukan.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="p-8 border-t border-slate-50 bg-slate-50/30">
                    {{ $customers->links() }}
                </div>
            </div>
        </main>
    </div>

</body>
</html>
