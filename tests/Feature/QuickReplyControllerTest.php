<?php

namespace Tests\Feature;

use App\Models\Admin;
use App\Models\QuickReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

/**
 * Feature tests for QuickReplyController (store and update).
 *
 * Tests HTTP-level validation and persistence of the command field.
 */
class QuickReplyControllerTest extends TestCase
{
    use RefreshDatabase;

    private Admin $admin;

    protected function setUp(): void
    {
        parent::setUp();

        // Create a superadmin so all permission checks pass
        $this->admin = Admin::create([
            'username'      => 'testadmin',
            'email'         => 'testadmin@example.com',
            'password'      => bcrypt('password'),
            'role'          => 'super_admin',
            'is_superadmin' => true,
            'status'        => 'online',
            'max_active_chats' => 5,
            'level'         => 1,
        ]);
    }

    /**
     * Authenticate as admin, bypassing the agent_session cookie check.
     */
    private function actingAsAdmin(): static
    {
        return $this->actingAs($this->admin, 'admin')
                    ->withCookie('agent_session', 'test-session-token');
    }

    // =========================================================================
    // store() — command validation tests
    // =========================================================================

    #[Test]
    public function store_accepts_valid_command(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam_masuk',
            'content' => 'Selamat datang!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam_masuk',
        ]);
    }

    #[Test]
    public function store_rejects_command_not_starting_with_slash(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => 'salam_masuk',
            'content' => 'Selamat datang!',
        ]);

        $response->assertSessionHasErrors('command');
        $this->assertDatabaseMissing('quick_replies', ['title' => 'Salam Masuk']);
    }

    #[Test]
    public function store_rejects_command_containing_spaces(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam masuk',
            'content' => 'Selamat datang!',
        ]);

        $response->assertSessionHasErrors('command');
        $this->assertDatabaseMissing('quick_replies', ['title' => 'Salam Masuk']);
    }

    #[Test]
    public function store_rejects_command_exceeding_50_characters(): void
    {
        $longCommand = '/' . str_repeat('a', 50); // 51 chars total

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Test',
            'command' => $longCommand,
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors('command');
    }

    #[Test]
    public function store_accepts_command_of_exactly_50_characters(): void
    {
        $command = '/' . str_repeat('a', 49); // exactly 50 chars

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Test',
            'command' => $command,
            'content' => 'Content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', ['command' => $command]);
    }

    #[Test]
    public function store_rejects_duplicate_command(): void
    {
        QuickReply::create([
            'title'   => 'Existing',
            'command' => '/salam_masuk',
            'content' => 'Existing content',
        ]);

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'New Entry',
            'command' => '/salam_masuk',
            'content' => 'New content',
        ]);

        $response->assertSessionHasErrors('command');
        $this->assertDatabaseMissing('quick_replies', ['title' => 'New Entry']);
    }

    #[Test]
    public function store_auto_generates_command_when_command_is_empty(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => '',
            'content' => 'Selamat datang!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam_masuk',
        ]);
    }

    #[Test]
    public function store_auto_generates_command_when_command_is_whitespace_only(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => '   ',
            'content' => 'Selamat datang!',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam_masuk',
        ]);
    }

    #[Test]
    public function store_auto_generates_unique_command_with_suffix_when_base_exists(): void
    {
        QuickReply::create([
            'title'   => 'Existing',
            'command' => '/salam_masuk',
            'content' => 'Existing',
        ]);

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Salam Masuk',
            'command' => '',
            'content' => 'New content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'title'   => 'Salam Masuk',
            'command' => '/salam_masuk_1',
        ]);
    }

    #[Test]
    public function store_saves_command_exactly_as_provided(): void
    {
        $command = '/test_command_123';

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Test',
            'command' => $command,
            'content' => 'Content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', ['command' => $command]);
    }

    #[Test]
    public function store_requires_title(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => '',
            'command' => '/test',
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors('title');
    }

    #[Test]
    public function store_requires_content(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Test',
            'command' => '/test',
            'content' => '',
        ]);

        $response->assertSessionHasErrors('content');
    }

    // =========================================================================
    // update() — command validation tests
    // =========================================================================

    #[Test]
    public function update_accepts_valid_command(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Old Title',
            'command' => '/old_command',
            'content' => 'Old content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'New Title',
            'command' => '/new_command',
            'content' => 'New content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'id'      => $reply->id,
            'command' => '/new_command',
        ]);
    }

    #[Test]
    public function update_accepts_own_command_unchanged(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Hello',
            'command' => '/hello',
            'content' => 'Content',
        ]);

        // Update with the same command — should not fail uniqueness check
        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'Hello Updated',
            'command' => '/hello',
            'content' => 'Updated content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'id'      => $reply->id,
            'title'   => 'Hello Updated',
            'command' => '/hello',
        ]);
    }

    #[Test]
    public function update_rejects_command_belonging_to_another_entry(): void
    {
        $other = QuickReply::create([
            'title'   => 'Other',
            'command' => '/other_command',
            'content' => 'Other content',
        ]);

        $reply = QuickReply::create([
            'title'   => 'Mine',
            'command' => '/my_command',
            'content' => 'My content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'Mine',
            'command' => '/other_command', // belongs to $other
            'content' => 'My content',
        ]);

        $response->assertSessionHasErrors('command');
        // Ensure the record was not changed
        $this->assertDatabaseHas('quick_replies', [
            'id'      => $reply->id,
            'command' => '/my_command',
        ]);
    }

    #[Test]
    public function update_rejects_command_not_starting_with_slash(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Test',
            'command' => '/test',
            'content' => 'Content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'Test',
            'command' => 'no_slash',
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors('command');
    }

    #[Test]
    public function update_rejects_command_containing_spaces(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Test',
            'command' => '/test',
            'content' => 'Content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'Test',
            'command' => '/has space',
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors('command');
    }

    #[Test]
    public function update_auto_generates_command_when_command_is_empty(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Old Title',
            'command' => '/old_title',
            'content' => 'Content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'New Title',
            'command' => '',
            'content' => 'Content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'id'      => $reply->id,
            'title'   => 'New Title',
            'command' => '/new_title',
        ]);
    }

    #[Test]
    public function update_auto_generates_unique_command_excluding_self(): void
    {
        // Another record already has /new_title
        QuickReply::create([
            'title'   => 'New Title',
            'command' => '/new_title',
            'content' => 'Other',
        ]);

        $reply = QuickReply::create([
            'title'   => 'Old Title',
            'command' => '/old_title',
            'content' => 'Content',
        ]);

        $response = $this->actingAsAdmin()->put("/admin/quick-replies/{$reply->id}", [
            'title'   => 'New Title',
            'command' => '',
            'content' => 'Content',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('quick_replies', [
            'id'      => $reply->id,
            'command' => '/new_title_1',
        ]);
    }

    // =========================================================================
    // Validation error messages
    // =========================================================================

    #[Test]
    public function store_returns_correct_error_message_for_missing_slash(): void
    {
        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'Test',
            'command' => 'no_slash',
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors(['command']);
        $errors = session('errors');
        $this->assertStringContainsString('`/`', $errors->first('command'));
    }

    #[Test]
    public function store_returns_correct_error_message_for_duplicate_command(): void
    {
        QuickReply::create([
            'title'   => 'Existing',
            'command' => '/duplicate',
            'content' => 'Content',
        ]);

        $response = $this->actingAsAdmin()->post('/admin/quick-replies', [
            'title'   => 'New',
            'command' => '/duplicate',
            'content' => 'Content',
        ]);

        $response->assertSessionHasErrors(['command']);
        $errors = session('errors');
        $this->assertStringContainsString('sudah digunakan', $errors->first('command'));
    }
}

