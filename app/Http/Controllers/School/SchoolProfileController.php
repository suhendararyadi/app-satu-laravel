<?php

namespace App\Http\Controllers\School;

use App\Http\Controllers\Controller;
use App\Http\Requests\School\UpdateSchoolProfileRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class SchoolProfileController extends Controller
{
    public function edit(Request $request): Response
    {
        return Inertia::render('school/profile', [
            'team' => $request->user()->currentTeam,
        ]);
    }

    public function update(UpdateSchoolProfileRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $data = collect($request->validated())->except('logo')->toArray();

        if ($request->hasFile('logo')) {
            if ($team->logo_path) {
                Storage::disk('public')->delete($team->logo_path);
            }
            $data['logo_path'] = $request->file('logo')->store('logos', 'public');
        }

        $team->update($data);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('School profile updated.')]);

        return to_route('cms.school.profile.edit');
    }
}
