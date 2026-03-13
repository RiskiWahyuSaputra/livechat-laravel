<?php

namespace App\Services;

use App\Models\ConversationFlow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use Illuminate\Database\Eloquent\Model;

/**
 * FlowEngine processes user messages against a published conversation flow.
 *
 * It works with any Eloquent model that has:
 *   current_flow_id (int|null)
 *   current_node_id (int|null)
 *   flow_context    (array|null)
 *
 * and supports ->update([...]).
 *
 * Returns an array of plain-text bot message strings; the caller is
 * responsible for persisting Message records, broadcasting, etc.
 */
class FlowEngine
{
    public function __construct(
        protected OfficeHoursService $officeHours
    ) {}

    // -------------------------------------------------------------------------
    // Public API
    // -------------------------------------------------------------------------

    /**
     * Start a specific flow on the given session, replacing any existing state.
     *
     * @return string[]  Bot messages to send.
     */
    public function startFlow(Model $session, string $flowCode): array
    {
        $flow = ConversationFlow::findPublished($flowCode);

        if (! $flow) {
            return [];
        }

        $startNode = FlowNode::where('flow_id', $flow->id)
            ->where('type', 'START')
            ->first();

        if (! $startNode) {
            return [];
        }

        $session->update([
            'current_flow_id' => $flow->id,
            'current_node_id' => $startNode->id,
            'flow_context'    => [],
        ]);

        return $this->renderAndAdvance($session, $startNode, []);
    }

    /**
     * Process an incoming user message for the session's current flow position.
     *
     * If the session has no active flow, starts the default flow.
     *
     * @return string[]  Bot messages to send.
     */
    public function handle(Model $session, string $userMessage): array
    {
        if (! $session->current_flow_id || ! $session->current_node_id) {
            return $this->startFlow($session, 'choose_customer_service');
        }

        $currentNode = FlowNode::find($session->current_node_id);

        if (! $currentNode) {
            return $this->startFlow($session, 'choose_customer_service');
        }

        // Persist user input when the awaiting node is INPUT
        $context = $session->flow_context ?? [];
        if ($currentNode->type === 'INPUT') {
            $saveKey          = $currentNode->content['save_to_context_key'] ?? 'user_input';
            $context[$saveKey] = $userMessage;
            $session->update(['flow_context' => $context]);
        }

        // Resolve next node via edge conditions
        $nextNode = $this->resolveNextNode($currentNode, $userMessage, $session);

        if (! $nextNode) {
            // Fall back to a FALLBACK node in the current flow if present
            $fallback = FlowNode::where('flow_id', $currentNode->flow_id)
                ->where('type', 'FALLBACK')
                ->first();

            if ($fallback && $fallback->id !== $currentNode->id) {
                $session->update(['current_node_id' => $fallback->id]);
                return $this->renderAndAdvance($session, $fallback, []);
            }

            return [];
        }

        $session->update(['current_node_id' => $nextNode->id]);

        return $this->renderAndAdvance($session, $nextNode, []);
    }

    // -------------------------------------------------------------------------
    // Internal helpers
    // -------------------------------------------------------------------------

    /**
     * Render a node and, for nodes that auto-advance (MESSAGE, START, SWITCH_FLOW),
     * keep advancing until a node that waits for input (MENU, INPUT, END).
     *
     * @param  string[]  $messages  Accumulated messages (recursive).
     * @return string[]
     */
    private function renderAndAdvance(Model $session, FlowNode $node, array $messages): array
    {
        // Guard against infinite loops (e.g. cycles in the graph)
        static $depth = 0;
        $depth++;
        if ($depth > 30) {
            $depth = 0;
            return $messages;
        }

        switch ($node->type) {
            case 'START':
                $next = $this->resolveNextNode($node, null, $session);
                if ($next) {
                    $session->update(['current_node_id' => $next->id]);
                    $messages = $this->renderAndAdvance($session, $next, $messages);
                }
                break;

            case 'MESSAGE':
                $text       = $this->interpolate($node->content['text'] ?? '', $session);
                $messages[] = $text;
                $next       = $this->resolveNextNode($node, null, $session);
                if ($next) {
                    $session->update(['current_node_id' => $next->id]);
                    $messages = $this->renderAndAdvance($session, $next, $messages);
                }
                break;

            case 'MENU':
                $prompt   = $node->content['prompt'] ?? '';
                $options  = $node->content['options'] ?? [];
                $text     = $prompt . "\n\n";
                foreach ($options as $opt) {
                    $text .= "{$opt['key']}. {$opt['label']}\n";
                }
                $messages[] = rtrim($text);
                // Stop – wait for user choice
                break;

            case 'INPUT':
                $prompt     = $this->interpolate($node->content['prompt'] ?? '', $session);
                $messages[] = $prompt;
                // Stop – wait for user input
                break;

            case 'SWITCH_FLOW':
                $targetCode = $node->content['to_flow_code'] ?? null;
                if ($targetCode) {
                    $switchMessages = $this->startFlow($session, $targetCode);
                    $messages       = array_merge($messages, $switchMessages);
                }
                break;

            case 'FALLBACK':
                $text       = $this->interpolate($node->content['text'] ?? 'Maaf, saya tidak mengerti pilihan Anda.', $session);
                $messages[] = $text;
                $goToCode   = $node->content['go_to_node_code'] ?? null;
                if ($goToCode) {
                    $target = FlowNode::where('flow_id', $node->flow_id)
                        ->where('code', $goToCode)
                        ->first();
                    if ($target && $target->id !== $node->id) {
                        $session->update(['current_node_id' => $target->id]);
                        $messages = $this->renderAndAdvance($session, $target, $messages);
                    }
                }
                break;

            case 'END':
                // Nothing to render; the flow is finished
                break;
        }

        $depth = max(0, $depth - 1);

        return $messages;
    }

    /**
     * Find the first edge from $node whose condition evaluates to true.
     */
    private function resolveNextNode(FlowNode $node, ?string $userMessage, Model $session): ?FlowNode
    {
        $edges = FlowEdge::where('from_node_id', $node->id)
            ->orderBy('priority')
            ->get();

        foreach ($edges as $edge) {
            if ($this->evaluateCondition($edge, $userMessage, $session)) {
                return FlowNode::find($edge->to_node_id);
            }
        }

        return null;
    }

    /**
     * Evaluate a single edge condition.
     */
    private function evaluateCondition(FlowEdge $edge, ?string $userMessage, Model $session): bool
    {
        $value = $edge->condition_value ?? [];

        return match ($edge->condition_type) {
            'always'           => true,
            'user_choice'      => strtolower(trim($userMessage ?? '')) === strtolower((string) ($value['choice'] ?? '')),
            'within_schedule'  => $this->officeHours->isOpen($value['service'] ?? 'cs_general'),
            'outside_schedule' => ! $this->officeHours->isOpen($value['service'] ?? 'cs_general'),
            default            => false,
        };
    }

    /**
     * Replace {context_key} placeholders with their values from session context.
     */
    private function interpolate(string $text, Model $session): string
    {
        $context = $session->flow_context ?? [];
        foreach ($context as $key => $val) {
            $text = str_replace('{' . $key . '}', (string) $val, $text);
        }

        return $text;
    }
}
