<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Admin;
use App\Models\Conversation;
use App\Models\ConversationRating;
use App\Models\User;
use App\Models\Message;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class LaporanController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Laporan General - ringkasan semua metrik utama
     */
    public function general(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->subDays(30)->startOfDay()));
        $endDate   = Carbon::parse($request->get('end_date',   Carbon::now()->endOfDay()));

        $overview             = $this->analyticsService->getOverviewStats();
        $trends               = $this->analyticsService->getConversationTrends();
        $peakHours            = $this->analyticsService->getPeakHours();
        $metrics              = $this->analyticsService->getConversationMetrics();
        $customerSatisfaction = $this->analyticsService->getCustomerSatisfaction();

        // Breakdown by type: customer chat vs internal chat
        $typeBreakdown = [
            'Customer Chat' => Conversation::withTrashed()->where('is_internal', false)->count(),
            'Internal Chat' => Conversation::withTrashed()->where('is_internal', true)->count(),
        ];

        // Breakdown by problem_category (top 5)
        $categoryBreakdown = Conversation::withTrashed()
            ->select('problem_category', DB::raw('count(*) as count'))
            ->whereNotNull('problem_category')
            ->where('problem_category', '!=', '')
            ->groupBy('problem_category')
            ->orderByDesc('count')
            ->limit(5)
            ->pluck('count', 'problem_category')
            ->toArray();

        return view('admin.laporan.general', compact(
            'overview', 'trends', 'peakHours', 'metrics',
            'customerSatisfaction', 'typeBreakdown', 'categoryBreakdown',
            'startDate', 'endDate'
        ));
    }

    /**
     * Laporan Performa Agen
     */
    public function performaAgen(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->subDays(30)->startOfDay()));
        $endDate   = Carbon::parse($request->get('end_date',   Carbon::now()->endOfDay()));

        $topPerformers    = $this->analyticsService->getTopPerformers();
        $agentWorkload    = $this->analyticsService->getAgentWorkload();
        $agentPerformance = $this->analyticsService->getAgentPerformance();

        // Additional: agents per status count
        $agentStatusCount = Admin::select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return view('admin.laporan.performa-agen', compact(
            'topPerformers', 'agentWorkload', 'agentPerformance',
            'agentStatusCount', 'startDate', 'endDate'
        ));
    }

    /**
     * Laporan Performa Bot
     */
    public function performaBot(Request $request)
    {
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->subDays(30)->startOfDay()));
        $endDate   = Carbon::parse($request->get('end_date',   Carbon::now()->endOfDay()));

        // Bot-handled: conversations where admin_id is null (still in bot flow)
        $botTotal      = Conversation::withTrashed()->whereNull('admin_id')->count();
        $botClosed     = Conversation::withTrashed()->whereNull('admin_id')->where('status', 'closed')->count();
        $botHandedOver = Conversation::withTrashed()->whereNotNull('admin_id')->count();
        $totalAll      = Conversation::withTrashed()->count();

        // Bot vs Agent handling ratio
        $botRatio   = $totalAll > 0 ? round(($botTotal / $totalAll) * 100, 1) : 0;
        $agentRatio = $totalAll > 0 ? round(($botHandedOver / $totalAll) * 100, 1) : 0;

        // Bot daily trend (conversations started with bot, last 7 days)
        $botTrend = [];
        $trendLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $trendLabels[] = $date->format('d M');
            $botTrend[]    = Conversation::withTrashed()
                ->whereNull('admin_id')
                ->whereDate('created_at', $date)
                ->count();
        }

        // Complaint category breakdown (bot menus)
        $complaintCategories = $this->analyticsService->getComplaintCategories();

        // Average handover time (time from conversation start to first admin claim)
        $avgHandoverSeconds = Conversation::withTrashed()
            ->whereNotNull('admin_id')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(SECOND, created_at, updated_at)) as avg_handover'))
            ->value('avg_handover');

        $avgHandoverMinutes = $avgHandoverSeconds ? round($avgHandoverSeconds / 60, 1) : 0;

        // Bot flow completion rate
        $botCompletionRate = $botTotal > 0 ? round(($botClosed / $botTotal) * 100, 1) : 0;

        return view('admin.laporan.performa-bot', compact(
            'botTotal', 'botClosed', 'botHandedOver', 'totalAll',
            'botRatio', 'agentRatio', 'botTrend', 'trendLabels',
            'complaintCategories', 'avgHandoverMinutes', 'botCompletionRate',
            'startDate', 'endDate'
        ));
    }

    /**
     * Laporan Contact (Pelanggan)
     */
    public function contact(Request $request)
    {
        $filter   = $request->get('filter');
        $startDate = Carbon::parse($request->get('start_date', Carbon::now()->subDays(30)->startOfDay()));
        $endDate   = Carbon::parse($request->get('end_date',   Carbon::now()->endOfDay()));

        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });

        if ($filter === '1_month') {
            $query->where('created_at', '>=', now()->subMonth());
        } elseif ($filter === '1_year') {
            $query->where('created_at', '>=', now()->subYear());
        }

        $customers = $query->with('conversations')->latest()->get();

        foreach ($customers as $customer) {
            $statusData = $this->mapUserStatus($customer);
            $customer->status_label = $statusData['label'];
            $customer->status_class = $statusData['class'];
        }

        // Summary stats
        $totalCustomers  = $customers->count();
        $onlineCustomers = $customers->where('is_online', true)->count();
        $blockedCustomers = User::where('is_blocked', true)->count();
        $newThisMonth    = User::where('created_at', '>=', now()->startOfMonth())->count();

        // Customer growth trend
        $growthData   = [];
        $growthLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $growthLabels[] = $date->format('d M');
            $growthData[]   = User::whereDate('created_at', $date)->count();
        }

        // Origin distribution
        $origins = User::select('origin', DB::raw('count(*) as count'))
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->groupBy('origin')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        return view('admin.laporan.contact', compact(
            'customers', 'filter', 'totalCustomers', 'onlineCustomers',
            'blockedCustomers', 'newThisMonth', 'growthData', 'growthLabels', 'origins',
            'startDate', 'endDate'
        ));
    }

    /**
     * API Data untuk Contact (refresh AJAX)
     */
    public function contactApiData(Request $request)
    {
        $query = User::whereNot(function ($q) {
            $q->where('email', 'like', 'anon_%@livechat.best')
              ->where('name', 'Guest');
        });

        $filter = $request->get('filter');
        if ($filter === '1_month') {
            $query->where('created_at', '>=', now()->subMonth());
        } elseif ($filter === '1_year') {
            $query->where('created_at', '>=', now()->subYear());
        }

        $customers = $query->with('conversations')->latest()->get()->map(function ($c) {
            $statusData = $this->mapUserStatus($c);
            return [
                'id'         => $c->id,
                'custom_id'  => 'CUST-' . str_pad($c->id, 4, '0', STR_PAD_LEFT),
                'name'       => $c->name,
                'contact'    => $c->contact,
                'origin'     => $c->origin,
                'status'     => $statusData['label'],
                'status_class' => $statusData['class'],
                'created_at' => $c->created_at->format('d M Y H:i'),
            ];
        });

        return response()->json($customers);
    }

    private function mapUserStatus($user): array
    {
        $activeConv = $user->conversations->whereIn('status', ['pending', 'queued', 'active'])->first();
        $status = $activeConv ? $activeConv->status : ($user->is_online ? 'online' : 'offline');

        $statusLabels = [
            'pending'    => 'Menunggu',
            'queued'     => 'Antrean',
            'active'     => 'Aktif (Chat)',
            'online'     => 'Online',
            'offline'    => 'Offline',
            'no_session' => 'Selesai',
        ];

        $statusClasses = [
            'pending'    => 'bg-warning',
            'queued'     => 'bg-info',
            'active'     => 'bg-primary',
            'online'     => 'bg-success',
            'offline'    => 'bg-secondary',
            'no_session' => 'bg-light',
        ];

        return [
            'label' => $statusLabels[$status] ?? 'Offline',
            'class' => $statusClasses[$status] ?? 'bg-secondary',
        ];
    }
}
