# Student Detail Page Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add a `students.show` page that displays a student's profile, class enrollment, attendance summary + paginated records, and guardian data.

**Architecture:** Single Inertia page (`students/show.tsx`) powered by a new `show()` method on the existing `StudentController`. All data is passed as Inertia props upfront; attendance records are paginated server-side via `?page=` query param.

**Tech Stack:** Laravel 13, Inertia v3, React 19, TypeScript, Pest 4, Wayfinder, Tailwind CSS v4, shadcn Card + Table + Pagination components.

---

## File Map

| Status | File | Purpose |
|--------|------|---------|
| Modify | `routes/students.php` | Add `students.show` route |
| Create | `tests/Feature/Students/StudentShowTest.php` | 9 Pest tests |
| Modify | `app/Http/Controllers/Students/StudentController.php` | Add `show()` method |
| Run | `php artisan wayfinder:generate` | Regenerate to include `show` |
| Create | `resources/js/pages/students/show.tsx` | Detail page component |
| Modify | `resources/js/pages/students/index.tsx` | Make student name a link |

---

## Task 1: Add `students.show` route

**Files:**
- Modify: `routes/students.php`

- [ ] **Step 1: Add the route**

Open `routes/students.php`. Add `show` **before** the existing `{user}/edit` line:

```php
// Wildcard after static routes
Route::get('students/{user}', [StudentController::class, 'show'])->name('show');
Route::get('students/{user}/edit', [StudentController::class, 'edit'])->name('edit');
Route::patch('students/{user}', [StudentController::class, 'update'])->name('update');
Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');
```

Full file after edit:

```php
<?php

use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentImportController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('students.')
    ->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('index');

        // Static routes BEFORE {user} wildcard
        Route::get('students/create', [StudentController::class, 'create'])->name('create');
        Route::post('students', [StudentController::class, 'store'])->name('store');
        Route::get('students/import', [StudentImportController::class, 'create'])->name('import');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('import.store');
        Route::get('students/import/template', [StudentImportController::class, 'template'])->name('import.template');

        // Wildcard after static routes
        Route::get('students/{user}', [StudentController::class, 'show'])->name('show');
        Route::get('students/{user}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::patch('students/{user}', [StudentController::class, 'update'])->name('update');
        Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');
    });
```

- [ ] **Step 2: Verify route appears**

```bash
php artisan route:list --path=students --except-vendor
```

Expected: line with `GET|HEAD {current_team}/students/{user} students.show` appears.

- [ ] **Step 3: Commit**

```bash
git add routes/students.php
git commit -m "feat(students): add students.show route"
```

---

## Task 2: Write failing tests

**Files:**
- Create: `tests/Feature/Students/StudentShowTest.php`

- [ ] **Step 1: Create the test file**

```bash
php artisan make:test --pest tests/Feature/Students/StudentShowTest
```

- [ ] **Step 2: Write all 9 tests**

Replace the file contents with:

```php
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
```

- [ ] **Step 3: Run tests to confirm they fail**

```bash
php artisan test --compact --filter=StudentShow
```

Expected: all 9 tests FAIL — either 500 (no `show()` method yet) or because TSX doesn't exist.

- [ ] **Step 4: Commit failing tests**

```bash
git add tests/Feature/Students/StudentShowTest.php
git commit -m "test(students): add failing StudentShowTest (9 tests)"
```

---

## Task 3: Implement `StudentController::show()`

**Files:**
- Modify: `app/Http/Controllers/Students/StudentController.php`

- [ ] **Step 1: Add required imports at the top of the controller**

The controller already imports `StudentEnrollment`, `TeamRole`, `User`, `Inertia`, `Request`, etc. Add these missing ones:

```php
use App\Enums\AttendanceStatus;
use App\Models\Academic\Guardian;
use App\Models\Schedule\AttendanceRecord;
```

After editing, the `use` block at the top of `StudentController.php` should be:

```php
use App\Enums\AttendanceStatus;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Academic\Guardian;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;
```

- [ ] **Step 2: Add the `show()` method**

Insert `show()` between `create()` and `store()` (after the closing `}` of `create()`):

```php
public function show(Request $request, string $currentTeam, User $user): Response
{
    $team = $request->user()->currentTeam;

    $studentMembership = $team->members()
        ->where('users.id', $user->id)
        ->wherePivot('role', TeamRole::Student->value)
        ->first();

    abort_unless($studentMembership !== null, 404);

    $classroomIds = $team->classrooms()->pluck('id');

    $enrollment = StudentEnrollment::whereIn('classroom_id', $classroomIds)
        ->where('user_id', $user->id)
        ->with([
            'classroom:id,name,grade_id,academic_year_id',
            'classroom.grade:id,name',
            'classroom.academicYear:id,name',
        ])
        ->first();

    $rawSummary = AttendanceRecord::query()
        ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
        ->whereIn('attendances.classroom_id', $classroomIds)
        ->where('attendance_records.student_user_id', $user->id)
        ->selectRaw('attendance_records.status, count(*) as count')
        ->groupBy('attendance_records.status')
        ->toBase()
        ->pluck('count', 'status');

    $attendanceSummary = collect(AttendanceStatus::cases())->map(fn (AttendanceStatus $s) => [
        'status' => $s->value,
        'count' => (int) ($rawSummary[$s->value] ?? 0),
    ]);

    $attendanceRecords = AttendanceRecord::query()
        ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
        ->leftJoin('subjects', 'attendances.subject_id', '=', 'subjects.id')
        ->whereIn('attendances.classroom_id', $classroomIds)
        ->where('attendance_records.student_user_id', $user->id)
        ->orderByDesc('attendances.date')
        ->select(
            'attendance_records.*',
            'attendances.date as attendance_date',
            'subjects.name as subject_name',
        )
        ->paginate(15)
        ->through(fn (AttendanceRecord $record) => [
            'date' => $record->attendance_date,
            'subject_name' => $record->subject_name,
            'status' => $record->status->value,
            'notes' => $record->notes,
        ]);

    $guardians = Guardian::where('student_id', $user->id)
        ->with('guardian:id,name,email')
        ->get()
        ->map(fn (Guardian $g) => [
            'name' => $g->guardian->name,
            'email' => $g->guardian->email,
            'relationship_label' => $g->relationship->label(),
        ]);

    return Inertia::render('students/show', [
        'student' => [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'joined_at' => $studentMembership->pivot->created_at,
        ],
        'enrollment' => $enrollment ? [
            'classroom_name' => $enrollment->classroom->name,
            'student_number' => $enrollment->student_number,
            'grade_name' => $enrollment->classroom->grade->name,
            'academic_year_name' => $enrollment->classroom->academicYear->name,
        ] : null,
        'attendance_summary' => $attendanceSummary,
        'attendance_records' => $attendanceRecords,
        'guardians' => $guardians,
    ]);
}
```

- [ ] **Step 3: Run pint to fix formatting**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 4: Run the tests (8 of 9 should pass; test 1 "admin can view" still fails until TSX exists)**

```bash
php artisan test --compact --filter=StudentShow
```

Expected: 8 pass, 1 fails (`admin can view student show page` — fails because `students/show.tsx` doesn't exist yet and `inertia.testing.ensure_pages_exist = true`).

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Students/StudentController.php
git commit -m "feat(students): implement StudentController::show() with profile, enrollment, attendance, guardians"
```

---

## Task 4: Regenerate Wayfinder

**Files:**
- Regenerated (git-ignored): `resources/js/actions/App/Http/Controllers/Students/StudentController.ts`

- [ ] **Step 1: Run wayfinder:generate**

```bash
php artisan wayfinder:generate
```

- [ ] **Step 2: Verify `show` method appears**

```bash
grep "show" resources/js/actions/App/Http/Controllers/Students/StudentController.ts
```

Expected: a `show` export function is present.

_(No commit needed — wayfinder files are git-ignored.)_

---

## Task 5: Create `students/show.tsx`

**Files:**
- Create: `resources/js/pages/students/show.tsx`

- [ ] **Step 1: Create the file**

```tsx
import { Head, Link, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface StudentDetail {
    id: number;
    name: string;
    email: string;
    joined_at: string;
}

interface EnrollmentDetail {
    classroom_name: string;
    student_number: string | null;
    grade_name: string;
    academic_year_name: string;
}

interface AttendanceSummaryItem {
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    count: number;
}

interface AttendanceRecordItem {
    date: string;
    subject_name: string | null;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes: string | null;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface PaginatedAttendanceRecords extends PaginationMeta {
    data: AttendanceRecordItem[];
}

interface GuardianItem {
    name: string;
    email: string;
    relationship_label: string;
}

interface Props {
    student: StudentDetail;
    enrollment: EnrollmentDetail | null;
    attendance_summary: AttendanceSummaryItem[];
    attendance_records: PaginatedAttendanceRecords;
    guardians: GuardianItem[];
}

const statusColors: Record<string, string> = {
    hadir: 'bg-green-100 text-green-800',
    sakit: 'bg-yellow-100 text-yellow-800',
    izin: 'bg-blue-100 text-blue-800',
    alpa: 'bg-red-100 text-red-800',
};

const statusLabels: Record<string, string> = {
    hadir: 'Hadir',
    sakit: 'Sakit',
    izin: 'Izin',
    alpa: 'Alpa',
};

export default function StudentShow({
    student,
    enrollment,
    attendance_summary,
    attendance_records,
    guardians,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    return (
        <>
            <Head title={student.name} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                {student.name}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {student.email}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={StudentController.index.url(teamSlug)}
                                >
                                    Kembali
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link
                                    href={StudentController.edit.url({
                                        current_team: teamSlug,
                                        user: student.id,
                                    })}
                                >
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Profil */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Profil</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-2 gap-4">
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Email
                                    </dt>
                                    <dd className="font-medium">
                                        {student.email}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Bergabung
                                    </dt>
                                    <dd className="font-medium">
                                        {new Date(
                                            student.joined_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    {/* Info Kelas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Info Kelas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {enrollment ? (
                                <dl className="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Kelas
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.classroom_name}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            NIS
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.student_number ?? '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Tingkat
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.grade_name}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Tahun Ajaran
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.academic_year_name}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    Belum terdaftar di kelas manapun.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Kehadiran */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Kehadiran</h2>

                        <div className="flex gap-3">
                            {attendance_summary.map((item) => (
                                <div
                                    key={item.status}
                                    className={`rounded-lg px-4 py-2 text-center ${statusColors[item.status]}`}
                                >
                                    <div className="text-2xl font-bold">
                                        {item.count}
                                    </div>
                                    <div className="text-xs font-medium">
                                        {statusLabels[item.status]}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {attendance_records.data.length > 0 ? (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tanggal</TableHead>
                                            <TableHead>
                                                Mata Pelajaran
                                            </TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Catatan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {attendance_records.data.map(
                                            (record, idx) => (
                                                <TableRow key={idx}>
                                                    <TableCell>
                                                        {new Date(
                                                            record.date,
                                                        ).toLocaleDateString(
                                                            'id-ID',
                                                            {
                                                                day: 'numeric',
                                                                month: 'short',
                                                                year: 'numeric',
                                                            },
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {record.subject_name ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <span
                                                            className={`rounded px-2 py-0.5 text-xs font-medium ${statusColors[record.status]}`}
                                                        >
                                                            {
                                                                statusLabels[
                                                                    record
                                                                        .status
                                                                ]
                                                            }
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {record.notes ?? '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                                <Pagination meta={attendance_records} />
                            </>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Belum ada data kehadiran.
                            </p>
                        )}
                    </div>

                    {/* Wali */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Data Wali</h2>
                        {guardians.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Hubungan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {guardians.map((g, idx) => (
                                        <TableRow key={idx}>
                                            <TableCell className="font-medium">
                                                {g.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {g.email}
                                            </TableCell>
                                            <TableCell>
                                                {g.relationship_label}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Belum ada data wali.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Run all 9 tests — all should pass now**

```bash
php artisan test --compact --filter=StudentShow
```

Expected: 9/9 pass.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/students/show.tsx
git commit -m "feat(students): add students/show.tsx detail page"
```

---

## Task 6: Update `students/index.tsx` — student name becomes a link

**Files:**
- Modify: `resources/js/pages/students/index.tsx`

- [ ] **Step 1: Replace the student name `TableCell`**

Find this block (around line 204):

```tsx
<TableCell>{student.name}</TableCell>
```

Replace with:

```tsx
<TableCell>
    <Link
        href={StudentController.show.url({
            current_team: teamSlug,
            user: student.id,
        })}
        className="font-medium hover:underline"
    >
        {student.name}
    </Link>
</TableCell>
```

_(The `Link` import from `@inertiajs/react` is already at the top of the file.)_

- [ ] **Step 2: Run prettier to fix formatting**

```bash
npm run format -- resources/js/pages/students/index.tsx
```

- [ ] **Step 3: Run full test suite**

```bash
php artisan test --compact
```

Expected: all 257 tests pass (248 prior + 9 new StudentShow tests).

- [ ] **Step 4: Run lint check on students pages**

```bash
npm run lint:check -- resources/js/pages/students/
```

Expected: 0 errors (the pre-existing `react-hooks/exhaustive-deps` warning in `index.tsx` is acceptable).

- [ ] **Step 5: Commit**

```bash
git add resources/js/pages/students/index.tsx
git commit -m "feat(students): make student name a link to detail page"
```

---

## Task 7: Final verification

- [ ] **Step 1: Run pint on all dirty PHP files**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 2: Run full PHP test suite**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 3: Run frontend checks**

```bash
npm run lint:check && npm run format:check
```

Expected: 0 errors, all files formatted.

- [ ] **Step 4: Push**

```bash
git push
```
