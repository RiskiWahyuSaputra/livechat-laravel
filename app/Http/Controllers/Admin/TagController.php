<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Tag;
use App\Models\Conversation;
use App\Services\TagService;
use Illuminate\Http\Request;

class TagController extends Controller
{
    protected $tagService;

    public function __construct(TagService $tagService)
    {
        $this->tagService = $tagService;
    }

    /**
     * Display a listing of tags (JSON for selector or management)
     */
    public function index(Request $request)
    {
        $tags = $this->tagService->getAllTags();

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json($tags);
        }

        return view('admin.tags.index', compact('tags'));
    }

    /**
     * Store a newly created tag
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name',
            'color' => 'nullable|string|max:7', // HEX color
        ]);

        $tag = $this->tagService->createTag($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag berhasil dibuat',
            'data' => $tag
        ]);
    }

    /**
     * Update the specified tag
     */
    public function update(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255|unique:tags,name,' . $tag->id,
            'color' => 'nullable|string|max:7',
        ]);

        $this->tagService->updateTag($tag, $validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag berhasil diperbarui',
            'data' => $tag
        ]);
    }

    /**
     * Remove the specified tag
     */
    public function destroy(Tag $tag)
    {
        $this->tagService->deleteTag($tag);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag berhasil dihapus'
        ]);
    }

    /**
     * Sync tags for a conversation
     */
    public function syncConversationTags(Request $request, Conversation $conversation)
    {
        $validated = $request->validate([
            'tag_ids' => 'nullable|array',
            'tag_ids.*' => 'exists:tags,id'
        ]);

        $this->tagService->syncConversationTags($conversation, $validated['tag_ids'] ?? []);

        return response()->json([
            'status' => 'success',
            'message' => 'Tag percakapan berhasil diperbarui',
            'data' => $conversation->load('tags')->tags
        ]);
    }

    /**
     * Get tags for a conversation
     */
    public function getConversationTags(Conversation $conversation)
    {
        return response()->json($conversation->tags);
    }
}
