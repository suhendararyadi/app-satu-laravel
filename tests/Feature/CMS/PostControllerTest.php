<?php

use App\Enums\TeamRole;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('admin sees only their team posts on index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $ownPost = Post::factory()->create(['team_id' => $team->id, 'author_id' => $user->id]);
    Post::factory()->create(['author_id' => $user->id]); // different team

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('cms.posts.index'))
        ->assertInertia(fn ($page) => $page
            ->has('posts', 1)
            ->where('posts.0.id', $ownPost->id)
        );
});

test('store creates post with auto-generated slug and author_id', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->post(route('cms.posts.store'), [
            'title' => 'My Test Post',
            'content' => 'Some content here',
            'is_published' => false,
        ])
        ->assertRedirect(route('cms.posts.index'));

    $this->assertDatabaseHas('posts', [
        'team_id' => $team->id,
        'author_id' => $user->id,
        'title' => 'My Test Post',
        'slug' => 'my-test-post',
    ]);
});

test('store returns 422 when slug already exists for team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    Post::factory()->create([
        'team_id' => $team->id,
        'author_id' => $user->id,
        'title' => 'My Test Post',
        'slug' => 'my-test-post',
    ]);

    $this->actingAs($user)
        ->post(route('cms.posts.store'), [
            'title' => 'My Test Post',
            'content' => 'Some content here',
        ])
        ->assertStatus(422);
});

test('store uploads featured image and saves path', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;
    $file = UploadedFile::fake()->image('test.jpg');

    $this->actingAs($user)
        ->post(route('cms.posts.store'), [
            'title' => 'Post With Image',
            'content' => 'Some content here',
            'featured_image' => $file,
        ])
        ->assertRedirect(route('cms.posts.index'));

    $post = $team->posts()->where('title', 'Post With Image')->first();
    expect($post->featured_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($post->featured_image_path);
});

test('update modifies post data and regenerates slug when title changes', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'author_id' => $user->id,
        'title' => 'Original Title',
        'slug' => 'original-title',
    ]);

    $this->actingAs($user)
        ->patch(route('cms.posts.update', $post), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'is_published' => true,
        ])
        ->assertRedirect(route('cms.posts.index'));

    $this->assertDatabaseHas('posts', [
        'id' => $post->id,
        'title' => 'Updated Title',
        'slug' => 'updated-title',
        'is_published' => 1,
    ]);
});

test('update with new featured image deletes old image and stores new one', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $oldFile = UploadedFile::fake()->image('old.jpg');
    $oldPath = Storage::disk('public')->put('posts', $oldFile);

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'author_id' => $user->id,
        'featured_image_path' => $oldPath,
    ]);

    $newFile = UploadedFile::fake()->image('new.jpg');

    $this->actingAs($user)
        ->patch(route('cms.posts.update', $post), [
            'title' => $post->title,
            'content' => $post->content,
            'featured_image' => $newFile,
        ])
        ->assertRedirect(route('cms.posts.index'));

    Storage::disk('public')->assertMissing($oldPath);

    $post->refresh();
    expect($post->featured_image_path)->not->toBeNull();
    Storage::disk('public')->assertExists($post->featured_image_path);
});

test('destroy deletes post and removes featured image from storage', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $file = UploadedFile::fake()->image('to-delete.jpg');
    $imagePath = Storage::disk('public')->put('posts', $file);

    $post = Post::factory()->create([
        'team_id' => $team->id,
        'author_id' => $user->id,
        'featured_image_path' => $imagePath,
    ]);

    $this->actingAs($user)
        ->delete(route('cms.posts.destroy', $post))
        ->assertRedirect(route('cms.posts.index'));

    $this->assertDatabaseMissing('posts', ['id' => $post->id]);
    Storage::disk('public')->assertMissing($imagePath);
});

test('member gets 403 on posts index', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('cms.posts.index'))
        ->assertForbidden();
});
