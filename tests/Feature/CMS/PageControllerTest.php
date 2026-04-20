<?php

use App\Enums\TeamRole;
use App\Models\Page;
use App\Models\Team;
use App\Models\User;

test('admin sees only their team pages on index', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $ownPage = Page::factory()->create(['team_id' => $team->id]);
    Page::factory()->create(); // different team

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('cms.pages.index'))
        ->assertInertia(fn ($page) => $page
            ->has('pages', 1)
            ->where('pages.0.id', $ownPage->id)
        );
});

test('store creates page with auto-generated slug', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->post(route('cms.pages.store'), [
            'title' => 'My Test Page',
            'content' => 'Some content here',
            'is_published' => true,
            'sort_order' => 0,
        ])
        ->assertRedirect(route('cms.pages.index'));

    $this->assertDatabaseHas('pages', [
        'team_id' => $team->id,
        'title' => 'My Test Page',
        'slug' => 'my-test-page',
    ]);
});

test('store returns 422 when slug already exists for team', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    // Create existing page with same slug
    Page::factory()->create([
        'team_id' => $team->id,
        'title' => 'My Test Page',
        'slug' => 'my-test-page',
    ]);

    $this->actingAs($user)
        ->post(route('cms.pages.store'), [
            'title' => 'My Test Page',
            'content' => 'Some content here',
        ])
        ->assertStatus(422);
});

test('update modifies page data correctly', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $page = Page::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)
        ->patch(route('cms.pages.update', $page), [
            'title' => 'Updated Title',
            'content' => 'Updated content',
            'is_published' => true,
            'sort_order' => 5,
        ])
        ->assertRedirect(route('cms.pages.index'));

    $this->assertDatabaseHas('pages', [
        'id' => $page->id,
        'title' => 'Updated Title',
        'content' => 'Updated content',
        'is_published' => 1,
        'sort_order' => 5,
    ]);
});

test('destroy deletes page', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $page = Page::factory()->create(['team_id' => $team->id]);

    $this->actingAs($user)
        ->delete(route('cms.pages.destroy', $page))
        ->assertRedirect(route('cms.pages.index'));

    $this->assertDatabaseMissing('pages', ['id' => $page->id]);
});

test('member gets 403 on pages index', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('cms.pages.index'))
        ->assertForbidden();
});
