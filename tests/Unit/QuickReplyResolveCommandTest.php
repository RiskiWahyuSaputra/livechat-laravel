<?php

namespace Tests\Unit;

use App\Http\Controllers\Admin\QuickReplyController;
use App\Models\QuickReply;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Test;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Unit tests for QuickReplyController::resolveCommand()
 *
 * Tests the private resolveCommand() method via reflection to verify
 * auto-generate logic for the command field.
 */
class QuickReplyResolveCommandTest extends TestCase
{
    use RefreshDatabase;

    private QuickReplyController $controller;
    private ReflectionMethod $resolveCommand;

    protected function setUp(): void
    {
        parent::setUp();

        $this->controller = new QuickReplyController();

        $this->resolveCommand = new ReflectionMethod(
            QuickReplyController::class,
            'resolveCommand'
        );
        $this->resolveCommand->setAccessible(true);
    }

    private function resolve(string $title, ?int $excludeId = null): string
    {
        return $this->resolveCommand->invoke($this->controller, $title, $excludeId);
    }

    // -------------------------------------------------------------------------
    // Format output tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_generates_command_starting_with_slash(): void
    {
        $command = $this->resolve('Hello World');
        $this->assertStringStartsWith('/', $command);
    }

    #[Test]
    public function it_converts_title_to_lowercase(): void
    {
        $command = $this->resolve('Hello World');
        $this->assertEquals('/hello_world', $command);
    }

    #[Test]
    public function it_replaces_spaces_with_underscores(): void
    {
        $command = $this->resolve('salam masuk');
        $this->assertEquals('/salam_masuk', $command);
    }

    #[Test]
    public function it_removes_special_characters(): void
    {
        $command = $this->resolve('Hello! World?');
        $this->assertEquals('/hello_world', $command);
    }

    #[Test]
    public function it_removes_non_ascii_characters(): void
    {
        $command = $this->resolve('Selamat Pagi!');
        $this->assertEquals('/selamat_pagi', $command);
    }

    #[Test]
    public function it_removes_punctuation(): void
    {
        $command = $this->resolve('Hello, World.');
        $this->assertEquals('/hello_world', $command);
    }

    #[Test]
    public function it_keeps_numbers_in_command(): void
    {
        $command = $this->resolve('reply 123');
        $this->assertEquals('/reply_123', $command);
    }

    #[Test]
    public function it_keeps_underscores_in_command(): void
    {
        $command = $this->resolve('hello_world');
        $this->assertEquals('/hello_world', $command);
    }

    #[Test]
    public function generated_command_length_does_not_exceed_50_chars(): void
    {
        $command = $this->resolve('Hello World');
        $this->assertLessThanOrEqual(50, strlen($command));
    }

    // -------------------------------------------------------------------------
    // Truncation tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_truncates_very_long_title_to_50_chars(): void
    {
        // 60-character title (all lowercase letters, no special chars)
        $title = str_repeat('a', 60);
        $command = $this->resolve($title);

        $this->assertLessThanOrEqual(50, strlen($command));
        $this->assertStringStartsWith('/', $command);
    }

    #[Test]
    public function it_truncates_exactly_at_49_chars_plus_slash(): void
    {
        // Title that produces exactly 49 base chars
        $title = str_repeat('a', 49);
        $command = $this->resolve($title);

        // Should be '/' + 49 chars = 50 chars total
        $this->assertEquals(50, strlen($command));
        $this->assertEquals('/' . str_repeat('a', 49), $command);
    }

    #[Test]
    public function it_truncates_title_longer_than_49_chars(): void
    {
        $title = str_repeat('b', 100);
        $command = $this->resolve($title);

        // Should be '/' + 49 chars = 50 chars total
        $this->assertEquals(50, strlen($command));
    }

    // -------------------------------------------------------------------------
    // Suffix uniqueness tests
    // -------------------------------------------------------------------------

    #[Test]
    public function it_adds_suffix_1_when_base_command_already_exists(): void
    {
        QuickReply::create([
            'title'   => 'Hello World',
            'command' => '/hello_world',
            'content' => 'test',
        ]);

        $command = $this->resolve('Hello World');
        $this->assertEquals('/hello_world_1', $command);
    }

    #[Test]
    public function it_adds_suffix_2_when_suffix_1_also_exists(): void
    {
        QuickReply::create(['title' => 'Hello World', 'command' => '/hello_world', 'content' => 'test']);
        QuickReply::create(['title' => 'Hello World 1', 'command' => '/hello_world_1', 'content' => 'test']);

        $command = $this->resolve('Hello World');
        $this->assertEquals('/hello_world_2', $command);
    }

    #[Test]
    public function it_adds_suffix_10_when_suffixes_1_through_9_exist(): void
    {
        QuickReply::create(['title' => 'Test', 'command' => '/test', 'content' => 'c']);
        for ($i = 1; $i <= 9; $i++) {
            QuickReply::create(['title' => "Test $i", 'command' => "/test_$i", 'content' => 'c']);
        }

        $command = $this->resolve('Test');
        $this->assertEquals('/test_10', $command);
    }

    #[Test]
    public function suffix_does_not_cause_total_length_to_exceed_50_chars(): void
    {
        // Create a base command that is exactly 49 chars (+ '/' = 50)
        $base = str_repeat('a', 49);
        QuickReply::create(['title' => str_repeat('a', 49), 'command' => '/' . $base, 'content' => 'c']);

        $command = $this->resolve(str_repeat('a', 49));

        $this->assertLessThanOrEqual(50, strlen($command));
        $this->assertStringStartsWith('/', $command);
    }

    #[Test]
    public function suffix_with_two_digits_does_not_exceed_50_chars(): void
    {
        // Create base + suffixes _1 through _9 to force _10
        $base = str_repeat('a', 49);
        QuickReply::create(['title' => 'a', 'command' => '/' . $base, 'content' => 'c']);
        for ($i = 1; $i <= 9; $i++) {
            // Suffix _i: base must be trimmed to fit
            $suffix = '_' . $i;
            $trimmedBase = substr($base, 0, 49 - strlen($suffix));
            QuickReply::create(['title' => "a$i", 'command' => '/' . $trimmedBase . $suffix, 'content' => 'c']);
        }

        $command = $this->resolve(str_repeat('a', 49));

        $this->assertLessThanOrEqual(50, strlen($command));
        $this->assertStringStartsWith('/', $command);
    }

    // -------------------------------------------------------------------------
    // excludeId tests (for update)
    // -------------------------------------------------------------------------

    #[Test]
    public function it_excludes_given_id_from_uniqueness_check(): void
    {
        $reply = QuickReply::create([
            'title'   => 'Hello World',
            'command' => '/hello_world',
            'content' => 'test',
        ]);

        // When updating the same record, it should return the same command (not add suffix)
        $command = $this->resolve('Hello World', $reply->id);
        $this->assertEquals('/hello_world', $command);
    }

    #[Test]
    public function it_still_adds_suffix_when_conflict_is_different_record(): void
    {
        $other = QuickReply::create([
            'title'   => 'Hello World',
            'command' => '/hello_world',
            'content' => 'test',
        ]);

        $current = QuickReply::create([
            'title'   => 'Something Else',
            'command' => '/something_else',
            'content' => 'test',
        ]);

        // Updating $current with title 'Hello World' — conflict with $other, not $current
        $command = $this->resolve('Hello World', $current->id);
        $this->assertEquals('/hello_world_1', $command);
    }

    // -------------------------------------------------------------------------
    // Edge cases
    // -------------------------------------------------------------------------

    #[Test]
    public function it_handles_title_with_only_special_characters(): void
    {
        // After stripping, base becomes empty — command would be just '/'
        // The method should still return something starting with '/'
        $command = $this->resolve('!!!');
        $this->assertStringStartsWith('/', $command);
        $this->assertLessThanOrEqual(50, strlen($command));
    }

    #[Test]
    public function it_handles_single_word_title(): void
    {
        $command = $this->resolve('salam');
        $this->assertEquals('/salam', $command);
    }

    #[Test]
    public function it_handles_title_with_multiple_spaces(): void
    {
        $command = $this->resolve('hello   world');
        // Multiple spaces become multiple underscores
        $this->assertStringStartsWith('/', $command);
        $this->assertMatchesRegularExpression('/^\/[a-z0-9_]+$/', $command);
    }
}

