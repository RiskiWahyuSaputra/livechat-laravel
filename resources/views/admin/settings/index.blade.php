@extends('layouts.admin_template')

@section('title', 'Pengaturan Integrasi')

@section('content')
<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h4 class="card-title">Pengaturan Sistem & Integrasi</h4>
            </div>
            <div class="card-body">
                <form action="{{ route('admin.settings.update') }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <ul class="nav nav-tabs nav-tabs-solid nav-justified">
                        <li class="nav-item"><a class="nav-link active" href="#tab-whapi" data-bs-toggle="tab"><i class="fe fe-message-circle"></i> Whapi.cloud</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab-gemini" data-bs-toggle="tab"><i class="fe fe-cpu"></i> Gemini AI</a></li>
                        <li class="nav-item"><a class="nav-link" href="#tab-general" data-bs-toggle="tab"><i class="fe fe-settings"></i> Umum</a></li>
                    </ul>
                    
                    <div class="tab-content pt-4">
                        <!-- Whapi Settings -->
                        <div class="tab-pane show active" id="tab-whapi">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Whapi API Token</label>
                                        <input type="password" name="whapi_token" class="form-control" value="{{ $settings['whapi_token'] ?? env('WHAPI_TOKEN') }}">
                                        <small class="text-muted">Dapatkan token dari dashboard Whapi.cloud.</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Whapi Admin Number</label>
                                        <input type="text" name="whapi_admin_number" class="form-control" value="{{ $settings['whapi_admin_number'] ?? env('WHAPI_ADMIN_NUMBER') }}">
                                        <small class="text-muted">Nomor WhatsApp Admin (format internasional tanpa '+', misal: 628xxx).</small>
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Webhook URL (Read-only)</label>
                                        <input type="text" class="form-control bg-light" value="{{ url('/api/webhook/whatsapp') }}" readonly>
                                        <small class="text-info">Copy URL ini ke dashboard Whapi Webhook.</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Gemini AI Settings -->
                        <div class="tab-pane" id="tab-gemini">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Gemini API Key</label>
                                        <input type="password" name="gemini_api_key" class="form-control" value="{{ $settings['gemini_api_key'] ?? env('GEMINI_API_KEY') }}">
                                    </div>
                                    <div class="form-group mb-3">
                                        <label class="form-label">Gemini Model</label>
                                        <select name="gemini_model" class="form-select">
                                            <option value="gemini-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-pro' ? 'selected' : '' }}>Gemini Pro (Paling Stabil)</option>
                                            <option value="gemini-1.5-flash" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-flash' ? 'selected' : '' }}>Gemini 1.5 Flash (Cepat)</option>
                                            <option value="gemini-1.5-pro" {{ ($settings['gemini_model'] ?? '') == 'gemini-1.5-pro' ? 'selected' : '' }}>Gemini 1.5 Pro (Pintar)</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- General Settings -->
                        <div class="tab-pane" id="tab-general">
                            <div class="row">
                                <div class="col-md-8 mx-auto">
                                    <div class="form-group mb-3">
                                        <label class="form-label">Nama Aplikasi</label>
                                        <input type="text" name="app_name" class="form-control" value="{{ $settings['app_name'] ?? config('app.name') }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="text-center mt-4 border-top pt-4">
                        <button type="submit" class="btn btn-primary btn-lg"><i class="fe fe-save"></i> Simpan Semua Perubahan</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
