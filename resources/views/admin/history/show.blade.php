<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Best Corporation - Detail Arsip Chat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        ::-webkit-scrollbar { width: 5px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #e2e8f0; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #cbd5e1; }
    </style>
</head>
<body class="bg-[#f8fafc] text-slate-800 font-sans antialiased h-screen flex overflow-hidden">

    @include('admin.partials.sidebar')

    <div class="flex-1 flex flex-col overflow-hidden">
        <!-- Header -->
        <header class="bg-white/90 backdrop-blur-md border-b border-slate-200 px-8 py-4 flex items-center justify-between shrink-0 z-30 shadow-sm">
            <div class="flex items-center gap-4">
                <a href="{{ route('admin.history.index') }}" class="p-2 bg-slate-100 hover:bg-slate-200 text-slate-500 rounded-xl transition-colors">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                </a>
                <div>
                    <h1 class="font-black text-slate-900 text-xl tracking-tighter uppercase">Arsip: {{ $conversation->customer->name ?? 'Pelanggan' }}</h1>
                    <p class="text-[10px] font-bold text-slate-400 uppercase tracking-[0.2em]">Diselesaikan pada {{ $conversation->deleted_at->translatedFormat('d F Y, H:i') }}</p>
                </div>
            </div>

            <div class="flex items-center gap-4">
                @if($conversation->problem_category)
                    <span class="px-4 py-2 rounded-2xl bg-blue-50 text-blue-600 border border-blue-100 text-xs font-black uppercase tracking-widest">{{ $conversation->problem_category }}</span>
                @endif
            </div>
        </header>

        <!-- Main Content (Messages) -->
        <main class="flex-1 overflow-y-auto p-4 md:p-8 bg-slate-50 relative">
            
            <div class="max-w-4xl mx-auto bg-white rounded-[2.5rem] shadow-sm border border-slate-100 overflow-hidden min-h-full flex flex-col">
                <div class="p-6 border-b border-slate-100 bg-slate-50/50 flex flex-wrap gap-4 items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-12 h-12 rounded-2xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-xl shadow-lg shadow-slate-200">
                            {{ strtoupper(substr($conversation->customer->name ?? '?', 0, 1)) }}
                        </div>
                        <div>
                            <p class="font-black text-slate-900 leading-tight">{{ $conversation->customer->name ?? 'Dihapus' }}</p>
                            <p class="text-[10px] font-bold text-slate-500 uppercase tracking-widest">{{ $conversation->customer->contact ?? '-' }} &bull; {{ $conversation->customer->origin ?? '-' }}</p>
                        </div>
                    </div>
                    
                    <div class="text-right">
                        <p class="text-[10px] font-black text-slate-400 uppercase tracking-widest mb-0.5">Penanganan Oleh</p>
                        <p class="text-sm font-bold text-slate-700">{{ $conversation->admin->username ?? 'Sistem' }}</p>
                    </div>
                </div>

                <div class="flex-1 p-6 flex flex-col gap-4 overflow-y-auto bg-slate-50 relative">
                    <div class="absolute inset-0 bg-grid-slate-100/[0.04] bg-[size:20px_20px]"></div>
                    
                    @forelse($conversation->messages as $msg)
                        <div class="flex flex-col w-full z-10 {{ $msg->message_type === 'whisper' ? 'items-center' : ($msg->sender_type === 'admin' ? 'items-end' : 'items-start') }}">
                            
                            @if($msg->sender_type === 'system')
                                <div class="w-full flex justify-center my-2">
                                    <div class="bg-red-50 text-red-600 font-medium text-[11px] px-3 py-1.5 rounded-full border border-red-100 text-center shadow-sm max-w-[85%]">
                                        {!! nl2br(e($msg->content)) !!}
                                    </div>
                                </div>
                            @else
                                <div class="max-w-[85%] flex flex-col relative {{ $msg->message_type === 'whisper' ? 'items-center text-center' : ($msg->sender_type === 'admin' ? 'items-end' : 'items-start') }}">
                                    
                                    @if($msg->message_type === 'whisper')
                                        <span class="text-[10px] font-bold text-amber-600 tracking-wider mb-1 flex items-center justify-center gap-1">
                                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                                            INTERNAL NOTE
                                        </span>
                                    @endif

                                    @if($msg->sender_type === 'user')
                                        <span class="text-[11px] text-slate-400 font-medium mb-1 ml-1 text-left">Pelanggan</span>
                                    @elseif($msg->sender_type === 'admin' && $msg->message_type !== 'whisper')
                                        <span class="text-[11px] text-slate-400 font-medium mb-1 mr-1 text-right">{{ $msg->sender->username ?? 'Admin' }}</span>
                                    @endif

                                    <div class="px-5 py-3 text-[15px] leading-relaxed relative max-w-[450px] mx-auto shadow-sm break-words overflow-hidden {{ $msg->sender_type === 'admin' && $msg->message_type !== 'whisper' ? 'bg-blue-600 text-white rounded-2xl rounded-br-sm border border-blue-700' : ($msg->sender_type === 'user' ? 'bg-white text-slate-800 rounded-2xl rounded-bl-sm border border-slate-200 shadow-sm' : 'bg-amber-100 text-amber-950 border-dashed border-2 border-amber-300 rounded-2xl w-fit') }}">
                                        {!! nl2br(e($msg->content)) !!}
                                    </div>
                                    
                                    <span class="text-[10px] text-slate-400 mt-1 mx-1">{{ $msg->created_at->format('H:i') }}</span>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="py-12 text-center text-slate-400">
                            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                            <p class="font-bold text-sm">Tidak ada pesan dalam arsip ini.</p>
                        </div>
                    @endforelse
                </div>
                
                <div class="p-4 bg-slate-100 border-t border-slate-200 text-center shrink-0">
                    <p class="text-xs font-bold text-slate-500 flex items-center justify-center gap-2">
                        <svg class="w-4 h-4 text-slate-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"></path></svg>
                        Sesi obrolan ini telah ditutup secara permanen dan bersifat Read-Only.
                    </p>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
