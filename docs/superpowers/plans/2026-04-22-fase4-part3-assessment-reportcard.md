# Fase 4: Penilaian & Rapor — Part 3: AssessmentController + ReportCardController

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Prerequisites:** Part 1 + Part 2 complete.

---

## Task 7: AssessmentController + Tests

**Files:**
- Create: `app/Http/Controllers/Academic/AssessmentController.php`
- Create: `tests/Feature/Academic/AssessmentControllerTest.php`

- [ ] **Step 1: Create controller**

```bash
php artisan make:controller Academic/AssessmentController --resource --no-interaction
```

- [ ] **Step 2: Write full controller**

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAssessmentRequest;
use App\Http\Requests\Academic\StoreScoresRequest;
use App\Http\Requests\Academic\UpdateAssessmentRequest;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $assessments = Assessment::where('team_id', $team->id)
            ->with(['classroom', 'subject', 'semester', 'category'])
            ->withCount(['scores as scores_filled' => fn ($q) => $q->whereNotNull('score')])
            ->withCount('scores as scores_total')
            ->when($request->classroom_id, fn ($q) => $q->where('classroom_id', $request->classroom_id))
            ->when($request->semester_id, fn ($q) => $q->where('semester_id', $request->semester_id))
            ->orderByDesc('date')
            ->paginate(20);

        return Inertia::render('academic/assessments/index', [
            'assessments' => $assessments,
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'filters' => $request->only(['classroom_id', 'semester_id']),
        ]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('academic/assessments/create', [
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'categories' => AssessmentCategory::where('team_id', $team->id)->get(),
        ]);
    }

    public function store(StoreAssessmentRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        $assessment = Assessment::create([
            'team_id' => $team->id,
            'classroom_id' => $request->classroom_id,
            'subject_id' => $request->subject_id,
            'semester_id' => $request->semester_id,
            'assessment_category_id' => $request->assessment_category_id,
            'title' => $request->title,
            'max_score' => $request->max_score,
            'date' => $request->date,
            'teacher_user_id' => $request->user()->id,
        ]);

        // Pre-populate null scores for all enrolled students
        StudentEnrollment::where('classroom_id', $request->classroom_id)
            ->each(function ($enrollment) use ($assessment) {
                Score::create([
                    'assessment_id' => $assessment->id,
                    'student_user_id' => $enrollment->user_id,
                    'score' => null,
                ]);
            });

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assessment berhasil dibuat.']);

        return to_route('academic.assessments.show', $assessment);
    }

    public function show(Request $request, string $currentTeam, Assessment $assessment): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($assessment->team_id !== $team->id, 403);

        $assessment->load(['classroom', 'subject', 'semester', 'category']);

        $scores = $assessment->scores()
            ->with('student:id,name')
            ->orderBy('student_user_id')
            ->get()
            ->map(fn ($s) => [
                'student_user_id' => $s->student_user_id,
                'name' => $s->student->name ?? '',
                'score' => $s->score,
                'notes' => $s->notes,
            ]);

        return Inertia::render('academic/assessments/show', [
            'assessment' => $assessment,
            'scores' => $scores,
        ]);
    }

    public function edit(Request $request, string $currentTeam, Assessment $assessment): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($assessment->team_id !== $team->id, 403);

        return Inertia::render('academic/assessments/edit', [
            'assessment' => $assessment,
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'categories' => AssessmentCategory::where('team_id', $team->id)->get(),
        ]);
    }

    public function update(UpdateAssessmentRequest $request, string $currentTeam, Assessment $assessment): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessment->team_id !== $team->id, 403);

        $assessment->update([
            'classroom_id' => $request->classroom_id,
            'subject_id' => $request->subject_id,
            'semester_id' => $request->semester_id,
            'assessment_category_id' => $request->assessment_category_id,
            'title' => $request->title,
            'max_score' => $request->max_score,
            'date' => $request->date,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assessment berhasil diperbarui.']);

        return to_route('academic.assessments.show', $assessment);
    }

    public function destroy(Request $request, string $currentTeam, Assessment $assessment): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessment->team_id !== $team->id, 403);

        $assessment->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Assessment berhasil dihapus.']);

        return to_route('academic.assessments.index');
    }

    public function storeScores(StoreScoresRequest $request, string $currentTeam, Assessment $assessment): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessment->team_id !== $team->id, 403);

        foreach ($request->scores as $row) {
            Score::updateOrCreate(
                ['assessment_id' => $assessment->id, 'student_user_id' => $row['student_user_id']],
                ['score' => $row['score'], 'notes' => $row['notes'] ?? null]
            );
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Nilai berhasil disimpan.']);

        return to_route('academic.assessments.show', $assessment);
    }
}
```

- [ ] **Step 3: Write test file**

```bash
php artisan make:test Academic/AssessmentControllerTest --pest --no-interaction
```

Full content of `tests/Feature/Academic/AssessmentControllerTest.php`:

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeAssessmentContext(): array
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
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 100]);

    $student = User::factory()->create();
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    $owner->switchTeam($team); // restore URL defaults

    return [$owner, $team, $semester, $classroom, $subject, $category, $student];
}

it('teacher can list assessments', function () {
    [$owner, $team] = makeAssessmentContext();

    $this->actingAs($owner)
        ->get(route('academic.assessments.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('academic/assessments/index'));
});

it('teacher can create assessment', function () {
    [$owner, $team, $semester, $classroom, $subject, $category] = makeAssessmentContext();

    $this->actingAs($owner)
        ->post(route('academic.assessments.store'), [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'assessment_category_id' => $category->id,
            'title' => 'UTS Matematika',
            'max_score' => 100,
            'date' => '2026-04-22',
        ])
        ->assertRedirect();

    expect(Assessment::where('team_id', $team->id)->where('title', 'UTS Matematika')->exists())->toBeTrue();
});

it('store pre-populates null scores for enrolled students', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();

    $this->actingAs($owner)
        ->post(route('academic.assessments.store'), [
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'semester_id' => $semester->id,
            'assessment_category_id' => $category->id,
            'title' => 'Tugas 1',
            'max_score' => 100,
            'date' => '2026-04-22',
        ]);

    $assessment = Assessment::where('title', 'Tugas 1')->first();
    expect(Score::where('assessment_id', $assessment->id)->where('student_user_id', $student->id)->whereNull('score')->exists())->toBeTrue();
});

it('show pre-fills existing scores', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
    ]);
    Score::factory()->create(['assessment_id' => $assessment->id, 'student_user_id' => $student->id, 'score' => 85]);

    $this->actingAs($owner)
        ->get(route('academic.assessments.show', $assessment))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/assessments/show')
            ->has('scores', 1)
            ->where('scores.0.score', '85.00')
        );
});

it('storeScores inserts new scores', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
        'max_score' => 100,
    ]);

    $this->actingAs($owner)
        ->post(route('academic.assessments.scores.store', $assessment), [
            'scores' => [['student_user_id' => $student->id, 'score' => 90, 'notes' => null]],
        ])
        ->assertRedirect();

    expect(Score::where('assessment_id', $assessment->id)->where('student_user_id', $student->id)->value('score'))->toBe('90.00');
});

it('storeScores updates existing scores', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
        'max_score' => 100,
    ]);
    Score::factory()->create(['assessment_id' => $assessment->id, 'student_user_id' => $student->id, 'score' => 70]);

    $this->actingAs($owner)
        ->post(route('academic.assessments.scores.store', $assessment), [
            'scores' => [['student_user_id' => $student->id, 'score' => 95, 'notes' => 'revised']],
        ]);

    expect(Score::where('assessment_id', $assessment->id)->where('student_user_id', $student->id)->value('score'))->toBe('95.00');
});

it('rejects score exceeding max_score', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
        'max_score' => 50,
    ]);

    $this->actingAs($owner)
        ->post(route('academic.assessments.scores.store', $assessment), [
            'scores' => [['student_user_id' => $student->id, 'score' => 75, 'notes' => null]],
        ])
        ->assertSessionHasErrors(['scores.0.score']);
});

it('destroy deletes assessment and cascades scores', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeAssessmentContext();
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
    ]);
    Score::factory()->create(['assessment_id' => $assessment->id, 'student_user_id' => $student->id]);

    $this->actingAs($owner)
        ->delete(route('academic.assessments.destroy', $assessment))
        ->assertRedirect();

    expect(Assessment::find($assessment->id))->toBeNull();
    expect(Score::where('assessment_id', $assessment->id)->count())->toBe(0);
});
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=AssessmentControllerTest
```

Expected: all 8 tests pass.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Academic/AssessmentController.php tests/Feature/Academic/AssessmentControllerTest.php --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Academic/AssessmentController.php tests/Feature/Academic/AssessmentControllerTest.php
git commit -m "feat: add AssessmentController with storeScores and tests"
```

---

## Task 8: ReportCardController + Tests

**Files:**
- Create: `app/Http/Controllers/Academic/ReportCardController.php`
- Create: `tests/Feature/Academic/ReportCardControllerTest.php`

- [ ] **Step 1: Create controller**

```bash
php artisan make:controller Academic/ReportCardController --no-interaction
```

- [ ] **Step 2: Write full controller**

```php
<?php

namespace App\Http\Controllers\Academic;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreReportCardRequest;
use App\Http\Requests\Academic\UpdateReportCardRequest;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\ReportCard;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\AttendanceRecord;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReportCardController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $classroomId = $request->input('classroom_id');
        $semesterId = $request->input('semester_id');

        $students = collect();

        if ($classroomId && $semesterId) {
            $enrollments = StudentEnrollment::where('classroom_id', $classroomId)
                ->with('student:id,name')
                ->get();

            $reportCards = ReportCard::where('semester_id', $semesterId)
                ->whereIn('student_user_id', $enrollments->pluck('user_id'))
                ->get()
                ->keyBy('student_user_id');

            $students = $enrollments->map(fn ($e) => [
                'user_id' => $e->user_id,
                'name' => $e->student->name ?? '',
                'report_card_id' => $reportCards->get($e->user_id)?->id,
                'has_report_card' => $reportCards->has($e->user_id),
            ]);
        }

        return Inertia::render('academic/report-cards/index', [
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'students' => $students,
            'filters' => ['classroom_id' => $classroomId, 'semester_id' => $semesterId],
        ]);
    }

    public function show(Request $request, string $currentTeam, ReportCard $reportCard): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($reportCard->team_id !== $team->id, 403);

        $reportCard->load(['student', 'classroom', 'semester', 'generatedBy']);

        $categories = AssessmentCategory::where('team_id', $team->id)->get();

        $assessments = Assessment::where('classroom_id', $reportCard->classroom_id)
            ->where('semester_id', $reportCard->semester_id)
            ->with(['subject', 'category', 'scores' => fn ($q) => $q->where('student_user_id', $reportCard->student_user_id)])
            ->get();

        $subjectGrades = $assessments->groupBy('subject_id')->map(function ($subjectAssessments) use ($categories) {
            $subject = $subjectAssessments->first()->subject;
            $finalGrade = 0.0;
            $categoryScores = [];

            foreach ($categories as $category) {
                $inCategory = $subjectAssessments->where('assessment_category_id', $category->id);
                $scores = $inCategory->flatMap(fn ($a) => $a->scores)->pluck('score')->filter(fn ($s) => $s !== null);
                $avg = $scores->isNotEmpty() ? (float) $scores->avg() : 0.0;
                $categoryScores[$category->id] = round($avg, 2);
                $finalGrade += $avg * ((float) $category->weight / 100);
            }

            return [
                'subject_id' => $subject->id,
                'subject_name' => $subject->name,
                'category_scores' => $categoryScores,
                'final_grade' => round($finalGrade, 2),
            ];
        })->values();

        $overallAverage = $subjectGrades->isNotEmpty() ? round($subjectGrades->avg('final_grade'), 2) : 0.0;

        $attendanceSummary = AttendanceRecord::whereHas('attendance', fn ($q) => $q
            ->where('classroom_id', $reportCard->classroom_id)
            ->where('semester_id', $reportCard->semester_id)
        )
            ->where('student_user_id', $reportCard->student_user_id)
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->get();

        return Inertia::render('academic/report-cards/show', [
            'report_card' => $reportCard,
            'categories' => $categories,
            'subject_grades' => $subjectGrades,
            'overall_average' => $overallAverage,
            'attendance_summary' => $attendanceSummary,
        ]);
    }

    public function store(StoreReportCardRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_unless($request->user()->teamRole($team)?->isAtLeast(TeamRole::Admin), 403);

        $reportCard = ReportCard::updateOrCreate(
            ['semester_id' => $request->semester_id, 'student_user_id' => $request->student_user_id],
            [
                'team_id' => $team->id,
                'classroom_id' => $request->classroom_id,
                'generated_by' => $request->user()->id,
                'homeroom_notes' => $request->homeroom_notes,
                'generated_at' => now(),
            ]
        );

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Rapor berhasil digenerate.']);

        return to_route('academic.report-cards.show', $reportCard);
    }

    public function update(UpdateReportCardRequest $request, string $currentTeam, ReportCard $reportCard): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($reportCard->team_id !== $team->id, 403);
        abort_unless($request->user()->teamRole($team)?->isAtLeast(TeamRole::Admin), 403);

        $reportCard->update(['homeroom_notes' => $request->homeroom_notes]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Catatan wali kelas berhasil diperbarui.']);

        return to_route('academic.report-cards.show', $reportCard);
    }
}
```

- [ ] **Step 3: Write test file**

```bash
php artisan make:test Academic/ReportCardControllerTest --pest --no-interaction
```

Full content of `tests/Feature/Academic/ReportCardControllerTest.php`:

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\AssessmentCategory;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\ReportCard;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeReportCardContext(): array
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
    $category = AssessmentCategory::factory()->create(['team_id' => $team->id, 'weight' => 100]);

    $student = User::factory()->create();
    StudentEnrollment::factory()->create(['classroom_id' => $classroom->id, 'user_id' => $student->id]);

    $owner->switchTeam($team); // restore URL defaults

    return [$owner, $team, $semester, $classroom, $subject, $category, $student];
}

it('index shows students with report card status', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    $this->actingAs($owner)
        ->get(route('academic.report-cards.index', ['classroom_id' => $classroom->id, 'semester_id' => $semester->id]))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/report-cards/index')
            ->has('students', 1)
            ->where('students.0.has_report_card', false)
        );
});

it('admin can generate report card', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    $this->actingAs($owner)
        ->post(route('academic.report-cards.store'), [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
            'homeroom_notes' => null,
        ])
        ->assertRedirect();

    expect(ReportCard::where('semester_id', $semester->id)->where('student_user_id', $student->id)->exists())->toBeTrue();
});

it('teacher cannot generate report card', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    $teacher = User::factory()->create();
    $team->members()->attach($teacher, ['role' => TeamRole::Teacher->value]);
    $owner->switchTeam($team); // restore URL defaults

    $this->actingAs($teacher)
        ->post(route('academic.report-cards.store'), [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'student_user_id' => $student->id,
        ])
        ->assertForbidden();
});

it('show computes weighted grades correctly', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    // category weight = 100, so final_grade = avg_score * 1
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
        'max_score' => 100,
    ]);
    Score::factory()->create(['assessment_id' => $assessment->id, 'student_user_id' => $student->id, 'score' => 80]);

    $reportCard = ReportCard::factory()->create([
        'team_id' => $team->id, 'semester_id' => $semester->id,
        'classroom_id' => $classroom->id, 'student_user_id' => $student->id,
        'generated_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('academic.report-cards.show', $reportCard))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('academic/report-cards/show')
            ->has('subject_grades', 1)
            ->where('subject_grades.0.final_grade', 80.0)
            ->where('overall_average', 80.0)
        );
});

it('show returns zero for subject with no scores', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    // Assessment exists but score is null
    $assessment = Assessment::factory()->create([
        'team_id' => $team->id, 'classroom_id' => $classroom->id,
        'subject_id' => $subject->id, 'semester_id' => $semester->id,
        'assessment_category_id' => $category->id, 'teacher_user_id' => $owner->id,
        'max_score' => 100,
    ]);
    Score::factory()->create(['assessment_id' => $assessment->id, 'student_user_id' => $student->id, 'score' => null]);

    $reportCard = ReportCard::factory()->create([
        'team_id' => $team->id, 'semester_id' => $semester->id,
        'classroom_id' => $classroom->id, 'student_user_id' => $student->id,
        'generated_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->get(route('academic.report-cards.show', $reportCard))
        ->assertInertia(fn ($page) => $page->where('subject_grades.0.final_grade', 0.0));
});

it('regenerating report card updates instead of creating duplicate', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();

    $this->actingAs($owner)
        ->post(route('academic.report-cards.store'), [
            'semester_id' => $semester->id, 'classroom_id' => $classroom->id,
            'student_user_id' => $student->id, 'homeroom_notes' => null,
        ]);

    $this->actingAs($owner)
        ->post(route('academic.report-cards.store'), [
            'semester_id' => $semester->id, 'classroom_id' => $classroom->id,
            'student_user_id' => $student->id, 'homeroom_notes' => 'Bagus',
        ]);

    expect(ReportCard::where('semester_id', $semester->id)->where('student_user_id', $student->id)->count())->toBe(1);
    expect(ReportCard::where('semester_id', $semester->id)->where('student_user_id', $student->id)->value('homeroom_notes'))->toBe('Bagus');
});

it('admin can update homeroom notes', function () {
    [$owner, $team, $semester, $classroom, $subject, $category, $student] = makeReportCardContext();
    $reportCard = ReportCard::factory()->create([
        'team_id' => $team->id, 'semester_id' => $semester->id,
        'classroom_id' => $classroom->id, 'student_user_id' => $student->id,
        'generated_by' => $owner->id,
    ]);

    $this->actingAs($owner)
        ->patch(route('academic.report-cards.update', $reportCard), ['homeroom_notes' => 'Perlu bimbingan'])
        ->assertRedirect();

    expect($reportCard->fresh()->homeroom_notes)->toBe('Perlu bimbingan');
});

it('returns 403 for report card of another team', function () {
    [$owner] = makeReportCardContext();
    $other = ReportCard::factory()->create();

    $this->actingAs($owner)
        ->get(route('academic.report-cards.show', $other))
        ->assertForbidden();
});
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ReportCardControllerTest
```

Expected: all 8 tests pass.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint app/Http/Controllers/Academic/ReportCardController.php tests/Feature/Academic/ReportCardControllerTest.php --format agent
```

- [ ] **Step 6: Run all tests to confirm nothing broken**

```bash
php artisan test --compact
```

Expected: all tests pass (prior count + 23 new tests).

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Academic/ReportCardController.php tests/Feature/Academic/ReportCardControllerTest.php
git commit -m "feat: add ReportCardController with weighted grade calculation and tests"
```

---

## Task 9: Regenerate Wayfinder

- [ ] **Step 1: Build to regenerate Wayfinder action files**

```bash
npm run build
```

Expected: build succeeds, new files appear in `resources/js/actions/App/Http/Controllers/Academic/`:
- `AssessmentCategoryController.ts`
- `AssessmentController.ts`
- `ReportCardController.ts`

- [ ] **Step 2: Commit**

```bash
git add resources/js/actions/ resources/js/routes/ resources/js/wayfinder/
git commit -m "chore: regenerate Wayfinder action files for assessment and report card controllers"
```
