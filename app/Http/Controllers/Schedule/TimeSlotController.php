<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreTimeSlotRequest;
use App\Http\Requests\Schedule\UpdateTimeSlotRequest;
use App\Models\Schedule\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimeSlotController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $timeSlots = $team->timeSlots()->orderBy('sort_order')->get();

        return Inertia::render('schedule/time-slots/index', ['timeSlots' => $timeSlots]);
    }

    public function create(): Response
    {
        return Inertia::render('schedule/time-slots/create');
    }

    public function store(StoreTimeSlotRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $timeSlot = $team->timeSlots()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil dibuat.']);

        return to_route('schedule.time-slots.edit', $timeSlot);
    }

    public function edit(Request $request, string $currentTeam, TimeSlot $timeSlot): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);

        return Inertia::render('schedule/time-slots/edit', ['timeSlot' => $timeSlot]);
    }

    public function update(UpdateTimeSlotRequest $request, string $currentTeam, TimeSlot $timeSlot): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);
        $timeSlot->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil diperbarui.']);

        return to_route('schedule.time-slots.edit', $timeSlot);
    }

    public function destroy(Request $request, string $currentTeam, TimeSlot $timeSlot): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);
        $timeSlot->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil dihapus.']);

        return to_route('schedule.time-slots.index');
    }
}
