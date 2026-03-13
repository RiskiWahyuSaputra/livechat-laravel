<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\ConversationFlow;
use App\Models\FlowEdge;
use App\Models\FlowNode;
use App\Models\HolidayDate;
use Illuminate\Http\Request;

class FlowController extends Controller
{
    // -------------------------------------------------------------------------
    // Flows
    // -------------------------------------------------------------------------

    public function index()
    {
        $flows = ConversationFlow::withCount(['nodes', 'edges'])->orderBy('id')->get();

        return view('admin.flows.index', compact('flows'));
    }

    public function create()
    {
        return view('admin.flows.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code'        => 'required|string|max:100|unique:conversation_flows,code|regex:/^[a-z0-9_]+$/',
            'name'        => 'required|string|max:200',
            'description' => 'nullable|string|max:1000',
        ]);

        ConversationFlow::create($data);

        return redirect()->route('admin.flows.index')
            ->with('success', 'Flow berhasil dibuat.');
    }

    public function show(ConversationFlow $flow)
    {
        $flow->load(['nodes', 'edges']);

        $graph = [
            'flow'  => $flow->only(['id', 'code', 'name', 'description', 'status']),
            'nodes' => $flow->nodes->map(fn($n) => [
                'id'       => $n->id,
                'code'     => $n->code,
                'type'     => $n->type,
                'content'  => $n->content,
                'position' => $n->position,
            ]),
            'edges' => $flow->edges->map(fn($e) => [
                'id'              => $e->id,
                'from_node_id'    => $e->from_node_id,
                'to_node_id'      => $e->to_node_id,
                'condition_type'  => $e->condition_type,
                'condition_value' => $e->condition_value,
                'priority'        => $e->priority,
            ]),
        ];

        return view('admin.flows.show', compact('flow', 'graph'));
    }

    public function update(Request $request, ConversationFlow $flow)
    {
        $data = $request->validate([
            'name'        => 'sometimes|required|string|max:200',
            'description' => 'nullable|string|max:1000',
            'graph'       => 'nullable|json',
        ]);

        if (isset($data['name'])) {
            $flow->update(['name' => $data['name'], 'description' => $data['description'] ?? $flow->description]);
        }

        // Import full graph JSON if provided
        if (! empty($data['graph'])) {
            $this->importGraph($flow, json_decode($data['graph'], true));
        }

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'message' => 'Flow diperbarui.']);
        }

        return redirect()->route('admin.flows.show', $flow)
            ->with('success', 'Flow diperbarui.');
    }

    public function destroy(ConversationFlow $flow)
    {
        $flow->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return redirect()->route('admin.flows.index')
            ->with('success', 'Flow dihapus.');
    }

    public function publish(Request $request, ConversationFlow $flow)
    {
        $status = $request->input('status', 'published');
        $flow->update(['status' => in_array($status, ['draft', 'published']) ? $status : 'published']);

        if ($request->expectsJson()) {
            return response()->json(['success' => true, 'status' => $flow->status]);
        }

        return back()->with('success', "Status flow diubah ke «{$flow->status}».");
    }

    // -------------------------------------------------------------------------
    // Graph import helper
    // -------------------------------------------------------------------------

    private function importGraph(ConversationFlow $flow, array $graph): void
    {
        $nodeMap = [];

        // Upsert nodes
        foreach ($graph['nodes'] ?? [] as $nodeData) {
            $node = FlowNode::updateOrCreate(
                ['flow_id' => $flow->id, 'code' => $nodeData['code']],
                [
                    'type'     => $nodeData['type'],
                    'content'  => $nodeData['content'] ?? null,
                    'position' => $nodeData['position'] ?? null,
                ]
            );
            $nodeMap[$nodeData['code']] = $node->id;
        }

        // Replace edges
        FlowEdge::where('flow_id', $flow->id)->delete();
        foreach ($graph['edges'] ?? [] as $e) {
            $fromId = is_numeric($e['from_node_id'])
                ? $e['from_node_id']
                : ($nodeMap[$e['from_node_code'] ?? ''] ?? null);
            $toId = is_numeric($e['to_node_id'])
                ? $e['to_node_id']
                : ($nodeMap[$e['to_node_code'] ?? ''] ?? null);

            if (! $fromId || ! $toId) {
                continue;
            }

            FlowEdge::create([
                'flow_id'         => $flow->id,
                'from_node_id'    => $fromId,
                'to_node_id'      => $toId,
                'condition_type'  => $e['condition_type'] ?? 'always',
                'condition_value' => $e['condition_value'] ?? null,
                'priority'        => $e['priority'] ?? 10,
            ]);
        }
    }

    // -------------------------------------------------------------------------
    // Holidays
    // -------------------------------------------------------------------------

    public function holidays()
    {
        $holidays = HolidayDate::orderBy('date')->get();

        return view('admin.flows.holidays', compact('holidays'));
    }

    public function storeHoliday(Request $request)
    {
        $data = $request->validate([
            'date' => 'required|date|unique:holiday_dates,date',
            'name' => 'nullable|string|max:200',
        ]);

        HolidayDate::create($data);

        if ($request->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Tanggal libur ditambahkan.');
    }

    public function destroyHoliday(HolidayDate $holiday)
    {
        $holiday->delete();

        if (request()->expectsJson()) {
            return response()->json(['success' => true]);
        }

        return back()->with('success', 'Tanggal libur dihapus.');
    }
}
