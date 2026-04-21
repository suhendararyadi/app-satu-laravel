<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $schedules = Schedule::query()
            ->where('team_id', $team->id)
            ->with(['semester', 'classroom', 'subject', 'teacher', 'timeSlot'])
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get();

        return Inertia::render('schedule/schedules/index', ['schedules' => $schedules]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('schedule/schedules/create', [
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'teachers' => User::whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))->get(['id', 'name']),
            'timeSlots' => TimeSlot::where('team_id', $team->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $schedule = Schedule::create(array_merge($request->validated(), ['team_id' => $team->id]));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil dibuat.']);

        return to_route('schedule.schedules.edit', $schedule);
    }

    public function edit(Request $request, string $currentTeam, Schedule $schedule): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);

        return Inertia::render('schedule/schedules/edit', [
            'schedule' => $schedule,
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'teachers' => User::whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))->get(['id', 'name']),
            'timeSlots' => TimeSlot::where('team_id', $team->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, string $currentTeam, Schedule $schedule): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);
        $schedule->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil diperbarui.']);

        return to_route('schedule.schedules.edit', $schedule);
    }

    public function destroy(Request $request, string $currentTeam, Schedule $schedule): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);
        $schedule->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil dihapus.']);

        return to_route('schedule.schedules.index');
    }
}
