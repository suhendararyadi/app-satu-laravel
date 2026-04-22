<?php

namespace App\Http\Controllers\Academic;

use App\Http\Controllers\Controller;
use App\Http\Requests\Academic\StoreAssessmentCategoryRequest;
use App\Http\Requests\Academic\UpdateAssessmentCategoryRequest;
use App\Models\Academic\AssessmentCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AssessmentCategoryController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $categories = AssessmentCategory::where('team_id', $team->id)
            ->withCount('assessments')
            ->orderBy('name')
            ->get();

        return Inertia::render('academic/assessment-categories/index', [
            'categories' => $categories,
            'total_weight' => number_format($categories->sum('weight'), 2, '.', ''),
        ]);
    }

    public function create(): Response
    {
        return Inertia::render('academic/assessment-categories/create');
    }

    public function store(StoreAssessmentCategoryRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        AssessmentCategory::create([
            'team_id' => $team->id,
            'name' => $request->name,
            'weight' => $request->weight,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil ditambahkan.']);

        return to_route('academic.assessment-categories.index');
    }

    public function edit(Request $request, string $currentTeam, AssessmentCategory $assessmentCategory): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        return Inertia::render('academic/assessment-categories/edit', [
            'category' => $assessmentCategory,
        ]);
    }

    public function update(UpdateAssessmentCategoryRequest $request, string $currentTeam, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        $assessmentCategory->update([
            'name' => $request->name,
            'weight' => $request->weight,
        ]);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil diperbarui.']);

        return to_route('academic.assessment-categories.index');
    }

    public function destroy(Request $request, string $currentTeam, AssessmentCategory $assessmentCategory): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($assessmentCategory->team_id !== $team->id, 403);

        if ($assessmentCategory->assessments()->exists()) {
            abort(422, 'Kategori sudah digunakan oleh assessment dan tidak dapat dihapus.');
        }

        $assessmentCategory->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Kategori berhasil dihapus.']);

        return to_route('academic.assessment-categories.index');
    }
}
