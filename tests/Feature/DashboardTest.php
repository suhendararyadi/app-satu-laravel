<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
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
            ->has('data.total_students')
            ->has('data.total_teachers')
            ->has('data.total_classrooms')
            ->has('data.attendance_today.hadir')
            ->has('data.attendance_today.sakit')
            ->has('data.attendance_today.izin')
            ->has('data.attendance_today.alpa')
            ->has('data.attendance_today.date')
            ->has('data.recent_assessments')
        );
});
