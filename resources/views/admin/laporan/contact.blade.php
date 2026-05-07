@extends('layouts.admin_template')

@section('title', 'Laporan Contact')

@push('styles')
<style>
    :root {
        --lp-primary: #4f46e5; --lp-primary-light: #ede9fe;
        --lp-success: #10b981; --lp-success-light: #d1fae5;
        --lp-warning: #f59e0b; --lp-warning-light: #fef3c7;
        --lp-danger: #ef4444;  --lp-danger-light: #fee2e2;
        --lp-info: #06b6d4;    --lp-info-light: #cffafe;
        --lp-dark: #1f2937;    --lp-gray-50: #f9fafb;
        --lp-gray-100: #f3f4f6; --lp-gray-200: #e5e7eb;
        --lp-gray-500: #6b7280; --lp-gray-700: #374151;
        --lp-white: #ffffff;
    }
    .laporan-page { background: var(--lp-gray-100); min-height: 100vh; padding: 24px; }
    .lap-tab-nav {
        display: flex; gap: 4px; background: var(--lp-white);
        border-radius: 12px; padding: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08); margin-bottom: 24px;
        border: 1px solid var(--lp-gray-200); overflow-x: auto; flex-wrap: nowrap;
    }
    .lap-tab-item {
        display: flex; align-items: center; gap: 8px; padding: 10px 18px;
        border-radius: 8px; font-size: 13px; font-weight: 500;
        color: var(--lp-gray-500); text-decoration: none; white-space: nowrap; transition: all 0.2s;
    }
    .lap-tab-item:hover { background: var(--lp-gray-100); color: var(--lp-dark); text-decoration: none; }
    .lap-tab-item.active { background: var(--lp-primary); color: #fff; }
    .lp-title { font-size: 22px; font-weight: 700; color: var(--lp-dark); margin-bottom: 4px; }
    .lp-subtitle { color: var(--lp-gray-500); font-size: 13px; margin: 0; }

    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card { background: var(--lp-white); border-radius: 12px; padding: 20px; border: 1px solid var(--lp-gray-200); display: flex; align-items: center; gap: 16px; transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .stat-body .stat-value { font-size: 26px; font-weight: 700; color: var(--lp-dark); line-height: 1; }
    .stat-body .stat-label { font-size: 12px; color: var(--lp-gray-500); margin-top: 4px; }

    /* Cards */
    .lp-card { background: var(--lp-white); border-radius: 12px; border: 1px solid var(--lp-gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 20px; overflow: hidden; }
    .lp-card-header { padding: 16px 20px; border-bottom: 1px solid var(--lp-gray-200); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    .lp-card-title { font-size: 14px; font-weight: 600; color: var(--lp-dark); display: flex; align-items: center; gap: 8px; margin: 0; }
    .lp-card-title i { color: var(--lp-primary); }
    .lp-card-body { padding: 20px; }

    .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .chart-wrap { height: 220px; position: relative; }

    /* Customer Table */
    .cust-table { width: 100%; border-collapse: collapse; }
    .cust-table th { background: var(--lp-gray-50); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: var(--lp-gray-500); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--lp-gray-200); }
    .cust-table td { padding: 12px 16px; border-bottom: 1px solid var(--lp-gray-100); font-size: 13px; color: var(--lp-gray-700); }
    .cust-table tr:last-child td { border-bottom: none; }
    .cust-table tr:hover td { background: var(--lp-gray-50); }

    /* Export buttons */
    .export-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; text-decoration: none; transition: all 0.2s; }
    .export-excel { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .export-excel:hover { background: #10b981; color: #fff; text-decoration: none; }
    .export-pdf { background: var(--lp-danger-light); color: #991b1b; border: 1px solid #fca5a5; }
    .export-pdf:hover { background: #ef4444; color: #fff; text-decoration: none; }
    .refresh-btn { display: inline-flex; align-items: center; gap: 6px; padding: 8px 16px; border-radius: 8px; font-size: 13px; font-weight: 500; background: var(--lp-primary-light); color: var(--lp-primary); border: 1px solid #c4b5fd; cursor: pointer; transition: all 0.2s; }
    .refresh-btn:hover { background: var(--lp-primary); color: #fff; }

    /* Filter buttons */
    .filter-group { display: flex; gap: 6px; flex-wrap: wrap; }
    .filter-btn { padding: 6px 14px; border-radius: 8px; font-size: 12px; font-weight: 600; text-decoration: none; border: 1px solid var(--lp-gray-200); color: var(--lp-gray-500); background: var(--lp-white); transition: all 0.2s; }
    .filter-btn:hover { background: var(--lp-primary-light); color: var(--lp-primary); text-decoration: none; }
    .filter-btn.active { background: var(--lp-primary); color: #fff; border-color: var(--lp-primary); }

    /* Origin list */
    .origin-item { display: flex; align-items: center; justify-content: space-between; padding: 10px 14px; background: var(--lp-gray-50); border-radius: 10px; border: 1px solid var(--lp-gray-200); margin-bottom: 8px; }
    .origin-name { font-size: 13px; color: var(--lp-gray-700); font-weight: 500; }
    .origin-count { font-size: 13px; font-weight: 700; color: var(--lp-primary); }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } .grid-2 { grid-template-columns: 1fr; } .laporan-page { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="laporan-page">

    {{-- Tab Navigation --}}
    <nav class="lap-tab-nav">
        <a href="{{ route('admin.laporan.general') }}" class="lap-tab-item">
            <i class="fe fe-bar-chart-2"></i> General
        </a>
        <a href="{{ route('admin.laporan.performa-agen') }}" class="lap-tab-item">
            <i class="fe fe-users"></i> Performa Agen
        </a>
        <a href="{{ route('admin.laporan.performa-bot') }}" class="lap-tab-item">
            <i class="fe fe-cpu"></i> Performa Bot
        </a>
        <a href="{{ route('admin.laporan.contact') }}" class="lap-tab-item active">
            <i class="fe fe-user"></i> Contact
        </a>
    </nav>

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="lp-title"><i class="fe fe-user" style="color:var(--lp-primary);margin-right:8px;"></i>Laporan Contact</h1>
            <p class="lp-subtitle">Data dan analisis pelanggan yang terdaftar</p>
        </div>
        <div class="filter-group">
            <a href="{{ route('admin.laporan.contact', ['filter' => '1_month']) }}" class="filter-btn {{ $filter == '1_month' ? 'active' : '' }}">1 Bulan</a>
            <a href="{{ route('admin.laporan.contact', ['filter' => '1_year']) }}"  class="filter-btn {{ $filter == '1_year'  ? 'active' : '' }}">1 Tahun</a>
            <a href="{{ route('admin.laporan.contact') }}" class="filter-btn {{ !$filter ? 'active' : '' }}">Semua</a>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-primary-light);color:var(--lp-primary);">
                <i class="fe fe-users"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $totalCustomers }}</div>
                <div class="stat-label">Total Pelanggan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-success-light);color:var(--lp-success);">
                <i class="fe fe-wifi"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $onlineCustomers }}</div>
                <div class="stat-label">Sedang Online</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-info-light);color:var(--lp-info);">
                <i class="fe fe-user-plus"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $newThisMonth }}</div>
                <div class="stat-label">Baru Bulan Ini</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-danger-light);color:var(--lp-danger);">
                <i class="fe fe-user-x"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $blockedCustomers }}</div>
                <div class="stat-label">Diblokir</div>
            </div>
        </div>
    </div>

    {{-- Growth Chart + Origins --}}
    <div class="grid-2">
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-trending-up"></i> Pertumbuhan Pelanggan (7 Hari)</h5>
            </div>
            <div class="lp-card-body">
                <div class="chart-wrap"><canvas id="growthChart"></canvas></div>
            </div>
        </div>
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-map-pin"></i> Top Asal Instansi</h5>
            </div>
            <div class="lp-card-body">
                @forelse($origins as $origin)
                <div class="origin-item">
                    <span class="origin-name">{{ $origin->origin ?: 'Tidak Diketahui' }}</span>
                    <span class="origin-count">{{ $origin->count }}</span>
                </div>
                @empty
                <p style="color:var(--lp-gray-500);text-align:center;padding:20px 0;">Belum ada data</p>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Customer Table --}}
    <div class="lp-card">
        <div class="lp-card-header">
            <h5 class="lp-card-title"><i class="fe fe-list"></i> Daftar Pelanggan</h5>
            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span class="badge bg-info" id="last-update" style="font-size:11px;">Update: {{ now()->format('H:i:s') }}</span>
                <a href="{{ route('admin.reports.export.excel', ['filter' => $filter]) }}" class="export-btn export-excel">
                    <i class="fe fe-file-text"></i> Excel
                </a>
                <a href="{{ route('admin.reports.export.pdf', ['filter' => $filter]) }}" class="export-btn export-pdf">
                    <i class="fe fe-file"></i> PDF
                </a>
                <button class="refresh-btn" id="refresh-data">
                    <i class="fe fe-refresh-cw"></i> Refresh
                </button>
            </div>
        </div>
        <div style="padding:0;">
            <div class="table-responsive">
                <table class="cust-table" id="customers-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nama Pelanggan</th>
                            <th>Kontak</th>
                            <th>Asal / Instansi</th>
                            <th>Status</th>
                            <th>Tgl Daftar</th>
                        </tr>
                    </thead>
                    <tbody id="customer-data">
                        @forelse($customers as $customer)
                        <tr>
                            <td><span style="font-family:monospace;font-size:12px;color:var(--lp-primary);">CUST-{{ str_pad($customer->id, 4, '0', STR_PAD_LEFT) }}</span></td>
                            <td><strong>{{ $customer->name }}</strong></td>
                            <td>{{ $customer->contact ?: '-' }}</td>
                            <td>{{ $customer->origin ?: '-' }}</td>
                            <td>
                                <span class="badge {{ $customer->status_class }}" style="font-size:11px;">
                                    {{ $customer->status_label }}
                                </span>
                            </td>
                            <td style="color:var(--lp-gray-500);">{{ $customer->created_at->format('d M Y H:i') }}</td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="6" style="text-align:center;padding:40px;color:var(--lp-gray-500);">
                                <i class="fe fe-inbox" style="font-size:32px;display:block;margin-bottom:8px;opacity:0.4;"></i>
                                Tidak ada data untuk periode ini
                            </td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Growth Chart
new Chart(document.getElementById('growthChart'), {
    type: 'bar',
    data: {
        labels: @json($growthLabels),
        datasets: [{
            label: 'Pelanggan Baru',
            data: @json($growthData),
            backgroundColor: 'rgba(79,70,229,0.7)',
            borderRadius: 6,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
    }
});

// DataTable
$(document).ready(function() {
    const table = $('#customers-table').DataTable({
        order: [[5, 'desc']],
        language: {
            emptyTable: 'Tidak ada data.',
            info: 'Menampilkan _START_ - _END_ dari _TOTAL_ entri',
            infoEmpty: '0 entri', infoFiltered: '(filter dari _MAX_)',
            lengthMenu: 'Tampilkan _MENU_ entri',
            search: 'Cari:', zeroRecords: 'Data tidak ditemukan',
            paginate: { first: '«', last: '»', next: '›', previous: '‹' }
        }
    });

    function refreshData() {
        const filter = '{{ $filter }}';
        const url = '{{ route("admin.laporan.contact.api") }}' + (filter ? '?filter=' + filter : '');
        $('#refresh-data i').addClass('fa-spin');
        $.ajax({
            url, type: 'GET', dataType: 'json',
            success(data) {
                table.clear();
                data.forEach(c => {
                    table.row.add([
                        `<span style="font-family:monospace;font-size:12px;color:#4f46e5;">${c.custom_id}</span>`,
                        `<strong>${c.name}</strong>`,
                        c.contact || '-',
                        c.origin || '-',
                        `<span class="badge ${c.status_class}" style="font-size:11px;">${c.status}</span>`,
                        c.created_at
                    ]);
                });
                table.draw();
                document.getElementById('last-update').textContent = 'Update: ' + new Date().toLocaleTimeString('id-ID');
                $('#refresh-data i').removeClass('fa-spin');
            },
            error() { $('#refresh-data i').removeClass('fa-spin'); }
        });
    }

    $('#refresh-data').on('click', refreshData);
    setInterval(refreshData, 60000);
});
</script>
@endpush
