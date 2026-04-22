# Student Detail Page — Design Spec

**Date:** 2026-04-22
**Status:** Approved

## Overview

Add a read-only detail page for each student at `/{current_team}/students/{user}`. The page shows the student's profile, class enrollment, attendance summary + full paginated records, and guardian data.

## Route & Controller

### New route

```
GET /{current_team}/students/{user}   → students.show
```

Added to `routes/students.php` in the static block, before the `{user}/edit` and `{user}` (PATCH/DELETE) lines.

### `StudentController::show()`

- Guard: `abort_unless` student belongs to current team with `TeamRole::Student`.
- Queries:
  - **Profile** — `$user->name`, `$user->email`, pivot `created_at` (joined_at)
  - **Enrollment** — first `StudentEnrollment` where `classroom_id` in team's classrooms, eager-loaded with `classroom.grade` and `classroom.academicYear`
  - **Attendance summary** — `AttendanceRecord` for `student_user_id = $user->id`, JOIN through `attendances` scoped to team's classrooms, grouped by `status`. Pre-fill all four statuses (`hadir`, `sakit`, `izin`, `alpa`) with count 0 when absent, so the frontend always receives exactly 4 entries.
  - **Attendance records** — same scope, paginated 15/page, ordered by attendance `date` DESC; each row: `date`, `subject_name`, `status`, `notes`
  - **Guardians** — `Guardian` where `student_id = $user->id`, eager-loaded with `guardian` user; returns `[{name, email, relationship_label}]`

### Props passed to Inertia

```php
[
    'student' => [
        'id'        => int,
        'name'      => string,
        'email'     => string,
        'joined_at' => string (ISO date),
    ],
    'enrollment' => [           // nullable
        'classroom_name'    => string,
        'student_number'    => string|null,
        'grade_name'        => string,
        'academic_year_name'=> string,
    ] | null,
    'attendance_summary' => [   // always 4 entries
        ['status' => 'hadir', 'count' => int],
        ['status' => 'sakit', 'count' => int],
        ['status' => 'izin',  'count' => int],
        ['status' => 'alpa',  'count' => int],
    ],
    'attendance_records' => PaginatedCollection([
        'date'         => string,
        'subject_name' => string,
        'status'       => string,
        'notes'        => string|null,
    ]),
    'guardians' => [
        ['name' => string, 'email' => string, 'relationship_label' => string],
        ...
    ],
]
```

## Frontend: `resources/js/pages/students/show.tsx`

### Layout (top to bottom)

1. **`<Head>`** — title: student name
2. **Page header**
   - Left: `<h1>` student name, `<p>` email (muted)
   - Right: "Edit" button → `StudentController.edit.url(...)`, "Kembali" button (outline) → `StudentController.index.url(...)`
3. **Profil card** (simple `<dl>` or key-value pairs)
   - Email
   - Tanggal bergabung (formatted)
4. **Info Kelas card**
   - If `enrollment` is null: muted text "Belum terdaftar di kelas manapun"
   - Else: Kelas, NIS (or "—"), Tingkat, Tahun Ajaran
5. **Kehadiran section**
   - 4 summary badges/stat boxes: Hadir, Sakit, Izin, Alpa (each with count)
   - Table: columns Tanggal | Mata Pelajaran | Status | Catatan
   - Server-side pagination (preserveState + preserveScroll)
   - Empty state if no records
6. **Wali section**
   - Table: columns Nama | Email | Hubungan
   - Empty state: "Belum ada data wali" if empty

### Navigation from index

In `students/index.tsx`, the student's **name cell** becomes a `<Link>` to `StudentController.show.url(...)`. No extra "Detail" column needed. This is a separate task in the implementation plan.

## Testing

- `tests/Feature/Students/StudentShowTest.php` (Pest)
- Tests:
  1. Owner can view show page (200 + correct Inertia component)
  2. Page returns correct student props (name, email)
  3. Page returns enrollment data when enrolled
  4. Enrollment is null when student has no enrollment
  5. Attendance summary counts are correct
  6. Attendance records are paginated
  7. Guardians are returned correctly
  8. Returns 404 for non-student user
  9. Unauthenticated user is redirected

## Out of Scope

- Editing or adding guardians from this page (future feature)
- Attendance filtering by semester/subject on this page
- Exporting student data
