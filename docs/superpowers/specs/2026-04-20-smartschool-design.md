# SmartSchool — Design Specification

**Date:** 2026-04-20
**Status:** Approved
**Project:** app-satu-laravel → SmartSchool SaaS

## Overview

SmartSchool adalah aplikasi manajemen sekolah (School Management System) berbasis SaaS untuk SMA/sederajat di Indonesia. Dibangun di atas stack Laravel 13 + React 19 + Inertia.js v3 + TypeScript + Tailwind CSS v4.

Aplikasi ini mengubah boilerplate multi-tenant (Team-based) yang sudah ada menjadi platform sekolah pintar dengan CMS, manajemen akademik, keuangan, dan komunikasi.

## Key Decisions

| Keputusan        | Pilihan                                                           |
| ---------------- | ----------------------------------------------------------------- |
| Target users     | Admin, Guru, Siswa, Orang Tua/Wali                                |
| Multi-tenancy    | SaaS — shared DB + team_id (school_id)                            |
| Kurikulum        | Agnostik (fokus administrasi, bukan kurikulum spesifik)           |
| Arsitektur       | Team-as-School — extend existing Team model, bukan rename/rebuild |
| Data isolation   | Shared database, semua model di-scope ke `team_id`                |
| CMS public URL   | Custom domain per-sekolah + fallback `/schools/{slug}`            |
| Periode akademik | Semester (Ganjil/Genap) per tahun ajaran                          |
| Strategi dev     | Incremental — 7 fase                                              |

## Architecture

### Multi-Tenancy: Team = School

Existing `Team` model di-extend dengan field sekolah. Tidak ada rename — UI menampilkan "Sekolah" tapi kode internal tetap `Team`. Ini memanfaatkan:

- Existing `Membership` pivot dengan role system
- Existing route scoping `/{current_team}/...` dengan `EnsureTeamMembership` middleware
- Existing invitation system
- Existing social auth (Google OAuth)

`is_personal = false` menandakan team sebagai sekolah (bukan personal workspace).

### CMS Custom Domain

Setiap sekolah bisa memasang custom domain (contoh: `www.sma1.sch.id`).

**Routing flow:**

1. Request masuk → `ResolveSchoolDomain` middleware cek `Host` header
2. Lookup `teams.custom_domain` → jika match, inject school context
3. Render public CMS routes (`/`, `/page/{slug}`, `/news`, dll)
4. Jika tidak match → lanjut ke route normal (authenticated app)
5. Fallback: `/schools/{slug}/...` untuk sekolah tanpa custom domain

**Public CMS routes (pada custom domain):**

- `GET /` — Homepage sekolah
- `GET /page/{slug}` — Halaman statis
- `GET /news` — Daftar berita/artikel
- `GET /news/{slug}` — Detail berita
- `GET /gallery` — Daftar galeri
- `GET /gallery/{id}` — Detail galeri + foto
- `GET /contact` — Halaman kontak

### Role & Permission System

Extend `TeamRole` enum dari 3 menjadi 5 role:

| Role      | Level | Mapping                     | Deskripsi                    |
| --------- | ----- | --------------------------- | ---------------------------- |
| `Owner`   | 5     | Kepala Sekolah / SuperAdmin | Full access                  |
| `Admin`   | 4     | Tata Usaha / Staff          | Kelola data, users, keuangan |
| `Teacher` | 3     | Guru                        | Kelola kelas sendiri         |
| `Student` | 2     | Siswa                       | Lihat data sendiri           |
| `Parent`  | 1     | Orang Tua/Wali              | Monitoring anak              |

**Permission matrix:**

| Fitur            | Owner | Admin | Teacher         | Student  | Parent    |
| ---------------- | ----- | ----- | --------------- | -------- | --------- |
| Settings sekolah | Full  | Full  | -               | -        | -         |
| Kelola users     | Full  | Full  | -               | -        | -         |
| CMS Pages        | Full  | Full  | -               | -        | -         |
| CMS Posts/Berita | Full  | Full  | Create/Edit own | -        | -         |
| Data akademik    | Full  | Full  | View            | -        | -         |
| Jadwal           | Full  | Full  | View own        | View own | View anak |
| Absensi          | Full  | Full  | Manage kelas    | View own | View anak |
| Nilai & Rapor    | Full  | Full  | Manage kelas    | View own | View anak |
| Keuangan/SPP     | Full  | Full  | -               | View own | View anak |
| Dashboard        | Full  | Full  | Kelas-level     | Personal | Per-anak  |

### Parent-Student Linking

Tabel `guardians` menghubungkan orang tua ↔ siswa:

```
guardians
├── id
├── team_id (FK → teams)
├── parent_user_id (FK → users)
├── student_user_id (FK → users)
├── relationship (enum: ayah/ibu/wali)
└── timestamps
```

Many-to-many: satu orang tua bisa punya banyak anak, satu siswa bisa punya banyak wali.

## Development Phases

### Fase 1: CMS & Profil Sekolah

**Extend `teams` table:**

- `npsn` (nullable, unique) — Nomor Pokok Sekolah Nasional
- `school_type` (enum: SMA/SMK/MA)
- `address`, `city`, `province`, `postal_code`
- `phone`, `email`
- `logo_path`
- `accreditation`
- `principal_name`
- `founded_year`
- `vision` (text), `mission` (text), `description` (text)
- `website_theme`
- `custom_domain` (nullable, unique)

**New models:**

**`pages`** — Halaman statis CMS

- id, team_id, title, slug, content (longText/rich), is_published, sort_order, meta_description, timestamps
- Unique: (team_id, slug)

**`posts`** — Berita/artikel

- id, team_id, author_id (FK → users), title, slug, excerpt, content (longText/rich), featured_image_path, is_published, published_at, meta_description, timestamps
- Unique: (team_id, slug)

**`galleries`** — Album foto

- id, team_id, title, description, is_published, timestamps

**`gallery_images`** — Foto dalam album

- id, gallery_id (FK → galleries), image_path, caption, sort_order, timestamps

**Controllers:**

- `SchoolProfileController` — edit profil sekolah (admin)
- `PageController` — CRUD halaman statis (admin)
- `PostController` — CRUD berita (admin/teacher)
- `GalleryController` — CRUD galeri (admin)
- `PublicSchoolController` — render public website (custom domain + fallback)

**Middleware:**

- `ResolveSchoolDomain` — resolve custom domain ke team context

### Fase 2: Foundation Akademik

**New models:**

**`academic_years`** — Tahun Ajaran

- id, team_id, name ("2025/2026"), start_date, end_date, is_active, timestamps
- Constraint: hanya satu active per team

**`semesters`** — Semester

- id, academic_year_id (FK), name ("Ganjil"/"Genap"), number (1/2), start_date, end_date, is_active, timestamps

**`grades`** — Tingkat/Jenjang

- id, team_id, name ("X", "XI", "XII"), level (10, 11, 12), timestamps

**`classrooms`** — Kelas

- id, team_id, grade_id (FK), academic_year_id (FK), name ("IPA-1", "IPS-2"), homeroom_teacher_id (FK → users), capacity, timestamps
- Unique: (team_id, grade_id, academic_year_id, name)

**`subjects`** — Mata Pelajaran

- id, team_id, name ("Matematika"), code ("MTK"), description, timestamps
- Unique: (team_id, code)

**`student_enrollments`** — Pendaftaran Siswa ke Kelas

- id, classroom_id (FK), student_user_id (FK → users), student_number (NIS), enrolled_at, timestamps
- Unique: (classroom_id, student_user_id)

**`teacher_assignments`** — Penugasan Guru

- id, team_id, teacher_user_id (FK → users), subject_id (FK), classroom_id (FK), semester_id (FK), timestamps
- Unique: (teacher_user_id, subject_id, classroom_id, semester_id)

**`guardians`** — Hubungan Orang Tua ↔ Siswa

- id, team_id (FK), parent_user_id (FK → users), student_user_id (FK → users), relationship (enum: ayah/ibu/wali), timestamps
- Unique: (team_id, parent_user_id, student_user_id)

**Controllers:**

- `AcademicYearController` — CRUD tahun ajaran + semester
- `GradeController` — CRUD tingkat
- `ClassroomController` — CRUD kelas + enrollment siswa
- `SubjectController` — CRUD mata pelajaran
- `TeacherAssignmentController` — assign guru ke mapel/kelas

**Extended `TeamRole` enum:**

- Add: `Teacher` (level 3), `Student` (level 2), `Parent` (level 1)
- Extend `TeamPermission` enum dengan permission baru per modul

### Fase 3: Penjadwalan & Kehadiran

**New models:**

**`time_slots`** — Jam Pelajaran

- id, team_id, name ("Jam 1"), start_time, end_time, sort_order, timestamps

**`schedules`** — Jadwal Pelajaran

- id, team_id, semester_id (FK), classroom_id (FK), subject_id (FK), teacher_user_id (FK → users), day_of_week (enum: Senin-Sabtu), time_slot_id (FK), room, timestamps
- Unique: (semester_id, classroom_id, day_of_week, time_slot_id)

**`attendances`** — Header Absensi

- id, team_id, classroom_id (FK), date, subject_id (FK, nullable), semester_id (FK), recorded_by (FK → users), timestamps
- Unique: (classroom_id, date, subject_id) — satu absensi per kelas per hari per mapel. Untuk absensi harian (subject_id = null), gunakan partial unique index atau validasi di application layer.

**`attendance_records`** — Detail Absensi per Siswa

- id, attendance_id (FK), student_user_id (FK → users), status (enum: hadir/sakit/izin/alpa), notes, timestamps
- Unique: (attendance_id, student_user_id)

**Controllers:**

- `TimeSlotController` — CRUD jam pelajaran
- `ScheduleController` — CRUD jadwal + view per kelas/guru
- `AttendanceController` — Input absensi, rekap per kelas/siswa

### Fase 4: Penilaian & Rapor

**New models:**

**`assessment_categories`** — Kategori Penilaian

- id, team_id, name ("Ulangan Harian", "UTS", "UAS", "Tugas"), weight (decimal, percentage), timestamps
- Bobot per kategori fleksibel per sekolah, total harus 100%

**`assessments`** — Penilaian/Ujian

- id, team_id, classroom_id (FK), subject_id (FK), semester_id (FK), assessment_category_id (FK), title, max_score, date, teacher_user_id (FK → users), timestamps

**`scores`** — Nilai Siswa

- id, assessment_id (FK), student_user_id (FK → users), score (decimal), notes, timestamps
- Unique: (assessment_id, student_user_id)

**`report_cards`** — Rapor (header/summary)

- id, team_id, semester_id (FK), classroom_id (FK), student_user_id (FK → users), generated_at, notes, timestamps
- Rapor di-generate dari agregasi skor per semester

**Controllers:**

- `AssessmentCategoryController` — CRUD kategori penilaian + bobot
- `AssessmentController` — CRUD penilaian + input nilai batch
- `ScoreController` — Input/edit nilai individual
- `ReportCardController` — Generate, view, print rapor

### Fase 5: Keuangan/SPP

**New models:**

**`fee_types`** — Jenis Tagihan

- id, team_id, name ("SPP", "Daftar Ulang", "Seragam"), amount (decimal), is_recurring (bool), recurrence (enum: monthly/semester/yearly), timestamps

**`invoices`** — Tagihan

- id, team_id, fee_type_id (FK), student_user_id (FK → users), semester_id (FK), amount (decimal), due_date, status (enum: pending/paid/overdue/cancelled), paid_at, timestamps

**`payments`** — Pembayaran

- id, invoice_id (FK), amount (decimal), payment_method, payment_reference, paid_at, received_by (FK → users), notes, timestamps

Mendukung pembayaran parsial (total payments bisa < invoice amount).

**Controllers:**

- `FeeTypeController` — CRUD jenis tagihan
- `InvoiceController` — Generate tagihan (batch per kelas), view, update status
- `PaymentController` — Catat pembayaran, print bukti bayar

### Fase 6: Komunikasi

**New models:**

**`announcements`** — Pengumuman

- id, team_id, author_id (FK → users), title, content (longText), target_type (enum: all/grade/classroom/role), target_id (nullable — grade_id, classroom_id, atau role string), is_published, published_at, timestamps

**Notifikasi In-App** — menggunakan Laravel's built-in notification system (`DatabaseNotification`). Tidak perlu model/tabel custom. Cukup `php artisan notifications:table` dan buat Notification classes (e.g. `NewScoreNotification`, `InvoiceDueNotification`). Data notifikasi disimpan di tabel `notifications` bawaan Laravel.

Pengumuman bisa ditargetkan: seluruh sekolah, per tingkat, per kelas, atau per role. Notifikasi di-trigger otomatis dari events (absensi, nilai baru, tagihan, dll).

**Controllers:**

- `AnnouncementController` — CRUD pengumuman
- `NotificationController` — List, mark as read, mark all read

### Fase 7: Dashboard & Laporan

Tidak ada model baru selain `report_exports`. Fase ini fokus pada query/aggregate dan UI.

**`report_exports`** — Export Laporan

- id, team_id, user_id (FK → users), type (enum: rapor/keuangan/absensi/rekap), parameters (json), file_path, status (enum: pending/processing/completed/failed), timestamps

**Dashboard per role:**

| Role        | Dashboard Content                                                                             |
| ----------- | --------------------------------------------------------------------------------------------- |
| Owner/Admin | Overview: jumlah siswa, guru, kelas. Kehadiran hari ini. Status keuangan. Pengumuman terbaru. |
| Teacher     | Kelas yang diampu. Absensi hari ini. Progress input nilai. Jadwal hari ini.                   |
| Student     | Jadwal hari ini. Nilai terbaru. Rekap kehadiran. Tagihan pending.                             |
| Parent      | Per-anak: nilai, kehadiran, tagihan. Multi-anak support.                                      |

**Export formats:** PDF (rapor, bukti bayar), Excel (rekap data).

## Data Model Summary

Total model baru: **20 model** + Laravel built-in notifications (di luar model existing yang di-extend)

| Fase | Models                                                                                                       |
| ---- | ------------------------------------------------------------------------------------------------------------ |
| 1    | pages, posts, galleries, gallery_images                                                                      |
| 2    | academic_years, semesters, grades, classrooms, subjects, student_enrollments, teacher_assignments, guardians |
| 3    | time_slots, schedules, attendances, attendance_records                                                       |
| 4    | assessment_categories, assessments, scores, report_cards                                                     |
| 5    | fee_types, invoices, payments                                                                                |
| 6    | announcements (notifications via Laravel built-in)                                                           |
| 7    | report_exports                                                                                               |

Semua model baru di-scope ke `team_id` untuk multi-tenant isolation. Notifications menggunakan `notifiable_id` (user) + custom `data.team_id` untuk scoping.

## Testing Strategy

- Setiap model baru harus punya factory + seeder
- Setiap controller harus punya feature test (Pest)
- Test authorization: pastikan setiap role hanya bisa akses sesuai permission matrix
- Test multi-tenancy: pastikan data sekolah A tidak bocor ke sekolah B
- Test CMS: custom domain resolution, fallback path routing

## Tech Stack (No Changes)

Stack tetap: Laravel 13, React 19, Inertia.js v3, TypeScript, Tailwind CSS v4, SQLite (dev), PostgreSQL (production/Laravel Cloud). Tidak ada dependency baru yang signifikan kecuali:

- Rich text editor untuk CMS content (TBD — kemungkinan Tiptap via React)
- PDF generation (kemungkinan `barryvdh/laravel-dompdf` atau `spatie/laravel-pdf`)
- Excel export (kemungkinan `maatwebsite/excel` atau `openspout/openspout`)

Keputusan library spesifik akan diambil pada fase yang relevan.
