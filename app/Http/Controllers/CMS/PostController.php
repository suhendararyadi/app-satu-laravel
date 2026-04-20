<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\CMS\StorePostRequest;
use App\Http\Requests\CMS\UpdatePostRequest;
use App\Models\Post;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PostController extends Controller
{
    /**
     * Display a listing of the team's posts.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $posts = $team->posts()->with('author')->latest()->get();

        return Inertia::render('cms/posts/index', ['posts' => $posts]);
    }

    /**
     * Show the form for creating a new post.
     */
    public function create(): Response
    {
        return Inertia::render('cms/posts/create');
    }

    /**
     * Store a newly created post in storage.
     */
    public function store(StorePostRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $validated = $request->validated();
        $slug = Str::slug($validated['title']);

        abort_if($team->posts()->where('slug', $slug)->exists(), 422, 'Slug sudah digunakan.');

        $path = null;
        if ($request->hasFile('featured_image')) {
            $path = Storage::disk('public')->put('posts', $request->file('featured_image'));
        }

        unset($validated['featured_image']);

        $team->posts()->create([
            ...$validated,
            'slug' => $slug,
            'featured_image_path' => $path,
            'author_id' => auth()->id(),
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post created.')]);

        return to_route('cms.posts.index');
    }

    /**
     * Show the form for editing the specified post.
     */
    public function edit(Request $request, string $currentTeam, Post $post): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($post->team_id !== $team->id, 403);

        $post->load('author');

        return Inertia::render('cms/posts/edit', ['post' => $post]);
    }

    /**
     * Update the specified post in storage.
     */
    public function update(UpdatePostRequest $request, string $currentTeam, Post $post): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($post->team_id !== $team->id, 403);

        $validated = $request->validated();

        if ($validated['title'] !== $post->title) {
            $slug = Str::slug($validated['title']);
            abort_if(
                $team->posts()->where('slug', $slug)->where('id', '!=', $post->id)->exists(),
                422,
                'Slug sudah digunakan.'
            );
            $validated['slug'] = $slug;
        }

        if ($request->hasFile('featured_image')) {
            if ($post->featured_image_path) {
                Storage::disk('public')->delete($post->featured_image_path);
            }
            $validated['featured_image_path'] = Storage::disk('public')->put('posts', $request->file('featured_image'));
        }

        unset($validated['featured_image']);

        $post->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post updated.')]);

        return to_route('cms.posts.index');
    }

    /**
     * Remove the specified post from storage.
     */
    public function destroy(Request $request, string $currentTeam, Post $post): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($post->team_id !== $team->id, 403);

        if ($post->featured_image_path) {
            Storage::disk('public')->delete($post->featured_image_path);
        }

        $post->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Post deleted.')]);

        return to_route('cms.posts.index');
    }
}
