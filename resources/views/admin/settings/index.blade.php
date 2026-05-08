@extends('layouts.admin_template')

@section('title', 'Pengaturan Sistem')

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

        /* Two-column grid for fields */
        .fields-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 16px;
        }

        @media (max-width: 768px) {
            .fields-grid {
                grid-template-columns: 1fr;
            }
        }

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

        .btn-copy:hover {
            background: #4f46e5;
        }

        /* Danger zone */
        .danger-box {
            background: #fffbeb;
            border: 1px solid #fcd34d;
            border-radius: 10px;
            padding: 16px;
        }

        .danger-box h6 {
            font-size: 13px;
            font-weight: 700;
            color: #92400e;
            margin: 0 0 6px;
        }

        .danger-box p {
            font-size: 12px;
            color: #78350f;
            margin: 0 0 12px;
        }

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

        .btn-danger-clean:hover {
            background: #d97706;
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
    </style>
@endpush

@section('content')
    <div class="settings-page">

        <div class="settings-header">
            <h3><i class="fe fe-settings" style="color:#6366f1;margin-right:8px;"></i>Pengaturan Sistem</h3>
            <p>Konfigurasi integrasi AI, OpenClaw, dan pengaturan umum aplikasi.</p>
        </div>

        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            <input type="hidden" name="ai_provider" value="openclaw">
            <input type="hidden" name="messaging_provider" value="openclaw">

            {{-- ── AI & OpenClaw ── --}}
            <div class="settings-card">
                <div class="settings-card-header">
                    <i class="fe fe-cpu" style="color:#6366f1;"></i>
                    <h5>Kecerdasan Buatan & OpenClaw</h5>
                </div>
                <div class="settings-card-body">

                    {{-- Gemini --}}
                    <div class="fields-grid">
                        <div class="field-group">
                            <label class="field-label">Gemini API Key</label>
                            <input type="password" name="gemini_api_key" class="field-input"
                                value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
                            <div class="field-hint">Fallback jika ingin kembali ke Gemini.</div>
                        </div>
                        <div class="field-group">
                            <label class="field-label">Model Gemini</label>
                            <select name="gemini_model" class="field-input">
                                @foreach (['gemini-pro' => 'Gemini Pro (Stabil)', 'gemini-1.5-flash' => 'Gemini 1.5 Flash (Cepat)', 'gemini-1.5-pro' => 'Gemini 1.5 Pro (Akurat)', 'gemini-2.0-flash' => 'Gemini 2.0 Flash'] as $val => $label)
                                    <option value="{{ $val }}"
                                        {{ ($settings['gemini_model'] ?? '') == $val ? 'selected' : '' }}>
                                        {{ $label }}</option>
                                @endforeach
                            </select>
                            <div class="field-hint">Dipakai saat provider AI masih Gemini.</div>
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
                                <option value="1"
                                    {{ (string) ($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED', '1')) === '1' ? 'selected' : '' }}>
                                    Aktif</option>
                                <option value="0"
                                    {{ (string) ($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED')) === '0' ? 'selected' : '' }}>
                                    Nonaktif</option>
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
                        <label class="field-label" style="color:#6366f1;"><i class="fe fe-link me-1"></i>Webhook URL
                            OpenClaw WhatsApp</label>
                        <div class="webhook-row">
                            <input type="text" id="openclawWebhookUrl"
                                value="{{ url('/api/webhook/openclaw/whatsapp') }}" readonly>
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
                    <i class="fe fe-sliders" style="color:#64748b;"></i>
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

@endsection
