@extends('layouts.admin_template')

@section('title', 'Laporan Performa Agen')

@push('styles')
<style>
    :root {
        --lp-primary: #334155; --lp-primary-light: #f1f5f9;
        --lp-success: #10b981; --lp-success-light: #d1fae5;
        --lp-warning: #f59e0b; --lp-warning-light: #fef3c7;
        --lp-danger: #ef4444;  --lp-danger-light: #fee2e2;
        --lp-info: #64748b;    --lp-info-light: #f1f5f9;
        --lp-dark: #0f172a;    --lp-gray-50: #f8fafc;
        --lp-gray-100: #f1f5f9; --lp-gray-200: #e2e8f0;
        --lp-gray-500: #64748b; --lp-gray-700: #334155;
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
        display: flex; align-items: center; gap: 8px;
        padding: 10px 18px; border-radius: 8px; font-size: 13px;
        font-weight: 500; color: var(--lp-gray-500);
        text-decoration: none; white-space: nowrap; transition: all 0.2s;
    }
    .lap-tab-item:hover { background: var(--lp-gray-100); color: var(--lp-dark); text-decoration: none; }
    .lap-tab-item.active { background: var(--lp-primary); color: #fff; }
    .lp-title { font-size: 22px; font-weight: 700; color: var(--lp-dark); margin-bottom: 4px; }
    .lp-subtitle { color: var(--lp-gray-500); font-size: 13px; margin: 0; }

    /* Stats */
    .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; margin-bottom: 24px; }
    .stat-card {
        background: var(--lp-white); border-radius: 12px; padding: 20px;
        border: 1px solid var(--lp-gray-200); display: flex; align-items: center;
        gap: 16px; transition: transform 0.2s, box-shadow 0.2s;
    }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 4px 16px rgba(0,0,0,0.08); }
    .stat-icon { width: 48px; height: 48px; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0; }
    .stat-body .stat-value { font-size: 26px; font-weight: 700; color: var(--lp-dark); line-height: 1; }
    .stat-body .stat-label { font-size: 12px; color: var(--lp-gray-500); margin-top: 4px; }

    /* Cards */
    .lp-card { background: var(--lp-white); border-radius: 12px; border: 1px solid var(--lp-gray-200); box-shadow: 0 1px 3px rgba(0,0,0,0.06); margin-bottom: 20px; }
    .lp-card-header { padding: 16px 20px; border-bottom: 1px solid var(--lp-gray-200); display: flex; align-items: center; justify-content: space-between; }
    .lp-card-title { font-size: 14px; font-weight: 600; color: var(--lp-dark); display: flex; align-items: center; gap: 8px; margin: 0; }
    .lp-card-title i { color: var(--lp-primary); }
    .lp-card-body { padding: 0; }

    /* Agent table */
    .agent-table { width: 100%; border-collapse: collapse; }
    .agent-table th { background: var(--lp-gray-50); padding: 12px 16px; text-align: left; font-size: 11px; font-weight: 600; color: var(--lp-gray-500); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid var(--lp-gray-200); }
    .agent-table td { padding: 14px 16px; border-bottom: 1px solid var(--lp-gray-100); font-size: 13px; color: var(--lp-gray-700); }
    .agent-table tr:last-child td { border-bottom: none; }
    .agent-table tr:hover td { background: var(--lp-gray-50); }

    /* Agent avatar */
    .ag-avatar { width: 36px; height: 36px; border-radius: 10px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; color: #fff; background: var(--lp-gray-700); }
    .ag-info { display: flex; align-items: center; gap: 12px; }

    /* Rank badges */
    .rank-badge { display: inline-flex; align-items: center; justify-content: center; width: 26px; height: 26px; border-radius: 8px; font-size: 11px; font-weight: 700; }
    .rank-1 { background: #fbbf24; color:#fff; }
    .rank-2 { background: #9ca3af; color:#fff; }
    .rank-3 { background: #d97706; color:#fff; }
    .rank-other { background: var(--lp-gray-200); color: var(--lp-gray-500); }

    /* Status pill */
    .status-pill { display: inline-flex; align-items: center; padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; }
    .status-online { background: var(--lp-success-light); color: #065f46; }
    .status-busy   { background: var(--lp-warning-light); color: #92400e; }
    .status-offline{ background: var(--lp-gray-200); color: var(--lp-gray-500); }

    /* Score box */
    .score-box { display: inline-flex; align-items: center; justify-content: center; width: 40px; height: 40px; border-radius: 8px; font-weight: 700; font-size: 13px; }
    .score-high { background: var(--lp-success-light); color: #065f46; }
    .score-mid  { background: var(--lp-warning-light); color: #92400e; }
    .score-low  { background: var(--lp-danger-light); color: #991b1b; }

    /* Progress */
    .prog-bar { height: 20px; background: var(--lp-gray-100); border-radius: 6px; overflow: hidden; }
    .prog-fill { height: 100%; border-radius: 6px; display: flex; align-items: center; padding: 0 8px; font-size: 11px; font-weight: 600; color: #fff; background: var(--lp-gray-700); }

    /* Top 3 podium */
    .podium { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin-bottom: 20px; }
    .podium-card { background: var(--lp-white); border-radius: 10px; border: 1px solid var(--lp-gray-200); padding: 12px; text-align: center; transition: transform 0.2s; }
    .podium-card:hover { transform: translateY(-2px); box-shadow: 0 2px 8px rgba(0,0,0,0.08); }
    .podium-avatar { width: 40px; height: 40px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; color: #fff; margin: 0 auto 8px; background: var(--lp-gray-700); }
    .podium-name { font-weight: 600; font-size: 13px; color: var(--lp-dark); margin-bottom: 4px; }
    .podium-score { font-size: 18px; font-weight: 700; color: var(--lp-dark); margin-bottom: 4px; }
    .podium-meta { font-size: 10px; color: var(--lp-gray-500); }

    .chart-wrap { height: 250px; position: relative; }
    .grid-2 { display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }

    @media (max-width: 1100px) { .stats-grid { grid-template-columns: repeat(2, 1fr); } }
    @media (max-width: 768px) { .stats-grid { grid-template-columns: 1fr 1fr; } .grid-2 { grid-template-columns: 1fr; } .podium { grid-template-columns: 1fr; } .laporan-page { padding: 16px; } }
</style>
@endpush

@section('content')
<div class="laporan-page">

    {{-- Header --}}
    <div class="d-flex justify-content-between align-items-start flex-wrap gap-3 mb-4">
        <div>
            <h1 class="lp-title"><i class="fe fe-users" style="color:var(--lp-primary);margin-right:8px;"></i>Laporan Performa Agen</h1>
            <p class="lp-subtitle">Analisis kinerja dan produktivitas setiap agen</p>
        </div>
    </div>

    {{-- Stats --}}
    <div class="stats-grid">
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-gray-100);color:var(--lp-gray-700);">
                <i class="fe fe-users"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ count($topPerformers['all']) }}</div>
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
            <div class="stat-icon" style="background:var(--lp-gray-100);color:var(--lp-gray-700);">
                <i class="fe fe-check-square"></i>
            </div>
            <div class="stat-body">
                <div class="stat-value">{{ collect($topPerformers['all'])->sum('closed_chats') }}</div>
                <div class="stat-label">Total Chat Diselesaikan</div>
            </div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background:var(--lp-gray-100);color:var(--lp-gray-700);">
                <i class="fe fe-star"></i>
            </div>
            <div class="stat-body">
                @php $ratedAgents = collect($topPerformers['all'])->where('total_ratings', '>', 0); @endphp
                <div class="stat-value">{{ $ratedAgents->count() > 0 ? number_format($ratedAgents->avg('avg_rating'), 1) : 'N/A' }}</div>
                <div class="stat-label">Rata-rata Rating</div>
            </div>
        </div>
    </div>

    {{-- Top 3 Performers --}}
    @if(count($topPerformers['top']) > 0)
    <div class="lp-card mb-4" style="border:none;background:transparent;box-shadow:none;">
        <div style="margin-bottom:12px;">
            <h6 style="font-weight:700;color:var(--lp-dark);font-size:15px;"><i class="fe fe-award" style="color:#fbbf24;margin-right:8px;"></i>Top 3 Agen Terbaik</h6>
        </div>
        <div class="podium">
            @foreach($topPerformers['top'] as $i => $agent)
            @php 
                $ratingEmoji = ['1f621','1f61e','1f610','1f60a','1f929'];
                $ratingIndex = $agent['avg_rating'] > 0 ? max(0, min(4, round($agent['avg_rating']) - 1)) : null;
                $medals = ['🥇','🥈','🥉'];
            @endphp
            <div class="podium-card">
                <div class="podium-avatar">{{ strtoupper(substr($agent['username'], 0, 2)) }}</div>
                <div class="podium-name">{{ $agent['username'] }}</div>
                <div class="podium-score">{{ $agent['score'] }} pts</div>
                <div class="podium-meta">{{ $agent['closed_chats'] }} chat • @if($ratingIndex !== null)<img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/{{ $ratingEmoji[$ratingIndex] }}.svg" style="width:16px;height:16px;vertical-align:middle;" alt="rating"> {{ number_format($agent['avg_rating'],1) }}@else N/A @endif</div>
            </div>
            @endforeach
        </div>
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
                        @forelse($agentsPaginated as $i => $agent)
                        @php $globalIndex = ($agentsPaginated->currentPage() - 1) * $agentsPaginated->perPage() + $i; @endphp
                        <tr>
                            <td>
                                <span class="rank-badge @if($globalIndex==0) rank-1 @elseif($globalIndex==1) rank-2 @elseif($globalIndex==2) rank-3 @else rank-other @endif">
                                    {{ $globalIndex + 1 }}
                                </span>
                            </td>
                            <td>
                                <div class="ag-info">
                                    <div class="ag-avatar" style="background:var(--lp-gray-700);">{{ strtoupper(substr($agent['username'], 0, 2)) }}</div>
                                    <span style="font-weight:600;">{{ $agent['username'] }}</span>
                                </div>
                            </td>
                            <td class="text-center">
                                <span class="status-pill status-{{ $agent['status'] }}">{{ ucfirst($agent['status']) }}</span>
                            </td>
                            <td class="text-center"><strong>{{ $agent['closed_chats'] }}</strong></td>
                            <td class="text-center">
                                @if($agent['avg_response_time'] > 0)
                                    <span style="color:{{ $agent['avg_response_time'] < 60 ? '#10b981' : ($agent['avg_response_time'] < 300 ? '#f59e0b' : '#ef4444') }};font-weight:600;">
                                        {{ $agent['avg_response_time'] < 60 ? '< 1 mnt' : number_format($agent['avg_response_time']/60,1).' mnt' }}
                                    </span>
                                @else <span style="color:var(--lp-gray-500);">N/A</span> @endif
                            </td>
                            <td class="text-center">
                                @if($agent['total_ratings'] > 0)
                                    @php
                                        $ratingEmoji = ['1f621','1f61e','1f610','1f60a','1f929'];
                                        $ratingIndex = max(0, min(4, round($agent['avg_rating']) - 1));
                                    @endphp
                                    <div style="display:flex;flex-direction:column;align-items:center;gap:2px;">
                                        <img src="https://cdn.jsdelivr.net/gh/twitter/twemoji@latest/assets/svg/{{ $ratingEmoji[$ratingIndex] }}.svg" style="width:24px;height:24px;" alt="rating">
                                        <div style="font-weight:600;color:var(--lp-dark);font-size:13px;">{{ number_format($agent['avg_rating'],1) }}</div>
                                        <div style="font-size:10px;color:var(--lp-gray-400);">({{ $agent['total_ratings'] }} ulasan)</div>
                                    </div>
                                @else <span style="color:var(--lp-gray-400);">N/A</span> @endif
                            </td>
                            <td class="text-center">
                                <span class="score-box @if($agent['score']>=100) score-high @elseif($agent['score']>=50) score-mid @else score-low @endif">
                                    {{ $agent['score'] }}
                                </span>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--lp-gray-500);">Belum ada data agen</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($agentsPaginated->hasPages())
            <div style="padding:16px 20px;border-top:1px solid var(--lp-gray-200);">
                {{ $agentsPaginated->links() }}
            </div>
            @endif
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
                                    <div class="ag-avatar" style="width:30px;height:30px;font-size:10px;">
                                        {{ strtoupper(substr($agent['username'], 0, 2)) }}
                                    </div>
                                    <span style="font-weight:500;font-size:12px;">{{ $agent['username'] }}</span>
                                </div>
                            </td>
                            <td class="text-center"><strong>{{ $agent['handled_chats'] }}</strong></td>
                            <td style="min-width:100px;">
                                <div class="prog-bar">
                                    <div class="prog-fill" style="width:{{ max(5,$agent['workload_percentage']) }}%;">
                                        {{ $agent['workload_percentage'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="3" style="text-align:center;padding:30px;color:var(--lp-gray-500);">Belum ada data</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if($agentWorkload->hasPages())
            <div style="padding:16px 20px;border-top:1px solid var(--lp-gray-200);">
                {{ $agentWorkload->links() }}
            </div>
            @endif
        </div>
    </div>

</div>
@endsection
