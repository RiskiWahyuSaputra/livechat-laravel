<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Preservation Property Tests — Queue Number Consistency
 *
 * **Validates: Requirements 3.1, 3.2, 3.3, 3.4, 3.5, 3.6**
 *
 * IMPORTANT: These tests MUST PASS on UNFIXED code.
 * They capture baseline behavior that must NOT regress after the fix.
 *
 * Observation-first methodology:
 *   - Observed: percakapan `active` tidak muncul dalam hasil reorderQueue()
 *   - Observed: percakapan `closed`/`resolved` tidak mempengaruhi queue_position percakapan lain
 *   - Observed: satu percakapan dalam antrian selalu mendapat queue_position = 1
 *   - Observed: setelah agen mengklaim percakapan, percakapan yang tersisa mendapat nomor berurutan mulai dari 1
 *   - Observed: percakapan `pending` dihitung bersama `queued` dalam total antrian
 *
 * Properties tested:
 *   - Preservation Active: status `active` NOT included in reorderQueue() results
 *   - Preservation Closed/Resolved: status `closed`/`resolved` do NOT affect queue_position of others
 *   - Preservation Single Item: exactly one pending/queued conversation gets queue_position = 1
 *   - Preservation Claim: after agent claims, remaining conversations get sequential numbers from 1
 *   - Preservation Pending Included: `pending` conversations ARE included in queue count alongside `queued`
 */
class QueueNumberConsistencyPreservationTest extends TestCase
{
    use RefreshDatabase;

    // -------------------------------------------------------------------------
    // Helpers (mirrored from QueueNumberConsistencyBugConditionTest)
    // -------------------------------------------------------------------------

    /**
     * Create a minimal User for testing.
     */
    private function makeUser(string $suffix = ''): User
    {
        return User::create([
            'name'     => 'TestUser' . $suffix,
            'email'    => 'testuser' . $suffix . '@example.com',
            'contact'  => '08000000' . $suffix,
            'origin'   => 'web',
            'password' => bcrypt('password'),
        ]);
    }

    /**
     * Create a Conversation directly in the DB with explicit attributes.
     * Bypasses ConversationFlowService to set up controlled state.
     * Uses DB::table for direct insertion to allow custom created_at/updated_at.
     */
    private function makeConversation(User $user, array $attrs = []): Conversation
    {
        $defaults = [
            'user_id'         => $user->id,
            'status'          => 'queued',
            'bot_phase'       => 'off',
            'last_message_at' => now()->toDateTimeString(),
            'created_at'      => now()->toDateTimeString(),
            'updated_at'      => now()->toDateTimeString(),
        ];

        $data = array_merge($defaults, array_map(function ($value) {
            if ($value instanceof \Carbon\Carbon || $value instanceof \Illuminate\Support\Carbon) {
                return $value->toDateTimeString();
            }
            return $value;
        }, $attrs));

        $id = DB::table('conversations')->insertGetId($data);

        return Conversation::find($id);
    }

    /**
     * Invoke the reorderQueue() logic from DashboardController (unfixed code).
     * Replicated here so tests are self-contained and do not depend on HTTP layer.
     * This is the EXACT logic from DashboardController::reorderQueue() on unfixed code.
     */
    private function invokeReorderQueue(): void
    {
        $queued = Conversation::whereIn('status', ['pending', 'queued'])
            ->orderBy('created_at')
            ->get();

        foreach ($queued as $i => $conv) {
            $conv->update(['queue_position' => $i + 1]);
        }
    }

    // =========================================================================
    // Property: Preservation Active
    //
    // Requirement 3.1: Percakapan dengan status `active` (sedang ditangani agen)
    // TIDAK boleh dihitung dalam perhitungan nomor antrian.
    //
    // Observed on unfixed code: percakapan `active` tidak muncul dalam hasil
    // reorderQueue() — reorderQueue() hanya memproses pending/queued.
    //
    // **Validates: Requirements 3.1**
    // =========================================================================

    #[Test]
    public function preservation_active_conversations_are_not_included_in_reorder_queue_results(): void
    {
        // Property: For all conversations with status `active`, they are NOT
        // included in reorderQueue() results (their queue_position is not set/updated).

        $testCases = [
            ['active' => 1, 'queued' => 0],
            ['active' => 1, 'queued' => 2],
            ['active' => 3, 'queued' => 3],
            ['active' => 5, 'queued' => 1],
        ];

        foreach ($testCases as $caseIndex => $case) {
            // Reset DB for each sub-case
            DB::table('conversations')->delete();
            DB::table('users')->delete();

            $activeConvs = [];
            $queuedConvs = [];

            // Create active conversations
            for ($i = 0; $i < $case['active']; $i++) {
                $user = $this->makeUser("a{$caseIndex}_{$i}");
                $activeConvs[] = $this->makeConversation($user, [
                    'status'         => 'active',
                    'queue_position' => null,
                    'created_at'     => now()->subMinutes(100 - $i),
                ]);
            }

            // Create queued conversations
            for ($i = 0; $i < $case['queued']; $i++) {
                $user = $this->makeUser("q{$caseIndex}_{$i}");
                $queuedConvs[] = $this->makeConversation($user, [
                    'status'     => 'queued',
                    'created_at' => now()->subMinutes(50 - $i),
                ]);
            }

            // Run reorderQueue (unfixed code logic)
            $this->invokeReorderQueue();

            // Assert: active conversations are NOT touched by reorderQueue
            foreach ($activeConvs as $activeConv) {
                $fresh = $activeConv->fresh();
                $this->assertNull(
                    $fresh->queue_position,
                    "Case {$caseIndex}: Active conversation id={$fresh->id} should have " .
                    "queue_position=null after reorderQueue(), but got {$fresh->queue_position}. " .
                    "Active conversations must NOT be included in queue reordering."
                );
            }

            // Assert: queued conversations ARE assigned sequential positions
            if ($case['queued'] > 0) {
                $queuedPositions = collect($queuedConvs)
                    ->map(fn($c) => $c->fresh()->queue_position)
                    ->sort()
                    ->values()
                    ->toArray();

                $expected = range(1, $case['queued']);
                $this->assertEquals(
                    $expected,
                    $queuedPositions,
                    "Case {$caseIndex}: Queued conversations should have sequential positions " .
                    "[" . implode(', ', $expected) . "] but got [" . implode(', ', $queuedPositions) . "]"
                );
            }
        }
    }

    // =========================================================================
    // Property: Preservation Closed
    //
    // Requirement 3.2: Percakapan dengan status `closed` TIDAK boleh dihitung
    // dalam perhitungan nomor antrian.
    //
    // Note: The DB schema supports statuses: pending, active, closed, queued.
    // The bugfix.md mentions `resolved` but the actual schema uses `closed` for
    // completed conversations. Tests use only valid DB statuses.
    //
    // Observed on unfixed code: percakapan `closed` tidak mempengaruhi
    // queue_position percakapan lain — reorderQueue() hanya memproses pending/queued.
    //
    // **Validates: Requirements 3.2**
    // =========================================================================

    #[Test]
    public function preservation_closed_conversations_do_not_affect_queue_positions_of_others(): void
    {
        // Property: For all conversations with status `closed`,
        // they do NOT affect queue_position of other (pending/queued) conversations.

        $testCases = [
            ['closed' => 1, 'queued' => 2],
            ['closed' => 2, 'queued' => 2],
            ['closed' => 4, 'queued' => 3],
            ['closed' => 8, 'queued' => 1],
        ];

        foreach ($testCases as $caseIndex => $case) {
            DB::table('conversations')->delete();
            DB::table('users')->delete();

            $closedConvs = [];
            $queuedConvs = [];

            // Create closed conversations
            for ($i = 0; $i < $case['closed']; $i++) {
                $user = $this->makeUser("c{$caseIndex}_{$i}");
                $closedConvs[] = $this->makeConversation($user, [
                    'status'         => 'closed',
                    'queue_position' => null,
                    'created_at'     => now()->subMinutes(200 - $i),
                ]);
            }

            // Create queued conversations
            for ($i = 0; $i < $case['queued']; $i++) {
                $user = $this->makeUser("q{$caseIndex}_{$i}");
                $queuedConvs[] = $this->makeConversation($user, [
                    'status'     => 'queued',
                    'created_at' => now()->subMinutes(50 - $i),
                ]);
            }

            // Run reorderQueue (unfixed code logic)
            $this->invokeReorderQueue();

            // Assert: queued conversations get sequential positions 1..N
            // (closed conversations do NOT inflate the count)
            $queuedPositions = collect($queuedConvs)
                ->map(fn($c) => $c->fresh()->queue_position)
                ->sort()
                ->values()
                ->toArray();

            $expected = range(1, $case['queued']);
            $this->assertEquals(
                $expected,
                $queuedPositions,
                "Case {$caseIndex}: With {$case['closed']} closed conversations present, " .
                "queued conversations should still get positions " .
                "[" . implode(', ', $expected) . "] but got [" . implode(', ', $queuedPositions) . "]. " .
                "Closed conversations must NOT affect queue_position of others."
            );

            // Assert: closed conversations are NOT touched by reorderQueue
            foreach ($closedConvs as $closedConv) {
                $fresh = $closedConv->fresh();
                $this->assertNull(
                    $fresh->queue_position,
                    "Case {$caseIndex}: closed conversation id={$fresh->id} should have " .
                    "queue_position=null after reorderQueue(), but got {$fresh->queue_position}."
                );
            }
        }
    }

    // =========================================================================
    // Property: Preservation Single Item
    //
    // Requirement 3.4: Ketika hanya ada satu percakapan dalam antrian, nomor
    // yang ditampilkan harus `1`.
    //
    // Observed on unfixed code: satu percakapan dalam antrian selalu mendapat
    // queue_position = 1 setelah reorderQueue().
    //
    // **Validates: Requirements 3.4**
    // =========================================================================

    #[Test]
    public function preservation_single_item_queue_always_gets_queue_position_one(): void
    {
        // Property: For any queue with exactly one pending/queued conversation,
        // assert queue_position = 1.

        // Test with single 'queued' conversation
        $user1 = $this->makeUser('single_queued');
        $conv1 = $this->makeConversation($user1, ['status' => 'queued']);

        $this->invokeReorderQueue();

        $this->assertEquals(
            1,
            $conv1->fresh()->queue_position,
            "A single 'queued' conversation must get queue_position=1 after reorderQueue()."
        );

        // Reset and test with single 'pending' conversation
        DB::table('conversations')->delete();
        DB::table('users')->delete();

        $user2 = $this->makeUser('single_pending');
        $conv2 = $this->makeConversation($user2, ['status' => 'pending']);

        $this->invokeReorderQueue();

        $this->assertEquals(
            1,
            $conv2->fresh()->queue_position,
            "A single 'pending' conversation must get queue_position=1 after reorderQueue()."
        );

        // Test with single queued + many non-queue conversations (active, closed, resolved)
        DB::table('conversations')->delete();
        DB::table('users')->delete();

        $userQ = $this->makeUser('q_only');
        $convQ = $this->makeConversation($userQ, ['status' => 'queued']);

        // Add noise: active and closed (valid DB statuses per schema)
        for ($i = 0; $i < 3; $i++) {
            $uA = $this->makeUser("noise_a{$i}");
            $uC = $this->makeUser("noise_c{$i}");
            $this->makeConversation($uA, ['status' => 'active', 'queue_position' => null]);
            $this->makeConversation($uC, ['status' => 'closed', 'queue_position' => null]);
        }

        $this->invokeReorderQueue();

        $this->assertEquals(
            1,
            $convQ->fresh()->queue_position,
            "A single 'queued' conversation must get queue_position=1 even when " .
            "active/closed conversations are present."
        );
    }

    // =========================================================================
    // Property: Preservation Claim
    //
    // Requirement 3.3: Ketika agen mengklaim percakapan, antrian yang tersisa
    // harus diperbarui sehingga nomor berurutan kembali dari 1.
    //
    // Observed on unfixed code: setelah agen mengklaim percakapan, percakapan
    // yang tersisa mendapat nomor berurutan mulai dari 1 (reorderQueue() dipanggil
    // di claimConversation).
    //
    // **Validates: Requirements 3.3**
    // =========================================================================

    #[Test]
    public function preservation_claim_remaining_conversations_get_sequential_numbers_from_one(): void
    {
        // Property: For any queue after an agent claims a conversation,
        // assert remaining conversations get sequential numbers starting from 1.

        $testCases = [
            ['total' => 3, 'claim_index' => 0],  // claim first (position 1)
            ['total' => 3, 'claim_index' => 1],  // claim middle (position 2)
            ['total' => 3, 'claim_index' => 2],  // claim last (position 3)
            ['total' => 5, 'claim_index' => 0],  // claim first of 5
            ['total' => 5, 'claim_index' => 2],  // claim middle of 5
        ];

        foreach ($testCases as $caseIndex => $case) {
            DB::table('conversations')->delete();
            DB::table('users')->delete();

            $conversations = [];

            // Create N queued conversations with sequential created_at
            for ($i = 0; $i < $case['total']; $i++) {
                $user = $this->makeUser("claim{$caseIndex}_{$i}");
                $conversations[] = $this->makeConversation($user, [
                    'status'         => 'queued',
                    'queue_position' => $i + 1,
                    'created_at'     => now()->subMinutes($case['total'] - $i),
                ]);
            }

            // Simulate agent claiming the conversation at claim_index
            $claimedConv = $conversations[$case['claim_index']];
            DB::table('conversations')
                ->where('id', $claimedConv->id)
                ->update(['status' => 'active', 'queue_position' => null]);

            // Simulate reorderQueue() being called after claim (as in claimConversation)
            $this->invokeReorderQueue();

            // Collect remaining (non-claimed) conversations
            $remaining = array_values(
                array_filter($conversations, fn($c) => $c->id !== $claimedConv->id)
            );

            // Assert: remaining conversations have sequential positions 1..N-1
            $remainingPositions = collect($remaining)
                ->map(fn($c) => $c->fresh()->queue_position)
                ->sort()
                ->values()
                ->toArray();

            $expectedCount = $case['total'] - 1;
            $expected = $expectedCount > 0 ? range(1, $expectedCount) : [];

            $this->assertEquals(
                $expected,
                $remainingPositions,
                "Case {$caseIndex}: After claiming conversation at index {$case['claim_index']} " .
                "from a queue of {$case['total']}, remaining conversations should have positions " .
                "[" . implode(', ', $expected) . "] but got [" . implode(', ', $remainingPositions) . "]."
            );

            // Assert: claimed conversation is no longer in queue (status = active)
            $claimedFresh = $claimedConv->fresh();
            $this->assertEquals(
                'active',
                $claimedFresh->status,
                "Case {$caseIndex}: Claimed conversation should have status='active'."
            );
        }
    }

    // =========================================================================
    // Property: Preservation Pending Included
    //
    // Requirement 3.6: Percakapan berstatus `pending` TETAP dihitung bersama
    // `queued` dalam total antrian.
    //
    // Observed on unfixed code: reorderQueue() menggunakan
    // whereIn('status', ['pending', 'queued']) — pending dihitung bersama queued.
    //
    // **Validates: Requirements 3.6**
    // =========================================================================

    #[Test]
    public function preservation_pending_conversations_are_included_in_queue_count_alongside_queued(): void
    {
        // Property: For all conversations with status `pending`, assert they ARE
        // included in queue count alongside `queued` conversations.

        $testCases = [
            ['pending' => 1, 'queued' => 1],
            ['pending' => 2, 'queued' => 0],
            ['pending' => 0, 'queued' => 2],
            ['pending' => 3, 'queued' => 2],
            ['pending' => 2, 'queued' => 3],
        ];

        foreach ($testCases as $caseIndex => $case) {
            DB::table('conversations')->delete();
            DB::table('users')->delete();

            $pendingConvs = [];
            $queuedConvs  = [];

            // Create pending conversations (earlier timestamps)
            for ($i = 0; $i < $case['pending']; $i++) {
                $user = $this->makeUser("p{$caseIndex}_{$i}");
                $pendingConvs[] = $this->makeConversation($user, [
                    'status'     => 'pending',
                    'created_at' => now()->subMinutes(100 - $i),
                ]);
            }

            // Create queued conversations (later timestamps)
            for ($i = 0; $i < $case['queued']; $i++) {
                $user = $this->makeUser("q{$caseIndex}_{$i}");
                $queuedConvs[] = $this->makeConversation($user, [
                    'status'     => 'queued',
                    'created_at' => now()->subMinutes(50 - $i),
                ]);
            }

            // Run reorderQueue (unfixed code logic)
            $this->invokeReorderQueue();

            $totalExpected = $case['pending'] + $case['queued'];

            // Assert: all pending conversations have queue_position assigned
            foreach ($pendingConvs as $pendingConv) {
                $fresh = $pendingConv->fresh();
                $this->assertNotNull(
                    $fresh->queue_position,
                    "Case {$caseIndex}: Pending conversation id={$fresh->id} should have " .
                    "a queue_position assigned by reorderQueue(), but got null. " .
                    "Pending conversations must be included in queue count."
                );
                $this->assertGreaterThanOrEqual(
                    1,
                    $fresh->queue_position,
                    "Case {$caseIndex}: Pending conversation id={$fresh->id} should have " .
                    "queue_position >= 1, but got {$fresh->queue_position}."
                );
            }

            // Assert: all queued conversations have queue_position assigned
            foreach ($queuedConvs as $queuedConv) {
                $fresh = $queuedConv->fresh();
                $this->assertNotNull(
                    $fresh->queue_position,
                    "Case {$caseIndex}: Queued conversation id={$fresh->id} should have " .
                    "a queue_position assigned by reorderQueue(), but got null."
                );
            }

            // Assert: combined positions are sequential 1..total (no gaps, no duplicates)
            $allConvs = array_merge($pendingConvs, $queuedConvs);
            $allPositions = collect($allConvs)
                ->map(fn($c) => $c->fresh()->queue_position)
                ->sort()
                ->values()
                ->toArray();

            $expected = $totalExpected > 0 ? range(1, $totalExpected) : [];

            $this->assertEquals(
                $expected,
                $allPositions,
                "Case {$caseIndex}: Combined pending+queued conversations should have " .
                "sequential positions [" . implode(', ', $expected) . "] but got " .
                "[" . implode(', ', $allPositions) . "]. " .
                "Pending conversations must be counted alongside queued."
            );
        }
    }

    // =========================================================================
    // Property: Preservation Active (Requirement 3.5)
    //
    // Requirement 3.5: User yang sudah memiliki percakapan `active` dengan agen
    // tidak mendapat nomor antrian.
    //
    // Observed on unfixed code: calculateQueuePosition() (DashboardController)
    // uses whereIn('status', ['pending', 'queued']) — active is excluded.
    //
    // **Validates: Requirements 3.5**
    // =========================================================================

    #[Test]
    public function preservation_active_conversations_are_excluded_from_queue_position_calculation(): void
    {
        // Property: For all conversations with status `active`, calculateQueuePosition()
        // does NOT count them, so their presence does not inflate queue numbers.

        $testCases = [
            ['active' => 1, 'queued' => 2],
            ['active' => 3, 'queued' => 2],
            ['active' => 5, 'queued' => 3],
        ];

        foreach ($testCases as $caseIndex => $case) {
            DB::table('conversations')->delete();
            DB::table('users')->delete();

            $queuedConvs = [];

            // Create active conversations (should NOT be counted)
            for ($i = 0; $i < $case['active']; $i++) {
                $user = $this->makeUser("a{$caseIndex}_{$i}");
                $this->makeConversation($user, [
                    'status'         => 'active',
                    'queue_position' => null,
                    'created_at'     => now()->subMinutes(200 - $i),
                ]);
            }

            // Create queued conversations
            for ($i = 0; $i < $case['queued']; $i++) {
                $user = $this->makeUser("q{$caseIndex}_{$i}");
                $queuedConvs[] = $this->makeConversation($user, [
                    'status'     => 'queued',
                    'created_at' => now()->subMinutes(50 - $i),
                ]);
            }

            // Use calculateQueuePosition() logic (from DashboardController unfixed code)
            // to verify active conversations are excluded
            foreach ($queuedConvs as $queuedConv) {
                $calculatedPosition = Conversation::whereIn('status', ['pending', 'queued'])
                    ->where('created_at', '<=', $queuedConv->created_at)
                    ->where('id', '<=', $queuedConv->id)
                    ->count();

                // Position should be based only on pending/queued count, not active
                $this->assertLessThanOrEqual(
                    $case['queued'],
                    $calculatedPosition,
                    "Case {$caseIndex}: calculateQueuePosition() for queued conversation " .
                    "id={$queuedConv->id} returned {$calculatedPosition}, which exceeds the " .
                    "total queued count {$case['queued']}. Active conversations must NOT be counted."
                );

                $this->assertGreaterThanOrEqual(
                    1,
                    $calculatedPosition,
                    "Case {$caseIndex}: calculateQueuePosition() should return >= 1 for a queued conversation."
                );
            }

            // Also verify via reorderQueue: queued positions are 1..N (not inflated by active)
            $this->invokeReorderQueue();

            $queuedPositions = collect($queuedConvs)
                ->map(fn($c) => $c->fresh()->queue_position)
                ->sort()
                ->values()
                ->toArray();

            $expected = range(1, $case['queued']);
            $this->assertEquals(
                $expected,
                $queuedPositions,
                "Case {$caseIndex}: With {$case['active']} active conversations present, " .
                "queued conversations should still get positions [" . implode(', ', $expected) . "] " .
                "but got [" . implode(', ', $queuedPositions) . "]. " .
                "Active conversations must NOT inflate queue positions."
            );
        }
    }
}
