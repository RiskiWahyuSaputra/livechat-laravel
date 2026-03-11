<?php

namespace App\Http\Controllers\Admin;

use App\Events\ConversationStatusChanged;
use App\Events\MessageSent;
use App\Events\TypingIndicator;
use App\Events\UserShouldBeLoggedOut;
use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Customer;
use App\Models\User;
use App\Services\AnalyticsService;
use App\Services\WhatsappService;
use App\Services\MessageSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class DashboardController extends Controller
{
    protected $analyticsService;
    protected $whatsappService;

    public function __construct(AnalyticsService $analyticsService, WhatsappService $whatsappService)
    {
        $this->analyticsService = $analyticsService;
        $this->whatsappService = $whatsappService;
    }

    /**
     * Dashboard Utama — Statistik dan daftar pelanggan.
     */
    public function index(Request $request)
    {
        $admin = Auth::guard('admin')->user();

        // Date range for analytics
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->startOfDay());
        $endDate = $request->get('end_date', Carbon::now()->endOfDay());

        // Get analytics data
        $overview = $this->analyticsService->getOverviewStats();
        $trends = $this->analyticsService->getConversationTrends();
        $peakHours = $this->analyticsService->getPeakHours();
        $topPerformers = $this->analyticsService->getTopPerformers();
        $complaintCategories = $this->analyticsService->getComplaintCategories();
        $customerSatisfaction = $this->analyticsService->getCustomerSatisfaction();
        $agentWorkload = $this->analyticsService->getAgentWorkload();
        $customerInsights = $this->analyticsService->getCustomerInsights();
        $metrics = $this->analyticsService->getConversationMetrics();
        $agentPerformance = $this->analyticsService->getAgentPerformance();
        $statusDistribution = $this->analyticsService->getStatusDistribution();

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
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', '%' . $request->search . '%')
                    ->orWhere('contact', 'like', '%' . $request->search . '%')
                    ->orWhere('origin', 'like', '%' . $request->search . '%');
            });
        }

        $customers = $query->with(['conversations' => function ($q) {
            $q->latest(); // Default: hanya ambil yang non-trashed
        }])->latest()->paginate(10)->withQueryString();

        // Map customers to include their current active status
        $customers->getCollection()->transform(function ($user) {
            // Cari percakapan AKTIF (yang belum di-soft delete)
            $activeConv = $user->conversations->whereIn('status', ['pending', 'queued', 'active'])->first();
            $user->current_status = $activeConv ? $activeConv->status : 'no_session';
            return $user;
        });

        return view('admin.dashboard', compact(
            'admin',
            'stats',
            'customers',
            'overview',
            'trends',
            'peakHours',
            'topPerformers',
            'complaintCategories',
            'customerSatisfaction',
            'agentWorkload',
            'customerInsights',
            'metrics',
            'agentPerformance',
            'statusDistribution',
            'startDate',
            'endDate'
        ));
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
     * Workspace Chat — tampilkan semua antrian/chat aktif + global search kategori.
     */
    public function chatWorkspace(Request $request)
    {
        $admin = Auth::guard('admin')->user();
        $searchService = new MessageSearchService();

        $sortOrder = $request->get('sort', 'recent') === 'oldest' ? 'asc' : 'desc';
        $search = trim((string) $request->get('search', ''));
        $quickFilters = array_values(array_filter(explode(',', (string) $request->get('quick_filters', ''))));
        $unreadOnly = $request->boolean('unread_only');

        $mainQuery = Conversation::with(['customer', 'admin', 'messages' => function ($query) {
                $query->latest()->limit(1);
            }])
            ->whereIn('status', ['pending', 'queued', 'active', 'closed']);

        if ($search !== '') {
            $needle = '%' . mb_strtolower($search) . '%';
            $mainQuery->where(function ($query) use ($needle) {
                $query->whereHas('customer', function ($customerQuery) use ($needle) {
                    $customerQuery->whereRaw('LOWER(name) LIKE ?', [$needle])
                        ->orWhereRaw('LOWER(contact) LIKE ?', [$needle]);
                })->orWhereHas('messages', function ($messageQuery) use ($needle) {
                    $messageQuery->whereRaw('LOWER(content) LIKE ?', [$needle]);
                });
            });
        }

        $conversations = $mainQuery
            ->orderBy('queue_position', 'asc') // Keep queue_position for pending/queued
            ->orderBy('last_message_at', $sortOrder)
            ->get();

        $pendingConversations = $conversations->whereIn('status', ['pending', 'queued'])->values();
        $activeConversations  = $conversations->where('status', 'active')->values();
        $closedConversations  = $conversations->where('status', 'closed')->values();

        if ($request->ajax() || $request->has('ajax')) {
            $searchResults = [
                'contacts' => [],
                'groups' => [],
                'messages' => [],
            ];

            if ($search !== '' || !empty($quickFilters) || $unreadOnly) {
                $searchResults = $searchService->search($search, $quickFilters, $unreadOnly);
            }

            return response()->json([
                'pending' => $pendingConversations,
                'active' => $activeConversations,
                'closed' => $closedConversations, // New: include closed conversations
                'search_results' => $searchResults,
                'search_summary' => [
                    'query' => $search,
                    'total_pending' => $pendingConversations->count(),
                    'total_active' => $activeConversations->count(),
                    'total_closed' => $closedConversations->count(), // New: include count
                ],
            ]);
        }

        // Ambil daftar admin lain yang tidak offline untuk pilihan Handover
        $otherAdmins = \App\Models\Admin::where('id', '!=', $admin->id)
            ->where('status', '!=', 'offline')
            ->get();

        return view('admin.chat', compact('admin', 'pendingConversations', 'activeConversations', 'closedConversations', 'otherAdmins'));
    }

    /**
     * Tampilkan isi conversation tertentu (panel kanan dashboard).
     */
    public function showConversation($id)
    {
        $conversation = Conversation::withTrashed()->findOrFail($id);
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

        // Proaktif: Jika admin sedang offline tapi mencoba claim, set jadi online
        if ($admin->status === 'offline') {
            $admin->update(['status' => 'online']);
        }

        // Cek kapasitas admin
        if (!$admin->canTakeNewChat()) {
            return response()->json(['error' => 'Anda sudah mencapai batas maksimum chat aktif.'], 422);
        }

        // Optimistic Locking: update jika status masih pending/queued dan (belum diklaim ATAU diklaim oleh admin ini sendiri)
        $updated = Conversation::where('id', $conversation->id)
            ->whereIn('status', ['pending', 'queued'])
            ->where(function($q) use ($admin) {
                $q->whereNull('admin_id')
                  ->orWhere('admin_id', $admin->id);
            })
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

        try {
            broadcast(new MessageSent($sysMessage));
            // Penting: Broadcast agar sidebar admin lain dan dashboard user terupdate
            broadcast(new ConversationStatusChanged($conversation, $admin->username));
        } catch (\Exception $e) {
            \Log::error('Broadcast failed during claimConversation: ' . $e->getMessage());
        }

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
            'content'         => ['required_without:file', 'nullable', 'string', 'max:2000'],
            'file'            => ['nullable', 'file', 'max:10240'], // Max 10MB
            'message_type'    => ['required', 'in:text,whisper,image,file'],
        ]);

        $admin        = Auth::guard('admin')->user();
        $conversation = Conversation::withTrashed()->findOrFail($request->conversation_id);

        // Logic to handle re-opening closed conversations
        if ($conversation->status === 'closed') {
            $conversation->update([
                'status'   => 'active',
                'admin_id' => $admin->id,
                'deleted_at' => null, // Restore the conversation if soft-deleted
            ]);
            // Broadcast status change
            broadcast(new ConversationStatusChanged($conversation, $admin->username));
        }

        // Pastikan admin ini yang menangani conversation ini (kecuali whisper bisa semua admin)
        // Gunakan non-strict comparison agar aman
        if ($request->message_type !== 'whisper' && $conversation->admin_id != $admin->id) {
            return response()->json(['error' => 'Anda tidak memiliki akses tulis ke chat ini.'], 403);
        }

        $messageType = $request->message_type;
        $content = $request->content;

        if ($request->hasFile('file')) {
            $file = $request->file('file');
            $mime = $file->getMimeType();
            $messageType = str_starts_with($mime, 'image/') ? 'image' : 'file';

            $fileName = time() . '_' . $file->getClientOriginalName();
            $path = $file->storeAs('uploads/chat', $fileName, 'public');
            $content = asset('storage/' . $path);
        }

        $message = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => $admin->id,
            'sender_type'     => 'admin',
            'message_type'    => $messageType,
            'content'         => $content ?? '',
        ]);

        \Log::info('Message created by admin', ['id' => $message->id]);

        $conversation->update(['last_message_at' => now()]);

        try {
            broadcast(new MessageSent($message));
            \Log::info('Admin Broadcast MessageSent success');

            // --- WHAPI NOTIFICATION START ---
            // Kirim ke WhatsApp user jika bukan pesan internal (whisper)
            if ($messageType !== 'whisper' && $conversation->customer) {
                $to = $conversation->customer->contact;
                if ($messageType === 'text') {
                    $this->whatsappService->sendMessage($to, "👩‍💼 *Admin:* " . $content);
                } else {
                    $this->whatsappService->sendMedia($to, $content, "👩‍💼 *Admin:* [Kirim Media]", $messageType);
                }
            }
            // --- WHAPI NOTIFICATION END ---

        } catch (\Exception $e) {
            \Log::error('Admin Broadcast/Whapi MessageSent failed', ['error' => $e->getMessage()]);
        }

        return response()->json([
            'success' => true,
            'message' => [
                'id'           => $message->id,
                'content'      => $message->content,
                'message_type' => $message->message_type,
                'created_at'   => $message->created_at->format('H:i'),
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

        // Gunakan kategori dari request jika ada, jika tidak, pertahankan kategori yang sudah ada (dari bot)
        $category = $request->problem_category ?: $conversation->problem_category;

        $conversation->update([
            'status'           => 'closed',
            'problem_category' => $category,
            'deleted_at'       => null, // Ensure it's not soft-deleted when setting status to closed
        ]);

        // $conversation->delete(); // Soft delete memindahkannya ke arsip - REMOVED

        $sysMessage = Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Chat telah ditutup. Terima kasih telah menghubungi kami!',
        ]);

        broadcast(new MessageSent($sysMessage));
        broadcast(new ConversationStatusChanged($conversation, $admin->username));

        if ($conversation->customer) {
            event(new UserShouldBeLoggedOut($conversation->customer));
        }

        return response()->json(['success' => true]);
    }

    /**
     * Block user spam — tutup chat dan tandai is_blocked = true.
     */
    public function blockUser(Request $request, Conversation $conversation)
    {
        $admin = Auth::guard('admin')->user();

        $conversation->customer->update(['is_blocked' => true]);

        $conversation->update([
            'status'     => 'closed',
            'deleted_at' => null, // Ensure it's not soft-deleted
        ]);
        // $conversation->delete(); // REMOVED

        Message::create([
            'conversation_id' => $conversation->id,
            'sender_id'       => 0,
            'sender_type'     => 'system',
            'message_type'    => 'text',
            'content'         => 'Akun Anda telah diblokir oleh administrator.',
        ]);

        broadcast(new ConversationStatusChanged($conversation, $admin->username));

        if ($conversation->customer) {
            event(new UserShouldBeLoggedOut($conversation->customer));
        }

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
            senderId: $admin->id,
            senderType: 'admin',
            senderRole: $admin->role,
            senderName: $admin->username,
            isTyping: $request->boolean('is_typing')
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
