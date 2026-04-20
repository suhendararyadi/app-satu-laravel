<?php

use App\Models\Page;
use App\Models\Team;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('factory creates a page with a team', function () {
    $team = Team::factory()->create();
    $page = Page::factory()->for($team)->create();

    expect($page->team_id)->toBe($team->id)
        ->and($page->title)->not->toBeEmpty()
        ->and($page->slug)->not->toBeEmpty()
        ->and($page->content)->not->toBeEmpty();
});

test('scopePublished returns only published pages', function () {
    $team = Team::factory()->create();

    Page::factory()->for($team)->create(['is_published' => true]);
    Page::factory()->for($team)->create(['is_published' => true]);
    Page::factory()->for($team)->create(['is_published' => false]);

    $publishedPages = Page::published()->get();

    expect($publishedPages)->toHaveCount(2);
    $publishedPages->each(fn ($page) => expect($page->is_published)->toBeTrue());
});

test('unique constraint prevents duplicate slugs within same team', function () {
    $team = Team::factory()->create();

    Page::factory()->for($team)->create(['slug' => 'same-slug']);

    expect(fn () => Page::factory()->for($team)->create(['slug' => 'same-slug']))
        ->toThrow(UniqueConstraintViolationException::class);
});
