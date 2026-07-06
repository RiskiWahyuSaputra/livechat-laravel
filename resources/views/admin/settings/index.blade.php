@extends('layouts.admin_template')

@section('title', 'Pengaturan Sistem')

@push('styles')
<style>
    .settings-page { padding: 24px; max-width: 100%; }
    .settings-header { margin-bottom: 28px; }
    .settings-header h3 { font-size: 22px; font-weight: 700; color: #1e293b; margin: 0 0 4px; }
    .settings-header p { color: #64748b; font-size: 14px; margin: 0; }

    .settings-header h3 {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .settings-header h3 .fe,
    .settings-header h3 svg.feather { font-size: 22px; line-height: 1; }

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
    .settings-card-icon {
        width: 34px;
        height: 34px;
        border-radius: 10px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        flex: 0 0 34px;
        font-size: 16px;
        line-height: 1;
    }
    .settings-card-icon.primary { color: #6366f1; background: #eef2ff; }
    .settings-card-icon.muted { color: #64748b; background: #f1f5f9; }
    .settings-card-icon.purple { color: #8b5cf6; background: #f5f3ff; }
    .settings-card-icon .fe { margin: 0; }
    .settings-card-icon + h5 {
        line-height: 1.3;
    }
    .settings-card-body { padding: 20px 24px; }

    .field-group { margin-bottom: 18px; }
    .field-group:last-child { margin-bottom: 0; }
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
        box-shadow: 0 0 0 3px rgba(99,102,241,0.08);
    }
    .field-hint { font-size: 12px; color: #94a3b8; margin-top: 4px; }

    .provider-hint {
        line-height: 1.65;
    }
    .provider-hint .hint-title,
    .warning-hint .hint-title {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-weight: 700;
    }
    .provider-hint .hint-icon,
    .warning-hint .hint-icon {
        width: 16px;
        height: 16px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        line-height: 1;
        border-radius: 999px;
        flex: 0 0 16px;
    }
    .provider-hint .hint-icon { color: #4f46e5; background: #eef2ff; }
    .warning-hint {
        margin-top: 8px;
        padding: 10px 12px;
        background: #fef3c7;
        border-left: 3px solid #f59e0b;
        border-radius: 4px;
        font-size: 12px;
        color: #92400e;
        line-height: 1.45;
    }
    .warning-hint .hint-icon { color: #b45309; background: #fde68a; }
    .provider-hint ul {
        margin: 4px 0 0 0;
        padding-left: 16px;
    }

    .field-divider { border: none; border-top: 1px solid #f1f5f9; margin: 20px 0; }

    /* Two-column grid for fields */
    .fields-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 16px; }
    @media (max-width: 768px) { .fields-grid { grid-template-columns: 1fr; } }

    /* Mode selector */
    .mode-options { display: flex; gap: 10px; flex-wrap: wrap; margin-top: 6px; }
    .mode-option {
        flex: 1; min-width: 140px;
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
    .mode-option input[type=radio] { display: none; }
    .mode-option.active-green { border-color: #10b981; background: #f0fdf4; }
    .mode-option.active-yellow { border-color: #f59e0b; background: #fffbeb; }
    .mode-option.active-red { border-color: #ef4444; background: #fef2f2; }
    .mode-label { font-size: 13px; font-weight: 600; color: #1e293b; }
    .mode-desc { font-size: 11px; color: #64748b; margin-top: 2px; }

    /* Day rows */
    .day-row {
        display: grid;
        grid-template-columns: 120px 1fr;
        align-items: center;
        gap: 12px;
        padding: 10px 0;
        border-bottom: 1px solid #f1f5f9;
    }
    .day-row:last-child { border-bottom: none; }
    .day-toggle { display: flex; align-items: center; gap: 8px; }
    .day-toggle label { font-size: 13px; font-weight: 600; color: #374151; cursor: pointer; }
    .time-inputs { display: flex; gap: 8px; align-items: center; }
    .time-input-wrap {
        display: flex;
        align-items: center;
        gap: 6px;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 6px 10px;
    }
    .time-input-wrap span { font-size: 11px; color: #94a3b8; white-space: nowrap; }
    .time-input-wrap input[type=time] {
        border: none;
        background: transparent;
        font-size: 13px;
        color: #1e293b;
        outline: none;
        width: 90px;
    }
    .day-closed { font-size: 12px; color: #94a3b8; }

    /* Webhook URL */
    .webhook-row {
        display: flex;
        gap: 8px;
        align-items: center;
    }
    .webhook-row input {
        flex: 1;
        padding: 9px 12px;
        border: 1px solid #c7d2fe;
        border-radius: 8px;
        font-size: 13px;
        background: #eef2ff;
        color: #3730a3;
    }
    .btn-copy {
        padding: 9px 16px;
        background: #6366f1;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        white-space: nowrap;
        transition: background 0.15s;
    }
    .btn-copy:hover { background: #4f46e5; }

    /* Danger zone */
    .danger-box {
        background: #fffbeb;
        border: 1px solid #fcd34d;
        border-radius: 10px;
        padding: 16px;
    }
    .danger-box h6 { font-size: 13px; font-weight: 700; color: #92400e; margin: 0 0 6px; }
    .danger-box p { font-size: 12px; color: #78350f; margin: 0 0 12px; }
    .btn-danger-clean {
        width: 100%;
        padding: 9px;
        background: #f59e0b;
        color: #fff;
        border: none;
        border-radius: 8px;
        font-size: 13px;
        font-weight: 600;
        cursor: pointer;
        transition: background 0.15s;
    }
    .btn-danger-clean:hover { background: #d97706; }

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
    .btn-save:hover { background: #4f46e5; }

    body.dark-mode .settings-card { background: #1e1e1e; border-color: #333; }
    body.dark-mode .settings-card-header { background: #252525; border-color: #333; }
    body.dark-mode .settings-card-header h5 { color: #e0e0e0; }
    body.dark-mode .field-label { color: #94a3b8; }
    body.dark-mode .field-input { background: #252525; border-color: #444; color: #e0e0e0; }
    body.dark-mode .field-input:focus { background: #2a2a2a; }
    body.dark-mode .mode-option { background: #252525; border-color: #444; }
    body.dark-mode .mode-label { color: #e0e0e0; }
    body.dark-mode .day-row { border-color: #333; }
    body.dark-mode .day-toggle label { color: #e0e0e0; }
    body.dark-mode .time-input-wrap { background: #252525; border-color: #444; }
    body.dark-mode .time-input-wrap input[type=time] { color: #e0e0e0; }
</style>
@endpush

@section('content')
<div class="settings-page">

    <div class="settings-header">
        <h3><i class="fe fe-settings" style="color:#6366f1;"></i>Pengaturan Sistem</h3>
        <p>Konfigurasi integrasi, mode operasional, dan pengaturan umum aplikasi.</p>
    </div>

    <form action="{{ route('admin.settings.update') }}" method="POST">
        @csrf
        @method('PUT')
        <input type="hidden" name="messaging_provider" value="openclaw">

        {{-- ── AI & OpenClaw ── --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <span class="settings-card-icon primary"><i class="fe fe-cpu"></i></span>
                <h5>Kecerdasan Buatan & OpenClaw</h5>
            </div>
            <div class="settings-card-body">

                {{-- AI Provider --}}
                <div class="field-group" style="margin-bottom:24px;">
                    <label class="field-label">AI Provider</label>
                    <select name="ai_provider" class="field-input">
                        @foreach(['openclaw' => 'OpenClaw (Local)', 'groq' => 'Groq (Cloud - Gratis)', 'gemini' => 'Gemini (Cloud)'] as $val => $label)
                            <option value="{{ $val }}" {{ ($settings['ai_provider'] ?? 'openclaw') == $val ? 'selected' : '' }}>{{ $label }}</option>
                        @endforeach
                    </select>
                    <div class="field-hint provider-hint">
                        <span class="hint-title"><span class="hint-icon"><i class="fe fe-settings"></i></span>Pilih provider AI:</span>
                        <ul>
                            <li><strong>OpenClaw</strong>: Lokal, unlimited, butuh gateway running</li>
                            <li><strong>Groq</strong>: Cloud, gratis, cepat, rate limit tinggi, <strong>isi "Groq API Key" di bawah</strong></li>
                            <li><strong>Gemini</strong>: Cloud, rate limit rendah, <strong>isi "Gemini API Key" di bawah</strong></li>
                        </ul>
                    </div>
                </div>

                <hr class="field-divider">

                {{-- Gemini --}}
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Gemini API Key</label>
                        <input type="password" name="gemini_api_key" class="field-input"
                               value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
                        <div class="field-hint">Fallback jika ingin kembali ke Gemini.</div>
                        <div class="warning-hint">
                            <span class="hint-title"><span class="hint-icon"><i class="fe fe-alert-triangle"></i></span>Rate Limit:</span>
                            API Key gratis Gemini memiliki batasan ~15-60 request/menit. Jika user bertanya lebih dari 3x dalam waktu singkat, sistem akan error selama 2-5 menit. Gunakan OpenClaw atau upgrade ke API Key berbayar.
                        </div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Model Gemini</label>
                        <select name="gemini_model" class="field-input">
                            @foreach(['gemini-pro' => 'Gemini Pro (Stabil)', 'gemini-1.5-flash' => 'Gemini 1.5 Flash (Cepat)', 'gemini-1.5-pro' => 'Gemini 1.5 Pro (Akurat)', 'gemini-2.0-flash' => 'Gemini 2.0 Flash'] as $val => $label)
                                <option value="{{ $val }}" {{ ($settings['gemini_model'] ?? '') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="field-hint">Dipakai saat provider AI masih Gemini.</div>
                    </div>
                </div>

                <hr class="field-divider">

                {{-- Groq --}}
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Groq API Key</label>
                        <input type="password" name="groq_api_key" class="field-input"
                               value="{{ $settings['groq_api_key'] ?? env('GROQ_API_KEY') }}"
                               placeholder="gsk_...">
                        <div class="field-hint">API Key dari <a href="https://console.groq.com" target="_blank" style="color:#4f46e5;">console.groq.com</a> (Gratis & Cepat)</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">Model Groq</label>
                        <select name="groq_model" class="field-input">
                            @foreach(['llama-3.3-70b-versatile' => 'Llama 3.3 70B (Recommended)', 'llama-3.1-70b-versatile' => 'Llama 3.1 70B', 'mixtral-8x7b-32768' => 'Mixtral 8x7B'] as $val => $label)
                                <option value="{{ $val }}" {{ ($settings['groq_model'] ?? 'llama-3.3-70b-versatile') == $val ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                        <div class="field-hint">Model yang digunakan saat provider = Groq</div>
                    </div>
                </div>

                <hr class="field-divider">

                {{-- OpenClaw Core --}}
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">OpenClaw Base URL</label>
                        <input type="text" name="openclaw_base_url" class="field-input"
                               value="{{ $settings['openclaw_base_url'] ?? env('OPENCLAW_BASE_URL', 'http://127.0.0.1:18789') }}"
                               placeholder="http://127.0.0.1:18789">
                        <div class="field-hint">Alamat gateway OpenClaw.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Hook Path</label>
                        <input type="text" name="openclaw_hook_path" class="field-input"
                               value="{{ $settings['openclaw_hook_path'] ?? env('OPENCLAW_HOOK_PATH', '/hooks/agent') }}"
                               placeholder="/hooks/agent">
                        <div class="field-hint">Endpoint hook agent OpenClaw.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Hook Token</label>
                        <input type="password" name="openclaw_hook_token" class="field-input"
                               value="{{ $settings['openclaw_hook_token'] ?? env('OPENCLAW_HOOK_TOKEN') }}">
                        <div class="field-hint">Token Bearer untuk hook OpenClaw.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Agent Name</label>
                        <input type="text" name="openclaw_agent_name" class="field-input"
                               value="{{ $settings['openclaw_agent_name'] ?? env('OPENCLAW_AGENT_NAME', 'Website AI') }}"
                               placeholder="Website AI">
                        <div class="field-hint">Nama agent penerima request.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Model</label>
                        <input type="text" name="openclaw_model" class="field-input"
                               value="{{ $settings['openclaw_model'] ?? env('OPENCLAW_MODEL', 'codex') }}"
                               placeholder="codex">
                        <div class="field-hint">Model default: <code>codex</code>.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Timeout (detik)</label>
                        <input type="number" min="5" name="openclaw_timeout_seconds" class="field-input"
                               value="{{ $settings['openclaw_timeout_seconds'] ?? env('OPENCLAW_TIMEOUT_SECONDS', 30) }}">
                        <div class="field-hint">Batas tunggu jawaban OpenClaw.</div>
                    </div>
                </div>

                <hr class="field-divider">

                {{-- WhatsApp --}}
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">WhatsApp Enabled</label>
                        <select name="openclaw_whatsapp_enabled" class="field-input">
                            <option value="1" {{ (string)($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED', '1')) === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ (string)($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED')) === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <div class="field-hint">Aktifkan jalur WhatsApp via OpenClaw.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw CLI Path</label>
                        <input type="text" name="openclaw_cli_path" class="field-input"
                               value="{{ $settings['openclaw_cli_path'] ?? env('OPENCLAW_CLI_PATH', 'openclaw') }}"
                               placeholder="openclaw">
                        <div class="field-hint">Path binary OpenClaw di server.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">WhatsApp Channel</label>
                        <input type="text" name="openclaw_whatsapp_channel" class="field-input"
                               value="{{ $settings['openclaw_whatsapp_channel'] ?? env('OPENCLAW_WHATSAPP_CHANNEL', 'whatsapp') }}"
                               placeholder="whatsapp">
                        <div class="field-hint">Nama channel untuk <code>openclaw message send</code>.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">WhatsApp Account</label>
                        <input type="text" name="openclaw_whatsapp_account" class="field-input"
                               value="{{ $settings['openclaw_whatsapp_account'] ?? env('OPENCLAW_WHATSAPP_ACCOUNT') }}"
                               placeholder="Opsional">
                        <div class="field-hint">Session/account tertentu (opsional).</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Public Base URL</label>
                        <input type="text" name="openclaw_public_base_url" class="field-input"
                               value="{{ $settings['openclaw_public_base_url'] ?? env('OPENCLAW_PUBLIC_BASE_URL') }}"
                               placeholder="https://xxxx.ngrok-free.app">
                        <div class="field-hint">URL publik Laravel agar file bisa diambil gateway WA.</div>
                    </div>
                    <div class="field-group">
                        <label class="field-label">OpenClaw Bridge Token</label>
                        <input type="password" name="openclaw_bridge_token" class="field-input"
                               value="{{ $settings['openclaw_bridge_token'] ?? env('OPENCLAW_BRIDGE_TOKEN') }}">
                        <div class="field-hint">Token keamanan webhook masuk dari OpenClaw.</div>
                    </div>
                </div>

                <div class="field-group" style="margin-top:4px;">
                    <label class="field-label" style="color:#6366f1;"><i class="fe fe-link me-1"></i>Webhook URL OpenClaw WhatsApp</label>
                    <div class="webhook-row">
                        <input type="text" id="openclawWebhookUrl" value="{{ url('/api/webhook/openclaw/whatsapp') }}" readonly>
                        <button type="button" class="btn-copy"
                                onclick="navigator.clipboard.writeText(document.getElementById('openclawWebhookUrl').value);this.textContent='Tersalin!';setTimeout(()=>this.textContent='Salin',2000);">
                            <i class="fe fe-copy me-1"></i>Salin
                        </button>
                    </div>
                    <div class="field-hint">Pasang URL ini di hook OpenClaw agar pesan WA masuk ke flow chat.</div>
                </div>

            </div>
        </div>

        {{-- ── Umum ── --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <span class="settings-card-icon muted"><i class="fe fe-sliders"></i></span>
                <h5>Umum & Sistem</h5>
            </div>
            <div class="settings-card-body">
                <div class="fields-grid">
                    <div class="field-group">
                        <label class="field-label">Nama Aplikasi / Bisnis</label>
                        <input type="text" name="app_name" class="field-input"
                               value="{{ $settings['app_name'] ?? config('app.name') }}">
                    </div>
                    <div class="field-group">
                        <label class="field-label">Waktu Pembersihan Otomatis</label>
                        <input type="time" name="cleanup_time" class="field-input" style="max-width:160px;"
                               value="{{ $settings['cleanup_time'] ?? '03:00' }}">
                        <div class="field-hint">Jam pembersihan antrean pengunjung mati (zona waktu server).</div>
                    </div>
                </div>

                <div class="danger-box" style="margin-top:4px;">
                    <h6><i class="fe fe-alert-triangle me-1"></i>Aksi Darurat — Bersihkan Sekarang</h6>
                    <p>Hapus seluruh jejak pengunjung anonim tak terpakai secara instan tanpa menunggu jadwal.</p>
                    <button type="submit" form="cleanup-form" class="btn-danger-clean"
                            onclick="return confirm('Yakin ingin membersihkan semua pengunjung anonim tidak aktif sekarang?')">
                        <i class="fe fe-trash-2 me-1"></i> Bersihkan Sampah Sekarang
                    </button>
                </div>
            </div>
        </div>

        {{-- ── Embed Widget Settings ── --}}
        <div class="settings-card">
            <div class="settings-card-header">
                <span class="settings-card-icon purple"><i class="fe fe-code"></i></span>
                <h5>Embed Widget Settings</h5>
            </div>
            <div class="settings-card-body">
                <div class="field-group">
                    <label class="field-label">Allowed Embed Domains</label>
                    <textarea name="embed_allowed_domains"
                              class="field-input"
                              rows="5"
                              placeholder="example.com&#10;*.mysite.org&#10;sub.another.com"
                              style="resize:vertical; font-family:monospace; font-size:13px;">{{ old('embed_allowed_domains', \App\Models\Setting::get('embed_allowed_domains', '')) }}</textarea>
                    <div class="field-hint">
                        Satu domain per baris. Gunakan <code>*.example.com</code> untuk mengizinkan semua subdomain.
                        Kosongkan untuk mengizinkan semua domain (tanpa pembatasan).
                    </div>
                </div>
            </div>
        </div>

        {{-- ── Save ── --}}
        <div style="margin-top:8px; margin-bottom:32px;">
            <button type="submit" class="btn-save">
                <i class="fe fe-save me-2"></i>Simpan Pengaturan
            </button>
        </div>

    </form>
</div>

<form id="cleanup-form" action="{{ route('admin.settings.cleanup') }}" method="POST" class="d-none">
    @csrf
</form>

@push('scripts')
<script>
    // Integration logic or other scripts can stay if any
</script>
@endpush

@endsection
