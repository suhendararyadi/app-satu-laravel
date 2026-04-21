<?php

namespace App\Http\Controllers\Students;

use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Models\Academic\StudentEnrollment;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        $students = $team->members()
            ->wherePivot('role', TeamRole::Student->value)
            ->with([
                'enrollments' => fn ($q) => $q
                    ->whereHas('classroom', fn ($q) => $q->where('team_id', $team->id))
                    ->with('classroom:id,name'),
            ])
            ->get()
            ->map(fn (User $user) => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'joined_at' => $user->pivot->created_at,
                'classrooms' => $user->enrollments->map(fn (StudentEnrollment $e) => [
                    'id' => $e->classroom->id,
                    'name' => $e->classroom->name,
                    'student_number' => $e->student_number,
                ]),
            ]);

        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/index', [
            'students' => $students,
            'classrooms' => $classrooms,
        ]);
    }

    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        // Remove all enrollments in this team's classrooms
        StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->delete();

        $team->members()->detach($user->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil dihapus dari tim.']);

        return to_route('students.index');
    }
}
