@extends('layouts.admin_template')

@section('title', 'Libur Nasional')

@section('content')
<div x-data="{
    showAdd: false,
    form: { date: '', name: '' },
    openAdd() { this.showAdd = true; this.form = { date: '', name: '' }; }
}">
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-header">
                    <div class="row align-items-center">
                        <div class="col">
                            <a href="{{ route('admin.flows.index') }}" class="text-muted small"><i class="fe fe-arrow-left me-1"></i>Kembali ke Flow</a>
                            <h4 class="card-title mt-1">Manajemen Libur Nasional</h4>
                            <p class="text-muted mb-0 small">
                                Tanggal yang terdaftar di sini akan dianggap sebagai hari libur nasional.
                                Layanan <strong>CS Voucher</strong> dan <strong>CS Undercutting Price</strong> tutup pada hari libur nasional.
                            </p>
                        </div>
                        <div class="col-auto">
                            <button @click="openAdd()" class="btn btn-primary btn-sm">
                                <i class="fe fe-plus"></i> Tambah Tanggal Libur
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        {{ session('success') }} <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                    @endif
                    @if($errors->any())
                    <div class="alert alert-danger">
                        <ul class="mb-0">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
                    </div>
                    @endif

                    <div class="table-responsive">
                        <table class="table table-center table-hover">
                            <thead class="thead-light">
                                <tr>
                                    <th>Tanggal</th>
                                    <th>Nama / Keterangan</th>
                                    <th>Hari</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($holidays as $h)
                                <tr>
                                    <td><strong>{{ $h->date->format('d M Y') }}</strong></td>
                                    <td>{{ $h->name ?? '-' }}</td>
                                    <td>{{ $h->date->isoFormat('dddd') }}</td>
                                    <td class="text-end">
                                        <form action="{{ route('admin.holidays.destroy', $h) }}" method="POST" class="d-inline"
                                              onsubmit="event.preventDefault(); Swal.fire({ title: 'Hapus tanggal libur?', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((r) => { if (r.isConfirmed) this.submit(); });">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted py-4">
                                        Belum ada tanggal libur. <a href="#" @click.prevent="openAdd()">Tambah sekarang</a>.
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

    {{-- Add Holiday Modal --}}
    <div x-show="showAdd" x-cloak class="modal fade show d-block" tabindex="-1" style="background:rgba(0,0,0,.5)">
        <div class="modal-dialog">
            <div class="modal-content">
                <form action="{{ route('admin.holidays.store') }}" method="POST">
                    @csrf
                    <div class="modal-header">
                        <h5 class="modal-title">Tambah Tanggal Libur Nasional</h5>
                        <button type="button" class="btn-close" @click="showAdd = false"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label class="form-label">Tanggal</label>
                            <input type="date" name="date" x-model="form.date" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Nama / Keterangan <small class="text-muted">(opsional)</small></label>
                            <input type="text" name="name" x-model="form.name" class="form-control" placeholder="e.g. Hari Kemerdekaan RI">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" @click="showAdd = false">Batal</button>
                        <button type="submit" class="btn btn-primary">Simpan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
