<?php

use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates a gallery with a team', function () {
    $gallery = Gallery::factory()->create();

    expect($gallery)->toBeInstanceOf(Gallery::class)
        ->and($gallery->team)->toBeInstanceOf(Team::class)
        ->and($gallery->title)->not->toBeEmpty();
});

test('images relationship returns gallery images', function () {
    $gallery = Gallery::factory()->create();
    GalleryImage::factory()->count(3)->create(['gallery_id' => $gallery->id]);

    expect($gallery->images)->toHaveCount(3)
        ->each->toBeInstanceOf(GalleryImage::class);
});

test('cascade delete removes gallery images when gallery is deleted', function () {
    $gallery = Gallery::factory()->create();
    GalleryImage::factory()->count(2)->create(['gallery_id' => $gallery->id]);

    expect(GalleryImage::where('gallery_id', $gallery->id)->count())->toBe(2);

    $gallery->delete();

    expect(GalleryImage::where('gallery_id', $gallery->id)->count())->toBe(0);
});
