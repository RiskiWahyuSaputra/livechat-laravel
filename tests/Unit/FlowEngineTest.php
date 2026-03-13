<?php

namespace Tests\Unit;

use App\Models\ConversationFlow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use App\Services\FlowEngine;
use App\Services\OfficeHoursService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class FlowEngineTest extends TestCase
{
    use RefreshDatabase;

    private FlowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();

        // Mock OfficeHoursService so engine tests are not time-dependent
        $this->officeHoursMock = Mockery::mock(OfficeHoursService::class);
        $this->engine = new FlowEngine($this->officeHoursMock);
    }

    // -------------------------------------------------------------------------
    // Helper: build a minimal published flow
    // -------------------------------------------------------------------------

    private function buildSimpleFlow(): array
    {
        $flow = ConversationFlow::create([
            'code'   => 'test_flow',
            'name'   => 'Test Flow',
            'status' => 'published',
        ]);

        $start   = FlowNode::create(['flow_id' => $flow->id, 'code' => 'start',   'type' => 'START']);
        $message = FlowNode::create(['flow_id' => $flow->id, 'code' => 'greeting','type' => 'MESSAGE', 'content' => ['text' => 'Hello!']]);
        $menu    = FlowNode::create(['flow_id' => $flow->id, 'code' => 'menu',    'type' => 'MENU',    'content' => ['prompt' => 'Choose:', 'options' => [['key' => '1', 'label' => 'Option A'], ['key' => '2', 'label' => 'Option B']]]]);
        $optA    = FlowNode::create(['flow_id' => $flow->id, 'code' => 'opt_a',   'type' => 'MESSAGE', 'content' => ['text' => 'You chose A']]);
        $optB    = FlowNode::create(['flow_id' => $flow->id, 'code' => 'opt_b',   'type' => 'MESSAGE', 'content' => ['text' => 'You chose B']]);
        $end     = FlowNode::create(['flow_id' => $flow->id, 'code' => 'end',     'type' => 'END']);
        $fallback = FlowNode::create(['flow_id' => $flow->id, 'code' => 'fallback', 'type' => 'FALLBACK', 'content' => ['text' => 'Invalid choice', 'go_to_node_code' => 'menu']]);

        // Edges
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id,   'to_node_id' => $message->id, 'condition_type' => 'always',      'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $message->id, 'to_node_id' => $menu->id,    'condition_type' => 'always',      'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $menu->id,    'to_node_id' => $optA->id,    'condition_type' => 'user_choice',  'condition_value' => ['choice' => '1'], 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $menu->id,    'to_node_id' => $optB->id,    'condition_type' => 'user_choice',  'condition_value' => ['choice' => '2'], 'priority' => 2]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $menu->id,    'to_node_id' => $fallback->id,'condition_type' => 'always',       'priority' => 99]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $optA->id,    'to_node_id' => $end->id,     'condition_type' => 'always',       'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $optB->id,    'to_node_id' => $end->id,     'condition_type' => 'always',       'priority' => 1]);

        return compact('flow', 'start', 'message', 'menu', 'optA', 'optB', 'end', 'fallback');
    }

    /** @return \App\Models\WhatsappSession */
    private function makeSession(): object
    {
        return \App\Models\WhatsappSession::create([
            'chat_id'      => 'test_' . uniqid(),
            'flow_context' => [],
        ]);
    }

    // -------------------------------------------------------------------------
    // Tests
    // -------------------------------------------------------------------------

    public function test_start_flow_renders_intro_and_menu(): void
    {
        $this->buildSimpleFlow();
        $session = $this->makeSession();

        $messages = $this->engine->startFlow($session, 'test_flow');

        // Expect greeting text AND menu prompt in the messages
        $this->assertContains('Hello!', $messages);
        $this->assertCount(2, $messages); // greeting + menu
        $this->assertStringContainsString('Choose:', $messages[1]);
        $this->assertStringContainsString('1. Option A', $messages[1]);
        $this->assertStringContainsString('2. Option B', $messages[1]);

        // Session should be updated
        $session->refresh();
        $this->assertNotNull($session->current_flow_id);
        $this->assertNotNull($session->current_node_id);
        $menuNode = FlowNode::where('code', 'menu')->first();
        $this->assertEquals($menuNode->id, $session->current_node_id);
    }

    public function test_handle_user_choice_routes_to_correct_node(): void
    {
        $nodes   = $this->buildSimpleFlow();
        $session = $this->makeSession();

        // Start flow (lands on MENU)
        $this->engine->startFlow($session, 'test_flow');

        // User picks option 1
        $messages = $this->engine->handle($session, '1');

        $this->assertContains('You chose A', $messages);
    }

    public function test_handle_user_choice_2_routes_to_option_b(): void
    {
        $this->buildSimpleFlow();
        $session = $this->makeSession();
        $this->engine->startFlow($session, 'test_flow');

        $messages = $this->engine->handle($session, '2');

        $this->assertContains('You chose B', $messages);
    }

    public function test_invalid_choice_triggers_fallback_then_menu_again(): void
    {
        $this->buildSimpleFlow();
        $session = $this->makeSession();
        $this->engine->startFlow($session, 'test_flow');

        $messages = $this->engine->handle($session, 'invalid');

        // Fallback message + menu re-render
        $this->assertContains('Invalid choice', $messages);
        $menuMessages = array_filter($messages, fn($m) => str_contains($m, 'Choose:'));
        $this->assertNotEmpty($menuMessages);
    }

    public function test_start_flow_returns_empty_for_unknown_code(): void
    {
        $session = $this->makeSession();
        $messages = $this->engine->startFlow($session, 'nonexistent_flow');
        $this->assertEmpty($messages);
    }

    public function test_schedule_condition_within_schedule_true(): void
    {
        $this->officeHoursMock->shouldReceive('isOpen')->with('cs_general')->andReturn(true);

        $flow = ConversationFlow::create(['code' => 'sched_flow', 'name' => 'S', 'status' => 'published']);
        $start  = FlowNode::create(['flow_id' => $flow->id, 'code' => 'start', 'type' => 'START']);
        $open   = FlowNode::create(['flow_id' => $flow->id, 'code' => 'open',  'type' => 'MESSAGE', 'content' => ['text' => 'We are open']]);
        $closed = FlowNode::create(['flow_id' => $flow->id, 'code' => 'closed','type' => 'MESSAGE', 'content' => ['text' => 'We are closed']]);
        $end    = FlowNode::create(['flow_id' => $flow->id, 'code' => 'end',   'type' => 'END']);

        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id, 'to_node_id' => $open->id,   'condition_type' => 'within_schedule',  'condition_value' => ['service' => 'cs_general'], 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id, 'to_node_id' => $closed->id, 'condition_type' => 'outside_schedule', 'condition_value' => ['service' => 'cs_general'], 'priority' => 2]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $open->id,  'to_node_id' => $end->id,    'condition_type' => 'always',  'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $closed->id,'to_node_id' => $end->id,    'condition_type' => 'always',  'priority' => 1]);

        $session  = $this->makeSession();
        $messages = $this->engine->startFlow($session, 'sched_flow');

        $this->assertContains('We are open', $messages);
        $this->assertNotContains('We are closed', $messages);
    }

    public function test_schedule_condition_outside_schedule_closed(): void
    {
        $this->officeHoursMock->shouldReceive('isOpen')->with('cs_general')->andReturn(false);

        $flow = ConversationFlow::create(['code' => 'sched_flow2', 'name' => 'S2', 'status' => 'published']);
        $start  = FlowNode::create(['flow_id' => $flow->id, 'code' => 'start', 'type' => 'START']);
        $open   = FlowNode::create(['flow_id' => $flow->id, 'code' => 'open',  'type' => 'MESSAGE', 'content' => ['text' => 'We are open']]);
        $closed = FlowNode::create(['flow_id' => $flow->id, 'code' => 'closed','type' => 'MESSAGE', 'content' => ['text' => 'We are closed']]);
        $end    = FlowNode::create(['flow_id' => $flow->id, 'code' => 'end',   'type' => 'END']);

        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id, 'to_node_id' => $open->id,   'condition_type' => 'within_schedule',  'condition_value' => ['service' => 'cs_general'], 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id, 'to_node_id' => $closed->id, 'condition_type' => 'outside_schedule', 'condition_value' => ['service' => 'cs_general'], 'priority' => 2]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $open->id,  'to_node_id' => $end->id,    'condition_type' => 'always',  'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $closed->id,'to_node_id' => $end->id,    'condition_type' => 'always',  'priority' => 1]);

        $session  = $this->makeSession();
        $messages = $this->engine->startFlow($session, 'sched_flow2');

        $this->assertContains('We are closed', $messages);
        $this->assertNotContains('We are open', $messages);
    }

    public function test_input_node_stores_context_and_advances(): void
    {
        $flow    = ConversationFlow::create(['code' => 'input_flow', 'name' => 'I', 'status' => 'published']);
        $start   = FlowNode::create(['flow_id' => $flow->id, 'code' => 'start',   'type' => 'START']);
        $input   = FlowNode::create(['flow_id' => $flow->id, 'code' => 'ask',     'type' => 'INPUT',   'content' => ['prompt' => 'Type something:', 'save_to_context_key' => 'answer']]);
        $confirm = FlowNode::create(['flow_id' => $flow->id, 'code' => 'confirm', 'type' => 'MESSAGE', 'content' => ['text' => 'Got it: {answer}']]);
        $end     = FlowNode::create(['flow_id' => $flow->id, 'code' => 'end',     'type' => 'END']);

        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $start->id,   'to_node_id' => $input->id,   'condition_type' => 'always', 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $input->id,   'to_node_id' => $confirm->id, 'condition_type' => 'always', 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flow->id, 'from_node_id' => $confirm->id, 'to_node_id' => $end->id,     'condition_type' => 'always', 'priority' => 1]);

        $session = $this->makeSession();
        $this->engine->startFlow($session, 'input_flow'); // Lands on INPUT prompt

        // User types their answer
        $messages = $this->engine->handle($session, 'Hello World');

        $this->assertContains('Got it: Hello World', $messages);
    }

    public function test_switch_flow_transitions_to_target_flow(): void
    {
        // Build flow A that has a SWITCH_FLOW node pointing to flow B
        $flowA = ConversationFlow::create(['code' => 'flow_a', 'name' => 'A', 'status' => 'published']);
        $flowB = ConversationFlow::create(['code' => 'flow_b', 'name' => 'B', 'status' => 'published']);

        $startA  = FlowNode::create(['flow_id' => $flowA->id, 'code' => 'start',  'type' => 'START']);
        $menu    = FlowNode::create(['flow_id' => $flowA->id, 'code' => 'menu',   'type' => 'MENU',        'content' => ['prompt' => 'Pick:', 'options' => [['key' => '1', 'label' => 'Go to B']]]]);
        $sw      = FlowNode::create(['flow_id' => $flowA->id, 'code' => 'switch', 'type' => 'SWITCH_FLOW', 'content' => ['to_flow_code' => 'flow_b']]);

        $startB  = FlowNode::create(['flow_id' => $flowB->id, 'code' => 'start',  'type' => 'START']);
        $helloB  = FlowNode::create(['flow_id' => $flowB->id, 'code' => 'hello',  'type' => 'MESSAGE',    'content' => ['text' => 'Welcome to Flow B']]);
        $endB    = FlowNode::create(['flow_id' => $flowB->id, 'code' => 'end',    'type' => 'END']);

        FlowEdge::create(['flow_id' => $flowA->id, 'from_node_id' => $startA->id, 'to_node_id' => $menu->id, 'condition_type' => 'always', 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flowA->id, 'from_node_id' => $menu->id,   'to_node_id' => $sw->id,   'condition_type' => 'user_choice', 'condition_value' => ['choice' => '1'], 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flowB->id, 'from_node_id' => $startB->id, 'to_node_id' => $helloB->id, 'condition_type' => 'always', 'priority' => 1]);
        FlowEdge::create(['flow_id' => $flowB->id, 'from_node_id' => $helloB->id, 'to_node_id' => $endB->id,  'condition_type' => 'always', 'priority' => 1]);

        $session  = $this->makeSession();
        $this->engine->startFlow($session, 'flow_a');

        // User picks '1' → triggers SWITCH_FLOW to flow_b
        $messages = $this->engine->handle($session, '1');

        $this->assertContains('Welcome to Flow B', $messages);

        // Session should now belong to flow B
        $session->refresh();
        $this->assertEquals($flowB->id, $session->current_flow_id);
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
