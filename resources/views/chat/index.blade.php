<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link rel="icon" type="image/x-icon" href="{{ asset('images/best-logo-1.png') }}">
    <script>
        window.broadcastingAuth = "{{ url('/broadcasting/auth') }}";
    </script>
    <title>Dashboard - Live Chat</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.14.8/dist/cdn.min.js"></script>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.7.2/css/all.min.css">
    <style>
        /* Sembunyikan elemen sebelum Alpine load penuh untuk mencegah loncatan layout */
        [x-cloak] { display: none !important; }
        /* Kustom scrollbar untuk gaya minimalist */
        ::-webkit-scrollbar { width: 6px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        /* ── Message Options (WhatsApp Style) ── */
        .bubble-wrapper {
            position: relative;
            display: inline-block;
            max-width: 100%;
        }
        .msg-options-btn {
            position: absolute;
            top: 50%;
            right: 8px;
            transform: translateY(-50%);
            width: 24px;
            height: 24px;
            background: transparent;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
            opacity: 0;
            transition: opacity 0.2s, color 0.2s;
            z-index: 10;
            color: #fff;
            filter: drop-shadow(0 1px 1px rgba(0,0,0,0.2));
        }
        .bubble-wrapper:hover .msg-options-btn {
            opacity: 1;
        }
        /* Memberikan ruang agar teks tidak bertubrukan dengan tombol dropdown */
        .bubble-wrapper .bubble-content {
            padding-right: 34px !important;
        }
        .msg-options-btn:hover {
            color: #cbd5e1;
        }

        .msg-context-menu {
            position: fixed;
            z-index: 9999;
            background: white;
            border-radius: 12px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.15);
            width: 150px;
            overflow: hidden;
            border: 1px solid #f1f5f9;
            animation: menuFadeIn 0.15s ease-out;
        }
        @keyframes menuFadeIn {
            from { opacity: 0; transform: scale(0.95); }
            to { opacity: 1; transform: scale(1); }
        }
        .menu-item {
            padding: 10px 14px;
            display: flex;
            align-items: center;
            gap: 10px;
            font-size: 13px;
            font-weight: 600;
            color: #334155;
            cursor: pointer;
            transition: background 0.1s;
        }
        .menu-item:hover {
            background: #f8fafc;
        }
        .menu-item.destructive {
            color: #ef4444;
        }
        .menu-item i {
            font-size: 14px;
            opacity: 0.7;
        }
    </style>
</head>
    <body class="bg-slate-50 text-slate-800 font-sans antialiased h-screen flex flex-col overflow-hidden" 
      @click="closeMenu()"
      x-data="chatApp({{ $conversation->id }}, {{ Auth::id() ?: 'null' }}, '{{ $conversation->status }}', {{ Js::from($messages) }}, '{{ $conversation->bot_phase }}', {{ Js::from($botCategories) }}, {{ Js::from($conversation->bot_phase === 'awaiting_submenu' ? \App\Models\BotMenu::whereNotNull('parent_id')->orderBy('order_index')->get()->map(fn($m) => ['id' => $m->id, 'label' => $m->label, 'parent_id' => $m->parent_id]) : []) }}, {{ Js::from($feedbackPending ?? false) }})">

    <!-- CONTEXT MENU / DROPDOWN -->
    <div x-show="menu.show" 
         x-cloak
         class="msg-context-menu" 
         :style="`top: ${menu.y}px; left: ${menu.x}px;`"
         @click.outside="closeMenu()">
        <div class="menu-item" @click="editMessage(menu.msgId)">
            <i class="fas fa-edit"></i>
            Edit Pesan
        </div>
        <div class="menu-item destructive" @click="deleteMessage(menu.msgId)">
            <i class="fas fa-trash-alt"></i>
            Hapus Pesan
        </div>
    </div>

    <!-- Header Navbar Minimalist -->
    <header class="bg-white border-b border-slate-200 px-3 md:px-4 py-2.5 md:py-3 flex items-center justify-between shrink-0 shadow-sm relative z-20">
        <!-- Red Accent Bar -->
        <div class="absolute top-0 left-0 right-0 h-1 bg-red-600"></div>

        <div class="flex items-center gap-2 md:gap-4 overflow-hidden mt-1">
            <div class="w-9 h-9 md:w-12 md:h-12 rounded-xl md:rounded-2xl bg-[#0a1d37] flex items-center justify-center font-black text-white text-sm md:text-xl shrink-0 shadow-lg shadow-slate-200">
                <span>{{ strtoupper(substr(Auth::user()->name, 0, 1)) }}</span>
            </div>
            <div class="overflow-hidden">
                <h1 class="font-black text-[#0a1d37] text-sm md:text-xl leading-tight truncate">Layanan Pelanggan</h1>
                <!-- Indikator Status -->
                <div class="flex items-center gap-1.5 text-[9px] md:text-xs font-bold text-slate-400 uppercase tracking-wider md:tracking-widest mt-0.5">
                    <span class="flex items-center gap-1 shrink-0"
                          :class="{
                              'text-red-600': status === 'pending' || status === 'queued',
                              'text-emerald-600': status === 'active',
                              'text-slate-400': status === 'closed'
                          }">
                        <div class="w-1.5 h-1.5 md:w-2 md:h-2 rounded-full"
                             :class="{
                                 'bg-red-600 animate-pulse': status === 'pending' || status === 'queued',
                                 'bg-emerald-600': status === 'active',
                                 'bg-slate-400': status === 'closed'
                             }"></div>
                        <span x-text="statusText"></span>
                    </span>
                </div>
            </div>
        </div>
        
        <form id="logout-form" method="POST" action="{{ route('chat.logout') }}" class="shrink-0">
            @csrf
            <button type="submit" class="bg-red-600 hover:bg-red-700 text-white shadow-lg shadow-red-200 text-[10px] md:text-sm font-black px-3 md:px-8 py-2 md:py-3 rounded-xl md:rounded-2xl transition-all hover:scale-105 active:scale-95 whitespace-nowrap">
                <span class="hidden xs:inline">Akhiri Percakapan</span>
                <span class="xs:hidden">Akhiri</span>
            </button>
        </form>
    </header>

    <!-- Area Konten Utama -->
    <main class="flex-1 w-full max-w-3xl mx-auto flex flex-col bg-slate-50 border-x border-slate-200 shadow-sm relative overflow-hidden">
        
        <!-- Riwayat Pesan -->
        <div id="messages-container" class="flex-1 overflow-y-auto p-3 md:p-4 space-y-4">
            
            <!-- Pesan Pembuka Default -->
            <div class="flex justify-center mb-6">
                <span class="text-slate-500 font-medium text-[10px] md:text-xs bg-white px-3 py-1 rounded-full border border-slate-100 shadow-sm">
                    Percakapan Dimulai
                </span>
            </div>

            <template x-for="(msg, index) in messages" :key="msg.id || msg.temp_id">
                <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                    
                    <!-- Pesan Sistem -->
                    <template x-if="msg.sender_type === 'system'">
                        <div class="w-full flex justify-center my-2 px-2">
                            <div class="bg-red-50 text-red-600 font-medium text-[10px] md:text-[11px] px-3 py-1.5 rounded-full border border-red-100 text-center shadow-sm max-w-[95%] md:max-w-[85%]">
                                <span x-html="formatMessage(msg.content)"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Bubble Chat Normal -->
                    <template x-if="msg.sender_type !== 'system'">
                        <div class="max-w-[90%] md:max-w-[85%] flex flex-col" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                            <!-- Nama Pengirim -->
                            <span x-show="msg.sender_type !== 'user'" class="text-[9px] md:text-[11px] text-slate-400 font-medium mb-1 ml-1" 
                                  x-text="msg.sender_id == 0 ? 'Live Support' : 'Live Support Agent'"></span>
                            <span x-show="msg.sender_type === 'user'" class="text-[9px] md:text-[11px] text-slate-400 font-medium mb-1 mr-1">Anda</span>
                            
                            <div class="bubble-wrapper">
                                <!-- Option Button -->
                                <template x-if="msg.sender_type === 'user'">
                                    <div class="msg-options-btn" @click.stop="openMenu(msg.id, $event)">
                                        <i class="fas fa-chevron-down"></i>
                                    </div>
                                </template>

                                <div class="bubble-content px-3.5 py-2 md:px-5 md:py-3 rounded-2xl text-[13px] md:text-[15px] leading-relaxed relative break-words overflow-hidden shadow-sm"
                                     :class="msg.sender_type === 'admin' 
                                        ? 'bg-blue-600 text-white rounded-bl-sm border border-blue-700' 
                                        : 'bg-white text-slate-800 rounded-br-sm border border-slate-200'"
                                     @contextmenu.prevent="handleContextMenu($event, msg.id)">
                                
                                <!-- Pesan Teks -->
                                <template x-if="!msg.message_type || msg.message_type === 'text'">
                                    <div>
                                        <span x-html="formatMessage(msg.content)"></span>
                                    </div>
                                </template>

                                <!-- Pesan Gambar -->
                                <template x-if="msg.message_type === 'image'">
                                    <div class="space-y-2">
                                        <template x-if="!String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                            <img :src="msg.content" 
                                                 class="rounded-lg max-w-full h-auto cursor-zoom-in hover:opacity-90 transition-opacity min-h-[50px] bg-slate-100" 
                                                 @click="openLightbox(msg.content)"
                                                 x-on:error="$el.src='https://placehold.co/200x150?text=Gambar+Gagal+Dimuat'">
                                        </template>
                                        <template x-if="String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                            <div class="rounded-lg border border-amber-300 bg-amber-50 text-amber-800 px-3 py-2 text-xs md:text-sm">
                                                Media gambar dari WhatsApp diterima, tetapi gateway belum mengirim URL file gambar ke web.
                                            </div>
                                        </template>
                                    </div>
                                </template>

                                <!-- Pesan File -->
                                <template x-if="msg.message_type === 'file'">
                                    <div class="w-full">
                                        <template x-if="String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                            <div class="rounded-lg border border-amber-300 bg-amber-50 text-amber-800 px-3 py-2 text-xs md:text-sm">
                                                Media file dari WhatsApp diterima, tetapi gateway belum mengirim URL file ke web.
                                            </div>
                                        </template>
                                        <template x-if="!String(msg.content || '').startsWith('whatsapp-media-placeholder:')">
                                        <div class="flex items-center gap-3 min-w-0">
                                            <div class="w-10 h-10 rounded-lg bg-slate-100 flex items-center justify-center text-slate-500 shrink-0">
                                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <div class="flex-1 min-w-0 overflow-hidden text-left">
                                                <p class="text-sm font-bold truncate mb-0.5" x-text="msg.content.split('/').pop()"></p>
                                                <a :href="msg.content" target="_blank" class="inline-flex items-center gap-1.5 text-[11px] font-bold uppercase tracking-wider hover:underline" :class="msg.sender_type === 'admin' ? 'text-blue-100' : 'text-blue-600'">
                                                    <span>Unduh Dokumen</span>
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </a>
                                            </div>
                                        </div>
                                        </template>
                                    </div>
                                </template>
                                </div>
                            </div>
                            
                            <!-- Timestamp -->
                            <span class="text-[9px] md:text-[10px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>

                            <!-- Bot Categories Inline (Muncul di pesan bot terakhir saat fase awaiting_category) -->
                            <template x-if="msg.sender_id == 0 && botPhase === 'awaiting_category' && isLastBotMessage(index, messages)">
                                <div class="mt-3 flex flex-wrap gap-2 w-full">
                                    <template x-for="cat in botCategories" :key="cat">
                                        <button @click="selectCategory(cat)" 
                                                class="px-3 py-2 bg-white hover:bg-red-50 text-red-600 border border-red-200 hover:border-red-300 rounded-xl text-[11px] font-bold transition-all shadow-sm flex-1 min-w-[140px] text-center">
                                            <span x-text="cat"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>

                            <!-- Bot Transfer Options (Muncul di pesan bot terakhir saat fase offer_agent_transfer) -->
                            <template x-if="msg.sender_id == 0 && botPhase === 'offer_agent_transfer' && isLastBotMessage(index, messages)">
                                <div class="mt-3 flex flex-col sm:flex-row gap-2 w-full">
                                    <button @click="selectOption('Tanya BEST AI')" 
                                            class="px-3 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-xl text-[11px] font-bold transition-all shadow-sm flex-1 text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-comment-dots"></i> Tanya BEST AI
                                    </button>
                                    <button @click="selectOption('Hubungi Agent')" 
                                            class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white border border-red-700 rounded-xl text-[11px] font-bold transition-all shadow-md flex-1 text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-headset"></i> Hubungi Agent
                                    </button>
                                </div>
                            </template>

                            <!-- Bot Initial Chat Options (Muncul di pesan bot terakhir saat fase chatting_with_ai) -->
                            <template x-if="msg.sender_id == 0 && botPhase === 'chatting_with_ai' && isLastBotMessage(index, messages)">
                                <div class="mt-3 flex flex-col sm:flex-row gap-2 w-full">
                                    <button @click="selectOption('Tanya BEST AI')" 
                                            class="px-3 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-xl text-[11px] font-bold transition-all shadow-sm flex-1 text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-comment-dots"></i> Tanya BEST AI
                                    </button>
                                    <button @click="selectOption('Hubungi Agent')" 
                                            class="px-3 py-2 bg-red-600 hover:bg-red-700 text-white border border-red-700 rounded-xl text-[11px] font-bold transition-all shadow-md flex-1 text-center flex items-center justify-center gap-2">
                                        <i class="fas fa-headset"></i> Hubungi Agent
                                    </button>
                                </div>
                            </template>

                            <!-- Bot Submenu Options (Muncul di pesan bot terakhir saat fase awaiting_submenu) -->
                            <template x-if="msg.sender_id == 0 && botPhase === 'awaiting_submenu' && isLastBotMessage(index, messages)">
                                <div class="mt-3 flex flex-wrap gap-2 w-full">
                                    <template x-for="sub in botSubmenus.filter(s => {
                                        // Filter only submenus that belong to the menu just picked by user
                                        let lastUserMsg = [...messages].reverse().find(m => m.sender_type === 'user');
                                        return lastUserMsg && String(lastUserMsg.content).toLowerCase().trim() === 'hubungi cs' ? s.parent_id == 13 : true;
                                    })" :key="sub.id">
                                        <button @click="selectOption(sub.label)" 
                                                class="px-3 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-xl text-[11px] font-bold transition-all shadow-sm flex-1 min-w-[140px] text-center">
                                            <span x-text="sub.label"></span>
                                        </button>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            <section x-show="shouldRenderInlineConversationSummary()" x-cloak class="mt-6">
                <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
                    <div class="flex items-start gap-3 bg-slate-50/80 px-4 py-4 md:px-5">
                        <button type="button"
                                @click="summary.expanded = !summary.expanded"
                                class="flex-1 text-left">
                            <div class="flex items-start justify-between gap-3">
                                <div class="flex items-start gap-3">
                                    <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                        <i class="fas fa-wand-magic-sparkles text-sm"></i>
                                    </div>
                                    <div>
                                        <div class="flex items-center gap-2">
                                            <h3 class="text-sm font-black text-slate-800 md:text-[15px]">AI Conversation Summary</h3>
                                            <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                                Privat
                                            </span>
                                        </div>
                                        <p class="mt-1 text-[11px] text-slate-500 md:text-xs">
                                            Hanya kamu yang bisa melihat ringkasan percakapan ini.
                                        </p>
                                    </div>
                                </div>
                                <i class="fas text-slate-400 transition-transform"
                                   :class="summary.expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                            </div>
                        </button>

                        <button type="button"
                                @click="fetchConversationSummary(true)"
                                :disabled="summary.loading"
                                class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                                title="Refresh summary">
                            <i class="fas fa-rotate-right" :class="summary.loading ? 'animate-spin' : ''"></i>
                        </button>
                    </div>

                    <div x-show="summary.expanded" x-cloak class="border-t border-slate-100 px-4 py-4 md:px-5">
                        <div x-show="summary.loading" class="space-y-3">
                            <div class="h-3 w-40 animate-pulse rounded-full bg-slate-200"></div>
                            <div class="h-3 w-full animate-pulse rounded-full bg-slate-100"></div>
                            <div class="h-3 w-11/12 animate-pulse rounded-full bg-slate-100"></div>
                            <div class="h-3 w-3/4 animate-pulse rounded-full bg-slate-100"></div>
                        </div>

                        <div x-show="!summary.loading && summary.available" x-cloak class="space-y-4">
                            <div class="flex flex-wrap items-center gap-2">
                                <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                      :class="summarySentimentClass()"
                                      x-text="summary.sentiment"></span>
                                <span x-show="summary.updatedAt"
                                      class="text-[11px] text-slate-400"
                                      x-text="`Diperbarui ${summary.updatedAt}`"></span>
                            </div>

                            <div class="text-[13px] leading-6 text-slate-700 md:text-sm">
                                <p class="font-semibold text-slate-800">Summary of conversation</p>
                                <p class="mt-2" x-text="summary.text"></p>
                            </div>
                        </div>

                        <div x-show="!summary.loading && !summary.available && summary.info" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-[12px] leading-6 text-slate-600 md:text-sm">
                            <span x-text="summary.info"></span>
                        </div>

                        <div x-show="!summary.loading && summary.error" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] leading-6 text-red-600 md:text-sm">
                            <span x-text="summary.error"></span>
                        </div>

                        <p class="mt-4 text-[11px] text-slate-400">
                            Ringkasan AI bisa kurang akurat dan tetap perlu dilihat bersama konteks percakapan.
                        </p>
                    </div>
                </div>
            </section>
            
            <!-- Elemen ini membantu scroll mentok bawah -->
            <div id="scroll-anchor" class="h-1"></div>
        </div>

        <!-- Sticky Status Footer (Typing Indicator & Closed Notice) -->
        <div class="shrink-0 bg-white border-t border-slate-100">
            <!-- Typing Indicator Ringan -->
            <div x-show="isTyping" x-cloak class="px-4 py-1.5 md:py-2 flex items-center gap-2 bg-slate-50/50">
                <span class="text-[10px] md:text-xs italic text-slate-500 font-medium" x-text="typingMessage"></span>
                <div class="flex gap-1">
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-1 h-1 md:w-1.5 md:h-1.5 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>

            <!-- Closed chat block -->
            <div x-show="status === 'closed' && !feedbackPending" x-cloak class="bg-slate-100 text-slate-600 py-3 md:py-4 text-center text-xs md:text-sm font-medium">
                Sesi obrolan ini telah ditutup.
            </div>

            <div x-show="status === 'closed' && feedbackPending" x-cloak class="border-t border-amber-200 bg-amber-50 p-4 md:p-5">
                <div class="max-w-xl mx-auto">
                    <section x-show="shouldRenderFeedbackConversationSummary()" x-cloak class="mb-4 overflow-hidden rounded-3xl border border-amber-200 bg-white shadow-sm">
                        <div class="flex items-start gap-3 bg-amber-50/60 px-4 py-4">
                            <button type="button"
                                    @click="summary.expanded = !summary.expanded"
                                    class="flex-1 text-left">
                                <div class="flex items-start justify-between gap-3">
                                    <div class="flex items-start gap-3">
                                        <div class="mt-0.5 flex h-10 w-10 items-center justify-center rounded-2xl bg-blue-50 text-blue-600">
                                            <i class="fas fa-wand-magic-sparkles text-sm"></i>
                                        </div>
                                        <div>
                                            <div class="flex items-center gap-2">
                                                <h3 class="text-sm font-black text-slate-800 md:text-[15px]">AI Conversation Summary</h3>
                                                <span class="rounded-full border border-slate-200 bg-white px-2 py-0.5 text-[10px] font-bold uppercase tracking-[0.18em] text-slate-500">
                                                    Privat
                                                </span>
                                            </div>
                                            <p class="mt-1 text-[11px] text-slate-500 md:text-xs">
                                                Ringkasan percakapan ini tetap hanya terlihat oleh kamu.
                                            </p>
                                        </div>
                                    </div>
                                    <i class="fas text-slate-400 transition-transform"
                                       :class="summary.expanded ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                                </div>
                            </button>

                            <button type="button"
                                    @click="fetchConversationSummary(true)"
                                    :disabled="summary.loading"
                                    class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl border border-slate-200 bg-white text-slate-500 transition hover:border-blue-200 hover:text-blue-600 disabled:cursor-not-allowed disabled:opacity-50"
                                    title="Refresh summary">
                                <i class="fas fa-rotate-right" :class="summary.loading ? 'animate-spin' : ''"></i>
                            </button>
                        </div>

                        <div x-show="summary.expanded" x-cloak class="border-t border-slate-100 px-4 py-4">
                            <div x-show="summary.loading" class="space-y-3">
                                <div class="h-3 w-40 animate-pulse rounded-full bg-slate-200"></div>
                                <div class="h-3 w-full animate-pulse rounded-full bg-slate-100"></div>
                                <div class="h-3 w-11/12 animate-pulse rounded-full bg-slate-100"></div>
                            </div>

                            <div x-show="!summary.loading && summary.available" x-cloak class="space-y-4">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="inline-flex items-center rounded-full px-2.5 py-1 text-[10px] font-black uppercase tracking-[0.18em]"
                                          :class="summarySentimentClass()"
                                          x-text="summary.sentiment"></span>
                                    <span x-show="summary.updatedAt"
                                          class="text-[11px] text-slate-400"
                                          x-text="`Diperbarui ${summary.updatedAt}`"></span>
                                </div>

                                <div class="text-[13px] leading-6 text-slate-700 md:text-sm">
                                    <p class="font-semibold text-slate-800">Summary of conversation</p>
                                    <p class="mt-2" x-text="summary.text"></p>
                                </div>
                            </div>

                            <div x-show="!summary.loading && !summary.available && summary.info" x-cloak class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-[12px] leading-6 text-slate-600 md:text-sm">
                                <span x-text="summary.info"></span>
                            </div>

                            <div x-show="!summary.loading && summary.error" x-cloak class="rounded-2xl border border-red-200 bg-red-50 px-4 py-3 text-[12px] leading-6 text-red-600 md:text-sm">
                                <span x-text="summary.error"></span>
                            </div>
                        </div>
                    </section>

                    <div class="text-center mb-4">
                        <h3 class="text-sm md:text-base font-black text-slate-800">Bagaimana pengalaman Anda dengan agen kami?</h3>
                        <p class="text-[11px] md:text-xs text-slate-500 mt-1">Pilih rating bintang 1 sampai 5 untuk membantu evaluasi performa agen.</p>
                    </div>

                    <div class="flex items-center justify-center gap-2 md:gap-3 mb-4">
                        <template x-for="star in [1, 2, 3, 4, 5]" :key="star">
                            <button type="button"
                                    @click="selectedRating = star"
                                    @mouseenter="hoverRating = star"
                                    @mouseleave="hoverRating = 0"
                                    class="transition-transform hover:scale-110 active:scale-95">
                                <i class="fas fa-star text-2xl md:text-3xl"
                                   :class="(hoverRating || selectedRating) >= star ? 'text-amber-400' : 'text-slate-300'"></i>
                            </button>
                        </template>
                    </div>

                    <textarea x-model="feedbackComment"
                              rows="3"
                              maxlength="1000"
                              placeholder="Opsional: tulis kesan atau saran singkat..."
                              class="w-full rounded-2xl border border-amber-200 bg-white px-4 py-3 text-sm text-slate-700 focus:border-amber-400 focus:ring-2 focus:ring-amber-200 resize-none"></textarea>

                    <div class="mt-4 flex items-center justify-between gap-3">
                        <button type="button"
                                @click="skipFeedback()"
                                :disabled="isSubmittingFeedback"
                                class="text-xs md:text-sm font-bold text-slate-500 hover:text-slate-700 transition-colors disabled:opacity-50">
                            Lewati
                        </button>
                        <button type="button"
                                @click="submitFeedback()"
                                :disabled="!selectedRating || isSubmittingFeedback"
                                class="rounded-2xl bg-amber-500 px-4 py-2.5 text-xs md:text-sm font-black text-white shadow-lg shadow-amber-200 transition-all hover:bg-amber-600 disabled:cursor-not-allowed disabled:opacity-50">
                            <span x-text="isSubmittingFeedback ? 'Mengirim...' : 'Kirim Feedback'"></span>
                        </button>
                    </div>
                </div>
            </div>

            <!-- Editing Indicator -->
            <div x-show="editingMsgId !== null" 
                 class="px-4 py-2 bg-blue-50 border-t border-blue-100 flex items-center justify-between" 
                 x-cloak>
                <div class="flex items-center gap-2 text-blue-600 font-bold text-[10px] md:text-xs">
                    <i class="fas fa-edit"></i>
                    Mengedit pesan...
                </div>
                <button type="button" @click="cancelEdit()" class="text-[10px] md:text-xs text-slate-400 hover:text-slate-600 font-black uppercase">BATAL</button>
            </div>

            <!-- Form Input Bawah -->
            <form @submit.prevent="sendMessage" 
                  method="POST" action="{{ route('chat.send') }}"
                  x-show="status !== 'closed'" class="p-2 md:p-3 bg-white flex items-end gap-2 relative">
                <button type="button" 
                        @click="$refs.fileInput.click()"
                        class="shrink-0 w-10 h-10 md:w-11 md:h-11 rounded-xl bg-slate-100 text-slate-500 flex items-center justify-center hover:bg-slate-200 focus:outline-none transition-all"
                        title="Unggah Gambar atau File">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
                <input type="file" x-ref="fileInput" class="hidden" @change="uploadFile">

                <textarea x-ref="messageInput"
                          x-model="newMessage" 
                          @input="sendTypingEvent(); resizeComposer()"
                          @keydown="handleComposerKeydown($event)"
                          placeholder="Ketik balasan Anda..." 
                          class="flex-1 min-h-[40px] md:min-h-[44px] bg-slate-100 border-transparent focus:bg-white focus:border-blue-500 focus:ring-2 focus:ring-blue-200 rounded-xl px-3.5 py-2 md:py-2.5 text-[13px] md:text-sm transition-colors resize-none overflow-hidden"
                          rows="1"></textarea>
                          
                <button type="submit" 
                        :disabled="!newMessage.trim() || isSending"
                        class="shrink-0 w-10 h-10 md:w-11 md:h-11 rounded-xl bg-blue-600 text-white flex items-center justify-center hover:bg-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2 disabled:opacity-50 disabled:cursor-not-allowed transition-all shadow-md shadow-blue-600/20">
                    <svg class="w-5 h-5 ml-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </main>

    <!-- Logic Alpine JS Tetap Sama, Tidak Diubah -->
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('chatApp', (conversationId, userId, initialStatus, initialMessages, initialBotPhase, botCategories, initialSubmenus, initialFeedbackPending) => ({
                conversationId: conversationId,
                userId: userId,
                status: initialStatus,
                messages: initialMessages,
                botPhase: initialBotPhase || 'off',
                botSubmenus: initialSubmenus || [],
                feedbackPending: !!initialFeedbackPending,
                selectedRating: 0,
                hoverRating: 0,
                feedbackComment: '',
                isSubmittingFeedback: false,
                newMessage: '',
                isSending: false,
                isTyping: false,
                typingMessage: 'Agen sedang merespon',
                typingTimeout: null,
                summaryRefreshTimer: null,
                summaryRequestId: 0,
                botCategories: botCategories,
                editingMsgId: null,
                summary: {
                    loading: false,
                    available: false,
                    expanded: true,
                    text: '',
                    sentiment: 'Neutral',
                    info: 'Ringkasan AI akan muncul setelah percakapan punya konteks yang cukup.',
                    error: '',
                    updatedAt: null,
                    lastFingerprint: null,
                    historyHash: null
                },
                menu: {
                    show: false,
                    msgId: null,
                    x: 0,
                    y: 0
                },

                init() {
                    this.scrollToBottom();
                    this.listenForEvents();
                    this.$nextTick(() => this.resizeComposer());
                    this.queueSummaryRefresh(250);

                    // Polling fallback: sync status dari server setiap 20 detik
                    setInterval(async () => {
                        if (this.status === 'closed') return;
                        try {
                            const res = await fetch('{{ route("chat.init") }}', {
                                method: 'GET',
                                headers: { 'Accept': 'application/json' }
                            });
                            const data = await res.json();
                            if (data.conversation && data.conversation.status !== this.status) {
                                console.log('🔄 Polling sync: status berubah dari', this.status, '→', data.conversation.status);
                                this.status = data.conversation.status;
                                if (data.conversation.bot_phase) this.botPhase = data.conversation.bot_phase;
                                if (data.bot_submenus) this.botSubmenus = data.bot_submenus;
                            }
                            if (typeof data.feedback_pending !== 'undefined') {
                                this.feedbackPending = !!data.feedback_pending;
                            }
                        } catch (e) { /* silent */ }
                    }, 20000);
                },

                handleComposerKeydown(event) {
                    if (event.key === 'Enter' && !event.shiftKey) {
                        event.preventDefault();
                        this.sendMessage();
                    } else if (event.key === 'Enter' && event.shiftKey) {
                        this.$nextTick(() => this.resizeComposer());
                    }
                },

                resizeComposer() {
                    const textarea = this.$refs.messageInput;
                    if (!textarea) return;

                    textarea.style.height = 'auto';
                    const newHeight = Math.min(textarea.scrollHeight, 150);
                    textarea.style.height = `${newHeight}px`;
                    
                    // Toggle overflow based on height
                    textarea.style.overflowY = textarea.scrollHeight > 150 ? 'auto' : 'hidden';
                },

                get statusText() {
                    if (this.status === 'pending') return 'Menunggu Agent';
                    if (this.status === 'queued') return 'Sedang Dalam Antrian';
                    if (this.status === 'active') return 'Terhubung dengan Agent';
                    return 'Sesi Ditutup';
                },

                eligibleSummaryMessageCount() {
                    return this.messages.filter((msg) => {
                        if (msg.sender_type === 'system') return false;
                        const type = msg.message_type || 'text';
                        return ['text', 'image', 'file'].includes(type);
                    }).length;
                },

                shouldRenderConversationSummary() {
                    return this.status === 'closed' && (
                        this.summary.loading
                        || this.summary.available
                        || this.summary.error !== ''
                        || this.eligibleSummaryMessageCount() >= 2
                    );
                },

                shouldRenderInlineConversationSummary() {
                    return false;
                },

                shouldRenderFeedbackConversationSummary() {
                    return this.status === 'closed' && this.feedbackPending && this.shouldRenderConversationSummary();
                },

                summaryFingerprint() {
                    return this.messages
                        .filter((msg) => msg.sender_type !== 'system')
                        .map((msg) => `${msg.id || msg.temp_id || 'temp'}:${msg.sender_type}:${msg.message_type || 'text'}:${String(msg.content || '').slice(0, 200)}`)
                        .join('|');
                },

                summarySentimentClass() {
                    if (this.summary.sentiment === 'Positive') {
                        return 'bg-emerald-50 text-emerald-700 border border-emerald-200';
                    }
                    if (this.summary.sentiment === 'Negative') {
                        return 'bg-rose-50 text-rose-700 border border-rose-200';
                    }

                    return 'bg-amber-50 text-amber-700 border border-amber-200';
                },

                queueSummaryRefresh(delay = 900) {
                    clearTimeout(this.summaryRefreshTimer);

                    if (this.status !== 'closed') {
                        return;
                    }

                    if (this.eligibleSummaryMessageCount() < 2) {
                        this.summary.available = false;
                        this.summary.text = '';
                        this.summary.sentiment = 'Neutral';
                        this.summary.error = '';
                        this.summary.info = 'Ringkasan AI akan muncul setelah percakapan punya konteks yang cukup.';
                        return;
                    }

                    this.summaryRefreshTimer = setTimeout(() => {
                        this.fetchConversationSummary();
                    }, delay);
                },

                async fetchConversationSummary(force = false) {
                    if (this.status !== 'closed') {
                        return;
                    }

                    if (this.eligibleSummaryMessageCount() < 2) {
                        this.summary.loading = false;
                        this.summary.available = false;
                        this.summary.text = '';
                        this.summary.sentiment = 'Neutral';
                        this.summary.error = '';
                        this.summary.info = 'Ringkasan AI akan muncul setelah percakapan punya konteks yang cukup.';
                        return;
                    }

                    const fingerprint = this.summaryFingerprint();
                    if (!force && fingerprint === this.summary.lastFingerprint) {
                        return;
                    }

                    const requestId = ++this.summaryRequestId;
                    this.summary.loading = true;
                    this.summary.error = '';

                    try {
                        const response = await fetch('{{ route('chat.summary', [], false) }}', {
                            method: 'GET',
                            headers: { 'Accept': 'application/json' }
                        });

                        const data = await response.json();
                        if (requestId !== this.summaryRequestId) return;
                        if (!response.ok) throw new Error(data.error || 'Gagal memuat ringkasan AI.');

                        this.summary.available = !!data.available;
                        this.summary.text = data.summary || '';
                        this.summary.sentiment = data.sentiment || 'Neutral';
                        this.summary.info = data.message || '';
                        this.summary.updatedAt = data.updated_at || null;
                        this.summary.historyHash = data.history_hash || null;
                        this.summary.lastFingerprint = fingerprint;
                    } catch (error) {
                        if (requestId !== this.summaryRequestId) return;

                        this.summary.available = false;
                        this.summary.text = '';
                        this.summary.sentiment = 'Neutral';
                        this.summary.error = error.message || 'Gagal memuat ringkasan AI.';
                        this.summary.info = '';
                    } finally {
                        if (requestId === this.summaryRequestId) {
                            this.summary.loading = false;
                        }
                    }
                },

                formatMessage(text) {
                    if (!text) return '';
                    
                    const badge = '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-blue-100 text-blue-700 mr-1.5 border border-blue-200 uppercase tracking-tight">BEST AI</span>';
                    
                    if (String(text).includes(badge)) {
                        let parts = String(text).split(badge);
                        let safeParts = parts.map(p => String(p).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;'));
                        let joined = safeParts.join(badge).replace(/\n/g, '<br>');
                        return joined.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                    }

                    let safeText = String(text).replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
                    let withBr = safeText.replace(/\n/g, '<br>');
                    return withBr.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
                },

                listenForEvents() {
                    if (typeof window.Echo === 'undefined') return;

                    // Catch-up setelah WebSocket reconnect
                    if (window.Echo.connector && window.Echo.connector.pusher) {
                        window.Echo.connector.pusher.connection.bind('connected', () => {
                            console.log('🔄 WebSocket reconnect — sync ulang data chat');
                            // Fetch ulang status dari server
                            fetch('{{ route("chat.init") }}', {
                                method: 'GET',
                                headers: { 'Accept': 'application/json' }
                            })
                            .then(res => res.json())
                            .then(data => {
                                if (data.conversation) {
                                    this.status = data.conversation.status;
                                    if (data.conversation.bot_phase) this.botPhase = data.conversation.bot_phase;
                                    if (data.bot_submenus) this.botSubmenus = data.bot_submenus;
                                }
                                if (data.messages) {
                                    this.messages = data.messages;
                                    this.scrollToBottom();
                                    this.queueSummaryRefresh(300);
                                }
                            })
                            .catch(e => console.warn('Reconnect sync failed:', e));
                        });
                    }

                    if (this.userId) {
                        window.Echo.private(`user.${this.userId}`)
                            .listen('.user.logged.out', (e) => {
                                if (this.feedbackPending) return;
                                setTimeout(() => {
                                    document.getElementById('logout-form').submit();
                                }, 2000);
                            });
                    }

                    window.Echo.private(`conversation.${this.conversationId}`)
                        .listen('.message.sent', (e) => {
                            if (e.sender_id == this.userId && e.sender_type === 'user') return;
                            if (e.is_whisper) return;

                            this.messages.push({
                                id: e.id,
                                sender_id: e.sender_id,
                                sender_type: e.sender_type,
                                message_type: e.message_type,
                                content: e.content,
                                created_at: new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.scrollToBottom();
                            this.queueSummaryRefresh(600);
                        })
                        .listen('.conversation.status.changed', (e) => {
                            this.status = e.status;
                            if (e.bot_phase) this.botPhase = e.bot_phase;
                            this.feedbackPending = !!e.feedback_requested;
                            if (this.feedbackPending) {
                                this.summary.expanded = true;
                                this.queueSummaryRefresh(250);
                            }
                            
                            if (e.status === 'closed') {
                                if (!this.feedbackPending) {
                                    setTimeout(() => {
                                        window.location.href = '{{ route('chat.logout') }}';
                                    }, 3000);
                                }
                            }
                        })
                        .listen('.typing', (e) => {
                            if (e.sender_type === 'admin') {
                                this.isTyping = e.is_typing;
                                this.typingMessage = (e.sender_role === 'super_admin') ? 'Admin sedang merespon' : 'Agent sedang merespon';
                                clearTimeout(this.typingTimeout);
                                if (this.isTyping) {
                                    this.typingTimeout = setTimeout(() => { this.isTyping = false; }, 3000);
                                }
                            }
                        })
                        .listen('.message.updated', (e) => {
                            const msg = this.messages.find(m => m.id === e.id);
                            if (msg) {
                                msg.content = e.content;
                                this.queueSummaryRefresh(400);
                            }
                        })
                        .listen('.message.deleted', (e) => {
                            this.messages = this.messages.filter(m => m.id !== e.id);
                            this.queueSummaryRefresh(400);
                        });
                },

                async sendMessage() {
                    if (!this.newMessage.trim() || this.isSending) return;

                    const content = this.newMessage;
                    const isEditing = this.editingMsgId !== null;
                    const editId = this.editingMsgId;

                    this.newMessage = ''; 
                    this.resizeComposer();
                    this.isSending = true;
                    this.editingMsgId = null;

                    if (!isEditing) {
                        const tempId = Date.now();
                        this.messages.push({
                            temp_id: tempId,
                            sender_type: 'user',
                            message_type: 'text',
                            content: content,
                            created_at: ''
                        });
                        this.scrollToBottom();

                        try {
                            const formData = new FormData();
                            formData.append('conversation_id', this.conversationId);
                            formData.append('content', content);

                            const response = await fetch('{{ route('chat.send', [], false) }}', {
                                method: 'POST',
                                headers: {
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: formData
                            });

                            const data = await response.json();
                            
                            const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                            if (msgIndex !== -1 && data.success) {
                                this.messages[msgIndex].id = data.message.id;
                                this.messages[msgIndex].message_type = data.message.message_type;
                                this.messages[msgIndex].content = data.message.content;
                                this.messages[msgIndex].created_at = data.message.created_at;
                                
                                // Tambahkan balasan bot jika ada (biasanya untuk fase bot)
                                if (data.bot_replies && data.bot_replies.length > 0) {
                                    data.bot_replies.forEach(botMsg => {
                                        // Hindari duplikat jika Echo sudah menambahkannya
                                        if (!this.messages.find(m => m.id === botMsg.id)) {
                                            this.messages.push(botMsg);
                                        }
                                    });
                                    this.scrollToBottom();
                                }

                                // Sync botPhase dari response backend (SINGLE SOURCE OF TRUTH)
                                if (data.bot_phase) {
                                    this.botPhase = data.bot_phase;
                                }
                                if (data.bot_submenus) {
                                    this.botSubmenus = data.bot_submenus;
                                }

                                this.queueSummaryRefresh(800);
                            }

                        } catch (error) {
                            this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        } finally {
                            this.isSending = false;
                            this.sendTypingEvent(false);
                        }
                    } else {
                        // Handle Update
                        try {
                            const response = await fetch(`/chat/message/${editId}`, {
                                method: 'PATCH',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({ content: content })
                            });

                            const data = await response.json();
                            if (!response.ok) throw new Error(data.error || 'Gagal memperbarui pesan.');

                            const msg = this.messages.find(m => m.id === editId);
                            if (msg) {
                                msg.content = content;
                                this.queueSummaryRefresh(400);
                            }
                        } catch (error) {
                            alert(error.message);
                            this.newMessage = content;
                            this.editingMsgId = editId;
                        } finally {
                            this.isSending = false;
                        }
                    }
                    
                    this.$nextTick(() => {
                        if (this.$refs.messageInput) this.$refs.messageInput.focus();
                    });
                },

                openMenu(msgId, event) {
                    this.menu.msgId = msgId;
                    this.menu.show = true;
                    
                    const rect = event.currentTarget.getBoundingClientRect();
                    this.menu.x = rect.left - 130; 
                    this.menu.y = rect.top + 25;

                    this.$nextTick(() => {
                        const menuWidth = 150;
                        if (this.menu.x + menuWidth > window.innerWidth) {
                            this.menu.x = window.innerWidth - menuWidth - 10;
                        }
                    });
                },

                handleContextMenu(event, msgId) {
                    const msg = this.messages.find(m => m.id === msgId);
                    if (!msg || msg.sender_type !== 'user') return;

                    this.menu.msgId = msgId;
                    this.menu.show = true;
                    this.menu.x = event.clientX;
                    this.menu.y = event.clientY;

                    this.$nextTick(() => {
                        const menuWidth = 150;
                        const menuHeight = 80;
                        if (this.menu.x + menuWidth > window.innerWidth) this.menu.x -= menuWidth;
                        if (this.menu.y + menuHeight > window.innerHeight) this.menu.y -= menuHeight;
                    });
                },

                closeMenu() {
                    this.menu.show = false;
                },

                editMessage(msgId) {
                    const msg = this.messages.find(m => m.id === msgId);
                    if (!msg) return;
                    
                    if (msg.sender_type !== 'user') {
                        alert('Anda hanya dapat mengedit pesan Anda sendiri.');
                        this.closeMenu();
                        return;
                    }

                    this.editingMsgId = msgId;
                    this.newMessage = msg.content.replace(/<br>/g, '\n');
                    this.closeMenu();
                    
                    this.$nextTick(() => {
                        this.$refs.messageInput.focus();
                        this.resizeComposer();
                    });
                },

                cancelEdit() {
                    this.editingMsgId = null;
                    this.newMessage = '';
                    this.resizeComposer();
                },

                async deleteMessage(msgId) {
                    const msg = this.messages.find(m => m.id === msgId);
                    if (!msg) return;

                    if (msg.sender_type !== 'user') {
                        alert('Anda hanya dapat menghapus pesan Anda sendiri.');
                        this.closeMenu();
                        return;
                    }

                    if (!confirm('Apakah Anda yakin ingin menghapus pesan ini?')) {
                        this.closeMenu();
                        return;
                    }

                    try {
                        const response = await fetch(`/chat/message/${msgId}`, {
                            method: 'DELETE',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Gagal menghapus pesan.');

                        this.messages = this.messages.filter(m => m.id !== msgId);
                        this.queueSummaryRefresh(400);
                    } catch (error) {
                        alert(error.message);
                    } finally {
                        this.closeMenu();
                    }
                },

                async uploadFile(e) {
                    const file = e.target.files[0];
                    if (!file) return;

                    this.isSending = true;
                    const tempId = Date.now();
                    
                    // Preview (if image)
                    let previewUrl = '';
                    let tempType = 'file';
                    if (file.type.startsWith('image/')) {
                        previewUrl = URL.createObjectURL(file);
                        tempType = 'image';
                    }

                    this.messages.push({
                        temp_id: tempId,
                        sender_type: 'user',
                        message_type: tempType,
                        content: previewUrl || file.name,
                        created_at: ''
                    });
                    this.scrollToBottom();

                    try {
                        const formData = new FormData();
                        formData.append('conversation_id', this.conversationId);
                        formData.append('file', file);

                        const response = await fetch('{{ route('chat.send', [], false) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: formData
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);

                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1 && data.success) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].message_type = data.message.message_type;
                            this.messages[msgIndex].content = data.message.content;
                            this.messages[msgIndex].created_at = data.message.created_at;
                            this.queueSummaryRefresh(800);
                        }
                    } catch (error) {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                        alert(error.message);
                    } finally {
                        this.isSending = false;
                        e.target.value = ''; // Reset input
                    }
                },

                async selectCategory(category) {
                    if (this.isSending || this.botPhase !== 'awaiting_category') return;
                    this.newMessage = category;
                    await this.sendMessage();
                    // botPhase sudah di-sync dari data.bot_phase di sendMessage()
                    // Tidak perlu hardcode lagi
                },

                async selectOption(option) {
                    if (this.isSending) return;
                    this.newMessage = option;
                    await this.sendMessage();
                },

                async submitFeedback() {
                    if (!this.selectedRating || this.isSubmittingFeedback) return;

                    this.isSubmittingFeedback = true;

                    try {
                        const response = await fetch('{{ route('chat.feedback.submit', ['conversation' => $conversation->id], false) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            },
                            body: JSON.stringify({
                                rating: this.selectedRating,
                                comment: this.feedbackComment
                            })
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Gagal mengirim feedback.');

                        this.feedbackPending = false;
                        window.location.href = '{{ route('chat.logout') }}';
                    } catch (error) {
                        alert(error.message);
                    } finally {
                        this.isSubmittingFeedback = false;
                    }
                },

                async skipFeedback() {
                    if (this.isSubmittingFeedback) return;

                    this.isSubmittingFeedback = true;

                    try {
                        const response = await fetch('{{ route('chat.feedback.skip', ['conversation' => $conversation->id], false) }}', {
                            method: 'POST',
                            headers: {
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json'
                            }
                        });

                        const data = await response.json();
                        if (!response.ok) throw new Error(data.error || 'Gagal melewati feedback.');

                        this.feedbackPending = false;
                        window.location.href = '{{ route('chat.logout') }}';
                    } catch (error) {
                        alert(error.message);
                    } finally {
                        this.isSubmittingFeedback = false;
                    }
                },

                // Sinkronkan indikator mengetik ke admin saat user sedang aktif menulis.
                sendTypingEvent(isTyping = true) {
                    if (this.status !== 'active') return;

                    fetch('{{ route('chat.typing', [], false) }}', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            conversation_id: this.conversationId,
                            is_typing: isTyping ? this.newMessage.length > 0 : false
                        })
                    });
                },

                // Cek apakah ini pesan bot terakhir (tidak ada pesan bot setelahnya)
                isLastBotMessage(currentIndex, messages) {
                    for (let i = currentIndex + 1; i < messages.length; i++) {
                        if (messages[i].sender_id == 0) return false;
                    }
                    return true;
                },

                scrollToBottom() {
                    setTimeout(() => {
                        const container = document.getElementById('messages-container');
                        // Use scroll into view on the anchor for better consistency
                        const anchor = document.getElementById('scroll-anchor');
                        if (anchor) anchor.scrollIntoView({behavior: 'smooth', block: 'end'});
                        else if (container) container.scrollTop = container.scrollHeight;
                    }, 50);
                }
            }));
        });
    </script>

    @include('partials.image-lightbox')
</body>
</html>
