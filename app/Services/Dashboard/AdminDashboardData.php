<?php

namespace App\Services\Dashboard;

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class AdminDashboardData
{
    /**
     * Get admin dashboard data for the given team.
     *
     * Note: $user is part of the uniform dashboard service interface.
     * Admin data is team-scoped, so $user is not used here, but Teacher/Student/Parent
     * services rely on it.
     */
    public function get(User $user, Team $team): array
    {
        $activeYear = AcademicYear::where('team_id', $team->id)
            ->where('is_active', true)
            ->first();

        $activeSemester = $activeYear
            ? Semester::where('academic_year_id', $activeYear->id)
                ->where('is_active', true)
                ->first()
            : null;

        $totalStudents = $activeYear
            ? StudentEnrollment::whereHas(
                'classroom',
                fn ($q) => $q->where('academic_year_id', $activeYear->id)
                    ->where('team_id', $team->id)
            )->count()
            : 0;

        $totalTeachers = $team->memberships()
            ->where('role', TeamRole::Teacher->value)
            ->count();

        $totalClassrooms = $activeYear
            ? $team->classrooms()->where('academic_year_id', $activeYear->id)->count()
            : 0;

        $today = now()->toDateString();
        $attendanceCounts = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        if ($activeSemester) {
            $attendanceIds = Attendance::where('team_id', $team->id)
                ->whereDate('date', $today)
                ->where('semester_id', $activeSemester->id)
                ->pluck('id');

            if ($attendanceIds->isNotEmpty()) {
                $statusCounts = AttendanceRecord::whereIn('attendance_id', $attendanceIds)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');

                foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                    $attendanceCounts[$status] = (int) ($statusCounts[$status] ?? 0);
                }
            }
        }

        $recentAssessments = [];

        if ($activeSemester) {
            $recentAssessments = Assessment::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->with(['classroom:id,name', 'subject:id,name'])
                ->latest('date')
                ->take(5)
                ->get()
                ->map(fn ($a) => [
                    'id' => $a->id,
                    'title' => $a->title,
                    'classroom' => $a->classroom?->name,
                    'subject' => $a->subject?->name,
                    'date' => $a->date?->toDateString(),
                ])
                ->toArray();
        }

        return [
            'total_students' => $totalStudents,
            'total_teachers' => $totalTeachers,
            'total_classrooms' => $totalClassrooms,
            'attendance_today' => array_merge($attendanceCounts, ['date' => $today]),
            'recent_assessments' => $recentAssessments,
        ];
    }
}
