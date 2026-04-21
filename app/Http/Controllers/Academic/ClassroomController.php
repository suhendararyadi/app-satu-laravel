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
            'grades' => $team->grades()->orderBy('order')->get(),
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
            'students' => $team->members()->get(),
        ]);
    }

    public function edit(Request $request, string $currentTeam, Classroom $classroom): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($classroom->team_id !== $team->id, 403);

        return Inertia::render('academic/classrooms/edit', [
            'classroom' => $classroom,
            'academicYears' => $team->academicYears()->orderByDesc('start_year')->get(),
            'grades' => $team->grades()->orderBy('order')->get(),
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
