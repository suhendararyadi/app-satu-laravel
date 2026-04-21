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

function makeEnrollmentContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();

    return [$owner, $team, $classroom];
}

it('enrolls a student into a classroom', function () {
    [$owner, $team, $classroom] = makeEnrollmentContext();
    $student = User::factory()->create();
    // Re-set URL::defaults after creating student (UserFactory::afterCreating overrides it).
    $owner->switchTeam($team);

    $this->actingAs($owner)
        ->post(route('academic.classrooms.enroll', $classroom), [
            'user_id' => $student->id,
            'student_number' => 'NIS001',
        ])
        ->assertRedirect();

    expect($classroom->enrollments()->count())->toBe(1);
    expect($classroom->enrollments()->first()->student_number)->toBe('NIS001');
});

it('validates enroll student rules', function () {
    [$owner, , $classroom] = makeEnrollmentContext();

    $this->actingAs($owner)
        ->post(route('academic.classrooms.enroll', $classroom), [
            'user_id' => 9999,
        ])
        ->assertSessionHasErrors(['user_id']);
});

it('unenrolls a student from a classroom', function () {
    [$owner, $team, $classroom] = makeEnrollmentContext();
    $student = User::factory()->create();
    // Re-set URL::defaults after creating student (UserFactory::afterCreating overrides it).
    $owner->switchTeam($team);
    $enrollment = StudentEnrollment::factory()->for($classroom)->create(['user_id' => $student->id]);

    $this->actingAs($owner)
        ->delete(route('academic.classrooms.unenroll', [$classroom, $enrollment]))
        ->assertRedirect();

    expect($classroom->enrollments()->count())->toBe(0);
});

it('returns 403 when unenrolling from another team classroom', function () {
    [$owner] = makeEnrollmentContext();
    $other = Classroom::factory()->create();
    $enrollment = StudentEnrollment::factory()->for($other)->create();

    $this->actingAs($owner)
        ->delete(route('academic.classrooms.unenroll', [$other, $enrollment]))
        ->assertForbidden();
});

it('returns 403 when enrollment does not belong to classroom', function () {
    [$owner, , $classroom] = makeEnrollmentContext();
    $otherClassroom = Classroom::factory()->for($classroom->team)->for($classroom->academicYear, 'academicYear')->for($classroom->grade, 'grade')->create();
    $enrollment = StudentEnrollment::factory()->for($otherClassroom)->create();

    $this->actingAs($owner)
        ->delete(route('academic.classrooms.unenroll', [$classroom, $enrollment]))
        ->assertForbidden();
});
