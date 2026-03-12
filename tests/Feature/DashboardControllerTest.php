<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\Customer;

class DashboardControllerTest extends TestCase
{
    use RefreshDatabase;

    protected $admin;
    protected $customer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = Admin::create([
            'username' => 'testadmin',
            'email' => 'testadmin@example.com',
            'password' => bcrypt('password'),
            'max_active_chats' => 2,
            'role' => 'agent',
        ]);

        $this->customer = Customer::create([
            'name' => 'Test Customer',
            'contact' => '1234567890',
            'session_token' => \Illuminate\Support\Str::random(10),
        ]);
    }

    /** @test */
    public function admin_can_claim_a_new_conversation_when_under_limit()
    {
        $conversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
                         ->post(route('admin.conversation.claim', $conversation));

        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'admin_id' => $this->admin->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_cannot_claim_a_new_conversation_when_at_limit()
    {
        for ($i = 0; $i < 2; $i++) {
            $customer = Customer::create([
                'name' => "Test Customer {$i}",
                'contact' => "123456789{$i}",
                'session_token' => \Illuminate\Support\Str::random(10),
            ]);
            Conversation::create([
                'customer_id' => $customer->id,
                'admin_id' => $this->admin->id,
                'status' => 'active',
            ]);
        }

        $newConversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
                         ->post(route('admin.conversation.claim', $newConversation));

        $response->assertStatus(422);
        $response->assertJson(['error' => 'Anda sudah mencapai batas maksimum chat aktif.']);
        $this->assertDatabaseHas('conversations', [
            'id' => $newConversation->id,
            'admin_id' => null,
            'status' => 'pending',
        ]);
    }

    /** @test */
    public function admin_can_reclaim_their_own_pending_conversation_when_at_limit()
    {
        for ($i = 0; $i < 2; $i++) {
            $customer = Customer::create([
                'name' => "Test Customer {$i}",
                'contact' => "123456789{$i}",
                'session_token' => \Illuminate\Support\Str::random(10),
            ]);
            Conversation::create([
                'customer_id' => $customer->id,
                'admin_id' => $this->admin->id,
                'status' => 'active',
            ]);
        }

        $ownPendingConversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'admin_id' => $this->admin->id, // Already assigned to this admin
            'status' => 'pending',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
                         ->post(route('admin.conversation.claim', $ownPendingConversation));

        $response->assertOk();
        $this->assertDatabaseHas('conversations', [
            'id' => $ownPendingConversation->id,
            'admin_id' => $this->admin->id,
            'status' => 'active',
        ]);
    }

    /** @test */
    public function admin_cannot_claim_a_conversation_already_claimed_by_another_admin()
    {
        $otherAdmin = Admin::create([
            'username' => 'otheradmin',
            'email' => 'otheradmin@example.com',
            'password' => bcrypt('password'),
            'role' => 'agent',
        ]);

        $conversation = Conversation::create([
            'customer_id' => $this->customer->id,
            'admin_id' => $otherAdmin->id,
            'status' => 'active',
        ]);

        $response = $this->actingAs($this->admin, 'admin')
                         ->post(route('admin.conversation.claim', $conversation));

        $response->assertStatus(409);
        $response->assertJson(['error' => 'Chat ini sudah diambil oleh admin lain.']);
        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'admin_id' => $otherAdmin->id,
            'status' => 'active',
        ]);
    }
}
