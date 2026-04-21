<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeClassroomUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

function makeClassroomContext(): array
{
    [$owner, $team] = makeClassroomUser();
    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();

    return [$owner, $team, $year, $grade, $classroom];
}

it('shows classrooms index', function () {
    [$owner, $team] = makeClassroomUser();

    $this->actingAs($owner)
        ->get(route('academic.classrooms.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/classrooms/index')
            ->has('classrooms')
        );
});

it('shows create classroom form', function () {
    [$owner] = makeClassroomUser();

    $this->actingAs($owner)
        ->get(route('academic.classrooms.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/classrooms/create')
            ->has('academicYears')
            ->has('grades')
        );
});

it('stores classroom', function () {
    [$owner, $team] = makeClassroomUser();
    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();

    $this->actingAs($owner)
        ->post(route('academic.classrooms.store'), [
            'name' => 'X-A',
            'academic_year_id' => $year->id,
            'grade_id' => $grade->id,
        ])
        ->assertRedirect();

    expect($team->classrooms()->count())->toBe(1);
});

it('validates classroom store rules', function () {
    [$owner] = makeClassroomUser();

    $this->actingAs($owner)
        ->post(route('academic.classrooms.store'), [
            'name' => '',
            'academic_year_id' => 9999,
            'grade_id' => 9999,
        ])
        ->assertSessionHasErrors(['name', 'academic_year_id', 'grade_id']);
});

it('shows classroom detail', function () {
    [$owner, , , , $classroom] = makeClassroomContext();

    $this->actingAs($owner)
        ->get(route('academic.classrooms.show', $classroom))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/classrooms/show')
            ->has('classroom')
            ->has('students')
        );
});

it('shows edit classroom form', function () {
    [$owner, , , , $classroom] = makeClassroomContext();

    $this->actingAs($owner)
        ->get(route('academic.classrooms.edit', $classroom))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/classrooms/edit')
            ->has('classroom')
            ->has('academicYears')
            ->has('grades')
        );
});

it('updates classroom', function () {
    [$owner, , , , $classroom] = makeClassroomContext();

    $this->actingAs($owner)
        ->patch(route('academic.classrooms.update', $classroom), ['name' => 'X-B'])
        ->assertRedirect();

    expect($classroom->fresh()->name)->toBe('X-B');
});

it('deletes classroom', function () {
    [$owner, $team, , , $classroom] = makeClassroomContext();

    $this->actingAs($owner)
        ->delete(route('academic.classrooms.destroy', $classroom))
        ->assertRedirect(route('academic.classrooms.index'));

    expect($team->classrooms()->count())->toBe(0);
});

it('returns 403 for classroom belonging to another team', function () {
    [$owner] = makeClassroomUser();
    $other = Classroom::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.classrooms.show', $other))
        ->assertForbidden();
});
