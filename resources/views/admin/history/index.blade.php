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
                <!-- Filter Form -->
                <form action="{{ route('admin.history.index') }}" method="GET" class="mb-4 p-3 border rounded">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label for="search" class="form-label">Cari Pelanggan</label>
                            <input type="text" name="search" id="search" value="{{ request('search') }}" class="form-control" placeholder="Nama atau kontak...">
                        </div>
                        <div class="col-md-3">
                            <label for="category" class="form-label">Kategori Masalah</label>
                            <select name="category" id="category" class="form-select">
                                <option value="">Semua Kategori</option>
                                @foreach($problemCategories as $category)
                                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>{{ $category }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="date_range" class="form-label">Rentang Tanggal</label>
                            <input type="text" name="date_range" id="date_range" value="{{ request('date_range') }}" class="form-control" placeholder="Pilih tanggal...">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100 me-2">Filter</button>
                            <a href="{{ route('admin.history.index') }}" class="btn btn-secondary w-100">Reset</a>
                        </div>
                    </div>
                </form>

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
                                    {{ $chat->deleted_at->timezone('Asia/Jakarta')->translatedFormat('d F Y') }}
                                    <br><small class="text-muted">{{ $chat->deleted_at->timezone('Asia/Jakarta')->translatedFormat('H:i') }}</small>
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

@push('scripts')
<script>
    $(function() {
        $('#date_range').daterangepicker({
            opens: 'left',
            autoUpdateInput: false,
            locale: {
                format: 'DD/MM/YYYY',
                cancelLabel: 'Clear'
            }
        });

        $('#date_range').on('apply.daterangepicker', function(ev, picker) {
            $(this).val(picker.startDate.format('DD/MM/YYYY') + ' - ' + picker.endDate.format('DD/MM/YYYY'));
        });

        $('#date_range').on('cancel.daterangepicker', function(ev, picker) {
            $(this).val('');
        });
    });
</script>
@endpush

