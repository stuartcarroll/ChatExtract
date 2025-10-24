<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index()
    {
        $groups = Group::withCount(['users', 'chatAccess', 'tagAccess'])
            ->with('creator')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.groups.index', compact('groups'));
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        $users = User::orderBy('name')->get();
        return view('admin.groups.create', compact('users'));
    }

    /**
     * Store a newly created group.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $group = Group::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'created_by' => auth()->id(),
        ]);

        // Attach users to group
        if (!empty($validated['user_ids'])) {
            foreach ($validated['user_ids'] as $userId) {
                $group->users()->attach($userId, ['added_by' => auth()->id()]);
            }
        }

        return redirect()->route('admin.groups.index')
            ->with('success', 'Group created successfully.');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        $group->load(['users', 'creator', 'chatAccess.chat', 'tagAccess.tag']);

        return view('admin.groups.show', compact('group'));
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group)
    {
        $users = User::orderBy('name')->get();
        $group->load('users');

        return view('admin.groups.edit', compact('group', 'users'));
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, Group $group)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'user_ids' => ['nullable', 'array'],
            'user_ids.*' => ['exists:users,id'],
        ]);

        $group->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
        ]);

        // Sync users
        if (isset($validated['user_ids'])) {
            $syncData = [];
            foreach ($validated['user_ids'] as $userId) {
                $syncData[$userId] = ['added_by' => auth()->id()];
            }
            $group->users()->sync($syncData);
        } else {
            $group->users()->sync([]);
        }

        return redirect()->route('admin.groups.index')
            ->with('success', 'Group updated successfully.');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Group $group)
    {
        $group->delete();

        return redirect()->route('admin.groups.index')
            ->with('success', 'Group deleted successfully.');
    }

    /**
     * Add a user to the group.
     */
    public function addUser(Request $request, Group $group)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
        ]);

        if (!$group->users()->where('user_id', $validated['user_id'])->exists()) {
            $group->users()->attach($validated['user_id'], ['added_by' => auth()->id()]);
        }

        return redirect()->route('admin.groups.show', $group)
            ->with('success', 'User added to group successfully.');
    }

    /**
     * Remove a user from the group.
     */
    public function removeUser(Group $group, User $user)
    {
        $group->users()->detach($user->id);

        return redirect()->route('admin.groups.show', $group)
            ->with('success', 'User removed from group successfully.');
    }
}
