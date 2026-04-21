<?php

use App\Enums\TeamRole;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeSubjectUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('shows subjects index', function () {
    [$owner, $team] = makeSubjectUser();
    Subject::factory()->for($team)->create(['name' => 'Matematika']);

    $this->actingAs($owner)
        ->get(route('academic.subjects.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/subjects/index')
            ->has('subjects', 1)
        );
});

it('shows create subject form', function () {
    [$owner] = makeSubjectUser();

    $this->actingAs($owner)
        ->get(route('academic.subjects.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/subjects/create'));
});

it('stores subject', function () {
    [$owner, $team] = makeSubjectUser();

    $this->actingAs($owner)
        ->post(route('academic.subjects.store'), [
            'name' => 'Matematika',
            'code' => 'MTK',
        ])
        ->assertRedirect();

    expect($team->subjects()->count())->toBe(1);
});

it('validates subject store rules', function () {
    [$owner] = makeSubjectUser();

    $this->actingAs($owner)
        ->post(route('academic.subjects.store'), ['name' => ''])
        ->assertSessionHasErrors(['name']);
});

it('shows edit subject form', function () {
    [$owner, $team] = makeSubjectUser();
    $subject = Subject::factory()->for($team)->create();

    $this->actingAs($owner)
        ->get(route('academic.subjects.edit', $subject))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/subjects/edit')->has('subject'));
});

it('updates subject', function () {
    [$owner, $team] = makeSubjectUser();
    $subject = Subject::factory()->for($team)->create(['name' => 'Old']);

    $this->actingAs($owner)
        ->patch(route('academic.subjects.update', $subject), ['name' => 'New'])
        ->assertRedirect();

    expect($subject->fresh()->name)->toBe('New');
});

it('deletes subject', function () {
    [$owner, $team] = makeSubjectUser();
    $subject = Subject::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('academic.subjects.destroy', $subject))
        ->assertRedirect(route('academic.subjects.index'));

    expect($team->subjects()->count())->toBe(0);
});

it('returns 403 for subject belonging to another team', function () {
    [$owner] = makeSubjectUser();
    $other = Subject::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.subjects.edit', $other))
        ->assertForbidden();
});
