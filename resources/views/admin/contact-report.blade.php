@extends('layouts.admin_template')

@section('title', 'Contact Report')

@push('styles')
<style>
    :root {
        --cr-primary: #4f46e5;
        --cr-success: #10b981;
        --cr-success-light: #d1fae5;
        --cr-danger: #ef4444;
        --cr-danger-light: #fee2e2;
        --cr-gray-100: #f3f4f6;
        --cr-gray-200: #e5e7eb;
        --cr-gray-500: #6b7280;
        --cr-gray-700: #374151;
        --cr-dark: #1f2937;
        --cr-white: #ffffff;
    }

    .cr-page {
        background: var(--cr-white);
        min-height: 100vh;
        padding: 24px;
    }

    .cr-page-header {
        margin-bottom: 24px;
    }

    .cr-page-title {
        font-size: 24px;
        font-weight: 700;
        color: var(--cr-dark);
        margin-bottom: 4px;
    }

    .cr-page-subtitle {
        color: var(--cr-gray-500);
        font-size: 14px;
        margin: 0;
    }

    /* Filter Bar */
    .cr-filter-card {
        background: var(--cr-white);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid var(--cr-gray-200);
        padding: 16px 20px;
        margin-bottom: 24px;
    }

    .cr-filter-label {
        font-size: 12px;
        font-weight: 600;
        color: var(--cr-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }

    .cr-filter-select,
    .cr-filter-input {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid var(--cr-gray-200);
        border-radius: 8px;
        font-size: 13px;
        color: var(--cr-dark);
        background: var(--cr-white);
        transition: border-color 0.15s;
    }

    .cr-filter-select:focus,
    .cr-filter-input:focus {
        outline: none;
        border-color: var(--cr-primary);
        box-shadow: 0 0 0 3px rgba(79, 70, 229, 0.1);
    }

    .cr-filter-btn {
        padding: 8px 20px;
        background: var(--cr-primary);
        color: white;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 500;
        cursor: pointer;
        transition: background 0.15s;
        white-space: nowrap;
    }

    .cr-filter-btn:hover {
        background: #4338ca;
    }

    .cr-filter-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }

    /* Date Range Picker */
    .cr-daterange-wrapper {
        position: relative;
        display: flex;
        align-items: center;
    }

    .cr-daterange-icon {
        position: absolute;
        left: 12px;
        color: var(--cr-gray-500);
        font-size: 14px;
        pointer-events: none;
        z-index: 1;
    }

    .cr-daterange-caret {
        position: absolute;
        right: 12px;
        color: var(--cr-gray-500);
        font-size: 14px;
        pointer-events: none;
        z-index: 1;
    }

    .cr-daterange-input {
        padding-left: 36px !important;
        padding-right: 32px !important;
        cursor: pointer;
    }

    /* Summary Cards */
    .cr-summary-card {
        background: var(--cr-white);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid var(--cr-gray-200);
        padding: 20px;
        height: 100%;
        transition: transform 0.2s, box-shadow 0.2s;
    }

    .cr-summary-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 4px 12px rgba(0,0,0,0.08);
    }

    .cr-card-icon {
        width: 44px;
        height: 44px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
        margin-bottom: 12px;
    }

    .cr-card-value {
        font-size: 28px;
        font-weight: 700;
        color: var(--cr-dark);
        line-height: 1;
        margin-bottom: 4px;
    }

    .cr-card-label {
        font-size: 12px;
        color: var(--cr-gray-500);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 8px;
    }

    .cr-card-count {
        font-size: 14px;
        color: var(--cr-gray-700);
        margin-bottom: 6px;
    }

    .cr-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 600;
    }

    .cr-badge-success {
        background: var(--cr-success-light);
        color: #065f46;
    }

    .cr-badge-danger {
        background: var(--cr-danger-light);
        color: #991b1b;
    }

    .cr-badge-neutral {
        background: var(--cr-gray-100);
        color: var(--cr-gray-500);
    }

    /* Chart Cards */
    .cr-chart-card {
        background: var(--cr-white);
        border-radius: 12px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
        border: 1px solid var(--cr-gray-200);
        margin-bottom: 24px;
    }

    .cr-chart-header {
        padding: 16px 20px;
        border-bottom: 1px solid var(--cr-gray-200);
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .cr-chart-title {
        font-size: 15px;
        font-weight: 600;
        color: var(--cr-dark);
        margin: 0;
    }

    .cr-chart-body {
        padding: 20px;
    }

    .cr-chart-wrapper {
        position: relative;
        height: 280px;
    }

    /* Loading overlay */
    .cr-loading-overlay {
        position: absolute;
        inset: 0;
        background: rgba(255,255,255,0.8);
        display: flex;
        align-items: center;
        justify-content: center;
        border-radius: 12px;
        z-index: 10;
    }

    /* Dark mode support */
    body.dark-mode .cr-page {
        background: #1a1a1a;
    }

    body.dark-mode .cr-filter-card,
    body.dark-mode .cr-summary-card,
    body.dark-mode .cr-chart-card {
        background: #1e1e1e;
        border-color: #333;
    }

    body.dark-mode .cr-page-title,
    body.dark-mode .cr-card-value,
    body.dark-mode .cr-chart-title {
        color: #e0e0e0;
    }

    body.dark-mode .cr-filter-select,
    body.dark-mode .cr-filter-input {
        background: #252525;
        border-color: #444;
        color: #e0e0e0;
    }

    body.dark-mode .cr-chart-header {
        border-bottom-color: #333;
    }

    body.dark-mode .cr-card-count {
        color: #a0a0a0;
    }

    /* Responsive overrides */
    @media (max-width: 991.98px) {
        .cr-page {
            padding: 16px;
        }
    }
</style>
@endpush

@section('content')

{{-- Embed initial data for Alpine.js to avoid first AJAX request --}}
<script>
    window.initialData = {!! json_encode($reportData) !!};
</script>

<div class="cr-page" x-data="contactReport" x-cloak>

    {{-- Loading overlay --}}
    <div x-show="loading" class="cr-loading-overlay" style="position: fixed; inset: 0; z-index: 9999; border-radius: 0;">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

    {{-- Page Header --}}
    <div class="cr-page-header">
        <h1 class="cr-page-title">
            <i class="fe fe-bar-chart-2 me-2" style="color: var(--cr-primary);"></i>
            Contact Report
        </h1>
        <p class="cr-page-subtitle">Laporan statistik kontak pelanggan berdasarkan periode dan channel</p>
    </div>

    {{-- Filter Bar --}}
    <div class="cr-filter-card">
        <div class="row g-3 align-items-end">

            {{-- Timezone Selector --}}
            <div class="col-12 col-sm-6 col-lg-4">
                <div class="cr-filter-label">Timezone</div>
                <select class="cr-filter-select" x-model="timezone">
                    <option value="UTC">UTC</option>
                    <option value="Asia/Jakarta">Asia/Jakarta (WIB)</option>
                    <option value="Asia/Makassar">Asia/Makassar (WITA)</option>
                    <option value="Asia/Jayapura">Asia/Jayapura (WIT)</option>
                </select>
            </div>

            {{-- Date Range Picker --}}
            <div class="col-12 col-sm-6 col-lg-5">
                <div class="cr-filter-label">Periode</div>
                <div class="cr-daterange-wrapper">
                    <i class="fe fe-calendar cr-daterange-icon"></i>
                    <input type="text" id="cr-daterange" class="cr-filter-input cr-daterange-input"
                           readonly placeholder="Pilih rentang tanggal">
                    <i class="fe fe-chevron-down cr-daterange-caret"></i>
                </div>
            </div>

            {{-- Apply Button --}}
            <div class="col-12 col-lg-3">
                <button class="cr-filter-btn w-100" @click="fetchData()" :disabled="loading">
                    <i class="fe fe-refresh-cw me-1"></i>
                    <span x-text="loading ? 'Memuat...' : 'Terapkan'"></span>
                </button>
            </div>

        </div>
    </div>

    {{-- Summary Cards --}}
    <div class="row g-3 mb-4">

        {{-- Total Contact --}}
        <div class="col-12 col-md-6 col-lg">
            <div class="cr-summary-card">
                <div class="cr-card-icon" style="background: rgba(79, 70, 229, 0.1); color: #4f46e5;">
                    <i class="fe fe-users"></i>
                </div>
                <div class="cr-card-label">Total Contact</div>
                <div class="cr-card-value" x-text="summary.total_contact ?? 0"></div>
                <small class="text-muted">Dalam rentang tanggal dipilih</small>
            </div>
        </div>

        {{-- Daily Trend --}}
        <div class="col-12 col-md-6 col-lg">
            <div class="cr-summary-card">
                <div class="cr-card-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;">
                    <i class="fe fe-calendar"></i>
                </div>
                <div class="cr-card-label">Daily Trend</div>
                <div class="cr-card-value" x-text="summary.daily?.count ?? 0"></div>
                <div class="cr-card-count">Hari ini</div>
                <template x-if="summary.daily?.change === null || summary.daily?.change === undefined">
                    <span class="cr-badge cr-badge-neutral">N/A</span>
                </template>
                <template x-if="summary.daily?.change !== null && summary.daily?.change !== undefined && summary.daily?.change >= 0">
                    <span class="cr-badge cr-badge-success">
                        <i class="fe fe-arrow-up" style="font-size: 10px;"></i>
                        <span x-text="summary.daily?.change_label"></span>
                    </span>
                </template>
                <template x-if="summary.daily?.change !== null && summary.daily?.change !== undefined && summary.daily?.change < 0">
                    <span class="cr-badge cr-badge-danger">
                        <i class="fe fe-arrow-down" style="font-size: 10px;"></i>
                        <span x-text="summary.daily?.change_label"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Weekly Trend --}}
        <div class="col-12 col-md-6 col-lg">
            <div class="cr-summary-card">
                <div class="cr-card-icon" style="background: rgba(6, 182, 212, 0.1); color: #06b6d4;">
                    <i class="fe fe-trending-up"></i>
                </div>
                <div class="cr-card-label">Weekly Trend</div>
                <div class="cr-card-value" x-text="summary.weekly?.count ?? 0"></div>
                <div class="cr-card-count">7 hari terakhir</div>
                <template x-if="summary.weekly?.change === null || summary.weekly?.change === undefined">
                    <span class="cr-badge cr-badge-neutral">N/A</span>
                </template>
                <template x-if="summary.weekly?.change !== null && summary.weekly?.change !== undefined && summary.weekly?.change >= 0">
                    <span class="cr-badge cr-badge-success">
                        <i class="fe fe-arrow-up" style="font-size: 10px;"></i>
                        <span x-text="summary.weekly?.change_label"></span>
                    </span>
                </template>
                <template x-if="summary.weekly?.change !== null && summary.weekly?.change !== undefined && summary.weekly?.change < 0">
                    <span class="cr-badge cr-badge-danger">
                        <i class="fe fe-arrow-down" style="font-size: 10px;"></i>
                        <span x-text="summary.weekly?.change_label"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Monthly Trend --}}
        <div class="col-12 col-md-6 col-lg">
            <div class="cr-summary-card">
                <div class="cr-card-icon" style="background: rgba(245, 158, 11, 0.1); color: #f59e0b;">
                    <i class="fe fe-bar-chart"></i>
                </div>
                <div class="cr-card-label">Monthly Trend</div>
                <div class="cr-card-value" x-text="summary.monthly?.count ?? 0"></div>
                <div class="cr-card-count">30 hari terakhir</div>
                <template x-if="summary.monthly?.change === null || summary.monthly?.change === undefined">
                    <span class="cr-badge cr-badge-neutral">N/A</span>
                </template>
                <template x-if="summary.monthly?.change !== null && summary.monthly?.change !== undefined && summary.monthly?.change >= 0">
                    <span class="cr-badge cr-badge-success">
                        <i class="fe fe-arrow-up" style="font-size: 10px;"></i>
                        <span x-text="summary.monthly?.change_label"></span>
                    </span>
                </template>
                <template x-if="summary.monthly?.change !== null && summary.monthly?.change !== undefined && summary.monthly?.change < 0">
                    <span class="cr-badge cr-badge-danger">
                        <i class="fe fe-arrow-down" style="font-size: 10px;"></i>
                        <span x-text="summary.monthly?.change_label"></span>
                    </span>
                </template>
            </div>
        </div>

        {{-- Quarterly Trend --}}
        <div class="col-12 col-md-6 col-lg">
            <div class="cr-summary-card">
                <div class="cr-card-icon" style="background: rgba(139, 92, 246, 0.1); color: #8b5cf6;">
                    <i class="fe fe-activity"></i>
                </div>
                <div class="cr-card-label">Quarterly Trend</div>
                <div class="cr-card-value" x-text="summary.quarterly?.count ?? 0"></div>
                <div class="cr-card-count">90 hari terakhir</div>
                <template x-if="summary.quarterly?.change === null || summary.quarterly?.change === undefined">
                    <span class="cr-badge cr-badge-neutral">N/A</span>
                </template>
                <template x-if="summary.quarterly?.change !== null && summary.quarterly?.change !== undefined && summary.quarterly?.change >= 0">
                    <span class="cr-badge cr-badge-success">
                        <i class="fe fe-arrow-up" style="font-size: 10px;"></i>
                        <span x-text="summary.quarterly?.change_label"></span>
                    </span>
                </template>
                <template x-if="summary.quarterly?.change !== null && summary.quarterly?.change !== undefined && summary.quarterly?.change < 0">
                    <span class="cr-badge cr-badge-danger">
                        <i class="fe fe-arrow-down" style="font-size: 10px;"></i>
                        <span x-text="summary.quarterly?.change_label"></span>
                    </span>
                </template>
            </div>
        </div>

    </div>

    {{-- Overtime Chart (full width) --}}
    <div class="cr-chart-card">
        <div class="cr-chart-header">
            <i class="fe fe-trending-up" style="color: var(--cr-primary);"></i>
            <h5 class="cr-chart-title">Kontak dari Waktu ke Waktu</h5>
        </div>
        <div class="cr-chart-body">
            <div class="cr-chart-wrapper">
                <canvas id="overtimeChart"></canvas>
            </div>
        </div>
    </div>

    {{-- Hourly & Daily Distribution Charts (half width on desktop) --}}
    <div class="row g-4">

        {{-- Hourly Chart --}}
        <div class="col-12 col-lg-6">
            <div class="cr-chart-card" style="margin-bottom: 0;">
                <div class="cr-chart-header">
                    <i class="fe fe-clock" style="color: var(--cr-primary);"></i>
                    <h5 class="cr-chart-title">Distribusi per Jam</h5>
                </div>
                <div class="cr-chart-body">
                    <div class="cr-chart-wrapper">
                        <canvas id="hourlyChart"></canvas>
                    </div>
                </div>
            </div>
        </div>

        {{-- Daily Distribution Chart --}}
        <div class="col-12 col-lg-6">
            <div class="cr-chart-card" style="margin-bottom: 0;">
                <div class="cr-chart-header">
                    <i class="fe fe-bar-chart-2" style="color: var(--cr-primary);"></i>
                    <h5 class="cr-chart-title">Distribusi per Hari</h5>
                </div>
                <div class="cr-chart-body">
                    <div class="cr-chart-wrapper">
                        <canvas id="dailyChart"></canvas>
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
document.addEventListener('alpine:init', () => {
    Alpine.data('contactReport', () => ({
        // Filter state
        timezone: 'UTC',
        startDate: '',
        endDate: '',

        // UI state
        loading: false,

        // Summary data
        summary: {},

        // Chart instances
        overtimeChartInstance: null,
        hourlyChartInstance: null,
        dailyChartInstance: null,

        // -------------------------------------------------------
        // Lifecycle
        // -------------------------------------------------------
        init() {
            const params = new URLSearchParams(window.location.search);
            this.timezone  = params.get('timezone')   || 'UTC';
            this.startDate = params.get('start_date') || this.defaultStartDate();
            this.endDate   = params.get('end_date')   || this.defaultEndDate();

            // Init daterangepicker after DOM is ready
            this.$nextTick(() => {
                this.initDateRangePicker();

                // Use embedded initial data to avoid first AJAX request
                if (window.initialData) {
                    this.summary = window.initialData.summary || {};
                    this.initCharts(window.initialData);
                } else {
                    this.fetchData();
                }
            });
        },

        // -------------------------------------------------------
        // Helpers
        // -------------------------------------------------------
        defaultStartDate() {
            const d = new Date();
            d.setDate(d.getDate() - 29);
            return d.toISOString().slice(0, 10);
        },

        defaultEndDate() {
            return new Date().toISOString().slice(0, 10);
        },

        initDateRangePicker() {
            const self = this;
            $('#cr-daterange').daterangepicker({
                startDate: moment(this.startDate),
                endDate:   moment(this.endDate),
                maxDate:   moment(),
                locale: {
                    format:        'DD MMM YYYY',
                    separator:     ' – ',
                    applyLabel:    'Terapkan',
                    cancelLabel:   'Batal',
                    fromLabel:     'Dari',
                    toLabel:       'Sampai',
                    customRangeLabel: 'Kustom',
                    daysOfWeek:    ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'],
                    monthNames:    ['Januari','Februari','Maret','April','Mei','Juni',
                                    'Juli','Agustus','September','Oktober','November','Desember'],
                    firstDay:      1,
                },
                ranges: {
                    '7 Hari Terakhir':   [moment().subtract(6, 'days'),   moment()],
                    '30 Hari Terakhir':  [moment().subtract(29, 'days'),  moment()],
                    '60 Hari Terakhir':  [moment().subtract(59, 'days'),  moment()],
                    '90 Hari Terakhir':  [moment().subtract(89, 'days'),  moment()],
                    '6 Bulan Terakhir':  [moment().subtract(179, 'days'), moment()],
                    '1 Tahun Terakhir':  [moment().subtract(364, 'days'), moment()],
                },
                showDropdowns: true,
                linkedCalendars: false,
            }, function(start, end) {
                self.startDate = start.format('YYYY-MM-DD');
                self.endDate   = end.format('YYYY-MM-DD');
            });
        },

        buildQueryParams() {
            return new URLSearchParams({
                timezone:   this.timezone,
                channel:    '',
                start_date: this.startDate,
                end_date:   this.endDate,
            });
        },

        dateRangeDays() {
            const start = new Date(this.startDate);
            const end   = new Date(this.endDate);
            return Math.round((end - start) / (1000 * 60 * 60 * 24));
        },

        // -------------------------------------------------------
        // URL management
        // -------------------------------------------------------
        updateURL() {
            const params = new URLSearchParams({
                timezone:   this.timezone,
                start_date: this.startDate,
                end_date:   this.endDate,
            });
            window.history.replaceState({}, '', `?${params.toString()}`);
        },

        // -------------------------------------------------------
        // Data fetching
        // -------------------------------------------------------
        async fetchData() {
            if (this.loading) return;

            this.loading = true;
            this.updateURL();

            try {
                const params   = this.buildQueryParams();
                const response = await fetch(`/admin/contact-report/data?${params.toString()}`, {
                    headers: {
                        'Accept':           'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({}));
                    const message   = errorData.message || `HTTP ${response.status}: Gagal memuat data.`;
                    Toast.fire({ icon: 'error', title: message });
                    return;
                }

                const data = await response.json();

                this.summary = data.summary || {};
                this.updateCharts(data);

            } catch (err) {
                console.error('[ContactReport] fetchData error:', err);
                Toast.fire({ icon: 'error', title: 'Gagal memuat data. Silakan coba lagi.' });
            } finally {
                this.loading = false;
            }
        },

        // -------------------------------------------------------
        // Chart initialisation (called once with initial data)
        // -------------------------------------------------------
        initCharts(data) {
            Chart.defaults.font.family = "'Inter', 'Segoe UI', sans-serif";
            Chart.defaults.color       = '#6b7280';

            const overtime = data.overtime           || { labels: [], data: [] };
            const hourly   = data.hourly             || { labels: [], data: [] };
            const daily    = data.daily_distribution || { labels: [], data: [] };

            // --- Overtime Chart (line) ---
            const overtimeCtx = document.getElementById('overtimeChart');
            if (overtimeCtx) {
                const days = this.dateRangeDays();
                this.overtimeChartInstance = new Chart(overtimeCtx.getContext('2d'), {
                    type: 'line',
                    data: {
                        labels:   overtime.labels,
                        datasets: [{
                            label:           'Kontak',
                            data:            overtime.data,
                            borderColor:     '#4f46e5',
                            backgroundColor: 'rgba(79, 70, 229, 0.08)',
                            fill:            true,
                            tension:         0.4,
                            pointRadius:     days > 60 ? 2 : 4,
                            pointBackgroundColor: '#4f46e5',
                            pointBorderColor:     '#fff',
                            pointBorderWidth:     2,
                        }],
                    },
                    options: {
                        responsive:          true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (item)  => `Kontak: ${item.raw}`,
                                },
                            },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            x: {
                                grid: { display: false },
                                ticks: {
                                    maxTicksLimit: days > 90 ? Math.ceil(days / 7) : undefined,
                                },
                            },
                        },
                    },
                });
            }

            // --- Hourly Chart (bar) ---
            const hourlyCtx = document.getElementById('hourlyChart');
            if (hourlyCtx) {
                this.hourlyChartInstance = new Chart(hourlyCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels:   hourly.labels,
                        datasets: [{
                            label:           'Kontak',
                            data:            hourly.data,
                            backgroundColor: 'rgba(6, 182, 212, 0.7)',
                            borderColor:     '#06b6d4',
                            borderWidth:     1,
                            borderRadius:    4,
                        }],
                    },
                    options: {
                        responsive:          true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (item)  => `Kontak: ${item.raw}`,
                                },
                            },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }

            // --- Daily Distribution Chart (bar) ---
            const dailyCtx = document.getElementById('dailyChart');
            if (dailyCtx) {
                this.dailyChartInstance = new Chart(dailyCtx.getContext('2d'), {
                    type: 'bar',
                    data: {
                        labels:   daily.labels,
                        datasets: [{
                            label:           'Kontak',
                            data:            daily.data,
                            backgroundColor: 'rgba(139, 92, 246, 0.7)',
                            borderColor:     '#8b5cf6',
                            borderWidth:     1,
                            borderRadius:    4,
                        }],
                    },
                    options: {
                        responsive:          true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: { display: false },
                            tooltip: {
                                callbacks: {
                                    title: (items) => items[0].label,
                                    label: (item)  => `Kontak: ${item.raw}`,
                                },
                            },
                        },
                        scales: {
                            y: { beginAtZero: true, grid: { color: '#f3f4f6' } },
                            x: { grid: { display: false } },
                        },
                    },
                });
            }
        },

        // -------------------------------------------------------
        // Chart update (destroy + re-create with new data)
        // -------------------------------------------------------
        updateCharts(data) {
            // Destroy existing instances
            if (this.overtimeChartInstance) {
                this.overtimeChartInstance.destroy();
                this.overtimeChartInstance = null;
            }
            if (this.hourlyChartInstance) {
                this.hourlyChartInstance.destroy();
                this.hourlyChartInstance = null;
            }
            if (this.dailyChartInstance) {
                this.dailyChartInstance.destroy();
                this.dailyChartInstance = null;
            }

            // Re-create with fresh data
            this.initCharts(data);
        },
    }));
});
</script>
@endpush
