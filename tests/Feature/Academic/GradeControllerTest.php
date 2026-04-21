<?php

use App\Enums\TeamRole;
use App\Models\Academic\Grade;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeGradeUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('shows grades index', function () {
    [$owner, $team] = makeGradeUser();
    Grade::factory()->for($team)->create(['name' => 'Kelas X']);

    $this->actingAs($owner)
        ->get(route('academic.grades.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/grades/index')
            ->has('grades', 1)
        );
});

it('shows create grade form', function () {
    [$owner] = makeGradeUser();

    $this->actingAs($owner)
        ->get(route('academic.grades.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/grades/create'));
});

it('stores grade', function () {
    [$owner, $team] = makeGradeUser();

    $this->actingAs($owner)
        ->post(route('academic.grades.store'), [
            'name' => 'Kelas X',
            'order' => 1,
        ])
        ->assertRedirect();

    expect($team->grades()->count())->toBe(1);
});

it('validates grade store rules', function () {
    [$owner] = makeGradeUser();

    $this->actingAs($owner)
        ->post(route('academic.grades.store'), [
            'name' => '',
            'order' => 0,
        ])
        ->assertSessionHasErrors(['name', 'order']);
});

it('shows edit grade form', function () {
    [$owner, $team] = makeGradeUser();
    $grade = Grade::factory()->for($team)->create();

    $this->actingAs($owner)
        ->get(route('academic.grades.edit', $grade))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/grades/edit')->has('grade'));
});

it('updates grade', function () {
    [$owner, $team] = makeGradeUser();
    $grade = Grade::factory()->for($team)->create(['name' => 'Old']);

    $this->actingAs($owner)
        ->patch(route('academic.grades.update', $grade), ['name' => 'New'])
        ->assertRedirect();

    expect($grade->fresh()->name)->toBe('New');
});

it('deletes grade', function () {
    [$owner, $team] = makeGradeUser();
    $grade = Grade::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('academic.grades.destroy', $grade))
        ->assertRedirect(route('academic.grades.index'));

    expect($team->grades()->count())->toBe(0);
});

it('returns 403 for grade belonging to another team', function () {
    [$owner] = makeGradeUser();
    $other = Grade::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.grades.edit', $other))
        ->assertForbidden();
});
