@extends('layouts.admin_template')

@section('title', 'Data Pelanggan')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Manajemen Data Pelanggan</h4>
                    </div>
                    <div class="col-auto">
                        <div class="flex-center">
                            <a href="{{ route('admin.customers.index') }}" class="btn btn-sm {{ !request('status') ? 'btn-primary' : 'btn-outline-primary' }} me-2">Semua</a>
                            <a href="{{ route('admin.customers.index', ['status' => 'active']) }}" class="btn btn-sm {{ request('status') == 'active' ? 'btn-primary' : 'btn-outline-primary' }} me-2">Aktif</a>
                            <a href="{{ route('admin.customers.index', ['status' => 'blocked']) }}" class="btn btn-sm {{ request('status') == 'blocked' ? 'btn-primary' : 'btn-outline-primary' }} me-2">Diblokir</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col-md-4">
                        <form action="{{ route('admin.customers.index') }}" method="GET" class="d-flex">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control" placeholder="Cari nama, kontak, atau instansi...">
                            <button type="submit" class="btn btn-primary ms-2"><i class="fe fe-search"></i></button>
                        </form>
                    </div>
                </div>

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
                                <th>Pelanggan</th>
                                <th>Kontak & Instansi</th>
                                <th>Status Akses</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <div class="table-profileimage">
                                        <div class="avatar avatar-sm me-2">
                                            <div class="avatar-title rounded-circle {{ $customer->is_blocked ? 'bg-secondary' : 'bg-primary' }} text-white">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </div>
                                        </div>
                                        <span class="{{ $customer->is_blocked ? 'text-decoration-line-through text-muted' : '' }}">
                                            <strong>{{ $customer->name }}</strong>
                                            <br><small class="text-muted">ID: CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $customer->contact }}</div>
                                    <small class="text-muted">{{ $customer->origin }}</small>
                                </td>
                                <td>
                                    @if($customer->is_blocked)
                                        <span class="badge bg-danger-light">Diblokir</span>
                                    @else
                                        <span class="badge bg-success-light">Aktif</span>
                                    @endif
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <form action="{{ route('admin.customers.update', $customer->id) }}" method="POST" class="me-2" onsubmit="return confirm('{{ $customer->is_blocked ? 'Aktifkan' : 'Blokir' }} pelanggan ini?');">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_blocked" value="{{ $customer->is_blocked ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm btn-white {{ $customer->is_blocked ? 'text-success' : 'text-warning' }}" title="{{ $customer->is_blocked ? 'Buka Blokir' : 'Blokir' }}">
                                                <i class="fe {{ $customer->is_blocked ? 'fe-unlock' : 'fe-lock' }}"></i>
                                            </button>
                                        </form>

                                        <form action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" onsubmit="return confirm('Hapus permanen pelanggan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger" title="Hapus">
                                                <i class="fe fe-trash-2"></i>
                                            </button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="4" class="text-center p-5 text-muted">Data pelanggan tidak ditemukan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
