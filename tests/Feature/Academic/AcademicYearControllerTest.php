<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeYearUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('shows academic years index', function () {
    [$owner, $team] = makeYearUser();
    AcademicYear::factory()->for($team)->create(['name' => '2024/2025']);

    $this->actingAs($owner)
        ->get(route('academic.years.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/years/index')
            ->has('years', 1)
        );
});

it('shows create form', function () {
    [$owner] = makeYearUser();

    $this->actingAs($owner)
        ->get(route('academic.years.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/create'));
});

it('stores academic year', function () {
    [$owner, $team] = makeYearUser();

    $this->actingAs($owner)
        ->post(route('academic.years.store'), [
            'name' => '2025/2026',
            'start_year' => 2025,
            'end_year' => 2026,
        ])
        ->assertRedirect();

    expect($team->academicYears()->count())->toBe(1);
});

it('validates store rules', function () {
    [$owner] = makeYearUser();

    $this->actingAs($owner)
        ->post(route('academic.years.store'), [
            'name' => '',
            'start_year' => 2026,
            'end_year' => 2025,
        ])
        ->assertSessionHasErrors(['name', 'end_year']);
});

it('shows edit form', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create();

    $this->actingAs($owner)
        ->get(route('academic.years.edit', $year))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/edit')->has('year'));
});

it('updates academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create(['name' => 'Old Name']);

    $this->actingAs($owner)
        ->patch(route('academic.years.update', $year), ['name' => 'New Name'])
        ->assertRedirect();

    expect($year->fresh()->name)->toBe('New Name');
});

it('deletes academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('academic.years.destroy', $year))
        ->assertRedirect(route('academic.years.index'));

    expect($team->academicYears()->count())->toBe(0);
});

it('activates academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create(['is_active' => false]);

    $this->actingAs($owner)
        ->post(route('academic.years.activate', $year))
        ->assertRedirect();

    expect($year->fresh()->is_active)->toBeTrue();
});

it('returns 403 for year belonging to another team', function () {
    [$owner] = makeYearUser();
    $other = AcademicYear::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.years.edit', $other))
        ->assertForbidden();
});
