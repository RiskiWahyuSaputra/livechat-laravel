<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\MessageSearchService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class GlobalSearchController extends Controller
{
    protected $searchService;

    public function __construct(MessageSearchService $searchService)
    {
        $this->searchService = $searchService;
    }

    /**
     * Search messages globally
     * 
     * GET /api/admin/messages/search
     * 
     * Query Parameters:
     * - q (required): Search keyword
     * - type: Filter by message type (all, text, image, video, file, link)
     * - sender_type: Filter by sender (all, user, admin, system)
     * - date_from: Start date (Y-m-d)
     * - date_to: End date (Y-m-d)
     * - conversation_id: Filter by specific conversation
     * - page: Page number (default: 1)
     * - per_page: Results per page (default: 20, max: 100)
     */
    public function search(Request $request)
    {
        // Validate request
        $validated = $request->validate([
            'q' => 'required|string|min:1|max:200',
            'type' => 'nullable|string|in:all,text,image,video,file,link',
            'sender_type' => 'nullable|string|in:all,user,admin,system',
            'date_from' => 'nullable|date_format:Y-m-d',
            'date_to' => 'nullable|date_format:Y-m-d|after_or_equal:date_from',
            'conversation_id' => 'nullable|integer|exists:conversations,id',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:100',
        ]);

        try {
            // Execute search
            $results = $this->searchService->search($validated);

            return response()->json([
                'success' => true,
                'data' => $results,
            ], 200);
        } catch (\Exception $e) {
            Log::error('Global search error: ' . $e->getMessage(), [
                'query' => $request->q,
                'filters' => $request->except(['q']),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan saat melakukan pencarian.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Extract URLs from message content (helper endpoint)
     */
    public function extractUrls(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
        ]);

        $urls = $this->searchService->extractUrls($validated['content']);

        return response()->json([
            'success' => true,
            'data' => [
                'urls' => $urls,
            ],
        ], 200);
    }

    /**
     * Generate highlighted snippet (helper endpoint for preview)
     */
    public function generateSnippet(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'query' => 'required|string',
        ]);

        $snippet = $this->searchService->generateSnippet(
            $validated['content'],
            $validated['query']
        );

        return response()->json([
            'success' => true,
            'data' => [
                'snippet' => $snippet,
            ],
        ], 200);
    }
}
