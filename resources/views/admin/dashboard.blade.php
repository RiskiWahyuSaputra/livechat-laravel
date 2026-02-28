@extends('layouts.admin_template')

@section('title', 'Dashboard')

@push('styles')
<style>
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
</style>
@endpush

@section('content')
<div class="row">
    <!-- Total Pelanggan -->
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100 shadow-sm border-0">
            <div class="card-body">
                <div class="home-user">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span class="bg-primary-light"><img src="{{ asset('admin/assets/img/icons/user.svg') }}" alt="img"></span>
                            <h6>Total Pelanggan</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['total_users'] }}</h3>
                        </div>
                        <div class="homegraph w-100">
                            <div id="chart-total"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pelanggan Online -->
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100 shadow-sm border-0">
            <div class="card-body">
                <div class="home-user home-provider">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span class="bg-success-light"><img src="{{ asset('admin/assets/img/icons/user-circle.svg') }}" alt="img"></span>
                            <h6>Pelanggan Online <span class="pulse-dot bg-success"></span></h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3 class="text-success">{{ $stats['online_users'] }}</h3>
                        </div>
                        <div class="homegraph w-100">
                            <div id="chart-online"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pelanggan Hari Ini -->
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100 shadow-sm border-0">
            <div class="card-body">
                <div class="home-user home-service">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span class="bg-warning-light"><img src="{{ asset('admin/assets/img/icons/service.svg') }}" alt="img"></span>
                            <h6>Pelanggan Hari Ini</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['today_users'] }}</h3>
                        </div>
                        <div class="homegraph w-100">
                            <div id="chart-today"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pelanggan Kemarin -->
    <div class="col-lg-3 col-sm-6 col-12 d-flex">
        <div class="card w-100 shadow-sm border-0">
            <div class="card-body">
                <div class="home-user home-subscription">
                    <div class="home-userhead">
                        <div class="home-usercount">
                            <span class="bg-info-light"><img src="{{ asset('admin/assets/img/icons/money.svg') }}" alt="img"></span>
                            <h6>Pelanggan Kemarin</h6>
                        </div>
                    </div>
                    <div class="home-usercontent">
                        <div class="home-usercontents">
                            <h3>{{ $stats['yesterday_users'] }}</h3>
                        </div>
                        <div class="homegraph w-100">
                            <div id="chart-yesterday"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row" x-data="{ 
    search: '',
    items: [
        @foreach($customers as $customer)
        {
            id: {{ $customer->id }},
            name: '{{ addslashes($customer->name) }}',
            contact: '{{ addslashes($customer->contact) }}',
            origin: '{{ addslashes($customer->origin) }}',
            isOnline: {{ $customer->is_online ? 'true' : 'false' }},
            status: '{{ $customer->current_status }}',
            date: '{{ $customer->created_at->format('d M Y') }}',
            dateHuman: '{{ $customer->created_at->diffForHumans() }}',
            initial: '{{ strtoupper(substr($customer->name, 0, 1)) }}',
            deleteUrl: '{{ route('admin.user.destroy', $customer->id) }}'
        },
        @endforeach
    ],
    get filteredItems() {
        if (this.search === '') return this.items;
        return this.items.filter(i => 
            i.name.toLowerCase().includes(this.search.toLowerCase()) || 
            i.contact.toLowerCase().includes(this.search.toLowerCase()) ||
            i.origin.toLowerCase().includes(this.search.toLowerCase())
        );
    }
}">
    <div class="col-sm-12">
        <div class="card shadow-sm border-0">
            <div class="card-header border-bottom-0 pt-4 px-4">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Daftar Pelanggan</h4>
                    </div>
                    <div class="col-auto">
                        <div class="d-flex align-items-center gap-3">
                            <!-- Real-time Search -->
                            <div class="search-input-group">
                                <i class="fe fe-search"></i>
                                <input type="text" x-model="search" placeholder="Cari nama, kontak atau instansi..." class="form-control">
                            </div>
                            
                            <div class="btn-group shadow-sm">
                                <a href="{{ route('admin.dashboard') }}" class="btn btn-sm {{ !request('filter') ? 'btn-primary' : 'btn-white' }}">Semua</a>
                                <a href="{{ route('admin.dashboard', ['filter' => 'online']) }}" class="btn btn-sm {{ request('filter') == 'online' ? 'btn-primary' : 'btn-white' }}">Online</a>
                                <a href="{{ route('admin.dashboard', ['filter' => 'today']) }}" class="btn btn-sm {{ request('filter') == 'today' ? 'btn-primary' : 'btn-white' }}">Hari Ini</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-center table-hover mb-0">
                        <thead class="thead-light">
                            <tr>
                                <th class="ps-4">Pelanggan</th>
                                <th>Kontak & Instansi</th>
                                <th>Status Akses</th>
                                <th>Bergabung Pada</th>
                                <th class="text-end pe-4">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <template x-for="item in filteredItems" :key="item.id">
                                <tr>
                                    <td class="ps-4">
                                        <div class="table-profileimage d-flex align-items-center">
                                            <div class="avatar avatar-sm me-2">
                                                <div class="avatar-title rounded-circle bg-primary text-white" x-text="item.initial"></div>
                                            </div>
                                            <span class="fw-semibold text-dark" x-text="item.name"></span>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-medium" x-text="item.contact"></div>
                                        <small class="text-muted" x-text="item.origin"></small>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="d-inline-block rounded-circle" 
                                                  :class="item.isOnline ? 'bg-success' : 'bg-secondary'"
                                                  style="width: 8px; height: 8px;"></span>
                                            <span class="badge rounded-pill fw-bold text-[10px] py-1 px-2 uppercase tracking-wider"
                                                  :class="{
                                                      'bg-success-light text-success': item.status === 'active',
                                                      'bg-warning-light text-warning': item.status === 'pending' || item.status === 'queued',
                                                      'bg-light text-muted': item.status === 'no_session' || item.status === 'closed'
                                                  }"
                                                  x-text="item.status === 'active' ? 'Percakapan Aktif' : 
                                                         (item.status === 'pending' || item.status === 'queued' ? 'Menunggu' : 'Selesai')"></span>
                                        </div>
                                        <small class="text-[10px] font-medium text-muted mt-1 d-block" x-text="item.isOnline ? 'Online' : 'Offline'"></small>
                                    </td>
                                    <td>
                                        <div x-text="item.date"></div>
                                        <small class="text-muted" x-text="item.dateHuman"></small>
                                    </td>
                                    <td class="text-end pe-4">
                                        <form :action="item.deleteUrl" method="POST" onsubmit="return confirm('Hapus pelanggan ini?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-white text-danger border-0 shadow-none"><i class="fe fe-trash-2"></i></button>
                                        </form>
                                    </td>
                                </tr>
                            </template>

                            <!-- Empty State -->
                            <template x-if="filteredItems.length === 0">
                                <tr>
                                    <td colspan="4">
                                        <div class="empty-state">
                                            <img src="https://illustrations.popsy.co/amber/no-data.svg" alt="No data found" class="img-fluid">
                                            <h5 class="text-muted fw-bold">Tidak ada data pelanggan</h5>
                                            <p class="text-muted small">Kata kunci "<span x-text="search" class="fw-bold"></span>" tidak cocok dengan data apapun.</p>
                                        </div>
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
                
                <!-- Pagination (Only show when not searching) -->
                <div class="px-4 py-3" x-show="search === ''">
                    {{ $customers->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        const chartData = @json($stats['chart_data']);
        const chartLabels = @json($stats['chart_labels']);

        const commonOptions = {
            chart: {
                type: 'area',
                height: 50,
                sparkline: {
                    enabled: true
                },
                animations: {
                    enabled: true,
                    easing: 'easeinout',
                    speed: 800
                }
            },
            stroke: {
                curve: 'smooth',
                width: 2
            },
            fill: {
                opacity: 0.3,
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.3,
                    stops: [0, 90, 100]
                }
            },
            series: [{
                name: 'Pelanggan Baru',
                data: chartData
            }],
            xaxis: {
                categories: chartLabels
            },
            tooltip: {
                fixed: { enabled: false },
                x: { show: true },
                y: {
                    title: {
                        formatter: function(seriesName) { return '' }
                    }
                },
                marker: { show: false }
            }
        };

        // Render Charts
        new ApexCharts(document.querySelector("#chart-total"), {
            ...commonOptions,
            colors: ['#007bff']
        }).render();

        new ApexCharts(document.querySelector("#chart-online"), {
            ...commonOptions,
            colors: ['#28a745']
        }).render();

        new ApexCharts(document.querySelector("#chart-today"), {
            ...commonOptions,
            colors: ['#ffc107']
        }).render();

        new ApexCharts(document.querySelector("#chart-yesterday"), {
            ...commonOptions,
            colors: ['#17a2b8']
        }).render();
    });
</script>
@endpush
