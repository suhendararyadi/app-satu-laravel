# Fase 2: Foundation Akademik — Part 2: Controllers (Tasks 6–10)

> Back to index: [2026-04-21-fase2-foundation-akademik.md](./2026-04-21-fase2-foundation-akademik.md)

---

## Task 6: AcademicYearController (12 methods)

- [ ] Run `php artisan make:request Academic/StoreAcademicYearRequest --no-interaction`
- [ ] Implement `app/Http/Requests/Academic/StoreAcademicYearRequest.php`:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['required', 'string', 'max:100'],
            'start_year' => ['required', 'integer', 'min:2000', 'max:2100'],
            'end_year'   => ['required', 'integer', 'min:2000', 'max:2100', 'gt:start_year'],
        ];
    }
}
```

- [ ] Run `php artisan make:request Academic/UpdateAcademicYearRequest --no-interaction`
- [ ] Implement `app/Http/Requests/Academic/UpdateAcademicYearRequest.php`:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAcademicYearRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'       => ['sometimes', 'required', 'string', 'max:100'],
            'start_year' => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100'],
            'end_year'   => ['sometimes', 'required', 'integer', 'min:2000', 'max:2100', 'gt:start_year'],
        ];
    }
}
```

- [ ] Run `php artisan make:request Academic/StoreSemesterRequest --no-interaction`
- [ ] Implement `app/Http/Requests/Academic/StoreSemesterRequest.php`:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['required', 'string', 'max:100'],
            'order' => ['required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
```

- [ ] Run `php artisan make:request Academic/UpdateSemesterRequest --no-interaction`
- [ ] Implement `app/Http/Requests/Academic/UpdateSemesterRequest.php`:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSemesterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name'  => ['sometimes', 'required', 'string', 'max:100'],
            'order' => ['sometimes', 'required', 'integer', 'min:1', 'max:10'],
        ];
    }
}
```

- [ ] Run `php artisan make:controller Academic/AcademicYearController --no-interaction`
- [ ] Implement `app/Http/Controllers/Academic/AcademicYearController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAcademicYearRequest;
use App\Http\Requests\Academic\StoreSemesterRequest;
use App\Http\Requests\Academic\UpdateAcademicYearRequest;
use App\Http\Requests\Academic\UpdateSemesterRequest;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Semester;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AcademicYearController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $years = $team->academicYears()->with('semesters')->orderByDesc('start_year')->get();

        return Inertia::render('academic/years/index', ['years' => $years]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/years/create');
    }

    public function store(StoreAcademicYearRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $year = $team->academicYears()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil dibuat.']);

        return to_route('academic.years.edit', $year);
    }

    public function edit(Request $request, string $currentTeam, AcademicYear $year): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        $year->load('semesters');

        return Inertia::render('academic/years/edit', ['year' => $year]);
    }

    public function update(UpdateAcademicYearRequest $request, string $currentTeam, AcademicYear $year): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        $year->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil diperbarui.']);

        return to_route('academic.years.edit', $year);
    }

    public function destroy(Request $request, string $currentTeam, AcademicYear $year): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        $year->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahun ajaran berhasil dihapus.']);

        return to_route('academic.years.index');
    }

    public function activate(Request $request, string $currentTeam, AcademicYear $year): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        $team->academicYears()->update(['is_active' => false]);
        $year->update(['is_active' => true]);
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tahun ajaran diaktifkan.']);

        return to_route('academic.years.index');
    }

    public function createSemester(Request $request, string $currentTeam, AcademicYear $year): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);

        return Inertia::render('academic/years/semester-create', ['year' => $year]);
    }

    public function storeSemester(StoreSemesterRequest $request, string $currentTeam, AcademicYear $year): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        $year->semesters()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Semester berhasil dibuat.']);

        return to_route('academic.years.edit', $year);
    }

    public function editSemester(Request $request, string $currentTeam, AcademicYear $year, Semester $semester): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        abort_if($semester->academic_year_id !== $year->id, 403);

        return Inertia::render('academic/years/semester-edit', ['year' => $year, 'semester' => $semester]);
    }

    public function updateSemester(UpdateSemesterRequest $request, string $currentTeam, AcademicYear $year, Semester $semester): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        abort_if($semester->academic_year_id !== $year->id, 403);
        $semester->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Semester berhasil diperbarui.']);

        return to_route('academic.years.edit', $year);
    }

    public function destroySemester(Request $request, string $currentTeam, AcademicYear $year, Semester $semester): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($year->team_id !== $team->id, 403);
        abort_if($semester->academic_year_id !== $year->id, 403);
        $semester->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Semester berhasil dihapus.']);

        return to_route('academic.years.edit', $year);
    }
}
```

- [ ] Run `php artisan make:test Feature/Academic/AcademicYearControllerTest --pest --no-interaction`
- [ ] Implement `tests/Feature/Academic/AcademicYearControllerTest.php`:

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
});

function makeYearUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $owner->currentTeam()->associate($team)->save();
    URL::defaults(['current_team' => $team->slug]);

    return [$owner, $team];
}

it('shows academic years index', function () {
    [$owner, $team] = makeYearUser();
    AcademicYear::factory()->for($team)->create(['name' => '2024/2025']);

    $this->actingAs($owner)
        ->get(route('academic.years.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/years/index')
            ->has('years', 1)
        );
});

it('shows create form', function () {
    [$owner] = makeYearUser();

    $this->actingAs($owner)
        ->get(route('academic.years.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/create'));
});

it('stores academic year', function () {
    [$owner, $team] = makeYearUser();

    $this->actingAs($owner)
        ->post(route('academic.years.store'), [
            'name'       => '2025/2026',
            'start_year' => 2025,
            'end_year'   => 2026,
        ])
        ->assertRedirect();

    expect($team->academicYears()->count())->toBe(1);
});

it('validates store rules', function () {
    [$owner] = makeYearUser();

    $this->actingAs($owner)
        ->post(route('academic.years.store'), [
            'name'       => '',
            'start_year' => 2026,
            'end_year'   => 2025,
        ])
        ->assertSessionHasErrors(['name', 'end_year']);
});

it('shows edit form', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create();

    $this->actingAs($owner)
        ->get(route('academic.years.edit', $year))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/edit')->has('year'));
});

it('updates academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create(['name' => 'Old Name']);

    $this->actingAs($owner)
        ->patch(route('academic.years.update', $year), ['name' => 'New Name'])
        ->assertRedirect();

    expect($year->fresh()->name)->toBe('New Name');
});

it('deletes academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('academic.years.destroy', $year))
        ->assertRedirect(route('academic.years.index'));

    expect($team->academicYears()->count())->toBe(0);
});

it('activates academic year', function () {
    [$owner, $team] = makeYearUser();
    $year = AcademicYear::factory()->for($team)->create(['is_active' => false]);

    $this->actingAs($owner)
        ->post(route('academic.years.activate', $year))
        ->assertRedirect();

    expect($year->fresh()->is_active)->toBeTrue();
});

it('returns 403 for year belonging to another team', function () {
    [$owner] = makeYearUser();
    $other = AcademicYear::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.years.edit', $other))
        ->assertForbidden();
});
```

- [ ] Run `php artisan make:test Feature/Academic/SemesterControllerTest --pest --no-interaction`
- [ ] Implement `tests/Feature/Academic/SemesterControllerTest.php`:

```php
<?php

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Semester;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\URL;

beforeEach(function () {
    $this->withoutVite();
});

function makeSemesterContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create(['user_id' => $owner->id]);
    $owner->currentTeam()->associate($team)->save();
    $year = AcademicYear::factory()->for($team)->create();
    URL::defaults(['current_team' => $team->slug]);

    return [$owner, $team, $year];
}

it('shows create semester form', function () {
    [$owner, , $year] = makeSemesterContext();

    $this->actingAs($owner)
        ->get(route('academic.years.semesters.create', $year))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/semester-create'));
});

it('stores semester', function () {
    [$owner, , $year] = makeSemesterContext();

    $this->actingAs($owner)
        ->post(route('academic.years.semesters.store', $year), [
            'name'  => 'Semester 1',
            'order' => 1,
        ])
        ->assertRedirect();

    expect($year->semesters()->count())->toBe(1);
});

it('shows edit semester form', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create();

    $this->actingAs($owner)
        ->get(route('academic.years.semesters.edit', [$year, $semester]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/years/semester-edit'));
});

it('updates semester', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create(['name' => 'Old']);

    $this->actingAs($owner)
        ->patch(route('academic.years.semesters.update', [$year, $semester]), ['name' => 'New'])
        ->assertRedirect();

    expect($semester->fresh()->name)->toBe('New');
});

it('deletes semester', function () {
    [$owner, , $year] = makeSemesterContext();
    $semester = Semester::factory()->for($year, 'academicYear')->create();

    $this->actingAs($owner)
        ->delete(route('academic.years.semesters.destroy', [$year, $semester]))
        ->assertRedirect();

    expect($year->semesters()->count())->toBe(0);
});
```

- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=AcademicYear`

---

## Task 7: GradeController

- [ ] Run `php artisan make:request Academic/StoreGradeRequest --no-interaction`
- [ ] Implement rules: `name` required string max:100, `order` required integer min:1 max:20
- [ ] Run `php artisan make:request Academic/UpdateGradeRequest --no-interaction`
- [ ] Implement rules: same with `sometimes`
- [ ] Run `php artisan make:controller Academic/GradeController --no-interaction`
- [ ] Implement `app/Http/Controllers/Academic/GradeController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreGradeRequest;
use App\Http\Requests\Academic\UpdateGradeRequest;
use App\Models\Academic\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $grades = $team->grades()->orderBy('order')->get();

        return Inertia::render('academic/grades/index', ['grades' => $grades]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/grades/create');
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $grade = $team->grades()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil dibuat.']);

        return to_route('academic.grades.edit', $grade);
    }

    public function edit(Request $request, string $currentTeam, Grade $grade): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);

        return Inertia::render('academic/grades/edit', ['grade' => $grade]);
    }

    public function update(UpdateGradeRequest $request, string $currentTeam, Grade $grade): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);
        $grade->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil diperbarui.']);

        return to_route('academic.grades.edit', $grade);
    }

    public function destroy(Request $request, string $currentTeam, Grade $grade): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);
        $grade->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil dihapus.']);

        return to_route('academic.grades.index');
    }
}
```

- [ ] Run `php artisan make:test Feature/Academic/GradeControllerTest --pest --no-interaction`
- [ ] Implement test with: index, create, store (valid + validation error), edit, update, destroy, 403 for other team
- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=Grade`

---

## Task 8: SubjectController

- [ ] Run `php artisan make:request Academic/StoreSubjectRequest --no-interaction`
- [ ] Implement rules: `name` required string max:200, `code` nullable string max:20
- [ ] Run `php artisan make:request Academic/UpdateSubjectRequest --no-interaction`
- [ ] Implement rules: same with `sometimes`
- [ ] Run `php artisan make:controller Academic/SubjectController --no-interaction`
- [ ] Implement `app/Http/Controllers/Academic/SubjectController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSubjectRequest;
use App\Http\Requests\Academic\UpdateSubjectRequest;
use App\Models\Academic\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $subjects = $team->subjects()->orderBy('name')->get();

        return Inertia::render('academic/subjects/index', ['subjects' => $subjects]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/subjects/create');
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $subject = $team->subjects()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil dibuat.']);

        return to_route('academic.subjects.edit', $subject);
    }

    public function edit(Request $request, string $currentTeam, Subject $subject): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);

        return Inertia::render('academic/subjects/edit', ['subject' => $subject]);
    }

    public function update(UpdateSubjectRequest $request, string $currentTeam, Subject $subject): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);
        $subject->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil diperbarui.']);

        return to_route('academic.subjects.edit', $subject);
    }

    public function destroy(Request $request, string $currentTeam, Subject $subject): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);
        $subject->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil dihapus.']);

        return to_route('academic.subjects.index');
    }
}
```

- [ ] Run `php artisan make:test Feature/Academic/SubjectControllerTest --pest --no-interaction`
- [ ] Implement test covering index, store, edit, update, destroy, 403
- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=Subject`

---

## Task 9: ClassroomController

- [ ] Run `php artisan make:request Academic/StoreClassroomRequest --no-interaction`
- [ ] Implement rules: `name` required string max:100, `academic_year_id` required exists:academic_years,id, `grade_id` required exists:grades,id
- [ ] Run `php artisan make:request Academic/UpdateClassroomRequest --no-interaction`
- [ ] Implement rules: same with `sometimes`
- [ ] Run `php artisan make:request Academic/EnrollStudentRequest --no-interaction`
- [ ] Implement rules: `user_id` required exists:users,id, `student_number` nullable string max:50
- [ ] Run `php artisan make:controller Academic/ClassroomController --no-interaction`
- [ ] Implement `app/Http/Controllers/Academic/ClassroomController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\EnrollStudentRequest;
use App\Http\Requests\Academic\StoreClassroomRequest;
use App\Http\Requests\Academic\UpdateClassroomRequest;
use App\Models\Academic\Classroom;
use App\Models\Academic\StudentEnrollment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ClassroomController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $classrooms = $team->classrooms()->with(['academicYear', 'grade'])->get();

        return Inertia::render('academic/classrooms/index', ['classrooms' => $classrooms]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('academic/classrooms/create', [
            'academicYears' => $team->academicYears()->orderByDesc('start_year')->get(),
            'grades'        => $team->grades()->orderBy('order')->get(),
        ]);
    }

    public function store(StoreClassroomRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $classroom = $team->classrooms()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kelas berhasil dibuat.']);

        return to_route('academic.classrooms.show', $classroom);
    }

    public function show(Request $request, string $currentTeam, Classroom $classroom): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);
        $classroom->load(['academicYear', 'grade', 'enrollments.user']);

        return Inertia::render('academic/classrooms/show', [
            'classroom' => $classroom,
            'students'  => $team->members()->get(),
        ]);
    }

    public function edit(Request $request, string $currentTeam, Classroom $classroom): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);

        return Inertia::render('academic/classrooms/edit', [
            'classroom'     => $classroom,
            'academicYears' => $team->academicYears()->orderByDesc('start_year')->get(),
            'grades'        => $team->grades()->orderBy('order')->get(),
        ]);
    }

    public function update(UpdateClassroomRequest $request, string $currentTeam, Classroom $classroom): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);
        $classroom->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kelas berhasil diperbarui.']);

        return to_route('academic.classrooms.show', $classroom);
    }

    public function destroy(Request $request, string $currentTeam, Classroom $classroom): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);
        $classroom->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kelas berhasil dihapus.']);

        return to_route('academic.classrooms.index');
    }

    public function enrollStudent(EnrollStudentRequest $request, string $currentTeam, Classroom $classroom): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);
        $classroom->enrollments()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil ditambahkan ke kelas.']);

        return to_route('academic.classrooms.show', $classroom);
    }

    public function unenrollStudent(Request $request, string $currentTeam, Classroom $classroom, StudentEnrollment $enrollment): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);
        abort_if($enrollment->classroom_id !== $classroom->id, 403);
        $enrollment->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil dikeluarkan dari kelas.']);

        return to_route('academic.classrooms.show', $classroom);
    }
}
```

- [ ] Run `php artisan make:test Feature/Academic/ClassroomControllerTest --pest --no-interaction`
- [ ] Implement test covering index, create, store, show, edit, update, destroy, 403
- [ ] Run `php artisan make:test Feature/Academic/StudentEnrollmentTest --pest --no-interaction`
- [ ] Implement test covering enrollStudent (success + duplicate), unenrollStudent, 403
- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=Classroom`

---

## Task 10: TeacherAssignmentController

- [ ] Run `php artisan make:request Academic/StoreTeacherAssignmentRequest --no-interaction`
- [ ] Implement rules:

```php
public function rules(): array
{
    return [
        'academic_year_id' => ['required', 'integer', 'exists:academic_years,id'],
        'subject_id'       => ['required', 'integer', 'exists:subjects,id'],
        'classroom_id'     => ['required', 'integer', 'exists:classrooms,id'],
        'user_id'          => ['required', 'integer', 'exists:users,id'],
    ];
}
```

- [ ] Run `php artisan make:controller Academic/TeacherAssignmentController --no-interaction`
- [ ] Implement `app/Http/Controllers/Academic/TeacherAssignmentController.php`:

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreTeacherAssignmentRequest;
use App\Models\Academic\TeacherAssignment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TeacherAssignmentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('academic/assignments/index', [
            'assignments'   => $team->teacherAssignments()->with(['academicYear', 'subject', 'classroom.grade', 'user'])->get(),
            'academicYears' => $team->academicYears()->orderByDesc('start_year')->get(),
            'subjects'      => $team->subjects()->orderBy('name')->get(),
            'classrooms'    => $team->classrooms()->with(['grade'])->get(),
            'teachers'      => $team->members()->get(),
        ]);
    }

    public function store(StoreTeacherAssignmentRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $team->teacherAssignments()->create(array_merge($request->validated(), ['team_id' => $team->id]));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penugasan guru berhasil dibuat.']);

        return to_route('academic.assignments.index');
    }

    public function destroy(Request $request, string $currentTeam, TeacherAssignment $assignment): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assignment->team_id !== $team->id, 403);
        $assignment->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Penugasan guru berhasil dihapus.']);

        return to_route('academic.assignments.index');
    }
}
```

- [ ] Run `php artisan make:test Feature/Academic/TeacherAssignmentControllerTest --pest --no-interaction`
- [ ] Implement test covering index, store, destroy, 403
- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=TeacherAssignment`
