<?php

namespace App\Http\Controllers\Admin;

use App\Events\AdminMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\AdminConversation;
use App\Models\AdminMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AgentChatController extends Controller
{
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $search = trim((string) $request->get('search', ''));

        // Get all other admins
        $otherAdmins = Admin::where('id', '!=', $admin->id)->get();

        // Get conversations where the current admin is either admin_1 or admin_2
        $query = AdminConversation::with(['admin1', 'admin2', 'messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->where(function($q) use ($admin) {
                $q->where('admin_1_id', $admin->id)
                  ->orWhere('admin_2_id', $admin->id);
            });

        if ($search !== '') {
            $query->where(function($q) use ($search) {
                $q->whereHas('admin1', function($q1) use ($search) {
                    $q1->where('username', 'like', "%{$search}%");
                })->orWhereHas('admin2', function($q2) use ($search) {
                    $q2->where('username', 'like', "%{$search}%");
                });
            });
        }

        $conversations = $query->orderBy('last_message_at', 'desc')
            ->get();

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'conversations' => $conversations,
                'other_admins' => $otherAdmins,
            ]);
        }

        return view('admin.agent_chat', compact('admin', 'conversations', 'otherAdmins'));
    }

    public function showConversation($id)
    {
        $conversation = AdminConversation::with(['admin1', 'admin2'])->findOrFail($id);
        $admin = Auth::guard('admin')->user();
        
        // Ensure the admin is part of the conversation
        if ($conversation->admin_1_id != $admin->id && $conversation->admin_2_id != $admin->id) {
            abort(403);
        }

        $messages = $conversation->messages()->with('sender')->orderBy('created_at', 'asc')->get();

        // If AJAX request, return JSON
        if (request()->ajax() || request()->get('ajax')) {
            return response()->json(['messages' => $messages]);
        }

        return view('admin.agent_conversation', compact('conversation', 'messages', 'admin'));
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id',
        ]);

        $admin = Auth::guard('admin')->user();
        $targetAdminId = $request->admin_id;

        if ($admin->id == $targetAdminId) {
            return response()->json(['error' => 'Cannot chat with yourself.'], 422);
        }

        // Always store admin_1_id < admin_2_id to avoid duplicate rows for the same pair
        $id1 = min($admin->id, $targetAdminId);
        $id2 = max($admin->id, $targetAdminId);

        $conversation = AdminConversation::firstOrCreate([
            'admin_1_id' => $id1,
            'admin_2_id' => $id2,
        ]);

        return response()->json([
            'success' => true,
            'conversation' => $conversation
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => 'required|exists:admin_conversations,id',
            'content' => 'required|string|max:2000',
        ]);

        $admin = Auth::guard('admin')->user();
        $conversation = AdminConversation::findOrFail($request->conversation_id);

        if ($conversation->admin_1_id != $admin->id && $conversation->admin_2_id != $admin->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $message = AdminMessage::create([
            'admin_conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'content' => $request->content,
            'message_type' => 'text',
        ]);

        $conversation->update(['last_message_at' => now()]);

        // Load sender relation
        $message->load('sender');

        try {
            broadcast(new AdminMessageSent($message));
        } catch (\Exception $e) {
            \Log::error('Admin broadcast failed: ' . $e->getMessage());
        }

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
