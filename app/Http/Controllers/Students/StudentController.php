<?php

namespace App\Http\Controllers\Students;

use App\Enums\AttendanceStatus;
use App\Enums\TeamRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Students\StoreStudentRequest;
use App\Http\Requests\Students\UpdateStudentRequest;
use App\Models\Academic\Guardian;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\AttendanceRecord;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use Inertia\Response;

class StudentController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $search = $request->string('search')->trim()->value();

        $studentsQuery = $team->members()
            ->wherePivot('role', TeamRole::Student->value)
            ->when($search, fn ($q) => $q->where(function ($q) use ($search) {
                $q->where('users.name', 'like', "%{$search}%")
                    ->orWhere('users.email', 'like', "%{$search}%");
            }))
            ->with([
                'enrollments' => fn ($q) => $q
                    ->whereHas('classroom', fn ($q) => $q->where('team_id', $team->id))
                    ->with('classroom:id,name'),
            ]);

        $paginated = $studentsQuery->paginate(15)->through(fn (User $user) => [
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
            'students' => $paginated,
            'classrooms' => $classrooms,
            'filters' => [
                'search' => $search,
            ],
        ]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/create', [
            'classrooms' => $classrooms,
        ]);
    }

    public function show(Request $request, string $currentTeam, User $user): Response
    {
        $team = $request->user()->currentTeam;

        $studentMembership = $team->members()
            ->where('users.id', $user->id)
            ->wherePivot('role', TeamRole::Student->value)
            ->first();

        abort_unless($studentMembership !== null, 404);

        $classroomIds = $team->classrooms()->pluck('id');

        $enrollment = StudentEnrollment::whereIn('classroom_id', $classroomIds)
            ->where('user_id', $user->id)
            ->with([
                'classroom:id,name,grade_id,academic_year_id',
                'classroom.grade:id,name',
                'classroom.academicYear:id,name',
            ])
            ->first();

        $rawSummary = AttendanceRecord::query()
            ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
            ->whereIn('attendances.classroom_id', $classroomIds)
            ->where('attendance_records.student_user_id', $user->id)
            ->selectRaw('attendance_records.status, count(*) as count')
            ->groupBy('attendance_records.status')
            ->toBase()
            ->pluck('count', 'status');

        $attendanceSummary = collect(AttendanceStatus::cases())->map(fn (AttendanceStatus $s) => [
            'status' => $s->value,
            'count' => (int) ($rawSummary[$s->value] ?? 0),
        ]);

        $attendanceRecords = AttendanceRecord::query()
            ->join('attendances', 'attendance_records.attendance_id', '=', 'attendances.id')
            ->leftJoin('subjects', 'attendances.subject_id', '=', 'subjects.id')
            ->whereIn('attendances.classroom_id', $classroomIds)
            ->where('attendance_records.student_user_id', $user->id)
            ->orderByDesc('attendances.date')
            ->select(
                'attendance_records.*',
                'attendances.date as attendance_date',
                'subjects.name as subject_name',
            )
            ->paginate(15)
            ->through(fn (AttendanceRecord $record) => [
                'date' => $record->attendance_date,
                'subject_name' => $record->subject_name,
                'status' => $record->status->value,
                'notes' => $record->notes,
            ]);

        $guardians = Guardian::where('student_id', $user->id)
            ->with('guardian:id,name,email')
            ->get()
            ->map(fn (Guardian $g) => [
                'name' => $g->guardian->name,
                'email' => $g->guardian->email,
                'relationship_label' => $g->relationship->label(),
            ]);

        return Inertia::render('students/show', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'joined_at' => $studentMembership->pivot->created_at,
            ],
            'enrollment' => $enrollment ? [
                'classroom_name' => $enrollment->classroom->name,
                'student_number' => $enrollment->student_number,
                'grade_name' => $enrollment->classroom->grade->name,
                'academic_year_name' => $enrollment->classroom->academicYear->name,
            ] : null,
            'attendance_summary' => $attendanceSummary,
            'attendance_records' => $attendanceRecords,
            'guardians' => $guardians,
        ]);
    }

    public function store(StoreStudentRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $validated = $request->validated();

        $student = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'email_verified_at' => now(),
        ]);

        $team->members()->attach($student->id, ['role' => TeamRole::Student->value]);

        if (! empty($validated['classroom_id'])) {
            StudentEnrollment::create([
                'classroom_id' => $validated['classroom_id'],
                'user_id' => $student->id,
                'student_number' => $validated['student_number'] ?? null,
            ]);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil ditambahkan.']);

        return to_route('students.index');
    }

    public function edit(Request $request, string $currentTeam, User $user): Response
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        $enrollment = StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->first();

        $classrooms = $team->classrooms()->select(['id', 'name'])->orderBy('name')->get();

        return Inertia::render('students/edit', [
            'student' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'enrollment' => $enrollment ? [
                'classroom_id' => $enrollment->classroom_id,
                'student_number' => $enrollment->student_number,
            ] : null,
            'classrooms' => $classrooms,
        ]);
    }

    public function update(UpdateStudentRequest $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        $validated = $request->validated();

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
        ]);

        $existingEnrollment = StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->first();

        if (! empty($validated['classroom_id'])) {
            if ($existingEnrollment) {
                $existingEnrollment->update([
                    'classroom_id' => $validated['classroom_id'],
                    'student_number' => $validated['student_number'] ?? null,
                ]);
            } else {
                StudentEnrollment::create([
                    'classroom_id' => $validated['classroom_id'],
                    'user_id' => $user->id,
                    'student_number' => $validated['student_number'] ?? null,
                ]);
            }
        } else {
            $existingEnrollment?->delete();
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Data siswa berhasil diperbarui.']);

        return to_route('students.index');
    }

    public function destroy(Request $request, string $currentTeam, User $user): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        abort_unless(
            $team->members()->where('users.id', $user->id)->wherePivot('role', TeamRole::Student->value)->exists(),
            404,
        );

        StudentEnrollment::whereIn(
            'classroom_id',
            $team->classrooms()->pluck('id'),
        )->where('user_id', $user->id)->delete();

        $team->members()->detach($user->id);

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Siswa berhasil dihapus dari tim.']);

        return to_route('students.index');
    }
}
