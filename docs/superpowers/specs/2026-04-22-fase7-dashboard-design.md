# Fase 7: Dashboard — Design Specification

**Date:** 2026-04-22
**Status:** Approved
**Scope:** Role-based dashboard only. Export (PDF/Excel) and `report_exports` model are out-of-scope for this phase.

## Overview

Mengganti placeholder dashboard dengan konten nyata berdasarkan role user yang sedang login. Satu URL (`/dashboard`), satu React page, tapi konten berbeda per role. Data diambil hanya dari semester/tahun ajaran aktif (`is_active = true`).

## Out of Scope

- Export PDF / Excel
- Model `report_exports`
- Data keuangan (Fase 5 belum dibangun)
- Pengumuman/notifikasi (Fase 6 belum dibangun)

## Architecture

### Role Detection

`DashboardController::index()` membaca role user di current team, lalu delegate ke service class yang sesuai.

```
Role level ≥ 4 (Owner/Admin)  → AdminDashboardData
Role = Teacher                → TeacherDashboardData
Role = Student                → StudentDashboardData
Role = Parent                 → ParentDashboardData
No school team (personal)     → existing hasSchoolTeam = false behavior
```

### Service Classes

```
app/Services/Dashboard/
├── AdminDashboardData.php
├── TeacherDashboardData.php
├── StudentDashboardData.php
└── ParentDashboardData.php
```

Each service has one public method: `get(User $user, Team $team): array`.

### Data Flow

```
Request → DashboardController
       → detect role in current team
       → call Service::get($user, $team)
       → Inertia::render('dashboard', ['role' => $role, 'data' => $dashboardData])
```

## Data Per Role

All queries are scoped to the current team and active semester (joined via `academic_years.is_active = true` and `semesters.is_active = true`).

### Owner / Admin (level ≥ 4)

```php
[
    'total_students'      => int,   // StudentEnrollment count in active year's classrooms
    'total_teachers'      => int,   // Users with Teacher role in team
    'total_classrooms'    => int,   // Classroom count for active academic year
    'attendance_today'    => [
        'hadir'  => int,
        'sakit'  => int,
        'izin'   => int,
        'alpa'   => int,
        'date'   => string,         // today's date
    ],
    'recent_assessments'  => Assessment[5],  // Latest 5 assessments across all classrooms
                                              // includes: title, classroom.name, subject.name, date
]
```

### Teacher

```php
[
    'my_classrooms'          => Classroom[],  // Via TeacherAssignment, active semester
                                               // includes: name, grade.name, student_count
    'schedule_today'         => Schedule[],   // day_of_week = today, for this teacher
                                               // includes: time_slot, classroom.name, subject.name, room
    'pending_assessments'    => [             // Assessments where score count < enrolled student count
        [
            'id'             => int,
            'title'          => string,
            'classroom'      => string,
            'subject'        => string,
            'date'           => string,
            'scored'         => int,
            'total'          => int,
        ]
    ],
]
```

### Student

```php
[
    'classroom'            => ?Classroom,     // Student's current classroom via StudentEnrollment
                                               // includes: name, grade.name, homeroom_teacher.name
    'schedule_today'       => Schedule[],     // For student's classroom today
    'recent_scores'        => Score[5],       // Latest 5 scores for this student
                                               // includes: assessment.title, assessment.subject.name,
                                               //           score, assessment.max_score
    'attendance_summary'   => [               // Active semester counts
        'hadir'  => int,
        'sakit'  => int,
        'izin'   => int,
        'alpa'   => int,
    ],
]
```

### Parent

```php
[
    'children' => [
        [
            'student'              => User,      // name, email
            'classroom'            => ?Classroom, // name, grade.name
            'recent_scores'        => Score[3],  // Latest 3 scores
            'attendance_summary'   => [
                'hadir'  => int,
                'sakit'  => int,
                'izin'   => int,
                'alpa'   => int,
            ],
        ]
    ],
]
```

Parent data is loaded from the `guardians` table where `guardian_id = $user->id`, scoped to `team_id`.

## Frontend

### Updated `resources/js/pages/dashboard.tsx`

Receives `role: string` and `data: DashboardData` (or `hasSchoolTeam: boolean` for personal team users). Renders role-specific component.

```tsx
// pseudocode
if (!hasSchoolTeam) return <OnboardingBanner />
switch (role) {
    case 'owner':
    case 'admin':  return <AdminDashboard data={data} />
    case 'teacher': return <TeacherDashboard data={data} />
    case 'student': return <StudentDashboard data={data} />
    case 'parent':  return <ParentDashboard data={data} />
}
```

### Sub-components `resources/js/components/dashboard/`

| File | Content |
|------|---------|
| `admin-dashboard.tsx` | 3 stat cards (Siswa/Guru/Kelas) + Kehadiran Hari Ini table (4 status) + 5 Penilaian Terbaru list |
| `teacher-dashboard.tsx` | Kelas Diampu cards (name + grade + student count) + Jadwal Hari Ini list + Penilaian Pending list (with scored/total badge) |
| `student-dashboard.tsx` | Kelas card + Jadwal Hari Ini list + 5 Nilai Terbaru list + Rekap Kehadiran (4 badge counters) |
| `parent-dashboard.tsx` | Per-anak card: nama siswa, kelas, 3 nilai terbaru, rekap kehadiran (4 badge counters) |

Existing `OnboardingBanner` behavior is preserved for users without a school team.

## TypeScript Types

Add to `resources/js/types/dashboard.ts` (new file):

```ts
export type AttendanceSummary = {
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
};

export type AdminDashboardData = {
    total_students: number;
    total_teachers: number;
    total_classrooms: number;
    attendance_today: AttendanceSummary & { date: string };
    recent_assessments: RecentAssessment[];
};

export type TeacherDashboardData = {
    my_classrooms: TeacherClassroom[];
    schedule_today: TodaySchedule[];
    pending_assessments: PendingAssessment[];
};

export type StudentDashboardData = {
    classroom: StudentClassroom | null;
    schedule_today: TodaySchedule[];
    recent_scores: RecentScore[];
    attendance_summary: AttendanceSummary;
};

export type ParentDashboardData = {
    children: ChildData[];
};

export type DashboardProps =
    | { hasSchoolTeam: false }
    | { hasSchoolTeam: true; role: 'owner' | 'admin'; data: AdminDashboardData }
    | { hasSchoolTeam: true; role: 'teacher'; data: TeacherDashboardData }
    | { hasSchoolTeam: true; role: 'student'; data: StudentDashboardData }
    | { hasSchoolTeam: true; role: 'parent'; data: ParentDashboardData };
```

(Full sub-types defined inline in the types file.)

## Testing

**`tests/Feature/DashboardControllerTest.php`** — update existing + add new tests:

| Test group | What to test |
|------------|-------------|
| Admin/Owner | Returns `role = admin`, `total_students`, `total_teachers`, `total_classrooms`, `attendance_today`, `recent_assessments` |
| Teacher | Returns `role = teacher`, `my_classrooms`, `schedule_today`, `pending_assessments` |
| Student | Returns `role = student`, `classroom`, `recent_scores`, `attendance_summary` |
| Parent | Returns `role = parent`, `children` array with student data |
| No school team | Returns `hasSchoolTeam = false`, no role/data key |
| Cross-tenant | Ensures data from another team is NOT included |

Each test creates minimal factory data (active academic year → active semester → classroom → enrollments) and asserts the correct structure in the Inertia response.

## Affected Files

### New Files
```
app/Services/Dashboard/AdminDashboardData.php
app/Services/Dashboard/TeacherDashboardData.php
app/Services/Dashboard/StudentDashboardData.php
app/Services/Dashboard/ParentDashboardData.php
resources/js/types/dashboard.ts
resources/js/components/dashboard/admin-dashboard.tsx
resources/js/components/dashboard/teacher-dashboard.tsx
resources/js/components/dashboard/student-dashboard.tsx
resources/js/components/dashboard/parent-dashboard.tsx
```

### Modified Files
```
app/Http/Controllers/DashboardController.php
resources/js/pages/dashboard.tsx
tests/Feature/DashboardControllerTest.php  (new test file — existing test is minimal)
```

### No Changes
- Migrations (no new tables)
- Routes (existing `/dashboard` route unchanged)
- Sidebar (existing nav items unchanged)
