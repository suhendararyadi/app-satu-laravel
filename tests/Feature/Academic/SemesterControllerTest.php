<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Semester;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeSemesterContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);
    $year = AcademicYear::factory()->for($team)->create();

    return [$owner, $team, $year];
}

it('shows create semester form', function () {
    [$owner, , $year] = makeSemesterContext();

    $this->actingAs($owner)
        ->get(route('academic.years.semesters.create', $year))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/semester-create'));
});

it('stores semester', function () {
    [$owner, , $year] = makeSemesterContext();

    $this->actingAs($owner)
        ->post(route('academic.years.semesters.store', $year), [
            'name' => 'Semester 1',
            'order' => 1,
        ])
        ->assertRedirect();

    expect($year->semesters()->count())->toBe(1);
});

it('shows edit semester form', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create();

    $this->actingAs($owner)
        ->get(route('academic.years.semesters.edit', [$year, $semester]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/semester-edit'));
});

it('updates semester', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create(['name' => 'Old']);

    $this->actingAs($owner)
        ->patch(route('academic.years.semesters.update', [$year, $semester]), ['name' => 'New'])
        ->assertRedirect();

    expect($semester->fresh()->name)->toBe('New');
});

it('deletes semester', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create();

    $this->actingAs($owner)
        ->delete(route('academic.years.semesters.destroy', [$year, $semester]))
        ->assertRedirect();

    expect($year->semesters()->count())->toBe(0);
});
