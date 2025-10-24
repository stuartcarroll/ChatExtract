<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Chat;
use App\Models\ChatAccess;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ChatAccessController extends Controller
{
    /**
     * Display a listing of chat access grants.
     */
    public function index(Request $request)
    {
        $query = ChatAccess::with(['chat', 'accessable', 'grantedBy']);

        // Filter by chat
        if ($request->filled('chat_id')) {
            $query->where('chat_id', $request->chat_id);
        }

        // Filter by type (user or group)
        if ($request->filled('type')) {
            if ($request->type === 'user') {
                $query->where('accessable_type', User::class);
            } elseif ($request->type === 'group') {
                $query->where('accessable_type', Group::class);
            }
        }

        $accesses = $query->orderBy('created_at', 'desc')->paginate(20);

        $chats = Chat::orderBy('name')->get();

        return view('admin.chat-access.index', compact('accesses', 'chats'));
    }

    /**
     * Show the form for creating a new chat access grant.
     */
    public function create(Request $request)
    {
        $chatId = $request->get('chat_id');
        $chats = Chat::orderBy('name')->get();
        $users = User::where('role', '!=', 'admin')->orderBy('name')->get();
        $groups = Group::orderBy('name')->get();

        return view('admin.chat-access.create', compact('chats', 'users', 'groups', 'chatId'));
    }

    /**
     * Store a newly created chat access grant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'chat_id' => ['required', 'exists:chats,id'],
            'accessable_type' => ['required', Rule::in(['user', 'group'])],
            'accessable_id' => ['required', 'integer'],
            'permission' => ['required', 'string', 'max:50'],
        ]);

        // Convert type to full class name
        $accessableType = $validated['accessable_type'] === 'user' ? User::class : Group::class;

        // Check if access already exists
        $exists = ChatAccess::where('chat_id', $validated['chat_id'])
            ->where('accessable_type', $accessableType)
            ->where('accessable_id', $validated['accessable_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Access already granted to this user/group for this chat.');
        }

        ChatAccess::create([
            'chat_id' => $validated['chat_id'],
            'accessable_type' => $accessableType,
            'accessable_id' => $validated['accessable_id'],
            'permission' => $validated['permission'],
            'granted_by' => auth()->id(),
        ]);

        return redirect()->route('admin.chat-access.index')
            ->with('success', 'Chat access granted successfully.');
    }

    /**
     * Display the specified chat access grant.
     */
    public function show(ChatAccess $chatAccess)
    {
        $chatAccess->load(['chat', 'accessable', 'grantedBy']);

        return view('admin.chat-access.show', compact('chatAccess'));
    }

    /**
     * Show the form for editing the specified chat access grant.
     */
    public function edit(ChatAccess $chatAccess)
    {
        $chatAccess->load('accessable');
        $chats = Chat::orderBy('title')->get();

        return view('admin.chat-access.edit', compact('chatAccess', 'chats'));
    }

    /**
     * Update the specified chat access grant.
     */
    public function update(Request $request, ChatAccess $chatAccess)
    {
        $validated = $request->validate([
            'permission' => ['required', 'string', 'max:50'],
        ]);

        $chatAccess->update($validated);

        return redirect()->route('admin.chat-access.index')
            ->with('success', 'Chat access updated successfully.');
    }

    /**
     * Remove the specified chat access grant.
     */
    public function destroy(ChatAccess $chatAccess)
    {
        $chatAccess->delete();

        return redirect()->route('admin.chat-access.index')
            ->with('success', 'Chat access revoked successfully.');
    }

    /**
     * Grant chat access from the chat show page.
     */
    public function grantFromChat(Request $request, Chat $chat)
    {
        $validated = $request->validate([
            'accessable_type' => ['required', Rule::in(['user', 'group'])],
            'accessable_id' => ['required', 'integer'],
            'permission' => ['required', 'string', 'max:50'],
        ]);

        $accessableType = $validated['accessable_type'] === 'user' ? User::class : Group::class;

        // Check if access already exists
        $exists = ChatAccess::where('chat_id', $chat->id)
            ->where('accessable_type', $accessableType)
            ->where('accessable_id', $validated['accessable_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Access already granted to this user/group.');
        }

        ChatAccess::create([
            'chat_id' => $chat->id,
            'accessable_type' => $accessableType,
            'accessable_id' => $validated['accessable_id'],
            'permission' => $validated['permission'],
            'granted_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Chat access granted successfully.');
    }
}
