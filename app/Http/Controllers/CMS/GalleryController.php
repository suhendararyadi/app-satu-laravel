<?php

namespace App\Http\Controllers\CMS;

use App\Http\Controllers\Controller;
use App\Http\Requests\CMS\StoreGalleryRequest;
use App\Http\Requests\CMS\UpdateGalleryRequest;
use App\Models\Gallery;
use App\Models\GalleryImage;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class GalleryController extends Controller
{
    /**
     * Display a listing of the team's galleries.
     */
    public function index(Request $request): InertiaResponse
    {
        $team = $request->user()->currentTeam;
        $galleries = $team->galleries()->with('images')->latest()->get();

        return Inertia::render('cms/galleries/index', ['galleries' => $galleries]);
    }

    /**
     * Show the form for creating a new gallery.
     */
    public function create(): InertiaResponse
    {
        return Inertia::render('cms/galleries/create');
    }

    /**
     * Store a newly created gallery in storage.
     */
    public function store(StoreGalleryRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $team->galleries()->create($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery created.')]);

        return to_route('cms.galleries.index');
    }

    /**
     * Show the form for editing the specified gallery.
     */
    public function edit(Request $request, string $currentTeam, Gallery $gallery): InertiaResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($gallery->team_id !== $team->id, 403);

        $gallery->load('images');

        return Inertia::render('cms/galleries/edit', ['gallery' => $gallery]);
    }

    /**
     * Update the specified gallery in storage.
     */
    public function update(UpdateGalleryRequest $request, string $currentTeam, Gallery $gallery): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($gallery->team_id !== $team->id, 403);

        $gallery->update($request->validated());

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery updated.')]);

        return to_route('cms.galleries.index');
    }

    /**
     * Remove the specified gallery from storage along with all its images.
     */
    public function destroy(Request $request, string $currentTeam, Gallery $gallery): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($gallery->team_id !== $team->id, 403);

        $gallery->load('images');

        foreach ($gallery->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $gallery->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Gallery deleted.')]);

        return to_route('cms.galleries.index');
    }

    /**
     * Upload and store an image for the specified gallery.
     */
    public function storeImage(Request $request, string $currentTeam, Gallery $gallery): JsonResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($gallery->team_id !== $team->id, 403);

        $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'caption' => ['nullable', 'string', 'max:255'],
        ]);

        $path = Storage::disk('public')->put('galleries', $request->file('image'));

        $image = $gallery->images()->create([
            'image_path' => $path,
            'caption' => $request->caption,
            'sort_order' => $gallery->images()->count(),
        ]);

        return response()->json([
            'id' => $image->id,
            'image_url' => Storage::disk('public')->url($image->image_path),
            'caption' => $image->caption,
            'sort_order' => $image->sort_order,
        ]);
    }

    /**
     * Remove the specified image from the gallery.
     */
    public function destroyImage(Request $request, string $currentTeam, Gallery $gallery, GalleryImage $image): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($gallery->team_id !== $team->id, 403);
        abort_if($image->gallery_id !== $gallery->id, 403);

        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->noContent();
    }
}
