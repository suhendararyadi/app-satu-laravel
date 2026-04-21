# Student Management Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Build a student management module with a list page (filterable by classroom) and bulk Excel import that creates user accounts and adds them to the current team as students.

**Architecture:** No new tables — students are `User` records with `role = 'student'` in `team_members`. Import uses `maatwebsite/excel` to parse `.xlsx` files row-by-row, skipping existing emails, creating `User` + `Membership` (and optionally `StudentEnrollment`) per row. Results are flashed to session and displayed on the import form page.

**Tech Stack:** PHP 8.4, Laravel 13, `maatwebsite/laravel-excel`, Inertia v3, React 19, TypeScript, Tailwind CSS v4, Pest v4.

---

## File Map

**New files:**
- `routes/students.php` — student management routes
- `app/Http/Controllers/Students/StudentController.php` — index, destroy
- `app/Http/Controllers/Students/StudentImportController.php` — create, store, template
- `app/Imports/StudentImport.php` — maatwebsite import class
- `app/Exports/StudentTemplateExport.php` — template download export class
- `app/Notifications/Students/WelcomeStudent.php` — email to new student with temp password
- `tests/Feature/Students/StudentControllerTest.php`
- `tests/Feature/Students/StudentImportControllerTest.php`
- `resources/js/pages/students/index.tsx`
- `resources/js/pages/students/import/create.tsx`

**Modified files:**
- `app/Models/User.php` — add `enrollments()` HasMany
- `routes/web.php` — require students.php
- `resources/js/components/app-sidebar.tsx` — add "Siswa" nav item to Academic group

---

## Task 1: Install maatwebsite/laravel-excel

**Files:**
- Modify: `composer.json` (via composer command)

- [ ] **Step 1: Install the package**

```bash
composer require maatwebsite/excel
```

Expected output: Package installed successfully.

- [ ] **Step 2: Publish the config**

```bash
php artisan vendor:publish --provider="Maatwebsite\Excel\ExcelServiceProvider" --tag=config --no-interaction
```

Expected: `config/excel.php` created.

- [ ] **Step 3: Verify install**

```bash
php artisan tinker --execute 'echo class_exists(Maatwebsite\Excel\Facades\Excel::class) ? "ok" : "fail";'
```

Expected output: `ok`

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/excel.php
git commit -m "chore: install maatwebsite/laravel-excel"
```

---

## Task 2: Add enrollments() relationship to User model

**Files:**
- Modify: `app/Models/User.php`

- [ ] **Step 1: Write the failing test**

Create `tests/Feature/Students/StudentControllerTest.php`:

```php
<?php

use App\Models\User;

it('user has enrollments relationship', function () {
    $user = User::factory()->create();
    expect($user->enrollments())->toBeInstanceOf(
        \Illuminate\Database\Eloquent\Relations\HasMany::class
    );
});
```

- [ ] **Step 2: Run test to verify it fails**

```bash
./vendor/bin/pest tests/Feature/Students/StudentControllerTest.php --filter="user has enrollments"
```

Expected: FAIL — `Call to undefined method enrollments()`

- [ ] **Step 3: Add the relationship to User model**

In `app/Models/User.php`, add the import and method:

```php
use App\Models\Academic\StudentEnrollment;
use Illuminate\Database\Eloquent\Relations\HasMany;
```

Add after the `socialAccounts()` method:

```php
/**
 * @return HasMany<StudentEnrollment, $this>
 */
public function enrollments(): HasMany
{
    return $this->hasMany(StudentEnrollment::class, 'user_id');
}
```

- [ ] **Step 4: Run pint**

```bash
./vendor/bin/pint app/Models/User.php --format agent
```

- [ ] **Step 5: Run test to verify it passes**

```bash
./vendor/bin/pest tests/Feature/Students/StudentControllerTest.php --filter="user has enrollments"
```

Expected: PASS

- [ ] **Step 6: Commit**

```bash
git add app/Models/User.php tests/Feature/Students/StudentControllerTest.php
git commit -m "feat: add enrollments() relationship to User model"
```

---

## Task 3: Routes + StudentController (index, destroy)

**Files:**
- Create: `routes/students.php`
- Create: `app/Http/Controllers/Students/StudentController.php`
- Modify: `routes/web.php`
- Modify: `tests/Feature/Students/StudentControllerTest.php`

- [ ] **Step 1: Write the failing tests**

Append to `tests/Feature/Students/StudentControllerTest.php`:

```php
use App\Enums\TeamRole;
use App\Models\Team;

beforeEach(function () {
    $this->withoutVite();
});

function makeStudentSetup(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $student = User::factory()->create();
    $team->members()->attach($student, ['role' => TeamRole::Student->value]);

    return [$owner, $team, $student];
}

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

    $this->actingAs($owner)
        ->get(route('students.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->has('students', 1)
        );
});

it('admin can remove a student from the team', function () {
    [$owner, $team, $student] = makeStudentSetup();

    $this->actingAs($owner)
        ->delete(route('students.destroy', ['user' => $student->id]))
        ->assertRedirect(route('students.index'));

    expect($team->members()->where('users.id', $student->id)->exists())->toBeFalse();
});

it('non-admin cannot access students index', function () {
    [$owner, $team, $student] = makeStudentSetup();

    $this->actingAs($student)
        ->get(route('students.index'))
        ->assertForbidden();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/pest tests/Feature/Students/StudentControllerTest.php --filter="admin can view"
```

Expected: FAIL — route not found

- [ ] **Step 3: Create the StudentController**

```bash
php artisan make:controller Students/StudentController --no-interaction
```

Replace the generated content of `app/Http/Controllers/Students/StudentController.php`:

```php
<?php

namespace App\Http\Controllers\Students;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Academic\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $students = $team->members()
            ->wherePivot('role', TeamRole::Student->value)
            ->with([
                'enrollments' => fn ($q) => $q
                    ->whereHas('classroom', fn ($q) => $q->where('team_id', $team->id))
                    ->with('classroom:id,name'),
            ])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'joined_at' => $user->pivot->created_at,
                'classrooms' => $user->enrollments->map(fn (StudentEnrollment $e) => [
                    'id' => $e->classroom->id,
                    'name' => $e->classroom->name,
                    'student_number' => $e->student_number,
                ]),
            ]);

        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/index', [
            'students' => $students,
            'classrooms' => $classrooms,
        ]);
    }

    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        // Remove all enrollments in this team's classrooms
        StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->delete();

        $team->members()->detach($user->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil dihapus dari tim.']);

        return to_route('students.index');
    }
}
```

- [ ] **Step 4: Create routes/students.php**

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
        Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');

        Route::get('students/import', [StudentImportController::class, 'create'])->name('import');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('import.store');
        Route::get('students/import/template', [StudentImportController::class, 'template'])->name('import.template');
    });
```

- [ ] **Step 5: Include routes/students.php in web.php**

In `routes/web.php`, add at the bottom:

```php
require __DIR__.'/students.php';
```

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Students/StudentController.php routes/students.php --format agent
```

- [ ] **Step 7: Run tests to verify they pass**

```bash
./vendor/bin/pest tests/Feature/Students/StudentControllerTest.php
```

Expected: All tests PASS

- [ ] **Step 8: Commit**

```bash
git add routes/students.php routes/web.php app/Http/Controllers/Students/StudentController.php tests/Feature/Students/StudentControllerTest.php
git commit -m "feat: add StudentController with index and destroy, student routes"
```

---

## Task 4: WelcomeStudent notification

**Files:**
- Create: `app/Notifications/Students/WelcomeStudent.php`

- [ ] **Step 1: Create the notification**

```bash
php artisan make:notification Students/WelcomeStudent --no-interaction
```

Replace the generated file `app/Notifications/Students/WelcomeStudent.php`:

```php
<?php

namespace App\Notifications\Students;

use App\Models\Team;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class WelcomeStudent extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Team $team,
        public readonly string $temporaryPassword,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Selamat datang di {$this->team->name}")
            ->line("Akun Anda telah dibuat oleh administrator {$this->team->name}.")
            ->line("Email: {$notifiable->email}")
            ->line("Password sementara: {$this->temporaryPassword}")
            ->action('Login Sekarang', url('/'))
            ->line('Segera ganti password Anda setelah login.');
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'team_id' => $this->team->id,
            'team_name' => $this->team->name,
        ];
    }
}
```

- [ ] **Step 2: Run pint**

```bash
./vendor/bin/pint app/Notifications/Students/WelcomeStudent.php --format agent
```

- [ ] **Step 3: Verify it compiles**

```bash
php artisan tinker --execute 'echo class_exists(App\Notifications\Students\WelcomeStudent::class) ? "ok" : "fail";'
```

Expected output: `ok`

- [ ] **Step 4: Commit**

```bash
git add app/Notifications/Students/WelcomeStudent.php
git commit -m "feat: add WelcomeStudent notification with temporary password"
```

---

## Task 5: StudentImport class + StudentTemplateExport

**Files:**
- Create: `app/Imports/StudentImport.php`
- Create: `app/Exports/StudentTemplateExport.php`

- [ ] **Step 1: Create the import class**

Create `app/Imports/StudentImport.php`:

```php
<?php

namespace App\Imports;

use App\Enums\TeamRole;
use App\Models\Academic\StudentEnrollment;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Students\WelcomeStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class StudentImport implements ToCollection, WithHeadingRow
{
    /** @var array{imported: int, skipped: int, errors: string[]} */
    private array $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

    public function __construct(
        private readonly Team $team,
        private readonly ?int $classroomId = null,
    ) {}

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));
            $nis = trim((string) ($row['nis'] ?? '')) ?: null;

            if ($email === '') {
                $this->result['errors'][] = "Baris ".($index + 2).": kolom Email kosong, dilewati.";

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->result['skipped']++;

                continue;
            }

            $temporaryPassword = Str::random(12);

            $user = User::create([
                'name' => $name ?: $email,
                'email' => $email,
                'password' => bcrypt($temporaryPassword),
                'email_verified_at' => now(),
            ]);

            $this->team->members()->attach($user->id, ['role' => TeamRole::Student->value]);

            if ($this->classroomId !== null) {
                StudentEnrollment::create([
                    'classroom_id' => $this->classroomId,
                    'user_id' => $user->id,
                    'student_number' => $nis,
                ]);
            }

            Notification::send($user, new WelcomeStudent($this->team, $temporaryPassword));

            $this->result['imported']++;
        }
    }

    /**
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
```

- [ ] **Step 2: Create the template export class**

Create `app/Exports/StudentTemplateExport.php`:

```php
<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;

class StudentTemplateExport implements FromArray, WithHeadings
{
    /**
     * @return array<int, array<int, string>>
     */
    public function array(): array
    {
        return [
            ['Budi Santoso', 'budi@contoh.com', '12345'],
        ];
    }

    /**
     * @return array<int, string>
     */
    public function headings(): array
    {
        return ['Nama', 'Email', 'NIS'];
    }
}
```

- [ ] **Step 3: Run pint**

```bash
./vendor/bin/pint app/Imports/StudentImport.php app/Exports/StudentTemplateExport.php --format agent
```

- [ ] **Step 4: Verify compilation**

```bash
php artisan tinker --execute 'echo class_exists(App\Imports\StudentImport::class) ? "ok" : "fail";'
```

Expected: `ok`

- [ ] **Step 5: Commit**

```bash
git add app/Imports/StudentImport.php app/Exports/StudentTemplateExport.php
git commit -m "feat: add StudentImport and StudentTemplateExport classes"
```

---

## Task 6: StudentImportController + tests

**Files:**
- Create: `app/Http/Controllers/Students/StudentImportController.php`
- Create: `tests/Feature/Students/StudentImportControllerTest.php`

- [ ] **Step 1: Write failing tests**

Create `tests/Feature/Students/StudentImportControllerTest.php`:

```php
<?php

use App\Enums\TeamRole;
use App\Imports\StudentImport;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->withoutVite();
});

function makeImportSetup(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('shows the import form page', function () {
    [$owner] = makeImportSetup();

    $this->actingAs($owner)
        ->get(route('students.import'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/import/create')
            ->has('classrooms')
        );
});

it('downloads import template', function () {
    [$owner] = makeImportSetup();

    Excel::fake();

    $this->actingAs($owner)
        ->get(route('students.import.template'))
        ->assertOk();

    Excel::assertDownloaded('template-import-siswa.xlsx');
});

it('imports students from uploaded excel', function () {
    [$owner, $team] = makeImportSetup();

    Notification::fake();
    Excel::fake();

    $file = UploadedFile::fake()->create('students.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($owner)
        ->post(route('students.import.store'), ['file' => $file])
        ->assertRedirect(route('students.import'));

    Excel::assertImported('students.xlsx');
});

it('import requires a file', function () {
    [$owner] = makeImportSetup();

    $this->actingAs($owner)
        ->post(route('students.import.store'), [])
        ->assertSessionHasErrors('file');
});

it('StudentImport creates user and adds to team', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $import = new StudentImport($team, null);
    $import->collection(collect([
        collect(['nama' => 'Budi', 'email' => 'budi@test.com', 'nis' => '99']),
    ]));

    expect(User::where('email', 'budi@test.com')->exists())->toBeTrue();
    expect($team->members()->where('users.email', 'budi@test.com')->exists())->toBeTrue();
    expect($import->getResult()['imported'])->toBe(1);
    expect($import->getResult()['skipped'])->toBe(0);

    Notification::assertSentTo(
        User::where('email', 'budi@test.com')->first(),
        \App\Notifications\Students\WelcomeStudent::class,
    );
});

it('StudentImport skips existing email', function () {
    Notification::fake();

    $existing = User::factory()->create(['email' => 'ada@test.com']);
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $import = new StudentImport($team, null);
    $import->collection(collect([
        collect(['nama' => 'Ada', 'email' => 'ada@test.com', 'nis' => '']),
    ]));

    expect($import->getResult()['skipped'])->toBe(1);
    expect($import->getResult()['imported'])->toBe(0);
    expect($team->members()->where('users.id', $existing->id)->exists())->toBeFalse();
});

it('StudentImport enrolls student when classroom given', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $year = \App\Models\Academic\AcademicYear::factory()->for($team)->create();
    $grade = \App\Models\Academic\Grade::factory()->for($team)->create();
    $classroom = \App\Models\Academic\Classroom::factory()
        ->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();

    $import = new StudentImport($team, $classroom->id);
    $import->collection(collect([
        collect(['nama' => 'Cici', 'email' => 'cici@test.com', 'nis' => '007']),
    ]));

    $user = User::where('email', 'cici@test.com')->first();
    expect($user->enrollments()->where('classroom_id', $classroom->id)->exists())->toBeTrue();
});
```

- [ ] **Step 2: Run tests to verify they fail**

```bash
./vendor/bin/pest tests/Feature/Students/StudentImportControllerTest.php --filter="shows the import form"
```

Expected: FAIL — controller not found

- [ ] **Step 3: Create StudentImportController**

```bash
php artisan make:controller Students/StudentImportController --no-interaction
```

Replace `app/Http/Controllers/Students/StudentImportController.php`:

```php
<?php

namespace App\Http\Controllers\Students;

use App\Exports\StudentTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('students/import/create', [
            'classrooms' => $team->classrooms()->select(['id', 'name'])->orderBy('name')->get(),
            'import_result' => session('import_result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'classroom_id' => ['nullable', 'integer'],
        ]);

        $team = $request->user()->currentTeam;
        $classroomId = $request->integer('classroom_id') ?: null;

        $import = new StudentImport($team, $classroomId);
        Excel::import($import, $request->file('file'));
        $result = $import->getResult();

        return redirect()
            ->route('students.import')
            ->with('import_result', $result);
    }

    public function template(): BinaryFileResponse
    {
        return Excel::download(new StudentTemplateExport(), 'template-import-siswa.xlsx');
    }
}
```

- [ ] **Step 4: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Students/StudentImportController.php --format agent
```

- [ ] **Step 5: Run all student tests**

```bash
./vendor/bin/pest tests/Feature/Students/ --compact
```

Expected: All tests PASS

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Students/StudentImportController.php tests/Feature/Students/StudentImportControllerTest.php
git commit -m "feat: add StudentImportController and StudentImport tests"
```

---

## Task 7: Rebuild Wayfinder types

After adding the two new controllers and routes, Wayfinder must regenerate its TypeScript action files so the frontend can use typed URLs.

**Files:**
- Auto-generated: `resources/js/actions/App/Http/Controllers/Students/StudentController.ts`
- Auto-generated: `resources/js/actions/App/Http/Controllers/Students/StudentImportController.ts`

- [ ] **Step 1: Generate Wayfinder actions**

```bash
php artisan wayfinder:generate
```

Expected: New files appear in `resources/js/actions/App/Http/Controllers/Students/`.

- [ ] **Step 2: Verify files were created**

```bash
ls resources/js/actions/App/Http/Controllers/Students/
```

Expected output includes `StudentController.ts` and `StudentImportController.ts`.

- [ ] **Step 3: Commit generated files**

```bash
git add resources/js/actions/
git commit -m "chore: regenerate wayfinder actions for StudentController"
```

---

## Task 8: Frontend — students/index.tsx

**Files:**
- Create: `resources/js/pages/students/index.tsx`

- [ ] **Step 1: Create the page**

Create `resources/js/pages/students/index.tsx`:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import StudentImportController from '@/actions/App/Http/Controllers/Students/StudentImportController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface StudentClassroom {
    id: number;
    name: string;
    student_number: string | null;
}

interface Student {
    id: number;
    name: string;
    email: string;
    joined_at: string;
    classrooms: StudentClassroom[];
}

interface ClassroomOption {
    id: number;
    name: string;
}

interface Props {
    students: Student[];
    classrooms: ClassroomOption[];
}

export default function StudentsIndex({ students, classrooms }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [classroomFilter, setClassroomFilter] = useState<string>('all');

    const filtered =
        classroomFilter === 'all'
            ? students
            : students.filter((s) =>
                  s.classrooms.some((c) => String(c.id) === classroomFilter),
              );

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            StudentController.destroy.url({
                current_team: teamSlug,
                user: deleteId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Manajemen Siswa" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                Manajemen Siswa
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {students.length} siswa terdaftar
                            </p>
                        </div>
                        <Button asChild>
                            <Link
                                href={StudentImportController.create.url(
                                    teamSlug,
                                )}
                            >
                                Import Siswa
                            </Link>
                        </Button>
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="text-sm text-muted-foreground">
                            Filter kelas:
                        </span>
                        <Select
                            value={classroomFilter}
                            onValueChange={setClassroomFilter}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Semua kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua kelas</SelectItem>
                                {classrooms.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>NIS</TableHead>
                                <TableHead className="w-24">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((student) => (
                                <TableRow key={student.id}>
                                    <TableCell>{student.name}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {student.email}
                                    </TableCell>
                                    <TableCell>
                                        {student.classrooms.length > 0
                                            ? student.classrooms
                                                  .map((c) => c.name)
                                                  .join(', ')
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        {student.classrooms.length > 0
                                            ? (student.classrooms[0]
                                                  .student_number ?? '—')
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => {
                                                setDeleteId(student.id);
                                                setConfirmOpen(true);
                                            }}
                                        >
                                            Hapus
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    {filtered.length === 0 && (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Tidak ada siswa ditemukan.
                        </p>
                    )}
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                onConfirm={confirmDelete}
            />
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check**

```bash
npm run types:check
```

Expected: No errors. If wayfinder imports are missing, run `php artisan wayfinder:generate` first (Task 7).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/students/index.tsx
git commit -m "feat: add students index page with classroom filter"
```

---

## Task 9: Frontend — students/import/create.tsx

**Files:**
- Create: `resources/js/pages/students/import/create.tsx`

- [ ] **Step 1: Create the import form page**

Create `resources/js/pages/students/import/create.tsx`:

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import StudentImportController from '@/actions/App/Http/Controllers/Students/StudentImportController';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface ClassroomOption {
    id: number;
    name: string;
}

interface ImportResult {
    imported: number;
    skipped: number;
    errors: string[];
}

interface Props {
    classrooms: ClassroomOption[];
    import_result?: ImportResult | null;
}

export default function StudentImportCreate({
    classrooms,
    import_result,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm<{ file: File | null; classroom_id: string }>({
        file: null,
        classroom_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(StudentImportController.store.url(teamSlug), {
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Import Siswa" />
            <div className="px-4 py-6">
                <div className="max-w-xl space-y-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Import Siswa</h1>
                        <Button variant="outline" asChild>
                            <Link
                                href={StudentController.index.url(teamSlug)}
                            >
                                Kembali
                            </Link>
                        </Button>
                    </div>

                    {import_result && (
                        <div className="rounded-md border p-4 space-y-2 text-sm">
                            <p className="font-semibold">Hasil Import</p>
                            <p className="text-green-600">
                                ✓ {import_result.imported} siswa berhasil
                                diimport
                            </p>
                            <p className="text-yellow-600">
                                ⚠ {import_result.skipped} baris dilewati (email
                                sudah ada)
                            </p>
                            {import_result.errors.length > 0 && (
                                <ul className="text-red-600 list-disc list-inside">
                                    {import_result.errors.map((err, i) => (
                                        <li key={i}>{err}</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div className="space-y-1.5">
                            <Label htmlFor="classroom_id">
                                Kelas (opsional)
                            </Label>
                            <Select
                                value={form.data.classroom_id}
                                onValueChange={(v) =>
                                    form.setData('classroom_id', v)
                                }
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Pilih kelas (opsional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {classrooms.map((c) => (
                                        <SelectItem
                                            key={c.id}
                                            value={String(c.id)}
                                        >
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="file">File Excel (.xlsx, .xls)</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) =>
                                    form.setData(
                                        'file',
                                        e.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            {form.errors.file && (
                                <p className="text-sm text-destructive">
                                    {form.errors.file}
                                </p>
                            )}
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Mengimport...' : 'Import'}
                            </Button>
                            <Button variant="outline" asChild>
                                <a
                                    href={StudentImportController.template.url(
                                        teamSlug,
                                    )}
                                >
                                    Download Template
                                </a>
                            </Button>
                        </div>
                    </form>

                    <div className="rounded-md bg-muted p-4 text-sm space-y-1">
                        <p className="font-medium">Format file Excel:</p>
                        <ul className="list-disc list-inside text-muted-foreground">
                            <li>Kolom wajib: <strong>Nama</strong>, <strong>Email</strong>, <strong>NIS</strong></li>
                            <li>Baris pertama adalah header</li>
                            <li>Email yang sudah terdaftar akan dilewati</li>
                            <li>Password sementara akan dikirim ke email siswa</li>
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check**

```bash
npm run types:check
```

Expected: No errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/students/import/create.tsx
git commit -m "feat: add student import form page with result summary"
```

---

## Task 10: Add sidebar navigation entry

**Files:**
- Modify: `resources/js/components/app-sidebar.tsx`

- [ ] **Step 1: Import the StudentController action**

In `app-sidebar.tsx`, add import alongside other controller imports:

```tsx
import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
```

Also add the `UserCheck` icon from lucide-react to the existing icon imports:

```tsx
UserCheck,
```

- [ ] **Step 2: Add "Siswa" to the academic nav group items array**

In `app-sidebar.tsx`, add after the "Penugasan Guru" item inside `academicNavGroups[0].items`:

```tsx
{
    title: 'Siswa',
    href: slug ? StudentController.index.url(slug) : '/',
    icon: UserCheck,
},
```

- [ ] **Step 3: Run TypeScript check and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: No errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/app-sidebar.tsx
git commit -m "feat: add Siswa nav item to academic sidebar group"
```

---

## Task 11: Run full CI check

- [ ] **Step 1: Run PHP lint**

```bash
./vendor/bin/pint --test
```

Expected: No issues.

- [ ] **Step 2: Run all tests**

```bash
php artisan test --compact
```

Expected: All tests PASS, including `Students/StudentControllerTest` and `Students/StudentImportControllerTest`.

- [ ] **Step 3: Run frontend checks**

```bash
npm run lint:check && npm run format:check && npm run types:check
```

Expected: No errors.

- [ ] **Step 4: Build frontend assets**

```bash
npm run build
```

Expected: Build succeeds with no errors.

- [ ] **Step 5: Confirm all CI checks pass, then push**

```bash
git push
```

---

## Self-Review Checklist

- [x] Excel import (maatwebsite) — Task 1 ✓
- [x] Skip duplicate emails — `StudentImport::collection()` checks `User::where('email'...)->exists()` ✓
- [x] Create user + add to team — Task 5 ✓
- [x] Enroll to classroom if selected — Task 5 (`$classroomId !== null`) ✓
- [x] WelcomeStudent notification with temp password — Task 4 + 5 ✓
- [x] Download template — `StudentImportController::template()` Task 6 ✓
- [x] Import result summary — `getResult()` flashed to session, shown in Task 9 ✓
- [x] Students index page with filter — Task 8 ✓
- [x] Remove student from team + unenroll — `StudentController::destroy()` Task 3 ✓
- [x] Admin-only access — `EnsureTeamMembership::class.':admin'` middleware ✓
- [x] Sidebar navigation — Task 10 ✓
- [x] Tests for all controller actions — Tasks 3, 6 ✓
- [x] Wayfinder types regenerated — Task 7 ✓
