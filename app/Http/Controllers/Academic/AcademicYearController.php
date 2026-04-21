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
