<?php

namespace App\Http\Controllers\Admin;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Customer;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    /**
     * Dashboard Utama — Statistik dan daftar pelanggan.
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        
        // Statistik Ringkas
        $stats = [
            'total_users' => User::count(),
            'online_users' => User::where('is_online', true)->count(),
            'today_users' => User::whereDate('created_at', now()->today())->count(),
            'yesterday_users' => User::whereDate('created_at', now()->yesterday())->count(),
        ];

        // Data Grafik (7 hari terakhir)
        $chartData = [];
        $chartLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = now()->subDays($i);
            $chartLabels[] = $date->format('d M');
            $chartData[] = User::whereDate('created_at', $date->format('Y-m-d'))->count();
        }
        $stats['chart_data'] = $chartData;
        $stats['chart_labels'] = $chartLabels;

        // Filter Pelanggan
        $query = User::query();

        if ($request->has('filter')) {
            switch ($request->filter) {
                case 'online':
                    // Custom logic untuk online (berdasar chat aktif saja jika ada)
                    break;
                case 'today':
                    $query->whereDate('created_at', now()->today());
                    break;
                case 'yesterday':
                    $query->whereDate('created_at', now()->yesterday());
                    break;
            }
        }

        if ($request->has('search')) {
            $query->where(function($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('contact', 'like', '%' . $request->search . '%')
                  ->orWhere('origin', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->latest()->paginate(10)->withQueryString();

        return view('admin.dashboard', compact('admin', 'stats', 'customers'));
    }

    /**
     * Hapus user secara permanen.
     */
    public function destroyUser(User $user)
    {
        // Hapus semua percakapan dan pesan terkait jika ada (opsional, tergantung cascade di DB)
        // Jika tidak cascade, hapus manual:
        foreach ($user->conversations as $conv) {
            $conv->messages()->delete();
            $conv->delete();
        }
        
        $user->delete();

        return back()->with('success', 'User berhasil dihapus secara permanen.');
    }

    /**
     * Workspace Chat — tampilkan semua antrian dan chat aktif.
     */
    public function chatWorkspace(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        $pendingConversations = Conversation::with('customer')
            ->whereIn('status', ['pending', 'queued'])
            ->orderBy('queue_position')
            ->orderBy('last_message_at')
            ->get();

        $activeConversations = Conversation::with(['customer', 'admin', 'messages' => function ($q) {
                $q->latest()->limit(1);
            }])
            ->where('status', 'active')
            ->get();

        if ($request->ajax() || $request->has('ajax')) {
            return response()->json([
                'pending' => $pendingConversations,
                'active'  => $activeConversations,
            ]);
        }

        // Ambil daftar admin lain yang tidak offline untuk pilihan Handover
        $otherAdmins = \App\Models\Admin::where('id', '!=', $admin->id)
            ->where('status', '!=', 'offline')
            ->get();

        return view('admin.chat', compact('admin', 'pendingConversations', 'activeConversations', 'otherAdmins'));
    }

    /**
     * Tampilkan isi conversation tertentu (panel kanan dashboard).
     */
    public function showConversation(Conversation $conversation)
    {
        $admin    = Auth::guard('admin')->user();
        $messages = $conversation->messages()->get();
        $quickReplies = \App\Models\QuickReply::pluck('content')->toArray();

        return view('admin.conversation', compact('conversation', 'messages', 'admin', 'quickReplies'));
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
        
        // Penting: Broadcast agar sidebar admin lain dan dashboard user terupdate
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
            'conversation_id' => ['required'], // existence checked manually
            'content'         => ['required', 'string', 'max:2000'],
            'message_type'    => ['required', 'in:text,whisper'],
        ]);

        $admin        = Auth::guard('admin')->user();
        $conversation = Conversation::findOrFail($request->conversation_id);

        // Pastikan admin ini yang menangani conversation ini (kecuali whisper bisa semua admin)
        // Gunakan non-strict comparison agar aman
        if ($request->message_type !== 'whisper' && $conversation->admin_id != $admin->id) {
            return response()->json(['error' => 'Anda tidak memiliki akses tulis ke chat ini.'], 403);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'sender_type'     => 'admin',
            'message_type'    => $request->message_type,
            'content'         => $request->content,
        ]);

        \Log::info('Message created by admin', ['id' => $message->id]);

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new MessageSent($message));
            \Log::info('Admin Broadcast MessageSent success');
        } catch (\Exception $e) {
            \Log::error('Admin Broadcast MessageSent failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'         => $message->id,
                'content'    => $message->content,
                'created_at' => $message->created_at->format('H:i'),
            ],
        ]);
    }

    /**
     * Handover (oper) chat ke admin lain.
     */
    public function handoverConversation(Request $request, Conversation $conversation)
    {
        $request->validate([
            'to_admin_id' => ['required', 'exists:admins,id'],
            'internal_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $fromAdmin = Auth::guard('admin')->user();
        $toAdmin   = \App\Models\Admin::findOrFail($request->to_admin_id);

        if ($conversation->admin_id !== $fromAdmin->id) {
            return response()->json(['error' => 'Anda bukan penanganan chat ini.'], 403);
        }

        // Kirim whisper note jika ada
        if ($request->internal_note) {
            $note = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id'       => $fromAdmin->id,
                'sender_type'     => 'admin',
                'message_type'    => 'whisper',
                'content'         => "Catatan Handover: " . $request->internal_note,
            ]);
            broadcast(new MessageSent($note));
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

        $conversation->delete(); // Soft delete memindahkannya ke arsip

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

        $conversation->customer->update(['is_blocked' => true]);

        $conversation->update(['status' => 'closed']);
        $conversation->delete();

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
            senderRole:     $admin->role,
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
