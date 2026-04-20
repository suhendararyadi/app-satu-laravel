<?php

use App\Models\Post;
use App\Models\Team;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates a post with team and author', function () {
    $team = Team::factory()->create();
    $author = User::factory()->create();
    $post = Post::factory()->for($team)->for($author, 'author')->create();

    expect($post->team_id)->toBe($team->id)
        ->and($post->author_id)->toBe($author->id)
        ->and($post->title)->not->toBeEmpty()
        ->and($post->slug)->not->toBeEmpty()
        ->and($post->content)->not->toBeEmpty();
});

test('scopePublished returns only published posts', function () {
    $team = Team::factory()->create();
    $author = User::factory()->create();

    Post::factory()->for($team)->for($author, 'author')->create(['is_published' => true]);
    Post::factory()->for($team)->for($author, 'author')->create(['is_published' => true]);
    Post::factory()->for($team)->for($author, 'author')->create(['is_published' => false]);

    $publishedPosts = Post::published()->get();

    expect($publishedPosts)->toHaveCount(2);
    $publishedPosts->each(fn ($post) => expect($post->is_published)->toBeTrue());
});

test('author relationship returns the correct user model', function () {
    $author = User::factory()->create();
    $post = Post::factory()->for($author, 'author')->create();

    expect($post->author)->toBeInstanceOf(User::class)
        ->and($post->author->id)->toBe($author->id);
});
