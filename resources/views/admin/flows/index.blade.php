@extends('layouts.admin_template')

@section('title', 'Manajemen Flow')

@section('content')
<div x-data="{
    showCreate: false,
    form: { code: '', name: '', description: '' },
    openCreate() { this.showCreate = true; this.form = { code: '', name: '', description: '' }; }
}">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="card-title">Manajemen Flow Chatbot</h4>
                            <p class="text-muted mb-0 small">Kelola alur percakapan chatbot secara dinamis.</p>
                        </div>
                        <div class="col-auto d-flex gap-2">
                            <a href="{{ route('admin.holidays.index') }}" class="btn btn-outline-secondary btn-sm">
                                <i class="fe fe-calendar"></i> Libur Nasional
                            </a>
                            <button @click="openCreate()" class="btn btn-primary btn-sm">
                                <i class="fe fe-plus"></i> Buat Flow Baru
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-center table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Kode</th>
                                    <th>Nama</th>
                                    <th>Status</th>
                                    <th class="text-center">Nodes</th>
                                    <th class="text-center">Edges</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($flows as $flow)
                                <tr>
                                    <td><code>{{ $flow->code }}</code></td>
                                    <td>
                                        <strong>{{ $flow->name }}</strong>
                                        @if($flow->description)
                                        <div class="text-muted small">{{ Str::limit($flow->description, 60) }}</div>
                                        @endif
                                    </td>
                                    <td>
                                        @if($flow->status === 'published')
                                        <span class="badge bg-success">Published</span>
                                        @else
                                        <span class="badge bg-secondary">Draft</span>
                                        @endif
                                    </td>
                                    <td class="text-center">{{ $flow->nodes_count }}</td>
                                    <td class="text-center">{{ $flow->edges_count }}</td>
                                    <td class="text-end">
                                        <a href="{{ route('admin.flows.show', $flow) }}" class="btn btn-sm btn-white text-primary me-1" title="Edit Flow"><i class="fe fe-edit"></i></a>

                                        {{-- Publish / Unpublish --}}
                                        <form action="{{ route('admin.flows.publish', $flow) }}" method="POST" class="d-inline">
                                            @csrf
                                            @if($flow->status === 'published')
                                            <input type="hidden" name="status" value="draft">
                                            <button type="submit" class="btn btn-sm btn-white text-warning me-1" title="Ubah ke Draft"><i class="fe fe-eye-off"></i></button>
                                            @else
                                            <input type="hidden" name="status" value="published">
                                            <button type="submit" class="btn btn-sm btn-white text-success me-1" title="Publish"><i class="fe fe-eye"></i></button>
                                            @endif
                                        </form>

                                        {{-- Delete --}}
                                        <form action="{{ route('admin.flows.destroy', $flow) }}" method="POST" class="d-inline"
                                              onsubmit="event.preventDefault(); Swal.fire({ title: 'Hapus flow?', text: 'Semua node dan edge akan ikut terhapus!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((r) => { if (r.isConfirmed) this.submit(); });">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger" title="Hapus"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted py-4">
                                        Belum ada flow. <a href="#" @click.prevent="openCreate()">Buat flow pertama</a>.
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Create Modal --}}
    <div x-show="showCreate" x-cloak class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.flows.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Buat Flow Baru</h5>
                        <button type="button" class="btn-close" @click="showCreate = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Kode <small class="text-muted">(huruf kecil, angka, underscore)</small></label>
                            <input type="text" name="code" x-model="form.code" class="form-control" placeholder="e.g. my_flow" required pattern="[a-z0-9_]+">
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama</label>
                            <input type="text" name="name" x-model="form.name" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Deskripsi <small class="text-muted">(opsional)</small></label>
                            <textarea name="description" x-model="form.description" class="form-control" rows="2"></textarea>
                        </div>
                        @if($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">@foreach($errors->all() as $err)<li>{{ $err }}</li>@endforeach</ul>
                        </div>
                        @endif
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showCreate = false">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
