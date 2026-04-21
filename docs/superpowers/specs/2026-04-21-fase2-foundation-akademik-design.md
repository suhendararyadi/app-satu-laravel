# Fase 2: Foundation Akademik — Design Specification

**Date:** 2026-04-21
**Status:** Approved
**Project:** SmartSchool — app-satu-laravel

## Overview

Fase 2 membangun fondasi akademik SmartSchool: struktur tahun ajaran, tingkat, kelas, mata pelajaran, enrollment siswa, dan penugasan guru. Ini adalah prerequisite untuk semua fitur akademik di Fase 3–7 (penjadwalan, absensi, penilaian, dll).

## Key Decisions

| Keputusan    | Pilihan                                                      |
| ------------ | ------------------------------------------------------------ |
| Scope        | Semua 8 model + 5 controller dalam satu fase                 |
| Frontend     | Backend + React pages CRUD lengkap                           |
| Navigasi     | Grup "Akademik" baru di sidebar                              |
| TeamRole     | Extend enum (Teacher/Student/Parent), UI management menyusul |
| Implementasi | Opsi A — migration-first, semua sekaligus per modul          |

## Data Model

Semua model di-scope ke `team_id` untuk multi-tenant isolation.

### `academic_years` — Tahun Ajaran

```
id
team_id          FK → teams
name             string ("2025/2026")
start_date       date
end_date         date
is_active        boolean (default false)
timestamps
```

- Hanya satu `is_active = true` per team — enforced di controller (set active → deactivate yang lain)
- Hapus tahun ajaran hanya jika tidak punya semester atau classroom yang terkait

### `semesters` — Semester

```
id
academic_year_id FK → academic_years
name             string ("Ganjil" / "Genap")
number           integer (1 / 2)
start_date       date
end_date         date
is_active        boolean (default false)
timestamps
```

- Tidak punya `team_id` langsung — diakses via `academic_year.team_id`
- Hanya satu `is_active = true` per `academic_year_id`
- Dikelola nested di bawah `AcademicYearController` (bukan controller terpisah)

### `grades` — Tingkat/Jenjang

```
id
team_id          FK → teams
name             string ("X", "XI", "XII")
level            integer (10, 11, 12)
timestamps
```

- Data sederhana dan jarang berubah
- Unique: `(team_id, level)`

### `classrooms` — Kelas

```
id
team_id               FK → teams
grade_id              FK → grades
academic_year_id      FK → academic_years
name                  string ("IPA-1", "IPS-2")
homeroom_teacher_id   FK → users (nullable)
capacity              integer (nullable)
timestamps
```

- Unique: `(team_id, grade_id, academic_year_id, name)`
- `homeroom_teacher_id` nullable — wali kelas boleh belum ditentukan saat kelas dibuat

### `subjects` — Mata Pelajaran

```
id
team_id      FK → teams
name         string ("Matematika")
code         string ("MTK")
description  text (nullable)
timestamps
```

- Unique: `(team_id, code)`
- Tidak terikat ke academic_year — mata pelajaran bersifat permanen per sekolah

### `student_enrollments` — Pendaftaran Siswa ke Kelas

```
id
classroom_id      FK → classrooms
student_user_id   FK → users
student_number    string (NIS, nullable)
enrolled_at       date
timestamps
```

- Unique: `(classroom_id, student_user_id)` — satu siswa tidak bisa double-enroll di kelas yang sama
- Dikelola lewat `ClassroomController` (endpoint nested di bawah classroom)

### `teacher_assignments` — Penugasan Guru ke Mapel/Kelas

```
id
team_id            FK → teams
teacher_user_id    FK → users
subject_id         FK → subjects
classroom_id       FK → classrooms
semester_id        FK → semesters
timestamps
```

- Unique: `(teacher_user_id, subject_id, classroom_id, semester_id)`

### `guardians` — Hubungan Orang Tua ↔ Siswa

```
id
team_id            FK → teams
parent_user_id     FK → users
student_user_id    FK → users
relationship       enum: ayah / ibu / wali
timestamps
```

- Unique: `(team_id, parent_user_id, student_user_id)`
- Many-to-many: satu orang tua bisa punya banyak anak, satu siswa bisa punya banyak wali
- Dikelola di luar Fase 2 frontend (data model dibuat, UI menyusul)

## Controllers & Routes

Semua route di bawah prefix `/{current_team}/academic/`, didefinisikan di file baru `routes/academic.php` (di-include dari `routes/web.php`).

Authorization: hanya `Owner` dan `Admin` yang bisa akses semua route akademik ini.

### AcademicYearController

| Method | Path                                               | Action                   |
| ------ | -------------------------------------------------- | ------------------------ |
| GET    | `/academic/years`                                  | index                    |
| GET    | `/academic/years/create`                           | create                   |
| POST   | `/academic/years`                                  | store                    |
| GET    | `/academic/years/{year}/edit`                      | edit                     |
| PUT    | `/academic/years/{year}`                           | update                   |
| DELETE | `/academic/years/{year}`                           | destroy                  |
| POST   | `/academic/years/{year}/activate`                  | activate (set is_active) |
| GET    | `/academic/years/{year}/semesters/create`          | createSemester           |
| POST   | `/academic/years/{year}/semesters`                 | storeSemester            |
| GET    | `/academic/years/{year}/semesters/{semester}/edit` | editSemester             |
| PUT    | `/academic/years/{year}/semesters/{semester}`      | updateSemester           |
| DELETE | `/academic/years/{year}/semesters/{semester}`      | destroySemester          |

### GradeController

| Method | Path                            | Action  |
| ------ | ------------------------------- | ------- |
| GET    | `/academic/grades`              | index   |
| GET    | `/academic/grades/create`       | create  |
| POST   | `/academic/grades`              | store   |
| GET    | `/academic/grades/{grade}/edit` | edit    |
| PUT    | `/academic/grades/{grade}`      | update  |
| DELETE | `/academic/grades/{grade}`      | destroy |

### ClassroomController

| Method | Path                                                        | Action                       |
| ------ | ----------------------------------------------------------- | ---------------------------- |
| GET    | `/academic/classrooms`                                      | index                        |
| GET    | `/academic/classrooms/create`                               | create                       |
| POST   | `/academic/classrooms`                                      | store                        |
| GET    | `/academic/classrooms/{classroom}`                          | show (detail + daftar siswa) |
| GET    | `/academic/classrooms/{classroom}/edit`                     | edit                         |
| PUT    | `/academic/classrooms/{classroom}`                          | update                       |
| DELETE | `/academic/classrooms/{classroom}`                          | destroy                      |
| POST   | `/academic/classrooms/{classroom}/enrollments`              | enrollStudent                |
| DELETE | `/academic/classrooms/{classroom}/enrollments/{enrollment}` | unenrollStudent              |

### SubjectController

| Method | Path                                | Action  |
| ------ | ----------------------------------- | ------- |
| GET    | `/academic/subjects`                | index   |
| GET    | `/academic/subjects/create`         | create  |
| POST   | `/academic/subjects`                | store   |
| GET    | `/academic/subjects/{subject}/edit` | edit    |
| PUT    | `/academic/subjects/{subject}`      | update  |
| DELETE | `/academic/subjects/{subject}`      | destroy |

### TeacherAssignmentController

| Method | Path                                 | Action                               |
| ------ | ------------------------------------ | ------------------------------------ |
| GET    | `/academic/assignments`              | index (filter by classroom/semester) |
| POST   | `/academic/assignments`              | store (assign guru)                  |
| DELETE | `/academic/assignments/{assignment}` | destroy (unassign guru)              |

## Frontend — React Pages

### Navigasi Sidebar

Tambah grup baru "Akademik" di `AppSidebar`, setelah grup CMS:

```
Akademik
├── Tahun Ajaran    → /{team}/academic/years
├── Tingkat         → /{team}/academic/grades
├── Kelas           → /{team}/academic/classrooms
├── Mata Pelajaran  → /{team}/academic/subjects
└── Penugasan Guru  → /{team}/academic/assignments
```

### Struktur Halaman

`resources/js/pages/academic/`:

```
years/
├── index.tsx   — tabel tahun ajaran + badge "Aktif" + tombol Aktifkan + ConfirmDeleteDialog
├── create.tsx  — form nama + tanggal mulai/akhir
└── edit.tsx    — form edit tahun ajaran + section manajemen semester (list + add + edit + delete)

grades/
├── index.tsx   — tabel tingkat + ConfirmDeleteDialog
├── create.tsx  — form nama + level
└── edit.tsx    — form edit nama + level

classrooms/
├── index.tsx   — tabel kelas, filter by grade + academic year
├── create.tsx  — form (pilih grade, tahun ajaran, nama, wali kelas, kapasitas)
├── edit.tsx    — form edit
└── show.tsx    — detail kelas: info header + tabel siswa enrolled + form enroll siswa baru

subjects/
├── index.tsx   — tabel mata pelajaran + ConfirmDeleteDialog
└── create.tsx  — form nama + kode + deskripsi

assignments/
└── index.tsx   — tabel penugasan guru, filter by classroom/semester
                  + form assign (inline form atau modal)
```

### TypeScript Types

File baru `resources/js/types/academic.ts`. Tipe `User` diimport dari `@/types` (existing):

````ts
import type { User } from '@/types'

```ts
export interface AcademicYear {
    id: number;
    name: string;
    start_date: string;
    end_date: string;
    is_active: boolean;
    semesters?: Semester[];
}

export interface Semester {
    id: number;
    academic_year_id: number;
    name: string;
    number: 1 | 2;
    start_date: string;
    end_date: string;
    is_active: boolean;
}

export interface Grade {
    id: number;
    name: string;
    level: number;
}

export interface Classroom {
    id: number;
    grade_id: number;
    academic_year_id: number;
    name: string;
    homeroom_teacher_id: number | null;
    capacity: number | null;
    grade?: Grade;
    academic_year?: AcademicYear;
    homeroom_teacher?: User;
}

export interface Subject {
    id: number;
    name: string;
    code: string;
    description: string | null;
}

export interface StudentEnrollment {
    id: number;
    classroom_id: number;
    student_user_id: number;
    student_number: string | null;
    enrolled_at: string;
    student?: User;
}

export interface TeacherAssignment {
    id: number;
    teacher_user_id: number;
    subject_id: number;
    classroom_id: number;
    semester_id: number;
    teacher?: User;
    subject?: Subject;
    classroom?: Classroom;
    semester?: Semester;
}

export interface Guardian {
    id: number;
    parent_user_id: number;
    student_user_id: number;
    relationship: 'ayah' | 'ibu' | 'wali';
}
````

### UI Patterns

Konsisten dengan CMS Fase 1:

- `ConfirmDeleteDialog` untuk semua aksi delete
- Error validasi dari Laravel di-render inline per field
- Breadcrumb lewat Inertia shared props
- Layout: `AppLayout` (sudah ada, tidak ada perubahan)

## TeamRole Extension

Extend `app/Enums/TeamRole.php`:

```php
enum TeamRole: string
{
    case Owner   = 'owner';    // level 5
    case Admin   = 'admin';    // level 4
    case Teacher = 'teacher';  // level 3  ← baru
    case Student = 'student';  // level 2  ← baru
    case Parent  = 'parent';   // level 1  ← baru
}
```

- Update `TeamPermission` enum jika ada permission yang perlu disesuaikan
- Tidak ada UI untuk manage role baru ini di Fase 2 — menyusul di fase berikutnya

## Testing Strategy

### Struktur File

```
tests/Feature/Academic/
├── AcademicYearControllerTest.php
├── SemesterControllerTest.php
├── GradeControllerTest.php
├── ClassroomControllerTest.php
├── SubjectControllerTest.php
├── StudentEnrollmentTest.php
└── TeacherAssignmentControllerTest.php
```

### Coverage per Controller

**AcademicYearController:**

- Owner/Admin dapat CRUD tahun ajaran
- Set active: hanya satu yang aktif per team
- Teacher/Student/Parent di-reject 403
- Data team A tidak bisa diakses team B
- Semester: CRUD nested, nested under academic year

**GradeController:**

- Owner/Admin dapat CRUD tingkat
- Unique constraint level per team ter-enforce
- Unauthorized roles di-reject 403

**ClassroomController:**

- Owner/Admin dapat CRUD kelas
- Unique constraint (team, grade, year, name) ter-enforce
- Enrollment: siswa bisa di-enroll, tidak bisa double-enroll
- Unenroll: menghapus enrollment
- Multi-tenancy: isolation antar team

**SubjectController:**

- Owner/Admin dapat CRUD mata pelajaran
- Unique constraint code per team ter-enforce
- Unauthorized roles di-reject 403

**TeacherAssignmentController:**

- Owner/Admin dapat assign/unassign guru
- Unique constraint ter-enforce
- Multi-tenancy isolation

**Estimasi:** ~45–55 test baru (total suite menjadi ~190–200 tests)

## Implementation Order

Dikerjakan modul per modul dalam urutan dependency:

1. `TeamRole` enum extension
2. TypeScript types file (`resources/js/types/academic.ts`)
3. `AcademicYear` + `Semester` (migrations → model → factory → controller → routes → React pages → tests)
4. `Grade` (sama)
5. `Classroom` (sama — depends on Grade + AcademicYear)
6. `Subject` (sama)
7. `StudentEnrollment` (depends on Classroom)
8. `TeacherAssignment` (depends on Classroom + Subject + Semester)
9. `Guardian` (model + migration + factory — UI menyusul)
10. Sidebar navigation update (`AppSidebar`)
