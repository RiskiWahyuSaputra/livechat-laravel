<?php

namespace App\Observers;

use App\Models\Message;

class MessageObserver
{
    /**
     * Handle the Message "created" event.
     * Automatically extract URLs and update media info.
     */
    public function created(Message $message): void
    {
        $this->extractMediaInfo($message);
    }

    /**
     * Handle the Message "updated" event.
     * Re-extract media info if content changed.
     */
    public function updated(Message $message): void
    {
        // Check if content was updated
        if ($message->isDirty('content')) {
            $this->extractMediaInfo($message);
        }
    }

    /**
     * Extract URLs and determine media type from message content
     */
    private function extractMediaInfo(Message $message): void
    {
        // Skip if content is empty
        if (empty($message->content)) {
            return;
        }

        // Extract URLs from content
        $urls = $this->extractUrls($message->content);

        // Determine media type
        $mediaType = $this->determineMediaType($message->content, $message->message_type);

        // Determine has_media flag
        $hasMedia = !empty($urls) || in_array($message->message_type, ['image', 'file']);

        // Update the message with extracted info
        $message->update([
            'has_media' => $hasMedia,
            'media_type' => $mediaType,
            'extracted_urls' => !empty($urls) ? json_encode($urls) : null,
        ]);
    }

    /**
     * Extract URLs from content
     */
    private function extractUrls(string $content): array
    {
        $pattern = '/https?:\/\/[^\s<>"{}|\\^`\[\]]+/i';
        preg_match_all($pattern, $content, $matches);

        return $matches[0] ?? [];
    }

    /**
     * Determine media type from content or message type
     */
    private function determineMediaType(string $content, ?string $messageType): ?string
    {
        // If message_type is image, return image
        if ($messageType === 'image') {
            return 'image';
        }

        // Check for video extensions in content
        $videoExtensions = ['.mp4', '.mov', '.avi', '.mkv', '.webm', '.3gp'];
        $lowerContent = mb_strtolower($content, 'UTF-8');

        foreach ($videoExtensions as $ext) {
            if (mb_strpos($lowerContent, $ext) !== false) {
                return 'video';
            }
        }

        // Check for image extensions in content
        $imageExtensions = ['.jpg', '.jpeg', '.png', '.gif', '.webp', '.bmp', '.svg'];
        foreach ($imageExtensions as $ext) {
            if (mb_strpos($lowerContent, $ext) !== false) {
                return 'image';
            }
        }

        return null;
    }
}
