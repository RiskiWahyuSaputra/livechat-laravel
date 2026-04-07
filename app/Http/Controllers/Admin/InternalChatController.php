<?php

namespace App\Http\Controllers\Admin;

use App\Events\InternalMessageSent;
use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\InternalConversation;
use App\Models\InternalMessage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class InternalChatController extends Controller
{
    public function index()
    {
        $admin = Auth::guard('admin')->user();
        
        // Get all admins except self
        $admins = Admin::where('id', '!=', $admin->id)
            ->where('status', '!=', 'offline')
            ->get();

        // Get recent conversations
        $conversations = InternalConversation::where('user_one_id', $admin->id)
            ->orWhere('user_two_id', $admin->id)
            ->with(['userOne', 'userTwo', 'messages' => function($q) {
                $q->latest()->limit(1);
            }])
            ->orderBy('last_message_at', 'desc')
            ->get();

        return view('admin.internal_chat', compact('admin', 'admins', 'conversations'));
    }

    public function show($id)
    {
        $admin = Auth::guard('admin')->user();
        $conversation = InternalConversation::findOrFail($id);

        if ($conversation->user_one_id != $admin->id && $conversation->user_two_id != $admin->id) {
            abort(403);
        }

        return response()->json([
            'conversation' => $conversation,
            'other_user' => $conversation->otherUser($admin->id)
        ]);
    }

    public function showConversation($id)
    {
        $admin = Auth::guard('admin')->user();
        $conversation = InternalConversation::with(['userOne', 'userTwo', 'messages'])->findOrFail($id);

        if ($conversation->user_one_id != $admin->id && $conversation->user_two_id != $admin->id) {
            abort(403);
        }

        $messages = $conversation->messages;
        $otherUser = $conversation->otherUser($admin->id);

        return view('admin.internal_conversation', compact('conversation', 'messages', 'admin', 'otherUser'));
    }

    public function startConversation(Request $request)
    {
        $request->validate([
            'admin_id' => 'required|exists:admins,id'
        ]);

        $admin = Auth::guard('admin')->user();
        $targetAdminId = $request->admin_id;

        if ($admin->id == $targetAdminId) {
            return response()->json(['error' => 'Cannot start conversation with yourself'], 422);
        }

        $conversation = InternalConversation::getOrCreate($admin->id, $targetAdminId);

        return response()->json([
            'conversation_id' => $conversation->id
        ]);
    }

    public function sendMessage(Request $request)
    {
        $request->validate([
            'internal_conversation_id' => 'required|exists:internal_conversations,id',
            'content' => 'required|string|max:2000'
        ]);

        $admin = Auth::guard('admin')->user();
        $conversation = InternalConversation::findOrFail($request->internal_conversation_id);

        if ($conversation->user_one_id != $admin->id && $conversation->user_two_id != $admin->id) {
            abort(403);
        }

        $message = InternalMessage::create([
            'internal_conversation_id' => $conversation->id,
            'sender_id' => $admin->id,
            'content' => $request->content,
            'message_type' => 'text'
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new InternalMessageSent($message))->toOthers();

        return response()->json([
            'success' => true,
            'message' => $message
        ]);
    }
}
