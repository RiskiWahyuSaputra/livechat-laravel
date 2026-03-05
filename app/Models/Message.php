<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    protected $fillable = [
        'conversation_id',
        'sender_id',
        'sender_type',
        'message_type',
        'content',
        'is_read',
        'has_media',
        'media_type',
        'extracted_urls',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'has_media' => 'boolean',
            'extracted_urls' => 'array',
        ];
    }

    // Relasi ke conversation
    public function conversation()
    {
        return $this->belongsTo(Conversation::class);
    }

    // Ambil pengirim secara dinamis (User atau Admin)
    public function sender()
    {
        if ($this->sender_type === 'user') {
            return User::find($this->sender_id);
        } elseif ($this->sender_type === 'admin') {
            return Admin::find($this->sender_id);
        }
        return null; // system message
    }

    // Cek apakah ini pesan whisper (internal admin)
    public function isWhisper(): bool
    {
        return $this->message_type === 'whisper';
    }

    // Cek apakah ini pesan sistem otomatis
    public function isSystem(): bool
    {
        return $this->sender_type === 'system';
    }

    // ============================================
    // Full-Text Search Scopes
    // ============================================

    /**
     * Scope for Full-Text Search using MySQL FULLTEXT
     */
    public function scopeFullTextSearch($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE)',
            [$searchTerm]
        );
    }

    /**
     * Scope for Full-Text Search with Query Expansion
     */
    public function scopeFullTextSearchExpanded($query, string $searchTerm)
    {
        return $query->whereRaw(
            'MATCH(content) AGAINST(? IN NATURAL LANGUAGE MODE WITH QUERY EXPANSION)',
            [$searchTerm]
        );
    }

    // ============================================
    // Media Filter Scopes
    // ============================================

    /**
     * Scope for filtering messages with media
     */
    public function scopeHasMedia($query, ?string $mediaType = null)
    {
        if ($mediaType === 'link') {
            return $query->whereNotNull('extracted_urls');
        }

        if ($mediaType) {
            return $query->where('has_media', true)
                ->where('media_type', $mediaType);
        }

        return $query->where('has_media', true);
    }

    /**
     * Scope for filtering image messages
     */
    public function scopeIsImage($query)
    {
        return $query->where('message_type', 'image')
            ->orWhere('media_type', 'image');
    }

    /**
     * Scope for filtering video messages
     */
    public function scopeIsVideo($query)
    {
        return $query->where('media_type', 'video');
    }

    /**
     * Scope for filtering messages with links
     */
    public function scopeHasLinks($query)
    {
        return $query->whereNotNull('extracted_urls')
            ->where('has_media', true);
    }

    // ============================================
    // Sender Type Scopes
    // ============================================

    /**
     * Scope for filtering by sender type
     */
    public function scopeBySenderType($query, string $senderType)
    {
        if ($senderType !== 'all') {
            return $query->where('sender_type', $senderType);
        }
        return $query;
    }

    // ============================================
    // Date Range Scopes
    // ============================================

    /**
     * Scope for filtering by date range
     */
    public function scopeDateRange($query, ?string $dateFrom, ?string $dateTo)
    {
        if ($dateFrom) {
            $query->whereDate('created_at', '>=', $dateFrom);
        }
        if ($dateTo) {
            $query->whereDate('created_at', '<=', $dateTo);
        }
        return $query;
    }

    // ============================================
    // Helper Methods
    // ============================================

    /**
     * Auto-extract URLs from content and update media fields
     * This should be called in the model observer or after save
     */
    public function extractAndUpdateMediaInfo(): void
    {
        // Extract URLs from content
        $pattern = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i';
        preg_match_all($pattern, $this->content, $matches);

        $urls = $matches[0] ?? [];

        // Determine media type
        $mediaType = null;
        if ($this->message_type === 'image') {
            $mediaType = 'image';
        } elseif ($this->message_type === 'file') {
            $videoExtensions = ['.mp4', '.mov', '.avi', '.mkv', '.webm', '.3gp'];
            $lowerContent = mb_strtolower($this->content, 'UTF-8');

            foreach ($videoExtensions as $ext) {
                if (mb_strpos($lowerContent, $ext) !== false) {
                    $mediaType = 'video';
                    break;
                }
            }
        }

        // Update the model
        $this->update([
            'has_media' => !empty($urls) || in_array($this->message_type, ['image', 'file']),
            'media_type' => $mediaType,
            'extracted_urls' => !empty($urls) ? $urls : null,
        ]);
    }
}
