<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeAssignmentContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();
    $subject = Subject::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();

    return [$owner, $team, $year, $subject, $classroom];
}

it('lists teacher assignments', function () {
    [$owner, $team, $year, $subject, $classroom] = makeAssignmentContext();
    $teacher = User::factory()->create();
    $owner->switchTeam($team);
    TeacherAssignment::factory()->create([
        'team_id' => $team->id,
        'academic_year_id' => $year->id,
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'user_id' => $teacher->id,
    ]);

    $this->actingAs($owner)
        ->get(route('academic.assignments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/assignments/index')
            ->has('assignments', 1)
        );
});

it('stores a teacher assignment', function () {
    [$owner, $team, $year, $subject, $classroom] = makeAssignmentContext();
    $teacher = User::factory()->create();
    $owner->switchTeam($team);

    $this->actingAs($owner)
        ->post(route('academic.assignments.store'), [
            'academic_year_id' => $year->id,
            'subject_id' => $subject->id,
            'classroom_id' => $classroom->id,
            'user_id' => $teacher->id,
        ])
        ->assertRedirect();

    expect($team->teacherAssignments()->count())->toBe(1);
});

it('validates store teacher assignment rules', function () {
    [$owner] = makeAssignmentContext();

    $this->actingAs($owner)
        ->post(route('academic.assignments.store'), [])
        ->assertSessionHasErrors(['academic_year_id', 'subject_id', 'classroom_id', 'user_id']);
});

it('destroys a teacher assignment', function () {
    [$owner, $team, $year, $subject, $classroom] = makeAssignmentContext();
    $teacher = User::factory()->create();
    $owner->switchTeam($team);
    $assignment = TeacherAssignment::factory()->create([
        'team_id' => $team->id,
        'academic_year_id' => $year->id,
        'subject_id' => $subject->id,
        'classroom_id' => $classroom->id,
        'user_id' => $teacher->id,
    ]);

    $this->actingAs($owner)
        ->delete(route('academic.assignments.destroy', $assignment))
        ->assertRedirect();

    expect($team->teacherAssignments()->count())->toBe(0);
});

it('returns 403 when destroying assignment from another team', function () {
    [$owner] = makeAssignmentContext();
    $otherAssignment = TeacherAssignment::factory()->create();

    $this->actingAs($owner)
        ->delete(route('academic.assignments.destroy', $otherAssignment))
        ->assertForbidden();
});
