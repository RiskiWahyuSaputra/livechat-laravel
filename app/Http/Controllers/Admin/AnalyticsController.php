<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AnalyticsController extends Controller
{
    protected $analyticsService;

    public function __construct(AnalyticsService $analyticsService)
    {
        $this->analyticsService = $analyticsService;
    }

    /**
     * Main analytics dashboard
     */
    public function index(Request $request)
    {
        $admin = auth('admin')->user();

        // Get date range from request or default to last 30 days
        $startDate = $request->get('start_date', Carbon::now()->subDays(30)->startOfDay());
        $endDate = $request->get('end_date', Carbon::now()->endOfDay());

        // Overview statistics
        $overview = $this->analyticsService->getOverviewStats();

        // Conversation trends
        $trends = $this->analyticsService->getConversationTrends();

        // Peak hours
        $peakHours = $this->analyticsService->getPeakHours();

        // Agent performance
        $agentPerformance = $this->analyticsService->getAgentPerformance();

        // Customer insights
        $customerInsights = $this->analyticsService->getCustomerInsights();

        // Conversation metrics
        $metrics = $this->analyticsService->getConversationMetrics();

        // Boss evaluation features
        $topPerformers = $this->analyticsService->getTopPerformers();
        $complaintCategories = $this->analyticsService->getComplaintCategories();
        $customerSatisfaction = $this->analyticsService->getCustomerSatisfaction();
        $agentWorkload = $this->analyticsService->getAgentWorkload();

        return view('admin.analytics', compact(
            'admin',
            'overview',
            'trends',
            'peakHours',
            'agentPerformance',
            'customerInsights',
            'metrics',
            'startDate',
            'endDate',
            'topPerformers',
            'complaintCategories',
            'customerSatisfaction',
            'agentWorkload'
        ));
    }

    /**
     * Get filtered analytics data via AJAX
     */
    public function filter(Request $request)
    {
        $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
        ]);

        $startDate = Carbon::parse($request->start_date)->startOfDay();
        $endDate = Carbon::parse($request->end_date)->endOfDay();

        $data = $this->analyticsService->getFilteredData($startDate, $endDate);

        return response()->json($data);
    }

    /**
     * Get real-time stats for AJAX polling
     */
    public function realtime()
    {
        $overview = $this->analyticsService->getOverviewStats();

        return response()->json($overview);
    }

    /**
     * Export analytics data
     */
    public function export(Request $request)
    {
        // For now, return JSON. Could be extended to CSV/Excel
        $agentPerformance = $this->analyticsService->getAgentPerformance();
        $metrics = $this->analyticsService->getConversationMetrics();
        $topPerformers = $this->analyticsService->getTopPerformers();
        $complaintCategories = $this->analyticsService->getComplaintCategories();

        return response()->json([
            'generated_at' => now()->toIso8601String(),
            'agent_performance' => $agentPerformance,
            'metrics' => $metrics,
            'top_performers' => $topPerformers,
            'complaint_categories' => $complaintCategories,
        ]);
    }
}
