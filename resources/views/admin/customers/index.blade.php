@extends('layouts.admin_template')

@section('title', 'Data Pelanggan')

@section('content')
<div class="row" x-data="customerManagement()">
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

                <div class="table-responsive">
                    <table class="table table-center table-hover">
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
                                    <div class="d-flex align-items-center gap-2 mb-1">
                                        <span class="d-inline-block rounded-circle" 
                                              style="width: 8px; height: 8px; background-color: {{ $customer->is_online ? '#28a745' : '#adb5bd' }};"></span>
                                        <span class="badge rounded-pill fw-bold text-[10px] py-1 px-2 uppercase tracking-wider"
                                              style="
                                              @if($customer->is_blocked) background-color: #fee2e2; color: #dc2626; 
                                              @elseif($customer->current_status === 'active') background-color: #dcfce7; color: #15803d; 
                                              @elseif(in_array($customer->current_status, ['pending', 'queued'])) background-color: #fef9c3; color: #854d0e;
                                              @else background-color: #f1f5f9; color: #64748b; @endif
                                              ">
                                            @if($customer->is_blocked) 
                                                Diblokir 
                                            @elseif($customer->current_status === 'active') 
                                                Percakapan Aktif 
                                            @elseif(in_array($customer->current_status, ['pending', 'queued'])) 
                                                Menunggu 
                                            @else 
                                                Selesai 
                                            @endif
                                        </span>
                                    </div>
                                    <small class="text-[10px] font-bold text-muted uppercase tracking-tighter">
                                        {{ $customer->is_online ? 'Online' : 'Offline' }}
                                    </small>
                                </td>
                                <td class="text-end">
                                    <div class="d-flex justify-content-end">
                                        <form :id="'form-status-'+{{ $customer->id }}" action="{{ route('admin.customers.update', $customer->id) }}" method="POST" class="me-2" @submit.prevent="confirmStatus(event, {{ $customer->id }}, '{{ $customer->is_blocked ? 'Aktifkan' : 'Blokir' }}')">
                                            @csrf
                                            @method('PUT')
                                            <input type="hidden" name="is_blocked" value="{{ $customer->is_blocked ? 0 : 1 }}">
                                            <button type="submit" class="btn btn-sm btn-white {{ $customer->is_blocked ? 'text-success' : 'text-warning' }}" title="{{ $customer->is_blocked ? 'Buka Blokir' : 'Blokir' }}">
                                                <i class="fe {{ $customer->is_blocked ? 'fe-unlock' : 'fe-lock' }}"></i>
                                            </button>
                                        </form>

                                        <form :id="'form-delete-'+{{ $customer->id }}" action="{{ route('admin.customers.destroy', $customer->id) }}" method="POST" @submit.prevent="confirmDelete(event, {{ $customer->id }})">
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

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('customerManagement', () => ({
            confirmStatus(event, customerId, actionName) {
                Swal.fire({
                    title: actionName + ' pelanggan ini?',
                    text: 'Anda dapat merubahnya kembali nanti.',
                    icon: 'warning',
                    showCancelButton: true,
                    confirmButtonColor: '#3085d6',
                    cancelButtonColor: '#d33',
                    confirmButtonText: 'Ya, ' + actionName,
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('form-status-' + customerId).submit();
                    }
                });
            },
            
            confirmDelete(event, customerId) {
                Swal.fire({
                    title: 'Hapus permanen pelanggan ini?',
                    text: 'Data yang dihapus tidak dapat dikembalikan!',
                    icon: 'error',
                    showCancelButton: true,
                    confirmButtonColor: '#d33',
                    cancelButtonColor: '#6c757d',
                    confirmButtonText: 'Ya, Hapus!',
                    cancelButtonText: 'Batal'
                }).then((result) => {
                    if (result.isConfirmed) {
                        document.getElementById('form-delete-' + customerId).submit();
                    }
                });
            }
        }));
    });
</script>
@endpush
