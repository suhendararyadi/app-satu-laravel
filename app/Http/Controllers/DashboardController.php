<?php

namespace App\Http\Controllers;

use App\Enums\TeamRole;
use App\Services\Dashboard\AdminDashboardData;
use App\Services\Dashboard\ParentDashboardData;
use App\Services\Dashboard\StudentDashboardData;
use App\Services\Dashboard\TeacherDashboardData;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $team = $user->currentTeam;

        // Personal team or no team: preserve original hasSchoolTeam behaviour
        if (! $team || $team->is_personal) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => $user->teams()->where('is_personal', false)->exists(),
            ]);
        }

        $role = $user->teamRole($team);

        if (! $role) {
            return Inertia::render('dashboard', ['hasSchoolTeam' => true]);
        }

        if ($role->level() >= TeamRole::Admin->level()) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new AdminDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Teacher) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new TeacherDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Student) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new StudentDashboardData)->get($user, $team),
            ]);
        }

        if ($role === TeamRole::Parent) {
            return Inertia::render('dashboard', [
                'hasSchoolTeam' => true,
                'role' => $role->value,
                'data' => (new ParentDashboardData)->get($user, $team),
            ]);
        }

        return Inertia::render('dashboard', ['hasSchoolTeam' => true]);
    }
}
