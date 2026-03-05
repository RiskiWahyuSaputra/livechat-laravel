<?php

namespace App\Services;

use App\Models\Message;
use App\Models\Conversation;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MessageSearchService
{
    private const MAX_SNIPPET_LENGTH = 200;
    private const CONTEXT_WORDS = 10;

    /**
     * Eksekusi pencarian dengan FTS dan filter
     */
    public function search(array $params): array
    {
        $query = $params['q'] ?? '';
        $type = $params['type'] ?? 'all';
        $senderType = $params['sender_type'] ?? 'all';
        $dateFrom = $params['date_from'] ?? null;
        $dateTo = $params['date_to'] ?? null;
        $conversationId = $params['conversation_id'] ?? null;
        $page = $params['page'] ?? 1;
        $perPage = min($params['per_page'] ?? 20, 100);

        if (empty($query)) {
            return [
                'results' => [],
                'pagination' => [
                    'current_page' => 1,
                    'per_page' => $perPage,
                    'total' => 0,
                    'last_page' => 0,
                ],
                'facets' => $this->getEmptyFacets(),
            ];
        }

        // Build base query with FTS
        $results = $this->executeFtsQuery(
            $query,
            $type,
            $senderType,
            $dateFrom,
            $dateTo,
            $conversationId,
            $page,
            $perPage
        );

        // Process results with highlighted snippets
        $processedResults = $this->processResults($results, $query);

        // Get facets
        $facets = $this->getFacets($query, $type, $senderType, $dateFrom, $dateTo, $conversationId);

        return [
            'results' => $processedResults,
            'pagination' => [
                'current_page' => $results->currentPage(),
                'per_page' => $results->perPage(),
                'total' => $results->total(),
                'last_page' => $results->lastPage(),
            ],
            'facets' => $facets,
        ];
    }

    /**
     * Eksekusi query FTS dengan scoring
     */
    private function executeFtsQuery(
        string $query,
        string $type,
        string $senderType,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $conversationId,
        int $page,
        int $perPage
    ): LengthAwarePaginator {
        // Escape special characters for FTS
        $escapedQuery = $this->escapeFtsQuery($query);

        // Build the FTS query
        $ftsQuery = "SELECT 
            m.*,
            MATCH(m.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score,
            c.user_id as conversation_user_id,
            c.admin_id as conversation_admin_id,
            cu.name as customer_name,
            a.name as admin_name
            FROM messages m
            INNER JOIN conversations c ON m.conversation_id = c.id
            LEFT JOIN users cu ON c.user_id = cu.id
            LEFT JOIN admins a ON c.admin_id = a.id
            WHERE MATCH(m.content) AGAINST(? IN NATURAL LANGUAGE MODE)";

        $bindings = [$query, $query];

        // Apply filters
        if ($type !== 'all') {
            if ($type === 'link') {
                $ftsQuery .= " AND m.extracted_urls IS NOT NULL";
            } elseif ($type === 'image') {
                $ftsQuery .= " AND (m.message_type = 'image' OR (m.message_type = 'file' AND m.media_type = 'image'))";
            } elseif ($type === 'video') {
                $ftsQuery .= " AND m.media_type = 'video'";
            } elseif ($type === 'file') {
                $ftsQuery .= " AND m.message_type = 'file' AND (m.media_type IS NULL OR m.media_type NOT IN ('image', 'video'))";
            } else {
                $ftsQuery .= " AND m.message_type = ?";
                $bindings[] = $type;
            }
        }

        if ($senderType !== 'all') {
            $ftsQuery .= " AND m.sender_type = ?";
            $bindings[] = $senderType;
        }

        if ($dateFrom) {
            $ftsQuery .= " AND DATE(m.created_at) >= ?";
            $bindings[] = $dateFrom;
        }

        if ($dateTo) {
            $ftsQuery .= " AND DATE(m.created_at) <= ?";
            $bindings[] = $dateTo;
        }

        if ($conversationId) {
            $ftsQuery .= " AND m.conversation_id = ?";
            $bindings[] = $conversationId;
        }

        // Order by relevance score and then by date
        $ftsQuery .= " ORDER BY relevance_score DESC, m.created_at DESC";

        // Get total count
        $countQuery = str_replace('SELECT m.*, MATCH(m.content) AGAINST(? IN NATURAL LANGUAGE MODE) as relevance_score,
            c.user_id as conversation_user_id,
            c.admin_id as conversation_admin_id,
            cu.name as customer_name,
            a.name as admin_name', 'SELECT COUNT(*) as total', $ftsQuery);
        $countQuery = preg_replace(
            '/FROM messages m.*WHERE MATCH\(m\.content\).*AGAINST\(/',
            'FROM messages m WHERE MATCH(m.content) AGAINST(',
            $countQuery
        );

        // For count, we need a simpler approach
        $countBindings = array_slice($bindings, 0, 2); // Only query and escaped query for count

        // Actually, let's do a simpler count query
        $baseWhere = "WHERE MATCH(m.content) AGAINST('$escapedQuery' IN NATURAL LANGUAGE MODE)";
        if ($type !== 'all') {
            if ($type === 'link') {
                $baseWhere .= " AND m.extracted_urls IS NOT NULL";
            } else {
                $baseWhere .= " AND m.message_type = '$type'";
            }
        }
        if ($senderType !== 'all') {
            $baseWhere .= " AND m.sender_type = '$senderType'";
        }
        if ($dateFrom) {
            $baseWhere .= " AND DATE(m.created_at) >= '$dateFrom'";
        }
        if ($dateTo) {
            $baseWhere .= " AND DATE(m.created_at) <= '$dateTo'";
        }
        if ($conversationId) {
            $baseWhere .= " AND m.conversation_id = $conversationId";
        }

        $countSql = "SELECT COUNT(*) as total FROM messages m INNER JOIN conversations c ON m.conversation_id = c.id $baseWhere";

        try {
            $total = DB::select($countSql)[0]->total ?? 0;
        } catch (\Exception $e) {
            Log::error('Search count error: ' . $e->getMessage());
            $total = 0;
        }

        // Add pagination
        $offset = ($page - 1) * $perPage;
        $ftsQuery .= " LIMIT $offset, $perPage";

        // Execute main query
        try {
            $messages = DB::select($ftsQuery, $bindings);
        } catch (\Exception $e) {
            Log::error('Search query error: ' . $e->getMessage());
            $messages = [];
        }

        // Convert to paginator
        return new LengthAwarePaginator(
            $messages,
            $total,
            $perPage,
            $page,
            ['path' => request()->url(), 'query' => request()->query()]
        );
    }

    /**
     * Escape special characters for FTS query
     */
    private function escapeFtsQuery(string $query): string
    {
        // Remove special FTS operators and escape quotes
        $escaped = preg_replace('/[+\-><()~*\"@]+/', ' ', $query);
        $escaped = str_replace("'", "\\'", trim($escaped));
        return $escaped;
    }

    /**
     * Process results with highlighted snippets
     */
    private function processResults($results, string $query): array
    {
        $processed = [];

        foreach ($results as $message) {
            // Determine sender name
            $senderName = 'Unknown';
            if ($message->sender_type === 'user') {
                $senderName = $message->customer_name ?? 'User';
            } elseif ($message->sender_type === 'admin') {
                $senderName = $message->admin_name ?? 'Admin';
            } elseif ($message->sender_type === 'system') {
                $senderName = 'System';
            }

            // Generate highlighted snippet
            $snippet = $this->generateSnippet($message->content, $query);

            // Determine conversation title
            $conversationTitle = "Percakapan #{$message->conversation_id}";

            $processed[] = [
                'id' => $message->id,
                'conversation_id' => $message->conversation_id,
                'sender_name' => $senderName,
                'sender_type' => $message->sender_type,
                'message_type' => $message->message_type,
                'content' => $message->content,
                'highlighted_content' => $snippet,
                'created_at' => $message->created_at,
                'conversation_title' => $conversationTitle,
                'relevance_score' => $message->relevance_score ?? 0,
            ];
        }

        return $processed;
    }

    /**
     * Generate highlighted snippet dengan konteks
     */
    public function generateSnippet(string $content, string $query, int $contextWords = self::CONTEXT_WORDS): string
    {
        // Strip HTML tags for cleaner snippet
        $plainContent = strip_tags($content);

        // Convert to lowercase for case-insensitive search
        $lowerContent = mb_strtolower($plainContent, 'UTF-8');
        $lowerQuery = mb_strtolower($query, 'UTF-8');

        // Find position of query in content
        $pos = mb_strpos($lowerContent, $lowerQuery, 0, 'UTF-8');

        if ($pos === false) {
            // Query not found directly, return truncated content
            return mb_substr($plainContent, 0, self::MAX_SNIPPET_LENGTH) . (mb_strlen($plainContent) > self::MAX_SNIPPET_LENGTH ? '...' : '');
        }

        // Calculate start position (go back contextWords words)
        $words = preg_split('/\s+/', $plainContent);
        $charPos = 0;
        $wordCount = 0;

        for ($i = 0; $i < count($words); $i++) {
            $charPos = mb_strpos($plainContent, $words[$i], $charPos, 'UTF-8');
            if ($charPos >= $pos) {
                break;
            }
            $wordCount++;
        }

        // Get start position
        $startPos = max(0, $pos - 50);

        // Get end position
        $endPos = min(mb_strlen($plainContent, 'UTF-8'), $pos + mb_strlen($query, 'UTF-8') + 150);

        // Extract snippet
        $snippet = mb_substr($plainContent, $startPos, $endPos - $startPos, 'UTF-8');

        // Add ellipsis if truncated
        if ($startPos > 0) {
            $snippet = '...' . $snippet;
        }
        if ($endPos < mb_strlen($plainContent, 'UTF-8')) {
            $snippet = $snippet . '...';
        }

        // Highlight the query
        $snippet = $this->highlightText($snippet, $query);

        return $snippet;
    }

    /**
     * Highlight text with mark tags
     */
    private function highlightText(string $text, string $query): string
    {
        // Case-insensitive highlight
        $pattern = '/(' . preg_quote($query, '/') . ')/i';
        return preg_replace($pattern, '<mark>$1</mark>', $text);
    }

    /**
     * Get facets for filtering options
     */
    private function getFacets(
        string $query,
        string $type,
        string $senderType,
        ?string $dateFrom,
        ?string $dateTo,
        ?int $conversationId
    ): array {
        $escapedQuery = $this->escapeFtsQuery($query);

        $baseWhere = "WHERE MATCH(m.content) AGAINST('$escapedQuery' IN NATURAL LANGUAGE MODE)";

        if ($dateFrom) {
            $baseWhere .= " AND DATE(m.created_at) >= '$dateFrom'";
        }
        if ($dateTo) {
            $baseWhere .= " AND DATE(m.created_at) <= '$dateTo'";
        }
        if ($conversationId) {
            $baseWhere .= " AND m.conversation_id = $conversationId";
        }

        // By message type
        $typeSql = "SELECT 
            SUM(CASE WHEN m.message_type = 'text' THEN 1 ELSE 0 END) as text_count,
            SUM(CASE WHEN m.message_type = 'image' THEN 1 ELSE 0 END) as image_count,
            SUM(CASE WHEN m.message_type = 'file' AND (m.media_type = 'video' OR m.content LIKE '%.mp4%' OR m.content LIKE '%.mov%') THEN 1 ELSE 0 END) as video_count,
            SUM(CASE WHEN m.message_type = 'file' AND (m.media_type IS NULL OR m.media_type NOT IN ('image', 'video')) THEN 1 ELSE 0 END) as file_count,
            SUM(CASE WHEN m.extracted_urls IS NOT NULL THEN 1 ELSE 0 END) as link_count
            FROM messages m $baseWhere";

        try {
            $typeResult = DB::select($typeSql)[0] ?? null;
            $byType = [
                'text' => (int) ($typeResult->text_count ?? 0),
                'image' => (int) ($typeResult->image_count ?? 0),
                'video' => (int) ($typeResult->video_count ?? 0),
                'file' => (int) ($typeResult->file_count ?? 0),
                'link' => (int) ($typeResult->link_count ?? 0),
            ];
        } catch (\Exception $e) {
            $byType = ['text' => 0, 'image' => 0, 'video' => 0, 'file' => 0, 'link' => 0];
        }

        // By sender type
        $senderSql = "SELECT 
            SUM(CASE WHEN m.sender_type = 'user' THEN 1 ELSE 0 END) as user_count,
            SUM(CASE WHEN m.sender_type = 'admin' THEN 1 ELSE 0 END) as admin_count,
            SUM(CASE WHEN m.sender_type = 'system' THEN 1 ELSE 0 END) as system_count
            FROM messages m $baseWhere";

        try {
            $senderResult = DB::select($senderSql)[0] ?? null;
            $bySender = [
                'user' => (int) ($senderResult->user_count ?? 0),
                'admin' => (int) ($senderResult->admin_count ?? 0),
                'system' => (int) ($senderResult->system_count ?? 0),
            ];
        } catch (\Exception $e) {
            $bySender = ['user' => 0, 'admin' => 0, 'system' => 0];
        }

        return [
            'by_type' => $byType,
            'by_sender' => $bySender,
        ];
    }

    /**
     * Get empty facets structure
     */
    private function getEmptyFacets(): array
    {
        return [
            'by_type' => [
                'text' => 0,
                'image' => 0,
                'video' => 0,
                'file' => 0,
                'link' => 0,
            ],
            'by_sender' => [
                'user' => 0,
                'admin' => 0,
                'system' => 0,
            ],
        ];
    }

    /**
     * Ekstrak URL dari konten pesan
     */
    public function extractUrls(string $content): array
    {
        $pattern = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i';
        preg_match_all($pattern, $content, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Determine media type from content/URL
     */
    public function determineMediaType(string $content, ?string $messageType): ?string
    {
        if ($messageType === 'image') {
            return 'image';
        }

        $videoExtensions = ['.mp4', '.mov', '.avi', '.mkv', '.webm', '.3gp'];
        $lowerContent = mb_strtolower($content, 'UTF-8');

        foreach ($videoExtensions as $ext) {
            if (mb_strpos($lowerContent, $ext, 0, 'UTF-8') !== false) {
                return 'video';
            }
        }

        // Check for image extensions
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'];
        foreach ($imageExtensions as $ext) {
            if (mb_strpos($lowerContent, $ext, 0, 'UTF-8') !== false) {
                return 'image';
            }
        }

        return null;
    }
}
