<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        // $user always has at least a personal team (is_personal=true) created on registration,
        // so EnsureTeamMembership middleware is always satisfied for authenticated users.
        return Inertia::render('dashboard', [
            'hasSchoolTeam' => $user->teams()->where('is_personal', false)->exists(),
        ]);
    }
}
