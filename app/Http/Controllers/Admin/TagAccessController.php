<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\Tag;
use App\Models\TagAccess;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class TagAccessController extends Controller
{
    /**
     * Display a listing of tag access grants.
     */
    public function index(Request $request)
    {
        $query = TagAccess::with(['tag', 'accessable', 'grantedBy']);

        // Filter by tag
        if ($request->filled('tag_id')) {
            $query->where('tag_id', $request->tag_id);
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

        $tags = Tag::orderBy('name')->get();

        return view('admin.tag-access.index', compact('accesses', 'tags'));
    }

    /**
     * Show the form for creating a new tag access grant.
     */
    public function create(Request $request)
    {
        $tagId = $request->get('tag_id');
        $tags = Tag::orderBy('name')->get();
        $users = User::where('role', '!=', 'admin')->orderBy('name')->get();
        $groups = Group::orderBy('name')->get();

        return view('admin.tag-access.create', compact('tags', 'users', 'groups', 'tagId'));
    }

    /**
     * Store a newly created tag access grant.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'tag_id' => ['required', 'exists:tags,id'],
            'accessable_type' => ['required', Rule::in(['user', 'group'])],
            'accessable_id' => ['required', 'integer'],
        ]);

        // Convert type to full class name
        $accessableType = $validated['accessable_type'] === 'user' ? User::class : Group::class;

        // Check if access already exists
        $exists = TagAccess::where('tag_id', $validated['tag_id'])
            ->where('accessable_type', $accessableType)
            ->where('accessable_id', $validated['accessable_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->withInput()
                ->with('error', 'Access already granted to this user/group for this tag.');
        }

        TagAccess::create([
            'tag_id' => $validated['tag_id'],
            'accessable_type' => $accessableType,
            'accessable_id' => $validated['accessable_id'],
            'granted_by' => auth()->id(),
        ]);

        return redirect()->route('admin.tag-access.index')
            ->with('success', 'Tag access granted successfully.');
    }

    /**
     * Display the specified tag access grant.
     */
    public function show(TagAccess $tagAccess)
    {
        $tagAccess->load(['tag', 'accessable', 'grantedBy']);

        return view('admin.tag-access.show', compact('tagAccess'));
    }

    /**
     * Remove the specified tag access grant.
     */
    public function destroy(TagAccess $tagAccess)
    {
        $tagAccess->delete();

        return redirect()->route('admin.tag-access.index')
            ->with('success', 'Tag access revoked successfully.');
    }

    /**
     * Grant tag access from the tag show page.
     */
    public function grantFromTag(Request $request, Tag $tag)
    {
        $validated = $request->validate([
            'accessable_type' => ['required', Rule::in(['user', 'group'])],
            'accessable_id' => ['required', 'integer'],
        ]);

        $accessableType = $validated['accessable_type'] === 'user' ? User::class : Group::class;

        // Check if access already exists
        $exists = TagAccess::where('tag_id', $tag->id)
            ->where('accessable_type', $accessableType)
            ->where('accessable_id', $validated['accessable_id'])
            ->exists();

        if ($exists) {
            return redirect()->back()
                ->with('error', 'Access already granted to this user/group.');
        }

        TagAccess::create([
            'tag_id' => $tag->id,
            'accessable_type' => $accessableType,
            'accessable_id' => $validated['accessable_id'],
            'granted_by' => auth()->id(),
        ]);

        return redirect()->back()
            ->with('success', 'Tag access granted successfully.');
    }
}
