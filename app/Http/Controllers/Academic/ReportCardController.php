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
