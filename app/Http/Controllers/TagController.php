<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Message;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of all tags (global).
     */
    public function index()
    {
        // Get all tags with message count (only counting messages user has access to)
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();

        $tags = Tag::withCount(['messages' => function($query) use ($userChatIds) {
            $query->whereIn('chat_id', $userChatIds);
        }])->orderBy('name')->get();

        return view('tags.index', compact('tags'));
    }

    /**
     * Store a newly created tag (global).
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name',
        ]);

        $tag = Tag::create([
            'name' => $request->name,
        ]);

        // If AJAX request, return JSON with tag data
        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'id' => $tag->id,
                'name' => $tag->name,
                'tag' => $tag
            ], 201);
        }

        // If called from inline form, redirect back to where they were
        if ($request->has('redirect_back')) {
            return back()->with('success', 'Tag "' . $tag->name . '" created successfully.');
        }

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag (global - any user can update).
     */
    public function update(Request $request, Tag $tag)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name,' . $tag->id,
        ]);

        $tag->update(['name' => $request->name]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag (global - any user can delete).
     */
    public function destroy(Tag $tag)
    {
        $tag->delete();

        return redirect()->route('tags.index')
            ->with('success', 'Tag deleted successfully.');
    }

    /**
     * Tag a message.
     */
    public function tagMessage(Request $request, Message $message)
    {
        $request->validate([
            'tag_id' => 'required|exists:tags,id',
        ]);

        $tag = Tag::findOrFail($request->tag_id);

        // Authorize - user must have access to the message's chat
        $userChatIds = auth()->user()->accessibleChatIds()->toArray();
        if (!in_array($message->chat_id, $userChatIds)) {
            abort(403);
        }

        // Toggle tag
        if ($message->tags()->where('tags.id', $tag->id)->exists()) {
            $message->tags()->detach($tag->id);
            $action = 'removed';
        } else {
            $message->tags()->attach($tag->id);
            $action = 'added';
        }

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'action' => $action,
                'message' => "Tag {$action} successfully."
            ]);
        }

        return back()->with('success', "Tag {$action} successfully.");
    }
}
