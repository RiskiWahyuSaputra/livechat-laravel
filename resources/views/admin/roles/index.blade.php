@extends('layouts.admin_template')

@section('title', 'Hak Akses')

@section('content')
<div x-data="{
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
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="card-title">Manajemen Admin & Hak Akses</h4>
                        </div>
                        <div class="col-auto">
                            <button @click="openCreate()" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> Tambah Admin</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif
                    @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-center table-hover datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Administrator</th>
                                    <th>Level Akses</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admins as $adm)
                                <tr>
                                    <td>
                                        <div class="table-profileimage">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-title rounded-circle bg-dark text-white">
                                                    {{ strtoupper(substr($adm->username, 0, 1)) }}
                                                </div>
                                            </div>
                                            <span>
                                                <strong>{{ $adm->username }}</strong>
                                                <br><small class="text-muted">{{ $adm->email }}</small>
                                            </span>
                                        </div>
                                    </td>
                                    <td>
                                        @if($adm->is_superadmin)
                                            <span class="badge bg-danger">Superadmin Global</span>
                                        @else
                                            <div class="d-flex flex-wrap gap-1">
                                                @if(!empty($adm->permissions))
                                                    @foreach((array)$adm->permissions as $perm)
                                                        <span class="badge bg-info-light">{{ $permissions[$perm] ?? $perm }}</span>
                                                    @endforeach
                                                @else
                                                    <span class="text-muted small">No permissions</span>
                                                @endif
                                            </div>
                                        @endif
                                    </td>
                                    <td class="text-end">
                                        <button @click="openEdit({{ collect($adm)->merge(['permissions' => $adm->permissions])->toJson() }})" class="btn btn-sm btn-white text-primary me-2"><i class="fe fe-edit"></i></button>
                                        @if(auth('admin')->id() !== $adm->id)
                                        <form action="{{ route('admin.roles.destroy', $adm->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Hapus admin ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center p-5 text-muted">Belum ada administrator.</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal -->
    <div class="modal fade" :class="showModal ? 'show d-block' : ''" tabindex="-1" x-show="showModal" x-cloak>
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <form :action="isEdit ? '/admin/roles/' + form.id : '/admin/roles'" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="modal-header">
                        <h5 class="modal-title" x-text="isEdit ? 'Edit Admin' : 'Tambah Admin'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="form-group mb-3">
                                    <label class="form-label">Username</label>
                                    <input type="text" name="username" x-model="form.username" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label">Email</label>
                                    <input type="email" name="email" x-model="form.email" class="form-control" required>
                                </div>
                                <div class="form-group mb-3">
                                    <label class="form-label" x-text="isEdit ? 'Password (Opsional)' : 'Password'"></label>
                                    <input type="password" name="password" x-model="form.password" class="form-control" :required="!isEdit">
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" name="is_superadmin" value="1" x-model="form.is_superadmin" id="isSuper">
                                    <label class="form-check-label font-weight-bold" for="isSuper">Superadmin Global</label>
                                    <input type="hidden" name="is_superadmin" value="0" x-show="!form.is_superadmin">
                                </div>
                                <div x-show="!form.is_superadmin">
                                    <label class="form-label d-block">Hak Akses</label>
                                    <div class="row">
                                        <template x-for="(label, key) in availablePermissions" :key="key">
                                            <div class="col-6 mb-2">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]" :value="key" x-model="form.permissions" :id="'perm_'+key">
                                                    <label class="form-check-label small" :for="'perm_'+key" x-text="label"></label>
                                                </div>
                                            </div>
                                        </template>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showModal = false">Batal</button>
                        <button type="submit" class="btn btn-primary" x-text="isEdit ? 'Simpan' : 'Tambah'"></button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="modal-backdrop fade" :class="showModal ? 'show d-block' : ''" x-show="showModal" x-cloak></div>
</div>
@endsection
