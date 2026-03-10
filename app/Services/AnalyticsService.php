<?php

namespace App\Services;

use App\Models\Admin;
use App\Models\Conversation;
use App\Models\User;
use App\Models\Message;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AnalyticsService
{
    /**
     * Get overview statistics
     */
    public function getOverviewStats()
    {
        $today = Carbon::today();
        $last7Days = Carbon::now()->subDays(7);
        $last30Days = Carbon::now()->subDays(30);

        return [
            'total_conversations' => Conversation::withTrashed()->count(),
            'today_conversations' => Conversation::withTrashed()->whereDate('created_at', $today)->count(),
            'active_conversations' => Conversation::whereIn('status', ['active', 'pending', 'queued'])->count(),
            'closed_conversations' => Conversation::withTrashed()->where('status', 'closed')->count(),
            'total_customers' => User::count(),
            'new_customers_today' => User::whereDate('created_at', $today)->count(),
            'new_customers_7days' => User::where('created_at', '>=', $last7Days)->count(),
            'total_agents' => Admin::count(),
            'online_agents' => Admin::where('status', 'online')->count(),
        ];
    }

    /**
     * Get status distribution for donut chart
     */
    public function getStatusDistribution()
    {
        $statuses = Conversation::withTrashed()->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'labels' => array_keys($statuses),
            'data' => array_values($statuses),
        ];
    }

    /**
     * Get conversation trends for last 7 days
     */
    public function getConversationTrends()
    {
        $data = [];
        $labels = [];

        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $labels[] = $date->format('d M');
            $data[] = Conversation::withTrashed()->whereDate('created_at', $date)->count();
        }

        return ['labels' => $labels, 'data' => $data];
    }

    /**
     * Get peak hours analysis
     */
    public function getPeakHours()
    {
        $hourlyData = Conversation::withTrashed()->select(DB::raw('HOUR(created_at) as hour'), DB::raw('count(*) as count'))
            ->where('created_at', '>=', Carbon::now()->subDays(30))
            ->groupBy('hour')
            ->orderBy('hour')
            ->get();

        $hours = array_fill(0, 24, 0);
        foreach ($hourlyData as $item) {
            $hours[$item->hour] = $item->count;
        }

        return [
            'labels' => array_map(fn($h) => sprintf('%02d:00', $h), range(0, 23)),
            'data' => $hours,
        ];
    }

    /**
     * Get agent performance statistics
     */
    public function getAgentPerformance()
    {
        $agents = Admin::with(['conversations' => function ($q) {
            $q->withTrashed()->where('status', 'closed');
        }])->get();

        $performance = [];

        foreach ($agents as $agent) {
            $closedChats = $agent->conversations->count();

            $performance[] = [
                'id' => $agent->id,
                'username' => $agent->username,
                'role' => $agent->role,
                'status' => $agent->status,
                'closed_chats' => $closedChats,
                'avg_response_time' => $closedChats > 0 ? $this->calculateAvgResponseTime($agent->id) : 0,
                'avg_duration' => $closedChats > 0 ? $this->calculateAvgChatDuration($agent->id) : 0,
                'is_superadmin' => $agent->is_superadmin,
            ];
        }

        // Sort by closed chats descending
        usort($performance, fn($a, $b) => $b['closed_chats'] <=> $a['closed_chats']);

        return $performance;
    }

    /**
     * Get top performing agents (for boss evaluation)
     */
    public function getTopPerformers()
    {
        $agents = Admin::with(['conversations' => function ($q) {
            $q->withTrashed()->where('status', 'closed');
        }])->where('is_superadmin', false)->get();

        $performance = [];

        foreach ($agents as $agent) {
            $closedChats = $agent->conversations->count();
            $avgResponseTime = $closedChats > 0 ? $this->calculateAvgResponseTime($agent->id) : 0;
            $avgDuration = $closedChats > 0 ? $this->calculateAvgChatDuration($agent->id) : 0;

            // Calculate performance score (higher is better)
            // Score = (chats * 10) + (faster response = higher score)
            $responseScore = $avgResponseTime > 0 ? max(0, 100 - ($avgResponseTime / 6)) : 0;
            $score = round(($closedChats * 10) + $responseScore);

            $performance[] = [
                'id' => $agent->id,
                'username' => $agent->username,
                'closed_chats' => $closedChats,
                'avg_response_time' => $avgResponseTime,
                'avg_duration' => $avgDuration,
                'score' => $score,
                'status' => $agent->status,
            ];
        }

        // Sort by score descending
        usort($performance, fn($a, $b) => $b['score'] <=> $a['score']);

        return [
            'top' => array_slice($performance, 0, 3),
            'bottom' => array_slice($performance, -3),
            'all' => $performance,
        ];
    }

    /**
     * Get complaint categories analysis
     */
    public function getComplaintCategories()
    {
        $definedCategories = config('chat.complaint_categories', [
            'Pendaftaran & Aktivasi',
            'Dukungan Teknis',
            'Masalah Pembayaran',
            'Komplain / Keluhan',
            'Lain-lain'
        ]);
        
        $dbCategories = Conversation::withTrashed()->select('problem_category', DB::raw('count(*) as count'))
            ->whereNotNull('problem_category')
            ->where('problem_category', '!=', '')
            ->groupBy('problem_category')
            ->get()
            ->pluck('count', 'problem_category')
            ->toArray();

        // Merge defined categories with those actually found in database to show "many" categories
        $allCategoryNames = array_unique(array_merge($definedCategories, array_keys($dbCategories)));

        $result = [];
        $chartData = [];
        $chartLabels = [];
        $total = array_sum($dbCategories);

        foreach ($allCategoryNames as $cat) {
            $count = $dbCategories[$cat] ?? 0;
            $percentage = $total > 0 ? round(($count / $total) * 100, 1) : 0;
            
            $result[] = [
                'category' => $cat,
                'count' => $count,
                'percentage' => $percentage,
            ];
            
            $chartData[] = $count;
            $chartLabels[] = $cat;
        }

        return [
            'categories' => $result,
            'total' => $total,
            'chart_data' => $chartData,
            'chart_labels' => $chartLabels,
        ];
    }

    /**
     * Get customer satisfaction analysis (if ratings exist)
     */
    public function getCustomerSatisfaction()
    {
        // Check if ConversationRating model exists and has data
        if (class_exists('App\Models\ConversationRating')) {
            $ratings = \App\Models\ConversationRating::all();
            
            if ($ratings->count() > 0) {
                $avgRating = $ratings->avg('rating');
                $ratingDistribution = $ratings->select('rating', DB::raw('count(*) as count'))
                    ->groupBy('rating')
                    ->pluck('count', 'rating')
                    ->toArray();

                return [
                    'average_rating' => round($avgRating, 2),
                    'total_ratings' => $ratings->count(),
                    'distribution' => $ratingDistribution,
                ];
            }
        }

        // Return empty/no data state
        return [
            'average_rating' => 0,
            'total_ratings' => 0,
            'distribution' => [],
            'has_data' => false,
        ];
    }

    /**
     * Get agent workload comparison
     */
    public function getAgentWorkload()
    {
        $agents = Admin::where('is_superadmin', false)->get();
        
        $workload = [];
        $totalChats = Conversation::withTrashed()->count();

        foreach ($agents as $agent) {
            $handledChats = Conversation::withTrashed()->where('admin_id', $agent->id)->count();
            $activeChats = Conversation::where('admin_id', $agent->id)->whereIn('status', ['active', 'pending', 'queued'])->count();
            $closedChats = Conversation::withTrashed()->where('admin_id', $agent->id)->where('status', 'closed')->count();

            $workload[] = [
                'id' => $agent->id,
                'username' => $agent->username,
                'status' => $agent->status,
                'handled_chats' => $handledChats,
                'active_chats' => $activeChats,
                'closed_chats' => $closedChats,
                'workload_percentage' => $totalChats > 0 ? round(($handledChats / $totalChats) * 100, 1) : 0,
            ];
        }

        usort($workload, fn($a, $b) => $b['handled_chats'] <=> $a['handled_chats']);

        return $workload;
    }

    /**
     * Calculate average response time for an agent (in seconds)
     */
    private function calculateAvgResponseTime($adminId)
    {
        $responseTimes = Conversation::withTrashed()->where('admin_id', $adminId)
            ->where('status', 'closed')
            ->join('messages', 'conversations.id', '=', 'messages.conversation_id')
            ->select('conversations.id', 'conversations.created_at')
            ->selectRaw('MIN(messages.created_at) as first_reply')
            ->where('messages.sender_type', 'admin')
            ->groupBy('conversations.id', 'conversations.created_at')
            ->get();

        $totalResponseTime = 0;
        $count = 0;

        foreach ($responseTimes as $rt) {
            $firstReply = Carbon::parse($rt->first_reply);
            $totalResponseTime += $firstReply->diffInSeconds($rt->created_at);
            $count++;
        }

        return $count > 0 ? round($totalResponseTime / $count) : 0;
    }

    /**
     * Calculate average chat duration for an agent (in minutes)
     */
    private function calculateAvgChatDuration($adminId)
    {
        $avgDuration = Conversation::withTrashed()->where('admin_id', $adminId)
            ->where('status', 'closed')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_duration'))
            ->value('avg_duration');

        return round($avgDuration ?? 0);
    }

    /**
     * Get customer insights
     */
    public function getCustomerInsights()
    {
        // Growth trend (last 7 days)
        $growthData = [];
        $growthLabels = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = Carbon::now()->subDays($i);
            $growthLabels[] = $date->format('d M');
            $growthData[] = User::whereDate('created_at', $date)->count();
        }

        // Origin distribution
        $origins = User::select('origin', DB::raw('count(*) as count'))
            ->whereNotNull('origin')
            ->where('origin', '!=', '')
            ->groupBy('origin')
            ->orderByDesc('count')
            ->limit(10)
            ->get();

        // Online status
        $onlineUsers = User::where('is_online', true)->count();
        $blockedUsers = User::where('is_blocked', true)->count();

        return [
            'growth' => ['labels' => $growthLabels, 'data' => $growthData],
            'origins' => $origins,
            'online_users' => $onlineUsers,
            'blocked_users' => $blockedUsers,
            'total_users' => User::count(),
        ];
    }

    /**
     * Get completion rate and average duration
     */
    public function getConversationMetrics()
    {
        $total = Conversation::withTrashed()->count();
        $closed = Conversation::withTrashed()->where('status', 'closed')->count();
        
        $completionRate = $total > 0 ? round(($closed / $total) * 100, 1) : 0;

        // Average duration for closed conversations
        $avgDuration = Conversation::withTrashed()->where('status', 'closed')
            ->select(DB::raw('AVG(TIMESTAMPDIFF(MINUTE, created_at, updated_at)) as avg_duration'))
            ->value('avg_duration');

        // Status distribution
        $statusDistribution = Conversation::withTrashed()->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->get()
            ->pluck('count', 'status')
            ->toArray();

        return [
            'completion_rate' => $completionRate,
            'avg_duration_minutes' => round($avgDuration ?? 0),
            'status_distribution' => $statusDistribution,
            'total' => $total,
            'closed' => $closed,
        ];
    }

    /**
     * Get date range filtered data
     */
    public function getFilteredData($startDate, $endDate)
    {
        $conversations = Conversation::withTrashed()->whereBetween('created_at', [$startDate, $endDate])->get();
        $users = User::whereBetween('created_at', [$startDate, $endDate])->get();

        return [
            'conversations_count' => $conversations->count(),
            'users_count' => $users->count(),
            'closed_count' => $conversations->where('status', 'closed')->count(),
        ];
    }
}
