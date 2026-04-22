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

    $this->owner = User::factory()->create();
    $this->team = Team::factory()->create();
    $this->team->members()->attach($this->owner, ['role' => TeamRole::Owner->value]);
    $this->owner->switchTeam($this->team);

    $this->student = User::factory()->create();
    $this->team->members()->attach($this->student, ['role' => TeamRole::Student->value]);
    $this->owner->switchTeam($this->team); // reset URL::defaults after student factory resets it
});

it('admin can view edit student page without enrollment', function () {
    $this->actingAs($this->owner)
        ->get(route('students.edit', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/edit')
            ->has('student')
            ->has('classrooms')
            ->where('enrollment', null)
        );
});

it('admin can view edit student page with existing enrollment', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    StudentEnrollment::create([
        'classroom_id' => $classroom->id,
        'user_id' => $this->student->id,
        'student_number' => '99001',
    ]);

    $this->actingAs($this->owner)
        ->get(route('students.edit', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('enrollment.classroom_id', $classroom->id)
            ->where('enrollment.student_number', '99001')
        );
});

it('admin can update student name and email', function () {
    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => 'Nama Baru',
            'email' => 'email.baru@test.test',
        ])
        ->assertRedirect(route('students.index'));

    expect($this->student->fresh()->name)->toBe('Nama Baru');
    expect($this->student->fresh()->email)->toBe('email.baru@test.test');
});

it('admin can assign a classroom to a student during update', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();

    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'classroom_id' => $classroom->id,
            'student_number' => '67890',
        ])
        ->assertRedirect(route('students.index'));

    expect(
        StudentEnrollment::where('user_id', $this->student->id)
            ->where('classroom_id', $classroom->id)
            ->where('student_number', '67890')
            ->exists()
    )->toBeTrue();
});

it('admin can change classroom during update', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $grade = Grade::factory()->for($this->team)->create();
    $oldClassroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    $newClassroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    StudentEnrollment::create(['classroom_id' => $oldClassroom->id, 'user_id' => $this->student->id]);

    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'classroom_id' => $newClassroom->id,
        ])
        ->assertRedirect(route('students.index'));

    expect(StudentEnrollment::where('user_id', $this->student->id)->where('classroom_id', $newClassroom->id)->exists())->toBeTrue();
    expect(StudentEnrollment::where('user_id', $this->student->id)->where('classroom_id', $oldClassroom->id)->exists())->toBeFalse();
});

it('admin can clear classroom assignment during update', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    StudentEnrollment::create(['classroom_id' => $classroom->id, 'user_id' => $this->student->id]);

    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => $this->student->name,
            'email' => $this->student->email,
            'classroom_id' => null,
        ])
        ->assertRedirect(route('students.index'));

    expect(StudentEnrollment::where('user_id', $this->student->id)->exists())->toBeFalse();
});

it('cannot edit a user who is not a student in the team', function () {
    $teacher = User::factory()->create();
    $this->team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);
    $this->owner->switchTeam($this->team); // reset URL::defaults after factory resets it

    $this->actingAs($this->owner)
        ->get(route('students.edit', ['user' => $teacher->id]))
        ->assertNotFound();
});

it('non-admin cannot access edit student page', function () {
    $otherStudent = User::factory()->create();
    $this->team->members()->attach($otherStudent, ['role' => TeamRole::Student->value]);
    $otherStudent->switchTeam($this->team);

    $this->actingAs($otherStudent)
        ->get(route('students.edit', ['user' => $this->student->id]))
        ->assertForbidden();
});

it('rejects update when email already belongs to another user', function () {
    $other = User::factory()->create(['email' => 'taken@test.test']);
    $this->owner->switchTeam($this->team); // reset URL::defaults after factory resets it

    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => $this->student->name,
            'email' => 'taken@test.test',
        ])
        ->assertSessionHasErrors('email');
});

it('allows updating with same email as current user', function () {
    $this->actingAs($this->owner)
        ->patch(route('students.update', ['user' => $this->student->id]), [
            'name' => 'Updated Name',
            'email' => $this->student->email,
        ])
        ->assertRedirect(route('students.index'));

    expect($this->student->fresh()->name)->toBe('Updated Name');
});
