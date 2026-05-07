@extends('layouts.admin_template')

@section('title', 'Pengaturan Sistem')

@push('styles')
<style>
:root {
    --primary: #4f46e5;
    --success: #10b981;
    --warning: #f59e0b;
    --danger: #ef4444;
    --dark: #1f2937;
    --gray-100: #f3f4f6;
    --gray-200: #e5e7eb;
    --gray-500: #6b7280;
}

.settings-card {
    background: white;
    border-radius: 12px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    border: 1px solid var(--gray-200);
    margin-bottom: 20px;
    overflow: hidden;
}

.settings-card-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--gray-200);
    display: flex;
    align-items: center;
    gap: 12px;
}

.settings-card-header .icon-box {
    width: 34px;
    height: 34px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 15px;
    flex-shrink: 0;
}

.settings-card-header h6 {
    font-size: 14px;
    font-weight: 700;
    color: var(--dark);
    margin: 0;
}

.settings-card-header p {
    font-size: 12px;
    color: var(--gray-500);
    margin: 2px 0 0;
}

.settings-card-body {
    padding: 24px;
}

/* Mode Cards */
.mode-option {
    position: relative;
    cursor: pointer;
}
.mode-option input[type="radio"] {
    position: absolute;
    opacity: 0;
    width: 0;
    height: 0;
}
.mode-card {
    border: 2px solid var(--gray-200);
    border-radius: 10px;
    padding: 16px;
    background: var(--gray-100);
    transition: all 0.15s ease;
    height: 100%;
    cursor: pointer;
}
.mode-card:hover {
    border-color: #c7d2fe;
    background: #eef2ff;
}
.mode-option input:checked + .mode-card.green  { border-color: #10b981; background: #ecfdf5; }
.mode-option input:checked + .mode-card.yellow { border-color: #f59e0b; background: #fffbeb; }
.mode-option input:checked + .mode-card.red    { border-color: #ef4444; background: #fef2f2; }

.mode-dot {
    width: 10px;
    height: 10px;
    border-radius: 50%;
    display: inline-block;
    flex-shrink: 0;
}
.mode-dot.green  { background: #10b981; }
.mode-dot.yellow { background: #f59e0b; }
.mode-dot.red    { background: #ef4444; }

.mode-title { font-size: 13px; font-weight: 700; margin-bottom: 2px; }
.mode-desc  { font-size: 11px; color: var(--gray-500); line-height: 1.4; }

/* Form fields */
.field-group {
    padding: 16px 0;
    border-bottom: 1px solid var(--gray-100);
    display: grid;
    grid-template-columns: 200px 1fr;
    gap: 16px;
    align-items: start;
}
.field-group:last-child { border-bottom: none; padding-bottom: 0; }
.field-group:first-child { padding-top: 0; }

.field-meta .field-title { font-size: 13px; font-weight: 600; color: var(--dark); }
.field-meta .field-hint  { font-size: 11px; color: var(--gray-500); margin-top: 2px; line-height: 1.4; }

@media (max-width: 640px) {
    .field-group { grid-template-columns: 1fr; gap: 6px; }
}

.form-control, .form-select {
    font-size: 13px;
    border-radius: 8px;
    border-color: var(--gray-200);
    background: var(--gray-100);
    padding: 8px 12px;
}
.form-control:focus, .form-select:focus {
    background: white;
    border-color: var(--primary);
    box-shadow: 0 0 0 3px rgba(79,70,229,0.1);
}

.section-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: .08em;
    color: var(--gray-500);
    margin-bottom: 12px;
    margin-top: 4px;
}

.webhook-box {
    background: var(--gray-100);
    border: 1px solid var(--gray-200);
    border-radius: 8px;
    padding: 14px 16px;
    margin-top: 16px;
}

.danger-box {
    background: #fffbeb;
    border: 1px solid #fde68a;
    border-radius: 10px;
    padding: 16px 20px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 16px;
    flex-wrap: wrap;
}

.save-bar {
    position: sticky;
    bottom: 0;
    background: rgba(255,255,255,0.96);
    backdrop-filter: blur(8px);
    border-top: 1px solid var(--gray-200);
    padding: 12px 0;
    margin-top: 8px;
    z-index: 50;
}

body.dark-mode .settings-card { background: #1e1e1e; border-color: #2a2a2a; }
body.dark-mode .settings-card-header { border-color: #2a2a2a; }
body.dark-mode .settings-card-header h6 { color: #e0e0e0; }
body.dark-mode .mode-card { background: #252525; border-color: #333; }
body.dark-mode .form-control, body.dark-mode .form-select { background: #252525; border-color: #333; color: #e0e0e0; }
body.dark-mode .field-group { border-color: #2a2a2a; }
body.dark-mode .field-meta .field-title { color: #d0d5dd; }
body.dark-mode .webhook-box { background: #252525; border-color: #333; }
body.dark-mode .save-bar { background: rgba(20,20,20,0.96); border-color: #2a2a2a; }
</style>
@endpush

@section('content')

@if(session('success'))
<div class="alert alert-success border-0 shadow-sm mb-4" style="border-radius:10px;">
    <i class="fe fe-check-circle me-2"></i> {{ session('success') }}
</div>
@endif

<div class="row mb-4">
    <div class="col-12">
        <h4 class="page-title mb-1">Pengaturan Sistem</h4>
        <p class="text-muted small mb-0">Konfigurasi mode operasional, integrasi AI, dan parameter sistem.</p>
    </div>
</div>

<form action="{{ route('admin.settings.update') }}" method="POST" id="settings-form">
@csrf
@method('PUT')
<input type="hidden" name="ai_provider" value="openclaw">
<input type="hidden" name="messaging_provider" value="openclaw">

{{-- ── 1. MODE OPERASIONAL ── --}}
<div class="settings-card">
    <div class="settings-card-header">
        <div class="icon-box" style="background:#ecfdf5; color:#10b981;">
            <i class="fe fe-activity"></i>
        </div>
        <div>
            <h6>Mode Operasional Chat</h6>
            <p>Kendalikan alur percakapan customer berdasarkan kondisi operasional.</p>
        </div>
    </div>
    <div class="settings-card-body">
        @php $currentMode = $settings['system_mode'] ?? 'office_hour'; @endphp

        <div class="row g-3 mb-4">
            <div class="col-md-4">
                <label class="mode-option d-block h-100">
                    <input type="radio" name="system_mode" value="office_hour" {{ $currentMode === 'office_hour' ? 'checked' : '' }}>
                    <div class="mode-card green">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="mode-dot green"></span>
                            <span class="mode-title text-success">Jam Kerja</span>
                        </div>
                        <div class="mode-desc">Customer bisa chat &amp; antri ke Agent</div>
                    </div>
                </label>
            </div>
            <div class="col-md-4">
                <label class="mode-option d-block h-100">
                    <input type="radio" name="system_mode" value="outside_office_hour" {{ $currentMode === 'outside_office_hour' ? 'checked' : '' }}>
                    <div class="mode-card yellow">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="mode-dot yellow"></span>
                            <span class="mode-title text-warning">Di Luar Jam Kerja</span>
                        </div>
                        <div class="mode-desc">Hanya dilayani AI, tidak ada Agent</div>
                    </div>
                </label>
            </div>
            <div class="col-md-4">
                <label class="mode-option d-block h-100">
                    <input type="radio" name="system_mode" value="closed" {{ $currentMode === 'closed' ? 'checked' : '' }}>
                    <div class="mode-card red">
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <span class="mode-dot red"></span>
                            <span class="mode-title text-danger">Tutup</span>
                        </div>
                        <div class="mode-desc">Chat ditolak sepenuhnya</div>
                    </div>
                </label>
            </div>
        </div>
        @error('system_mode') <div class="text-danger small mb-3">{{ $message }}</div> @enderror

        <div class="row g-3">
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Jam Buka <span class="text-muted fw-normal">(Senin–Jumat)</span></label>
                <input type="time" name="office_hours_start"
                    class="form-control @error('office_hours_start') is-invalid @enderror"
                    value="{{ $settings['office_hours_start'] ?? '09:00' }}">
                @error('office_hours_start') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-sm-6 col-md-4">
                <label class="form-label fw-semibold" style="font-size:12px;">Jam Tutup <span class="text-muted fw-normal">(Senin–Jumat)</span></label>
                <input type="time" name="office_hours_end"
                    class="form-control @error('office_hours_end') is-invalid @enderror"
                    value="{{ $settings['office_hours_end'] ?? '17:00' }}">
                @error('office_hours_end') <div class="invalid-feedback">{{ $message }}</div> @enderror
            </div>
            <div class="col-md-4 d-flex align-items-end">
                <p class="text-muted mb-1 lh-sm" style="font-size:11px;">
                    Pesan otomatis tiap mode diatur di
                    <a href="{{ route('admin.bot-menus.index') }}" class="fw-semibold" style="color:var(--primary);">Alur Chat →</a>
                </p>
            </div>
        </div>
    </div>
</div>

{{-- ── 2. KECERDASAN BUATAN ── --}}
<div class="settings-card">
    <div class="settings-card-header">
        <div class="icon-box" style="background:#eef2ff; color:#4f46e5;">
            <i class="fe fe-cpu"></i>
        </div>
        <div>
            <h6>Kecerdasan Buatan Pra-Claim</h6>
            <p>Konfigurasi provider AI dan parameter koneksi OpenClaw.</p>
        </div>
    </div>
    <div class="settings-card-body">

        <p class="section-label">Gemini (Fallback)</p>

        <div class="field-group">
            <div class="field-meta">
                <div class="field-title">Gemini API Key</div>
                <div class="field-hint">Fallback jika ingin kembali ke Gemini.</div>
            </div>
            <input type="password" name="gemini_api_key" class="form-control" value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
        </div>

        <div class="field-group">
            <div class="field-meta">
                <div class="field-title">Model Gemini</div>
                <div class="field-hint">Dipakai saat provider AI masih Gemini.</div>
            </div>
            <select name="gemini_model" class="form-select">
                <option value="gemini-pro"       {{ ($settings['gemini_model'] ?? '') == 'gemini-pro'       ? 'selected' : '' }}>Gemini Pro (Stabil)</option>
                <option value="gemini-1.5-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Cepat)</option>
                <option value="gemini-1.5-pro"   {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-pro'   ? 'selected' : '' }}>Gemini 1.5 Pro (Akurat)</option>
                <option value="gemini-2.0-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash</option>
            </select>
        </div>

        <p class="section-label mt-4">OpenClaw — Koneksi &amp; Agent</p>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Base URL</label>
                <input type="text" name="openclaw_base_url" class="form-control" value="{{ $settings['openclaw_base_url'] ?? env('OPENCLAW_BASE_URL', 'http://127.0.0.1:18789') }}" placeholder="http://127.0.0.1:18789">
                <div class="form-text" style="font-size:11px;">Alamat gateway OpenClaw.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Hook Path</label>
                <input type="text" name="openclaw_hook_path" class="form-control" value="{{ $settings['openclaw_hook_path'] ?? env('OPENCLAW_HOOK_PATH', '/hooks/agent') }}" placeholder="/hooks/agent">
                <div class="form-text" style="font-size:11px;">Endpoint hook agent. Default: <code>/hooks/agent</code>.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Hook Token</label>
                <input type="password" name="openclaw_hook_token" class="form-control" value="{{ $settings['openclaw_hook_token'] ?? env('OPENCLAW_HOOK_TOKEN') }}">
                <div class="form-text" style="font-size:11px;">Token Bearer untuk memanggil hook OpenClaw.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Agent Name</label>
                <input type="text" name="openclaw_agent_name" class="form-control" value="{{ $settings['openclaw_agent_name'] ?? env('OPENCLAW_AGENT_NAME', 'Website AI') }}" placeholder="Website AI">
                <div class="form-text" style="font-size:11px;">Nama agent yang menerima request dari website ini.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Model</label>
                <input type="text" name="openclaw_model" class="form-control" value="{{ $settings['openclaw_model'] ?? env('OPENCLAW_MODEL', 'codex') }}" placeholder="codex">
                <div class="form-text" style="font-size:11px;">Model default: <code>codex</code>.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Timeout (detik)</label>
                <input type="number" min="5" name="openclaw_timeout_seconds" class="form-control" value="{{ $settings['openclaw_timeout_seconds'] ?? env('OPENCLAW_TIMEOUT_SECONDS', 30) }}">
                <div class="form-text" style="font-size:11px;">Batas tunggu jawaban OpenClaw.</div>
            </div>
        </div>

        <p class="section-label mt-4">OpenClaw — WhatsApp</p>

        <div class="row g-3 mb-2">
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">WhatsApp Enabled</label>
                <select name="openclaw_whatsapp_enabled" class="form-select">
                    <option value="1" {{ (string)($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED', '1')) === '1' ? 'selected' : '' }}>Aktif</option>
                    <option value="0" {{ (string)($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED')) === '0' ? 'selected' : '' }}>Nonaktif</option>
                </select>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">CLI Path</label>
                <input type="text" name="openclaw_cli_path" class="form-control" value="{{ $settings['openclaw_cli_path'] ?? env('OPENCLAW_CLI_PATH', 'openclaw') }}" placeholder="openclaw">
                <div class="form-text" style="font-size:11px;">Path binary OpenClaw untuk kirim pesan outbound.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">WhatsApp Channel</label>
                <input type="text" name="openclaw_whatsapp_channel" class="form-control" value="{{ $settings['openclaw_whatsapp_channel'] ?? env('OPENCLAW_WHATSAPP_CHANNEL', 'whatsapp') }}" placeholder="whatsapp">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">WhatsApp Account <span class="text-muted fw-normal">(opsional)</span></label>
                <input type="text" name="openclaw_whatsapp_account" class="form-control" value="{{ $settings['openclaw_whatsapp_account'] ?? env('OPENCLAW_WHATSAPP_ACCOUNT') }}" placeholder="Opsional">
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Public Base URL</label>
                <input type="text" name="openclaw_public_base_url" class="form-control" value="{{ $settings['openclaw_public_base_url'] ?? env('OPENCLAW_PUBLIC_BASE_URL') }}" placeholder="https://xxxx.ngrok-free.app">
                <div class="form-text" style="font-size:11px;">URL publik Laravel untuk file gambar WhatsApp.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Bridge Token</label>
                <input type="password" name="openclaw_bridge_token" class="form-control" value="{{ $settings['openclaw_bridge_token'] ?? env('OPENCLAW_BRIDGE_TOKEN') }}">
                <div class="form-text" style="font-size:11px;">Token untuk webhook masuk dari OpenClaw ke Laravel.</div>
            </div>
        </div>

        <div class="webhook-box">
            <div class="d-flex align-items-center gap-2 mb-2">
                <i class="fe fe-link" style="color:var(--primary); font-size:13px;"></i>
                <span class="fw-semibold" style="font-size:12px; color:var(--primary);">Webhook URL OpenClaw WhatsApp</span>
            </div>
            <div class="input-group input-group-sm">
                <input type="text" id="webhookUrl" class="form-control font-monospace" value="{{ url('/api/webhook/openclaw/whatsapp') }}" readonly style="font-size:12px;">
                <button class="btn btn-outline-primary btn-sm fw-semibold" type="button"
                    onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value).then(()=>{ this.textContent='✓ Tersalin'; setTimeout(()=>{ this.textContent='Copy'; },2000); })">
                    Copy
                </button>
            </div>
            <div class="form-text mt-1" style="font-size:11px;">Pakai URL ini pada hook OpenClaw agar pesan WhatsApp masuk mengikuti flow chat website.</div>
        </div>
    </div>
</div>

{{-- ── 3. UMUM & SISTEM ── --}}
<div class="settings-card">
    <div class="settings-card-header">
        <div class="icon-box" style="background:var(--gray-100); color:var(--gray-500);">
            <i class="fe fe-settings"></i>
        </div>
        <div>
            <h6>Umum &amp; Sistem Database</h6>
            <p>Nama aplikasi, jadwal pembersihan, dan aksi darurat.</p>
        </div>
    </div>
    <div class="settings-card-body">
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">Nama Aplikasi / Bisnis</label>
                <input type="text" name="app_name" class="form-control" value="{{ $settings['app_name'] ?? config('app.name') }}">
                <div class="form-text" style="font-size:11px;">Ditampilkan di header dan notifikasi sistem.</div>
            </div>
            <div class="col-md-6">
                <label class="form-label fw-semibold" style="font-size:12px;">
                    <i class="fe fe-clock me-1" style="color:var(--primary);"></i> Waktu Pembersihan Otomatis
                </label>
                <input type="time" name="cleanup_time" class="form-control" value="{{ $settings['cleanup_time'] ?? '03:00' }}">
                <div class="form-text" style="font-size:11px;">Jam (zona waktu server) antrean pengunjung mati dihapus setiap hari.</div>
            </div>
        </div>

        <div class="danger-box">
            <div>
                <p class="fw-semibold mb-1" style="font-size:13px; color:#92400e;">
                    <i class="fe fe-alert-triangle me-1"></i> Aksi Darurat Instan
                </p>
                <p class="mb-0" style="font-size:12px; color:#78350f; opacity:.85;">
                    Bersihkan jejak pengunjung anonim tak terpakai secara instan tanpa menunggu jadwal.
                </p>
            </div>
            <button type="submit" form="cleanup-form" class="btn btn-warning btn-sm fw-bold px-4 flex-shrink-0"
                style="color:#664d03;"
                onclick="return confirm('Peringatan! Anda yakin ingin menghapus seluruh pengunjung anonim yang tidak aktif sekarang?')">
                <i class="fe fe-trash-2 me-1"></i> Bersihkan Sampah
            </button>
        </div>
    </div>
</div>

{{-- ── SAVE BAR ── --}}
<div class="save-bar">
    <div class="d-flex align-items-center justify-content-between">
        <span class="text-muted d-none d-sm-block" style="font-size:12px;">
            <i class="fe fe-shield me-1 text-success"></i> Perubahan disimpan ke server dengan aman.
        </span>
        <button type="submit" class="btn btn-primary fw-bold px-5 ms-auto" style="border-radius:8px;">
            <i class="fe fe-save me-2"></i> Simpan Pengaturan
        </button>
    </div>
</div>

</form>

<form id="cleanup-form" action="{{ route('admin.settings.cleanup') }}" method="POST" class="d-none">
    @csrf
</form>

@push('scripts')
<script>
document.querySelectorAll('input[name="system_mode"]').forEach(radio => {
    radio.addEventListener('change', function() {
        const map = {
            office_hour:         'green',
            outside_office_hour: 'yellow',
            closed:              'red',
        };
        document.querySelectorAll('input[name="system_mode"]').forEach(r => {
            const card = r.nextElementSibling;
            card.style.borderColor = '';
            card.style.background = '';
        });
        const card = this.nextElementSibling;
        const colors = {
            green:  { border: '#10b981', bg: '#ecfdf5' },
            yellow: { border: '#f59e0b', bg: '#fffbeb' },
            red:    { border: '#ef4444', bg: '#fef2f2' },
        };
        const c = colors[map[this.value]];
        if (c) { card.style.borderColor = c.border; card.style.background = c.bg; }
    });
});
</script>
@endpush

@endsection
