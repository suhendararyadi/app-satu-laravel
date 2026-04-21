<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreGradeRequest;
use App\Http\Requests\Academic\UpdateGradeRequest;
use App\Models\Academic\Grade;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class GradeController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $grades = $team->grades()->orderBy('order')->get();

        return Inertia::render('academic/grades/index', ['grades' => $grades]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/grades/create');
    }

    public function store(StoreGradeRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $grade = $team->grades()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil dibuat.']);

        return to_route('academic.grades.edit', $grade);
    }

    public function edit(Request $request, string $currentTeam, Grade $grade): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);

        return Inertia::render('academic/grades/edit', ['grade' => $grade]);
    }

    public function update(UpdateGradeRequest $request, string $currentTeam, Grade $grade): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);
        $grade->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil diperbarui.']);

        return to_route('academic.grades.edit', $grade);
    }

    public function destroy(Request $request, string $currentTeam, Grade $grade): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($grade->team_id !== $team->id, 403);
        $grade->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Tingkat berhasil dihapus.']);

        return to_route('academic.grades.index');
    }
}
