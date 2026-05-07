<?php

namespace App\Services;

use App\Models\Tag;
use App\Models\Conversation;
use Illuminate\Support\Collection;

class TagService
{
    /**
     * Get all tags
     */
    public function getAllTags(): Collection
    {
        return Tag::all();
    }

    /**
     * Create a new tag
     */
    public function createTag(array $data): Tag
    {
        return Tag::create($data);
    }

    /**
     * Update an existing tag
     */
    public function updateTag(Tag $tag, array $data): bool
    {
        return $tag->update($data);
    }

    /**
     * Delete a tag
     */
    public function deleteTag(Tag $tag): bool
    {
        return $tag->delete();
    }

    /**
     * Attach tags to a conversation
     */
    public function syncConversationTags(Conversation $conversation, array $tagIds): void
    {
        $conversation->tags()->sync($tagIds);
    }

    /**
     * Add a single tag to a conversation
     */
    public function attachTagToConversation(Conversation $conversation, int $tagId): void
    {
        $conversation->tags()->syncWithoutDetaching([$tagId]);
    }

    /**
     * Remove a single tag from a conversation
     */
    public function detachTagFromConversation(Conversation $conversation, int $tagId): void
    {
        $conversation->tags()->detach($tagId);
    }

    /**
     * Get tags for a specific conversation
     */
    public function getConversationTags(Conversation $conversation): Collection
    {
        return $conversation->tags;
    }
}
