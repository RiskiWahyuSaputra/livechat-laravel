<!-- Chat Widget Container -->
<div class="fixed bottom-6 right-6 md:bottom-8 md:right-8 z-50 flex flex-col items-end chat-widget-container">
    
    <!-- Chat Popup Window -->
    <div x-show="isOpen" x-cloak
         x-transition:enter="transition ease-out duration-300 transform origin-bottom-right"
         x-transition:enter-start="opacity-0 scale-50 translate-y-4"
         x-transition:enter-end="opacity-100 scale-100 translate-y-0"
         x-transition:leave="transition ease-in duration-200 transform origin-bottom-right"
         x-transition:leave-start="opacity-100 scale-100 translate-y-0"
         x-transition:leave-end="opacity-0 scale-50 translate-y-4"
         class="bg-white w-[340px] sm:w-[380px] h-[500px] max-h-[75vh] rounded-2xl shadow-2xl border border-slate-200 flex flex-col overflow-hidden mb-4 relative"
         style="display: none;">
        
        <!-- Loading Overlay -->
        <div x-show="isLoading" class="absolute inset-0 bg-white/80 backdrop-blur-sm z-50 flex items-center justify-center">
            <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
        </div>

        <!-- Header -->
        <header class="bg-white px-3 py-2 flex items-center justify-between shrink-0 shadow-sm relative border-b border-slate-100" style="background: white !important;">
            <div class="absolute top-0 left-0 right-0 h-1 bg-blue-600"></div>
            <div class="flex items-center gap-2.5 mt-0.5">
                <div class="w-8 h-8 rounded-lg bg-[#0a1d37] flex items-center justify-center shadow-sm">
                    <span class="font-black text-white text-base">CS</span>
                </div>
                <div>
                    <h3 class="font-black text-[#0a1d37] text-xs leading-tight">Layanan Pelanggan</h3>
                    <div class="flex items-center gap-1.5 text-[9px] font-bold text-slate-400 uppercase tracking-widest mt-0.5">
                        <span class="flex items-center gap-1 shrink-0"
                            :class="{
                                'text-blue-500': status === 'pending' || status === 'queued',
                                'text-emerald-500': status === 'active',
                                'text-slate-400': status === 'closed'
                            }">
                            <div class="w-1.5 h-1.5 rounded-full"
                                :class="{
                                    'bg-blue-500 animate-pulse': status === 'pending' || status === 'queued',
                                    'bg-emerald-500': status === 'active',
                                    'bg-slate-400': status === 'closed'
                                }"></div>
                            <span x-text="statusText"></span>
                        </span>
                    </div>
                </div>
            </div>
        </header>

        <!-- Messages Area (Show if chatting and NOT in registration form) -->
        <div x-show="isChatting && !showRegForm" id="widget-messages-container" class="flex-1 overflow-y-auto p-4 space-y-3 bg-slate-50 relative">
            <div class="flex justify-between items-center mb-4">
                <button @click="isChatting = false" class="text-[10px] font-bold text-blue-600 hover:underline flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Menu Utama
                </button>
                <span class="text-slate-400 font-medium text-[10px]">Percakapan Dimulai</span>
            </div>

            <template x-for="(msg, index) in messages" :key="msg.id || msg.temp_id">
                <div class="flex flex-col w-full" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                    
                    <!-- System Message -->
                    <template x-if="msg.sender_type === 'system'">
                        <div class="w-full flex justify-center my-2">
                            <div class="bg-blue-50 text-blue-800 text-[10px] px-3 py-1.5 rounded-lg border border-blue-100 text-center max-w-[85%] shadow-sm">
                                <span class="block font-medium" x-text="msg.content"></span>
                            </div>
                        </div>
                    </template>

                    <!-- Normal Text Bubble -->
                    <template x-if="msg.sender_type !== 'system'">
                        <div class="max-w-[88%] flex flex-col min-w-0" :class="msg.sender_type === 'user' ? 'items-end' : 'items-start'">
                            <span x-show="msg.sender_type !== 'user'" class="text-[10px] text-slate-400 font-medium mb-0.5 ml-1">Live Support</span>

                            <div class="px-3 py-2 md:px-3.5 md:py-2.5 rounded-2xl text-[13px] leading-relaxed shadow-sm relative overflow-hidden min-w-0 max-w-full"
                                :class="msg.sender_type === 'user' 
                                    ? 'bg-blue-600 text-white rounded-br-sm' 
                                    : 'bg-white text-slate-800 rounded-bl-sm border border-slate-200'">

                                <!-- Pesan Teks -->
                                <template x-if="!msg.message_type || msg.message_type === 'text'">
                                    <div class="break-words">
                                        <div x-html="formatMessage(msg.content)"></div>
                                    </div>
                                </template>

                                <!-- Pesan Gambar -->
                                <template x-if="msg.message_type === 'image'">
                                    <div class="max-w-full">
                                        <div class="space-y-2">
                                            <img :src="msg.content" 
                                                 class="rounded-lg max-w-full h-auto cursor-pointer hover:opacity-90 transition-opacity min-h-[50px] bg-slate-100 object-cover" 
                                                 @click="window.open(msg.content, '_blank')"
                                                 x-on:error="$el.src='https://placehold.co/200x150?text=Gambar+Gagal+Dimuat'">
                                        </div>
                                    </div>
                                </template>

                                <!-- Pesan File -->
                                <template x-if="msg.message_type === 'file'">
                                    <div class="w-full min-w-0">
                                        <div class="flex items-center gap-2 min-w-0">
                                            <div class="w-8 h-8 rounded-lg bg-slate-100/20 flex items-center justify-center text-current shrink-0 border border-white/10">
                                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                                            </div>
                                            <div class="flex-1 min-w-0">
                                                <p class="text-[11px] font-bold truncate leading-tight mb-1" x-text="msg.content.split('/').pop()"></p>
                                                <a :href="msg.content" target="_blank" class="inline-flex items-center gap-1 text-[10px] font-bold uppercase tracking-wider hover:opacity-80" :class="msg.sender_type === 'user' ? 'text-white underline' : 'text-blue-600 underline'">
                                                    <span>Unduh</span>
                                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a2 2 0 002 2h12a2 2 0 002-2v-1m-4-4l-4 4m0 0l-4-4m4 4V4"></path></svg>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </template>
                            </div>
                            <span class="text-[9px] text-slate-400 mt-1 mx-1" x-text="msg.created_at || 'mengirim...'"></span>

                            <!-- Bot Categories Inline -->
                            <template x-if="msg.sender_id == 0 && index === messages.length - 1">
                                <div class="mt-2 flex flex-wrap gap-1.5 w-full">
                                    <!-- Phase: awaiting_category -->
                                    <template x-if="botPhase === 'awaiting_category'">
                                        <div class="flex flex-wrap gap-2 w-full mt-2"
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0 transform translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0">
                                            <template x-for="cat in botCategories" :key="cat">     
                                                <button @click="selectCategory(cat)" 
                                                        class="px-4 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[12px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-2 hover:-translate-y-0.5 active:scale-95 group">
                                                    <div class="w-6 h-6 rounded-full bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                                                        <i class="fas fa-tag text-blue-400 text-[10px]"></i>
                                                    </div>
                                                    <span x-text="cat"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Phase: awaiting_cs_type -->
                                    <template x-if="botPhase === 'awaiting_cs_type'">
                                        <div class="flex flex-wrap gap-2 w-full mt-2"
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0 transform translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0">
                                            <button @click="newMessage = 'Customer service'; sendMessage()" 
                                                    class="px-4 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[12px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-2 hover:-translate-y-0.5 active:scale-95 group">
                                                <div class="w-6 h-6 rounded-full bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                                                    <i class="fas fa-headset text-blue-600 text-[11px]"></i>
                                                </div>
                                                <span>Customer service</span>
                                            </button>
                                            <button @click="newMessage = 'CS Voucher'; sendMessage()" 
                                                    class="px-4 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[12px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-2 hover:-translate-y-0.5 active:scale-95 group">
                                                <div class="w-6 h-6 rounded-full bg-orange-50 group-hover:bg-orange-100 flex items-center justify-center transition-colors">
                                                    <i class="fas fa-ticket-alt text-orange-500 text-[11px]"></i>
                                                </div>
                                                <span>CS Voucher</span>
                                            </button>
                                        </div>
                                    </template>

                                    <!-- Phase: awaiting_submenu (Dynamic) -->
                                    <template x-if="botPhase === 'awaiting_submenu'">
                                        <div class="flex flex-wrap gap-2 w-full mt-2"
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0 transform translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0">
                                            <template x-for="child in botSubmenus" :key="child.id">
                                                <button @click="handleSubmenuClick(child)" 
                                                        class="px-4 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[12px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-2 hover:-translate-y-0.5 active:scale-95 group">
                                                    <div class="w-6 h-6 rounded-full bg-blue-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                                                        <i class="fas fa-chevron-circle-right text-blue-300 text-[11px]"></i>
                                                    </div>
                                                    <span x-text="child.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Phase: awaiting_main_menu -->
                                    <template x-if="botPhase === 'awaiting_main_menu'">
                                        <div class="flex flex-wrap gap-2 w-full mt-2"
                                             x-transition:enter="transition ease-out duration-300"
                                             x-transition:enter-start="opacity-0 transform translate-y-2"
                                             x-transition:enter-end="opacity-100 transform translate-y-0">
                                            <template x-for="item in chat_main_menu" :key="item.id">
                                                <button @click="handleMenuClick(item.id)" 
                                                        class="px-4 py-2 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[12px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-2 hover:-translate-y-0.5 active:scale-95 group">
                                                    <div class="w-6 h-6 rounded-full bg-slate-50 group-hover:bg-blue-100 flex items-center justify-center transition-colors">
                                                        <template x-if="item.label.toLowerCase().includes('youtube')">
                                                            <i class="fab fa-youtube text-red-500 text-[11px]"></i>
                                                        </template>
                                                        <template x-if="item.label.toLowerCase().includes('cs') || item.label.toLowerCase().includes('hubungi')">
                                                            <i class="fas fa-headset text-blue-500 text-[11px]"></i>
                                                        </template>
                                                        <template x-if="item.label.toLowerCase().includes('seminar') || item.label.toLowerCase().includes('jadwal')">
                                                            <i class="fas fa-calendar-alt text-green-600 text-[11px]"></i>
                                                        </template>
                                                        <template x-if="!item.label.toLowerCase().includes('youtube') && !item.label.toLowerCase().includes('cs') && !item.label.toLowerCase().includes('hubungi') && !item.label.toLowerCase().includes('seminar') && !item.label.toLowerCase().includes('jadwal')">
                                                            <i class="fas fa-chevron-right text-slate-300 text-[11px]"></i>
                                                        </template>
                                                    </div>
                                                    <span x-text="item.label"></span>
                                                </button>
                                            </template>
                                        </div>
                                    </template>

                                    <!-- Phase: offer_agent_transfer -->
                                    <template x-if="botPhase === 'offer_agent_transfer'">
                                        <div class="flex flex-wrap gap-2 w-full mt-2">
                                            <button @click="newMessage = 'LANJUT'; sendMessage()" 
                                                    class="px-3 py-1.5 bg-white hover:bg-blue-50 text-blue-600 border border-blue-200 hover:border-blue-300 rounded-full text-[11px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-1.5 hover:-translate-y-0.5 active:scale-95">
                                                <i class="fas fa-comment-dots text-blue-400 text-xs"></i> Tanya Lagi
                                            </button>
                                            <button @click="newMessage = 'AGENT'; sendMessage()" 
                                                    class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white border border-blue-700 rounded-full text-[11px] font-bold tracking-tight transition-all shadow-sm flex items-center gap-1.5 hover:-translate-y-0.5 active:scale-95">
                                                <i class="fas fa-headset text-xs"></i> Hubungi Agent
                                            </button>
                                        </div>
                                    </template>
                                </div>
                            </template>
                        </div>
                    </template>

                </div>
            </template>
            <div id="widget-scroll-anchor" class="h-1"></div>
        </div>

        <!-- Registration & Greeting -->
        <div x-show="!isChatting || showRegForm" class="flex-1 overflow-y-auto p-4 bg-slate-50 flex flex-col">
            <!-- Step 1: Greeting & Buttons -->
            <div x-show="!showRegForm" class="flex-1 flex flex-col justify-center">
                <div class="bg-white rounded-2xl p-4 shadow-sm border border-slate-100 mb-6">
                    <div class="flex items-start gap-3">
                        <div class="w-8 h-8 rounded-full bg-blue-600 flex items-center justify-center shrink-0">
                            <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
                        </div>
                        <div>
                            <p class="text-sm text-slate-700 leading-relaxed" x-text="chat_greeting"></p>
                        </div>
                    </div>
                </div>

                <!-- Loading state saat menu belum siap -->
                <div x-show="chat_main_menu.length === 0 && !isInitialized" class="flex items-center justify-center py-6">
                    <div class="animate-spin rounded-full h-6 w-6 border-b-2 border-blue-600"></div>
                </div>

                <div class="grid grid-cols-1 gap-3">
                    <template x-for="item in chat_main_menu" :key="item.id">
                        <button @click="handleMenuClick(item.id)" 
                                class="w-full text-left px-4 py-3 bg-white hover:bg-blue-50 text-slate-700 hover:text-blue-600 border border-slate-200 hover:border-blue-300 rounded-2xl text-xs font-bold transition-all shadow-sm hover:shadow-md flex items-center justify-between group transform hover:-translate-y-0.5 active:scale-[0.98]">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-full flex items-center justify-center bg-slate-50 group-hover:bg-blue-100 transition-colors shrink-0">
                                    <template x-if="item.label.toLowerCase().includes('youtube')">
                                        <i class="fab fa-youtube text-red-500 text-sm"></i>
                                    </template>
                                    <template x-if="item.label.toLowerCase().includes('cs') || item.label.toLowerCase().includes('hubungi')">
                                        <i class="fas fa-headset text-blue-500 text-sm"></i>
                                    </template>
                                    <template x-if="item.label.toLowerCase().includes('seminar') || item.label.toLowerCase().includes('jadwal')">
                                        <i class="fas fa-calendar-alt text-green-600 text-sm"></i>
                                    </template>
                                    <template x-if="!item.label.toLowerCase().includes('youtube') && !item.label.toLowerCase().includes('cs') && !item.label.toLowerCase().includes('hubungi') && !item.label.toLowerCase().includes('seminar') && !item.label.toLowerCase().includes('jadwal')">
                                        <i class="fas fa-circle-chevron-right text-slate-300 text-sm"></i>
                                    </template>
                                </div>
                                <span x-text="item.label"></span>
                            </div>
                            <svg class="w-4 h-4 text-slate-300 group-hover:text-blue-500 transition-colors" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"></path></svg>
                        </button>
                    </template>
                    <div x-show="chat_main_menu.length === 0" class="text-center py-4 text-xs text-slate-400 italic">
                        Menu belum tersedia...
                    </div>
                </div>
            </div>

            <!-- Step 2: Data Entry -->
            <div x-show="showRegForm" x-cloak class="flex-1 flex flex-col justify-center">
                <button x-show="!isAuthenticated" @click="showRegForm = false" class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-4 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Kembali ke Menu
                </button>
                <!-- Tombol Batal untuk kembali membatalkan ke CS jika sudah Auth -->
                <button x-show="isAuthenticated" @click="cancelRegistration" class="inline-flex items-center text-xs text-slate-500 hover:text-blue-600 mb-4 transition-colors">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"></path></svg>
                    Batal
                </button>
                
                <div class="text-center mb-6">
                    <h4 class="font-bold text-slate-900">Lengkapi Data Diri</h4>
                    <p class="text-xs text-slate-500 mt-1">Satu langkah lagi untuk terhubung dengan kami.</p>
                </div>

                <form @submit.prevent="submitRegistration" class="space-y-4">
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Nama Lengkap <span class="text-blue-500">*</span></label>
                        <input type="text" x-model="regForm.name" required class="form-control" placeholder="Masukkan nama Anda" style="border-radius: 12px;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">No. Handphone <span class="text-blue-500">*</span></label>
                        <input type="text" x-model="regForm.contact" required class="form-control" placeholder="Contoh: 08123456789" style="border-radius: 12px;">
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-slate-700 mb-1">Asal / Instansi <span class="text-blue-500">*</span></label>
                        <input type="text" x-model="regForm.origin" required class="form-control" placeholder="Nama perusahaan atau asal Anda" style="border-radius: 12px;">
                    </div>

                    <button type="submit" :disabled="isLoading" class="btn btn-primary w-100 py-1.5 mt-2" style="border-radius: 12px; font-weight: bold;">
                        <span x-show="!isLoading">Mulai Chat</span>
                        <div x-show="isLoading" class="spinner-border spinner-border-sm" role="status"></div>
                    </button>
                    
                    <div x-show="regError" x-text="regError" class="text-xs text-danger text-center font-medium mt-2"></div>
                </form>
            </div>
        </div>

        <!-- Typing Indicator & Footer -->
        <div x-show="isChatting && !showRegForm" class="shrink-0 bg-white">
            <div x-show="isTyping" x-cloak class="px-4 py-1.5 flex items-center gap-2 bg-slate-50/80 border-t border-slate-100">
                <span class="text-[10px] italic text-slate-400 font-medium" x-text="typingMessage"></span>
                <div class="flex gap-1">
                    <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 0ms"></div>
                    <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 150ms"></div>
                    <div class="w-1 h-1 rounded-full bg-slate-400 animate-bounce" style="animation-delay: 300ms"></div>
                </div>
            </div>

            <div x-show="status === 'closed'" x-cloak class="bg-slate-100 text-slate-500 text-xs text-center p-2.5 border-t border-slate-200 font-medium">
                Sesi pertanyaan ini telah ditutup oleh agen.
            </div>

            <form @submit.prevent="sendMessage" 
                  x-show="status !== 'closed'" class="border-t border-slate-200 p-2.5 bg-white flex items-end gap-2 relative">
                <button type="button" 
                        @click="$refs.fileInput.click()"
                        class="btn btn-light shrink-0 w-10 h-10 d-flex align-items-center justify-center"
                        title="Unggah Gambar atau File" style="border-radius: 12px;">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 20px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"></path></svg>
                </button>
                <input type="file" x-ref="fileInput" class="hidden" @change="uploadFile">

                <textarea x-model="newMessage" 
                        x-ref="messageInput"
                        @input="sendTypingEvent"
                        @keydown.enter.prevent="if(!event.shiftKey) sendMessage()"
                        placeholder="Ketik balasan Anda..." 
                        class="form-control flex-1 resize-none"
                        style="border-radius: 12px; background: #f8f9fa; border: none;"
                        rows="1"></textarea>
                <button type="submit" 
                        :disabled="!newMessage.trim() || isSending || isLoading"
                        class="btn btn-primary shrink-0 w-10 h-10 d-flex align-items-center justify-center" style="border-radius: 12px;">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24" style="width: 18px;"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"></path></svg>
                </button>
            </form>
        </div>
    </div>

    <!-- Float Button (FAB) -->
    <button @click="toggleChat" 
       class="w-16 h-16 rounded-full bg-blue-600 fab-pulse flex items-center justify-center text-white shadow-2xl shadow-blue-600/40 hover:bg-blue-700 transition-all transform hover:scale-110 active:scale-95 z-[60] group"
       style="border-radius: 50% !important;"
       :aria-label="isOpen ? 'Tutup Chat' : 'Buka Chat'">
        <svg x-show="!isOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"></path></svg>
        <svg x-show="isOpen" class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>
        <!-- Unread Badge -->
        <div x-show="unreadCount > 0 && !isOpen" class="absolute -top-2 -right-2 bg-blue-700 text-white rounded-full w-6 h-6 flex items-center justify-center text-xs font-bold border-2 border-white">
            <span x-text="unreadCount"></span>
        </div>
    </button>
</div>

@push('scripts')
<script>
    document.addEventListener('alpine:init', () => {
        Alpine.data('chatWidget', () => ({
            open: false,
            isOpen: false,
            isLoading: false,
            isInitialized: false,
            isAuthenticated: {{ $isAuthenticated ? 'true' : 'false' }},
            csrfToken: '{{ csrf_token() }}',
            user: {
                name: '{{ Auth::check() ? Auth::user()->name : '' }}',
                origin: '{{ Auth::check() ? Auth::user()->origin : 'Pelanggan' }}',
                initial: '{{ Auth::check() ? strtoupper(substr(Auth::user()->name, 0, 1)) : 'G' }}'
            },

            // Form Data
            regForm: {
                name: '',
                contact: '',
                origin: ''
            },
            selectedOption: null,
            showRegForm: false,
            regError: '',
            chat_greeting: '{!! addslashes(\App\Models\Setting::get("bot_greeting_message", "Selamat datang di layanan pelanggan BRILLIAN.BIS! Ada yang bisa kami bantu?")) !!}',
            chat_main_menu: [],

            // Chat Data
            messages: [],
            newMessage: '',
            isSending: false,
            conversationId: null,
            userId: null,
            status: 'pending',
            unreadCount: 0,
            isTyping: false,
            isChatting: false,
            typingMessage: '',
            typingTimeout: null,

            // Bot Settings
            botPhase: 'off',
            botCategories: ['Pertanyaan Umum', 'Masalah Teknis', 'Layanan Produk', 'Lainnya'],
            botSubmenus: [],

            initWidget() {
                this.fetchChatData();
                setInterval(() => {
                    if (this.isAuthenticated && !window.Echo) {
                        this.fetchChatData();
                    }
                }, 30000);
            },

            toggleChat() {
                this.isOpen = !this.isOpen;
                if (this.isOpen) {
                    this.unreadCount = 0;
                    this.$nextTick(() => {
                        this.scrollToBottom();
                        if (this.$refs.messageInput) this.$refs.messageInput.focus();
                    });
                }
            },

            formatMessage(content) {
                if (!content) return '';
                return content.replace(/\n/g, '<br>');
            },

            get statusText() {
                switch(this.status) {
                    case 'pending': return 'Menunggu';
                    case 'queued': return 'Dalam Antrian';
                    case 'active': return 'Terhubung';
                    case 'closed': return 'Selesai';
                    default: return 'Online';
                }
            },

            async handleMenuClick(id) {
                this.isLoading = true;
                try {
                    this.selectedOption = id;
                    const menu = this.chat_main_menu.find(m => m.id == id);
                    if (!menu) return;
                    
                    const actionType = menu.action_type || 'connect_cs';
                    this.isChatting = true;
                    
                    if (actionType === 'submenu') {
                        this.messages.push({
                            id: 'local-user-' + Date.now(),
                            sender_type: 'user',
                            content: "Saya memilih: " + menu.label,
                            created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        });

                        setTimeout(() => {
                            this.messages.push({
                                id: 'local-bot-' + Date.now(),
                                sender_id: 0,
                                sender_type: 'admin',
                                content: menu.message_response || "Pilih layanan yang Anda inginkan:",
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });

                            this.botSubmenus = menu.submenus || [];
                            this.botPhase = 'awaiting_submenu';
                            this.scrollToBottom();
                        }, 400);
                        
                    } else if (actionType === 'link') {
                        this.messages.push({
                            id: 'local-user-' + Date.now(),
                            sender_type: 'user',
                            content: "Saya ingin melihat: " + menu.label,
                            created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        });

                        setTimeout(() => {
                            let content = menu.message_response || "Memproses permintaan Anda...";
                            if (menu.action_value) {
                                const isYoutube = menu.action_value.toLowerCase().includes('youtube.com') || menu.action_value.toLowerCase().includes('youtu.be');
                                if (isYoutube) {
                                    content += `<div class="mt-1.5"><a href="${menu.action_value}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-red-600 text-white rounded-full font-bold no-underline transition-all hover:bg-red-700 active:scale-95 shadow-sm" style="font-size: 11px; text-decoration: none; color: white;"><i class="fab fa-youtube" style="font-size: 12px;"></i> Buka YouTube</a></div>`;
                                } else {
                                    content += `<div class="mt-1.5"><a href="${menu.action_value}" target="_blank" class="inline-flex items-center justify-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white rounded-full font-bold no-underline transition-all hover:bg-blue-700 active:scale-95 shadow-sm" style="font-size: 11px; text-decoration: none; color: white;"><i class="fas fa-external-link-alt" style="font-size: 10px;"></i> Lihat Detail</a></div>`;
                                }
                            }

                            this.messages.push({
                                id: 'local-bot-' + Date.now(),
                                sender_id: 0,
                                sender_type: 'admin',
                                content: content,
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });

                            this.messages.push({
                                id: 'local-bot-menu-' + Date.now(),
                                sender_id: 0,
                                sender_type: 'admin',
                                content: "Ada lagi yang bisa kami bantu? Pilih menu di bawah ini:",
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.botPhase = 'awaiting_main_menu';
                            this.scrollToBottom();
                        }, 400);
                    } else {
                        await this.handleConnectCS(menu);
                    }
                } catch (e) {
                    console.error("Menu click error:", e);
                } finally {
                    this.isLoading = false;
                }
            },

            async handleConnectCS(menu) {
                if (!this.conversationId) {
                    try {
                        if (menu.label.toLowerCase().includes('voucher')) {
                            this.showRegForm = true;
                            this.isAuthenticated = false;
                        } else {
                            this.isLoading = true;
                            const response = await fetch('{{ route('chat.registerAnonymous') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'X-CSRF-TOKEN': this.csrfToken,
                                    'Accept': 'application/json'
                                },
                                body: JSON.stringify({
                                    selected_option: menu.id
                                })
                            });

                            const data = await response.json();
                            if (response.ok && data.success) {
                                this.isAuthenticated = true;
                                if (data.user) {
                                    this.user.name = data.user.name;
                                    this.user.initial = data.user.name.charAt(0).toUpperCase();
                                }
                                if (data.bot_phase) {
                                    this.botPhase = data.bot_phase;
                                }
                                await this.fetchChatData();
                            } else {
                                this.showRegForm = true;
                            }
                        }
                    } catch (e) {
                        this.showRegForm = true;
                    } finally {
                        this.isLoading = false;
                    }
                } else {
                    this.newMessage = menu.label;
                    await this.sendMessage();
                }
            },

            async handleSubmenuClick(child) {
                this.selectedOption = child.id;
                if (this.conversationId) {
                     this.newMessage = child.label;
                     this.sendMessage();
                     return;
                }
                this.messages.push({
                    id: 'local-user-' + Date.now(),
                    sender_type: 'user',
                    content: child.label,
                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                });
                setTimeout(() => {
                    if (child.action_type === 'connect_cs') {
                         this.handleConnectCS(child);
                    } else if (child.action_type === 'link') {
                         this.messages.push({
                            id: 'local-bot-' + Date.now(),
                            sender_id: 0,
                            sender_type: 'admin',
                            content: "Silakan buka tautan berikut: " + child.action_value,
                            created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                        });
                        this.scrollToBottom();
                    }
                }, 500);
            },

            async submitRegistration() {
                this.isLoading = true;
                this.regError = '';
                try {
                    const response = await fetch('{{ route('chat.register') }}', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': this.csrfToken,
                            'Content-Type': 'application/json',
                            'Accept': 'application/json'
                        },
                        body: JSON.stringify({
                            ...this.regForm,
                            selected_option: this.selectedOption
                        })
                    });
                    const data = await response.json();
                    if (data.success) {
                        this.showRegForm = false;
                        this.isAuthenticated = true;
                        // Update UI data instantly
                        this.user.name = data.user.name;
                        this.user.origin = data.user.origin || 'Pelanggan';
                        this.user.initial = data.user.name.charAt(0).toUpperCase();
                        
                        // Fetch the rest of chat data (conversation, etc)
                        await this.fetchChatData();
                    } else {
                        this.regError = data.message || 'Terjadi kesalahan.';
                    }
                } catch (error) {
                    this.regError = 'Gagal terhubung.';
                } finally {
                    this.isLoading = false;
                }
            },

            cancelRegistration() {
                this.showRegForm = false;
                this.regError = '';
            },

            async fetchChatData() {
                this.isLoading = true;
                try {
                    const response = await fetch('{{ route('chat.init') }}', {
                        method: 'GET',
                        headers: { 'Accept': 'application/json' }
                    });
                    const data = await response.json();
                    if (data.csrf_token) this.csrfToken = data.csrf_token;
                    if (data.chat_greeting) this.chat_greeting = data.chat_greeting;
                    if (data.chat_main_menu) this.chat_main_menu = data.chat_main_menu;

                    if (data.conversation) {
                        this.conversationId = data.conversation.id;
                        this.userId = data.user_id;
                        this.status = data.status;
                        this.botPhase = data.bot_phase || data.conversation.bot_phase || 'off';
                        if (data.bot_submenus) this.botSubmenus = data.bot_submenus;
                        this.isAuthenticated = true;
                        if (data.user) {
                            this.user.name = data.user.name;
                            this.user.initial = data.user.name ? data.user.name.charAt(0).toUpperCase() : 'G';
                        }
                        this.messages = data.messages.map(m => ({
                            id: m.id,
                            sender_id: m.sender_id,
                            sender_type: m.sender_type,
                            message_type: m.message_type,
                            content: m.content,
                            created_at: m.created_at
                        }));
                        this.isChatting = (this.messages.length > 0 && this.user.name !== 'Guest') || ['active','pending','queued'].includes(data.status);
                        this.listenForEvents();
                    } else {
                        this.isAuthenticated = false;
                    }
                    this.isInitialized = true;
                    this.$nextTick(() => { this.scrollToBottom(); });
                } catch (e) {
                } finally {
                    this.isLoading = false;
                }
            },

            listenForEvents() {
                if (typeof window.Echo === 'undefined' || !this.conversationId) return;
                try {
                    if (this.userId) {
                        window.Echo.private(`user.${this.userId}`).listen('.user.logged.out', (e) => { location.reload(); });
                    }
                    window.Echo.private(`conversation.${this.conversationId}`)
                        .listen('.message.sent', (e) => {
                            if (this.messages.some(m => m.id === e.id)) return;
                            if (e.sender_id == this.userId && e.sender_type === 'user') return;
                            if (e.is_whisper) return;
                            this.messages.push({
                                id: e.id,
                                sender_id: e.sender_id,
                                sender_type: e.sender_type,
                                message_type: e.message_type,
                                content: e.content,
                                created_at: e.created_at ? new Date(e.created_at).toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'}) : new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            if (this.isOpen) this.scrollToBottom(); else this.unreadCount++;
                        })
                        .listen('.conversation.status.changed', (e) => {
                            this.status = e.status;
                            if (e.bot_phase) this.botPhase = e.bot_phase;
                        })
                        .listen('.typing', (e) => {
                            if (e.sender_type === 'admin') {
                                this.isTyping = e.is_typing;
                                this.typingMessage = (e.sender_role === 'super_admin') ? 'Admin sedang merespon' : 'Agent sedang merespon';
                                clearTimeout(this.typingTimeout);
                                if (this.isTyping) this.typingTimeout = setTimeout(() => { this.isTyping = false; }, 3000);
                            }
                        });
                } catch (err) {}
            },

            async sendMessage() {
                if (!this.newMessage.trim() || this.isSending) return;
                const content = this.newMessage;
                this.newMessage = ''; 
                if (!this.conversationId) {
                    this.messages.push({
                        id: 'local-msg-' + Date.now(),
                        sender_type: 'user',
                        content: content,
                        created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                    });
                    const matchedMenu = this.chat_main_menu.find(m => content.toLowerCase().includes(m.label.toLowerCase()));
                    if (matchedMenu) {
                        this.handleMenuClick(matchedMenu.id);
                    } else {
                        setTimeout(() => {
                            this.messages.push({
                                id: 'local-bot-err-' + Date.now(),
                                sender_id: 0,
                                sender_type: 'admin',
                                content: "Silakan pilih menu.",
                                created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                            });
                            this.scrollToBottom();
                        }, 500);
                    }
                    this.scrollToBottom();
                    return;
                }
                this.isSending = true;
                const tempId = Date.now();
                this.messages.push({
                    temp_id: tempId,
                    sender_type: 'user',
                    message_type: 'text',
                    content: content,
                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                });
                this.scrollToBottom();
                try {
                    const formData = new FormData();
                    formData.append('conversation_id', this.conversationId);
                    formData.append('content', content);
                    const response = await fetch('{{ route('chat.send') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: formData
                    });
                    const data = await response.json();
                    if (response.ok && data.success) {
                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].content = data.message.content;
                        }
                        if (data.bot_replies) data.bot_replies.forEach(botMsg => {
                            if (!this.messages.some(m => m.id === botMsg.id)) this.messages.push(botMsg);
                        });
                        if (data.bot_phase) {
                            this.botPhase = data.bot_phase;
                            if (data.bot_phase === 'require_registration') this.showRegForm = true;
                        }
                    } else {
                        this.messages = this.messages.filter(m => m.temp_id !== tempId);
                    }
                } catch (error) {
                    this.messages = this.messages.filter(m => m.temp_id !== tempId);
                } finally {
                    this.isSending = false;
                    this.sendTypingEvent(false);
                }
            },

            async uploadFile(e) {
                const file = e.target.files[0];
                if (!file) return;
                this.isSending = true;
                const tempId = Date.now();
                let previewUrl = file.type.startsWith('image/') ? URL.createObjectURL(file) : '';
                this.messages.push({
                    temp_id: tempId,
                    sender_type: 'user',
                    message_type: previewUrl ? 'image' : 'file',
                    content: previewUrl || file.name,
                    created_at: new Date().toLocaleTimeString([], {hour: '2-digit', minute:'2-digit'})
                });
                this.scrollToBottom();
                try {
                    const formData = new FormData();
                    formData.append('conversation_id', this.conversationId);
                    formData.append('file', file);
                    const response = await fetch('{{ route('chat.send') }}', {
                        method: 'POST',
                        headers: { 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                        body: formData
                    });
                    const data = await response.json();
                    if (data.success) {
                        const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                        if (msgIndex !== -1) {
                            this.messages[msgIndex].id = data.message.id;
                            this.messages[msgIndex].content = data.message.content;
                        }
                    }
                } catch (error) {
                    this.messages = this.messages.filter(m => m.temp_id !== tempId);
                } finally {
                    this.isSending = false;
                    e.target.value = '';
                }
            },

            async selectCategory(category) {
                if (this.isSending || this.botPhase !== 'awaiting_category') return;
                this.newMessage = category;
                await this.sendMessage();
            },

            sendTypingEvent(isTyping = true) {
                if (!this.conversationId || this.status !== 'active') return;
                fetch('{{ route('chat.typing') }}', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': this.csrfToken, 'Accept': 'application/json' },
                    body: JSON.stringify({ conversation_id: this.conversationId, is_typing: isTyping ? this.newMessage.length > 0 : false })
                });
            },

            scrollToBottom() {
                setTimeout(() => {
                    const anchor = document.getElementById('widget-scroll-anchor');
                    if (anchor) anchor.scrollIntoView({behavior: 'smooth', block: 'end'});
                }, 50);
            }
        }));
    });
</script>
@endpush