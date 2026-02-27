@extends('layouts.admin_template')

@section('title', 'Balasan Cepat')

@section('content')
<div x-data="{
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
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <h4 class="card-title">Manajemen Balasan Cepat</h4>
                        </div>
                        <div class="col-auto">
                            <button @click="openCreate()" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> Tambah Balasan</button>
                        </div>
                    </div>
                </div>
                <div class="card-body">

                    <div class="table-responsive">
                        <table class="table table-center table-hover datatable">
                            <thead class="thead-light">
                                <tr>
                                    <th>Judul / Singkatan</th>
                                    <th>Isi Pesan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($replies as $reply)
                                <tr>
                                    <td><strong>{{ $reply->title }}</strong></td>
                                    <td><div class="text-wrap" style="max-width: 400px;">{{ $reply->content }}</div></td>
                                    <td class="text-end">
                                        <button @click="openEdit({{ $reply->toJson() }})" class="btn btn-sm btn-white text-primary me-2"><i class="fe fe-edit"></i></button>
                                        <form action="{{ route('admin.quick-replies.destroy', $reply->id) }}" method="POST" class="d-inline" onsubmit="event.preventDefault(); Swal.fire({ title: 'Hapus balasan cepat?', text: 'Balasan cepat tidak bisa dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) this.submit(); });">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center p-5 text-muted">Belum ada balasan cepat.</td>
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
        <div class="modal-dialog">
            <div class="modal-content">
                <form :action="isEdit ? '/admin/quick-replies/' + form.id : '/admin/quick-replies'" method="POST">
                    @csrf
                    <template x-if="isEdit">
                        <input type="hidden" name="_method" value="PUT">
                    </template>
                    <div class="modal-header">
                        <h5 class="modal-title" x-text="isEdit ? 'Edit Balasan' : 'Tambah Balasan'"></h5>
                        <button type="button" class="btn-close" @click="showModal = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="form-group mb-3">
                            <label class="form-label">Judul / Singkatan</label>
                            <input type="text" name="title" x-model="form.title" class="form-control" required>
                        </div>
                        <div class="form-group mb-3">
                            <label class="form-label">Isi Pesan</label>
                            <textarea name="content" x-model="form.content" class="form-control" rows="5" required></textarea>
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
