@extends('layouts.admin_template')

@section('title', 'Laporan Performa Agen')

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
        --lp-gray-300: #d1d5db;
        --lp-gray-400: #9ca3af;
        --lp-gray-500: #6b7280;
        --lp-gray-700: #374151;
        --lp-white: #ffffff;
    }

    .laporan-page {
        background: var(--lp-gray-100);
        min-height: 100vh;
        padding: 24px;
    }

    /* Page Header */
    .lp-title {
        font-size: 20px;
        font-weight: 700;
        color: var(--lp-dark);
        margin-bottom: 2px;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .lp-title i {
        color: var(--lp-primary);
        font-size: 18px;
    }
    .lp-subtitle {
        color: var(--lp-gray-500);
        font-size: 13px;
        margin: 0;
    }

    /* ── Stats Grid ── */
    .stats-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .stat-card {
        background: var(--lp-white);
        border-radius: 10px;
        padding: 18px 20px;
        border: 1px solid var(--lp-gray-200);
        display: flex;
        align-items: center;
        gap: 14px;
        transition: box-shadow 0.2s;
    }
    .stat-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .stat-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .stat-body .stat-value {
        font-size: 24px;
        font-weight: 700;
        color: var(--lp-dark);
        line-height: 1;
    }
    .stat-body .stat-label {
        font-size: 12px;
        color: var(--lp-gray-500);
        margin-top: 4px;
    }

    /* ── Top Performers ── */
    .top-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
        margin-bottom: 24px;
    }
    .top-card {
        background: var(--lp-white);
        border-radius: 10px;
        border: 1px solid var(--lp-gray-200);
        padding: 20px;
        display: flex;
        align-items: center;
        gap: 16px;
        transition: box-shadow 0.2s;
    }
    .top-card:hover {
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
    }
    .top-rank {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 13px;
        font-weight: 700;
        flex-shrink: 0;
    }
    .top-rank-1 {
        background: #fef3c7;
        color: #b45309;
    }
    .top-rank-2 {
        background: var(--lp-gray-100);
        color: var(--lp-gray-500);
    }
    .top-rank-3 {
        background: #fed7aa;
        color: #c2410c;
    }
    .top-avatar {
        width: 40px;
        height: 40px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 13px;
        color: var(--lp-white);
        background: var(--lp-primary);
        flex-shrink: 0;
    }
    .top-info {
        flex: 1;
        min-width: 0;
    }
    .top-name {
        font-weight: 600;
        font-size: 14px;
        color: var(--lp-dark);
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .top-meta {
        font-size: 12px;
        color: var(--lp-gray-500);
        margin-top: 2px;
    }
    .top-score {
        text-align: right;
        flex-shrink: 0;
    }
    .top-score-value {
        font-size: 20px;
        font-weight: 700;
        color: var(--lp-dark);
        line-height: 1;
    }
    .top-score-label {
        font-size: 11px;
        color: var(--lp-gray-400);
        margin-top: 2px;
    }

    /* ── Section Title ── */
    .section-title {
        font-size: 14px;
        font-weight: 600;
        color: var(--lp-dark);
        margin-bottom: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .section-title i {
        color: var(--lp-gray-400);
        font-size: 14px;
    }

    /* ── Cards ── */
    .lp-card {
        background: var(--lp-white);
        border-radius: 10px;
        border: 1px solid var(--lp-gray-200);
        margin-bottom: 20px;
        overflow: hidden;
    }
    .lp-card-header {
        padding: 14px 20px;
        border-bottom: 1px solid var(--lp-gray-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .lp-card-title {
        font-size: 13px;
        font-weight: 600;
        color: var(--lp-dark);
        display: flex;
        align-items: center;
        gap: 8px;
        margin: 0;
    }
    .lp-card-title i {
        color: var(--lp-gray-400);
        font-size: 14px;
    }
    .lp-card-body {
        padding: 0;
    }

    /* ── Agent Table ── */
    .agent-table {
        width: 100%;
        border-collapse: collapse;
    }
    .agent-table th {
        background: var(--lp-gray-50);
        padding: 10px 16px;
        text-align: left;
        font-size: 11px;
        font-weight: 600;
        color: var(--lp-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--lp-gray-200);
    }
    .agent-table td {
        padding: 12px 16px;
        border-bottom: 1px solid var(--lp-gray-100);
        font-size: 13px;
        color: var(--lp-gray-700);
        vertical-align: middle;
    }
    .agent-table tr:last-child td {
        border-bottom: none;
    }
    .agent-table tr:hover td {
        background: var(--lp-gray-50);
    }

    /* Agent info */
    .ag-info {
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .ag-avatar {
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 11px;
        color: var(--lp-white);
        background: var(--lp-primary);
        flex-shrink: 0;
    }

    /* Rank badge */
    .rank-num {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        width: 24px;
        height: 24px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        background: var(--lp-gray-100);
        color: var(--lp-gray-500);
    }
    .rank-num.rank-gold {
        background: #fef3c7;
        color: #b45309;
    }
    .rank-num.rank-silver {
        background: var(--lp-gray-200);
        color: var(--lp-gray-500);
    }
    .rank-num.rank-bronze {
        background: #fed7aa;
        color: #c2410c;
    }

    /* Status pill */
    .status-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    .status-pill::before {
        content: '';
        display: block;
        width: 6px;
        height: 6px;
        border-radius: 50%;
    }
    .status-online {
        background: var(--lp-success-light);
        color: #065f46;
    }
    .status-online::before {
        background: #10b981;
    }
    .status-busy {
        background: var(--lp-warning-light);
        color: #92400e;
    }
    .status-busy::before {
        background: #f59e0b;
    }
    .status-offline {
        background: var(--lp-gray-100);
        color: var(--lp-gray-500);
    }
    .status-offline::before {
        background: var(--lp-gray-400);
    }

    /* Score badge */
    .score-badge {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        min-width: 36px;
        height: 28px;
        padding: 0 8px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12px;
    }
    .score-high {
        background: var(--lp-success-light);
        color: #065f46;
    }
    .score-mid {
        background: var(--lp-warning-light);
        color: #92400e;
    }
    .score-low {
        background: var(--lp-danger-light);
        color: #991b1b;
    }

    /* Progress bar */
    .prog-track {
        height: 6px;
        background: var(--lp-gray-100);
        border-radius: 3px;
        overflow: hidden;
    }
    .prog-fill {
        height: 100%;
        border-radius: 3px;
        background: var(--lp-primary);
    }

    /* Layout */
    .grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }

    /* ── Responsive ── */
    @media (max-width: 1100px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: 1fr 1fr; }
        .top-grid { grid-template-columns: 1fr; }
        .grid-2 { grid-template-columns: 1fr; }
        .laporan-page { padding: 16px; }
    }
    @media (max-width: 480px) {
        .stats-grid { grid-template-columns: 1fr; }
    }
</style>
@endpush

@section('content')
<div class="laporan-page">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="lp-title"><i class="fe fe-users"></i> Performa Agen</h1>
            <p class="lp-subtitle">Analisis kinerja dan produktivitas setiap agen</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-primary-light);color:var(--lp-primary);">
                <i class="fe fe-users"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ count($agentPerformance) }}</div>
                <div class="stat-label">Total Agen</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-success-light);color:var(--lp-success);">
                <i class="fe fe-wifi"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ $agentStatusCount['online'] ?? 0 }}</div>
                <div class="stat-label">Agen Online</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-warning-light);color:var(--lp-warning);">
                <i class="fe fe-check-square"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ collect($agentPerformance)->sum('closed_chats') }}</div>
                <div class="stat-label">Total Chat Diselesaikan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-info-light);color:var(--lp-info);">
                <i class="fe fe-star"></i>
            </div>
            <div class="stat-body">
                @php $ratedAgents = collect($agentPerformance)->where('total_ratings', '>', 0); @endphp
                <div class="stat-value">{{ $ratedAgents->count() > 0 ? number_format($ratedAgents->avg('avg_rating'), 1) : 'N/A' }}</div>
                <div class="stat-label">Rata-rata Rating</div>
            </div>
        </div>
    </div>

    {{-- Top 3 Performers --}}
    @if(count($topPerformers['top']) > 0)
    <div class="section-title"><i class="fe fe-award"></i> Top Agen</div>
    <div class="top-grid">
        @foreach($topPerformers['top'] as $i => $agent)
        @php $rankClasses = ['top-rank-1','top-rank-2','top-rank-3']; @endphp
        <div class="top-card">
            <div class="top-rank {{ $rankClasses[$i] ?? '' }}">{{ $i + 1 }}</div>
            <div class="top-avatar">{{ strtoupper(substr($agent['username'], 0, 2)) }}</div>
            <div class="top-info">
                <div class="top-name">{{ $agent['username'] }}</div>
                <div class="top-meta">{{ $agent['closed_chats'] }} chat · Rating {{ $agent['avg_rating'] > 0 ? number_format($agent['avg_rating'],1).'★' : 'N/A' }}</div>
            </div>
            <div class="top-score">
                <div class="top-score-value">{{ $agent['score'] }}</div>
                <div class="top-score-label">poin</div>
            </div>
        </div>
        @endforeach
    </div>
    @endif

    {{-- Main Table + Workload --}}
    <div class="grid-2">
        {{-- Full Performance Table --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-list"></i> Semua Agen</h5>
            </div>
            <div class="lp-card-body">
                <table class="agent-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Agen</th>
                            <th class="text-center">Status</th>
                            <th class="text-center">Chat</th>
                            <th class="text-center">Resp. Time</th>
                            <th class="text-center">Rating</th>
                            <th class="text-center">Score</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($topPerformers['all'] as $i => $agent)
                        <tr>
                            <td>
                                <span class="rank-num @if($i==0) rank-gold @elseif($i==1) rank-silver @elseif($i==2) rank-bronze @endif">
                                    {{ $i + 1 }}
                                </span>
                            </td>
                            <td>
                                <div class="ag-info">
                                    <div class="ag-avatar">{{ strtoupper(substr($agent['username'], 0, 2)) }}</div>
                                    <span style="font-weight:600;">{{ $agent['username'] }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-pill status-{{ $agent['status'] }}">{{ ucfirst($agent['status']) }}</span>
                            </td>
                            <td class="text-center"><strong>{{ $agent['closed_chats'] }}</strong></td>
                            <td class="text-center">
                                @if($agent['avg_response_time'] > 0)
                                    <span style="color:{{ $agent['avg_response_time'] < 60 ? 'var(--lp-success)' : ($agent['avg_response_time'] < 300 ? 'var(--lp-warning)' : 'var(--lp-danger)') }};font-weight:600;">
                                        {{ $agent['avg_response_time'] < 60 ? '< 1 mnt' : number_format($agent['avg_response_time']/60,1).' mnt' }}
                                    </span>
                                @else <span style="color:var(--lp-gray-400);">N/A</span> @endif
                            </td>
                            <td class="text-center">
                                @if($agent['total_ratings'] > 0)
                                    <span style="font-weight:600;color:var(--lp-warning);">{{ number_format($agent['avg_rating'],1) }} ★</span>
                                @else <span style="color:var(--lp-gray-400);">N/A</span> @endif
                            </td>
                            <td class="text-center">
                                <span class="score-badge @if($agent['score']>=100) score-high @elseif($agent['score']>=50) score-mid @else score-low @endif">
                                    {{ $agent['score'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--lp-gray-400);">Belum ada data agen</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Workload --}}
        <div class="lp-card">
            <div class="lp-card-header">
                <h5 class="lp-card-title"><i class="fe fe-briefcase"></i> Beban Kerja</h5>
            </div>
            <div class="lp-card-body">
                <table class="agent-table">
                    <thead>
                        <tr>
                            <th>Agen</th>
                            <th class="text-center">Total</th>
                            <th>Porsi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agentWorkload as $agent)
                        <tr>
                            <td>
                                <div class="ag-info">
                                    <div class="ag-avatar" style="width:28px;height:28px;font-size:10px;border-radius:6px;">
                                        {{ strtoupper(substr($agent['username'], 0, 2)) }}
                                    </div>
                                    <span style="font-weight:500;font-size:12px;">{{ $agent['username'] }}</span>
                                </div>
                            </td>
                            <td class="text-center"><strong>{{ $agent['handled_chats'] }}</strong></td>
                            <td style="min-width:100px;">
                                <div style="display:flex;align-items:center;gap:8px;">
                                    <div class="prog-track" style="flex:1;">
                                        <div class="prog-fill" style="width:{{ max(3,$agent['workload_percentage']) }}%;"></div>
                                    </div>
                                    <span style="font-size:11px;font-weight:600;color:var(--lp-gray-500);min-width:32px;text-align:right;">{{ $agent['workload_percentage'] }}%</span>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--lp-gray-400);">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</div>
@endsection
