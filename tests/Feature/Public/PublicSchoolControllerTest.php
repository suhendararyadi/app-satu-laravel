<?php

use App\Models\Gallery;
use App\Models\Page;
use App\Models\Post;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->team = Team::factory()->school()->create();
});

it('home returns 200 for school team', function () {
    $this->withoutVite()
        ->get(route('public.school.home', ['team' => $this->team->slug]))
        ->assertStatus(200);
});

it('home returns 404 for personal team', function () {
    $personalTeam = Team::factory()->personal()->create();

    $this->withoutVite()
        ->get(route('public.school.home', ['team' => $personalTeam->slug]))
        ->assertStatus(404);
});

it('page returns 200 for published page', function () {
    $page = Page::factory()->create([
        'team_id' => $this->team->id,
        'is_published' => true,
        'slug' => 'test-page',
    ]);

    $this->withoutVite()
        ->get(route('public.school.page', ['team' => $this->team->slug, 'page' => $page->slug]))
        ->assertStatus(200);
});

it('page returns 404 for unpublished page', function () {
    $page = Page::factory()->create([
        'team_id' => $this->team->id,
        'is_published' => false,
        'slug' => 'test-page',
    ]);

    $this->withoutVite()
        ->get(route('public.school.page', ['team' => $this->team->slug, 'page' => $page->slug]))
        ->assertStatus(404);
});

it('news index returns 200', function () {
    $this->withoutVite()
        ->get(route('public.school.news', ['team' => $this->team->slug]))
        ->assertStatus(200);
});

it('post show returns 200 for published post', function () {
    $post = Post::factory()->published()->create([
        'team_id' => $this->team->id,
        'author_id' => $this->user->id,
        'slug' => 'test-post',
    ]);

    $this->withoutVite()
        ->get(route('public.school.post', ['team' => $this->team->slug, 'post' => $post->slug]))
        ->assertStatus(200);
});

it('post show returns 404 for unpublished post', function () {
    $post = Post::factory()->create([
        'team_id' => $this->team->id,
        'author_id' => $this->user->id,
        'is_published' => false,
        'slug' => 'test-post',
    ]);

    $this->withoutVite()
        ->get(route('public.school.post', ['team' => $this->team->slug, 'post' => $post->slug]))
        ->assertStatus(404);
});

it('gallery index returns 200', function () {
    $this->withoutVite()
        ->get(route('public.school.gallery', ['team' => $this->team->slug]))
        ->assertStatus(200);
});

it('gallery detail returns 200 for published gallery', function () {
    $gallery = Gallery::factory()->create([
        'team_id' => $this->team->id,
        'is_published' => true,
    ]);

    $this->withoutVite()
        ->get(route('public.school.gallery.detail', ['team' => $this->team->slug, 'gallery' => $gallery->id]))
        ->assertStatus(200);
});

it('gallery detail returns 404 for unpublished gallery', function () {
    $gallery = Gallery::factory()->create([
        'team_id' => $this->team->id,
        'is_published' => false,
    ]);

    $this->withoutVite()
        ->get(route('public.school.gallery.detail', ['team' => $this->team->slug, 'gallery' => $gallery->id]))
        ->assertStatus(404);
});

it('contact returns 200', function () {
    $this->withoutVite()
        ->get(route('public.school.contact', ['team' => $this->team->slug]))
        ->assertStatus(200);
});
