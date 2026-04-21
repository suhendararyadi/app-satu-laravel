<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\StudentEnrollment;
use App\Models\Team;
use App\Models\User;

beforeEach(function () {
    $this->withoutVite();
});

function makeStudentSetup(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $student = User::factory()->create();
    $team->members()->attach($student, ['role' => TeamRole::Student->value]);

    $owner->switchTeam($team);

    return [$owner, $team, $student];
}

it('user can load their enrollments', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    StudentEnrollment::factory()->create(['user_id' => $user->id]);
    StudentEnrollment::factory()->create(['user_id' => $other->id]);

    expect($user->enrollments)->toHaveCount(1);
});

it('admin can view students index', function () {
    [$owner] = makeStudentSetup();

    $this->actingAs($owner)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/index')
            ->has('students')
            ->has('classrooms')
        );
});

it('students index only lists role=student members', function () {
    [$owner, $team, $student] = makeStudentSetup();

    $teacher = User::factory()->create();
    $team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);
    $owner->switchTeam($team);

    $this->actingAs($owner)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students', 1)
        );
});

it('admin can remove a student from the team', function () {
    [$owner, $team, $student] = makeStudentSetup();

    // Create a classroom and enrollment for the student
    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();
    StudentEnrollment::create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    $this->actingAs($owner)
        ->delete(route('students.destroy', ['user' => $student->id]))
        ->assertRedirect(route('students.index'));

    expect($team->members()->where('users.id', $student->id)->exists())->toBeFalse();
    expect(StudentEnrollment::where('user_id', $student->id)->exists())->toBeFalse();
});

it('non-admin cannot access students index', function () {
    [$owner, $team, $student] = makeStudentSetup();

    $this->actingAs($student)
        ->get(route('students.index'))
        ->assertForbidden();
});
