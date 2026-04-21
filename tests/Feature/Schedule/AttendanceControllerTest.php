<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeAttendanceContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $year = AcademicYear::factory()->for($team)->create(['is_active' => true]);
    $semester = Semester::factory()->for($year, 'academicYear')->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $subject = Subject::factory()->for($team)->create();

    $student = User::factory()->create();
    StudentEnrollment::factory()->create([
        'classroom_id' => $classroom->id,
        'user_id' => $student->id,
    ]);

    // Re-switch to restore URL::defaults after UserFactory::afterCreating resets it
    $owner->switchTeam($team);

    return [$owner, $team, $semester, $classroom, $subject, $student];
}

it('lists attendances', function () {
    [$owner, $team, $semester, $classroom] = makeAttendanceContext();

    Attendance::factory()->create([
        'team_id' => $team->id,
        'classroom_id' => $classroom->id,
        'semester_id' => $semester->id,
        'recorded_by' => $owner->id,
        'date' => '2026-04-21',
    ]);

    $this->actingAs($owner)
        ->get(route('schedule.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/index')
            ->has('attendances')
        );
});

it('shows create attendance form', function () {
    [$owner] = makeAttendanceContext();

    $this->actingAs($owner)
        ->get(route('schedule.attendance.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/create')
            ->has('classrooms')
            ->has('semesters')
            ->has('subjects')
        );
});

it('stores attendance with records', function () {
    [$owner, $team, $semester, $classroom, $subject, $student] = makeAttendanceContext();

    $this->actingAs($owner)
        ->post(route('schedule.attendance.store'), [
            'classroom_id' => $classroom->id,
            'date' => '2026-04-21',
            'subject_id' => null,
            'semester_id' => $semester->id,
            'records' => [
                ['student_user_id' => $student->id, 'status' => 'hadir', 'notes' => null],
            ],
        ])
        ->assertRedirect();

    expect(Attendance::where('team_id', $team->id)->count())->toBe(1);
    expect(AttendanceRecord::count())->toBe(1);
});

it('validates attendance store rules', function () {
    [$owner] = makeAttendanceContext();

    $this->actingAs($owner)
        ->post(route('schedule.attendance.store'), [])
        ->assertSessionHasErrors(['classroom_id', 'date', 'semester_id', 'records']);
});

it('shows attendance detail', function () {
    [$owner, $team, $semester, $classroom] = makeAttendanceContext();
    $attendance = Attendance::factory()->create([
        'team_id' => $team->id,
        'classroom_id' => $classroom->id,
        'semester_id' => $semester->id,
        'recorded_by' => $owner->id,
        'date' => '2026-04-21',
    ]);

    $this->actingAs($owner)
        ->get(route('schedule.attendance.show', $attendance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/show')
            ->has('attendance')
        );
});

it('returns 403 for attendance belonging to another team', function () {
    [$owner] = makeAttendanceContext();
    $other = Attendance::factory()->create();

    $this->actingAs($owner)
        ->get(route('schedule.attendance.show', $other))
        ->assertForbidden();
});
