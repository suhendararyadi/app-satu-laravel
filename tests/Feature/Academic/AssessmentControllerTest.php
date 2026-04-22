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
