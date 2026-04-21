<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreAttendanceRequest;
use App\Http\Requests\Schedule\UpdateAttendanceRequest;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Schedule\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $attendances = Attendance::query()
            ->where('team_id', $team->id)
            ->with(['classroom', 'subject', 'semester'])
            ->orderByDesc('date')
            ->paginate(20);

        return Inertia::render('attendance/index', ['attendances' => $attendances]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('attendance/create', [
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        $attendance = Attendance::create([
            'team_id' => $team->id,
            'classroom_id' => $request->classroom_id,
            'date' => $request->date,
            'subject_id' => $request->subject_id,
            'semester_id' => $request->semester_id,
            'recorded_by' => $request->user()->id,
        ]);

        foreach ($request->records as $record) {
            $attendance->records()->create($record);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil disimpan.']);

        return to_route('schedule.attendance.show', $attendance);
    }

    public function show(Request $request, string $currentTeam, Attendance $attendance): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        $attendance->load(['classroom', 'subject', 'semester', 'records.student']);

        return Inertia::render('attendance/show', ['attendance' => $attendance]);
    }

    public function edit(Request $request, string $currentTeam, Attendance $attendance): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        $attendance->load(['records']);

        $students = StudentEnrollment::where('classroom_id', $attendance->classroom_id)
            ->with('student:id,name')
            ->get()
            ->map(fn ($e) => ['id' => $e->user_id, 'name' => $e->student->name ?? '']);

        return Inertia::render('attendance/edit', [
            'attendance' => $attendance,
            'students' => $students,
        ]);
    }

    public function update(UpdateAttendanceRequest $request, string $currentTeam, Attendance $attendance): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        $attendance->records()->delete();
        foreach ($request->records as $record) {
            $attendance->records()->create($record);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil diperbarui.']);

        return to_route('schedule.attendance.show', $attendance);
    }
}
