# Fase 2: Foundation Akademik — Part 1: Backend Setup (Tasks 1–5)

> Back to index: [2026-04-21-fase2-foundation-akademik.md](./2026-04-21-fase2-foundation-akademik.md)

---

## Task 1: Extend TeamRole Enum

- [ ] Update `app/Enums/TeamRole.php`:

```php
<?php

namespace App\Enums;

enum TeamRole: string
{
    case Owner   = 'owner';
    case Admin   = 'admin';
    case Teacher = 'teacher';
    case Student = 'student';
    case Parent  = 'parent';

    public function label(): string
    {
        return match($this) {
            self::Owner   => 'Pemilik',
            self::Admin   => 'Admin',
            self::Teacher => 'Guru',
            self::Student => 'Siswa',
            self::Parent  => 'Orang Tua',
        };
    }

    public function permissions(): array
    {
        return match($this) {
            self::Owner   => ['*'],
            self::Admin   => ['manage-team', 'manage-content', 'manage-academic'],
            self::Teacher => [],
            self::Student => [],
            self::Parent  => [],
        };
    }

    public function level(): int
    {
        return match($this) {
            self::Owner   => 5,
            self::Admin   => 4,
            self::Teacher => 3,
            self::Student => 2,
            self::Parent  => 1,
        };
    }
}
```

- [ ] Update `resources/js/types/teams.ts` — change the `TeamRole` type:

```typescript
// FROM:
export type TeamRole = 'owner' | 'admin' | 'member';

// TO:
export type TeamRole = 'owner' | 'admin' | 'teacher' | 'student' | 'parent';
```

- [ ] In `tests/Feature/CMS/GalleryControllerTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/CMS/PageControllerTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/CMS/PostControllerTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/Teams/TeamInvitationTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/Teams/TeamMemberTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/Teams/TeamTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `tests/Feature/School/SchoolProfileTest.php` — replace all `TeamRole::Member` → `TeamRole::Student`
- [ ] In `database/factories/TeamInvitationFactory.php` — change default role from `TeamRole::Member` to `TeamRole::Student`
- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `php artisan test --compact --filter=Team` to verify existing tests still pass

---

## Task 2: TypeScript Academic Types

- [ ] Create `resources/js/types/academic.ts`:

```typescript
export interface AcademicYear {
    id: number;
    team_id: number;
    name: string;
    start_year: number;
    end_year: number;
    is_active: boolean;
    semesters?: Semester[];
    created_at: string;
    updated_at: string;
}

export interface Semester {
    id: number;
    academic_year_id: number;
    name: string;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Grade {
    id: number;
    team_id: number;
    name: string;
    order: number;
    created_at: string;
    updated_at: string;
}

export interface Subject {
    id: number;
    team_id: number;
    name: string;
    code: string | null;
    created_at: string;
    updated_at: string;
}

export interface Classroom {
    id: number;
    team_id: number;
    academic_year_id: number;
    grade_id: number;
    name: string;
    academic_year?: AcademicYear;
    grade?: Grade;
    enrollments?: StudentEnrollment[];
    created_at: string;
    updated_at: string;
}

export interface StudentEnrollment {
    id: number;
    classroom_id: number;
    user_id: number;
    student_number: string | null;
    user?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

export interface TeacherAssignment {
    id: number;
    team_id: number;
    academic_year_id: number;
    subject_id: number;
    classroom_id: number;
    user_id: number;
    academic_year?: AcademicYear;
    subject?: Subject;
    classroom?: Classroom;
    user?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

export interface Guardian {
    id: number;
    student_id: number;
    guardian_id: number;
    relationship: 'ayah' | 'ibu' | 'wali';
    student?: { id: number; name: string };
    guardian?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}
```

- [ ] Run `npm run types:check`

---

## Task 3: Database Migrations

- [ ] Run `php artisan make:migration create_academic_years_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('academic_years', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->unsignedSmallInteger('start_year');
    $table->unsignedSmallInteger('end_year');
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
```

- [ ] Run `php artisan make:migration create_semesters_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('semesters', function (Blueprint $table) {
    $table->id();
    $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->unsignedTinyInteger('order')->default(1);
    $table->boolean('is_active')->default(false);
    $table->timestamps();
});
```

- [ ] Run `php artisan make:migration create_grades_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('grades', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->unsignedTinyInteger('order')->default(1);
    $table->timestamps();
});
```

- [ ] Run `php artisan make:migration create_subjects_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('subjects', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->string('name', 200);
    $table->string('code', 20)->nullable();
    $table->timestamps();
});
```

- [ ] Run `php artisan make:migration create_classrooms_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('classrooms', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
    $table->foreignId('grade_id')->constrained()->cascadeOnDelete();
    $table->string('name', 100);
    $table->timestamps();
});
```

- [ ] Run `php artisan make:migration create_student_enrollments_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('student_enrollments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->string('student_number', 50)->nullable();
    $table->timestamps();
    $table->unique(['classroom_id', 'user_id']);
});
```

- [ ] Run `php artisan make:migration create_teacher_assignments_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('teacher_assignments', function (Blueprint $table) {
    $table->id();
    $table->foreignId('team_id')->constrained()->cascadeOnDelete();
    $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
    $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
    $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
    $table->foreignId('user_id')->constrained()->cascadeOnDelete();
    $table->timestamps();
    $table->unique(['academic_year_id', 'subject_id', 'classroom_id', 'user_id']);
});
```

- [ ] Run `php artisan make:migration create_guardians_table --no-interaction`
- [ ] Implement migration:

```php
Schema::create('guardians', function (Blueprint $table) {
    $table->id();
    $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
    $table->foreignId('guardian_id')->constrained('users')->cascadeOnDelete();
    $table->string('relationship', 20);
    $table->timestamps();
    $table->unique(['student_id', 'guardian_id']);
});
```

- [ ] Run `php artisan migrate`

---

## Task 4: Models, Factories, Enum, Team Relations

- [ ] Create `app/Enums/GuardianRelationship.php`:

```php
<?php

namespace App\Enums;

enum GuardianRelationship: string
{
    case Ayah = 'ayah';
    case Ibu  = 'ibu';
    case Wali = 'wali';

    public function label(): string
    {
        return match($this) {
            self::Ayah => 'Ayah',
            self::Ibu  => 'Ibu',
            self::Wali => 'Wali',
        };
    }
}
```

- [ ] Run `php artisan make:model Academic/AcademicYear --no-interaction`
- [ ] Implement `app/Models/Academic/AcademicYear.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'start_year', 'end_year', 'is_active'])]
class AcademicYear extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function semesters(): HasMany
    {
        return $this->hasMany(Semester::class)->orderBy('order');
    }
}
```

- [ ] Run `php artisan make:model Academic/Semester --no-interaction`
- [ ] Implement `app/Models/Academic/Semester.php`:

```php
<?php

namespace App\Models\Academic;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['academic_year_id', 'name', 'order', 'is_active'])]
class Semester extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/Grade --no-interaction`
- [ ] Implement `app/Models/Academic/Grade.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'name', 'order'])]
class Grade extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function classrooms(): HasMany
    {
        return $this->hasMany(Classroom::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/Subject --no-interaction`
- [ ] Implement `app/Models/Academic/Subject.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'name', 'code'])]
class Subject extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/Classroom --no-interaction`
- [ ] Implement `app/Models/Academic/Classroom.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'academic_year_id', 'grade_id', 'name'])]
class Classroom extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/StudentEnrollment --no-interaction`
- [ ] Implement `app/Models/Academic/StudentEnrollment.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['classroom_id', 'user_id', 'student_number'])]
class StudentEnrollment extends Model
{
    use HasFactory;

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/TeacherAssignment --no-interaction`
- [ ] Implement `app/Models/Academic/TeacherAssignment.php`:

```php
<?php

namespace App\Models\Academic;

use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'academic_year_id', 'subject_id', 'classroom_id', 'user_id'])]
class TeacherAssignment extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] Run `php artisan make:model Academic/Guardian --no-interaction`
- [ ] Implement `app/Models/Academic/Guardian.php`:

```php
<?php

namespace App\Models\Academic;

use App\Enums\GuardianRelationship;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['student_id', 'guardian_id', 'relationship'])]
class Guardian extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return [
            'relationship' => GuardianRelationship::class,
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    public function guardian(): BelongsTo
    {
        return $this->belongsTo(User::class, 'guardian_id');
    }
}
```

- [ ] Update `app/Models/Team.php` — add 6 hasMany relations (after existing relations):

```php
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Grade;
use App\Models\Academic\Subject;
use App\Models\Academic\Classroom;
use App\Models\Academic\TeacherAssignment;
// ...

public function academicYears(): HasMany
{
    return $this->hasMany(AcademicYear::class);
}

public function grades(): HasMany
{
    return $this->hasMany(Grade::class);
}

public function subjects(): HasMany
{
    return $this->hasMany(Subject::class);
}

public function classrooms(): HasMany
{
    return $this->hasMany(Classroom::class);
}

public function teacherAssignments(): HasMany
{
    return $this->hasMany(TeacherAssignment::class);
}
```

- [ ] Create factories in `database/factories/Academic/`:
    - `AcademicYearFactory.php` — name: `fake()->year().'/'.fake()->year()`, start_year: `fake()->year()`, end_year: `start_year + 1`, is_active: false
    - `SemesterFactory.php` — name: `'Semester '.fake()->numberBetween(1,2)`, order: 1, is_active: false
    - `GradeFactory.php` — name: `'Kelas '.fake()->randomElement(['X','XI','XII'])`, order: 1
    - `SubjectFactory.php` — name: `fake()->word()`, code: `fake()->optional()->lexify('???')`
    - `ClassroomFactory.php` — name: `fake()->bothify('?-##')`
    - `StudentEnrollmentFactory.php` — student_number: `fake()->optional()->numerify('###########')`
    - `TeacherAssignmentFactory.php` — all FKs provided by test context
    - `GuardianFactory.php` — relationship: `fake()->randomElement(['ayah','ibu','wali'])`

- [ ] Run `./vendor/bin/pint --dirty --format agent`

---

## Task 5: Routes

- [ ] Create `routes/academic.php`:

```php
<?php

use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\ClassroomController;
use App\Http\Controllers\Academic\GradeController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\TeacherAssignmentController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('academic.')
    ->group(function () {
        // Academic Years + Semesters
        Route::resource('academic/years', AcademicYearController::class)->except(['show']);
        Route::post('academic/years/{year}/activate', [AcademicYearController::class, 'activate'])
            ->name('years.activate');
        Route::get('academic/years/{year}/semesters/create', [AcademicYearController::class, 'createSemester'])
            ->name('years.semesters.create');
        Route::post('academic/years/{year}/semesters', [AcademicYearController::class, 'storeSemester'])
            ->name('years.semesters.store');
        Route::get('academic/years/{year}/semesters/{semester}/edit', [AcademicYearController::class, 'editSemester'])
            ->name('years.semesters.edit');
        Route::patch('academic/years/{year}/semesters/{semester}', [AcademicYearController::class, 'updateSemester'])
            ->name('years.semesters.update');
        Route::delete('academic/years/{year}/semesters/{semester}', [AcademicYearController::class, 'destroySemester'])
            ->name('years.semesters.destroy');

        // Grades
        Route::resource('academic/grades', GradeController::class)->except(['show']);

        // Subjects
        Route::resource('academic/subjects', SubjectController::class)->except(['show']);

        // Classrooms + Enrollment
        Route::resource('academic/classrooms', ClassroomController::class);
        Route::post('academic/classrooms/{classroom}/enroll', [ClassroomController::class, 'enrollStudent'])
            ->name('classrooms.enroll');
        Route::delete('academic/classrooms/{classroom}/enroll/{enrollment}', [ClassroomController::class, 'unenrollStudent'])
            ->name('classrooms.unenroll');

        // Teacher Assignments
        Route::get('academic/assignments', [TeacherAssignmentController::class, 'index'])
            ->name('assignments.index');
        Route::post('academic/assignments', [TeacherAssignmentController::class, 'store'])
            ->name('assignments.store');
        Route::delete('academic/assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
            ->name('assignments.destroy');
    });
```

- [ ] Update `routes/web.php` — add after the CMS require line:

```php
require __DIR__.'/academic.php';
```

- [ ] Run `php artisan route:list --name=academic --except-vendor` to verify all routes registered
