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
                        <p class="text-muted mb-0">Selesai pada: {{ $conversation->deleted_at->format('d M Y H:i') }} WIB</p>
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

                <!-- Chat History -->
                <div class="chat-history-container p-3 border rounded bg-white" style="max-height: 600px; overflow-y: auto;">
                    @forelse($conversation->messages as $msg)
                        <div class="d-flex flex-column mb-3 {{ $msg->sender_type === 'admin' ? 'align-items-end' : ($msg->sender_type === 'system' ? 'align-items-center' : 'align-items-start') }}">
                            
                            @if($msg->sender_type === 'system')
                                <div class="bg-red-50 text-red-600 text-[11px] px-3 py-1 rounded-full border border-red-100 mb-2">
                                    {{ $msg->content }}
                                </div>
                            @else
                                <div class="mb-1">
                                    <small class="text-muted font-weight-bold">
                                        {{ $msg->sender_type === 'admin' ? ($msg->message_type === 'whisper' ? 'NOTE INTERNAL' : 'Agen') : 'Pelanggan' }}
                                    </small>
                                </div>
                                <div class="px-3 py-2 rounded-lg shadow-sm {{ $msg->message_type === 'whisper' ? 'bg-amber-100 text-amber-900 border-dashed border border-amber-300' : ($msg->sender_type === 'admin' ? 'bg-primary text-white' : 'bg-light border') }}" style="max-width: 80%; word-wrap: break-word;">
                                    {!! nl2br(e($msg->content)) !!}
                                </div>
                                <div class="mt-1">
                                    <small class="text-muted" style="font-size: 10px;">{{ $msg->created_at->format('H:i') }}</small>
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
