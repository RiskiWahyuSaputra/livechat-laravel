@extends('layouts.admin_template')

@section('title', 'Dashboard')

@section('content')
<div class="row">
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100">
            <div class="card-body">
                <div class="home-user">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span><img src="{{ asset('admin/assets/img/icons/user.svg') }}" alt="img"></span>
                            <h6>Total Pelanggan</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['total_users'] }}</h3>
                        </div>
                        <div class="homegraph">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100">
            <div class="card-body">
                <div class="home-user home-provider">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span><img src="{{ asset('admin/assets/img/icons/user-circle.svg') }}" alt="img"></span>
                            <h6>Online Sekarang</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3 class="text-success">{{ $stats['online_users'] }}</h3>
                        </div>
                        <div class="homegraph">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100">
            <div class="card-body">
                <div class="home-user home-service">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span><img src="{{ asset('admin/assets/img/icons/service.svg') }}" alt="img"></span>
                            <h6>Pelanggan Hari Ini</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['today_users'] }}</h3>
                        </div>
                        <div class="homegraph">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100">
            <div class="card-body">
                <div class="home-user home-subscription">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span><img src="{{ asset('admin/assets/img/icons/money.svg') }}" alt="img"></span>
                            <h6>Pelanggan Kemarin</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['yesterday_users'] }}</h3>
                        </div>
                        <div class="homegraph">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Daftar Pelanggan</h4>
                    </div>
                    <div class="col-auto">
                        <div class="flex-center">
                            <a href="{{ route('admin.dashboard') }}" class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-outline-primary' }} me-2">Semua</a>
                            <a href="{{ route('admin.dashboard', ['filter' => 'online']) }}" class="btn btn-sm {{ request('filter') == 'online' ? 'btn-primary' : 'btn-outline-primary' }} me-2">Online</a>
                            <a href="{{ route('admin.dashboard', ['filter' => 'today']) }}" class="btn btn-sm {{ request('filter') == 'today' ? 'btn-primary' : 'btn-outline-primary' }} me-2">Hari Ini</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-center table-hover datatable">
                        <thead class="thead-light">
                            <tr>
                                <th>Pelanggan</th>
                                <th>Kontak & Instansi</th>
                                <th>Status</th>
                                <th>Bergabung Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td>
                                    <div class="table-profileimage">
                                        <div class="avatar avatar-sm me-2">
                                            <div class="avatar-title rounded-circle bg-primary text-white">
                                                {{ strtoupper(substr($customer->name, 0, 1)) }}
                                            </div>
                                        </div>
                                        <span>{{ $customer->name }}</span>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $customer->contact }}</div>
                                    <small class="text-muted">{{ $customer->origin }}</small>
                                </td>
                                <td>
                                    @if($customer->conversations()->where('status', 'active')->exists())
                                        <span class="badge bg-success-light">Sedang Chat</span>
                                    @else
                                        <span class="badge bg-light text-dark">Idle</span>
                                    @endif
                                </td>
                                <td>
                                    <div>{{ $customer->created_at->format('d M Y') }}</div>
                                    <small class="text-muted">{{ $customer->created_at->diffForHumans() }}</small>
                                </td>
                                <td class="text-end">
                                    <form action="{{ route('admin.user.destroy', $customer->id) }}" method="POST" onsubmit="event.preventDefault(); Swal.fire({ title: 'Hapus pelanggan ini?', text: 'Data tidak dapat dikembalikan!', icon: 'warning', showCancelButton: true, confirmButtonColor: '#d33', cancelButtonColor: '#6c757d', confirmButtonText: 'Ya, Hapus!', cancelButtonText: 'Batal' }).then((result) => { if (result.isConfirmed) this.submit(); });">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-white text-danger"><i class="fe fe-trash-2"></i></button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada data pelanggan.</td>
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
