@extends('layouts.admin_template')

@section('title', 'Laporan Pelanggan')

@section('content')
<div class="page-header">
    <div class="row align-items-center">
        <div class="col">
            <h3 class="page-title">Laporan Pelanggan</h3>
            <ul class="breadcrumb">
                <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Dashboard</a></li>
                <li class="breadcrumb-item active">Laporan</li>
            </ul>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Filter Laporan</h4>
                    </div>
                    <div class="col-auto">
                        <div class="btn-group">
                            <a href="{{ route('admin.reports.index', ['filter' => '1_month']) }}" class="btn btn-sm {{ $filter == '1_month' ? 'btn-primary' : 'btn-outline-primary' }}">1 Bulan Terakhir</a>
                            <a href="{{ route('admin.reports.index', ['filter' => '1_year']) }}" class="btn btn-sm {{ $filter == '1_year' ? 'btn-primary' : 'btn-outline-primary' }}">1 Tahun Terakhir</a>
                            <a href="{{ route('admin.reports.index') }}" class="btn btn-sm {{ !$filter ? 'btn-primary' : 'btn-outline-primary' }}">Semua Waktu</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <div class="row mb-4">
                    <div class="col">
                        <div class="d-flex gap-2">
                            <a href="{{ route('admin.reports.export.excel', ['filter' => $filter]) }}" class="btn btn-success">
                                <i class="fe fe-file-text"></i> Ekspor Excel
                            </a>
                            <a href="{{ route('admin.reports.export.pdf', ['filter' => $filter]) }}" class="btn btn-danger">
                                <i class="fe fe-file"></i> Ekspor PDF
                            </a>
                            <button id="refresh-data" class="btn btn-primary">
                                <i class="fe fe-refresh-cw"></i> Segarkan Data
                            </button>
                        </div>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center">
                            <span class="badge bg-info me-2" id="last-update">Update terakhir: {{ now()->format('H:i:s') }}</span>
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" id="auto-refresh" checked>
                                <label class="form-check-label" for="auto-refresh">Auto-Refresh (30s)</label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-center table-hover" id="customers-table">
                        <thead class="thead-light">
                            <tr>
                                <th>ID</th>
                                <th>Nama Pelanggan</th>
                                <th>Kontak</th>
                                <th>Asal / Instansi</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody id="customer-data">
                            @forelse($customers as $customer)
                            <tr>
                                <td>CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</td>
                                <td><strong>{{ $customer->name }}</strong></td>
                                <td>{{ $customer->contact }}</td>
                                <td>{{ $customer->origin }}</td>
                                <td>
                                    <span class="badge {{ $customer->status_class }}">
                                        {{ $customer->status_label }}
                                    </span>
                                </td>
                                <td>{{ $customer->created_at->format('d M Y H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center">Tidak ada data untuk periode ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let table = $('#customers-table').DataTable({
        "order": [[ 5, "desc" ]],
        "language": {
            "emptyTable": "Tidak ada data untuk periode ini.",
            "info": "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
            "infoEmpty": "Menampilkan 0 sampai 0 dari 0 entri",
            "infoFiltered": "(disaring dari _MAX_ total entri)",
            "lengthMenu": "Tampilkan _MENU_ entri",
            "loadingRecords": "Memuat...",
            "processing": "Memproses...",
            "search": "Cari:",
            "zeroRecords": "Tidak ditemukan data yang sesuai",
            "paginate": {
                "first": "Pertama",
                "last": "Terakhir",
                "next": "Selanjutnya",
                "previous": "Sebelumnya"
            }
        }
    });

    function refreshData() {
        const filter = '{{ $filter }}';
        const url = '{{ route("admin.reports.api-data") }}' + (filter ? '?filter=' + filter : '');

        $('#refresh-data i').addClass('fa-spin');

        $.ajax({
            url: url,
            type: 'GET',
            dataType: 'json',
            success: function(data) {
                table.clear();
                
                if (data.length > 0) {
                    data.forEach(function(c) {
                        table.row.add([
                            c.custom_id,
                            `<strong>${c.name}</strong>`,
                            c.contact,
                            c.origin || '-',
                            `<span class="badge ${c.status_class}">${c.status}</span>`,
                            c.created_at
                        ]);
                    });
                }
                
                table.draw();
                $('#last-update').text('Update terakhir: ' + new Date().toLocaleTimeString('id-ID'));
                $('#refresh-data i').removeClass('fa-spin');
            },
            error: function() {
                console.error('Gagal mengambil data laporan');
                $('#refresh-data i').removeClass('fa-spin');
            }
        });
    }

    $('#refresh-data').on('click', function() {
        refreshData();
    });

    // Auto refresh every 30 seconds if enabled
    setInterval(function() {
        if ($('#auto-refresh').is(':checked')) {
            refreshData();
        }
    }, 30000);
});
</script>
@endpush
