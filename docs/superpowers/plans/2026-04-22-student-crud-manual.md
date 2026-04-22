# Student Manual CRUD Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add manual create and edit for students to `StudentController`, following the exact same patterns used by ClassroomController and sibling modules.

**Architecture:** Extend the existing `StudentController` with `create`, `store`, `edit`, `update` methods and corresponding Form Requests. Add two new Inertia pages (`students/create`, `students/edit`) and a "Tambah Siswa" button to the index page. All new routes sit inside the existing `EnsureTeamMembership::class.':admin'` middleware group.

**Tech Stack:** Laravel 13, Inertia.js v3, React 19, TypeScript, Pest 4, Wayfinder

---

## File Map

| Action | File |
|---|---|
| Create | `app/Http/Requests/Students/StoreStudentRequest.php` |
| Create | `app/Http/Requests/Students/UpdateStudentRequest.php` |
| Create | `resources/js/pages/students/create.tsx` |
| Create | `resources/js/pages/students/edit.tsx` |
| Create | `tests/Feature/Students/StudentCreateTest.php` |
| Create | `tests/Feature/Students/StudentEditTest.php` |
| Modify | `app/Http/Controllers/Students/StudentController.php` |
| Modify | `routes/students.php` |
| Modify | `resources/js/pages/students/index.tsx` |

---

## Task 1: Add new routes

**Files:**
- Modify: `routes/students.php`

- [ ] **Step 1: Add four new routes inside the existing middleware group**

Replace the current contents of `routes/students.php` with:

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
        Route::get('students/{user}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::patch('students/{user}', [StudentController::class, 'update'])->name('update');
        Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');
    });
```

- [ ] **Step 2: Verify routes are registered**

```bash
php artisan route:list --name=students
```

Expected output shows 9 routes: `students.index`, `students.create`, `students.store`, `students.import`, `students.import.store`, `students.import.template`, `students.edit`, `students.update`, `students.destroy`.

- [ ] **Step 3: Commit**

```bash
git add routes/students.php
git commit -m "feat(students): add create/store/edit/update routes"
```

---

## Task 2: Write failing tests for create & store

**Files:**
- Create: `tests/Feature/Students/StudentCreateTest.php`

- [ ] **Step 1: Create the test file**

```bash
php artisan make:test --pest Students/StudentCreateTest
```

- [ ] **Step 2: Replace the generated file contents**

```php
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
```

- [ ] **Step 3: Run the tests — verify they fail**

```bash
php artisan test --compact --filter=StudentCreateTest
```

Expected: All tests FAIL because `StudentController` has no `create`/`store` method yet.

---

## Task 3: Implement `StoreStudentRequest`

**Files:**
- Create: `app/Http/Requests/Students/StoreStudentRequest.php`

- [ ] **Step 1: Generate the Form Request**

```bash
php artisan make:request Students/StoreStudentRequest
```

- [ ] **Step 2: Replace the generated file contents**

```php
<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;

class StoreStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'max:255'],
            'student_number' => ['nullable', 'string', 'max:50'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }
}
```

---

## Task 4: Implement `StudentController::create` and `store`

**Files:**
- Modify: `app/Http/Controllers/Students/StudentController.php`

- [ ] **Step 1: Add imports and two new methods to `StudentController`**

Replace the full file with:

```php
<?php

namespace App\Http\Controllers\Students;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Academic\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $search = $request->string('search')->trim()->value();

        $studentsQuery = $team->members()
            ->wherePivot('role', TeamRole::Student->value)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            }))
            ->with([
                'enrollments' => fn ($q) => $q
                    ->whereHas('classroom', fn ($q) => $q->where('team_id', $team->id))
                    ->with('classroom:id,name'),
            ]);

        $paginated = $studentsQuery->paginate(15)->through(fn (User $user) => [
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
            'students' => $paginated,
            'classrooms' => $classrooms,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/create', [
            'classrooms' => $classrooms,
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $validated = $request->validated();

        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $team->members()->attach($student->id, ['role' => TeamRole::Student->value]);

        if (! empty($validated['classroom_id'])) {
            StudentEnrollment::create([
                'classroom_id' => $validated['classroom_id'],
                'user_id' => $student->id,
                'student_number' => $validated['student_number'] ?? null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil ditambahkan.']);

        return to_route('students.index');
    }

    public function edit(Request $request, string $currentTeam, User $user): Response
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        $enrollment = StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->first();

        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/edit', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'enrollment' => $enrollment ? [
                'classroom_id' => $enrollment->classroom_id,
                'student_number' => $enrollment->student_number,
            ] : null,
            'classrooms' => $classrooms,
        ]);
    }

    public function update(UpdateStudentRequest $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $existingEnrollment = StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->first();

        if (! empty($validated['classroom_id'])) {
            if ($existingEnrollment) {
                $existingEnrollment->update([
                    'classroom_id' => $validated['classroom_id'],
                    'student_number' => $validated['student_number'] ?? null,
                ]);
            } else {
                StudentEnrollment::create([
                    'classroom_id' => $validated['classroom_id'],
                    'user_id' => $user->id,
                    'student_number' => $validated['student_number'] ?? null,
                ]);
            }
        } else {
            $existingEnrollment?->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data siswa berhasil diperbarui.']);

        return to_route('students.index');
    }

    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

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

- [ ] **Step 2: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Students/StudentController.php app/Http/Requests/Students/StoreStudentRequest.php --format agent
```

Expected: `{"result":"pass"}`

- [ ] **Step 3: Run the create tests — verify they pass**

```bash
php artisan test --compact --filter=StudentCreateTest
```

Expected: All 7 tests PASS.

- [ ] **Step 4: Commit**

```bash
git add app/Http/Controllers/Students/StudentController.php \
        app/Http/Requests/Students/StoreStudentRequest.php \
        tests/Feature/Students/StudentCreateTest.php
git commit -m "feat(students): add manual create/store with tests"
```

---

## Task 5: Write failing tests for edit & update

**Files:**
- Create: `tests/Feature/Students/StudentEditTest.php`

- [ ] **Step 1: Generate the test file**

```bash
php artisan make:test --pest Students/StudentEditTest
```

- [ ] **Step 2: Replace the generated file contents**

```php
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
```

- [ ] **Step 3: Run the tests — verify they fail**

```bash
php artisan test --compact --filter=StudentEditTest
```

Expected: All tests FAIL because `UpdateStudentRequest` doesn't exist and `edit`/`update` methods are missing.

---

## Task 6: Implement `UpdateStudentRequest`

**Files:**
- Create: `app/Http/Requests/Students/UpdateStudentRequest.php`

- [ ] **Step 1: Generate the Form Request**

```bash
php artisan make:request Students/UpdateStudentRequest
```

- [ ] **Step 2: Replace the generated file contents**

```php
<?php

namespace App\Http\Requests\Students;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateStudentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($this->route('user'))],
            'student_number' => ['nullable', 'string', 'max:50'],
            'classroom_id' => ['nullable', 'integer', 'exists:classrooms,id'],
        ];
    }
}
```

- [ ] **Step 3: Run pint**

```bash
./vendor/bin/pint app/Http/Requests/Students/UpdateStudentRequest.php --format agent
```

Expected: `{"result":"pass"}`

- [ ] **Step 4: Run the edit tests — verify they now pass**

```bash
php artisan test --compact --filter=StudentEditTest
```

Expected: All 10 tests PASS (the controller already has `edit`/`update` from Task 4).

- [ ] **Step 5: Run all student tests to confirm nothing regressed**

```bash
php artisan test --compact tests/Feature/Students/
```

Expected: All student tests PASS.

- [ ] **Step 6: Commit**

```bash
git add app/Http/Requests/Students/UpdateStudentRequest.php \
        tests/Feature/Students/StudentEditTest.php
git commit -m "feat(students): add manual edit/update with tests"
```

---

## Task 7: Regenerate Wayfinder types

After adding new controller methods, Wayfinder needs to regenerate its typed actions so the frontend can import them.

**Files:**
- Auto-generated: `resources/js/actions/App/Http/Controllers/Students/StudentController.ts`

- [ ] **Step 1: Regenerate Wayfinder**

```bash
php artisan wayfinder:generate
```

- [ ] **Step 2: Verify the generated file has the new methods**

Open `resources/js/actions/App/Http/Controllers/Students/StudentController.ts` and confirm it contains `create`, `store`, `edit`, `update` alongside the existing `index`, `destroy`.

---

## Task 8: Create `students/create.tsx` page

**Files:**
- Create: `resources/js/pages/students/create.tsx`

- [ ] **Step 1: Create the file**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import InputError from '@/components/input-error';
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

interface Props {
    classrooms: ClassroomOption[];
}

export default function Create({ classrooms }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        name: '',
        email: '',
        password: '',
        student_number: '',
        classroom_id: '',
    });

    function generatePassword() {
        const chars =
            'ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789';
        let pwd = '';
        for (let i = 0; i < 12; i++) {
            pwd += chars.charAt(Math.floor(Math.random() * chars.length));
        }
        form.setData('password', pwd);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(StudentController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Siswa" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Siswa</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div>
                            <Label htmlFor="password">Password</Label>
                            <div className="flex gap-2">
                                <Input
                                    id="password"
                                    type="text"
                                    value={form.data.password}
                                    onChange={(e) =>
                                        form.setData('password', e.target.value)
                                    }
                                />
                                <Button
                                    type="button"
                                    variant="outline"
                                    onClick={generatePassword}
                                >
                                    Generate
                                </Button>
                            </div>
                            <InputError message={form.errors.password} />
                        </div>
                        <div>
                            <Label htmlFor="student_number">
                                NIS (Nomor Induk Siswa)
                            </Label>
                            <Input
                                id="student_number"
                                value={form.data.student_number}
                                onChange={(e) =>
                                    form.setData(
                                        'student_number',
                                        e.target.value,
                                    )
                                }
                                placeholder="Opsional"
                            />
                            <InputError message={form.errors.student_number} />
                        </div>
                        <div>
                            <Label htmlFor="classroom_id">Kelas</Label>
                            <Select
                                value={form.data.classroom_id || 'none'}
                                onValueChange={(v) =>
                                    form.setData(
                                        'classroom_id',
                                        v === 'none' ? '' : v,
                                    )
                                }
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Tidak ada kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Tidak ada kelas
                                    </SelectItem>
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
                            <InputError message={form.errors.classroom_id} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={StudentController.index.url(teamSlug)}
                                >
                                    Batal
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

---

## Task 9: Create `students/edit.tsx` page

**Files:**
- Create: `resources/js/pages/students/edit.tsx`

- [ ] **Step 1: Create the file**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import InputError from '@/components/input-error';
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

interface StudentData {
    id: number;
    name: string;
    email: string;
}

interface EnrollmentData {
    classroom_id: number;
    student_number: string | null;
}

interface Props {
    student: StudentData;
    enrollment: EnrollmentData | null;
    classrooms: ClassroomOption[];
}

export default function Edit({ student, enrollment, classrooms }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        name: student.name,
        email: student.email,
        student_number: enrollment?.student_number ?? '',
        classroom_id: enrollment ? String(enrollment.classroom_id) : '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            StudentController.update.url({
                current_team: teamSlug,
                user: student.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Siswa" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Siswa</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div>
                            <Label htmlFor="student_number">
                                NIS (Nomor Induk Siswa)
                            </Label>
                            <Input
                                id="student_number"
                                value={form.data.student_number}
                                onChange={(e) =>
                                    form.setData(
                                        'student_number',
                                        e.target.value,
                                    )
                                }
                                placeholder="Opsional"
                            />
                            <InputError message={form.errors.student_number} />
                        </div>
                        <div>
                            <Label htmlFor="classroom_id">Kelas</Label>
                            <Select
                                value={form.data.classroom_id || 'none'}
                                onValueChange={(v) =>
                                    form.setData(
                                        'classroom_id',
                                        v === 'none' ? '' : v,
                                    )
                                }
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Tidak ada kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Tidak ada kelas
                                    </SelectItem>
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
                            <InputError message={form.errors.classroom_id} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Perbarui
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={StudentController.index.url(teamSlug)}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

---

## Task 10: Add "Tambah Siswa" button to index page and "Edit" action to each row

**Files:**
- Modify: `resources/js/pages/students/index.tsx`

- [ ] **Step 1: Update `<PageHeader>` to show two action buttons**

Replace the `action` prop of `<PageHeader>` (lines 127–140 in the current file):

```tsx
action={
    <div className="flex gap-2">
        <Button asChild variant="outline">
            <Link
                href={StudentImportController.create.url(
                    teamSlug,
                )}
            >
                Import Siswa
            </Link>
        </Button>
        <Button asChild>
            <Link href={StudentController.create.url(teamSlug)}>
                Tambah Siswa
            </Link>
        </Button>
    </div>
}
```

- [ ] **Step 2: Add an "Edit" button in the Aksi column for each row**

Replace the `<TableCell>` in the Aksi column (currently contains only the "Hapus" button) with:

```tsx
<TableCell>
    <div className="flex gap-2">
        <Button
            size="sm"
            variant="outline"
            asChild
        >
            <Link
                href={StudentController.edit.url({
                    current_team: teamSlug,
                    user: student.id,
                })}
            >
                Edit
            </Link>
        </Button>
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
    </div>
</TableCell>
```

- [ ] **Step 3: Run TypeScript check**

```bash
npm run types:check
```

Expected: No errors.

- [ ] **Step 4: Run lint**

```bash
npm run lint:check
```

Expected: No errors. If there are import-order issues, run `npm run lint` to auto-fix.

- [ ] **Step 5: Commit frontend changes**

```bash
git add resources/js/pages/students/create.tsx \
        resources/js/pages/students/edit.tsx \
        resources/js/pages/students/index.tsx \
        resources/js/actions/App/Http/Controllers/Students/StudentController.ts
git commit -m "feat(students): add create and edit pages, Tambah Siswa button in index"
```

---

## Task 11: Final verification

- [ ] **Step 1: Run the full test suite**

```bash
php artisan test --compact
```

Expected: All tests pass (231 existing + 17 new = 248 total).

- [ ] **Step 2: Run pint on all modified PHP files**

```bash
./vendor/bin/pint --dirty --format agent
```

Expected: `{"result":"pass"}`

- [ ] **Step 3: Commit any pint fixes if needed, then run CI check**

```bash
composer ci:check
```

Expected: All checks pass (lint, format, types, tests).
