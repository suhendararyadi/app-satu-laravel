<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Guardian;
use App\Models\Academic\Score;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class ParentDashboardData
{
    /**
     * Get parent dashboard data for the given user and team.
     *
     * Guardian model has no team_id; scoping to team is done via StudentEnrollment.
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

        // Find all students where this user is a guardian
        // Guardian model has no team_id; scope to team via StudentEnrollment on active year
        $guardians = Guardian::where('guardian_id', $user->id)
            ->with('student')
            ->get();

        $children = [];

        foreach ($guardians as $guardian) {
            $studentUser = $guardian->student;

            if (! $studentUser || ! $activeYear) {
                continue;
            }

            // Verify student is enrolled in this team's active year
            $enrollment = StudentEnrollment::where('user_id', $studentUser->id)
                ->whereHas(
                    'classroom',
                    fn ($q) => $q->where('academic_year_id', $activeYear->id)
                        ->where('team_id', $team->id)
                )
                ->with(['classroom:id,name,grade_id', 'classroom.grade:id,name'])
                ->first();

            if (! $enrollment) {
                continue;
            }

            $classroom = [
                'id' => $enrollment->classroom->id,
                'name' => $enrollment->classroom->name,
                'grade' => $enrollment->classroom->grade?->name,
            ];

            // Latest 3 scores in the active semester
            $recentScores = [];

            if ($activeSemester) {
                $recentScores = Score::where('student_user_id', $studentUser->id)
                    ->whereHas(
                        'assessment',
                        fn ($q) => $q->where('semester_id', $activeSemester->id)
                            ->where('team_id', $team->id)
                    )
                    ->with(['assessment:id,title,max_score,subject_id', 'assessment.subject:id,name'])
                    ->latest()
                    ->take(3)
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
                        ->where('student_user_id', $studentUser->id)
                        ->select('status', DB::raw('count(*) as count'))
                        ->groupBy('status')
                        ->pluck('count', 'status');

                    foreach (['hadir', 'sakit', 'izin', 'alpa'] as $status) {
                        $attendanceSummary[$status] = (int) ($statusCounts[$status] ?? 0);
                    }
                }
            }

            $children[] = [
                'student' => [
                    'id' => $studentUser->id,
                    'name' => $studentUser->name,
                    'email' => $studentUser->email,
                ],
                'classroom' => $classroom,
                'recent_scores' => $recentScores,
                'attendance_summary' => $attendanceSummary,
            ];
        }

        return ['children' => $children];
    }
}
