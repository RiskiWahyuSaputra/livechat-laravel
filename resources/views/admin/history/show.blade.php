@extends('layouts.admin_template')

@section('title', 'Detail Arsip Chat')

@section('content')
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="card-header">
                <div class="row align-items-center">
                    <div class="col">
                        <h4 class="card-title">Detail Percakapan Arsip</h4>
                        <p class="text-muted mb-0">Selesai pada: {{ $conversation->deleted_at->timezone('Asia/Jakarta')->translatedFormat('d F Y, H:i') }}</p>
                    </div>
                    <div class="col-auto">
                        <a href="{{ route('admin.history.index') }}" class="btn btn-primary btn-sm">
                            <i class="fe fe-arrow-left"></i> Kembali
                        </a>
                    </div>
                </div>
            </div>
            <div class="card-body">
                <!-- Info Pelanggan & Agen -->
                <div class="row mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="font-weight-bold">Informasi Pelanggan</h6>
                            <p class="mb-1"><strong>Nama:</strong> {{ $conversation->customer->name ?? 'Dihapus' }}</p>
                            <p class="mb-1"><strong>Kontak:</strong> {{ $conversation->customer->contact ?? '-' }}</p>
                            <p class="mb-0"><strong>Instansi:</strong> {{ $conversation->customer->origin ?? '-' }}</p>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded">
                            <h6 class="font-weight-bold">Informasi Sesi</h6>
                            <p class="mb-1"><strong>Agen Penangan:</strong> {{ $conversation->admin->username ?? 'Sistem' }}</p>
                            <p class="mb-1"><strong>Kategori:</strong> <span class="badge bg-info-light">{{ $conversation->problem_category ?? '-' }}</span></p>
                            <p class="mb-0"><strong>Durasi:</strong> {{ $conversation->created_at->diffForHumans($conversation->deleted_at, true) }}</p>
                        </div>
                    </div>
                </div>

                <!-- AI Summary -->
                @if($conversation->summary)
                <div class="mb-4">
                    <div class="rounded-2xl border border-blue-100 bg-blue-50 overflow-hidden">
                        <div class="flex items-center gap-2 px-4 py-2.5">
                            <span class="text-blue-600 text-sm">✨</span>
                            <span class="text-sm font-semibold text-slate-700">AI Conversation Summary</span>
                        </div>
                        <div class="border-t border-blue-100 px-4 py-3 bg-white">
                            <p class="text-sm text-slate-700 leading-relaxed">{{ $conversation->summary }}</p>
                        </div>
                    </div>
                </div>
                @endif

                <!-- Rating -->
                @if($conversation->rating)
                <div class="mb-4">
                    <div class="p-3 bg-light rounded">
                        <h6 class="font-weight-bold mb-3">Rating Pelanggan</h6>
                        @php
                            $ratingEmoji = ['😡','😞','😐','😊','😍'];
                            $ratingLabels = ['Sangat Tidak Puas','Tidak Puas','Cukup Puas','Puas','Sangat Puas'];
                            $ratingIndex = max(0, min(4, $conversation->rating->rating - 1));
                        @endphp
                        <div class="d-flex align-items-center gap-3">
                            <div style="font-size:48px;line-height:1;">{{ $ratingEmoji[$ratingIndex] }}</div>
                            <div>
                                <div class="font-weight-bold">{{ $ratingLabels[$ratingIndex] }}</div>
                                <small class="text-muted">Rating: {{ $conversation->rating->rating }}/5</small>
                            </div>
                        </div>
                        @if($conversation->rating->comment)
                        <div class="mt-3 p-2 bg-white rounded border">
                            <small class="text-muted d-block mb-1"><strong>Komentar:</strong></small>
                            <p class="mb-0">{{ $conversation->rating->comment }}</p>
                        </div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Chat History -->
                <div class="chat-history-container p-3 border rounded bg-white" style="max-height: 600px; overflow-y: auto;">
                    @forelse($conversation->messages as $msg)
                        <div class="d-flex flex-column mb-3 {{ $msg->sender_type === 'admin' ? 'align-items-end' : ($msg->sender_type === 'system' ? 'align-items-center' : 'align-items-start') }}">
                            
                            @if($msg->sender_type === 'system')
                                <div class="bg-red-50 text-red-600 text-[11px] px-3 py-1 rounded-full border border-red-100 mb-2">
                                    {{ $msg->content }}
                                </div>
                            @else
                                <div class="mb-1 d-flex align-items-center">
                                    <small class="text-muted font-weight-bold">
                                        {{ $msg->sender_type === 'admin' ? ($msg->message_type === 'whisper' ? 'NOTE INTERNAL' : ($msg->sender_id == 0 ? 'Bot Assistant' : 'Anda')) : 'Pelanggan' }}
                                    </small>
                                    @if($msg->sender_id == 0 && $msg->sender_type === 'admin')
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 ml-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>
                                    @endif
                                </div>
                                <div class="px-3 py-2 rounded-lg shadow-sm {{ $msg->message_type === 'whisper' ? 'bg-amber-100 text-amber-900 border-dashed border border-amber-300' : ($msg->sender_type === 'admin' ? 'bg-primary text-white' : 'bg-light border') }}" style="max-width: 80%; word-wrap: break-word;">
                                    @if($msg->message_type === 'image')
                                        <img src="{{ $msg->content }}"
                                             alt="Gambar"
                                             class="img-fluid rounded"
                                             style="max-width:280px; max-height:220px; object-fit:cover; cursor:zoom-in; border:1px solid rgba(0,0,0,.08);"
                                             onclick="openLightbox(@js($msg->content)); return false;"
                                             onerror="this.onerror=null;this.replaceWith(document.createTextNode(this.src))">
                                    @elseif($msg->message_type === 'file')
                                        <a href="{{ $msg->content }}" target="_blank"
                                           class="{{ $msg->sender_type === 'admin' ? 'text-white' : 'text-primary' }} d-flex align-items-center gap-1"
                                           style="word-break:break-all;">
                                            <i class="fe fe-file me-1"></i>
                                            {{ basename(parse_url($msg->content, PHP_URL_PATH)) ?: $msg->content }}
                                        </a>
                                    @else
                                        {!! nl2br(e($msg->content)) !!}
                                    @endif
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted" style="font-size: 10px;">{{ $msg->created_at->timezone('Asia/Jakarta')->translatedFormat('H:i') }}</small>
                                </div>
                            @endif
                        </div>
                    @empty
                        <p class="text-center text-muted">Tidak ada pesan dalam percakapan ini.</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
    @include('partials.image-lightbox')
@endpush
