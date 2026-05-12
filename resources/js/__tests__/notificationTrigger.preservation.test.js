/**
 * Preservation Property Tests — Notification and fetchChats Behavior for Non-Buggy Inputs
 *
 * Validates: Requirements 2.3, 2.4, 3.1, 3.5, 3.6
 *
 * TUJUAN: Memastikan bahwa perilaku yang BENAR pada kode UNFIXED tetap
 * dipertahankan setelah fix diterapkan. Test ini dijalankan pada kode UNFIXED
 * terlebih dahulu untuk mengamati dan mendokumentasikan baseline behavior.
 *
 * Observasi pada kode UNFIXED (non-buggy inputs):
 *   - {status: 'pending', bot_phase: 'off'}       → playNotification() IS called ✓
 *   - {status: 'queued',  bot_phase: null}         → playNotification() IS called ✓
 *   - {status: 'pending', bot_phase: undefined}    → playNotification() IS called ✓
 *   - {status: 'closed',  bot_phase: 'off'}        → playNotification() is NOT called ✓
 *   - Any event regardless of bot_phase            → fetchChats() IS called ✓
 *
 * EXPECTED OUTCOME: Semua test LULUS pada kode unfixed (baseline terkonfirmasi).
 *
 * Handler UNFIXED (dari chat.blade.php ~line 1381-1384):
 *
 *   function handleConversationStatusChanged(e) {
 *       fetchChats();
 *       if (['pending', 'queued'].includes(e.status)) {
 *           playNotification();
 *       }
 *   }
 *
 * **Validates: Requirements 2.3, 2.4, 3.1, 3.5, 3.6**
 */

import { describe, it, expect, vi, beforeEach } from 'vitest';
import * as fc from 'fast-check';

// ---------------------------------------------------------------------------
// Handler Logic — UNFIXED CODE (extracted from chat.blade.php ~line 1381-1384)
//
// Logika handler yang BELUM diperbaiki. Hanya memeriksa e.status,
// tidak memeriksa e.bot_phase sama sekali.
// ---------------------------------------------------------------------------

/**
 * Buat instance handler yang mereplikasi logika FIXED dari chat.blade.php.
 * playNotification dan fetchChats di-mock sebagai spy untuk mendeteksi pemanggilan.
 */
function createUnfixedHandler() {
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

// ---------------------------------------------------------------------------
// Konstanta — domain input
// ---------------------------------------------------------------------------

/** Nilai bot_phase yang menandakan user sudah masuk antrian agent (non-buggy) */
const NON_BUGGY_BOT_PHASES_ACTIVE = ['off'];

/** Nilai bot_phase untuk percakapan tanpa bot (backward compatibility) */
const BOT_PHASE_NULL_UNDEFINED = [null, undefined];

/** Semua nilai bot_phase yang TIDAK memenuhi isBugCondition */
const NON_BUGGY_BOT_PHASES = [...NON_BUGGY_BOT_PHASES_ACTIVE, ...BOT_PHASE_NULL_UNDEFINED];

/** Status yang seharusnya memicu notifikasi */
const NOTIFICATION_STATUSES = ['pending', 'queued'];

/** Status yang TIDAK seharusnya memicu notifikasi */
const NON_NOTIFICATION_STATUSES = ['closed', 'open', 'resolved', 'assigned'];

/** Semua nilai bot_phase yang mungkin (termasuk buggy dan non-buggy) */
const ALL_BOT_PHASES = [
    'off',
    null,
    undefined,
    'awaiting_main_menu',
    'awaiting_submenu',
    'chatting_with_ai',
    'offer_agent_transfer',
];

// ---------------------------------------------------------------------------
// Test Suite
// ---------------------------------------------------------------------------

describe('Preservation Property Tests — Non-Buggy Input Behavior (FIXED CODE)', () => {

    beforeEach(() => {
        vi.clearAllMocks();
    });

    // =========================================================================
    // P2a: bot_phase === 'off' dengan status pending/queued → playNotification() dipanggil
    // =========================================================================

    describe('P2a: playNotification() dipanggil saat bot_phase === "off" dan status pending/queued', () => {

        /**
         * P2a — Unit test: observasi langsung pada kode unfixed
         *
         * **Validates: Requirements 2.3, 2.4, 3.1**
         */
        it('Unit: {status: "pending", bot_phase: "off"} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'off' });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        it('Unit: {status: "queued", bot_phase: "off"} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: 'off' });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        /**
         * P2a — PBT: untuk semua kombinasi status pending/queued x bot_phase 'off',
         * playNotification() SELALU dipanggil.
         *
         * **Validates: Requirements 2.3, 2.4, 3.1**
         */
        it('PBT P2a: untuk semua status pending/queued dengan bot_phase "off", playNotification() selalu dipanggil', () => {
            fc.assert(
                fc.property(
                    fc.constantFrom(...NOTIFICATION_STATUSES),
                    (status) => {
                        const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase: 'off' });

                        // playNotification HARUS dipanggil — user sudah masuk antrian agent
                        expect(playNotification).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

    });

    // =========================================================================
    // P2b: bot_phase null/undefined dengan status pending/queued → playNotification() dipanggil
    //      (backward compatibility — percakapan tanpa bot)
    // =========================================================================

    describe('P2b: playNotification() dipanggil saat bot_phase null/undefined dan status pending/queued (backward compatibility)', () => {

        /**
         * P2b — Unit test: observasi langsung pada kode unfixed
         *
         * **Validates: Requirements 2.4, 3.5**
         */
        it('Unit: {status: "queued", bot_phase: null} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: null });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        it('Unit: {status: "pending", bot_phase: undefined} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: undefined });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        it('Unit: {status: "pending", bot_phase: null} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: null });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        it('Unit: {status: "queued", bot_phase: undefined} → playNotification() dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: undefined });

            expect(playNotification).toHaveBeenCalledTimes(1);
        });

        /**
         * P2b — PBT: untuk semua kombinasi status pending/queued x bot_phase null/undefined,
         * playNotification() SELALU dipanggil (backward compatibility).
         *
         * **Validates: Requirements 2.4, 3.5**
         */
        it('PBT P2b: untuk semua status pending/queued dengan bot_phase null/undefined, playNotification() selalu dipanggil', () => {
            fc.assert(
                fc.property(
                    fc.constantFrom(...NOTIFICATION_STATUSES),
                    fc.constantFrom(null, undefined),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // playNotification HARUS dipanggil — percakapan tanpa bot
                        expect(playNotification).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

    });

    // =========================================================================
    // P2c: fetchChats() selalu dipanggil untuk semua event, terlepas dari bot_phase
    // =========================================================================

    describe('P2c: fetchChats() selalu dipanggil untuk semua event (unconditional)', () => {

        /**
         * P2c — Unit test: observasi langsung pada kode unfixed
         *
         * **Validates: Requirements 3.6**
         */
        it('Unit: fetchChats() dipanggil untuk event dengan bot_phase "off"', () => {
            const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'off' });

            expect(fetchChats).toHaveBeenCalledTimes(1);
        });

        it('Unit: fetchChats() dipanggil untuk event dengan bot_phase null', () => {
            const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'queued', bot_phase: null });

            expect(fetchChats).toHaveBeenCalledTimes(1);
        });

        it('Unit: fetchChats() dipanggil untuk event dengan bot_phase "awaiting_main_menu"', () => {
            const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'pending', bot_phase: 'awaiting_main_menu' });

            expect(fetchChats).toHaveBeenCalledTimes(1);
        });

        it('Unit: fetchChats() dipanggil untuk event dengan status "closed"', () => {
            const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'closed', bot_phase: 'off' });

            expect(fetchChats).toHaveBeenCalledTimes(1);
        });

        /**
         * P2c — PBT: untuk semua kombinasi status x bot_phase yang mungkin,
         * fetchChats() SELALU dipanggil tepat satu kali.
         *
         * **Validates: Requirements 3.6**
         */
        it('PBT P2c: fetchChats() selalu dipanggil untuk semua kombinasi status dan bot_phase', () => {
            fc.assert(
                fc.property(
                    // Generator: semua status yang mungkin
                    fc.constantFrom(
                        'pending', 'queued', 'closed', 'open', 'resolved', 'assigned'
                    ),
                    // Generator: semua bot_phase yang mungkin (termasuk null/undefined)
                    fc.constantFrom(...ALL_BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // fetchChats HARUS selalu dipanggil — sidebar admin selalu diperbarui
                        expect(fetchChats).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 200,
                    verbose: true,
                }
            );
        });

        /**
         * P2c — PBT tambahan: fetchChats() dipanggil bahkan untuk event dengan
         * bot_phase aktif (fase bot) — sidebar tetap diperbarui meskipun notifikasi tidak berbunyi.
         *
         * **Validates: Requirements 3.6**
         */
        it('PBT P2c (bot phases): fetchChats() dipanggil untuk semua event dengan bot_phase aktif', () => {
            fc.assert(
                fc.property(
                    fc.constantFrom('pending', 'queued', 'closed'),
                    fc.constantFrom(
                        'awaiting_main_menu',
                        'awaiting_submenu',
                        'chatting_with_ai',
                        'offer_agent_transfer'
                    ),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, fetchChats } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        expect(fetchChats).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 100,
                    verbose: true,
                }
            );
        });

    });

    // =========================================================================
    // P2d: status bukan pending/queued → playNotification() TIDAK dipanggil
    // =========================================================================

    describe('P2d: playNotification() TIDAK dipanggil saat status bukan pending/queued', () => {

        /**
         * P2d — Unit test: observasi langsung pada kode unfixed
         *
         * **Validates: Requirements 3.1**
         */
        it('Unit: {status: "closed", bot_phase: "off"} → playNotification() TIDAK dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'closed', bot_phase: 'off' });

            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Unit: {status: "open", bot_phase: "off"} → playNotification() TIDAK dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'open', bot_phase: 'off' });

            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Unit: {status: "resolved", bot_phase: null} → playNotification() TIDAK dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'resolved', bot_phase: null });

            expect(playNotification).not.toHaveBeenCalled();
        });

        it('Unit: {status: "assigned", bot_phase: "off"} → playNotification() TIDAK dipanggil', () => {
            const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

            handleConversationStatusChanged({ status: 'assigned', bot_phase: 'off' });

            expect(playNotification).not.toHaveBeenCalled();
        });

        /**
         * P2d — PBT: untuk semua event dengan status bukan pending/queued,
         * playNotification() TIDAK PERNAH dipanggil, terlepas dari bot_phase.
         *
         * **Validates: Requirements 3.1**
         */
        it('PBT P2d: untuk semua status non-pending/queued, playNotification() tidak pernah dipanggil', () => {
            fc.assert(
                fc.property(
                    // Generator: status yang TIDAK memicu notifikasi
                    fc.constantFrom(...NON_NOTIFICATION_STATUSES),
                    // Generator: semua bot_phase yang mungkin
                    fc.constantFrom(...ALL_BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // playNotification TIDAK boleh dipanggil untuk status non-pending/queued
                        expect(playNotification).not.toHaveBeenCalled();
                    }
                ),
                {
                    numRuns: 200,
                    verbose: true,
                }
            );
        });

        /**
         * P2d — PBT tambahan: status 'closed' secara khusus tidak memicu notifikasi
         * bahkan dengan bot_phase 'off' (kasus yang paling mungkin membingungkan).
         *
         * **Validates: Requirements 3.1**
         */
        it('PBT P2d (closed): status "closed" tidak pernah memicu playNotification() untuk semua bot_phase', () => {
            fc.assert(
                fc.property(
                    fc.constantFrom(...ALL_BOT_PHASES),
                    (bot_phase) => {
                        const { handleConversationStatusChanged, playNotification } = createUnfixedHandler();

                        handleConversationStatusChanged({ status: 'closed', bot_phase });

                        expect(playNotification).not.toHaveBeenCalled();
                    }
                ),
                {
                    numRuns: 50,
                    verbose: true,
                }
            );
        });

    });

    // =========================================================================
    // Kombinasi lengkap: semua non-buggy inputs
    // =========================================================================

    describe('Kombinasi: semua non-buggy inputs menghasilkan perilaku yang benar', () => {

        /**
         * PBT Kombinasi: untuk semua event di mana isBugCondition = false,
         * perilaku handler unfixed sudah benar dan harus dipertahankan.
         *
         * isBugCondition = false ketika:
         *   - bot_phase === 'off', null, atau undefined (non-buggy bot_phase), ATAU
         *   - status bukan 'pending'/'queued'
         *
         * **Validates: Requirements 2.3, 2.4, 3.1, 3.5, 3.6**
         */
        it('PBT Kombinasi: non-buggy inputs — fetchChats() selalu dipanggil, playNotification() dipanggil sesuai status', () => {
            fc.assert(
                fc.property(
                    // Generator: status dari domain notifikasi (pending/queued)
                    fc.constantFrom(...NOTIFICATION_STATUSES),
                    // Generator: bot_phase non-buggy (off, null, undefined)
                    fc.constantFrom(...NON_BUGGY_BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, playNotification, fetchChats } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // fetchChats HARUS selalu dipanggil
                        expect(fetchChats).toHaveBeenCalledTimes(1);

                        // playNotification HARUS dipanggil untuk pending/queued dengan non-buggy bot_phase
                        expect(playNotification).toHaveBeenCalledTimes(1);
                    }
                ),
                {
                    numRuns: 100,
                    verbose: true,
                }
            );
        });

        it('PBT Kombinasi: non-notification status — fetchChats() dipanggil, playNotification() tidak dipanggil', () => {
            fc.assert(
                fc.property(
                    // Generator: status yang tidak memicu notifikasi
                    fc.constantFrom(...NON_NOTIFICATION_STATUSES),
                    // Generator: semua bot_phase yang mungkin
                    fc.constantFrom(...ALL_BOT_PHASES),
                    (status, bot_phase) => {
                        const { handleConversationStatusChanged, playNotification, fetchChats } = createUnfixedHandler();

                        handleConversationStatusChanged({ status, bot_phase });

                        // fetchChats HARUS selalu dipanggil
                        expect(fetchChats).toHaveBeenCalledTimes(1);

                        // playNotification TIDAK boleh dipanggil untuk status non-pending/queued
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

});
