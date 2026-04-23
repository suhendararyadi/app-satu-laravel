<?php

namespace App\Services\Dashboard;

use App\Models\Academic\AcademicYear;
use App\Models\Academic\Assessment;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\TeacherAssignment;
use App\Models\Schedule\Schedule;
use App\Models\Team;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class TeacherDashboardData
{
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

        // Unique classrooms this teacher is assigned to in the active academic year
        $myClassrooms = [];

        if ($activeYear) {
            $assignments = TeacherAssignment::where('team_id', $team->id)
                ->where('academic_year_id', $activeYear->id)
                ->where('user_id', $user->id)
                ->with(['classroom.grade'])
                ->get()
                ->unique('classroom_id');

            $classroomIds = $assignments->pluck('classroom_id')->filter()->values();

            $enrollmentCounts = StudentEnrollment::whereIn('classroom_id', $classroomIds)
                ->select('classroom_id', DB::raw('count(*) as count'))
                ->groupBy('classroom_id')
                ->pluck('count', 'classroom_id');

            $myClassrooms = $assignments
                ->map(fn ($a) => [
                    'id' => $a->classroom->id,
                    'name' => $a->classroom->name,
                    'grade' => $a->classroom->grade?->name,
                    'student_count' => (int) ($enrollmentCounts[$a->classroom_id] ?? 0),
                ])
                ->values()
                ->toArray();
        }

        // Today's schedule for this teacher in the active semester
        $scheduleToday = [];

        if ($activeSemester) {
            $days = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
            $todayName = $days[now()->dayOfWeek];

            $scheduleToday = Schedule::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('teacher_user_id', $user->id)
                ->where('day_of_week', $todayName)
                ->with(['classroom:id,name', 'subject:id,name', 'timeSlot:id,start_time,end_time'])
                ->get()
                ->map(fn ($s) => [
                    'id' => $s->id,
                    'classroom' => $s->classroom?->name,
                    'subject' => $s->subject?->name,
                    'room' => $s->room,
                    'time_slot' => $s->timeSlot
                        ? $s->timeSlot->start_time.' - '.$s->timeSlot->end_time
                        : null,
                ])
                ->toArray();
        }

        // Assessments where scored count < enrolled student count
        $pendingAssessments = [];

        if ($activeSemester) {
            $assessments = Assessment::where('team_id', $team->id)
                ->where('semester_id', $activeSemester->id)
                ->where('teacher_user_id', $user->id)
                ->with(['classroom:id,name', 'subject:id,name'])
                ->withCount('scores')
                ->get();

            $assessmentClassroomIds = $assessments->pluck('classroom_id')->unique()->filter()->values();

            $assessmentEnrollmentCounts = StudentEnrollment::whereIn('classroom_id', $assessmentClassroomIds)
                ->select('classroom_id', DB::raw('count(*) as count'))
                ->groupBy('classroom_id')
                ->pluck('count', 'classroom_id');

            $pendingAssessments = $assessments
                ->filter(function ($assessment) use ($assessmentEnrollmentCounts) {
                    $total = (int) ($assessmentEnrollmentCounts[$assessment->classroom_id] ?? 0);

                    return $assessment->scores_count < $total;
                })
                ->map(function ($assessment) use ($assessmentEnrollmentCounts) {
                    return [
                        'id' => $assessment->id,
                        'title' => $assessment->title,
                        'classroom' => $assessment->classroom?->name,
                        'subject' => $assessment->subject?->name,
                        'date' => $assessment->date?->toDateString(),
                        'scored' => $assessment->scores_count,
                        'total' => (int) ($assessmentEnrollmentCounts[$assessment->classroom_id] ?? 0),
                    ];
                })
                ->values()
                ->toArray();
        }

        return [
            'my_classrooms' => $myClassrooms,
            'schedule_today' => $scheduleToday,
            'pending_assessments' => $pendingAssessments,
        ];
    }
}
