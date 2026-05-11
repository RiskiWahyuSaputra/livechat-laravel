/**
 * Preservation Property Tests — Pengiriman Pesan Manual Tidak Berubah
 *
 * Validates: Requirements 3.1, 3.2, 3.3, 3.4
 *
 * TUJUAN: Mengkonfirmasi bahwa perilaku pengiriman pesan manual (via sendMessage()
 * langsung atau via Enter keydown) berfungsi dengan benar pada kode FIXED.
 * Test ini DIHARAPKAN LULUS pada kode fixed — kelulusan tersebut mengkonfirmasi
 * tidak ada regresi setelah fix diterapkan.
 *
 * Observasi pada kode fixed:
 * - sendMessage() dipanggil langsung dengan newMessage = 'pesan manual'
 *   → HTTP POST ke admin.chat.send dipanggil, newMessage menjadi '', pesan masuk ke list
 * - handleKeydown dengan key: 'Enter' (tanpa Shift)
 *   → sendMessage() dipanggil
 * - applySlashReply(reply) hanya mengisi newMessage = reply.content, menutup dropdown,
 *   memfokuskan textarea — TIDAK memanggil sendMessage() secara otomatis
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4**
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as fc from 'fast-check';

// ---------------------------------------------------------------------------
// Konstanta
// ---------------------------------------------------------------------------
const SEND_URL = '/admin/chat/send';

// ---------------------------------------------------------------------------
// Helper: buat instance komponen adminChat yang mereplikasi logika dari
// conversation.blade.php — termasuk sendMessage() dan handleKeydown().
// fetch() di-mock agar tidak ada HTTP request nyata.
// ---------------------------------------------------------------------------
function createAdminChatComponent({
    status = 'active',
    adminId = 1,
    sessionAdminId = 1,
    conversationId = 42,
    messageType = 'text',
} = {}) {
    const component = {
        conversationId,
        adminId,
        sessionAdminId,
        messages: [],
        status,
        newMessage: '',
        messageType,
        showSlash: false,
        slashQuery: '',
        slashIndex: 0,
        isSending: false,
        editingMsgId: null,
        quickReplies: [],
        lastActivity: Date.now(),
        reminderSentCount: 0,

        // Computed property — sama persis dengan kode asli
        get canReply() {
            if (this.status === 'closed') return true;
            return this.status === 'active' && this.adminId == this.sessionAdminId;
        },

        // $refs mock — termasuk messageInput untuk fixed applySlashReply
        $refs: {
            messageInput: {
                focus: vi.fn(),
                value: '',
                setSelectionRange: vi.fn(),
                style: { height: '32px' },
            },
        },

        // $nextTick — eksekusi callback secara sinkron
        $nextTick: vi.fn((cb) => cb()),

        // scrollToBottom — no-op
        scrollToBottom: vi.fn(),

        // sendTypingEvent — no-op
        sendTypingEvent: vi.fn(),

        // resizeComposer — no-op
        resizeComposer: vi.fn(),

        // updateMessage — no-op (tidak relevan untuk preservation test)
        updateMessage: vi.fn(),

        // applySlashReply — KODE YANG SUDAH DIPERBAIKI (fixed version)
        applySlashReply(reply) {
            if (!this.canReply) return;
            this.newMessage = reply.content;
            this.showSlash = false;
            this.$nextTick(() => {
                if (this.$refs.messageInput) {
                    this.$refs.messageInput.focus();
                    const len = this.$refs.messageInput.value.length;
                    this.$refs.messageInput.setSelectionRange(len, len);
                    this.resizeComposer();
                }
            });
        },

        // handleKeydown — KODE ASLI dari conversation.blade.php
        handleKeydown(e) {
            // Navigate slash dropdown
            if (this.showSlash && this.filteredReplies && this.filteredReplies.length > 0) {
                if (e.key === 'ArrowDown') {
                    e.preventDefault && e.preventDefault();
                    this.slashIndex = (this.slashIndex + 1) % this.filteredReplies.length;
                    this.$nextTick(() => this.scrollSlashItem && this.scrollSlashItem());
                    return;
                }
                if (e.key === 'ArrowUp') {
                    e.preventDefault && e.preventDefault();
                    this.slashIndex = (this.slashIndex - 1 + this.filteredReplies.length) % this.filteredReplies.length;
                    this.$nextTick(() => this.scrollSlashItem && this.scrollSlashItem());
                    return;
                }
                if (e.key === 'Tab' || (e.key === 'Enter' && !e.shiftKey)) {
                    e.preventDefault && e.preventDefault();
                    this.applySlashReply(this.filteredReplies[this.slashIndex]);
                    return;
                }
                if (e.key === 'Escape') {
                    this.showSlash = false;
                    return;
                }
            }

            if (e.key === 'Enter' && !e.shiftKey) {
                e.preventDefault && e.preventDefault();
                this.sendMessage();
            } else if (e.key === 'Enter' && e.shiftKey) {
                this.$nextTick(() => this.resizeComposer());
            }
        },

        // sendMessage — KODE ASLI dari conversation.blade.php (async)
        async sendMessage() {
            if (this.editingMsgId !== null) {
                return this.updateMessage();
            }
            if (!this.newMessage.trim() || this.isSending) return;

            this.lastActivity = Date.now();
            this.reminderSentCount = 0;

            const content = this.newMessage;
            const type = this.messageType;

            this.newMessage = '';
            if (this.$refs.messageInput) {
                this.$refs.messageInput.style.height = '32px';
            }
            this.isSending = true;
            this.editingMsgId = null;

            const tempId = Date.now();
            this.messages.push({
                temp_id: tempId,
                sender_type: 'admin',
                message_type: type,
                content: content,
                created_at: new Date().toISOString()
            });
            this.scrollToBottom();

            try {
                const formData = new FormData();
                formData.append('conversation_id', this.conversationId);
                formData.append('message_type', type);
                formData.append('content', content);

                const response = await fetch(SEND_URL, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': 'test-csrf-token',
                        'Accept': 'application/json'
                    },
                    body: formData
                });

                const data = await response.json();
                if (!response.ok) throw new Error(data.error || data.message || 'Server Error ' + response.status);

                const msgIndex = this.messages.findIndex(m => m.temp_id === tempId);
                if (msgIndex !== -1) {
                    this.messages[msgIndex].id = data.message.id;
                    this.messages[msgIndex].message_type = data.message.message_type;
                    this.messages[msgIndex].content = data.message.content;
                }
            } catch (error) {
                this.messages = this.messages.filter(m => m.temp_id !== tempId);
                // Tidak memanggil alert() di test environment
            } finally {
                this.isSending = false;
                this.sendTypingEvent(false);
            }

            this.$nextTick(() => {
                if (this.$refs && this.$refs.messageInput) {
                    this.$refs.messageInput.focus();
                }
            });
        },
    };

    return component;
}

// ---------------------------------------------------------------------------
// Helper: buat mock fetch yang mengembalikan respons sukses
// ---------------------------------------------------------------------------
function createMockFetch(overrides = {}) {
    const defaultResponse = {
        ok: true,
        status: 200,
        message: {
            id: 999,
            message_type: 'text',
            content: 'mocked content',
        },
    };

    const responseData = { message: { ...defaultResponse.message, ...overrides } };

    return vi.fn().mockResolvedValue({
        ok: true,
        status: 200,
        json: vi.fn().mockResolvedValue(responseData),
    });
}

// ---------------------------------------------------------------------------
// Test Suite
// ---------------------------------------------------------------------------
describe('Preservation Tests — Pengiriman Pesan Manual Tidak Berubah (FIXED CODE)', () => {

    let mockFetch;

    beforeEach(() => {
        mockFetch = createMockFetch();
        vi.stubGlobal('fetch', mockFetch);
    });

    // -------------------------------------------------------------------------
    // Property 2a: sendMessage() langsung — HTTP POST dipanggil & newMessage = ''
    // -------------------------------------------------------------------------
    describe('Property 2a: sendMessage() langsung — HTTP POST dipanggil dan newMessage dikosongkan', () => {

        /**
         * Scoped example: sendMessage() dengan newMessage = 'pesan manual'
         * → fetch dipanggil ke SEND_URL, newMessage menjadi ''
         *
         * **Validates: Requirements 3.1, 3.3**
         */
        it('Scoped case: sendMessage() dengan newMessage = "pesan manual" → fetch dipanggil dan newMessage = ""', async () => {
            const component = createAdminChatComponent();
            component.newMessage = 'pesan manual';

            await component.sendMessage();

            // fetch harus dipanggil
            expect(mockFetch).toHaveBeenCalledTimes(1);

            // fetch harus dipanggil ke URL yang benar
            expect(mockFetch).toHaveBeenCalledWith(
                SEND_URL,
                expect.objectContaining({ method: 'POST' })
            );

            // newMessage harus dikosongkan setelah pengiriman
            expect(component.newMessage).toBe('');
        });

        /**
         * Scoped example: pesan muncul di list setelah sendMessage()
         *
         * **Validates: Requirements 3.1**
         */
        it('Scoped case: pesan muncul di messages list setelah sendMessage()', async () => {
            const component = createAdminChatComponent();
            component.newMessage = 'pesan manual';

            const initialCount = component.messages.length;
            await component.sendMessage();

            // Pesan harus masuk ke list (setidaknya satu pesan baru)
            expect(component.messages.length).toBeGreaterThan(initialCount);
        });

        /**
         * PBT: untuk semua string tidak kosong, sendMessage() selalu memanggil
         * fetch dengan method POST dan mengosongkan newMessage.
         *
         * **Validates: Requirements 3.1, 3.2**
         */
        it('PBT: untuk semua pesan tidak kosong, fetch dipanggil dengan POST dan newMessage = "" setelah sendMessage()', async () => {
            await fc.assert(
                fc.asyncProperty(
                    // Generator: string tidak kosong, tidak hanya whitespace
                    fc.string({ minLength: 1, maxLength: 200 }).filter(s => s.trim().length > 0),
                    async (message) => {
                        // Reset mock untuk setiap iterasi
                        const iterFetch = createMockFetch();
                        vi.stubGlobal('fetch', iterFetch);

                        const component = createAdminChatComponent();
                        component.newMessage = message;

                        await component.sendMessage();

                        // fetch harus dipanggil tepat 1 kali
                        expect(iterFetch).toHaveBeenCalledTimes(1);

                        // fetch harus dipanggil dengan method POST
                        expect(iterFetch).toHaveBeenCalledWith(
                            SEND_URL,
                            expect.objectContaining({ method: 'POST' })
                        );

                        // newMessage harus dikosongkan
                        expect(component.newMessage).toBe('');
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

        /**
         * PBT: untuk semua string tidak kosong, pesan muncul di messages list
         * setelah sendMessage() (optimistic update).
         *
         * **Validates: Requirements 3.1**
         */
        it('PBT: untuk semua pesan tidak kosong, pesan muncul di messages list setelah sendMessage()', async () => {
            await fc.assert(
                fc.asyncProperty(
                    fc.string({ minLength: 1, maxLength: 200 }).filter(s => s.trim().length > 0),
                    async (message) => {
                        const iterFetch = createMockFetch({ content: message });
                        vi.stubGlobal('fetch', iterFetch);

                        const component = createAdminChatComponent();
                        component.newMessage = message;

                        const initialCount = component.messages.length;
                        await component.sendMessage();

                        // Pesan harus masuk ke list (optimistic update menambah entry)
                        expect(component.messages.length).toBeGreaterThan(initialCount);

                        // Setidaknya satu pesan dari admin harus ada
                        const adminMessages = component.messages.filter(m => m.sender_type === 'admin');
                        expect(adminMessages.length).toBeGreaterThan(0);
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

        /**
         * PBT: payload FormData yang dikirim ke server harus mengandung
         * conversation_id, message_type, dan content yang benar.
         *
         * **Validates: Requirements 3.1, 3.2**
         */
        it('PBT: payload fetch mengandung conversation_id, message_type, dan content yang benar', async () => {
            await fc.assert(
                fc.asyncProperty(
                    fc.string({ minLength: 1, maxLength: 200 }).filter(s => s.trim().length > 0),
                    fc.integer({ min: 1, max: 1000 }),
                    async (message, convId) => {
                        const iterFetch = createMockFetch();
                        vi.stubGlobal('fetch', iterFetch);

                        const component = createAdminChatComponent({ conversationId: convId });
                        component.newMessage = message;

                        await component.sendMessage();

                        expect(iterFetch).toHaveBeenCalledTimes(1);

                        const [url, options] = iterFetch.mock.calls[0];
                        expect(url).toBe(SEND_URL);
                        expect(options.method).toBe('POST');

                        // Verifikasi FormData mengandung field yang benar
                        const body = options.body;
                        expect(body).toBeInstanceOf(FormData);
                        expect(body.get('conversation_id')).toBe(String(convId));
                        expect(body.get('content')).toBe(message);
                        expect(body.get('message_type')).toBe('text');
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

    });

    // -------------------------------------------------------------------------
    // Property 2b: handleKeydown Enter (tanpa Shift) → sendMessage() dipanggil
    // -------------------------------------------------------------------------
    describe('Property 2b: handleKeydown Enter (tanpa Shift) → sendMessage() dipanggil', () => {

        /**
         * Scoped example: Enter keydown tanpa Shift → sendMessage() dipanggil
         *
         * **Validates: Requirements 3.2**
         */
        it('Scoped case: handleKeydown({ key: "Enter", shiftKey: false }) → sendMessage() dipanggil', async () => {
            const component = createAdminChatComponent();
            component.newMessage = 'pesan via enter';

            // Spy pada sendMessage
            const sendMessageSpy = vi.spyOn(component, 'sendMessage');

            const event = { key: 'Enter', shiftKey: false, preventDefault: vi.fn() };
            component.handleKeydown(event);

            // sendMessage harus dipanggil
            expect(sendMessageSpy).toHaveBeenCalledTimes(1);

            // preventDefault harus dipanggil
            expect(event.preventDefault).toHaveBeenCalled();
        });

        /**
         * Scoped example: Shift+Enter → sendMessage() TIDAK dipanggil
         * (hanya newline, bukan kirim)
         *
         * **Validates: Requirements 3.2**
         */
        it('Scoped case: handleKeydown({ key: "Enter", shiftKey: true }) → sendMessage() TIDAK dipanggil', () => {
            const component = createAdminChatComponent();
            component.newMessage = 'pesan dengan shift enter';

            const sendMessageSpy = vi.spyOn(component, 'sendMessage');

            const event = { key: 'Enter', shiftKey: true, preventDefault: vi.fn() };
            component.handleKeydown(event);

            // sendMessage TIDAK boleh dipanggil untuk Shift+Enter
            expect(sendMessageSpy).not.toHaveBeenCalled();
        });

        /**
         * PBT: untuk semua string tidak kosong, handleKeydown Enter (tanpa Shift)
         * selalu memanggil sendMessage().
         *
         * **Validates: Requirements 3.2**
         */
        it('PBT: untuk semua pesan tidak kosong, Enter keydown (tanpa Shift) selalu memanggil sendMessage()', () => {
            fc.assert(
                fc.property(
                    fc.string({ minLength: 1, maxLength: 200 }).filter(s => s.trim().length > 0),
                    (message) => {
                        const component = createAdminChatComponent();
                        component.newMessage = message;

                        const sendMessageSpy = vi.spyOn(component, 'sendMessage');

                        const event = { key: 'Enter', shiftKey: false, preventDefault: vi.fn() };
                        component.handleKeydown(event);

                        // sendMessage harus dipanggil tepat 1 kali
                        expect(sendMessageSpy).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

        /**
         * PBT: untuk semua string, Shift+Enter TIDAK memanggil sendMessage().
         *
         * **Validates: Requirements 3.2**
         */
        it('PBT: Shift+Enter tidak pernah memanggil sendMessage()', () => {
            fc.assert(
                fc.property(
                    fc.string({ maxLength: 200 }),
                    (message) => {
                        const component = createAdminChatComponent();
                        component.newMessage = message;

                        const sendMessageSpy = vi.spyOn(component, 'sendMessage');

                        const event = { key: 'Enter', shiftKey: true, preventDefault: vi.fn() };
                        component.handleKeydown(event);

                        expect(sendMessageSpy).not.toHaveBeenCalled();
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

        /**
         * PBT: Enter keydown saat slash dropdown TERBUKA → applySlashReply dipanggil,
         * bukan sendMessage() langsung.
         *
         * **Validates: Requirements 3.3**
         */
        it('PBT: Enter keydown saat showSlash === true → applySlashReply dipanggil (bukan sendMessage langsung)', () => {
            fc.assert(
                fc.property(
                    fc.string({ minLength: 1, maxLength: 50 }),
                    (content) => {
                        const component = createAdminChatComponent();
                        component.newMessage = '/test';
                        component.showSlash = true;
                        component.filteredReplies = [{ id: 1, command: 'test', content }];
                        component.slashIndex = 0;

                        const applySlashReplySpy = vi.spyOn(component, 'applySlashReply');
                        const sendMessageSpy = vi.spyOn(component, 'sendMessage');

                        const event = { key: 'Enter', shiftKey: false, preventDefault: vi.fn() };
                        component.handleKeydown(event);

                        // applySlashReply harus dipanggil (bukan sendMessage langsung)
                        expect(applySlashReplySpy).toHaveBeenCalledTimes(1);
                        expect(applySlashReplySpy).toHaveBeenCalledWith({ id: 1, command: 'test', content });

                        // sendMessage dipanggil oleh applySlashReply (bug unfixed) — bukan langsung oleh handleKeydown
                        // Kita hanya memverifikasi applySlashReply dipanggil, bukan sendMessage langsung
                    }
                ),
                {
                    numRuns: 30,
                    verbose: true,
                }
            );
        });

    });

    // -------------------------------------------------------------------------
    // Property 2c: sendMessage() tidak mengirim jika newMessage kosong/whitespace
    // -------------------------------------------------------------------------
    describe('Property 2c: sendMessage() tidak mengirim jika newMessage kosong atau hanya whitespace', () => {

        /**
         * Scoped example: newMessage = '' → fetch tidak dipanggil
         *
         * **Validates: Requirements 3.1**
         */
        it('Scoped case: sendMessage() dengan newMessage = "" → fetch tidak dipanggil', async () => {
            const component = createAdminChatComponent();
            component.newMessage = '';

            await component.sendMessage();

            expect(mockFetch).not.toHaveBeenCalled();
        });

        /**
         * PBT: untuk semua string yang hanya berisi whitespace, fetch tidak dipanggil.
         *
         * **Validates: Requirements 3.1**
         */
        it('PBT: untuk semua string whitespace-only, fetch tidak dipanggil', async () => {
            await fc.assert(
                fc.asyncProperty(
                    // Generator: array karakter whitespace, digabung menjadi string
                    fc.array(fc.constantFrom(' ', '\t', '\n', '\r'), { minLength: 1, maxLength: 20 })
                        .map(chars => chars.join('')),
                    async (whitespace) => {
                        const iterFetch = createMockFetch();
                        vi.stubGlobal('fetch', iterFetch);

                        const component = createAdminChatComponent();
                        component.newMessage = whitespace;

                        await component.sendMessage();

                        expect(iterFetch).not.toHaveBeenCalled();
                    }
                ),
                {
                    numRuns: 30,
                    verbose: true,
                }
            );
        });

    });

    // -------------------------------------------------------------------------
    // Property 2d: Replace existing text — applySlashReply mengisi newMessage
    // dengan reply.content (sebelum sendMessage() membersihkannya pada unfixed code)
    // -------------------------------------------------------------------------
    describe('Property 2d: applySlashReply mengisi newMessage dengan reply.content (Requirements 3.4)', () => {

        /**
         * Observasi pada kode FIXED: applySlashReply mengisi newMessage dengan
         * reply.content, menutup dropdown (showSlash = false), memfokuskan textarea,
         * dan TIDAK memanggil sendMessage() secara otomatis.
         *
         * **Validates: Requirements 3.4**
         */
        it('Scoped case: applySlashReply mengatur newMessage = reply.content, showSlash = false, dan TIDAK memanggil sendMessage (fixed)', () => {
            const component = createAdminChatComponent();
            component.newMessage = 'teks lama';

            const sendMessageSpy = vi.spyOn(component, 'sendMessage');
            const reply = { id: 1, command: 'salam', content: 'Halo, selamat datang!' };
            component.applySlashReply(reply);

            // newMessage harus diisi dengan konten quick reply
            expect(component.newMessage).toBe('Halo, selamat datang!');

            // showSlash harus ditutup
            expect(component.showSlash).toBe(false);

            // Pada kode FIXED, sendMessage TIDAK dipanggil oleh applySlashReply
            expect(sendMessageSpy).not.toHaveBeenCalled();
        });

        /**
         * PBT: untuk semua kombinasi (existingText, reply.content),
         * setelah applySlashReply, showSlash === false (dropdown ditutup).
         * Ini adalah perilaku yang harus dipertahankan setelah fix.
         *
         * **Validates: Requirements 3.4**
         */
        it('PBT: untuk semua kombinasi teks lama dan konten quick reply, showSlash === false setelah applySlashReply', () => {
            fc.assert(
                fc.property(
                    fc.string({ maxLength: 100 }),                              // teks lama (bisa kosong)
                    fc.string({ minLength: 1, maxLength: 200 }),                // konten quick reply
                    fc.integer({ min: 1, max: 100 }),
                    (existingText, replyContent, replyId) => {
                        const component = createAdminChatComponent();
                        component.newMessage = existingText;
                        component.showSlash = true;

                        const reply = { id: replyId, command: 'test', content: replyContent };
                        component.applySlashReply(reply);

                        // showSlash harus ditutup setelah applySlashReply
                        expect(component.showSlash).toBe(false);
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

    });

});
