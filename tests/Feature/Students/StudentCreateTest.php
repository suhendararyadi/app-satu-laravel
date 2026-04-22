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
});

it('admin can view create student page', function () {
    $this->actingAs($this->owner)
        ->get(route('students.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/create')
            ->has('classrooms')
        );
});

it('admin can create a student without classroom', function () {
    $this->actingAs($this->owner)
        ->post(route('students.store'), [
            'name' => 'Budi Siswa',
            'email' => 'budi.siswa@test.test',
            'password' => 'password123',
        ])
        ->assertRedirect(route('students.index'));

    $student = User::where('email', 'budi.siswa@test.test')->first();
    expect($student)->not->toBeNull();
    expect(
        $this->team->members()
            ->where('users.id', $student->id)
            ->wherePivot('role', TeamRole::Student->value)
            ->exists()
    )->toBeTrue();
    expect(StudentEnrollment::where('user_id', $student->id)->exists())->toBeFalse();
});

it('admin can create a student with classroom and NIS', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();

    $this->actingAs($this->owner)
        ->post(route('students.store'), [
            'name' => 'Sari Siswi',
            'email' => 'sari.siswi@test.test',
            'password' => 'password123',
            'student_number' => '12345',
            'classroom_id' => $classroom->id,
        ])
        ->assertRedirect(route('students.index'));

    $student = User::where('email', 'sari.siswi@test.test')->first();
    expect(
        StudentEnrollment::where('user_id', $student->id)
            ->where('classroom_id', $classroom->id)
            ->where('student_number', '12345')
            ->exists()
    )->toBeTrue();
});

it('rejects duplicate email when creating student', function () {
    User::factory()->create(['email' => 'existing@test.test']);
    $this->owner->switchTeam($this->team); // reset URL::defaults after factory resets it

    $this->actingAs($this->owner)
        ->post(route('students.store'), [
            'name' => 'Duplikat',
            'email' => 'existing@test.test',
            'password' => 'password123',
        ])
        ->assertSessionHasErrors('email');
});

it('rejects password shorter than 8 characters', function () {
    $this->actingAs($this->owner)
        ->post(route('students.store'), [
            'name' => 'Budi',
            'email' => 'budi@test.test',
            'password' => 'short',
        ])
        ->assertSessionHasErrors('password');
});

it('rejects store when name is missing', function () {
    $this->actingAs($this->owner)
        ->post(route('students.store'), [
            'email' => 'budi@test.test',
            'password' => 'password123',
        ])
        ->assertSessionHasErrors('name');
});

it('non-admin cannot access create student page', function () {
    $student = User::factory()->create();
    $this->team->members()->attach($student, ['role' => TeamRole::Student->value]);
    $student->switchTeam($this->team);

    $this->actingAs($student)
        ->get(route('students.create'))
        ->assertForbidden();
});
