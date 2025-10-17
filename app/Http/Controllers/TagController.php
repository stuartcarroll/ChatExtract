<?php

namespace App\Http\Controllers;

use App\Models\Tag;
use App\Models\Message;
use Illuminate\Http\Request;

class TagController extends Controller
{
    /**
     * Display a listing of the user's tags.
     */
    public function index()
    {
        $tags = auth()->user()->tags()->withCount('messages')->orderBy('name')->get();
        return view('tags.index', compact('tags'));
    }

    /**
     * Store a newly created tag.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name,NULL,id,user_id,' . auth()->id(),
        ]);

        $tag = auth()->user()->tags()->create([
            'name' => $request->name,
        ]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag created successfully.');
    }

    /**
     * Update the specified tag.
     */
    public function update(Request $request, Tag $tag)
    {
        // Authorize
        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        $request->validate([
            'name' => 'required|string|max:50|unique:tags,name,' . $tag->id . ',id,user_id,' . auth()->id(),
        ]);

        $tag->update(['name' => $request->name]);

        return redirect()->route('tags.index')
            ->with('success', 'Tag updated successfully.');
    }

    /**
     * Remove the specified tag.
     */
    public function destroy(Tag $tag)
    {
        // Authorize
        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

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

        // Authorize - user must own the tag
        if ($tag->user_id !== auth()->id()) {
            abort(403);
        }

        // Authorize - user must have access to the message's chat
        $userChatIds = auth()->user()->ownedChats()->pluck('id')->toArray();
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
