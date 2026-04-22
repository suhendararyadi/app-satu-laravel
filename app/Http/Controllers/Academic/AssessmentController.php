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
