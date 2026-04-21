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
            'assignments' => $team->teacherAssignments()->with(['academicYear', 'subject', 'classroom.grade', 'user'])->get(),
            'academicYears' => $team->academicYears()->orderByDesc('start_year')->get(),
            'subjects' => $team->subjects()->orderBy('name')->get(),
            'classrooms' => $team->classrooms()->with(['grade'])->get(),
            'teachers' => $team->members()->get(),
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
