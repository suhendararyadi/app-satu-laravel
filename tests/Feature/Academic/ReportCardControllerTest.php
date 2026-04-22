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
            ->where('subject_grades.0.final_grade', 80)
            ->where('overall_average', 80)
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
        ->assertInertia(fn ($page) => $page->where('subject_grades.0.final_grade', 0));
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
