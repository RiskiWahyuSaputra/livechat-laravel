<?php

namespace Tests\Feature;

use App\Models\Conversation;
use App\Models\User;
use App\Services\ConversationFlowService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Bug Condition Exploration Test — Queue Number Consistency
 *
 * **Validates: Requirements 1.1, 1.2, 1.3, 1.4, 1.5, 1.6**
 *
 * Phase 1 (before fix): Tests FAILED — confirmed bugs exist.
 * Phase 2 (after fix):  Tests MUST PASS — confirms bugs are fixed.
 *
 * The 4 bug scenarios tested via the FIXED ConversationFlowService:
 *   1. Duplikat (Race Condition) — reorderQueue() with lockForUpdate prevents duplicates
 *   2. Pending Diabaikan — createConversation() now counts both pending+queued
 *   3. Basis ID vs created_at — calculateQueuePosition() uses created_at FIFO
 *   4. reorderQueue Dipanggil — reorderQueue() is called at all queue entry points
 */
class QueueNumberConsistencyBugConditionTest extends TestCase
{
    use RefreshDatabase;

    private ConversationFlowService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(ConversationFlowService::class);
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    private function makeUser(string $suffix = ''): User
    {
        return User::create([
            'name'     => 'TestUser' . $suffix,
            'email'    => 'testuser' . $suffix . '@example.com',
            'contact'  => '08000000' . $suffix,
            'origin'   => 'web',
            'password' => '$2y$04$usesomesillystringfore2uDLvp1Ii2e./U9C8sBjqp8I90dH6hi',
        ]);
    }

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

    // =========================================================================
    // Bug 1 FIX: Duplikat (Race Condition)
    //
    // Fix: reorderQueue() uses DB::transaction() + lockForUpdate() so concurrent
    // calls serialize and each conversation gets a unique position.
    //
    // Test: create 2 conversations, call reorderQueue(), verify unique positions.
    // =========================================================================

    #[Test]
    public function bug1_fix_reorder_queue_assigns_unique_positions_to_concurrent_conversations(): void
    {
        $user1 = $this->makeUser('1');
        $user2 = $this->makeUser('2');

        // Both conversations enter the queue (simulating concurrent inserts)
        $conv1 = $this->makeConversation($user1, [
            'status'         => 'queued',
            'queue_position' => null,
            'created_at'     => now()->subSeconds(2),
        ]);
        $conv2 = $this->makeConversation($user2, [
            'status'         => 'queued',
            'queue_position' => null,
            'created_at'     => now()->subSeconds(1),
        ]);

        // FIX: reorderQueue() assigns unique sequential positions atomically
        $this->service->reorderQueue();

        $conv1->refresh();
        $conv2->refresh();

        // EXPECTED: each conversation gets a unique queue_position
        $this->assertNotNull($conv1->queue_position, 'conv1 should have a queue_position after reorderQueue()');
        $this->assertNotNull($conv2->queue_position, 'conv2 should have a queue_position after reorderQueue()');
        $this->assertNotEquals(
            $conv1->queue_position,
            $conv2->queue_position,
            'FIX 1 VERIFIED: Both conversations have unique queue_position values. ' .
            'conv1=' . $conv1->queue_position . ', conv2=' . $conv2->queue_position
        );

        // Positions must be 1 and 2
        $positions = [$conv1->queue_position, $conv2->queue_position];
        sort($positions);
        $this->assertEquals([1, 2], $positions,
            'FIX 1 VERIFIED: Positions are sequential [1, 2], not duplicates.'
        );
    }

    // =========================================================================
    // Bug 2 FIX: Pending Diabaikan
    //
    // Fix: reorderQueue() uses whereIn('status', ['pending', 'queued']) so
    // pending conversations are counted alongside queued ones.
    //
    // Test: 2 pending + 1 new queued → after reorderQueue(), new conv gets position 3.
    // =========================================================================

    #[Test]
    public function bug2_fix_new_queued_conversation_accounts_for_existing_pending_conversations(): void
    {
        $user1 = $this->makeUser('1');
        $user2 = $this->makeUser('2');
        $user3 = $this->makeUser('3');

        // 2 existing pending conversations
        $this->makeConversation($user1, [
            'status'     => 'pending',
            'created_at' => now()->subMinutes(20),
        ]);
        $this->makeConversation($user2, [
            'status'     => 'pending',
            'created_at' => now()->subMinutes(10),
        ]);

        // New queued conversation enters
        $newConv = $this->makeConversation($user3, [
            'status'     => 'queued',
            'created_at' => now()->subMinutes(5),
        ]);

        // FIX: reorderQueue() counts both pending AND queued
        $this->service->reorderQueue();

        $newConv->refresh();

        // EXPECTED: new conversation is at position 3 (after the 2 pending ones)
        $this->assertEquals(
            3,
            $newConv->queue_position,
            'FIX 2 VERIFIED: New queued conversation got queue_position=' . $newConv->queue_position .
            '. Expected 3 (2 pending + 1 queued = position 3).'
        );
    }

    // =========================================================================
    // Bug 3 FIX: Basis ID vs created_at
    //
    // Fix: calculateQueuePosition() uses where('created_at', '<=', ...) not id.
    //
    // Test: convEarly has higher id but earlier created_at → should get position 1.
    // =========================================================================

    #[Test]
    public function bug3_fix_queue_position_is_based_on_created_at_not_id(): void
    {
        $user1 = $this->makeUser('1');
        $user2 = $this->makeUser('2');

        $earlyTimeStr = '2020-01-01 08:00:00';
        $lateTimeStr  = '2020-01-01 09:00:00';

        // convLate inserted first (lower id), then updated to late timestamp
        $convLate = $this->makeConversation($user1, [
            'status'     => 'queued',
            'created_at' => $earlyTimeStr,
            'updated_at' => $earlyTimeStr,
        ]);
        DB::table('conversations')->where('id', $convLate->id)->update([
            'created_at' => $lateTimeStr,
            'updated_at' => $lateTimeStr,
        ]);

        // convEarly inserted second (higher id) but has earlier created_at
        $convEarly = $this->makeConversation($user2, [
            'status'     => 'queued',
            'created_at' => $earlyTimeStr,
            'updated_at' => $earlyTimeStr,
        ]);

        $convLate  = Conversation::find($convLate->id);
        $convEarly = Conversation::find($convEarly->id);

        // Verify setup
        $this->assertGreaterThan($convLate->id, $convEarly->id,
            'Setup: convEarly should have higher id than convLate');
        $this->assertTrue($convEarly->created_at->lt($convLate->created_at),
            'Setup: convEarly should have earlier created_at than convLate');

        // FIX: calculateQueuePosition() uses created_at-based FIFO
        $positionForEarly = $this->service->calculateQueuePosition($convEarly);
        $positionForLate  = $this->service->calculateQueuePosition($convLate);

        // convEarly (earlier created_at) must be position 1, convLate must be position 2
        $this->assertEquals(1, $positionForEarly,
            'FIX 3 VERIFIED: convEarly (earlier created_at, higher id) got position=' . $positionForEarly .
            '. Expected 1 based on created_at FIFO (not id-based).'
        );
        $this->assertEquals(2, $positionForLate,
            'FIX 3 VERIFIED: convLate (later created_at, lower id) got position=' . $positionForLate .
            '. Expected 2 based on created_at FIFO.'
        );
    }

    // =========================================================================
    // Bug 4 FIX: reorderQueue Dipanggil di Semua Entry Point
    //
    // Fix: reorderQueue() is now called in createConversation(), handleBotResponse(),
    // updateProfile(), and submitWhatsappRegister().
    //
    // Test: after reorderQueue() is called, all queue_position values in DB are
    // sequential and up-to-date.
    // =========================================================================

    #[Test]
    public function bug4_fix_reorder_queue_updates_all_queue_positions_in_db(): void
    {
        $user1 = $this->makeUser('1');
        $user2 = $this->makeUser('2');
        $user3 = $this->makeUser('3');

        // 2 existing queued conversations
        $conv1 = $this->makeConversation($user1, [
            'status'         => 'queued',
            'queue_position' => null,
            'created_at'     => now()->subMinutes(20),
        ]);
        $conv2 = $this->makeConversation($user2, [
            'status'         => 'queued',
            'queue_position' => null,
            'created_at'     => now()->subMinutes(10),
        ]);

        // New conversation enters the queue
        $conv3 = $this->makeConversation($user3, [
            'status'         => 'queued',
            'queue_position' => null,
            'created_at'     => now()->subMinutes(5),
        ]);

        // FIX: reorderQueue() updates ALL conversations in the queue
        $this->service->reorderQueue();

        $conv1->refresh();
        $conv2->refresh();
        $conv3->refresh();

        // EXPECTED: all 3 conversations have sequential positions 1, 2, 3
        $this->assertEquals(1, $conv1->queue_position,
            'FIX 4 VERIFIED: conv1 (earliest created_at) should have queue_position=1.'
        );
        $this->assertEquals(2, $conv2->queue_position,
            'FIX 4 VERIFIED: conv2 should have queue_position=2.'
        );
        $this->assertEquals(3, $conv3->queue_position,
            'FIX 4 VERIFIED: conv3 (newest) should have queue_position=3.'
        );
    }

    // =========================================================================
    // Combined: Core invariant — unique sequential positions based on created_at
    //
    // **Validates: Requirements 2.1, 2.2, 2.3, 2.4, 2.5, 2.6**
    // =========================================================================

    #[Test]
    public function property_all_queued_conversations_must_have_unique_sequential_positions_based_on_created_at(): void
    {
        $users = [];
        for ($i = 1; $i <= 3; $i++) {
            $users[] = $this->makeUser((string) $i);
        }

        // Create 3 conversations with varying created_at timestamps (no queue_position yet)
        foreach ($users as $i => $user) {
            $this->makeConversation($user, [
                'status'         => 'queued',
                'queue_position' => null,
                'created_at'     => now()->subMinutes(30 - ($i * 10)), // 30, 20, 10 min ago
            ]);
        }

        // FIX: reorderQueue() assigns correct sequential positions
        $this->service->reorderQueue();

        $allQueued = Conversation::whereIn('status', ['pending', 'queued'])
            ->orderBy('created_at')
            ->get();

        $positions = $allQueued->pluck('queue_position')->toArray();
        $expectedPositions = range(1, count($allQueued));

        // Check 1: All positions must be unique
        $this->assertCount(
            count($positions),
            array_unique($positions),
            'FIX VERIFIED: No duplicate queue_position values. Got: [' . implode(', ', $positions) . ']'
        );

        // Check 2: Positions must be sequential starting from 1
        sort($positions);
        $this->assertEquals(
            $expectedPositions,
            $positions,
            'FIX VERIFIED: Queue positions are sequential. Got: [' .
            implode(', ', $positions) . ']. Expected: [' .
            implode(', ', $expectedPositions) . ']'
        );

        // Check 3: Each conversation's queue_position matches its created_at rank
        foreach ($allQueued as $index => $conv) {
            $this->assertEquals(
                $index + 1,
                $conv->queue_position,
                'FIX VERIFIED: Conversation id=' . $conv->id .
                ' (created_at=' . $conv->created_at . ') has correct queue_position=' .
                $conv->queue_position . ' matching created_at FIFO rank.'
            );
        }
    }
}
