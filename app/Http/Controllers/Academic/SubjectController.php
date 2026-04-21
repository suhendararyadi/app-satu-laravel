<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreSubjectRequest;
use App\Http\Requests\Academic\UpdateSubjectRequest;
use App\Models\Academic\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class SubjectController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $subjects = $team->subjects()->orderBy('name')->get();

        return Inertia::render('academic/subjects/index', ['subjects' => $subjects]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/subjects/create');
    }

    public function store(StoreSubjectRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $subject = $team->subjects()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil dibuat.']);

        return to_route('academic.subjects.edit', $subject);
    }

    public function edit(Request $request, string $currentTeam, Subject $subject): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);

        return Inertia::render('academic/subjects/edit', ['subject' => $subject]);
    }

    public function update(UpdateSubjectRequest $request, string $currentTeam, Subject $subject): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);
        $subject->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil diperbarui.']);

        return to_route('academic.subjects.edit', $subject);
    }

    public function destroy(Request $request, string $currentTeam, Subject $subject): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($subject->team_id !== $team->id, 403);
        $subject->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Mata pelajaran berhasil dihapus.']);

        return to_route('academic.subjects.index');
    }
}
