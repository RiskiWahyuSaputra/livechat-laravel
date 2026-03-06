@extends('layouts.admin_template')

@section('title', 'Analytics - Analysis')

@push('styles')
<style>
    /* Minimalist Color Variables */
    :root {
        --primary: #4f46e5;
        --primary-light: #818cf8;
        --success: #10b981;
        --success-light: #d1fae5;
        --warning: #f59e0b;
        --warning-light: #fef3c7;
        --danger: #ef4444;
        --danger-light: #fee2e2;
        --info: #06b6d4;
        --info-light: #cffafe;
        --dark: #1f2937;
        --gray-100: #f3f4f6;
        --gray-200: #e5e7eb;
        --gray-300: #d1d5db;
        --gray-500: #6b7280;
        --gray-700: #374151;
        --white: #ffffff;
    }

    /* Base Layout */
    .analytics-page {
        background: var(--gray-100);
        min-height: 100vh;
        padding: 24px;
    }
    
    .page-header {
        margin-bottom: 24px;
    }
    
    .page-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--dark);
        margin-bottom: 4px;
    }
    
    .page-subtitle {
        color: var(--gray-500);
        font-size: 14px;
        margin: 0;
    }

    /* Cards */
    .analytics-card {
        background: var(--white);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid var(--gray-200);
        margin-bottom: 24px;
    }
    
    .card-header-custom {
        padding: 16px 20px;
        border-bottom: 1px solid var(--gray-200);
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    
    .card-title-custom {
        font-size: 15px;
        font-weight: 600;
        color: var(--dark);
        margin: 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .card-title-custom i {
        color: var(--primary);
    }
    
    .card-body-custom {
        padding: 20px;
    }

    /* Stats Cards */
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
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .stat-box:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
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
        line-height: 1;
        margin-bottom: 4px;
    }
    
    .stat-box .stat-label {
        font-size: 12px;
        color: var(--gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Charts Grid */
    .charts-grid {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 24px;
        margin-bottom: 24px;
    }
    
    .chart-wrapper {
        height: 280px;
        position: relative;
    }

    /* Tables */
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
        letter-spacing: 0.5px;
        border-bottom: 1px solid var(--gray-200);
    }
    
    .data-table td {
        padding: 14px 16px;
        border-bottom: 1px solid var(--gray-100);
        font-size: 14px;
        color: var(--gray-700);
    }
    
    .data-table tr:last-child td {
        border-bottom: none;
    }
    
    .data-table tr:hover td {
        background: var(--gray-100);
    }

    /* Badges & Status */
    .status-pill {
        display: inline-flex;
        align-items: center;
        padding: 4px 10px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }
    
    .status-online { background: var(--success-light); color: #065f46; }
    .status-busy { background: var(--warning-light); color: #92400e; }
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

    /* Score Circle */
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
    
    .score-high { background: var(--success-light); color: #065f46; }
    .score-mid { background: var(--warning-light); color: #92400e; }
    .score-low { background: var(--danger-light); color: #991b1b; }

    /* Progress Bars */
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
        min-width: fit-content;
    }

    /* Info Grid */
    .info-grid {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 16px;
    }

    /* Agent Info */
    .agent-info {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    
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

    /* Category List */
    .category-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
    }
    
    .category-item {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }
    
    .category-header {
        display: flex;
        justify-content: space-between;
        font-size: 13px;
    }
    
    .category-name {
        color: var(--gray-700);
        font-weight: 500;
    }
    
    .category-count {
        color: var(--gray-500);
    }

    /* Rating Stars */
    .rating-display {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .rating-big {
        font-size: 36px;
        font-weight: 700;
        color: var(--dark);
    }
    
    .rating-stars {
        display: flex;
        gap: 2px;
    }
    
    .rating-stars i {
        font-size: 16px;
    }
    
    .star-filled { color: #fbbf24; }
    .star-empty { color: var(--gray-300); }

    /* Origin List */
    .origin-list {
        display: flex;
        flex-direction: column;
        gap: 10px;
    }
    
    .origin-item {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 12px;
        background: var(--gray-100);
        border-radius: 8px;
    }
    
    .origin-name {
        font-size: 13px;
        color: var(--gray-700);
    }
    
    .origin-count {
        font-size: 12px;
        font-weight: 600;
        color: var(--primary);
    }

    /* Date Filter */
    .date-filter {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .date-filter input {
        padding: 8px 12px;
        border: 1px solid var(--gray-200);
        border-radius: 8px;
        font-size: 13px;
    }
    
    .date-filter input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }
    
    .date-filter button {
        padding: 8px 16px;
        background: var(--primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .stats-grid { grid-template-columns: repeat(3, 1fr); }
    }
    
    @media (max-width: 768px) {
        .stats-grid { grid-template-columns: repeat(2, 1fr); }
        .charts-grid { grid-template-columns: 1fr; }
        .info-grid { grid-template-columns: 1fr; }
        .analytics-page { padding: 16px; }
    }
</style>
@endpush

@section('content')
<div class="analytics-page">
    <!-- Header -->
    <div class="page-header d-flex justify-content-between align-items-start flex-wrap gap-3">
        <div>
            <h1 class="page-title">Analytics & Analysis</h1>
            <p class="page-subtitle">Monitor your livechat performance and insights</p>
        </div>
        <form method="GET" action="{{ route('admin.analytics.index') }}" class="date-filter">
            <input type="date" name="start_date" value="{{ $startDate->format('Y-m-d') }}">
            <span style="color: var(--gray-500);">→</span>
            <input type="date" name="end_date" value="{{ $endDate->format('Y-m-d') }}">
            <button type="submit"><i class="fe fe-filter" style="margin-right: 4px;"></i>Filter</button>
        </form>
    </div>

    <!-- Stats Overview -->
    <div class="stats-grid">
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                <i class="fe fe-message-square"></i>
            </div>
            <div class="stat-value">{{ $overview['total_conversations'] }}</div>
            <div class="stat-label">Total Chats</div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <i class="fe fe-message-circle"></i>
            </div>
            <div class="stat-value">{{ $overview['active_conversations'] }}</div>
            <div class="stat-label">Active</div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(6, 182, 212, 0.1); color: var(--info);">
                <i class="fe fe-users"></i>
            </div>
            <div class="stat-value">{{ $overview['total_customers'] }}</div>
            <div class="stat-label">Customers</div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(245, 158, 11, 0.1); color: var(--warning);">
                <i class="fe fe-headphones"></i>
            </div>
            <div class="stat-value">{{ $overview['online_agents'] }}</div>
            <div class="stat-label">Online Agents</div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(16, 185, 129, 0.1); color: var(--success);">
                <i class="fe fe-check-circle"></i>
            </div>
            <div class="stat-value">{{ $metrics['completion_rate'] }}%</div>
            <div class="stat-label">Completion</div>
        </div>
        <div class="stat-box">
            <div class="stat-icon" style="background: rgba(79, 70, 229, 0.1); color: var(--primary);">
                <i class="fe fe-clock"></i>
            </div>
            <div class="stat-value">{{ $metrics['avg_duration_minutes'] }}m</div>
            <div class="stat-label">Avg Duration</div>
        </div>
    </div>

    <!-- Charts Row -->
    <div class="charts-grid">
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-trending-up"></i>
                    Conversation Trends (Last 7 Days)
                </h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-wrapper">
                    <canvas id="trendsChart"></canvas>
                </div>
            </div>
        </div>
        
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-clock"></i>
                    Peak Hours
                </h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-wrapper">
                    <canvas id="peakHoursChart"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Agent Performance Table -->
    <div class="analytics-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom">
                <i class="fe fe-users"></i>
                Agent Performance
            </h5>
        </div>
        <div class="card-body-custom" style="padding: 0;">
            <table class="data-table">
                <thead>
                    <tr>
                        <th style="width: 50px;">#</th>
                        <th>Agent</th>
                        <th class="text-center">Status</th>
                        <th class="text-center">Chats</th>
                        <th class="text-center">Response Time</th>
                        <th class="text-center">Score</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($topPerformers['all'] as $index => $agent)
                    <tr>
                        <td>
                            <span class="badge-rank @if($index < 3) rank-{{ $index + 1 }} @else rank-other @endif">
                                {{ $index + 1 }}
                            </span>
                        </td>
                        <td>
                            <div class="agent-info">
                                <div class="agent-avatar" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                                    {{ strtoupper(substr($agent['username'], 0, 2)) }}
                                </div>
                                <span style="font-weight: 600; color: var(--dark);">{{ $agent['username'] }}</span>
                            </div>
                        </td>
                        <td class="text-center">
                            <span class="status-pill status-{{ $agent['status'] }}">
                                {{ ucfirst($agent['status']) }}
                            </span>
                        </td>
                        <td class="text-center">
                            <strong>{{ $agent['closed_chats'] }}</strong>
                        </td>
                        <td class="text-center">
                            @if($agent['avg_response_time'] > 0)
                                <span style="color: {{ $agent['avg_response_time'] < 60 ? '#10b981' : ($agent['avg_response_time'] < 300 ? '#f59e0b' : '#ef4444') }}; font-weight: 600;">
                                    {{ floor($agent['avg_response_time'] / 60) }}m {{ $agent['avg_response_time'] % 60 }}s
                                </span>
                            @else
                                <span style="color: var(--gray-500);">N/A</span>
                            @endif
                        </td>
                        <td class="text-center">
                            <span class="score-box @if($agent['score'] >= 100) score-high @elseif($agent['score'] >= 50) score-mid @else score-low @endif">
                                {{ $agent['score'] }}
                            </span>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="text-center" style="color: var(--gray-500); padding: 40px;">No agent data available</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Info Grid -->
    <div class="info-grid">
        <!-- Complaint Categories -->
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-alert-circle"></i>
                    Complaint Categories
                </h5>
            </div>
            <div class="card-body-custom">
                @if(count($complaintCategories['categories']) > 0)
                    @php
                        $categoryColors = [
                            'Pendaftaran & Aktivasi' => '#4f46e5',
                            'Dukungan Teknis' => '#10b981',
                            'Masalah Pembayaran' => '#f59e0b',
                            'Komplain / Keluhan' => '#ef4444',
                            'Lain-lain' => '#6b7280'
                        ];
                        $fallbackPalette = ['#6366f1', '#8b5cf6', '#ec4899', '#f43f5e', '#3b82f6', '#06b6d4', '#14b8a6'];
                    @endphp
                    <div class="category-list">
                        @foreach($complaintCategories['categories'] as $index => $category)
                        <div class="category-item">
                            <div class="category-header">
                                <span class="category-name">{{ $category['category'] }}</span>
                                <span class="category-count">{{ $category['count'] }} ({{ $category['percentage'] }}%)</span>
                            </div>
                            <div class="progress-custom">
                                @php
                                    $color = $categoryColors[$category['category']] ?? $fallbackPalette[$index % count($fallbackPalette)];
                                @endphp
                                <div class="progress-fill" style="width: {{ $category['percentage'] }}%; background: {{ $color }};">
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p style="color: var(--gray-500); text-align: center; padding: 20px;">No data available</p>
                @endif
            </div>
        </div>

        <!-- Customer Satisfaction -->
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-heart"></i>
                    Satisfaction
                </h5>
            </div>
            <div class="card-body-custom" style="text-align: center;">
                <div class="rating-display justify-content-center mb-3">
                    <span class="rating-big">{{ $customerSatisfaction['average_rating'] }}</span>
                    <div>
                        <div class="rating-stars">
                            @for($i = 1; $i <= 5; $i++)
                                <i class="fe fe-star @if($i <= floor($customerSatisfaction['average_rating'])) star-filled @else star-empty @endif"></i>
                            @endfor
                        </div>
                        <small style="color: var(--gray-500);">{{ $customerSatisfaction['total_ratings'] }} reviews</small>
                    </div>
                </div>
                <hr style="margin: 16px 0;">
                <div style="display: flex; justify-content: center; gap: 16px;">
                    @foreach([5,4,3,2,1] as $star)
                    <div style="text-align: center;">
                        <div class="rating-stars">
                            @for($j = 1; $j <= $star; $j++)
                                <i class="fe fe-star star-filled" style="font-size: 10px;"></i>
                            @endfor
                        </div>
                        <small style="color: var(--gray-500);">{{ $customerSatisfaction['distribution'][$star] ?? 0 }}</small>
                    </div>
                    @endforeach
                </div>
            </div>
        </div>

        <!-- Top Origins -->
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-map-pin"></i>
                    Top Origins
                </h5>
            </div>
            <div class="card-body-custom">
                @if($customerInsights['origins']->count() > 0)
                    <div class="origin-list">
                        @foreach($customerInsights['origins'] as $origin)
                        <div class="origin-item">
                            <span class="origin-name">{{ $origin->origin ?: 'Unknown' }}</span>
                            <span class="origin-count">{{ $origin->count }}</span>
                        </div>
                        @endforeach
                    </div>
                @else
                    <p style="color: var(--gray-500); text-align: center; padding: 20px;">No data available</p>
                @endif
            </div>
        </div>
    </div>

    <!-- Customer Growth & Workload -->
    <div class="charts-grid">
        <!-- Customer Growth -->
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-user-plus"></i>
                    Customer Growth (Last 7 Days)
                </h5>
            </div>
            <div class="card-body-custom">
                <div class="chart-wrapper">
                    <canvas id="customerGrowthChart"></canvas>
                </div>
                <div style="display: flex; justify-content: space-around; margin-top: 16px; padding-top: 16px; border-top: 1px solid var(--gray-200);">
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: 700; color: var(--dark);">{{ $customerInsights['total_users'] }}</div>
                        <small style="color: var(--gray-500);">Total</small>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: 700; color: var(--success);">{{ $customerInsights['online_users'] }}</div>
                        <small style="color: var(--gray-500);">Online</small>
                    </div>
                    <div style="text-align: center;">
                        <div style="font-size: 20px; font-weight: 700; color: var(--danger);">{{ $customerInsights['blocked_users'] }}</div>
                        <small style="color: var(--gray-500);">Blocked</small>
                    </div>
                </div>
            </div>
        </div>

        <!-- Agent Workload -->
        <div class="analytics-card">
            <div class="card-header-custom">
                <h5 class="card-title-custom">
                    <i class="fe fe-briefcase"></i>
                    Workload
                </h5>
            </div>
            <div class="card-body-custom" style="padding: 0;">
                <table class="data-table">
                    <thead>
                        <tr>
                            <th>Agent</th>
                            <th class="text-center">Total</th>
                            <th>Workload</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($agentWorkload as $agent)
                        <tr>
                            <td>
                                <div class="agent-info">
                                    <div class="agent-avatar" style="background: linear-gradient(135deg, #4f46e5, #7c3aed);">
                                        {{ strtoupper(substr($agent['username'], 0, 2)) }}
                                    </div>
                                    <span style="font-weight: 500;">{{ $agent['username'] }}</span>
                                </div>
                            </td>
                            <td class="text-center"><strong>{{ $agent['handled_chats'] }}</strong></td>
                            <td>
                                <div class="progress-custom">
                                    <div class="progress-fill" style="width: {{ max(5, $agent['workload_percentage']) }}%; background: linear-gradient(90deg, #4f46e5, #7c3aed);">
                                        {{ $agent['workload_percentage'] }}%
                                    </div>
                                </div>
                            </td>
                        </tr>
                        @empty
                        <tr>
                            <td colspan="3" class="text-center" style="color: var(--gray-500);">No data available</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Status Distribution -->
    <div class="analytics-card">
        <div class="card-header-custom">
            <h5 class="card-title-custom">
                <i class="fe fe-pie-chart"></i>
                Status Distribution
            </h5>
        </div>
        <div class="card-body-custom">
            <div style="display: flex; align-items: center; gap: 32px;">
                <div style="width: 180px; height: 180px;">
                    <canvas id="statusChart"></canvas>
                </div>
                <div style="flex: 1;">
                    <div style="display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px;">
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(79, 70, 229, 0.08); border-radius: 8px;">
                            <div style="width: 12px; height: 12px; border-radius: 3px; background: #4f46e5;"></div>
                            <div>
                                <div style="font-weight: 600;">Active</div>
                                <small style="color: var(--gray-500);">{{ $metrics['status_distribution']['active'] ?? 0 }} chats</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(245, 158, 11, 0.08); border-radius: 8px;">
                            <div style="width: 12px; height: 12px; border-radius: 3px; background: #f59e0b;"></div>
                            <div>
                                <div style="font-weight: 600;">Pending</div>
                                <small style="color: var(--gray-500);">{{ $metrics['status_distribution']['pending'] ?? 0 }} chats</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(6, 182, 212, 0.08); border-radius: 8px;">
                            <div style="width: 12px; height: 12px; border-radius: 3px; background: #06b6d4;"></div>
                            <div>
                                <div style="font-weight: 600;">Queued</div>
                                <small style="color: var(--gray-500);">{{ $metrics['status_distribution']['queued'] ?? 0 }} chats</small>
                            </div>
                        </div>
                        <div style="display: flex; align-items: center; gap: 12px; padding: 12px; background: rgba(16, 185, 129, 0.08); border-radius: 8px;">
                            <div style="width: 12px; height: 12px; border-radius: 3px; background: #10b981;"></div>
                            <div>
                                <div style="font-weight: 600;">Closed</div>
                                <small style="color: var(--gray-500);">{{ $metrics['status_distribution']['closed'] ?? 0 }} chats</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    // Global Chart Options
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

    // Customer Growth
    new Chart(document.getElementById('customerGrowthChart').getContext('2d'), {
        type: 'line',
        data: {
            labels: {!! json_encode($customerInsights['growth']['labels']) !!},
            datasets: [{
                data: {!! json_encode($customerInsights['growth']['data']) !!},
                borderColor: '#06b6d4',
                backgroundColor: 'rgba(6, 182, 212, 0.08)',
                fill: true,
                tension: 0.4,
                pointRadius: 3,
                pointBackgroundColor: '#06b6d4'
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

    // Status Distribution (Doughnut)
    new Chart(document.getElementById('statusChart').getContext('2d'), {
        type: 'doughnut',
        data: {
            labels: ['Active', 'Pending', 'Queued', 'Closed'],
            datasets: [{
                data: [
                    {{ $metrics['status_distribution']['active'] ?? 0 }},
                    {{ $metrics['status_distribution']['pending'] ?? 0 }},
                    {{ $metrics['status_distribution']['queued'] ?? 0 }},
                    {{ $metrics['status_distribution']['closed'] ?? 0 }}
                ],
                backgroundColor: ['#4f46e5', '#f59e0b', '#06b6d4', '#10b981'],
                borderWidth: 0,
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: { legend: { display: false } }
        }
    });
</script>
@endpush
