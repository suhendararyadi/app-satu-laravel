<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\CMS\StorePageRequest;
use App\Http\Requests\CMS\UpdatePageRequest;
use App\Models\Page;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PageController extends Controller
{
    /**
     * Display a listing of the team's pages.
     */
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $pages = $team->pages()->orderBy('sort_order')->get();

        return Inertia::render('cms/pages/index', ['pages' => $pages]);
    }

    /**
     * Show the form for creating a new page.
     */
    public function create(): Response
    {
        return Inertia::render('cms/pages/create');
    }

    /**
     * Store a newly created page in storage.
     */
    public function store(StorePageRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $data = $request->validated();
        $data['slug'] = $this->generateSlug($data['title']);

        abort_if($team->pages()->where('slug', $data['slug'])->exists(), 422, 'Slug already exists.');

        $team->pages()->create($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page created.')]);

        return to_route('cms.pages.index');
    }

    /**
     * Show the form for editing the specified page.
     */
    public function edit(Request $request, string $currentTeam, Page $page): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($page->team_id !== $team->id, 403);

        return Inertia::render('cms/pages/edit', ['page' => $page]);
    }

    /**
     * Update the specified page in storage.
     */
    public function update(UpdatePageRequest $request, string $currentTeam, Page $page): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($page->team_id !== $team->id, 403);

        $validated = $request->validated();

        if ($validated['title'] !== $page->title) {
            $slug = Str::slug($validated['title']);
            abort_if(
                $team->pages()->where('slug', $slug)->where('id', '!=', $page->id)->exists(),
                422,
                'Slug sudah digunakan.'
            );
            $validated['slug'] = $slug;
        }

        $page->update($validated);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page updated.')]);

        return to_route('cms.pages.index');
    }

    /**
     * Remove the specified page from storage.
     */
    public function destroy(Request $request, string $currentTeam, Page $page): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($page->team_id !== $team->id, 403);

        $page->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Page deleted.')]);

        return to_route('cms.pages.index');
    }

    /**
     * Generate a URL-friendly slug from the given title.
     */
    private function generateSlug(string $title): string
    {
        return Str::slug($title);
    }
}
