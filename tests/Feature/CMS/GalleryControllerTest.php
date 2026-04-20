<?php

use App\Enums\TeamRole;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin sees only their team galleries on index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $ownGallery = Gallery::factory()->create(['team_id' => $team->id]);
    Gallery::factory()->create(); // different team

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('cms.galleries.index'))
        ->assertInertia(fn ($page) => $page
            ->has('galleries', 1)
            ->where('galleries.0.id', $ownGallery->id)
        );
});

test('admin can create a gallery', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->post(route('cms.galleries.store'), [
            'title' => 'My Test Gallery',
            'description' => 'A nice gallery',
            'is_published' => false,
        ])
        ->assertRedirect(route('cms.galleries.index'));

    $this->assertDatabaseHas('galleries', [
        'team_id' => $team->id,
        'title' => 'My Test Gallery',
        'description' => 'A nice gallery',
    ]);
});

test('admin can update a gallery', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $gallery = Gallery::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)
        ->patch(route('cms.galleries.update', $gallery), [
            'title' => 'Updated Gallery Title',
            'description' => 'Updated description',
            'is_published' => true,
        ])
        ->assertRedirect(route('cms.galleries.index'));

    $this->assertDatabaseHas('galleries', [
        'id' => $gallery->id,
        'title' => 'Updated Gallery Title',
        'is_published' => 1,
    ]);
});

test('destroy gallery deletes record and all gallery images from storage', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $gallery = Gallery::factory()->create(['team_id' => $team->id]);

    $file1 = UploadedFile::fake()->image('img1.jpg');
    $file2 = UploadedFile::fake()->image('img2.jpg');
    $path1 = Storage::disk('public')->put('galleries', $file1);
    $path2 = Storage::disk('public')->put('galleries', $file2);

    GalleryImage::factory()->create(['gallery_id' => $gallery->id, 'image_path' => $path1]);
    GalleryImage::factory()->create(['gallery_id' => $gallery->id, 'image_path' => $path2]);

    $this->actingAs($user)
        ->delete(route('cms.galleries.destroy', $gallery))
        ->assertRedirect(route('cms.galleries.index'));

    $this->assertDatabaseMissing('galleries', ['id' => $gallery->id]);
    Storage::disk('public')->assertMissing($path1);
    Storage::disk('public')->assertMissing($path2);
});

test('storeImage uploads image and returns json with id, image_url, caption, sort_order', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $gallery = Gallery::factory()->create(['team_id' => $team->id]);
    $file = UploadedFile::fake()->image('test.jpg');

    $response = $this->actingAs($user)
        ->post(route('cms.galleries.images.store', ['gallery' => $gallery]), [
            'image' => $file,
            'caption' => 'A caption',
        ])
        ->assertOk()
        ->assertJsonStructure(['id', 'image_url', 'caption', 'sort_order']);

    $json = $response->json();
    expect($json['caption'])->toBe('A caption');
    expect($json['sort_order'])->toBe(0);

    $image = GalleryImage::find($json['id']);
    expect($image)->not->toBeNull();
    Storage::disk('public')->assertExists($image->image_path);
});

test('destroyImage deletes file from storage and returns 204', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $gallery = Gallery::factory()->create(['team_id' => $team->id]);

    $file = UploadedFile::fake()->image('to-delete.jpg');
    $path = Storage::disk('public')->put('galleries', $file);

    $image = GalleryImage::factory()->create(['gallery_id' => $gallery->id, 'image_path' => $path]);

    $this->actingAs($user)
        ->delete(route('cms.galleries.images.destroy', ['gallery' => $gallery, 'image' => $image]))
        ->assertNoContent();

    $this->assertDatabaseMissing('gallery_images', ['id' => $image->id]);
    Storage::disk('public')->assertMissing($path);
});

test('member gets 403 on galleries index', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('cms.galleries.index'))
        ->assertForbidden();
});
