# Fase 4: Penilaian & Rapor — Design Specification

**Date:** 2026-04-22
**Status:** Approved
**Phase:** 4 of 7 — SmartSchool

## Overview

Fase 4 menambahkan sistem penilaian akademik ke SmartSchool: kategori penilaian dengan bobot persentase, assessment (ujian/tugas) per kelas, input nilai batch per assessment, dan rapor per siswa per semester yang dihitung on-the-fly dari nilai yang ada.

## Key Decisions

| Keputusan | Pilihan |
|-----------|---------|
| Sistem nilai | Weighted average berbasis kategori (bobot % per kategori) |
| Scope kategori | Per-sekolah (team) — satu set berlaku semua mapel |
| Input nilai | Batch per assessment — semua siswa sekaligus dalam satu form |
| Rapor | Nilai dihitung on-the-fly saat render, tidak di-snapshot ke DB |
| Generate rapor | Admin/Owner saja; Teacher bisa view |
| Access assessment | Semua Teacher + Admin/Owner (tanpa pembatasan per teacher_assignment) |

## Architecture

### Nilai Akhir Computation

Nilai akhir per mapel per semester dihitung saat halaman rapor dibuka:

```
nilai_akhir_mapel = Σ (rata_rata_nilai_dalam_kategori × bobot_kategori / 100)
```

Contoh dengan 4 kategori (Tugas 20%, UH 30%, UTS 20%, UAS 30%):
- Rata-rata Tugas: 85 → 85 × 0.20 = 17.0
- Rata-rata UH: 78 → 78 × 0.30 = 23.4
- UTS: 80 → 80 × 0.20 = 16.0
- UAS: 82 → 82 × 0.30 = 24.6
- **Nilai Akhir: 81.0**

Jika belum ada nilai dalam suatu kategori, kategori tersebut dianggap 0 untuk mapel itu.

## Data Model

### `assessment_categories`

```
assessment_categories
├── id
├── team_id (FK → teams, cascade delete)
├── name           — "Ulangan Harian", "UTS", "UAS", "Tugas"
├── weight         — decimal(5,2), percentage (0.00–100.00)
├── timestamps
```

- Unique: tidak ada unique constraint (sekolah bisa punya dua kategori dengan nama sama jika mau)
- Validasi application layer: total `weight` per `team_id` harus = 100 saat ada assessment yang aktif
- Tidak bisa dihapus jika sudah ada assessment yang menggunakan kategori ini

### `assessments`

```
assessments
├── id
├── team_id        — FK → teams (untuk multi-tenant scoping)
├── classroom_id   — FK → classrooms (cascade delete)
├── subject_id     — FK → subjects (cascade delete)
├── semester_id    — FK → semesters (cascade delete)
├── assessment_category_id — FK → assessment_categories (restrict delete)
├── title          — "UTS Matematika Kelas X IPA-1"
├── max_score      — decimal(8,2), default 100
├── date           — date
├── teacher_user_id — FK → users (siapa yang membuat)
├── timestamps
```

- Tidak ada unique constraint — guru bisa buat multiple assessment dalam kategori yang sama

### `scores`

```
scores
├── id
├── assessment_id  — FK → assessments (cascade delete)
├── student_user_id — FK → users (cascade delete)
├── score          — decimal(8,2), nullable (null = belum dinilai)
├── notes          — text, nullable
├── timestamps
```

- Unique: `(assessment_id, student_user_id)`
- `score` nullable agar bisa "pre-populate" semua siswa di kelas saat assessment dibuat,
  dengan nilai null = belum dinilai. Score tidak bisa melebihi `assessment.max_score`.

### `report_cards`

```
report_cards
├── id
├── team_id        — FK → teams
├── semester_id    — FK → semesters
├── classroom_id   — FK → classrooms
├── student_user_id — FK → users
├── generated_by   — FK → users (siapa yang generate)
├── homeroom_notes — text, nullable (catatan wali kelas)
├── generated_at   — timestamp
├── timestamps
```

- Unique: `(semester_id, student_user_id)` — satu rapor per siswa per semester
- Nilai aktual **tidak** disimpan di sini — dihitung on-the-fly dari `scores`
- Tabel ini hanya menyimpan metadata + catatan wali kelas

## Controllers & Routes

Semua routes di bawah prefix `/{current_team}`, middleware `auth + verified + EnsureTeamMembership:admin,teacher`.

### `AssessmentCategoryController` → `academic.assessment-categories.*`

| Method | Route | Action |
|--------|-------|--------|
| GET | `academic/assessment-categories` | `index` — list + total bobot |
| GET | `academic/assessment-categories/create` | `create` |
| POST | `academic/assessment-categories` | `store` |
| GET | `academic/assessment-categories/{category}/edit` | `edit` |
| PATCH | `academic/assessment-categories/{category}` | `update` |
| DELETE | `academic/assessment-categories/{category}` | `destroy` |

Akses: Admin/Owner only (teacher read-only untuk lihat bobot saja jika diperlukan).

### `AssessmentController` → `academic.assessments.*`

| Method | Route | Action |
|--------|-------|--------|
| GET | `academic/assessments` | `index` — filter by classroom/subject/semester |
| GET | `academic/assessments/create` | `create` |
| POST | `academic/assessments` | `store` |
| GET | `academic/assessments/{assessment}` | `show` — + batch input nilai |
| GET | `academic/assessments/{assessment}/edit` | `edit` |
| PATCH | `academic/assessments/{assessment}` | `update` |
| DELETE | `academic/assessments/{assessment}` | `destroy` |
| POST | `academic/assessments/{assessment}/scores` | `storeScores` — batch save nilai |

Akses: semua Teacher + Admin/Owner.

### `ReportCardController` → `academic.report-cards.*`

| Method | Route | Action |
|--------|-------|--------|
| GET | `academic/report-cards` | `index` — pilih classroom + semester → daftar siswa + status |
| GET | `academic/report-cards/{report_card}` | `show` — rapor lengkap satu siswa |
| POST | `academic/report-cards` | `store` — generate rapor (Admin/Owner only) |
| PATCH | `academic/report-cards/{report_card}` | `update` — edit homeroom_notes (Admin/Owner only) |

## Frontend Pages

Semua di `resources/js/pages/academic/`, mengikuti pola sibling modules.

### `academic/assessment-categories/index.tsx`

- Tabel: Nama, Bobot (%), Jumlah Assessment
- Badge total bobot: hijau jika = 100%, merah jika ≠ 100%
- Tombol Tambah Kategori, Edit, Hapus (disabled jika ada assessment)
- Warning jika total ≠ 100%: "Total bobot saat ini X%. Harus tepat 100% agar nilai akhir dapat dihitung."

### `academic/assessments/index.tsx`

- Filter: classroom (dropdown), semester (dropdown)
- Tabel: Judul, Kategori, Mapel, Kelas, Tanggal, Jumlah Nilai Terisi / Total Siswa
- Tombol Tambah, lihat detail (nama assessment jadi link ke show), Edit, Hapus

### `academic/assessments/create.tsx` & `edit.tsx`

- Form fields: Kelas (select), Mata Pelajaran (select, filtered by classroom), Semester (select),
  Kategori (select), Judul, Nilai Maksimal (number, default 100), Tanggal

### `academic/assessments/show.tsx`

- Header: judul assessment, info kelas/mapel/kategori/semester/tanggal
- Tabel batch input: kolom Nama Siswa | Score (input number 0–max_score) | Catatan (input text)
- Siswa diambil dari `student_enrollments` di classroom tersebut
- Tombol "Simpan Semua Nilai" → POST ke `storeScores`
- Pre-filled dengan nilai yang sudah ada (jika sudah pernah diisi)

### `academic/report-cards/index.tsx`

- Dua dropdown di atas: Kelas dan Semester
- Tabel siswa di kelas tersebut + kolom "Status Rapor": badge Sudah/Belum
- Klik baris → ke `show` rapor siswa tersebut
- Tombol "Generate Semua Rapor Kelas" (Admin only) — batch generate untuk yang belum ada

### `academic/report-cards/show.tsx`

- Header: nama siswa, kelas, semester, tanggal generate, nama generator
- Tabel nilai per mapel:
  - Kolom: Mata Pelajaran | nilai per kategori (satu kolom per kategori) | Nilai Akhir
  - Footer: rata-rata nilai akhir semua mapel
- Rekap kehadiran: 4 badge Hadir/Sakit/Izin/Alpa (reuse pola dari students/show)
- Catatan Wali Kelas (editable untuk Admin/Owner, read-only untuk Teacher/Siswa)
- Tombol "Generate/Update Rapor" (Admin/Owner only)

## Navigation

Tambah entry di sidebar dengan sub-item:
- Penilaian → Kategori Penilaian (`assessment-categories.index`)
- Penilaian → Daftar Penilaian (`assessments.index`)
- Penilaian → Rapor (`report-cards.index`)

## Testing Strategy

### `AssessmentCategoryControllerTest` (~8 tests)
- Admin dapat melihat daftar kategori
- Admin dapat membuat kategori baru
- Admin dapat mengupdate kategori
- Admin dapat menghapus kategori yang belum dipakai
- Kategori yang sudah dipakai assessment tidak bisa dihapus (422)
- Teacher tidak bisa akses (403)
- Total bobot ditampilkan dengan benar

### `AssessmentControllerTest` (~10 tests)
- Admin/Teacher dapat melihat daftar assessment
- Admin/Teacher dapat membuat assessment
- `show` menampilkan daftar siswa yang terdaftar di classroom
- `show` pre-fills nilai yang sudah ada
- `storeScores` menyimpan nilai batch (insert baru)
- `storeScores` meng-update nilai yang sudah ada (upsert)
- Score melebihi max_score ditolak (422)
- Assessment dapat dihapus (cascade scores)

### `ReportCardControllerTest` (~8 tests)
- `index` menampilkan daftar siswa + status rapor
- `show` menghitung nilai akhir dengan benar (weighted average)
- `show` menampilkan 0 untuk mapel tanpa nilai
- `store` membuat rapor baru (Admin/Owner)
- `store` mengembalikan 403 untuk Teacher
- `update` hanya mengubah `homeroom_notes`
- Rapor yang sudah ada bisa di-update (tidak duplikat)
- `show` menampilkan rekap kehadiran

## File Map

| Status | File |
|--------|------|
| Create | `database/migrations/*_create_assessment_categories_table.php` |
| Create | `database/migrations/*_create_assessments_table.php` |
| Create | `database/migrations/*_create_scores_table.php` |
| Create | `database/migrations/*_create_report_cards_table.php` |
| Create | `app/Models/Academic/AssessmentCategory.php` |
| Create | `app/Models/Academic/Assessment.php` |
| Create | `app/Models/Academic/Score.php` |
| Create | `app/Models/Academic/ReportCard.php` |
| Create | `database/factories/Academic/AssessmentCategoryFactory.php` |
| Create | `database/factories/Academic/AssessmentFactory.php` |
| Create | `database/factories/Academic/ScoreFactory.php` |
| Create | `database/factories/Academic/ReportCardFactory.php` |
| Create | `app/Http/Requests/Academic/StoreAssessmentCategoryRequest.php` |
| Create | `app/Http/Requests/Academic/UpdateAssessmentCategoryRequest.php` |
| Create | `app/Http/Requests/Academic/StoreAssessmentRequest.php` |
| Create | `app/Http/Requests/Academic/UpdateAssessmentRequest.php` |
| Create | `app/Http/Requests/Academic/StoreScoresRequest.php` |
| Create | `app/Http/Requests/Academic/StoreReportCardRequest.php` |
| Create | `app/Http/Requests/Academic/UpdateReportCardRequest.php` |
| Create | `app/Http/Controllers/Academic/AssessmentCategoryController.php` |
| Create | `app/Http/Controllers/Academic/AssessmentController.php` |
| Create | `app/Http/Controllers/Academic/ReportCardController.php` |
| Create | `routes/academic.php` (extend existing or new file) |
| Create | `resources/js/pages/academic/assessment-categories/index.tsx` |
| Create | `resources/js/pages/academic/assessments/index.tsx` |
| Create | `resources/js/pages/academic/assessments/create.tsx` |
| Create | `resources/js/pages/academic/assessments/edit.tsx` |
| Create | `resources/js/pages/academic/assessments/show.tsx` |
| Create | `resources/js/pages/academic/report-cards/index.tsx` |
| Create | `resources/js/pages/academic/report-cards/show.tsx` |
| Modify | Sidebar component — tambah nav entry Penilaian |
| Create | `tests/Feature/Academic/AssessmentCategoryControllerTest.php` |
| Create | `tests/Feature/Academic/AssessmentControllerTest.php` |
| Create | `tests/Feature/Academic/ReportCardControllerTest.php` |
