@extends('layouts.admin_template')

@section('title', 'Pengaturan Integrasi')

@section('content')
<div class="row justify-content-center mb-5">
    <div class="col-md-10 col-lg-8 pe-lg-4 ps-lg-4">
        <div class="d-flex justify-content-between align-items-center mb-4 mt-2">
            <div>
                <h3 class="fw-bolder mb-0 text-dark">Pengaturan Sistem</h3>
                <p class="text-muted">Konfigurasi token layanan eksternal dan kontrol lingkungan aplikasi Anda.</p>
            </div>
        </div>
        
        <form action="{{ route('admin.settings.update') }}" method="POST">
            @csrf
            @method('PUT')
            
            <!-- AI Settings Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fe fe-command me-2"></i> Kecerdasan Buatan Pra-Claim</h5>
                </div>
                <div class="card-body pt-3 pb-4">
                    <input type="hidden" name="ai_provider" value="openclaw">
                    <input type="hidden" name="messaging_provider" value="openclaw">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">Gemini API Key</label>
                        <input type="password" name="gemini_api_key" class="form-control form-control-lg bg-light" value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
                        <small class="text-muted mt-1 d-block">Tetap boleh diisi sebagai fallback jika sewaktu-waktu ingin kembali ke Gemini.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">Model Gemini</label>
                        <div class="position-relative">
                            <select name="gemini_model" class="form-select form-select-lg bg-light border-0 py-3 cursor-pointer">
                                <option value="gemini-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-pro' ? 'selected' : '' }}>Gemini Pro (Versi Lama & Paling Stabil)</option>
                                <option value="gemini-1.5-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Sangat Cepat & Responsif)</option>
                                <option value="gemini-1.5-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Akurasi Kalimat Maksimal)</option>
                                <option value="gemini-2.0-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-2.0-flash' ? 'selected' : '' }}>Gemini 2.0 Flash</option>
                            </select>
                        </div>
                        <small class="text-muted mt-2 d-block">Dipakai saat provider AI masih Gemini.</small>
                    </div>
                    <hr class="my-4">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Base URL</label>
                        <input type="text" name="openclaw_base_url" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_base_url'] ?? env('OPENCLAW_BASE_URL', 'http://127.0.0.1:18789') }}" placeholder="http://127.0.0.1:18789">
                        <small class="text-muted mt-1 d-block">Alamat gateway OpenClaw Anda.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Hook Path</label>
                        <input type="text" name="openclaw_hook_path" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_hook_path'] ?? env('OPENCLAW_HOOK_PATH', '/hooks/agent') }}" placeholder="/hooks/agent">
                        <small class="text-muted mt-1 d-block">Mengacu ke endpoint hook agent OpenClaw. Default dokumentasi adalah `/hooks/agent`.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Hook Token</label>
                        <input type="password" name="openclaw_hook_token" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_hook_token'] ?? env('OPENCLAW_HOOK_TOKEN') }}">
                        <small class="text-muted mt-1 d-block">Token Bearer untuk memanggil hook OpenClaw.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Agent Name</label>
                        <input type="text" name="openclaw_agent_name" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_agent_name'] ?? env('OPENCLAW_AGENT_NAME', 'Website AI') }}" placeholder="Website AI">
                        <small class="text-muted mt-1 d-block">Nama agent yang menerima request dari website ini.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Model</label>
                        <input type="text" name="openclaw_model" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_model'] ?? env('OPENCLAW_MODEL', 'codex') }}" placeholder="codex">
                        <small class="text-muted mt-1 d-block">Model default Anda saat ini adalah `codex`.</small>
                    </div>
                    <div class="form-group mb-1">
                        <label class="form-label fw-bold opacity-75">OpenClaw Timeout (detik)</label>
                        <input type="number" min="5" name="openclaw_timeout_seconds" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_timeout_seconds'] ?? env('OPENCLAW_TIMEOUT_SECONDS', 30) }}">
                        <small class="text-muted mt-1 d-block">Batas tunggu website saat menunggu jawaban OpenClaw.</small>
                    </div>
                    <hr class="my-4">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw WhatsApp Enabled</label>
                        <select name="openclaw_whatsapp_enabled" class="form-select form-select-lg bg-light border-0 py-3 cursor-pointer">
                            <option value="1" {{ (string) ($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED', '1')) === '1' ? 'selected' : '' }}>Aktif</option>
                            <option value="0" {{ (string) ($settings['openclaw_whatsapp_enabled'] ?? env('OPENCLAW_WHATSAPP_ENABLED')) === '0' ? 'selected' : '' }}>Nonaktif</option>
                        </select>
                        <small class="text-muted mt-1 d-block">Aktifkan jika OpenClaw juga akan menangani jalur WhatsApp.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw CLI Path</label>
                        <input type="text" name="openclaw_cli_path" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_cli_path'] ?? env('OPENCLAW_CLI_PATH', 'openclaw') }}" placeholder="openclaw">
                        <small class="text-muted mt-1 d-block">Path binary OpenClaw pada server Laravel untuk kirim pesan outbound WhatsApp.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw WhatsApp Channel</label>
                        <input type="text" name="openclaw_whatsapp_channel" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_whatsapp_channel'] ?? env('OPENCLAW_WHATSAPP_CHANNEL', 'whatsapp') }}" placeholder="whatsapp">
                        <small class="text-muted mt-1 d-block">Nama channel yang dipakai command `openclaw message send`.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw WhatsApp Account</label>
                        <input type="text" name="openclaw_whatsapp_account" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_whatsapp_account'] ?? env('OPENCLAW_WHATSAPP_ACCOUNT') }}" placeholder="Opsional">
                        <small class="text-muted mt-1 d-block">Isi jika instance WhatsApp OpenClaw Anda memakai account atau session tertentu.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Public Base URL</label>
                        <input type="text" name="openclaw_public_base_url" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_public_base_url'] ?? env('OPENCLAW_PUBLIC_BASE_URL') }}" placeholder="https://xxxx.ngrok-free.app">
                        <small class="text-muted mt-1 d-block">Harus mengarah ke URL publik aplikasi Laravel Anda agar file di `public/images/...` bisa diambil gateway WhatsApp. Jangan isi dengan URL gateway OpenClaw bila port tunnel-nya berbeda.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">OpenClaw Bridge Token</label>
                        <input type="password" name="openclaw_bridge_token" class="form-control form-control-lg bg-light" value="{{ $settings['openclaw_bridge_token'] ?? env('OPENCLAW_BRIDGE_TOKEN') }}">
                        <small class="text-muted mt-1 d-block">Token untuk mengamankan webhook masuk dari OpenClaw ke Laravel.</small>
                    </div>
                    <div class="form-group mb-1">
                        <label class="form-label fw-bold text-info"><i class="fe fe-link"></i> OpenClaw WhatsApp Webhook URL</label>
                        <div class="input-group input-group-lg">
                            <input type="text" id="openclawWebhookUrl" class="form-control bg-white border-info text-dark" value="{{ url('/api/webhook/openclaw/whatsapp') }}" readonly>
                            <button class="btn btn-info text-white fw-bold px-4" type="button" onclick="navigator.clipboard.writeText(document.getElementById('openclawWebhookUrl').value); alert('Tautan Webhook OpenClaw tersalin!');"><i class="fe fe-copy me-1"></i> Copy</button>
                        </div>
                        <small class="text-muted mt-2 d-block">Pakai URL ini pada hook OpenClaw agar pesan WhatsApp masuk mengikuti flow chat website yang sama.</small>
                    </div>
                </div>
            </div>

            <!-- General Settings Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-0 text-dark"><i class="fe fe-settings me-2 opacity-50"></i> Umum & Sistem Database</h5>
                </div>
                <div class="card-body pt-3 pb-4">
                    <div class="form-group mb-4 pb-3 border-bottom">
                        <label class="form-label fw-bold opacity-75">Nama Aplikasi / Bisnis</label>
                        <input type="text" name="app_name" class="form-control form-control-lg border-0 bg-light" value="{{ $settings['app_name'] ?? config('app.name') }}">
                    </div>
                    
                    <div class="row pt-2 align-items-stretch">
                        <!-- Auto Cleanup -->
                        <div class="col-lg-7 d-flex align-items-center mb-4 mb-lg-0">
                            <div class="w-100">
                                <label class="form-label fw-bold text-dark"><i class="fe fe-clock me-1 text-primary"></i> Waktu Pembersihan Otomatis</label>
                                <input type="time" name="cleanup_time" class="form-control form-control-lg border-1 bg-white mb-2" style="max-width: 200px" value="{{ $settings['cleanup_time'] ?? '03:00' }}">
                                <p class="text-muted small mb-0 lh-sm">Tentukan jam persis (zona waktu server) kapankah setiap harinya antrean percakapan dari pengunjung mati akan dihapus mesin.</p>
                            </div>
                        </div>
                        
                        <!-- Manual Force Cleanup (Danger Zone) -->
                        <div class="col-lg-5">
                            <div class="h-100 p-3 rounded-4 bg-warning bg-opacity-10 border border-warning">
                                <label class="form-label text-warning-emphasis fw-bold"><i class="fe fe-alert-triangle me-1"></i> Aksi Darurat Instan</label>
                                <p class="small text-dark opacity-75 mb-3 lh-sm">Bersihkan ratusan jejak pengunjung *anonim* tak terpakai secara instan tanpa menunggu waktu.</p>
                                <button type="submit" form="cleanup-form" class="btn btn-warning w-100 fw-bold shadow-sm" style="color: #664d03; border-color: #ffc107;" onclick="return confirm('Peringatan Krusial! Anda yakin ingin menghapus / mengosongkan seluruh pengunjung anonim yang tidak aktif ke keranjang sampah Server detik ini juga?')">
                                    <i class="fe fe-trash-2 me-1"></i> Bersihkan Sampah
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Big Save Button Area -->
            <div class="text-center mt-5 mb-3 px-2">
                <button type="submit" class="btn btn-primary btn-lg w-100 rounded-pill fs-5 py-3 shadow-sm fw-bolder" style="letter-spacing: 0.5px;">
                    <i class="fe fe-save me-2"></i> TERAPKAN & SIMPAN PENGATURAN
                </button>
                <p class="text-muted small mt-3 opacity-75"><i class="fe fe-shield text-success"></i> Proses ini menyimpan ke form server Anda dengan aman, tanpa mengacaukan pembersihan sistem.</p>
            </div>
        </form>
    </div>
</div>

<!-- Standalone Background HTML Form untuk pembersihan paksa tanpa konflik form Setting -->
<form id="cleanup-form" action="{{ route('admin.settings.cleanup') }}" method="POST" class="d-none">
    @csrf
</form>
@endsection
