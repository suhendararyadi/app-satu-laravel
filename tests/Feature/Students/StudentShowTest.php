<?php

use App\Enums\AttendanceStatus;
use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Guardian;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
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

it('admin can view student show page', function () {
    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/show')
            ->has('student')
            ->has('enrollment')
            ->has('attendance_summary')
            ->has('attendance_records')
            ->has('guardians')
        );
});

it('page returns correct student data', function () {
    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('student.id', $this->student->id)
            ->where('student.name', $this->student->name)
            ->where('student.email', $this->student->email)
        );
});

it('enrollment is null when student has no enrollment', function () {
    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('enrollment', null)
        );
});

it('page returns enrollment data when student is enrolled', function () {
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
        'student_number' => '12001',
    ]);

    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('enrollment.classroom_name', $classroom->name)
            ->where('enrollment.student_number', '12001')
            ->where('enrollment.grade_name', $grade->name)
            ->where('enrollment.academic_year_name', $year->name)
        );
});

it('attendance summary always has all four statuses', function () {
    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('attendance_summary', 4)
            ->where('attendance_summary.0.status', 'hadir')
            ->where('attendance_summary.1.status', 'sakit')
            ->where('attendance_summary.2.status', 'izin')
            ->where('attendance_summary.3.status', 'alpa')
        );
});

it('attendance summary counts are correct', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $semester = Semester::factory()->for($year, 'academicYear')->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    $attendance = Attendance::factory()
        ->for($this->team)
        ->for($classroom)
        ->for($semester)
        ->create();

    AttendanceRecord::factory()->count(3)->create([
        'attendance_id' => $attendance->id,
        'student_user_id' => $this->student->id,
        'status' => AttendanceStatus::Hadir->value,
    ]);
    AttendanceRecord::factory()->count(2)->create([
        'attendance_id' => $attendance->id,
        'student_user_id' => $this->student->id,
        'status' => AttendanceStatus::Alpa->value,
    ]);

    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('attendance_summary.0.status', 'hadir')
            ->where('attendance_summary.0.count', 3)
            ->where('attendance_summary.3.status', 'alpa')
            ->where('attendance_summary.3.count', 2)
        );
});

it('attendance records are paginated', function () {
    $year = AcademicYear::factory()->for($this->team)->create();
    $semester = Semester::factory()->for($year, 'academicYear')->create();
    $grade = Grade::factory()->for($this->team)->create();
    $classroom = Classroom::factory()
        ->for($this->team)
        ->for($year, 'academicYear')
        ->for($grade, 'grade')
        ->create();
    $attendance = Attendance::factory()
        ->for($this->team)
        ->for($classroom)
        ->for($semester)
        ->create();

    AttendanceRecord::factory()->count(20)->create([
        'attendance_id' => $attendance->id,
        'student_user_id' => $this->student->id,
    ]);

    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('attendance_records.total', 20)
            ->where('attendance_records.per_page', 15)
            ->has('attendance_records.data', 15)
        );
});

it('returns guardians when present', function () {
    $guardian = User::factory()->create(['name' => 'Bapak Wali', 'email' => 'bapak@test.test']);
    $this->owner->switchTeam($this->team); // reset URL::defaults after factory resets it

    Guardian::create([
        'student_id' => $this->student->id,
        'guardian_id' => $guardian->id,
        'relationship' => 'ayah',
    ]);

    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $this->student->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('guardians', 1)
            ->where('guardians.0.name', 'Bapak Wali')
            ->where('guardians.0.email', 'bapak@test.test')
            ->where('guardians.0.relationship_label', 'Ayah')
        );
});

it('returns 404 for a user who is not a student of this team', function () {
    $nonStudent = User::factory()->create();
    $this->team->members()->attach($nonStudent, ['role' => TeamRole::Owner->value]);
    $this->owner->switchTeam($this->team); // reset URL::defaults after factory resets it

    $this->actingAs($this->owner)
        ->get(route('students.show', ['user' => $nonStudent->id]))
        ->assertNotFound();
});
