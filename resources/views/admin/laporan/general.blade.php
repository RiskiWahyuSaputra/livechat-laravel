@extends('layouts.admin_template')

@section('title', 'Laporan General')

@push('styles')
<style>
    :root {
        --lp-primary: #4f46e5;
        --lp-primary-light: #ede9fe;
        --lp-success: #10b981;
        --lp-success-light: #d1fae5;
        --lp-warning: #f59e0b;
        --lp-warning-light: #fef3c7;
        --lp-danger: #ef4444;
        --lp-danger-light: #fee2e2;
        --lp-info: #06b6d4;
        --lp-info-light: #cffafe;
        --lp-dark: #1f2937;
        --lp-gray-50: #f9fafb;
        --lp-gray-100: #f3f4f6;
        --lp-gray-200: #e5e7eb;
        --lp-gray-500: #6b7280;
        --lp-gray-700: #374151;
        --lp-white: #ffffff;
    }

    .laporan-page { background: var(--lp-gray-100); min-height: 100vh; padding: 24px; }

    /* Tab Nav */
    .lap-tab-nav {
        display: flex;
        gap: 4px;
        background: var(--lp-white);
        border-radius: 12px;
        padding: 6px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        margin-bottom: 24px;
        border: 1px solid var(--lp-gray-200);
        overflow-x: auto;
        flex-wrap: nowrap;
    }
    .lap-tab-item {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        color: var(--lp-gray-500);
        text-decoration: none;
        white-space: nowrap;
        transition: all 0.2s;
    }
    .lap-tab-item:hover { background: var(--lp-gray-100); color: var(--lp-dark); text-decoration: none; }
    .lap-tab-item.active { background: var(--lp-primary); color: #fff; }
    .lap-tab-item i { font-size: 15px; }

    /* Page Header */
    .lp-header { margin-bottom: 24px; }
    .lp-title { font-size: 22px; font-weight: 700; color: var(--lp-dark); margin-bottom: 4px; }
    .lp-subtitle { color: var(--lp-gray-500); font-size: 13px; }

    /* Stats Grid */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--lp-white);
        border-radius: 12px;
        padding: 20px;
        border: 1px solid var(--lp-gray-200);
        display: flex;
        align-items: center;
        gap: 16px;
        transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .stat-icon {
        width: 48px; height: 48px; border-radius: 12px;
        display: flex; align-items: center; justify-content: center;
        font-size: 20px; flex-shrink: 0;
    }
    .stat-body .stat-value { font-size: 26px; font-weight: 700; color: var(--lp-dark); line-height: 1; }
    .stat-body .stat-label { font-size: 12px; color: var(--lp-gray-500); margin-top: 4px; }
    .stat-body .stat-sub { font-size: 11px; color: var(--lp-success); font-weight: 600; margin-top: 2px; }

    /* Cards */
    .lp-card {
        background: var(--lp-white);
        border-radius: 12px;
        border: 1px solid var(--lp-gray-200);
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        margin-bottom: 20px;
    }
    .lp-card-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--lp-gray-200);
        display: flex; align-items: center; justify-content: space-between;
    }
    .lp-card-title {
        font-size: 14px; font-weight: 600; color: var(--lp-dark);
        display: flex; align-items: center; gap: 8px; margin: 0;
    }
    .lp-card-title i { color: var(--lp-primary); }
    .lp-card-body { padding: 20px; }

    /* Two col grid */
    .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .chart-wrap { height: 260px; position: relative; }

    /* Satisfaction */
    .rating-big { font-size: 42px; font-weight: 700; color: var(--lp-dark); }
    .star-filled { color: #fbbf24; }
    .star-empty { color: var(--lp-gray-200); }

    /* Channel pills */
    .channel-list { display: flex; flex-direction: column; gap: 12px; }
    .channel-item {
        display: flex; align-items: center; justify-content: space-between;
        padding: 12px 16px; background: var(--lp-gray-50);
        border-radius: 10px; border: 1px solid var(--lp-gray-200);
    }
    .channel-name { font-weight: 600; font-size: 13px; color: var(--lp-dark); display: flex; align-items: center; gap: 8px; }
    .channel-count { font-size: 20px; font-weight: 700; color: var(--lp-primary); }

    /* Date filter */
    .date-filter-form {
        display: flex; align-items: center; gap: 8px; flex-wrap: wrap;
    }
    .date-filter-form input[type=date] {
        padding: 8px 12px; border: 1px solid var(--lp-gray-200);
        border-radius: 8px; font-size: 13px; color: var(--lp-dark);
    }
    .date-filter-form button {
        padding: 8px 16px; background: var(--lp-primary);
        color: #fff; border: none; border-radius: 8px;
        font-size: 13px; font-weight: 500; cursor: pointer;
        display: flex; align-items: center; gap: 6px;
    }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .grid-2, .grid-equal { grid-template-columns: 1fr; }
        .laporan-page { padding: 16px; }
    }
    @media (max-width: 480px) { .stats-grid { grid-template-columns: 1fr; } }
</style>
@endpush

@section('content')
<div class="laporan-page">

    {{-- Header --}}
    <div class="lp-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="lp-title"><i class="fe fe-bar-chart-2" style="color:var(--lp-primary);margin-right:8px;"></i>Laporan General</h1>
            <p class="lp-subtitle">Ringkasan performa livechat keseluruhan sistem</p>
        </div>
        <form method="GET" action="{{ route('admin.laporan.general') }}" class="date-filter-form">
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
            <span style="color:var(--lp-gray-500);">→</span>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
            <button type="submit"><i class="fe fe-filter"></i> Filter</button>
        </form>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-primary-light);color:var(--lp-primary);">
                <i class="fe fe-message-square"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $overview['total_conversations'] }}</div>
                <div class="stat-label">Total Percakapan</div>
                <div class="stat-sub">+{{ $overview['today_conversations'] }} hari ini</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-success-light);color:var(--lp-success);">
                <i class="fe fe-check-circle"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $metrics['completion_rate'] }}%</div>
                <div class="stat-label">Completion Rate</div>
                <div class="stat-sub">{{ $metrics['closed'] }} diselesaikan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-info-light);color:var(--lp-info);">
                <i class="fe fe-users"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $overview['total_customers'] }}</div>
                <div class="stat-label">Total Pelanggan</div>
                <div class="stat-sub">+{{ $overview['new_customers_7days'] }} 7 hari terakhir</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-warning-light);color:var(--lp-warning);">
                <i class="fe fe-clock"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $metrics['avg_duration_minutes'] }}m</div>
                <div class="stat-label">Rata-rata Durasi Chat</div>
                <div class="stat-sub">Per percakapan</div>
            </div>
        </div>
    </div>

    {{-- Charts Row --}}
    <div class="grid-2">
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-trending-up"></i> Tren Percakapan (7 Hari)</h5>
            </div>
            <div class="lp-card-body">
                <div class="chart-wrap"><canvas id="trendsChart"></canvas></div>
            </div>
        </div>
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-layers"></i> Tipe Percakapan</h5>
            </div>
            <div class="lp-card-body">
                <div class="channel-list">
                    @php
                        $typeIcons  = ['Customer Chat' => 'fe-message-circle', 'Internal Chat' => 'fe-users'];
                        $typeColors = ['Customer Chat' => 'var(--lp-primary)',  'Internal Chat' => 'var(--lp-info)'];
                    @endphp
                    @foreach($typeBreakdown as $type => $count)
                    <div class="channel-item">
                        <span class="channel-name">
                            <i class="fe {{ $typeIcons[$type] ?? 'fe-message-square' }}" style="color:{{ $typeColors[$type] ?? 'var(--lp-primary)' }};"></i>
                            {{ $type }}
                        </span>
                        <span class="channel-count">{{ $count }}</span>
                    </div>
                    @endforeach
                </div>
                {{-- Type Donut --}}
                <div style="height:160px;margin-top:16px;position:relative;"><canvas id="typeChart"></canvas></div>
            </div>
        </div>
    </div>

    {{-- Peak Hours & Satisfaction --}}
    <div class="grid-equal">
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-clock"></i> Jam Sibuk (30 Hari Terakhir)</h5>
            </div>
            <div class="lp-card-body">
                <div class="chart-wrap"><canvas id="peakChart"></canvas></div>
            </div>
        </div>
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-heart"></i> Kepuasan Pelanggan</h5>
            </div>
            <div class="lp-card-body" style="text-align:center;">
                @if($customerSatisfaction['has_data'])
                    <div class="rating-big">{{ $customerSatisfaction['average_rating'] }}</div>
                    <div style="display:flex;justify-content:center;gap:4px;margin:8px 0;">
                        @for($i=1;$i<=5;$i++)
                            <i class="fe fe-star {{ $i <= floor($customerSatisfaction['average_rating']) ? 'star-filled' : 'star-empty' }}" style="font-size:20px;"></i>
                        @endfor
                    </div>
                    <p style="color:var(--lp-gray-500);font-size:13px;">{{ $customerSatisfaction['total_ratings'] }} ulasan</p>
                    <div style="display:flex;justify-content:center;gap:12px;margin-top:12px;">
                        @foreach([5,4,3,2,1] as $star)
                        <div style="text-align:center;">
                            <div style="font-weight:700;color:var(--lp-dark);">{{ $customerSatisfaction['distribution'][$star] ?? 0 }}</div>
                            <small style="color:#fbbf24;">{{ str_repeat('★', $star) }}</small>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div style="padding:40px 0;color:var(--lp-gray-500);">
                        <i class="fe fe-star" style="font-size:40px;opacity:0.3;display:block;margin-bottom:12px;"></i>
                        Belum ada data rating
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- Status Distribution + Category --}}
    <div class="grid-equal">
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-pie-chart"></i> Distribusi Status Percakapan</h5>
            </div>
            <div class="lp-card-body">
                <div style="display:flex;align-items:center;gap:24px;flex-wrap:wrap;">
                    <div style="width:160px;height:160px;flex-shrink:0;">
                        <canvas id="statusChart"></canvas>
                    </div>
                    <div style="display:flex;flex-direction:column;gap:10px;flex:1;">
                        @php
                            $statusInfo = [
                                'active'  => ['label'=>'Aktif',    'color'=>'#4f46e5'],
                                'pending' => ['label'=>'Pending',   'color'=>'#f59e0b'],
                                'queued'  => ['label'=>'Antrean',   'color'=>'#06b6d4'],
                                'closed'  => ['label'=>'Selesai',   'color'=>'#10b981'],
                                'bot'     => ['label'=>'Bot',       'color'=>'#8b5cf6'],
                            ];
                        @endphp
                        @foreach($metrics['status_distribution'] as $status => $count)
                        @php $info = $statusInfo[$status] ?? ['label'=>ucfirst($status),'color'=>'#9ca3af']; @endphp
                        <div style="display:flex;align-items:center;justify-content:space-between;padding:8px 12px;border-radius:8px;background:var(--lp-gray-50);border:1px solid var(--lp-gray-200);">
                            <div style="display:flex;align-items:center;gap:8px;">
                                <div style="width:10px;height:10px;border-radius:3px;background:{{ $info['color'] }};flex-shrink:0;"></div>
                                <small style="color:var(--lp-gray-700);">{{ $info['label'] }}</small>
                            </div>
                            <strong style="color:var(--lp-dark);font-size:14px;">{{ $count }}</strong>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>

        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-tag"></i> Kategori Pertanyaan (Top 5)</h5>
            </div>
            <div class="lp-card-body">
                @if(count($categoryBreakdown) > 0)
                @php
                    $catTotal = array_sum($categoryBreakdown);
                    $catPalette = ['#4f46e5','#10b981','#f59e0b','#ef4444','#06b6d4'];
                @endphp
                <div style="display:flex;flex-direction:column;gap:12px;">
                    @php $catIdx = 0; @endphp
                    @foreach($categoryBreakdown as $catName => $catCount)
                    @php
                        $catPct = $catTotal > 0 ? round(($catCount / $catTotal) * 100, 1) : 0;
                        $catColor = $catPalette[$catIdx % count($catPalette)];
                        $catIdx++;
                    @endphp
                    <div>
                        <div style="display:flex;justify-content:space-between;font-size:12px;margin-bottom:5px;">
                            <span style="color:var(--lp-gray-700);font-weight:500;">{{ Str::limit($catName, 30) }}</span>
                            <span style="color:var(--lp-gray-500);">{{ $catCount }} ({{ $catPct }}%)</span>
                        </div>
                        <div style="height:16px;background:var(--lp-gray-100);border-radius:6px;overflow:hidden;">
                            <div style="height:100%;width:{{ max(3, $catPct) }}%;background:{{ $catColor }};border-radius:6px;"></div>
                        </div>
                    </div>
                    @endforeach
                </div>
                @else
                <div style="text-align:center;padding:30px 0;color:var(--lp-gray-500);">
                    <i class="fe fe-tag" style="font-size:32px;opacity:0.3;display:block;margin-bottom:8px;"></i>
                    Belum ada kategori pertanyaan
                </div>
                @endif
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const chartDefaults = {
    responsive: true, maintainAspectRatio: false,
    plugins: { legend: { display: false } }
};

// Trends Chart
new Chart(document.getElementById('trendsChart'), {
    type: 'line',
    data: {
        labels: @json($trends['labels']),
        datasets: [{
            label: 'Percakapan',
            data: @json($trends['data']),
            borderColor: '#4f46e5',
            backgroundColor: 'rgba(79,70,229,0.08)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#4f46e5', pointRadius: 4,
        }]
    },
    options: { ...chartDefaults, scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } } }
});

// Peak Hours Chart
new Chart(document.getElementById('peakChart'), {
    type: 'bar',
    data: {
        labels: @json($peakHours['labels']),
        datasets: [{
            label: 'Percakapan',
            data: @json($peakHours['data']),
            backgroundColor: 'rgba(79,70,229,0.7)',
            borderRadius: 4,
        }]
    },
    options: { ...chartDefaults, scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false }, ticks: { maxTicksLimit: 12 } } } }
});

// Status Donut
const statusData = @json(array_values($metrics['status_distribution']));
const statusLabels = @json(array_keys($metrics['status_distribution']));
new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels,
        datasets: [{ data: statusData, backgroundColor: ['#4f46e5','#f59e0b','#06b6d4','#10b981','#8b5cf6'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '65%', plugins: { legend: { display: false } } }
});

// Type Donut (Customer vs Internal)
const typeData = @json(array_values($typeBreakdown));
const typeLabels = @json(array_keys($typeBreakdown));
new Chart(document.getElementById('typeChart'), {
    type: 'doughnut',
    data: {
        labels: typeLabels,
        datasets: [{ data: typeData, backgroundColor: ['#4f46e5','#06b6d4'], borderWidth: 2, borderColor: '#fff' }]
    },
    options: { responsive: true, maintainAspectRatio: false, cutout: '60%', plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } } }
});
</script>
@endpush
