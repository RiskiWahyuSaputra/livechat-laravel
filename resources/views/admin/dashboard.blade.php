@extends('layouts.admin_template')

@section('title', 'Dashboard')

@push('styles')
<style>
    #indonesia-svg-map {
        width: 100%;
        height: 100%;
        filter: drop-shadow(0 4px 6px rgba(0,0,0,0.05));
    }
    .land {
        fill: #ffffff;
        stroke: #94a3b8;
        stroke-width: 0.5;
        transition: all 0.3s ease;
        cursor: grab;
    }
    .land:active {
        cursor: grabbing;
    }
    .land:hover {
        stroke: #1d4ed8;
        stroke-width: 2;
        filter: brightness(0.95) drop-shadow(0 2px 4px rgba(29, 78, 216, 0.3));
    }
    .user-dot {
        pointer-events: auto;
        cursor: pointer;
        transition: all 0.5s cubic-bezier(0.4, 0, 0.2, 1);
        filter: drop-shadow(0 1px 2px rgba(0,0,0,0.2));
    }
    .user-dot:hover {
        r: 6;
        filter: drop-shadow(0 2px 4px rgba(0,0,0,0.3));
    }
    .user-dot.pulse {
        animation: dotPulse 2s infinite;
    }
    @keyframes dotPulse {
        0% { r: 3; opacity: 1; }
        50% { r: 5; opacity: 0.7; }
        100% { r: 3; opacity: 1; }
    }
    .map-info-overlay {
        position: absolute;
        top: 20px;
        right: 20px;
        background: rgba(255, 255, 255, 0.95);
        backdrop-filter: blur(4px);
        padding: 12px 16px;
        border-radius: 10px;
        border: 1px solid var(--gray-200);
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        z-index: 10;
        min-width: 150px;
    }
    .zoom-controls {
        position: absolute;
        bottom: 20px;
        right: 20px;
        display: flex;
        flex-direction: column;
        gap: 8px;
        z-index: 20;
    }
    #map-container {
        overflow: hidden !important;
    }
    .zoom-btn {
        width: 36px;
        height: 36px;
        background: white;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        color: var(--dark);
        box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        transition: all 0.2s;
    }
    .zoom-btn:hover {
        background: var(--gray-100);
        color: var(--primary);
        border-color: var(--primary);
    }
    .empty-state {
        padding: 60px 20px;
        text-align: center;
        background: #fcfcfc;
    }
    .empty-state img {
        max-width: 150px;
        margin-bottom: 20px;
        opacity: 0.8;
    }
    .search-input-group {
        position: relative;
        width: 300px;
    }
    .search-input-group i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #adb5bd;
    }
    .search-input-group input {
        padding-left: 35px;
        border-radius: 10px;
        border: 1px solid #e9ecef;
        background-color: #f8f9fa;
        transition: all 0.2s;
    }
    .search-input-group input:focus {
        background-color: #fff;
        box-shadow: 0 0 0 0.2rem rgba(0, 123, 255, 0.1);
        border-color: #007bff;
    }
    .homegraph {
        margin-top: 10px;
        min-height: 50px;
    }
    .pulse-dot {
        width: 20px !important;
        height: 20px !important;
        background-color: #28a745;
        border-radius: 50% !important;
        display: inline-block;
        margin-left: 5px;
        box-shadow: 0 0 0 rgba(40, 167, 69, 0.4);
        animation: pulse 2s infinite;
    }
    @keyframes pulse {
        0% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0.4); }
        70% { box-shadow: 0 0 0 10px rgba(40, 167, 69, 0); }
        100% { box-shadow: 0 0 0 0 rgba(40, 167, 69, 0); }
    }

    /* Analytics Styles - Keep existing dashboard styles */
    :root {
        --primary: #4f46e5;
        --success: #10b981;
        --warning: #f59e0b;
        --info: #06b6d4;
        --danger: #ef4444;
        --dark: #1f2937;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-500: #6b7280;
        --white: #ffffff;
    }

    .dashboard-card {
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid var(--gray-200);
        margin-bottom: 24px;
    }
    
    .card-header-custom {
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .card-title-custom {
        font-size: 15px;
        font-weight: 600;
        color: var(--dark);
        margin: 0;
    }
    
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(6, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    
    .stat-box {
        background: var(--white);
        border-radius: 10px;
        padding: 20px;
        text-align: center;
        border: 1px solid var(--gray-200);
    }
    
    .stat-box .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 12px;
        font-size: 20px;
    }
    
    .stat-box .stat-value {
        font-size: 26px;
        font-weight: 700;
        color: var(--dark);
    }
    
    .stat-box .stat-label {
        font-size: 12px;
        color: var(--gray-500);
    }

    .charts-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .chart-wrapper { height: 280px; position: relative; }

    .data-table {
        width: 100%;
        border-collapse: collapse;
    }
    
    .data-table th {
        background: var(--gray-100);
        padding: 12px 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: var(--gray-500);
        text-transform: uppercase;
    }
    
    .data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--gray-100);
    }

    .status-pill {
        display: inline-flex;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-online { background: #d1fae5; color: #065f46; }
    .status-busy { background: #fef3c7; color: #92400e; }
    .status-offline { background: var(--gray-200); color: var(--gray-500); }
    
    .badge-rank {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
    }
    .rank-1 { background: linear-gradient(135deg, #fbbf24, #d97706); color: white; }
    .rank-2 { background: linear-gradient(135deg, #9ca3af, #6b7280); color: white; }
    .rank-3 { background: linear-gradient(135deg, #d97706, #b45309); color: white; }
    .rank-other { background: var(--gray-200); color: var(--gray-500); }

    .score-box {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 40px;
        height: 40px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13px;
    }
    .score-high { background: #d1fae5; color: #065f46; }
    .score-mid { background: #fef3c7; color: #92400e; }
    .score-low { background: #fee2e2; color: #991b1b; }

    .progress-custom {
        height: 22px;
        background: var(--gray-100);
        border-radius: 6px;
        overflow: hidden;
    }
    .progress-custom .progress-fill {
        height: 100%;
        border-radius: 6px;
        display: flex;
        align-items: center;
        padding: 0 10px;
        font-size: 11px;
        font-weight: 600;
        color: white;
    }

    .info-grid {
        display: grid;
        grid-template-columns: repeat(2, 1fr);
        gap: 24px;
        margin-bottom: 24px;
    }

    .agent-info { display: flex; align-items: center; gap: 12px; }
    .agent-avatar {
        width: 36px;
        height: 36px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 12px;
        color: white;
    }

    .category-list { display: flex; flex-direction: column; gap: 12px; }
    .category-item { display: flex; flex-direction: column; gap: 6px; }
    .category-header { display: flex; justify-content: space-between; font-size: 13px; }
    .category-name { color: var(--gray-500); font-weight: 500; }
    .category-count { color: var(--gray-500); }

    .rating-display { display: flex; align-items: center; gap: 8px; }
    .rating-big { font-size: 36px; font-weight: 700; color: var(--dark); }
    .star-filled { color: #fbbf24; }
    .star-empty { color: var(--gray-200); }

    .origin-list { display: flex; flex-direction: column; gap: 10px; }
    .origin-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: var(--gray-100);
        border-radius: 8px;
    }
    .origin-name { font-size: 13px; color: var(--gray-500); }
    .origin-count { font-size: 12px; font-weight: 600; color: var(--primary); }

    /* Choropleth Map Styles */
    #indonesia-map {
        height: 420px;
        width: 100%;
        border-radius: 8px;
        background: #f8fafc;
        z-index: 1;
    }
    .map-legend {
        background: white;
        padding: 10px 14px;
        border-radius: 8px;
        box-shadow: 0 2px 8px rgba(0,0,0,0.15);
        font-size: 12px;
        line-height: 20px;
    }
    .map-legend i {
        width: 16px;
        height: 16px;
        display: inline-block;
        margin-right: 6px;
        border-radius: 3px;
        vertical-align: middle;
    }
    .map-legend .legend-title {
        font-weight: 600;
        margin-bottom: 6px;
        color: var(--dark);
        font-size: 11px;
        text-transform: uppercase;
    }
    .map-info-tooltip {
        padding: 8px 12px;
        background: white;
        border-radius: 8px;
        box-shadow: 0 3px 14px rgba(0,0,0,0.2);
        font-size: 13px;
        line-height: 1.6;
        min-width: 180px;
    }
    .map-info-tooltip .province-name {
        font-weight: 700;
        font-size: 14px;
        color: var(--dark);
        margin-bottom: 4px;
        border-bottom: 1px solid #e5e7eb;
        padding-bottom: 4px;
    }
    .map-info-tooltip .city-row {
        display: flex;
        justify-content: space-between;
        color: #4b5563;
        font-size: 12px;
    }
    .map-info-tooltip .city-row .city-count {
        font-weight: 600;
        color: var(--primary);
    }
    .map-info-tooltip .total-row {
        margin-top: 4px;
        padding-top: 4px;
        border-top: 1px solid #e5e7eb;
        font-weight: 700;
        display: flex;
        justify-content: space-between;
        color: var(--dark);
    }
    body.dark-mode #indonesia-map {
        background: #1a1a2e;
    }
    body.dark-mode .map-legend {
        background: #1e1e1e;
        color: #e0e0e0;
    }
    body.dark-mode .map-legend .legend-title {
        color: #e0e0e0;
    }
    body.dark-mode .map-info-tooltip {
        background: #1e1e1e;
        color: #e0e0e0;
    }
    body.dark-mode .map-info-tooltip .province-name {
        color: #fff;
        border-bottom-color: #333;
    }
    body.dark-mode .map-info-tooltip .city-row {
        color: #aaa;
    }
    body.dark-mode .map-info-tooltip .total-row {
        color: #fff;
        border-top-color: #333;
    }

    @media (max-width: 1200px) { .stats-grid { grid-template-columns: repeat(3, 1fr); } }
    @media (max-width: 768px) { 
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .charts-grid { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; }
    }

    /* Animation Styles */
    @keyframes fadeInUp {
        from {
            opacity: 0;
            transform: translateY(20px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .animate-fade-in-up {
        animation: fadeInUp 0.6s ease-out both;
    }

    .stats-grid .stat-box {
        opacity: 0;
        animation: fadeInUp 0.5s ease-out forwards;
    }

    .dashboard-card {
        opacity: 0;
        animation: fadeInUp 0.5s ease-out forwards;
    }

    /* Staggered delays for stat boxes */
    .stats-grid .stat-box:nth-child(1) { animation-delay: 0.1s; }
    .stats-grid .stat-box:nth-child(2) { animation-delay: 0.15s; }
    .stats-grid .stat-box:nth-child(3) { animation-delay: 0.2s; }
    .stats-grid .stat-box:nth-child(4) { animation-delay: 0.25s; }
    .stats-grid .stat-box:nth-child(5) { animation-delay: 0.3s; }
    .stats-grid .stat-box:nth-child(6) { animation-delay: 0.35s; }

    /* Staggered delays for charts and other cards */
    .charts-grid .dashboard-card:nth-child(1) { animation-delay: 0.45s; }
    .charts-grid .dashboard-card:nth-child(2) { animation-delay: 0.5s; }
    .charts-grid .dashboard-card:nth-child(3) { animation-delay: 0.55s; }
    .info-grid .dashboard-card:nth-child(1) { animation-delay: 0.6s; }
    .info-grid .dashboard-card:nth-child(2) { animation-delay: 0.65s; }
    .info-grid .dashboard-card:nth-child(3) { animation-delay: 0.7s; }
    .row.mt-4 .card { 
        opacity: 0;
        animation: fadeInUp 0.5s ease-out forwards;
        animation-delay: 0.75s; 
    }
</style>
@endpush

@section('content')
<div class="animate-fade-in-up">
    <!-- ==================== ANALYTICS SECTION ==================== -->
<div class="row mb-4">
    <div class="col-12">
        <h4 class="page-title mb-3">Ringkasan Analisis</h4>
        
        <!-- Stats Overview -->
        <div class="stats-grid">
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                    <i class="fe fe-message-square"></i>
                </div>
                <div class="stat-value">{{ $overview['total_conversations'] }}</div>
                <div class="stat-label">Jumlah Percakapan</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fe fe-message-circle"></i>
                </div>
                <div class="stat-value">{{ $overview['active_conversations'] }}</div>
                <div class="stat-label">Aktif</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--info);">
                    <i class="fe fe-users"></i>
                </div>
                <div class="stat-value">{{ $overview['total_customers'] }}</div>
                <div class="stat-label">Pelanggan</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                    <i class="fe fe-headphones"></i>
                </div>
                <div class="stat-value">{{ $overview['online_agents'] }}</div>
                <div class="stat-label">Agen Online</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                    <i class="fe fe-check-circle"></i>
                </div>
                <div class="stat-value">{{ $metrics['completion_rate'] }}%</div>
                <div class="stat-label">Penyelesaian</div>
            </div>
            <div class="stat-box">
                <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                    <i class="fe fe-clock"></i>
                </div>
                <div class="stat-value">{{ $metrics['avg_duration_minutes'] }}m</div>
                <div class="stat-label">Rata-rata Durasi</div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="charts-grid">
    <div class="dashboard-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom"><i class="fe fe-trending-up me-2"></i>Tren Percakapan (7 Hari Terakhir)</h5>
        </div>
        <div class="card-body-custom" style="padding: 20px;">
            <div class="chart-wrapper">
                <canvas id="trendsChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom"><i class="fe fe-clock me-2"></i>Jam Sibuk</h5>
        </div>
        <div class="card-body-custom" style="padding: 20px;">
            <div class="chart-wrapper">
                <canvas id="peakHoursChart"></canvas>
            </div>
        </div>
    </div>
    
    <div class="dashboard-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom"><i class="fe fe-pie-chart me-2"></i>Distribusi Status</h5>
        </div>
        <div class="card-body-custom" style="padding: 20px;">
            <div class="chart-wrapper" style="display: flex; align-items: center; justify-content: center;">
                <div style="position: relative; height: 230px; width: 100%;">
                    <canvas id="statusDonutChart"></canvas>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Agent Performance -->
<div class="dashboard-card">
    <div class="card-header-custom" style="display:flex;justify-content:space-between;align-items:center;">
        <h5 class="card-title-custom">Performa Agen</h5>
        <div style="display: flex; align-items: center;">
            <label for="agentPerfSelect" style="font-size: 12px; color: var(--gray-500); margin: 0 8px 0 0;">Tampilkan:</label>
            <select id="agentPerfSelect" style="padding: 4px 8px; border-radius: 6px; border: 1px solid var(--gray-200); font-size: 12px; color: var(--dark); background: white; cursor: pointer; height: 28px;">
                <option value="5">5 Agent</option>
                <option value="10">10 Agent</option>
                <option value="15">15 Agent</option>
                <option value="all" selected>Semua Agent</option>
            </select>
        </div>
    </div>
    <div class="card-body-custom" style="padding: 0; overflow-x: auto;">
        <table class="performance-table" style="width: 100%; border-collapse: collapse;">
            <thead>
                <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0;">
                    <th style="padding: 14px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Peringkat</th>
                    <th style="padding: 14px 16px; text-align: left; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Agen</th>
                    <th style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Status</th>
                    <th style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Obrolan ditutup</th>
                    <th style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Rata-rata respon</th>
                    <th style="padding: 14px 16px; text-align: center; font-size: 11px; font-weight: 600; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px;">Skor</th>
                </tr>
            </thead>
            <tbody>
                @forelse($topPerformers['all'] as $index => $agent)
                <tr class="agent-perf-row" style="border-bottom: 1px solid #f1f5f9; transition: background 0.2s;" onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background='white'">
                    <td style="padding: 16px; vertical-align: middle;">
                        <span style="display: inline-flex; align-items: center; justify-content: center; min-width: 28px; height: 28px; border-radius: 6px; font-size: 12px; font-weight: 700; @if($index == 0) background: #fef3c7; color: #b45309; @elseif($index == 1) background: #f3f4f6; color: #6b7280; @elseif($index == 2) background: #fed7aa; color: #c2410c; @else background: #f1f5f9; color: #94a3b8; @endif">
                            {{ $index + 1 }}
                        </span>
                    </td>
                    <td style="padding: 16px; vertical-align: middle;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div style="width: 38px; height: 38px; border-radius: 8px; background: linear-gradient(135deg, #6366f1, #8b5cf6); display: flex; align-items: center; justify-content: center; color: white; font-weight: 600; font-size: 13px;">
                                {{ strtoupper(substr($agent['username'], 0, 2)) }}
                            </div>
                            <div style="font-weight: 600; color: #1e293b; font-size: 14px;">{{ $agent['username'] }}</div>
                        </div>
                    </td>
                    <td style="padding: 16px; vertical-align: middle; text-align: center;">
                        <span style="display: inline-block; padding: 4px 10px; border-radius: 4px; font-size: 11px; font-weight: 600; @if($agent['status'] == 'online') background: #d1fae5; color: #047857; @elseif($agent['status'] == 'busy') background: #fef3c7; color: #b45309; @else background: #f1f5f9; color: #64748b; @endif">
                            {{ ucfirst($agent['status']) }}
                        </span>
                    </td>
                    <td style="padding: 16px; vertical-align: middle; text-align: center;">
                        <span style="font-weight: 600; font-size: 14px; color: #1e293b;">{{ $agent['closed_chats'] }}</span>
                    </td>
                    <td style="padding: 16px; vertical-align: middle; text-align: center;">
                        @if($agent['avg_response_time'] > 0)
                            <span style="font-weight: 500; font-size: 13px; @if($agent['avg_response_time'] < 60) color: #10b981; @elseif($agent['avg_response_time'] < 300) color: #f59e0b; @else color: #ef4444; @endif">
                                {{ floor($agent['avg_response_time'] / 60) }}m {{ $agent['avg_response_time'] % 60 }}s
                            </span>
                        @else
                            <span style="color: #cbd5e1;">-</span>
                        @endif
                    </td>
                    <td style="padding: 16px; vertical-align: middle; text-align: center;">
                        <span style="font-weight: 700; font-size: 15px; @if($agent['score'] >= 100) color: #10b981; @elseif($agent['score'] >= 50) color: #f59e0b; @else color: #ef4444; @endif">{{ $agent['score'] }}</span>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="6" style="padding: 40px; text-align: center; color: #94a3b8;">No agent data available</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

<!-- Full Width Map Section - Priority placement after stats -->
<div class="dashboard-card" style="margin-bottom: 24px;">
    <div class="card-header-custom" style="display:flex;justify-content:space-between;align-items:center;">
        <h5 class="card-title-custom"><i class="fe fe-map me-2"></i>Peta Sebaran Pelanggan</h5>
        <span style="font-size:11px;color:var(--gray-500);">Hover untuk detail • Klik wilayah untuk lihat pelanggan</span>
    </div>
    <div class="card-body-custom" style="padding: 12px; position: relative;">
        <div id="map-container" style="width: 100%; height: 500px; position: relative; overflow: hidden; background: #f8fafc; border-radius: 8px; border: 1px solid var(--gray-100);">
            <!-- SVG will be injected here via JS or included directly -->
            @include('admin.partials.indonesia_svg')
            <div id="map-tooltip" style="position: absolute; pointer-events: none; background: rgba(255,255,255,0.95); color: #333; padding: 12px 16px; border-radius: 8px; font-size: 13px; display: none; z-index: 1000; box-shadow: 0 4px 15px rgba(0,0,0,0.2); border: 1px solid rgba(0,0,0,0.1); max-width: 250px; backdrop-filter: blur(4px);"></div>
            
            <div class="zoom-controls">
                <button class="zoom-btn" id="zoom-in" title="Zoom In"><i class="fe fe-plus"></i></button>
                <button class="zoom-btn" id="zoom-out" title="Zoom Out"><i class="fe fe-minus"></i></button>
                <button class="zoom-btn" id="zoom-reset" title="Reset Camera"><i class="fe fe-refresh-cw"></i></button>
            </div>

            <!-- Province Detail Panel -->
            <div id="province-panel" style="position: absolute; top: 0; right: -350px; width: 320px; height: 100%; background: white; box-shadow: -4px 0 20px rgba(0,0,0,0.15); z-index: 100; transition: right 0.3s ease; overflow-y: auto; border-radius: 8px 0 0 8px;">
                <div style="padding: 20px; border-bottom: 1px solid #e5e7eb;">
                    <div style="display: flex; justify-content: space-between; align-items: center;">
                        <h4 id="panel-province-name" style="margin: 0; font-size: 16px; font-weight: 700; color: #1d4ed8;">Provinsi</h4>
                        <button id="close-panel" style="background: none; border: none; font-size: 20px; cursor: pointer; color: #666;">&times;</button>
                    </div>
                    <p id="panel-province-count" style="margin: 8px 0 0; font-size: 14px; color: #666;">0 Pelanggan</p>
                </div>
                <div id="panel-customer-list" style="padding: 15px;">
                    <!-- Customer list will be populated here -->
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="info-grid">
    <!-- Complaint Categories -->
    <div class="dashboard-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom"><i class="fe fe-alert-circle me-2"></i>Kategori Komplen</h5>
        </div>
        <div class="card-body-custom" style="padding: 20px; height: 444px; display: flex; align-items: center;">
            @if(count($complaintCategories['categories']) > 0)
                <div class="chart-wrapper" style="display: flex; align-items: center; justify-content: center; width: 100%;">
                    <div style="position: relative; height: 350px; width: 100%;">
                        <canvas id="complaintCategoriesChart"></canvas>
                    </div>
                </div>
            @else
                <p style="color: var(--gray-500); text-align: center; padding: 20px; width: 100%;">No data available</p>
            @endif
        </div>
    </div>
</div>

<!-- Customer Table Section -->
<div class="row mt-4">
    <div class="col-lg-12">
        <div class="card shadow-sm border-0">
            <div class="card-header bg-white border-0 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Daftar Pelanggan</h5>
                <div class="search-input-group">
                    <i class="fe fe-search"></i>
                    <form method="GET" action="{{ route('admin.dashboard') }}" class="d-flex">
                        <input type="text" name="search" class="form-control" placeholder="Cari pelanggan..." value="{{ request('search') }}">
                        <button type="submit" class="btn btn-primary ms-2">Cari</button>
                    </form>
                </div>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Nama</th>
                                <th>Contact</th>
                                <th>Origin</th>
                                <th>Status</th>
                                <th>Tanggal Daftar</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($customers as $customer)
                            <tr>
                                <td>{{ $customer->name }}</td>
                                <td>{{ $customer->contact }}</td>
                                <td>{{ $customer->origin ?: '-' }}</td>
                                <td>
                                    @if($customer->is_blocked)
                                    <span class="badge bg-danger">Blocked</span>
                                    @elseif($customer->current_status && $customer->current_status != 'no_session')
                                    <span class="badge bg-{{ $customer->current_status == 'active' ? 'success' : ($customer->current_status == 'pending' ? 'warning' : 'info') }}">
                                        {{ ucfirst($customer->current_status) }}
                                    </span>
                                    @elseif($customer->is_online)
                                    <span class="badge bg-success">Online</span>
                                    @else
                                    <span class="badge bg-secondary">Offline</span>
                                    @endif
                                </td>
                                <td>{{ $customer->created_at->format('d M Y, H:i') }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center">Tidak ada pelanggan</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination -->
                <div class="d-flex justify-content-center mt-4">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script src="https://cdn.jsdelivr.net/npm/svg-pan-zoom@3.6.1/dist/svg-pan-zoom.min.js"></script>
<script>
    // ==================== ANALYTICS CHARTS ====================
    Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
    Chart.defaults.color = '#6b7280';
    
    // Conversation Trends
    new Chart(document.getElementById('trendsChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: {!! json_encode($trends['labels']) !!},
            datasets: [{
                data: {!! json_encode($trends['data']) !!},
                borderColor: '#4f46e5',
                backgroundColor: 'rgba(79, 70, 229, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 4,
                pointBackgroundColor: '#4f46e5',
                pointBorderColor: '#fff',
                pointBorderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false } }
            }
        }
    });

    // Peak Hours
    new Chart(document.getElementById('peakHoursChart').getContext('2d'), {
        type: 'bar',
        data: {
            labels: {!! json_encode($peakHours['labels']) !!},
            datasets: [{
                data: {!! json_encode($peakHours['data']) !!},
                backgroundColor: '#10b981',
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                x: { grid: { display: false }, ticks: { maxTicksLimit: 6 } }
            }
        }
    });

    // Status Distribution Donut Chart
    const statusLabels = {!! json_encode($statusDistribution['labels']) !!};
    const statusData = {!! json_encode($statusDistribution['data']) !!};
    
    // Map status to readable labels
    const statusMap = {
        'pending': 'Pending',
        'active': 'Active',
        'queued': 'Queued',
        'closed': 'Closed'
    };
    const readableLabels = statusLabels.map(s => statusMap[s] || s);
    
    const statusColors = {
        'pending': '#f59e0b',
        'active': '#10b981',
        'queued': '#3b82f6',
        'closed': '#6b7280'
    };
    const backgroundColors = statusLabels.map(s => statusColors[s] || '#6b7280');

    new Chart(document.getElementById('statusDonutChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: readableLabels,
            datasets: [{
                data: statusData,
                backgroundColor: backgroundColors,
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            layout: {
                padding: {
                    bottom: 10
                }
            },
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        padding: 24,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                }
            }
        }
    });

    // Complaint Categories Donut Chart
    const complaintCategoriesRaw = {!! json_encode($complaintCategories['categories']) !!};
    if (complaintCategoriesRaw && (Array.isArray(complaintCategoriesRaw) ? complaintCategoriesRaw.length > 0 : Object.keys(complaintCategoriesRaw).length > 0)) {
        const complaintCategories = Array.isArray(complaintCategoriesRaw) ? complaintCategoriesRaw : Object.values(complaintCategoriesRaw);
        const complaintLabels = complaintCategories.map(c => c.category);
        const complaintData = complaintCategories.map(c => c.count);
        
        // Map specific colors to specific categories, with a fallback palette for "many" categories
        const categoryColorMap = {
            'Pendaftaran & Aktivasi': '#4f46e5',
            'Dukungan Teknis': '#10b981',
            'Masalah Pembayaran': '#f59e0b',
            'Komplain / Keluhan': '#ef4444',
            'Lain-lain': '#6b7280'
        };
        
        const fallbackPalette = [
            '#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#3b82f6', '#06b6d4', '#14b8a6'
        ];
        
        const complaintColors = complaintLabels.map((label, i) => {
            return categoryColorMap[label] || fallbackPalette[i % fallbackPalette.length];
        });

        new Chart(document.getElementById('complaintCategoriesChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: complaintLabels,
                datasets: [{
                    data: complaintData,
                    backgroundColor: complaintColors,
                    borderWidth: 0,
                    hoverOffset: 4
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '65%',
                layout: {
                    padding: {
                        bottom: 15
                    }
                },
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            padding: 24,
                            usePointStyle: true,
                            pointStyle: 'circle'
                        }
                    }
                }
            }
        });
    }

    // ==================== AGENT PERFORMANCE LOGIC ====================
    document.addEventListener('DOMContentLoaded', function() {
        const select = document.getElementById('agentPerfSelect');
        const rows = document.querySelectorAll('.agent-perf-row');
        
        function updateRowsVisibility(val) {
            rows.forEach((row, index) => {
                if (val === 'all') {
                    row.style.display = '';
                } else {
                    const limit = parseInt(val);
                    if (index < limit) {
                        row.style.display = '';
                    } else {
                        row.style.display = 'none';
                    }
                }
            });
        }

        if(select) {
            select.addEventListener('change', function() {
                updateRowsVisibility(this.value);
            });
            
            // Initialize display based on current value
            updateRowsVisibility(select.value);
        }
    });

    // ==================== SVG INTERACTIVE MAP ====================
    (function() {
        const svgElement = document.getElementById('indonesia-svg-map');
        const tooltip = document.getElementById('map-tooltip');
        const container = document.getElementById('map-container');
        if (!svgElement) return;

        // Distinct Colors for Provinces
        const PROVINCE_COLORS = {
            'ID-AC': '#fecaca', 'ID-BA': '#fed7aa', 'ID-BB': '#fef08a', 'ID-BE': '#d9f99d',
            'ID-BT': '#bbf7d0', 'ID-GO': '#99f6e4', 'ID-JA': '#bae6fd', 'ID-JB': '#c7d2fe',
            'ID-JI': '#ddd6fe', 'ID-JK': '#f5d0fe', 'ID-JT': '#fbcfe8', 'ID-KB': '#fecdd3',
            'ID-KI': '#e2e8f0', 'ID-KR': '#cbd5e1', 'ID-KS': '#94a3b8', 'ID-KT': '#64748b',
            'ID-KU': '#475569', 'ID-LA': '#334155', 'ID-MA': '#1e293b', 'ID-MU': '#0f172a',
            'ID-NB': '#ef4444', 'ID-NT': '#f97316', 'ID-PA': '#f59e0b', 'ID-PB': '#eab308',
            'ID-RI': '#84cc16', 'ID-SA': '#10b981', 'ID-SB': '#06b6d4', 'ID-SG': '#3b82f6',
            'ID-SN': '#6366f1', 'ID-SR': '#8b5cf6', 'ID-SS': '#a855f7', 'ID-ST': '#d946ef',
            'ID-SU': '#ec4899', 'ID-YO': '#f43f5e'
        };

        const CITY_TO_PROVINCE = {
            'jakarta': 'ID-JK', 'jakarta utara': 'ID-JK', 'jakarta selatan': 'ID-JK',
            'jakarta barat': 'ID-JK', 'jakarta timur': 'ID-JK', 'jakarta pusat': 'ID-JK',
            'bekasi': 'ID-JB', 'bandung': 'ID-JB', 'bogor': 'ID-JB', 'depok': 'ID-JB',
            'cirebon': 'ID-JB', 'tasikmalaya': 'ID-JB', 'karawang': 'ID-JB', 'sukabumi': 'ID-JB',
            'tangerang': 'ID-BT', 'serang': 'ID-BT', 'cilegon': 'ID-BT',
            'semarang': 'ID-JT', 'solo': 'ID-JT', 'surakarta': 'ID-JT', 'pekalongan': 'ID-JT',
            'surabaya': 'ID-JI', 'malang': 'ID-JI', 'kediri': 'ID-JI', 'sidoarjo': 'ID-JI',
            'yogyakarta': 'ID-YO', 'jogja': 'ID-YO',
            'medan': 'ID-SU', 'pematangsiantar': 'ID-SU',
            'padang': 'ID-SB', 'bukittinggi': 'ID-SB',
            'palembang': 'ID-SS',
            'lampung': 'ID-LA', 'bandar lampung': 'ID-LA',
            'pekanbaru': 'ID-RI', 'dumai': 'ID-RI',
            'batam': 'ID-KR', 'tanjungpinang': 'ID-KR',
            'jambi': 'ID-JA',
            'bengkulu': 'ID-BE',
            'banda aceh': 'ID-AC', 'aceh': 'ID-AC',
            'pangkal pinang': 'ID-BB', 'bangka': 'ID-BB',
            'pontianak': 'ID-KB',
            'banjarmasin': 'ID-KS',
            'palangkaraya': 'ID-KT',
            'samarinda': 'ID-KI', 'balikpapan': 'ID-KI',
            'tarakan': 'ID-KU',
            'makassar': 'ID-SN', 'pare-pare': 'ID-SN',
            'manado': 'ID-SA',
            'palu': 'ID-ST',
            'kendari': 'ID-SG',
            'gorontalo': 'ID-GO',
            'mamuju': 'ID-SR',
            'denpasar': 'ID-BA', 'bali': 'ID-BA',
            'mataram': 'ID-NB', 'lombok': 'ID-NB',
            'kupang': 'ID-NT',
            'ambon': 'ID-MA', 'maluku': 'ID-MA',
            'ternate': 'ID-MU',
            'jayapura': 'ID-PA', 'papua': 'ID-PA',
            'manokwari': 'ID-PB'
        };

        // Initialize Zoom/Pan
        const panZoom = svgPanZoom('#indonesia-svg-map', {
            zoomEnabled: true,
            controlIconsEnabled: false,
            fit: true,
            center: true,
            minZoom: 0.5,
            maxZoom: 15,
            zoomScaleSensitivity: 0.2,
            panEnabled: true,
            zoomWheelEnabled: true,
            beforePan: function(oldPan, newPan) {
                return newPan;
            }
        });

        // External Zoom Controls
        document.getElementById('zoom-in').addEventListener('click', () => panZoom.zoomIn());
        document.getElementById('zoom-out').addEventListener('click', () => panZoom.zoomOut());
        document.getElementById('zoom-reset').addEventListener('click', () => {
            panZoom.resetZoom();
            panZoom.center();
        });

        // Apply Colors Instantly
        Object.keys(PROVINCE_COLORS).forEach(id => {
            const path = document.getElementById(id);
            if (path) {
                path.style.fill = PROVINCE_COLORS[id];
            }
        });

        // Fetch User Data
        fetch('{{ route("admin.map.data") }}')
            .then(res => res.json())
            .then(data => {
                const provinceCounts = {};
                
                // Track counts for tooltip
                data.provinces.forEach(item => {
                    const originLower = (item.origin || '').toLowerCase().trim();
                    const provinceId = CITY_TO_PROVINCE[originLower];
                    if (provinceId) {
                        provinceCounts[provinceId] = (provinceCounts[provinceId] || 0) + item.count;
                    }
                });

                // Apply Colors Based on Data
                const getColor = (count) => {
                    if (count === 0 || !count) return '#f1f5f9';
                    if (count <= 2) return '#dbeafe';
                    if (count <= 5) return '#bfdbfe';
                    if (count <= 10) return '#93c5fd';
                    if (count <= 20) return '#60a5fa';
                    if (count <= 50) return '#3b82f6';
                    if (count <= 100) return '#2563eb';
                    return '#1d4ed8';
                };

                // Store customers by province for click functionality
                const customersByProvince = {};
                data.users.forEach(user => {
                    const originLower = (user.origin || '').toLowerCase().trim();
                    const provinceId = CITY_TO_PROVINCE[originLower];
                    if (provinceId) {
                        if (!customersByProvince[provinceId]) {
                            customersByProvince[provinceId] = [];
                        }
                        customersByProvince[provinceId].push(user);
                    }
                });

                // Update paths with counts and colors
                Object.keys(provinceCounts).forEach(id => {
                    const path = document.getElementById(id);
                    if (path) {
                        path.setAttribute('data-count', provinceCounts[id]);
                        path.setAttribute('data-province-id', id);
                        path.style.fill = getColor(provinceCounts[id]);
                    }
                });

                // Province Hover and Click Effects
                const paths = svgElement.querySelectorAll('.land');
                const provincePanel = document.getElementById('province-panel');
                const panelProvinceName = document.getElementById('panel-province-name');
                const panelProvinceCount = document.getElementById('panel-province-count');
                const panelCustomerList = document.getElementById('panel-customer-list');
                const closePanelBtn = document.getElementById('close-panel');
                
                // Close panel function
                const closePanel = () => {
                    provincePanel.style.right = '-350px';
                };
                
                closePanelBtn.addEventListener('click', closePanel);
                
                // Show customers in panel
                const showProvinceCustomers = (provinceId, provinceTitle) => {
                    const customers = customersByProvince[provinceId] || [];
                    
                    panelProvinceName.textContent = provinceTitle || 'Provinsi Tidak Diketahui';
                    panelProvinceCount.textContent = `${customers.length} Pelanggan`;
                    
                    if (customers.length === 0) {
                        panelCustomerList.innerHTML = `
                            <div style="text-align: center; padding: 30px; color: #999;">
                                <i class="fe fe-users" style="font-size: 40px; margin-bottom: 10px;"></i>
                                <p>Tidak ada pelanggan di wilayah ini</p>
                            </div>
                        `;
                    } else {
                        const customersByOrigin = {};
                        customers.forEach(customer => {
                            const origin = customer.origin || 'Tidak diketahui';
                            if (!customersByOrigin[origin]) {
                                customersByOrigin[origin] = [];
                            }
                            customersByOrigin[origin].push(customer);
                        });
                        
                        let html = '';
                        Object.keys(customersByOrigin).forEach(origin => {
                            const originCustomers = customersByOrigin[origin];
                            html += `
                                <div style="margin-bottom: 15px;">
                                    <div style="font-size: 12px; font-weight: 600; color: #1d4ed8; margin-bottom: 8px; padding-bottom: 4px; border-bottom: 1px solid #e5e7eb;">
                                        ${origin} (${originCustomers.length})
                                    </div>
                            `;
                            originCustomers.forEach(customer => {
                                html += `
                                    <div style="display: flex; align-items: center; padding: 8px 0; border-bottom: 1px solid #f3f4f6;">
                                        <div style="width: 32px; height: 32px; border-radius: 50%; background: #dbeafe; display: flex; align-items: center; justify-content: center; margin-right: 10px; flex-shrink: 0;">
                                            <span style="font-size: 12px; font-weight: 600; color: #1d4ed8;">${customer.name.charAt(0).toUpperCase()}</span>
                                        </div>
                                        <div style="flex: 1; min-width: 0;">
                                            <div style="font-size: 13px; font-weight: 500; color: #333; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">${customer.name}</div>
                                            <div style="font-size: 11px; color: #666;">${customer.contact || '-'}</div>
                                        </div>
                                    </div>
                                `;
                            });
                            html += '</div>';
                        });
                        panelCustomerList.innerHTML = html;
                    }
                    
                    provincePanel.style.right = '0';
                };
                
                paths.forEach(path => {
                    path.addEventListener('mouseenter', () => {
                        const count = path.getAttribute('data-count') || 0;
                        const title = path.getAttribute('title');
                        
                        tooltip.style.display = 'block';
                        tooltip.innerHTML = `
                            <div style="font-weight:700;margin-bottom:2px;color:#1d4ed8;">${title}</div>
                            <div style="font-size:14px;font-weight:600;color:#333;">${count} Pelanggan</div>
                            <div style="font-size:11px;color:#666;margin-top:4px;">Klik untuk detail</div>
                        `;
                    });

                    path.addEventListener('mouseleave', () => tooltip.style.display = 'none');
                    
                    path.addEventListener('click', () => {
                        const provinceId = path.getAttribute('id');
                        const title = path.getAttribute('title');
                        showProvinceCustomers(provinceId, title);
                    });
                });
            });

        // Mouse follow tooltip
        container.addEventListener('mousemove', (e) => {
            if (tooltip.style.display === 'block') {
                const rect = container.getBoundingClientRect();
                tooltip.style.left = (e.clientX - rect.left + 15) + 'px';
                tooltip.style.top = (e.clientY - rect.top + 15) + 'px';
            }
        });
    })();

    // ==================== ORIGINAL DASHBOARD CHARTS ====================
    // User Growth Chart logic
    document.addEventListener('DOMContentLoaded', function() {
        const growthChartEl = document.getElementById('userGrowthChart');
        if (growthChartEl) {
            const ctx = growthChartEl.getContext('2d');
            new Chart(ctx, {
                type: 'line',
                data: {
                    labels: {!! json_encode($stats['chart_labels']) !!},
                    datasets: [{
                        label: 'Pelanggan Baru',
                        data: {!! json_encode($stats['chart_data']) !!},
                        borderColor: '#667eea',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        fill: true,
                        tension: 0.4,
                        pointRadius: 5,
                        pointBackgroundColor: '#667eea'
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: true, position: 'top' }
                    },
                    scales: {
                        y: { beginAtZero: true, ticks: { stepSize: 1 } }
                    }
                }
            });
        }
    });
</script>
@endpush
