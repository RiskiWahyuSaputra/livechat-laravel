<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Manajemen Role & Akses</title>
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
        form: { id: '', username: '', email: '', password: '', is_superadmin: false, permissions: [] },
        availablePermissions: {{ Js::from($permissions) }},
        openCreate() {
            this.isEdit = false;
            this.form = { id: '', username: '', email: '', password: '', is_superadmin: false, permissions: [] };
            this.showModal = true;
        },
        openEdit(admin) {
            this.isEdit = true;
            this.form = { 
                id: admin.id, 
                username: admin.username, 
                email: admin.email, 
                password: '', 
                is_superadmin: Boolean(admin.is_superadmin), 
                permissions: Array.isArray(admin.permissions) ? admin.permissions : (admin.permissions ? Object.values(admin.permissions) : []) 
            };
            this.showModal = true;
        }
    }">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex items-center justify-between shrink-0 z-30 shadow-sm">
            <div>
                <h1 class="font-black text-slate-900 text-2xl tracking-tighter uppercase">Role & Akses</h1>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-[0.2em]">Manajemen Admin dan Hak Akses Sistem</p>
            </div>

            <div class="flex items-center gap-4">
                 <div class="flex items-center gap-3 bg-slate-50 border border-slate-100 px-4 py-2 rounded-2xl">
                    <div class="w-8 h-8 rounded-lg border-2 border-red-500 bg-[#0a1d37] flex items-center justify-center font-bold text-white text-xs">
                        {{ strtoupper(substr(auth('admin')->user()->username, 0, 1)) }}
                    </div>
                    <div class="text-left">
                        <p class="text-[10px] font-black text-slate-400 uppercase leading-none mb-1">Superadmin</p>
                        <p class="text-xs font-bold text-slate-900 leading-none">{{ auth('admin')->user()->username }}</p>
                    </div>
                </div>
            </div>
        </header>

        <!-- Main Content -->
        <main class="flex-1 overflow-y-auto p-8 bg-slate-50/50">
            
            @if(session('success'))
            <div class="mb-6 bg-emerald-50 border border-emerald-200 text-emerald-600 px-6 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('success') }}
                </div>
            </div>
            @endif
            @if(session('error'))
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl flex items-center justify-between shadow-sm">
                <div class="flex items-center gap-3 font-bold text-sm">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    {{ session('error') }}
                </div>
            </div>
            @endif
            @if($errors->any())
            <div class="mb-6 bg-red-50 border border-red-200 text-red-600 px-6 py-4 rounded-2xl shadow-sm">
                <ul class="list-disc list-inside text-sm font-bold ms-2">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif

            <!-- Table Section -->
            <div class="bg-white rounded-[2.5rem] border border-slate-100 shadow-sm overflow-hidden">
                <div class="p-8 border-b border-slate-100 flex flex-wrap items-center justify-between gap-6">
                    <button @click="openCreate()" class="bg-[#0a1d37] hover:bg-slate-800 text-white px-6 py-3 rounded-2xl text-xs font-black uppercase transition-all shadow-lg shadow-slate-200 flex items-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"></path></svg>
                        Tambah Admin
                    </button>
                    <div class="text-xs font-bold text-slate-400">Total: {{ count($admins) }} Admin</div>
                </div>

                <!-- Table Content -->
                <div class="overflow-x-auto">
                    <table class="w-full text-left border-collapse">
                        <thead>
                            <tr class="bg-slate-50/50">
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Administrator</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100">Level Akses</th>
                                <th class="px-8 py-5 text-[10px] font-black text-slate-400 uppercase tracking-widest border-b border-slate-100 text-right">Opsi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-50">
                            @forelse($admins as $adm)
                            <tr class="hover:bg-slate-50/50 transition-colors group">
                                <td class="px-8 py-5 align-top">
                                    <div class="flex items-center gap-4">
                                        <div class="w-12 h-12 rounded-2xl flex items-center justify-center font-black text-white text-base shadow-md shrink-0 {{ $adm->is_superadmin ? 'bg-red-600 shadow-red-200' : 'bg-slate-400 shadow-slate-200' }}">
                                            {{ strtoupper(substr($adm->username, 0, 1)) }}
                                        </div>
                                        <div>
                                            <p class="font-black text-slate-900 leading-tight">{{ $adm->username }}</p>
                                            <p class="text-xs font-bold text-slate-500">{{ $adm->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-8 py-5 align-top">
                                    @if($adm->is_superadmin)
                                        <span class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-red-50 border border-red-100 text-red-600 text-[10px] font-black uppercase tracking-widest">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"></path></svg>
                                            Superadmin Global
                                        </span>
                                    @else
                                        <div class="flex flex-wrap gap-1.5 max-w-sm">
                                            @if(!empty($adm->permissions))
                                                @foreach((array)$adm->permissions as $perm)
                                                    <span class="px-2.5 py-1 rounded-lg bg-slate-100 text-slate-600 text-[10px] font-bold tracking-wide">{{ $permissions[$perm] ?? $perm }}</span>
                                                @endforeach
                                            @else
                                                <span class="text-xs text-slate-400 italic font-medium">Tidak ada hak akses spesifik</span>
                                            @endif
                                        </div>
                                    @endif
                                </td>
                                <td class="px-8 py-5 align-top text-right">
                                    <div class="flex items-center justify-end gap-2">
                                        <button @click="openEdit({{ collect($adm)->merge(['permissions' => $adm->permissions])->toJson() }})" class="p-2 text-blue-500 hover:bg-blue-50 rounded-xl transition-colors" title="Edit">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>
                                        </button>
                                        @if(auth('admin')->id() !== $adm->id)
                                        <form action="{{ route('admin.roles.destroy', $adm->id) }}" method="POST" class="inline-block" onsubmit="return confirm('Yakin ingin menghapus administrator ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="p-2 text-red-500 hover:bg-red-50 rounded-xl transition-colors" title="Hapus">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>
                                            </button>
                                        </form>
                                        @else
                                        <!-- Placeholder to align layout identically -->
                                        <span class="p-2 w-9 h-9 inline-block"></span>
                                        @endif
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="3" class="px-8 py-20 text-center">
                                    <p class="text-slate-400 text-sm font-bold">Belum ada data administrator.</p>
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
    <div x-cloak x-show="showModal" class="fixed inset-0 z-50 flex items-center justify-center bg-slate-900/60 backdrop-blur-sm p-4 overflow-y-auto">
        <div @click.away="showModal = false" class="bg-white rounded-[2rem] shadow-2xl w-full max-w-2xl my-auto transform transition-all">
            <div class="px-8 py-6 border-b border-slate-100 flex justify-between items-center sticky top-0 bg-white rounded-t-[2rem] z-10">
                <h3 class="font-black text-slate-900 text-xl tracking-tighter uppercase" x-text="isEdit ? 'Edit Administrator' : 'Tambah Administrator'"></h3>
                <button @click="showModal = false" class="text-slate-400 hover:text-red-500 transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
                </button>
            </div>
            
            <form :action="isEdit ? '/admin/roles/' + form.id : '/admin/roles'" method="POST" class="p-8">
                @csrf
                <template x-if="isEdit"><input type="hidden" name="_method" value="PUT"></template>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-6 relative">
                    <!-- Left Column: User Data -->
                    <div class="space-y-5">
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Username</label>
                            <input type="text" name="username" x-model="form.username" required placeholder="Ketik username..." 
                                   class="w-full bg-slate-50 border-2 border-transparent focus:border-[#0a1d37]/20 focus:ring-4 focus:ring-[#0a1d37]/5 rounded-2xl px-5 py-3 text-sm font-bold text-slate-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2">Email</label>
                            <input type="email" name="email" x-model="form.email" required placeholder="admin@best.com" 
                                   class="w-full bg-slate-50 border-2 border-transparent focus:border-[#0a1d37]/20 focus:ring-4 focus:ring-[#0a1d37]/5 rounded-2xl px-5 py-3 text-sm font-bold text-slate-700 outline-none transition-all">
                        </div>
                        <div>
                            <label class="block text-[10px] font-black text-slate-400 uppercase tracking-widest mb-2" x-text="isEdit ? 'Password (Kosongkan bila tidak mengubah)' : 'Password'"></label>
                            <input type="password" name="password" x-model="form.password" :required="!isEdit" placeholder="Minimal 8 karakter..." 
                                   class="w-full bg-slate-50 border-2 border-transparent focus:border-[#0a1d37]/20 focus:ring-4 focus:ring-[#0a1d37]/5 rounded-2xl px-5 py-3 text-sm font-bold text-slate-700 outline-none transition-all">
                        </div>
                    </div>

                    <!-- Right Column: Permissions Data -->
                    <div class="bg-slate-50 rounded-3xl p-5 border border-slate-100">
                        <label class="flex items-center gap-3 p-4 bg-white border-2 rounded-2xl cursor-pointer transition-all mb-4 mt-2" 
                               :class="form.is_superadmin ? 'border-red-500 shadow-md shadow-red-100' : 'border-slate-100 hover:border-slate-200'">
                            <input type="checkbox" name="is_superadmin" value="1" x-model="form.is_superadmin" class="w-5 h-5 text-red-600 rounded focus:ring-red-500">
                            <div>
                                <span class="block text-xs font-black text-slate-800 uppercase tracking-wide">Superadmin Global</span>
                                <span class="block text-[10px] text-slate-500 font-semibold mt-0.5">Memiliki akses ke seluruh fitur sistem.</span>
                            </div>
                        </label>
                        <input type="hidden" name="is_superadmin" value="0" x-show="!form.is_superadmin">

                        <div x-show="!form.is_superadmin" x-collapse>
                            <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-3 px-2">Hak Akses Spesifik</p>
                            <div class="space-y-2">
                                <template x-for="(label, key) in availablePermissions" :key="key">
                                    <label class="flex items-center gap-3 p-3 bg-white border border-slate-100 rounded-xl cursor-pointer hover:bg-blue-50 hover:border-blue-200 transition-colors">
                                        <input type="checkbox" name="permissions[]" :value="key" x-model="form.permissions" class="w-4 h-4 text-blue-600 rounded bg-slate-100 border-slate-300 focus:ring-blue-500">
                                        <span class="text-xs font-bold text-slate-700" x-text="label"></span>
                                    </label>
                                </template>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-8 flex gap-4 pt-6 border-t border-slate-100 sticky bottom-0 bg-white">
                    <button type="button" @click="showModal = false" class="w-1/3 px-6 py-4 rounded-2xl text-xs font-black uppercase tracking-wider text-slate-500 bg-slate-100 hover:bg-slate-200 transition-colors">Batal</button>
                    <button type="submit" class="w-2/3 px-6 py-4 rounded-2xl text-xs font-black uppercase tracking-widest text-white transition-all shadow-lg"
                            :class="form.is_superadmin ? 'bg-red-600 hover:bg-red-700 shadow-red-200' : 'bg-[#0a1d37] hover:bg-slate-800 shadow-slate-300'"
                            x-text="isEdit ? 'Simpan Perubahan' : 'Buat Administrator'"></button>
                </div>
            </form>
        </div>
    </div>

</body>
</html>
