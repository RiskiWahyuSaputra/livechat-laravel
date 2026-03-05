@extends('layouts.admin_template')

@section('title', 'Manajemen Role')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Daftar Role</h4>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.roles.create') }}" class="btn btn-primary btn-sm"><i class="fe fe-plus"></i> Tambah Role</a>
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

                <div class="table-responsive">
                    <table class="table table-center table-hover datatable">
                        <thead class="thead-light">
                            <tr>
                                <th>Nama Role</th>
                                <th>Slug</th>
                                <th>Keterangan</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($roles as $role)
                            <tr>
                                <td><strong>{{ $role->name }}</strong></td>
                                <td>{{ $role->slug }}</td>
                                <td>{{ $role->description ?? '-' }}</td>
                                <td class="text-end">
                                    <a href="{{ route('admin.roles.edit', $role->id) }}" class="btn btn-sm btn-white text-primary me-2"><i class="fe fe-edit"></i></a>
                                    <form action="{{ route('admin.roles.destroy', $role->id) }}" method="POST" class="d-inline" onsubmit="return confirm('Apakah Anda yakin ingin menghapus role ini?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center p-5 text-muted">Belum ada role.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-3">
                    {{ $roles->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
