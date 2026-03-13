@extends('layouts.admin_template')

@section('title', 'Alur Chat Bot')

@section('content')
<div x-data="{
    showModal: false,
    isEdit: false,
    form: { id: '', parent_id: '', label: '', message_response: '', action_type: 'submenu', action_value: '' },
    openCreate(parentId = null) {
        this.isEdit = false;
        this.form = { id: '', parent_id: parentId, label: '', message_response: '', action_type: 'submenu', action_value: '' };
        this.showModal = true;
    },
    openEdit(menu) {
        this.isEdit = true;
        this.form = { 
            id: menu.id, 
            parent_id: menu.parent_id, 
            label: menu.label, 
            message_response: menu.message_response, 
            action_type: menu.action_type, 
            action_value: menu.action_value 
        };
        this.showModal = true;
    }
}">
    <div class="page-header">
        <div class="row align-items-center">
            <div class="col">
                <h3 class="page-title">Manajemen Alur Chat Bot</h3>
                <ul class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                    <li class="breadcrumb-item active">Alur Chat Bot</li>
                </ul>
            </div>
            <div class="col-auto">
                <button @click="openCreate()" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> Tambah Menu Utama</button>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-sm-12">
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-center table-hover datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Label Tombol</th>
                                    <th>Tipe Aksi</th>
                                    <th>Pesan / Nilai Balasan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($menus as $menu)
                                    <!-- Parent Menu -->
                                    <tr class="bg-light">
                                        <td><strong>{{ $menu->label }}</strong></td>
                                        <td><span class="badge badge-pill bg-primary-light text-primary">{{ strtoupper($menu->action_type) }}</span></td>
                                        <td><div class="text-wrap" style="max-width: 300px;">{{ $menu->message_response ?: ($menu->action_value ?: '-') }}</div></td>
                                        <td class="text-end">
                                            @if($menu->action_type === 'submenu')
                                                <button @click="openCreate({{ $menu->id }})" class="btn btn-sm btn-white text-success me-1" title="Tambah Submenu"><i class="fe fe-plus-circle"></i></button>
                                            @endif
                                            <button @click="openEdit({{ $menu->toJson() }})" class="btn btn-sm btn-white text-primary me-1"><i class="fe fe-edit"></i></button>
                                            <form action="{{ route('admin.bot-menus.destroy', $menu->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus menu ini dan seluruh submenunya?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    <!-- Child Menus -->
                                    @foreach($menu->children as $child)
                                    <tr>
                                        <td><span class="ms-4 text-muted"><i class="fe fe-corner-down-right me-2"></i> {{ $child->label }}</span></td>
                                        <td><span class="badge badge-pill bg-success-light text-success">{{ strtoupper($child->action_type) }}</span></td>
                                        <td><div class="text-wrap ms-4" style="max-width: 300px;">{{ $child->message_response ?: ($child->action_value ?: '-') }}</div></td>
                                        <td class="text-end">
                                            <button @click="openEdit({{ $child->toJson() }})" class="btn btn-sm btn-white text-primary me-1"><i class="fe fe-edit"></i></button>
                                            <form action="{{ route('admin.bot-menus.destroy', $child->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus submenu ini?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                            </form>
                                        </td>
                                    </tr>
                                    @endforeach
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" :class="showModal ? 'show d-block' : ''" tabindex="-1" x-show="showModal" x-cloak>
        <div class="modal-dialog">
            <div class="modal-content">
                <form :action="isEdit ? '/admin/bot-menus/' + form.id : '/admin/bot-menus'" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <input type="hidden" name="parent_id" x-model="form.parent_id">

                    <div class="modal-header">
                        <h5 class="modal-title" x-text="isEdit ? 'Edit Menu Bot' : 'Tambah Menu Bot'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold">Label Tombol <span class="text-danger">*</span></label>
                            <input type="text" name="label" x-model="form.label" class="form-control" placeholder="Contoh: Hubungi CS" required>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold">Tipe Aksi <span class="text-danger">*</span></label>
                            <select name="action_type" x-model="form.action_type" class="form-control" required>
                                <option value="submenu">Buka Submenu (Pilihan Baru)</option>
                                <option value="link">Buka Link / Website</option>
                                <option value="connect_cs">Hubungkan ke Customer Service</option>
                            </select>
                        </div>

                        <div class="form-group mb-3">
                            <label class="form-label font-weight-bold">Pesan Balasan Bot</label>
                            <textarea name="message_response" x-model="form.message_response" class="form-control" rows="3" placeholder="Pesan yang dikirim bot saat tombol diklik"></textarea>
                            <small class="text-muted">Muncul sebagai gelembung chat dari bot.</small>
                        </div>

                        <div class="form-group mb-3" x-show="form.action_type === 'link'">
                            <label class="form-label font-weight-bold">URL Link</label>
                            <input type="url" name="action_value" x-model="form.action_value" class="form-control" placeholder="https://example.com">
                        </div>

                        <div class="form-group mb-3" x-show="form.action_type === 'connect_cs'">
                            <label class="form-label font-weight-bold">Nama Kategori/Departemen</label>
                            <input type="text" name="action_value" x-model="form.action_value" class="form-control" placeholder="Contoh: General Support">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary" x-text="isEdit ? 'Simpan Perubahan' : 'Tambah Menu'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>
</div>
@endsection
