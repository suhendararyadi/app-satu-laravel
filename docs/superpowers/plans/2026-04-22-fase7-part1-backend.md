# Fase 7: Dashboard Role-Based — Part 1: Backend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build four role-aware service classes and update DashboardController to return role-specific data for Owner/Admin, Teacher, Student, and Parent roles.

**Architecture:** Four service classes in `app/Services/Dashboard/`, each with a `get(User $user, Team $team): array` method. DashboardController detects the user's role on their current team and delegates to the correct service. When the current team is personal, the existing `hasSchoolTeam` behaviour is preserved.

**Tech Stack:** Laravel 13, Eloquent, Pest 4, SQLite in-memory tests.

---

## File Map

**New:**
- `app/Services/Dashboard/AdminDashboardData.php` — Owner/Admin stats
- `app/Services/Dashboard/TeacherDashboardData.php` — Teacher data
- `app/Services/Dashboard/StudentDashboardData.php` — Student data
- `app/Services/Dashboard/ParentDashboardData.php` — Parent data

**Modified:**
- `app/Http/Controllers/DashboardController.php` — role routing
- `tests/Feature/DashboardTest.php` — new role-based tests appended (4 existing tests must keep passing)

---

## Schema Notes (verified from DB)

- `classrooms` — no `homeroom_teacher_id`; only `team_id`, `academic_year_id`, `grade_id`, `name`
- `teacher_assignments` — uses `academic_year_id` (not `semester_id`) for scoping
- `assessments` — uses `semester_id` for scoping; has `teacher_user_id`
- `guardians` — columns are `student_id` (FK to users), `guardian_id` (FK to users), `relationship`; **no** `team_id`; parent-to-team scoping is done via `StudentEnrollment`
- `schedules` — `day_of_week` stored as Indonesian string: `'Senin'`, `'Selasa'`, `'Rabu'`, `'Kamis'`, `'Jumat'`, `'Sabtu'`; mapping from PHP `now()->dayOfWeek` (0=Minggu): `$days = ['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu']`
- `attendance_records` — `status` cast to `AttendanceStatus` enum, but raw strings appear when using `->pluck('count', 'status')` on a `select()` query
- `team_members` table — `Membership` model; `$team->memberships()` is `HasMany<Membership>` scoped by `team_id`

---

## Task 1: AdminDashboardData + DashboardController backbone

**Files:**
- Create: `app/Services/Dashboard/AdminDashboardData.php`
- Modify: `app/Http/Controllers/DashboardController.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Add helper functions and admin test to DashboardTest.php**

Open `tests/Feature/DashboardTest.php`. The file currently has 4 tests. Append the following **after** the last test (do not remove any existing tests). Also add the new `use` imports at the top of the file alongside the existing ones:

**New imports to add at top:**
```php
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
```

**Append to end of file:**
```php

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
        ->get(route('dashboard'))
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
```

- [ ] **Step 2: Run the new test to confirm it fails**

```bash
./vendor/bin/pest --filter "returns admin dashboard data for owner" --compact
```

Expected: FAIL (DashboardController does not return `role`/`data` props yet)

- [ ] **Step 3: Create the service directory and AdminDashboardData**

```bash
mkdir -p app/Services/Dashboard
```

Create `app/Services/Dashboard/AdminDashboardData.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardData
{
    public function get(User $user, Team $team): array
    {
        $activeYear = AcademicYear::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeYear
            ? Semester::where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        $totalStudents = $activeYear
            ? StudentEnrollment::whereHas(
                'classroom',
                fn ($q) => $q->where('academic_year_id', $activeYear->id)
                    ->where('team_id', $team->id)
            )->count()
            : 0;

        $totalTeachers = $team->memberships()
            ->where('role', TeamRole::Teacher->value)
            ->count();

        $totalClassrooms = $activeYear
            ? $team->classrooms()->where('academic_year_id', $activeYear->id)->count()
            : 0;

        $today = now()->toDateString();
        $attendanceCounts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        if ($activeSemester) {
            $attendanceIds = Attendance::where('team_id', $team->id)
                ->whereDate('date', $today)
                ->where('semester_id', $activeSemester->id)
                ->pluck('id');

            if ($attendanceIds->isNotEmpty()) {
                $statusCounts = AttendanceRecord::whereIn('attendance_id', $attendanceIds)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');

                foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                    $attendanceCounts[$status] = (int) ($statusCounts[$status] ?? 0);
                }
            }
        }

        $recentAssessments = [];

        if ($activeSemester) {
            $recentAssessments = Assessment::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->with(['classroom:id,name', 'subject:id,name'])
                ->latest('date')
                ->take(5)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'classroom' => $a->classroom?->name,
                    'subject' => $a->subject?->name,
                    'date' => $a->date?->toDateString(),
                ])
                ->toArray();
        }

        return [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classrooms' => $totalClassrooms,
            'attendance_today' => array_merge($attendanceCounts, ['date' => $today]),
            'recent_assessments' => $recentAssessments,
        ];
    }
}
```

- [ ] **Step 4: Update DashboardController**

Replace `app/Http/Controllers/DashboardController.php` entirely:

```php
<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Services\Dashboard\AdminDashboardData;
use App\Services\Dashboard\ParentDashboardData;
use App\Services\Dashboard\StudentDashboardData;
use App\Services\Dashboard\TeacherDashboardData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        // Personal team or no team: preserve original hasSchoolTeam behaviour
        if (! $team || $team->is_personal) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => $user->teams()->where('is_personal', false)->exists(),
            ]);
        }

        $role = $user->teamRole($team);

        if (! $role) {
            return Inertia::render('dashboard', ['hasSchoolTeam' => true]);
        }

        if ($role->level() >= TeamRole::Admin->level()) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new AdminDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Teacher) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new TeacherDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Student) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new StudentDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Parent) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new ParentDashboardData)->get($user, $team),
            ]);
        }

        return Inertia::render('dashboard', ['hasSchoolTeam' => true]);
    }
}
```

Note: `TeacherDashboardData`, `StudentDashboardData`, `ParentDashboardData` do not exist yet — PHP will throw a class-not-found only if those branches are reached. The admin test will not hit them.

- [ ] **Step 5: Run admin test to verify it passes**

```bash
./vendor/bin/pest --filter "returns admin dashboard data for owner" --compact
```

Expected: PASS

- [ ] **Step 6: Run all four original dashboard tests to verify backward compatibility**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected: 5 tests pass (4 original + 1 new admin test)

- [ ] **Step 7: Run pint**

```bash
./vendor/bin/pint app/Services/Dashboard/AdminDashboardData.php app/Http/Controllers/DashboardController.php --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Services/Dashboard/AdminDashboardData.php app/Http/Controllers/DashboardController.php tests/Feature/DashboardTest.php
git commit -m "feat: add AdminDashboardData service and update DashboardController for role routing"
```

---

## Task 2: TeacherDashboardData service

**Files:**
- Create: `app/Services/Dashboard/TeacherDashboardData.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Write failing teacher test**

Add these imports to `tests/Feature/DashboardTest.php` if not already present:
```php
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
```

Append to end of `tests/Feature/DashboardTest.php`:

```php

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
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'teacher')
            ->has('data.my_classrooms')
            ->has('data.schedule_today')
            ->has('data.pending_assessments')
        );
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
./vendor/bin/pest --filter "returns teacher dashboard data" --compact
```

Expected: FAIL (`TeacherDashboardData` class does not exist)

- [ ] **Step 3: Create TeacherDashboardData service**

Create `app/Services/Dashboard/TeacherDashboardData.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\TeacherAssignment;
use App\Models\Schedule\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherDashboardData
{
    public function get(User $user, Team $team): array
    {
        $activeYear = AcademicYear::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeYear
            ? Semester::where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        // Unique classrooms this teacher is assigned to in the active academic year
        $myClassrooms = [];

        if ($activeYear) {
            $assignments = TeacherAssignment::where('team_id', $team->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('user_id', $user->id)
                ->with(['classroom.grade'])
                ->get()
                ->unique('classroom_id');

            $classroomIds = $assignments->pluck('classroom_id')->unique()->filter()->values();

            $enrollmentCounts = StudentEnrollment::whereIn('classroom_id', $classroomIds)
                ->select('classroom_id', DB::raw('count(*) as count'))
                ->groupBy('classroom_id')
                ->pluck('count', 'classroom_id');

            $myClassrooms = $assignments
                ->map(fn ($a) => [
                    'id' => $a->classroom->id,
                    'name' => $a->classroom->name,
                    'grade' => $a->classroom->grade?->name,
                    'student_count' => (int) ($enrollmentCounts[$a->classroom_id] ?? 0),
                ])
                ->values()
                ->toArray();
        }

        // Today's schedule for this teacher in the active semester
        $scheduleToday = [];

        if ($activeSemester) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $todayName = $days[now()->dayOfWeek];

            $scheduleToday = Schedule::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('teacher_user_id', $user->id)
                ->where('day_of_week', $todayName)
                ->with(['classroom:id,name', 'subject:id,name', 'timeSlot'])
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'classroom' => $s->classroom?->name,
                    'subject' => $s->subject?->name,
                    'room' => $s->room,
                    'time_slot' => $s->timeSlot
                        ? $s->timeSlot->start_time.' - '.$s->timeSlot->end_time
                        : null,
                ])
                ->toArray();
        }

        // Assessments where scored count < enrolled student count
        $pendingAssessments = [];

        if ($activeSemester) {
            $assessments = Assessment::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('teacher_user_id', $user->id)
                ->with(['classroom:id,name', 'subject:id,name'])
                ->withCount('scores')
                ->get();

            $classroomIds = $assessments->pluck('classroom_id')->unique()->filter()->values();

            $enrollmentCounts = StudentEnrollment::whereIn('classroom_id', $classroomIds)
                ->select('classroom_id', DB::raw('count(*) as count'))
                ->groupBy('classroom_id')
                ->pluck('count', 'classroom_id');

            $pendingAssessments = $assessments
                ->filter(function ($assessment) use ($enrollmentCounts) {
                    $total = (int) ($enrollmentCounts[$assessment->classroom_id] ?? 0);

                    return $assessment->scores_count < $total;
                })
                ->map(function ($assessment) use ($enrollmentCounts) {
                    return [
                        'id' => $assessment->id,
                        'title' => $assessment->title,
                        'classroom' => $assessment->classroom?->name,
                        'subject' => $assessment->subject?->name,
                        'date' => $assessment->date?->toDateString(),
                        'scored' => $assessment->scores_count,
                        'total' => (int) ($enrollmentCounts[$assessment->classroom_id] ?? 0),
                    ];
                })
                ->values()
                ->toArray();
        }

        return [
            'my_classrooms' => $myClassrooms,
            'schedule_today' => $scheduleToday,
            'pending_assessments' => $pendingAssessments,
        ];
    }
}
```

- [ ] **Step 4: Run teacher test to verify it passes**

```bash
./vendor/bin/pest --filter "returns teacher dashboard data" --compact
```

Expected: PASS

- [ ] **Step 5: Run all dashboard tests**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected: All 6 pass

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Services/Dashboard/TeacherDashboardData.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Services/Dashboard/TeacherDashboardData.php tests/Feature/DashboardTest.php
git commit -m "feat: add TeacherDashboardData service"
```

---

## Task 3: StudentDashboardData service

**Files:**
- Create: `app/Services/Dashboard/StudentDashboardData.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Write failing student test**

Append to end of `tests/Feature/DashboardTest.php`:

```php

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
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'student')
            ->has('data.classroom')
            ->has('data.schedule_today')
            ->has('data.recent_scores')
            ->has('data.attendance_summary.hadir')
            ->has('data.attendance_summary.sakit')
            ->has('data.attendance_summary.izin')
            ->has('data.attendance_summary.alpa')
        );
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
./vendor/bin/pest --filter "returns student dashboard data" --compact
```

Expected: FAIL (`StudentDashboardData` class does not exist)

- [ ] **Step 3: Create StudentDashboardData service**

Create `app/Services/Dashboard/StudentDashboardData.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Schedule\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentDashboardData
{
    public function get(User $user, Team $team): array
    {
        $activeYear = AcademicYear::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeYear
            ? Semester::where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        // Student's current classroom via StudentEnrollment in active academic year
        $classroom = null;
        $enrolledClassroomId = null;

        if ($activeYear) {
            $enrollment = StudentEnrollment::where('user_id', $user->id)
                ->whereHas(
                    'classroom',
                    fn ($q) => $q->where('academic_year_id', $activeYear->id)
                        ->where('team_id', $team->id)
                )
                ->with(['classroom.grade'])
                ->first();

            if ($enrollment) {
                $enrolledClassroomId = $enrollment->classroom_id;
                $classroom = [
                    'id' => $enrollment->classroom->id,
                    'name' => $enrollment->classroom->name,
                    'grade' => $enrollment->classroom->grade?->name,
                ];
            }
        }

        // Today's schedule for student's classroom in active semester
        $scheduleToday = [];

        if ($activeSemester && $enrolledClassroomId) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $todayName = $days[now()->dayOfWeek];

            $scheduleToday = Schedule::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('classroom_id', $enrolledClassroomId)
                ->where('day_of_week', $todayName)
                ->with(['subject:id,name', 'timeSlot'])
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'subject' => $s->subject?->name,
                    'room' => $s->room,
                    'time_slot' => $s->timeSlot
                        ? $s->timeSlot->start_time.' - '.$s->timeSlot->end_time
                        : null,
                ])
                ->toArray();
        }

        // Latest 5 scores in the active semester
        $recentScores = [];

        if ($activeSemester) {
            $recentScores = Score::where('student_user_id', $user->id)
                ->whereHas(
                    'assessment',
                    fn ($q) => $q->where('semester_id', $activeSemester->id)
                        ->where('team_id', $team->id)
                )
                ->with(['assessment.subject:id,name'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($score) => [
                    'id' => $score->id,
                    'score' => (float) $score->score,
                    'assessment_title' => $score->assessment?->title,
                    'subject' => $score->assessment?->subject?->name,
                    'max_score' => (float) ($score->assessment?->max_score ?? 100),
                ])
                ->toArray();
        }

        // Attendance summary for active semester
        $attendanceSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        if ($activeSemester) {
            $attendanceIds = Attendance::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->pluck('id');

            if ($attendanceIds->isNotEmpty()) {
                $statusCounts = AttendanceRecord::whereIn('attendance_id', $attendanceIds)
                    ->where('student_user_id', $user->id)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');

                foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                    $attendanceSummary[$status] = (int) ($statusCounts[$status] ?? 0);
                }
            }
        }

        return [
            'classroom' => $classroom,
            'schedule_today' => $scheduleToday,
            'recent_scores' => $recentScores,
            'attendance_summary' => $attendanceSummary,
        ];
    }
}
```

- [ ] **Step 4: Run student test to verify it passes**

```bash
./vendor/bin/pest --filter "returns student dashboard data" --compact
```

Expected: PASS

- [ ] **Step 5: Run all dashboard tests**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected: All 7 pass

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Services/Dashboard/StudentDashboardData.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Services/Dashboard/StudentDashboardData.php tests/Feature/DashboardTest.php
git commit -m "feat: add StudentDashboardData service"
```

---

## Task 4: ParentDashboardData service

**Files:**
- Create: `app/Services/Dashboard/ParentDashboardData.php`
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Write failing parent test**

Add this import to `tests/Feature/DashboardTest.php` if not already present:
```php
use App\Models\Academic\Guardian;
```

Append to end of `tests/Feature/DashboardTest.php`:

```php

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
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
            ->where('role', 'parent')
            ->has('data.children')
            ->has('data.children.0.student')
            ->has('data.children.0.classroom')
            ->has('data.children.0.recent_scores')
            ->has('data.children.0.attendance_summary')
        );
});
```

- [ ] **Step 2: Run test to confirm it fails**

```bash
./vendor/bin/pest --filter "returns parent dashboard data" --compact
```

Expected: FAIL (`ParentDashboardData` class does not exist)

- [ ] **Step 3: Create ParentDashboardData service**

Create `app/Services/Dashboard/ParentDashboardData.php`:

```php
<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Guardian;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParentDashboardData
{
    public function get(User $user, Team $team): array
    {
        $activeYear = AcademicYear::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeYear
            ? Semester::where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        // Find all students where this user is a guardian
        // Guardian model has no team_id; scope to team via StudentEnrollment on active year
        $guardians = Guardian::where('guardian_id', $user->id)
            ->with('student')
            ->get();

        $children = [];

        foreach ($guardians as $guardian) {
            $studentUser = $guardian->student;

            if (! $studentUser || ! $activeYear) {
                continue;
            }

            // Verify student is enrolled in this team's active year
            $enrollment = StudentEnrollment::where('user_id', $studentUser->id)
                ->whereHas(
                    'classroom',
                    fn ($q) => $q->where('academic_year_id', $activeYear->id)
                        ->where('team_id', $team->id)
                )
                ->with(['classroom.grade'])
                ->first();

            if (! $enrollment) {
                continue;
            }

            $classroom = [
                'id' => $enrollment->classroom->id,
                'name' => $enrollment->classroom->name,
                'grade' => $enrollment->classroom->grade?->name,
            ];

            // Latest 3 scores in the active semester
            $recentScores = [];

            if ($activeSemester) {
                $recentScores = Score::where('student_user_id', $studentUser->id)
                    ->whereHas(
                        'assessment',
                        fn ($q) => $q->where('semester_id', $activeSemester->id)
                            ->where('team_id', $team->id)
                    )
                    ->with(['assessment.subject:id,name'])
                    ->latest()
                    ->take(3)
                    ->get()
                    ->map(fn ($score) => [
                        'id' => $score->id,
                        'score' => (float) $score->score,
                        'assessment_title' => $score->assessment?->title,
                        'subject' => $score->assessment?->subject?->name,
                        'max_score' => (float) ($score->assessment?->max_score ?? 100),
                    ])
                    ->toArray();
            }

            // Attendance summary for active semester
            $attendanceSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

            if ($activeSemester) {
                $attendanceIds = Attendance::where('team_id', $team->id)
                    ->where('semester_id', $activeSemester->id)
                    ->pluck('id');

                if ($attendanceIds->isNotEmpty()) {
                    $statusCounts = AttendanceRecord::whereIn('attendance_id', $attendanceIds)
                        ->where('student_user_id', $studentUser->id)
                        ->select('status', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status');

                    foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                        $attendanceSummary[$status] = (int) ($statusCounts[$status] ?? 0);
                    }
                }
            }

            $children[] = [
                'student' => [
                    'id' => $studentUser->id,
                    'name' => $studentUser->name,
                    'email' => $studentUser->email,
                ],
                'classroom' => $classroom,
                'recent_scores' => $recentScores,
                'attendance_summary' => $attendanceSummary,
            ];
        }

        return ['children' => $children];
    }
}
```

- [ ] **Step 4: Run parent test to verify it passes**

```bash
./vendor/bin/pest --filter "returns parent dashboard data" --compact
```

Expected: PASS

- [ ] **Step 5: Run all dashboard tests**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected: All 8 pass

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Services/Dashboard/ParentDashboardData.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Services/Dashboard/ParentDashboardData.php tests/Feature/DashboardTest.php
git commit -m "feat: add ParentDashboardData service"
```

---

## Task 5: Cross-tenant isolation test

**Files:**
- Modify: `tests/Feature/DashboardTest.php`

- [ ] **Step 1: Write cross-tenant isolation test**

Append to end of `tests/Feature/DashboardTest.php`:

```php

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
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('data.total_students', 1)
            ->where('data.total_classrooms', 1)
        );
});
```

- [ ] **Step 2: Run cross-tenant test**

```bash
./vendor/bin/pest --filter "admin dashboard does not include data from other teams" --compact
```

Expected: PASS

- [ ] **Step 3: Run all dashboard tests**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected: All 9 pass

- [ ] **Step 4: Run full test suite to ensure no regressions**

```bash
./vendor/bin/pest --compact
```

Expected: All tests pass

- [ ] **Step 5: Commit**

```bash
git add tests/Feature/DashboardTest.php
git commit -m "test: add cross-tenant isolation test for admin dashboard"
```
