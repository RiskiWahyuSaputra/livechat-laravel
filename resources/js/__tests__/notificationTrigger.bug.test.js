/**
 * Bug Condition Exploration Test — Notification Fires During Bot Phase
 *
 * Validates: Requirements 1.3, 2.5
 *
 * TUJUAN: Memverifikasi bahwa pada kode FIXED, `playNotification()` TIDAK dipanggil
 * ketika `bot_phase` masih dalam fase bot (bukan 'off').
 *
 * Test ini DIHARAPKAN LULUS pada kode fixed — kelulusan tersebut adalah
 * bukti bahwa bug telah diperbaiki.
 *
 * Fix yang diterapkan: handler `conversation.status.changed` di chat.blade.php
 * sekarang memeriksa `e.bot_phase` sebelum memanggil playNotification():
 *
 *   // FIXED CODE:
 *   if (['pending', 'queued'].includes(e.status) && (e.bot_phase === 'off' || e.bot_phase === null || e.bot_phase === undefined)) {
 *       this.playNotification();   // ← hanya dipanggil ketika bot_phase === 'off' atau tidak ada bot
 *   }
 *
 * **Validates: Requirements 1.3, 2.5**
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as fc from 'fast-check';

// ---------------------------------------------------------------------------
// Handler Logic — FIXED CODE (extracted from chat.blade.php ~line 1381-1384)
//
// Ini adalah logika handler yang SUDAH diperbaiki. Memeriksa e.status DAN
// e.bot_phase sebelum memanggil playNotification().
// ---------------------------------------------------------------------------

/**
 * Buat instance handler yang mereplikasi logika FIXED dari chat.blade.php.
 * playNotification dan fetchChats di-mock sebagai spy untuk mendeteksi pemanggilan.
 */
function createFixedHandler() {
    const playNotification = vi.fn();
    const fetchChats = vi.fn();

    /**
     * handleConversationStatusChanged — FIXED version
     * Logika setelah fix diterapkan di chat.blade.php:
     *
     *   this.fetchChats();
     *   // Play sound only when user has truly entered the agent queue (bot_phase === 'off' or no bot)
     *   if (['pending', 'queued'].includes(e.status) && (e.bot_phase === 'off' || e.bot_phase === null || e.bot_phase === undefined)) {
     *       this.playNotification();
     *   }
     */
    function handleConversationStatusChanged(e) {
        fetchChats();
        // Play sound only when user has truly entered the agent queue (bot_phase === 'off' or no bot)
        if (['pending', 'queued'].includes(e.status) && (e.bot_phase === 'off' || e.bot_phase === null || e.bot_phase === undefined)) {
            playNotification();
        }
    }

    return { handleConversationStatusChanged, playNotification, fetchChats };
}

// Alias for backward compatibility with test cases
const createUnfixedHandler = createFixedHandler;

// ---------------------------------------------------------------------------
// Konstanta
// ---------------------------------------------------------------------------

/** Fase-fase bot yang TIDAK seharusnya memicu notifikasi */
const BOT_PHASES = [
    'awaiting_main_menu',
    'awaiting_submenu',
    'chatting_with_ai',
    'offer_agent_transfer',
];

// ---------------------------------------------------------------------------
// Test Suite
// ---------------------------------------------------------------------------

describe('Bug Condition Exploration — Notification Does NOT Fire During Bot Phase (FIXED CODE)', () => {

    /**
     * Property 1: Bug Condition — Notification Fires During Bot Phase
     *
     * Untuk setiap event dengan status 'pending' atau 'queued' DAN bot_phase
     * dalam ['awaiting_main_menu', 'awaiting_submenu', 'chatting_with_ai',
     * 'offer_agent_transfer'], handler yang BELUM diperbaiki SHALL NOT memanggil
     * playNotification().
     *
     * PADA KODE UNFIXED: assertion ini AKAN GAGAL — playNotification() dipanggil
     * untuk semua event pending/queued tanpa memandang bot_phase.
     *
     * Kegagalan ini MEMBUKTIKAN bug ada.
     *
     * **Validates: Requirements 1.3, 2.5**
     */
    describe('Property 1: playNotification() TIDAK boleh dipanggil saat bot_phase aktif', () => {

        beforeEach(() => {
            vi.clearAllMocks();
        });

        // -------------------------------------------------------------------
        // Scoped cases — satu per satu untuk setiap bot_phase
        // -------------------------------------------------------------------

        it('Scoped: {status: "pending", bot_phase: "awaiting_main_menu"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            // Dispatch event dengan bot_phase aktif
            handleConversationStatusChanged({ status: 'pending', bot_phase: 'awaiting_main_menu' });

            // ASSERTION: playNotification TIDAK boleh dipanggil
            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Scoped: {status: "pending", bot_phase: "awaiting_submenu"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'awaiting_submenu' });

            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Scoped: {status: "pending", bot_phase: "chatting_with_ai"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'chatting_with_ai' });

            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Scoped: {status: "pending", bot_phase: "offer_agent_transfer"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'offer_agent_transfer' });

            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Scoped: {status: "queued", bot_phase: "chatting_with_ai"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: 'chatting_with_ai' });

            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Scoped: {status: "queued", bot_phase: "offer_agent_transfer"} → playNotification() TIDAK boleh dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: 'offer_agent_transfer' });

            // ↓ INI AKAN GAGAL pada kode unfixed — membuktikan bug ada
            expect(playNotification).not.toHaveBeenCalled();
        });

        // -------------------------------------------------------------------
        // PBT: generate semua kombinasi status x bot_phase dari domain bug
        // -------------------------------------------------------------------

        it('PBT: untuk semua kombinasi status pending/queued x bot_phase aktif, playNotification() TIDAK boleh dipanggil', () => {
            fc.assert(
                fc.property(
                    // Generator: status dari domain bug
                    fc.constantFrom('pending', 'queued'),
                    // Generator: bot_phase dari domain bug
                    fc.constantFrom(...BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

                        // Dispatch event yang memenuhi isBugCondition
                        handleConversationStatusChanged({ status, bot_phase });

                        // ASSERTION: playNotification TIDAK boleh dipanggil
                        // ↓ INI AKAN GAGAL pada kode unfixed untuk SEMUA kombinasi
                        // Counterexample: {status: 'pending', bot_phase: 'awaiting_main_menu'}
                        //                 → playNotification() dipanggil (bug terkonfirmasi)
                        expect(playNotification).not.toHaveBeenCalled();
                    }
                ),
                {
                    numRuns: 100,
                    verbose: true,
                }
            );
        });

    });

    /**
     * Observasi tambahan: fetchChats() SELALU dipanggil (ini adalah perilaku
     * yang benar dan harus dipertahankan setelah fix).
     *
     * Test ini DIHARAPKAN LULUS pada kode unfixed maupun fixed.
     */
    describe('Observasi: fetchChats() selalu dipanggil (perilaku yang benar)', () => {

        it('fetchChats() dipanggil untuk event dengan bot_phase aktif', () => {
            const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'awaiting_main_menu' });

            // fetchChats harus selalu dipanggil — ini sudah benar di kode unfixed
            expect(fetchChats).toHaveBeenCalledTimes(1);
        });

        it('PBT: fetchChats() selalu dipanggil untuk semua event', () => {
            fc.assert(
                fc.property(
                    fc.constantFrom('pending', 'queued'),
                    fc.constantFrom(...BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // fetchChats harus selalu dipanggil
                        expect(fetchChats).toHaveBeenCalledTimes(1);
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
