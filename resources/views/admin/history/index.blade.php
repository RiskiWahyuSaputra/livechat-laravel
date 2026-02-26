@extends('layouts.admin_template')

@section('title', 'Riwayat & Arsip')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Riwayat Chat & Arsip</h4>
                    </div>
                    <div class="col-auto">
                        <form action="{{ route('admin.history.index') }}" method="GET" class="d-flex">
                            <input type="text" name="search" value="{{ request('search') }}" class="form-control form-control-sm" placeholder="Cari pelanggan...">
                            <button type="submit" class="btn btn-sm btn-primary ms-2"><i class="fe fe-search"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-center table-hover datatable">
                        <thead class="thead-light">
                            <tr>
                                <th>Pelanggan</th>
                                <th>Agen</th>
                                <th>Kategori</th>
                                <th>Selesai Pada</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($archives as $chat)
                            <tr>
                                <td>
                                    <div class="table-profileimage">
                                        <div class="avatar avatar-sm me-2">
                                            <div class="avatar-title rounded-circle bg-secondary text-white">
                                                {{ strtoupper(substr($chat->customer->name ?? '?', 0, 1)) }}
                                            </div>
                                        </div>
                                        <span>
                                            <strong>{{ $chat->customer->name ?? 'Dihapus' }}</strong>
                                            <br><small class="text-muted">{{ $chat->customer->contact ?? '-' }}</small>
                                        </span>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $chat->admin->username ?? 'Sistem' }}
                                    </span>
                                </td>
                                <td>
                                    @if($chat->problem_category)
                                        <span class="badge bg-info-light">{{ $chat->problem_category }}</span>
                                    @else
                                        <span class="text-muted">-</span>
                                    @endif
                                </td>
                                <td>
                                    {{ $chat->deleted_at->format('d M Y') }}
                                    <br><small class="text-muted">{{ $chat->deleted_at->format('H:i') }} WIB</small>
                                </td>
                                <td class="text-end">
                                    <a href="{{ route('admin.history.show', $chat->id) }}" class="btn btn-sm btn-white text-primary">
                                        <i class="fe fe-eye"></i>
                                    </a>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center p-5 text-muted">Belum ada riwayat percakapan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="mt-4">
                    {{ $archives->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
