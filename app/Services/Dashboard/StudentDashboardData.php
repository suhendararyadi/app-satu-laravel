<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Schedule\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class StudentDashboardData
{
    /**
     * Get student dashboard data for the given user and team.
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

        // Student's current classroom via StudentEnrollment in active academic year
        $classroom = null;
        $enrolledClassroomId = null;

        if ($activeYear) {
            $enrollment = StudentEnrollment::where('user_id', $user->id)
                ->whereHas(
                    'classroom',
                    fn ($q) => $q->where('academic_year_id', $activeYear->id)
                        ->where('team_id', $team->id)
                )
                ->with(['classroom:id,name,grade_id', 'classroom.grade:id,name'])
                ->first();

            if ($enrollment) {
                $enrolledClassroomId = $enrollment->classroom_id;
                $classroom = [
                    'id' => $enrollment->classroom->id,
                    'name' => $enrollment->classroom->name,
                    'grade' => $enrollment->classroom->grade?->name,
                ];
            }
        }

        // Today's schedule for student's classroom in active semester
        $scheduleToday = [];

        if ($activeSemester && $enrolledClassroomId) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $todayName = $days[now()->dayOfWeek];

            $scheduleToday = Schedule::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('classroom_id', $enrolledClassroomId)
                ->where('day_of_week', $todayName)
                ->with(['subject:id,name', 'timeSlot:id,start_time,end_time'])
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'subject' => $s->subject?->name,
                    'room' => $s->room,
                    'time_slot' => $s->timeSlot
                        ? $s->timeSlot->start_time.' - '.$s->timeSlot->end_time
                        : null,
                ])
                ->toArray();
        }

        // Latest 5 scores in the active semester
        $recentScores = [];

        if ($activeSemester) {
            $recentScores = Score::where('student_user_id', $user->id)
                ->whereHas(
                    'assessment',
                    fn ($q) => $q->where('semester_id', $activeSemester->id)
                        ->where('team_id', $team->id)
                )
                ->with(['assessment:id,title,max_score,subject_id', 'assessment.subject:id,name'])
                ->latest()
                ->take(5)
                ->get()
                ->map(fn ($score) => [
                    'id' => $score->id,
                    'score' => (float) $score->score,
                    'assessment_title' => $score->assessment?->title,
                    'subject' => $score->assessment?->subject?->name,
                    'max_score' => (float) ($score->assessment?->max_score ?? 100),
                ])
                ->toArray();
        }

        // Attendance summary for active semester
        $attendanceSummary = ['hadir' => 0, 'sakit' => 0, 'izin' => 0, 'alpa' => 0];

        if ($activeSemester) {
            $attendanceIds = Attendance::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->pluck('id');

            if ($attendanceIds->isNotEmpty()) {
                $statusCounts = AttendanceRecord::whereIn('attendance_id', $attendanceIds)
                    ->where('student_user_id', $user->id)
                    ->select('status', DB::raw('count(*) as count'))
                    ->groupBy('status')
                    ->pluck('count', 'status');

                foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                    $attendanceSummary[$status] = (int) ($statusCounts[$status] ?? 0);
                }
            }
        }

        return [
            'classroom' => $classroom,
            'schedule_today' => $scheduleToday,
            'recent_scores' => $recentScores,
            'attendance_summary' => $attendanceSummary,
        ];
    }
}
