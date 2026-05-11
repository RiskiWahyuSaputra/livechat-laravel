@extends('layouts.admin_template')

@section('title', 'Jam Operasional')

@push('styles')
    <style>
        .settings-page {
            padding: 24px;
            max-width: 100%;
        }

        .settings-header {
            margin-bottom: 28px;
        }

        .settings-header h3 {
            font-size: 22px;
            font-weight: 700;
            color: #1e293b;
            margin: 0 0 4px;
        }

        .settings-header p {
            color: #64748b;
            font-size: 14px;
            margin: 0;
        }

        .settings-card {
            background: #fff;
            border: 1px solid #e2e8f0;
            border-radius: 12px;
            margin-bottom: 20px;
            overflow: hidden;
        }

        .settings-card-header {
            padding: 16px 24px;
            border-bottom: 1px solid #f1f5f9;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
        }

        .settings-card-header h5 {
            font-size: 14px;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.04em;
        }

        .settings-card-header i {
            font-size: 16px;
        }

        .settings-card-body {
            padding: 20px 24px;
        }

        .field-group {
            margin-bottom: 18px;
        }

        .field-group:last-child {
            margin-bottom: 0;
        }

        .field-label {
            display: block;
            font-size: 12px;
            font-weight: 600;
            color: #475569;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            margin-bottom: 6px;
        }

        .field-input {
            width: 100%;
            padding: 9px 12px;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            font-size: 14px;
            color: #1e293b;
            background: #f8fafc;
            transition: border-color 0.15s;
        }

        .field-input:focus {
            outline: none;
            border-color: #6366f1;
            background: #fff;
            box-shadow: 0 0 0 3px rgba(99, 102, 241, 0.08);
        }

        .field-hint {
            font-size: 12px;
            color: #94a3b8;
            margin-top: 4px;
        }

        .field-divider {
            border: none;
            border-top: 1px solid #f1f5f9;
            margin: 20px 0;
        }

        /* Mode selector */
        .mode-options {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            margin-top: 6px;
        }

        .mode-option {
            flex: 1;
            min-width: 140px;
            padding: 12px 16px;
            border: 2px solid #e2e8f0;
            border-radius: 10px;
            cursor: pointer;
            transition: all 0.15s;
            display: flex;
            align-items: center;
            gap: 10px;
            background: #f8fafc;
        }

        .mode-option input[type=radio] {
            display: none;
        }

        .mode-option.active-green {
            border-color: #10b981;
            background: #f0fdf4;
        }

        .mode-option.active-yellow {
            border-color: #f59e0b;
            background: #fffbeb;
        }

        .mode-option.active-red {
            border-color: #ef4444;
            background: #fef2f2;
        }

        .mode-label {
            font-size: 13px;
            font-weight: 600;
            color: #1e293b;
        }

        .mode-desc {
            font-size: 11px;
            color: #64748b;
            margin-top: 2px;
        }

        /* Day rows */
        .day-row {
            display: grid;
            grid-template-columns: 120px 1fr;
            align-items: center;
            gap: 12px;
            padding: 10px 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .day-row:last-child {
            border-bottom: none;
        }

        .day-toggle {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .day-toggle label {
            font-size: 13px;
            font-weight: 600;
            color: #374151;
            cursor: pointer;
        }

        .time-inputs {
            display: flex;
            gap: 8px;
            align-items: center;
        }

        .time-input-wrap {
            display: flex;
            align-items: center;
            gap: 6px;
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 6px 10px;
        }

        .time-input-wrap span {
            font-size: 11px;
            color: #94a3b8;
            white-space: nowrap;
        }

        .time-input-wrap input[type=time] {
            border: none;
            background: transparent;
            font-size: 13px;
            color: #1e293b;
            outline: none;
            width: 90px;
        }

        .day-closed {
            font-size: 12px;
            color: #94a3b8;
        }

        /* Save button */
        .btn-save {
            width: 100%;
            padding: 14px;
            background: #6366f1;
            color: #fff;
            border: none;
            border-radius: 10px;
            font-size: 15px;
            font-weight: 700;
            cursor: pointer;
            transition: background 0.15s;
            letter-spacing: 0.02em;
        }

        .btn-save:hover {
            background: #4f46e5;
        }

        body.dark-mode .settings-card {
            background: #1e1e1e;
            border-color: #333;
        }

        body.dark-mode .settings-card-header {
            background: #252525;
            border-color: #333;
        }

        body.dark-mode .settings-card-header h5 {
            color: #e0e0e0;
        }

        body.dark-mode .field-label {
            color: #94a3b8;
        }

        body.dark-mode .field-input {
            background: #252525;
            border-color: #444;
            color: #e0e0e0;
        }

        body.dark-mode .field-input:focus {
            background: #2a2a2a;
        }

        body.dark-mode .mode-option {
            background: #252525;
            border-color: #444;
        }

        body.dark-mode .mode-label {
            color: #e0e0e0;
        }

        body.dark-mode .day-row {
            border-color: #333;
        }

        body.dark-mode .day-toggle label {
            color: #e0e0e0;
        }

        body.dark-mode .time-input-wrap {
            background: #252525;
            border-color: #444;
        }

        body.dark-mode .time-input-wrap input[type=time] {
            color: #e0e0e0;
        }
    </style>
@endpush

@section('content')
    <div class="settings-page">

        <div class="settings-header">
            <h3><i class="fe fe-clock" style="color:#6366f1;margin-right:8px;"></i>Jam Operasional</h3>
            <p>Kendalikan alur percakapan customer berdasarkan kondisi operasional dan jam kerja.</p>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')

            {{-- ── Mode Operasional ── --}}
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fe fe-activity" style="color:#10b981;"></i>
                    <h5>Mode Operasional Chat</h5>
                </div>
                <div class="settings-card-body">

                    @php $currentMode = $settings['system_mode'] ?? 'office_hour'; @endphp
                    <div class="field-group">
                        <label class="field-label">Mode Aktif Saat Ini</label>
                        <div class="mode-options">
                            <label class="mode-option {{ $currentMode === 'office_hour' ? 'active-green' : '' }}">
                                <input type="radio" name="system_mode" value="office_hour"
                                    {{ $currentMode === 'office_hour' ? 'checked' : '' }}>
                                <div>
                                    <div class="mode-label">🟢 Jam Kerja</div>
                                    <div class="mode-desc">Customer bisa chat & antri ke Agent</div>
                                </div>
                            </label>
                            <label class="mode-option {{ $currentMode === 'outside_office_hour' ? 'active-yellow' : '' }}">
                                <input type="radio" name="system_mode" value="outside_office_hour"
                                    {{ $currentMode === 'outside_office_hour' ? 'checked' : '' }}>
                                <div>
                                    <div class="mode-label">🟡 Di Luar Jam Kerja</div>
                                    <div class="mode-desc">Hanya dilayani AI</div>
                                </div>
                            </label>
                            <label class="mode-option {{ $currentMode === 'closed' ? 'active-red' : '' }}">
                                <input type="radio" name="system_mode" value="closed"
                                    {{ $currentMode === 'closed' ? 'checked' : '' }}>
                                <div>
                                    <div class="mode-label">🔴 Tutup</div>
                                    <div class="mode-desc">Chat ditolak sepenuhnya</div>
                                </div>
                            </label>
                        </div>
                    </div>

                    <hr class="field-divider">

                    <div class="field-group">
                        <label class="field-label">Zona Waktu Sistem</label>
                        <select name="office_hours_timezone" class="field-input" style="max-width:280px;">
                            @foreach (['Asia/Jakarta' => 'WIB (Asia/Jakarta)', 'Asia/Makassar' => 'WITA (Asia/Makassar)', 'Asia/Jayapura' => 'WIT (Asia/Jayapura)', 'UTC' => 'UTC'] as $tz => $tzLabel)
                                <option value="{{ $tz }}"
                                    {{ ($settings['office_hours_timezone'] ?? 'Asia/Jakarta') == $tz ? 'selected' : '' }}>
                                    {{ $tzLabel }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="field-group">
                        <label class="field-label">Detail Jam Operasional Per Hari</label>
                        @foreach (['monday' => 'Senin', 'tuesday' => 'Selasa', 'wednesday' => 'Rabu', 'thursday' => 'Kamis', 'friday' => 'Jumat', 'saturday' => 'Sabtu', 'sunday' => 'Minggu'] as $day => $dayLabel)
                            @php
                                $isWeekend = in_array($day, ['saturday', 'sunday']);
                                $isActive =
                                    ($settings["office_hours_{$day}_active"] ?? ($isWeekend ? '0' : '1')) == '1';
                            @endphp
                            <div class="day-row" id="container_{{ $day }}">
                                <div class="day-toggle">
                                    <div class="form-check form-switch mb-0">
                                        <input type="checkbox" name="office_hours_{{ $day }}_active"
                                            value="1" class="form-check-input" id="check_{{ $day }}"
                                            {{ $isActive ? 'checked' : '' }}
                                            onchange="toggleDayInputs('{{ $day }}')">
                                        <label class="form-check-label"
                                            for="check_{{ $day }}">{{ $dayLabel }}</label>
                                    </div>
                                </div>
                                <div>
                                    <div id="inputs_{{ $day }}"
                                        class="time-inputs {{ $isActive ? '' : 'd-none' }}">
                                        <div class="time-input-wrap">
                                            <span>Mulai</span>
                                            <input type="time" name="office_hours_{{ $day }}_start"
                                                value="{{ $settings["office_hours_{$day}_start"] ?? '08:00' }}">
                                        </div>
                                        <span style="color:#94a3b8;font-size:12px;">–</span>
                                        <div class="time-input-wrap">
                                            <span>Selesai</span>
                                            <input type="time" name="office_hours_{{ $day }}_end"
                                                value="{{ $settings["office_hours_{$day}_end"] ?? '17:00' }}">
                                        </div>
                                    </div>
                                    <div id="closed_text_{{ $day }}"
                                        class="day-closed {{ $isActive ? 'd-none' : '' }}">
                                        Libur / Tutup
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    <div class="field-hint mt-2">
                        Pesan otomatis tiap mode diatur di <a href="{{ route('admin.bot-menus.index') }}"
                            style="color:#6366f1;font-weight:600;">Alur Chat → Edit Sapaan</a>.
                    </div>

                </div>
            </div>

            {{-- ── Save ── --}}
            <div style="margin-top:8px; margin-bottom:32px;">
                <button type="submit" class="btn-save">
                    <i class="fe fe-save me-2"></i>Simpan Jam Operasional
                </button>
            </div>

        </form>
    </div>

    @push('scripts')
        <script>
            function toggleDayInputs(day) {
                const cb = document.getElementById('check_' + day);
                const inputs = document.getElementById('inputs_' + day);
                const closed = document.getElementById('closed_text_' + day);
                if (cb.checked) {
                    inputs.classList.remove('d-none');
                    closed.classList.add('d-none');
                } else {
                    inputs.classList.add('d-none');
                    closed.classList.remove('d-none');
                }
            }

            // Mode option visual toggle
            document.querySelectorAll('.mode-option input[type=radio]').forEach(radio => {
                radio.addEventListener('change', () => {
                    document.querySelectorAll('.mode-option').forEach(opt => {
                        opt.classList.remove('active-green', 'active-yellow', 'active-red');
                    });
                    const label = radio.closest('.mode-option');
                    if (radio.value === 'office_hour') label.classList.add('active-green');
                    else if (radio.value === 'outside_office_hour') label.classList.add('active-yellow');
                    else label.classList.add('active-red');
                });
            });
        </script>
    @endpush

@endsection
