<?php

namespace App\Http\Controllers\Admin;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard admin — tampilkan semua antrian dan chat aktif.
     */
    public function index()
    {
        $admin = Auth::guard('admin')->user();

        $pendingConversations = Conversation::with('user')
            ->whereIn('status', ['pending', 'queued'])
            ->orderBy('queue_position')
            ->orderBy('last_message_at')
            ->get();

        $activeConversations = Conversation::with(['user', 'admin', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->where('status', 'active')
            ->get();

        // Ambil daftar admin lain yang tidak offline untuk pilihan Handover
        $otherAdmins = \App\Models\Admin::where('id', '!=', $admin->id)
            ->where('status', '!=', 'offline')
            ->get();

        return view('admin.dashboard', compact('admin', 'pendingConversations', 'activeConversations', 'otherAdmins'));
    }

    /**
     * Tampilkan isi conversation tertentu (panel kanan dashboard).
     */
    public function showConversation(Conversation $conversation)
    {
        $admin    = Auth::guard('admin')->user();
        $messages = $conversation->messages()->get();

        return view('admin.conversation', compact('conversation', 'messages', 'admin'));
    }

    /**
     * Klaim chat dari antrian — dengan Optimistic Locking untuk anti double-claim.
     */
    public function claimConversation(Request $request, Conversation $conversation)
    {
        $admin = Auth::guard('admin')->user();

        // Cek kapasitas admin
        if (!$admin->canTakeNewChat()) {
            return response()->json(['error' => 'Anda sudah mencapai batas maksimum chat aktif.'], 422);
        }

        // Optimistic Locking: hanya update jika status masih pending/queued dan belum diklaim
        $updated = Conversation::where('id', $conversation->id)
            ->whereIn('status', ['pending', 'queued'])
            ->whereNull('admin_id')
            ->update([
                'admin_id'       => $admin->id,
                'status'         => 'active',
                'queue_position' => null,
            ]);

        if (!$updated) {
            return response()->json([
                'error' => 'Chat ini sudah diambil oleh admin lain.',
            ], 409); // 409 Conflict
        }

        $conversation->refresh();

        // Kirim pesan sistem ke user
        $sysMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => "Chat Anda sedang diproses oleh {$admin->username}. Silakan mulai berbicara.",
        ]);

        broadcast(new MessageSent($sysMessage));
        broadcast(new ConversationStatusChanged($conversation, $admin->username));

        // Update posisi antrian untuk conversation lain yang masih queued
        $this->reorderQueue();

        return response()->json(['success' => true, 'conversation_id' => $conversation->id]);
    }

    /**
     * Kirim pesan dari admin (termasuk whisper).
     */
    public function sendMessage(Request $request)
    {
        $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'content'         => ['required', 'string', 'max:2000'],
            'message_type'    => ['required', 'in:text,whisper'],
        ]);

        $admin        = Auth::guard('admin')->user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Pastikan admin ini yang menangani conversation ini (kecuali whisper bisa semua admin)
        if ($request->message_type !== 'whisper' && $conversation->admin_id !== $admin->id) {
            return response()->json(['error' => 'Anda tidak memiliki akses tulis ke chat ini.'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'sender_type'     => 'admin',
            'message_type'    => $request->message_type,
            'content'         => $request->content,
        ]);

        $conversation->update(['last_message_at' => now()]);

        broadcast(new MessageSent($message))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Handover (oper) chat ke admin lain.
     */
    public function handoverConversation(Request $request, Conversation $conversation)
    {
        $request->validate([
            'to_admin_id' => ['required', 'exists:admins,id'],
        ]);

        $fromAdmin = Auth::guard('admin')->user();
        $toAdmin   = \App\Models\Admin::findOrFail($request->to_admin_id);

        if ($conversation->admin_id !== $fromAdmin->id) {
            return response()->json(['error' => 'Anda bukan penanganan chat ini.'], 403);
        }

        $conversation->update(['admin_id' => $toAdmin->id]);

        // Pesan sistem tentang handover
        $sysMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => "Chat diteruskan dari {$fromAdmin->username} ke {$toAdmin->username}.",
        ]);

        broadcast(new MessageSent($sysMessage));
        broadcast(new ConversationStatusChanged($conversation, $fromAdmin->username));

        return response()->json(['success' => true]);
    }

    /**
     * Tutup conversation + isi kategori masalah.
     */
    public function closeConversation(Request $request, Conversation $conversation)
    {
        $request->validate([
            'problem_category' => ['nullable', 'string', 'max:100'],
        ]);

        $admin = Auth::guard('admin')->user();

        $conversation->update([
            'status'           => 'closed',
            'problem_category' => $request->problem_category,
        ]);

        $sysMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Chat telah ditutup. Terima kasih telah menghubungi kami!',
        ]);

        broadcast(new MessageSent($sysMessage));
        broadcast(new ConversationStatusChanged($conversation, $admin->username));

        return response()->json(['success' => true]);
    }

    /**
     * Block user spam — tutup chat dan tandai is_blocked = true.
     */
    public function blockUser(Request $request, Conversation $conversation)
    {
        $admin = Auth::guard('admin')->user();

        $conversation->user->update(['is_blocked' => true]);

        $conversation->update(['status' => 'closed']);

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Akun Anda telah diblokir oleh administrator.',
        ]);

        broadcast(new ConversationStatusChanged($conversation, $admin->username));

        return response()->json(['success' => true]);
    }

    /**
     * Update status admin (online/busy/offline).
     */
    public function updateStatus(Request $request)
    {
        $request->validate([
            'status' => ['required', 'in:online,busy,offline'],
        ]);

        Auth::guard('admin')->user()->update(['status' => $request->status]);

        return response()->json(['success' => true]);
    }

    /**
     * Typing indicator dari admin.
     */
    public function typing(Request $request)
    {
        $request->validate([
            'conversation_id' => ['required', 'exists:conversations,id'],
            'is_typing'       => ['required', 'boolean'],
        ]);

        $admin = Auth::guard('admin')->user();

        broadcast(new TypingIndicator(
            conversationId: $request->conversation_id,
            senderId:       $admin->id,
            senderType:     'admin',
            senderName:     $admin->username,
            isTyping:       $request->boolean('is_typing')
        ))->toOthers();

        return response()->json(['success' => true]);
    }

    /**
     * Urutkan ulang posisi antrian setelah ada yang diklaim/ditutup.
     */
    private function reorderQueue(): void
    {
        $queued = Conversation::where('status', 'queued')
            ->orderBy('created_at')
            ->get();

        foreach ($queued as $i => $conv) {
            $conv->update(['queue_position' => $i + 1]);
        }
    }
}
