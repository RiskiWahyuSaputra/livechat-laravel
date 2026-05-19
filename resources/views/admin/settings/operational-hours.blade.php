@extends('layouts.admin_template')

@section('title', 'Jam Operasional')

@section('content')
    <div class="row justify-content-center mb-5">
        <div class="col-md-10 col-lg-8 pe-lg-4 ps-lg-4">
            <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
                <div>
                    <h3 class="fw-bolder mb-0 text-dark">Jam Operasional</h3>
                    <p class="text-muted">Kendalikan alur percakapan customer berdasarkan kondisi operasional dan jam kerja.
                    </p>
                </div>
            </div>

            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                @method('PUT')

                <!-- Operational Mode Card -->
                <div class="card shadow-sm border-0 rounded-4 mb-4">
                    <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                        <h5 class="fw-bold mb-0 text-success"><i class="fe fe-activity me-2"></i> Mode Operasional Chat</h5>
                        <p class="text-muted small mt-1 mb-0">Kendalikan alur percakapan customer berdasarkan kondisi
                            operasional.</p>
                    </div>
                    <div class="card-body pt-3 pb-4">
                        {{-- System Mode --}}
                        <div class="form-group mb-4">
                            <label class="form-label fw-semibold small text-dark mb-2">Mode Aktif Saat Ini</label>
                            @php $currentMode = $settings['system_mode'] ?? 'office_hour'; @endphp
                            <div class="d-flex gap-2 flex-wrap mt-1">
                                <label
                                    class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border {{ $currentMode === 'office_hour' ? 'border-success bg-success bg-opacity-10' : 'border bg-white' }}"
                                    style="cursor:pointer;flex:1;min-width:160px;">
                                    <input type="radio" name="system_mode" value="office_hour"
                                        {{ $currentMode === 'office_hour' ? 'checked' : '' }} class="form-check-input mt-0" style="width:16px;height:16px;">
                                    <div>
                                        <div class="fw-semibold small text-success" style="font-size:13px;">Jam Kerja</div>
                                        <div class="text-muted" style="font-size:11px;">Customer bisa chat & antri ke Agent</div>
                                    </div>
                                </label>
                                <label
                                    class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border {{ $currentMode === 'outside_office_hour' ? 'border-warning bg-warning bg-opacity-10' : 'border bg-white' }}"
                                    style="cursor:pointer;flex:1;min-width:160px;">
                                    <input type="radio" name="system_mode" value="outside_office_hour"
                                        {{ $currentMode === 'outside_office_hour' ? 'checked' : '' }}
                                        class="form-check-input mt-0" style="width:16px;height:16px;">
                                    <div>
                                        <div class="fw-semibold small text-warning" style="font-size:13px;">Di Luar Jam Kerja</div>
                                        <div class="text-muted" style="font-size:11px;">Hanya dilayani AI, tidak ada Agent</div>
                                    </div>
                                </label>
                                <label
                                    class="d-flex align-items-center gap-2 px-3 py-2 rounded-3 border {{ $currentMode === 'closed' ? 'border-danger bg-danger bg-opacity-10' : 'border bg-white' }}"
                                    style="cursor:pointer;flex:1;min-width:160px;">
                                    <input type="radio" name="system_mode" value="closed"
                                        {{ $currentMode === 'closed' ? 'checked' : '' }} class="form-check-input mt-0" style="width:16px;height:16px;">
                                    <div>
                                        <div class="fw-semibold small text-danger" style="font-size:13px;">Tutup</div>
                                        <div class="text-muted" style="font-size:11px;">Chat ditolak sepenuhnya</div>
                                    </div>
                                </label>
                            </div>
                            @error('system_mode')
                                <div class="text-danger small mt-1">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- Office Hours --}}
                        <div class="mt-4 pt-3 border-top">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="fw-bold mb-0 text-dark"><i class="fe fe-clock me-2 text-primary"></i> Detail Jam
                                    Operasional Per Hari</h6>
                            </div>

                            <div class="form-group mb-4">
                                <label class="form-label fw-bold small text-uppercase opacity-75">Zona Waktu Sistem</label>
                                <select name="office_hours_timezone" class="form-select form-select-lg bg-light border-0">
                                    <option value="Asia/Jakarta"
                                        {{ ($settings['office_hours_timezone'] ?? 'Asia/Jakarta') == 'Asia/Jakarta' ? 'selected' : '' }}>
                                        WIB (Asia/Jakarta)</option>
                                    <option value="Asia/Makassar"
                                        {{ ($settings['office_hours_timezone'] ?? '') == 'Asia/Makassar' ? 'selected' : '' }}>
                                        WITA (Asia/Makassar)</option>
                                    <option value="Asia/Jayapura"
                                        {{ ($settings['office_hours_timezone'] ?? '') == 'Asia/Jayapura' ? 'selected' : '' }}>
                                        WIT (Asia/Jayapura)</option>
                                    <option value="UTC"
                                        {{ ($settings['office_hours_timezone'] ?? '') == 'UTC' ? 'selected' : '' }}>UTC
                                    </option>
                                </select>
                                <small class="text-muted mt-1 d-block">Pilih zona waktu yang akan digunakan sebagai acuan
                                    jam operasional.</small>
                            </div>

                            <div class="row g-3">
                                @foreach (['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $label)
                                    @php
                                        $isWeekend = in_array($day, ['saturday', 'sunday']);
                                        $isActive =
                                            ($settings["office_hours_{$day}_active"] ?? ($isWeekend ? '0' : '1')) ==
                                            '1';
                                    @endphp
                                    <div class="col-12">
                                        <div class="p-3 rounded-3 border {{ $isActive ? 'bg-white border-primary border-opacity-25' : 'bg-light border-dashed' }}"
                                            id="container_{{ $day }}">
                                            <div class="row align-items-center">
                                                <div class="col-md-3">
                                                    <div class="form-check form-switch mb-0">
                                                        <input type="checkbox"
                                                            name="office_hours_{{ $day }}_active" value="1"
                                                            class="form-check-input" id="check_{{ $day }}"
                                                            {{ $isActive ? 'checked' : '' }}
                                                            onchange="toggleDayInputs('{{ $day }}')">
                                                        <label class="form-check-label fw-bold ms-1"
                                                            for="check_{{ $day }}">{{ $label }}</label>
                                                    </div>
                                                </div>
                                                <div class="col-md-9">
                                                    <div id="inputs_{{ $day }}"
                                                        class="row g-2 {{ $isActive ? '' : 'd-none' }}">
                                                        <div class="col-6">
                                                            <div
                                                                class="input-group input-group-sm border rounded-2 bg-light overflow-hidden">
                                                                <span
                                                                    class="input-group-text bg-white border-0 small text-muted px-2">Mulai</span>
                                                                <input type="time"
                                                                    name="office_hours_{{ $day }}_start"
                                                                    class="form-control border-0 bg-transparent"
                                                                    value="{{ $settings["office_hours_{$day}_start"] ?? '08:00' }}">
                                                            </div>
                                                        </div>
                                                        <div class="col-6">
                                                            <div
                                                                class="input-group input-group-sm border rounded-2 bg-light overflow-hidden">
                                                                <span
                                                                    class="input-group-text bg-white border-0 small text-muted px-2">Selesai</span>
                                                                <input type="time"
                                                                    name="office_hours_{{ $day }}_end"
                                                                    class="form-control border-0 bg-transparent"
                                                                    value="{{ $settings["office_hours_{$day}_end"] ?? '17:00' }}">
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div id="closed_text_{{ $day }}"
                                                        class="text-muted small {{ $isActive ? 'd-none' : '' }}">
                                                        <i class="fe fe-slash me-1"></i> Tutup (Libur)
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <small class="text-muted d-block mt-3">Pesan otomatis untuk setiap mode diatur di menu <a
                                href="{{ route('admin.bot-menus.index') }}" class="text-primary fw-bold">Alur Chat</a> →
                            Edit Sapaan.</small>
                    </div>
                </div>

                <!-- Big Save Button Area -->
                <div class="text-center mt-5 mb-3 px-2">
                    <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fs-5 py-3 shadow-sm fw-bolder"
                        style="letter-spacing: 0.5px;">
                        <i class="fe fe-save me-2"></i> SIMPAN JAM OPERASIONAL
                    </button>
                </div>
            </form>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        function toggleDayInputs(day) {
            const checkbox = document.getElementById('check_' + day);
            const inputs = document.getElementById('inputs_' + day);
            const closedText = document.getElementById('closed_text_' + day);
            const container = document.getElementById('container_' + day);

            if (checkbox.checked) {
                inputs.classList.remove('d-none');
                closedText.classList.add('d-none');
                container.classList.remove('bg-light', 'border-dashed');
                container.classList.add('bg-white', 'border-primary', 'border-opacity-25');
            } else {
                inputs.classList.add('d-none');
                closedText.classList.remove('d-none');
                container.classList.add('bg-light', 'border-dashed');
                container.classList.remove('bg-white', 'border-primary', 'border-opacity-25');
            }
        }
    </script>
@endpush
