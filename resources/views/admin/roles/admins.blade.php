@extends('layouts.admin_template')

@section('title', 'Hak Akses')

@section('content')
@push('scripts')
<script>
const adminRolesData = {
    showModal: false,
    isEdit: false,
    form: { id: '', username: '', email: '', password: '', role: 'agent', is_superadmin: false, permissions: [] },
    availablePermissions: {{ Js::from($permissions) }},
    permissionGroups: {
        'Modul Percakapan & Pelayanan': ['view_chat', 'manage_quick_replies'],
        'Modul Pelanggan': ['manage_customers'],
        'Modul Sistem & Keamanan': ['manage_roles']
    },
    init() {
        this.$watch('form.role', (value) => {
            if (value === 'super_admin') {
                this.form.is_superadmin = true;
                this.form.permissions = Object.keys(this.availablePermissions);
            } else {
                this.form.is_superadmin = false;
                if (!this.isEdit) {
                    this.form.permissions = [];
                }
            }
        });
    },
    openCreate() {
        this.isEdit = false;
        this.form = { id: '', username: '', email: '', password: '', role: 'agent', is_superadmin: false, permissions: [] };
        this.showModal = true;
    },
    openEdit(admin) {
        this.isEdit = true;
        this.form = { 
            id: admin.id, 
            username: admin.username, 
            email: admin.email, 
            password: '', 
            role: admin.role,
            is_superadmin: Boolean(admin.is_superadmin), 
            permissions: Array.isArray(admin.permissions) ? admin.permissions : (admin.permissions ? Object.values(admin.permissions) : []) 
        };
        this.showModal = true;
    },
    confirmSubmit(e) {
        // Prevent accidental downgrading of own role
        const currentAdminId = {{ auth('admin')->id() }};
        if (this.isEdit && this.form.id === currentAdminId && this.form.role === 'agent') {
            e.preventDefault();
            Swal.fire({
                title: 'PERINGATAN!',
                text: 'Anda akan mengubah peran Anda sendiri menjadi Agent. Anda akan kehilangan akses ke menu manajemen hak akses setelah ini. Lanjutkan?',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#6c757d',
                confirmButtonText: 'Ya, Lanjutkan',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    e.target.submit();
                }
            });
            return false;
        }
        return true;
    }
}

function confirmDelete(e, isSuperadmin) {
    e.preventDefault();
    let title = isSuperadmin ? 'PERINGATAN KRITIS!' : 'Hapus admin?';
    let msg = isSuperadmin 
        ? 'Anda akan menghapus pengguna dengan level Superadmin Global. Tindakan ini berdampak besar pada sistem dan tidak dapat dibatalkan. Apakah Anda benar-benar yakin?'
        : 'Hapus admin ini tidak dapat dikembalikan.';
        
    Swal.fire({
        title: title,
        text: msg,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6c757d',
        confirmButtonText: 'Ya, Hapus!',
        cancelButtonText: 'Batal'
    }).then((result) => {
        if (result.isConfirmed) {
            e.target.submit();
        }
    });
    return false;
}
</script>
<div x-data="adminRolesData">
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
                                            <span class="badge bg-primary mb-1">{{ $adm->roleModel->name ?? $adm->role }}</span>
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
                                        <form action="{{ route('admin.admins.destroy', $adm->id) }}" method="POST" class="d-inline" onsubmit="return confirmDelete(event, {{ $adm->is_superadmin ? 'true' : 'false' }});">
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
                <form :action="isEdit ? '/admin/admins/' + form.id : '/admin/admins'" method="POST" @submit="confirmSubmit">
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
                                <div class="form-group mb-3">
                                    <label class="form-label font-weight-bold">Role Administrator</label>
                                    <select name="role" x-model="form.role" class="form-select" required>
                                        <option value="super_admin">Superadmin</option>
                                        <template x-for="roleItem in {{ Js::from($rolesList) }}" :key="roleItem.slug">
                                            <template x-if="roleItem.slug !== 'super_admin'">
                                                <option :value="roleItem.slug" x-text="roleItem.name"></option>
                                            </template>
                                        </template>
                                        <!-- Keep original agent for fallback if no roles in DB -->
                                        <template x-if="{{ $rolesList->count() }} === 0">
                                            <option value="agent">Agent</option>
                                        </template>
                                    </select>
                                </div>
                                <div>
                                    <label class="form-label d-block text-primary mb-3"><i class="fe fe-shield"></i> Penetapan Hak Akses</label>
                                    
                                    <template x-for="(keys, groupName) in permissionGroups" :key="groupName">
                                        <div class="card bg-light bg-opacity-50 mb-3 border-0 shadow-none">
                                            <div class="card-body p-3">
                                                <h6 class="card-title text-muted text-uppercase small font-weight-bold mb-2" x-text="groupName"></h6>
                                                <div class="row">
                                                    <template x-for="key in keys" :key="key">
                                                        <div class="col-12 mb-2" x-show="availablePermissions[key]">
                                                            <div class="form-check custom-checkbox">
                                                                <input class="form-check-input" type="checkbox" name="permissions[]" :value="key" x-model="form.permissions" :id="'perm_'+key" :disabled="form.role === 'super_admin'">
                                                                <label class="form-check-label small" :for="'perm_'+key" x-text="availablePermissions[key]"></label>
                                                            </div>
                                                        </div>
                                                    </template>
                                                </div>
                                            </div>
                                        </div>
                                    </template>
                                    
                                    <div class="alert alert-info py-2 small mt-2 d-flex align-items-center" x-show="form.role === 'super_admin'">
                                        <i class="fe fe-info me-2 fs-5"></i> 
                                        <span>Superadmin global secara otomatis diberikan akses penuh ke seluruh modul sistem.</span>
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
