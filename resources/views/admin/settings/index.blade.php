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
            
            <!-- Whapi Settings Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-0 text-success"><i class="fe fe-message-circle me-2"></i> Integrasi Whapi.cloud</h5>
                </div>
                <div class="card-body pt-3 pb-4">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">Whapi API Token</label>
                        <input type="password" name="whapi_token" class="form-control form-control-lg bg-light" value="{{ $settings['whapi_token'] ?? env('WHAPI_TOKEN') }}">
                        <small class="text-muted mt-1 d-block">Kunci rahasia *(API Key)* yang didapatkan dari dashboard Whapi.cloud.</small>
                    </div>
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">Whapi Admin Number</label>
                        <input type="text" name="whapi_admin_number" class="form-control form-control-lg bg-light" value="{{ $settings['whapi_admin_number'] ?? env('WHAPI_ADMIN_NUMBER') }}" placeholder="Misal: 628123456789">
                        <small class="text-muted mt-1 d-block">Nomor WhatsApp utama penerima pesan tanpa menggunakan simbol `+`.</small>
                    </div>
                    <div class="form-group mb-1">
                        <label class="form-label fw-bold text-info"><i class="fe fe-link"></i> Webhook URL <span class="badge bg-secondary ms-1 fw-normal">Wajib Disalin</span></label>
                        <div class="input-group input-group-lg">
                            <input type="text" id="webhookUrl" class="form-control bg-white border-info text-dark" value="{{ url('/api/webhook/whatsapp') }}" readonly>
                            <button class="btn btn-info text-white fw-bold px-4" type="button" onclick="navigator.clipboard.writeText(document.getElementById('webhookUrl').value); alert('Tautan Webhook Tersalin ke Papan Klip!');"><i class="fe fe-copy me-1"></i> Copy</button>
                        </div>
                        <small class="text-muted mt-2 d-block">Tempelkan URL ini secara utuh pada pengaturan Webhook *Instance* di aplikasi Whapi Anda.</small>
                    </div>
                </div>
            </div>

            <!-- Gemini AI Settings Card -->
            <div class="card shadow-sm border-0 rounded-4 mb-4">
                <div class="card-header bg-white border-bottom-0 pt-4 pb-0">
                    <h5 class="fw-bold mb-0 text-primary"><i class="fe fe-command me-2"></i> Kecerdasan Buatan (Gemini AI)</h5>
                </div>
                <div class="card-body pt-3 pb-4">
                    <div class="form-group mb-3">
                        <label class="form-label fw-bold opacity-75">Gemini API Key</label>
                        <input type="password" name="gemini_api_key" class="form-control form-control-lg bg-light" value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
                        <small class="text-muted mt-1 d-block">Dapatkan ini dari dasbor *Google AI Studio* untuk mengaktifkan asisten BEST AI.</small>
                    </div>
                    <div class="form-group mb-1">
                        <label class="form-label fw-bold opacity-75">Mesin Otak yang Digunakan</label>
                        <div class="position-relative">
                            <select name="gemini_model" class="form-select form-select-lg bg-light border-0 py-3 cursor-pointer">
                                <option value="gemini-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-pro' ? 'selected' : '' }}>Gemini Pro (Versi Lama & Paling Stabil)</option>
                                <option value="gemini-1.5-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Sangat Cepat & Responsif)</option>
                                <option value="gemini-1.5-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Akurasi Kalimat Maksimal)</option>
                            </select>
                        </div>
                        <small class="text-muted mt-2 d-block">Pilih model algoritma yang ingin dipakai untuk memberikan penalaran *(reasoning)* otomatis kepada klien.</small>
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
