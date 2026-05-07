<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\QuickReply;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class QuickReplyController extends Controller
{
    public function index()
    {
        $replies = QuickReply::orderBy('created_at', 'desc')->paginate(10);
        return view('admin.quick-replies.index', compact('replies'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'command' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('quick_replies', 'command'),
            ],
            'content' => 'required|string',
        ]);

        QuickReply::create($request->all());

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil ditambahkan.');
    }

    public function update(Request $request, QuickReply $quickReply)
    {
        $request->validate([
            'title' => 'nullable|string|max:255',
            'command' => [
                'required',
                'string',
                'max:50',
                'regex:/^[a-z0-9_]+$/',
                Rule::unique('quick_replies', 'command')->ignore($quickReply->id),
            ],
            'content' => 'required|string',
        ]);

        $quickReply->update($request->all());

        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil diperbarui.');
    }

    public function destroy(QuickReply $quickReply)
    {
        $quickReply->delete();
        return redirect()->route('admin.quick-replies.index')->with('success', 'Balasan cepat berhasil dihapus.');
    }

    public function list()
    {
        $replies = QuickReply::select('id', 'command', 'content')
            ->orderBy('command', 'asc')
            ->get();
        return response()->json($replies);
    }
}
