<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Balasan Cepat</title>
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
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex overflow-hidden" x-data="{
        showModal: false,
        isEdit: false,
        form: { id: '', title: '', content: '' },
        openCreate() {
            this.isEdit = false;
            this.form = { id: '', title: '', content: '' };
            this.showModal = true;
        },
        openEdit(reply) {
            this.isEdit = true;
            this.form = { id: reply.id, title: reply.title, content: reply.content };
            this.showModal = true;
        }
    }">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex items-center justify-between shrink-0 z-30 shadow-sm">
            <div>
                <h1 class="font-black text-slate-900 text-2xl tracking-tighter uppercase">Balasan Cepat</h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Manajemen template pesan balasan agent</p>
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
                <div class="p-8 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6">
                    <button @click="openCreate()" class="bg-red-600 hover:bg-red-700 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all shadow-lg shadow-red-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"></path></svg>
                        Tambah Balasan
                    </button>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Judul / Singkatan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Isi Pesan</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($replies as $reply)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-5 align-top">
                                    <p class="font-black text-slate-900 leading-tight">{{ $reply->title }}</p>
                                </td>
                                <td class="px-8 py-5 pl-4 max-w-lg">
                                    <p class="text-sm font-medium text-slate-600 whitespace-pre-wrap">{{ $reply->content }}</p>
                                </td>
                                <td class="px-8 py-5 align-top text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit({{ $reply->toJson() }})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-xl transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        <form action="{{ route('admin.quick-replies.destroy', $reply->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus balasan cepat ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-20 text-center">
                                    <div class="w-20 h-20 bg-slate-50 rounded-3xl mx-auto flex items-center justify-center mb-4 text-slate-300">
                                        <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"></path></svg>
                                    </div>
                                    <h4 class="text-slate-800 font-black text-lg">Belum Ada Balasan Cepat</h4>
                                    <p class="text-slate-400 text-sm font-bold">Tambahkan balasan cepat agar agen dapat membalas chat lebih efisien.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>

    <!-- Modal Form (AlpineJS) -->
    <div x-cloak x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4">
        <div @click.away="showModal = false" class="bg-white rounded-[2rem] shadow-2xl w-full max-w-lg overflow-hidden transform transition-all">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center">
                <h3 class="font-black text-slate-900 text-xl" x-text="isEdit ? 'Edit Balasan Cepat' : 'Tambah Balasan Cepat'"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form :action="isEdit ? '/admin/quick-replies/' + form.id : '/admin/quick-replies'" method="POST" class="p-8">
                @csrf
                <template x-if="isEdit">
                    <input type="hidden" name="_method" value="PUT">
                </template>

                <div class="space-y-5">
                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Judul / Singkatan</label>
                        <input type="text" name="title" x-model="form.title" required placeholder="Contoh: Sapaan Pagi" 
                               class="w-full bg-slate-50 border-2 border-transparent focus:border-red-500/20 focus:ring-4 focus:ring-red-500/5 rounded-2xl px-5 py-3 text-sm font-bold text-slate-700 outline-none transition-all">
                        <p class="text-xs text-slate-400 mt-1 font-medium">Judul singkat untuk memudahkan agen mencari.</p>
                    </div>

                    <div>
                        <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Isi Pesan</label>
                        <textarea name="content" x-model="form.content" required rows="5" placeholder="Contoh: Selamat pagi! Ada yang bisa kami bantu hari ini?"
                                  class="w-full bg-slate-50 border-2 border-transparent focus:border-red-500/20 focus:ring-4 focus:ring-red-500/5 rounded-2xl px-5 py-3 text-sm font-bold text-slate-700 outline-none transition-all resize-none"></textarea>
                    </div>
                </div>

                <div class="mt-8 flex gap-3">
                    <button type="button" @click="showModal = false" class="flex-1 px-6 py-3.5 rounded-2xl text-xs font-black uppercase text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="flex-1 px-6 py-3.5 rounded-2xl text-xs font-black uppercase tracking-widest text-white bg-red-600 hover:bg-red-700 transition-colors shadow-lg shadow-red-200" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Balasan'"></button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
