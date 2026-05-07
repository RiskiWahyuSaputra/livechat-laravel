@extends('layouts.admin_template')

@section('title', 'Laporan Performa Bot')

@push('styles')
<style>
    :root {
        --lp-primary: #4f46e5; --lp-primary-light: #ede9fe;
        --lp-success: #10b981; --lp-success-light: #d1fae5;
        --lp-warning: #f59e0b; --lp-warning-light: #fef3c7;
        --lp-danger: #ef4444;  --lp-danger-light: #fee2e2;
        --lp-info: #06b6d4;    --lp-info-light: #cffafe;
        --lp-purple: #8b5cf6;  --lp-purple-light: #ede9fe;
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
    .lp-card-header { padding: 16px 20px; border-bottom: 1px solid var(--lp-gray-200); display: flex; align-items: center; justify-content: space-between; }
    .lp-card-title { font-size: 14px; font-weight: 600; color: var(--lp-dark); display: flex; align-items: center; gap: 8px; margin: 0; }
    .lp-card-title i { color: var(--lp-primary); }
    .lp-card-body { padding: 20px; }

    .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .grid-equal { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .chart-wrap { height: 250px; position: relative; }

    /* Ratio Display */
    .ratio-display { display: flex; align-items: center; gap: 0; border-radius: 10px; overflow: hidden; height: 60px; }
    .ratio-bot { background: linear-gradient(135deg, #8b5cf6, #6d28d9); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; transition: flex 0.5s; }
    .ratio-agent { background: linear-gradient(135deg, #4f46e5, #3730a3); color: #fff; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; transition: flex 0.5s; }

    /* Category bars */
    .cat-list { display: flex; flex-direction: column; gap: 12px; }
    .cat-item { display: flex; flex-direction: column; gap: 6px; }
    .cat-row { display: flex; justify-content: space-between; font-size: 13px; }
    .cat-name { color: var(--lp-gray-700); font-weight: 500; }
    .cat-count { color: var(--lp-gray-500); }
    .prog-bar { height: 18px; background: var(--lp-gray-100); border-radius: 6px; overflow: hidden; }
    .prog-fill { height: 100%; border-radius: 6px; display: flex; align-items: center; padding: 0 8px; font-size: 11px; font-weight: 600; color: #fff; min-width: 28px; }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } .grid-2, .grid-equal { grid-template-columns: 1fr; } .laporan-page { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="laporan-page">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="lp-title"><i class="fe fe-cpu" style="color:var(--lp-primary);margin-right:8px;"></i>Laporan Performa Bot</h1>
            <p class="lp-subtitle">Analisis efisiensi bot dalam menangani percakapan</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-purple-light);color:var(--lp-purple);">
                <i class="fe fe-cpu"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $botTotal }}</div>
                <div class="stat-label">Ditangani Bot</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-success-light);color:var(--lp-success);">
                <i class="fe fe-check-circle"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $botCompletionRate }}%</div>
                <div class="stat-label">Bot Completion Rate</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-info-light);color:var(--lp-info);">
                <i class="fe fe-shuffle"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $botHandedOver }}</div>
                <div class="stat-label">Dialihkan ke Agen</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-warning-light);color:var(--lp-warning);">
                <i class="fe fe-clock"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $avgHandoverMinutes }}m</div>
                <div class="stat-label">Rata-rata Handover</div>
            </div>
        </div>
    </div>

    {{-- Ratio + Trend --}}
    <div class="grid-2">
        {{-- Trend Chart --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-trending-up"></i> Tren Chat Bot (7 Hari)</h5>
            </div>
            <div class="lp-card-body">
                <div class="chart-wrap"><canvas id="botTrendChart"></canvas></div>
            </div>
        </div>

        {{-- Ratio --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-pie-chart"></i> Rasio Bot vs Agen</h5>
            </div>
            <div class="lp-card-body">
                <div style="height:180px;position:relative;margin-bottom:16px;">
                    <canvas id="ratioChart"></canvas>
                </div>
                <div class="ratio-display" style="margin-bottom:16px;">
                    <div class="ratio-bot" style="flex:{{ $botRatio }};gap:4px;">
                        <i class="fe fe-cpu"></i> {{ $botRatio }}%
                    </div>
                    <div class="ratio-agent" style="flex:{{ $agentRatio }};gap:4px;">
                        <i class="fe fe-user"></i> {{ $agentRatio }}%
                    </div>
                </div>
                <div style="display:flex;justify-content:space-between;font-size:12px;color:var(--lp-gray-500);">
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#8b5cf6;margin-right:5px;"></span>Bot ({{ $botTotal }})</span>
                    <span><span style="display:inline-block;width:10px;height:10px;border-radius:3px;background:#4f46e5;margin-right:5px;"></span>Agen ({{ $botHandedOver }})</span>
                </div>
            </div>
        </div>
    </div>

    {{-- Kategori & Detail --}}
    <div class="grid-equal">
        {{-- Kategori Bot Menu --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-list"></i> Kategori Pertanyaan Bot</h5>
            </div>
            <div class="lp-card-body">
                @if($complaintCategories['total'] > 0)
                @php
                    $palette = ['#8b5cf6','#4f46e5','#06b6d4','#10b981','#f59e0b','#ef4444','#ec4899'];
                @endphp
                <div class="cat-list">
                    @foreach($complaintCategories['categories'] as $i => $cat)
                    @if($cat['count'] > 0)
                    <div class="cat-item">
                        <div class="cat-row">
                            <span class="cat-name">{{ $cat['category'] }}</span>
                            <span class="cat-count">{{ $cat['count'] }} ({{ $cat['percentage'] }}%)</span>
                        </div>
                        <div class="prog-bar">
                            <div class="prog-fill" style="width:{{ max(3, $cat['percentage']) }}%;background:{{ $palette[$i % count($palette)] }};"></div>
                        </div>
                    </div>
                    @endif
                    @endforeach
                </div>
                @else
                <p style="color:var(--lp-gray-500);text-align:center;padding:30px 0;">Belum ada data kategori</p>
                @endif
            </div>
        </div>

        {{-- Bot Summary Detail --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-info"></i> Ringkasan Bot</h5>
            </div>
            <div class="lp-card-body">
                <div style="display:flex;flex-direction:column;gap:14px;">
                    @php
                        $summaryItems = [
                            ['label' => 'Total Percakapan Sistem',   'value' => $totalAll,          'icon' => 'fe-message-square', 'color' => '#4f46e5'],
                            ['label' => 'Ditangani Penuh Bot',       'value' => $botClosed,         'icon' => 'fe-cpu',            'color' => '#8b5cf6'],
                            ['label' => 'Dialihkan ke Agen',         'value' => $botHandedOver,     'icon' => 'fe-shuffle',        'color' => '#06b6d4'],
                            ['label' => 'Bot Completion Rate',       'value' => $botCompletionRate.'%', 'icon' => 'fe-percent',    'color' => '#10b981'],
                            ['label' => 'Rata-rata Waktu Handover',  'value' => $avgHandoverMinutes.' mnt', 'icon' => 'fe-clock', 'color' => '#f59e0b'],
                            ['label' => 'Porsi Bot dari Total',      'value' => $botRatio.'%',      'icon' => 'fe-pie-chart',      'color' => '#ef4444'],
                        ];
                    @endphp
                    @foreach($summaryItems as $item)
                    <div style="display:flex;align-items:center;justify-content:space-between;padding:10px 14px;background:var(--lp-gray-50);border-radius:10px;border:1px solid var(--lp-gray-200);">
                        <div style="display:flex;align-items:center;gap:10px;">
                            <div style="width:32px;height:32px;border-radius:8px;background:{{ $item['color'] }}22;display:flex;align-items:center;justify-content:center;">
                                <i class="fe {{ $item['icon'] }}" style="color:{{ $item['color'] }};font-size:14px;"></i>
                            </div>
                            <span style="font-size:13px;color:var(--lp-gray-700);">{{ $item['label'] }}</span>
                        </div>
                        <span style="font-weight:700;color:var(--lp-dark);font-size:14px;">{{ $item['value'] }}</span>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
// Bot Trend
new Chart(document.getElementById('botTrendChart'), {
    type: 'line',
    data: {
        labels: @json($trendLabels),
        datasets: [{
            label: 'Chat Bot',
            data: @json($botTrend),
            borderColor: '#8b5cf6',
            backgroundColor: 'rgba(139,92,246,0.1)',
            borderWidth: 2.5, fill: true, tension: 0.4,
            pointBackgroundColor: '#8b5cf6', pointRadius: 4,
        }]
    },
    options: {
        responsive: true, maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: { y: { beginAtZero: true, grid: { color: '#f3f4f6' } }, x: { grid: { display: false } } }
    }
});

// Ratio Donut
new Chart(document.getElementById('ratioChart'), {
    type: 'doughnut',
    data: {
        labels: ['Bot', 'Agen'],
        datasets: [{ data: [{{ $botTotal }}, {{ $botHandedOver }}], backgroundColor: ['#8b5cf6','#4f46e5'], borderWidth: 3, borderColor: '#fff' }]
    },
    options: {
        responsive: true, maintainAspectRatio: false, cutout: '65%',
        plugins: { legend: { position: 'bottom', labels: { font: { size: 11 } } } }
    }
});
</script>
@endpush
