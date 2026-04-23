<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Guardian;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
use App\Models\Team;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('hasSchoolTeam')
        );
});

test('dashboard passes hasSchoolTeam false when user has no school team', function () {
    $user = User::factory()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', false)
        );
});

test('dashboard passes hasSchoolTeam true when user has a school team', function () {
    $user = User::factory()->create();
    $schoolTeam = Team::factory()->create(['is_personal' => false]);

    $schoolTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
        );
});

// ---------------------------------------------------------------------------
// Helper functions for role-based dashboard tests
// ---------------------------------------------------------------------------

/**
 * Create a school team and attach $user with $role, then switch to it.
 *
 * @return array{0: User, 1: Team}
 */
function makeDashboardTeam(TeamRole $role): array
{
    $user = User::factory()->create();
    $team = Team::factory()->create(['is_personal' => false]);
    $team->members()->attach($user, ['role' => $role->value]);
    $user->switchTeam($team);

    return [$user, $team];
}

/**
 * Create an active AcademicYear + active Semester for the given team.
 *
 * @return array{0: AcademicYear, 1: Semester}
 */
function makeActiveYear(Team $team): array
{
    $year = AcademicYear::factory()->for($team)->create(['is_active' => true]);
    $semester = Semester::factory()->for($year, 'academicYear')->create(['is_active' => true]);

    return [$year, $semester];
}

// ---------------------------------------------------------------------------
// Admin / Owner dashboard
// ---------------------------------------------------------------------------

it('returns admin dashboard data for owner', function () {
    [$owner, $team] = makeDashboardTeam(TeamRole::Owner);
    [$year, $semester] = makeActiveYear($team);

    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $student = User::factory()->create();
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    $teacher = User::factory()->create();
    $team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);

    $this->withoutVite()
        ->actingAs($owner)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'owner')
            ->where('data.total_students', 1)
            ->where('data.total_teachers', 1)
            ->where('data.total_classrooms', 1)
            ->has('data.attendance_today.hadir')
            ->has('data.attendance_today.sakit')
            ->has('data.attendance_today.izin')
            ->has('data.attendance_today.alpa')
            ->has('data.attendance_today.date')
            ->has('data.recent_assessments')
        );
});

// ---------------------------------------------------------------------------
// Teacher dashboard
// ---------------------------------------------------------------------------

it('returns teacher dashboard data', function () {
    [$teacher, $team] = makeDashboardTeam(TeamRole::Teacher);
    [$year, $semester] = makeActiveYear($team);

    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $subject = Subject::factory()->for($team)->create();

    TeacherAssignment::factory()->create([
        'team_id' => $team->id,
        'academic_year_id' => $year->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'user_id' => $teacher->id,
    ]);

    $this->withoutVite()
        ->actingAs($teacher)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'teacher')
            ->has('data.my_classrooms', 1)
            ->where('data.my_classrooms.0.name', $classroom->name)
            ->where('data.my_classrooms.0.student_count', 0)
            ->has('data.schedule_today', 0)
            ->has('data.pending_assessments', 0)
        );
});

// ---------------------------------------------------------------------------
// Parent dashboard
// ---------------------------------------------------------------------------

it('returns parent dashboard data', function () {
    [$parent, $team] = makeDashboardTeam(TeamRole::Parent);
    [$year, $semester] = makeActiveYear($team);

    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();

    $studentUser = User::factory()->create();
    $team->members()->attach($studentUser, ['role' => TeamRole::Student->value]);
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $studentUser->id]);

    Guardian::factory()->create([
        'student_id' => $studentUser->id,
        'guardian_id' => $parent->id,
    ]);

    $this->withoutVite()
        ->actingAs($parent)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'parent')
            ->has('data.children', 1)
            ->has('data.children.0.student')
            ->where('data.children.0.student.id', $studentUser->id)
            ->where('data.children.0.student.name', $studentUser->name)
            ->where('data.children.0.student.email', $studentUser->email)
            ->has('data.children.0.classroom')
            ->where('data.children.0.classroom.name', $classroom->name)
            ->where('data.children.0.classroom.grade', $grade->name)
            ->has('data.children.0.recent_scores', 0)
            ->where('data.children.0.attendance_summary.hadir', 0)
            ->where('data.children.0.attendance_summary.sakit', 0)
            ->where('data.children.0.attendance_summary.izin', 0)
            ->where('data.children.0.attendance_summary.alpa', 0)
        );
});

// ---------------------------------------------------------------------------
// Student dashboard
// ---------------------------------------------------------------------------

it('returns student dashboard data', function () {
    [$student, $team] = makeDashboardTeam(TeamRole::Student);
    [$year, $semester] = makeActiveYear($team);

    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    $this->withoutVite()
        ->actingAs($student)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'student')
            ->has('data.classroom')
            ->has('data.classroom.id')
            ->where('data.classroom.name', $classroom->name)
            ->where('data.classroom.grade', $grade->name)
            ->has('data.schedule_today', 0)
            ->has('data.recent_scores', 0)
            ->where('data.attendance_summary.hadir', 0)
            ->where('data.attendance_summary.sakit', 0)
            ->where('data.attendance_summary.izin', 0)
            ->where('data.attendance_summary.alpa', 0)
        );
});

// ---------------------------------------------------------------------------
// Cross-tenant isolation
// ---------------------------------------------------------------------------

it('admin dashboard does not include data from other teams', function () {
    [$owner, $team] = makeDashboardTeam(TeamRole::Owner);
    [$year] = makeActiveYear($team);

    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $student = User::factory()->create();
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    // Another team with its own student and classroom (should NOT appear in $team's data)
    $otherTeam = Team::factory()->create(['is_personal' => false]);
    $otherYear = AcademicYear::factory()->for($otherTeam)->create(['is_active' => true]);
    $otherGrade = Grade::factory()->for($otherTeam)->create();
    $otherClassroom = Classroom::factory()->for($otherTeam)->for($otherYear, 'academicYear')->for($otherGrade)->create();
    $otherStudent = User::factory()->create();
    StudentEnrollment::factory()->create(['classroom_id' => $otherClassroom->id, 'user_id' => $otherStudent->id]);

    $this->withoutVite()
        ->actingAs($owner)
        ->get(route('dashboard', $team))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('data.total_students', 1)
            ->where('data.total_classrooms', 1)
        );
});
