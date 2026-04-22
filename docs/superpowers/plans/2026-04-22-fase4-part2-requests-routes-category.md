# Fase 4: Penilaian & Rapor — Part 2: Requests, Routes, AssessmentCategoryController

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Prerequisites:** Part 1 complete (migrations, models, factories exist).

**Spec:** `docs/superpowers/specs/2026-04-22-fase4-penilaian-rapor-design.md`

---

## Task 4: Form Requests

**Files:** 7 form request files in `app/Http/Requests/Academic/`

- [ ] **Step 1: Create request files**

```bash
php artisan make:request Academic/StoreAssessmentCategoryRequest --no-interaction
php artisan make:request Academic/UpdateAssessmentCategoryRequest --no-interaction
php artisan make:request Academic/StoreAssessmentRequest --no-interaction
php artisan make:request Academic/UpdateAssessmentRequest --no-interaction
php artisan make:request Academic/StoreScoresRequest --no-interaction
php artisan make:request Academic/StoreReportCardRequest --no-interaction
php artisan make:request Academic/UpdateReportCardRequest --no-interaction
```

- [ ] **Step 2: Write StoreAssessmentCategoryRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
```

- [ ] **Step 3: Write UpdateAssessmentCategoryRequest**

Same rules as Store. Full content:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentCategoryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'weight' => ['required', 'numeric', 'min:0', 'max:100'],
        ];
    }
}
```

- [ ] **Step 4: Write StoreAssessmentRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'assessment_category_id' => ['required', 'integer', 'exists:assessment_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ];
    }
}
```

- [ ] **Step 5: Write UpdateAssessmentRequest**

Same rules as Store:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateAssessmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'assessment_category_id' => ['required', 'integer', 'exists:assessment_categories,id'],
            'title' => ['required', 'string', 'max:255'],
            'max_score' => ['required', 'numeric', 'min:0'],
            'date' => ['required', 'date'],
        ];
    }
}
```

- [ ] **Step 6: Write StoreScoresRequest**

`$this->route('assessment')` returns the route-bound Assessment model:

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreScoresRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $assessment = $this->route('assessment');

        return [
            'scores' => ['required', 'array', 'min:1'],
            'scores.*.student_user_id' => ['required', 'integer', 'exists:users,id'],
            'scores.*.score' => ['nullable', 'numeric', 'min:0', 'max:' . $assessment->max_score],
            'scores.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 7: Write StoreReportCardRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class StoreReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'student_user_id' => ['required', 'integer', 'exists:users,id'],
            'homeroom_notes' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 8: Write UpdateReportCardRequest**

```php
<?php

namespace App\Http\Requests\Academic;

use Illuminate\Foundation\Http\FormRequest;

class UpdateReportCardRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'homeroom_notes' => ['nullable', 'string'],
        ];
    }
}
```

- [ ] **Step 9: Run pint**

```bash
./vendor/bin/pint app/Http/Requests/Academic/ --format agent
```

- [ ] **Step 10: Commit**

```bash
git add app/Http/Requests/Academic/
git commit -m "feat: add form requests for assessment categories, assessments, scores, and report cards"
```

---

## Task 5: Routes

**Files:**
- Modify: `routes/academic.php`

The existing file has one group with `:admin` middleware. We need to:
1. Add `assessment-categories` resource inside the **existing** `:admin` group
2. Add a **new** group with `:teacher` middleware for assessments + report-cards

- [ ] **Step 1: Add imports and routes to `routes/academic.php`**

Add 3 new `use` statements at the top (after existing imports):

```php
use App\Http\Controllers\Academic\AssessmentCategoryController;
use App\Http\Controllers\Academic\AssessmentController;
use App\Http\Controllers\Academic\ReportCardController;
```

- [ ] **Step 2: Extend existing `:admin` group**

Inside the existing admin group (before the closing `});`), add:

```php
        // Assessment Categories
        Route::resource('academic/assessment-categories', AssessmentCategoryController::class)
            ->parameters(['assessment-categories' => 'assessmentCategory'])
            ->except(['show']);
```

- [ ] **Step 3: Add new `:teacher` group at end of file**

```php
Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':teacher'])
    ->prefix('/{current_team}')
    ->name('academic.')
    ->group(function () {
        // Assessments
        Route::resource('academic/assessments', AssessmentController::class);
        Route::post('academic/assessments/{assessment}/scores', [AssessmentController::class, 'storeScores'])
            ->name('assessments.scores.store');

        // Report Cards
        Route::resource('academic/report-cards', ReportCardController::class)
            ->parameters(['report-cards' => 'reportCard'])
            ->only(['index', 'show', 'store', 'update']);
    });
```

- [ ] **Step 4: Run pint**

```bash
./vendor/bin/pint routes/academic.php --format agent
```

- [ ] **Step 5: Verify routes exist**

```bash
php artisan route:list --name=academic. --except-vendor
```

Expected: see `academic.assessment-categories.*`, `academic.assessments.*`, `academic.assessments.scores.store`, `academic.report-cards.*`.

- [ ] **Step 6: Commit**

```bash
git add routes/academic.php
git commit -m "feat: add routes for assessment categories, assessments, and report cards"
```

---

## Task 6: AssessmentCategoryController + Tests

**Files:**
- Create: `app/Http/Controllers/Academic/AssessmentCategoryController.php`
- Create: `tests/Feature/Academic/AssessmentCategoryControllerTest.php`

- [ ] **Step 1: Create controller**

```bash
php artisan make:controller Academic/AssessmentCategoryController --resource --no-interaction
```

- [ ] **Step 2: Write full controller**

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAssessmentCategoryRequest;
use App\Http\Requests\Academic\UpdateAssessmentCategoryRequest;
use App\Models\Academic\AssessmentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $categories = AssessmentCategory::where('team_id', $team->id)
            ->withCount('assessments')
            ->orderBy('name')
            ->get();

        return Inertia::render('academic/assessment-categories/index', [
            'categories' => $categories,
            'total_weight' => $categories->sum('weight'),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/assessment-categories/create');
    }

    public function store(StoreAssessmentCategoryRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        AssessmentCategory::create([
            'team_id' => $team->id,
            'name' => $request->name,
            'weight' => $request->weight,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil ditambahkan.']);

        return to_route('academic.assessment-categories.index');
    }

    public function edit(Request $request, string $currentTeam, AssessmentCategory $assessmentCategory): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        return Inertia::render('academic/assessment-categories/edit', [
            'category' => $assessmentCategory,
        ]);
    }

    public function update(UpdateAssessmentCategoryRequest $request, string $currentTeam, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        $assessmentCategory->update([
            'name' => $request->name,
            'weight' => $request->weight,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.']);

        return to_route('academic.assessment-categories.index');
    }

    public function destroy(Request $request, string $currentTeam, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        if ($assessmentCategory->assessments()->exists()) {
            abort(422, 'Kategori sudah digunakan oleh assessment dan tidak dapat dihapus.');
        }

        $assessmentCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dihapus.']);

        return to_route('academic.assessment-categories.index');
    }
}
```

- [ ] **Step 3: Write failing test**

```bash
php artisan make:test Academic/AssessmentCategoryControllerTest --pest --no-interaction
```

Full content of `tests/Feature/Academic/AssessmentCategoryControllerTest.php`:

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeAssessmentCategoryContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('admin can list assessment categories with total weight', function () {
    [$owner, $team] = makeAssessmentCategoryContext();

    AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 40]);
    AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 60]);

    $this->actingAs($owner)
        ->get(route('academic.assessment-categories.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/assessment-categories/index')
            ->has('categories', 2)
            ->where('total_weight', '100.00')
        );
});

it('admin can create assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();

    $this->actingAs($owner)
        ->post(route('academic.assessment-categories.store'), [
            'name' => 'UTS',
            'weight' => 30,
        ])
        ->assertRedirect();

    expect(AssessmentCategory::where('team_id', $team->id)->where('name', 'UTS')->exists())->toBeTrue();
});

it('validates assessment category store rules', function () {
    [$owner] = makeAssessmentCategoryContext();

    $this->actingAs($owner)
        ->post(route('academic.assessment-categories.store'), [])
        ->assertSessionHasErrors(['name', 'weight']);
});

it('admin can update assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);

    $this->actingAs($owner)
        ->patch(route('academic.assessment-categories.update', $category), [
            'name' => 'UAS',
            'weight' => 40,
        ])
        ->assertRedirect();

    expect($category->fresh()->name)->toBe('UAS');
});

it('admin can delete unused assessment category', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);

    $this->actingAs($owner)
        ->delete(route('academic.assessment-categories.destroy', $category))
        ->assertRedirect();

    expect(AssessmentCategory::find($category->id))->toBeNull();
});

it('cannot delete assessment category that has assessments', function () {
    [$owner, $team] = makeAssessmentCategoryContext();
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id]);
    Assessment::factory()->create(['assessment_category_id' => $category->id, 'team_id' => $team->id]);

    $this->actingAs($owner)
        ->delete(route('academic.assessment-categories.destroy', $category))
        ->assertStatus(422);

    expect(AssessmentCategory::find($category->id))->not->toBeNull();
});

it('returns 403 when accessing category of another team', function () {
    [$owner] = makeAssessmentCategoryContext();
    $other = AssessmentCategory::factory()->create();

    $this->actingAs($owner)
        ->patch(route('academic.assessment-categories.update', $other), ['name' => 'X', 'weight' => 10])
        ->assertForbidden();
});

it('teacher cannot access assessment categories', function () {
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $teacher = User::factory()->create();
    $team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);
    $owner->switchTeam($team); // restore URL defaults

    $this->actingAs($teacher)
        ->get(route('academic.assessment-categories.index'))
        ->assertForbidden();
});
```

- [ ] **Step 4: Run tests to see them fail first**

```bash
php artisan test --compact --filter=AssessmentCategoryControllerTest
```

Expected: most tests fail (controller/routes not wired, or passes after wiring — either way verify output).

- [ ] **Step 5: Run tests again to confirm pass**

```bash
php artisan test --compact --filter=AssessmentCategoryControllerTest
```

Expected: all 7 tests pass.

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Academic/AssessmentCategoryController.php tests/Feature/Academic/AssessmentCategoryControllerTest.php --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Academic/AssessmentCategoryController.php tests/Feature/Academic/AssessmentCategoryControllerTest.php
git commit -m "feat: add AssessmentCategoryController with tests"
```
