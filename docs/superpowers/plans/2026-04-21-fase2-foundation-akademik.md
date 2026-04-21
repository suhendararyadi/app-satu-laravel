# Fase 2: Foundation Akademik — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun fondasi akademik SmartSchool: tahun ajaran, semester, tingkat, kelas, mata pelajaran, enrollment siswa, penugasan guru, dan wali siswa.

**Architecture:** Multi-tenant (team-scoped), semua model dibatasi oleh `team_id`. Route group baru `academic.*` di bawah `EnsureTeamMembership:admin`. Frontend menggunakan Inertia React pages di `resources/js/pages/academic/`.

**Tech Stack:** Laravel 13 + Pest + Inertia.js v3 + React 19 + TypeScript + Tailwind CSS v4 + shadcn/ui + Wayfinder

**Design Spec:** `docs/superpowers/specs/2026-04-21-fase2-foundation-akademik-design.md`

---

## Plan Parts

This plan is split into 3 part files for manageability. Execute in order:

| Part | File                                                                 | Tasks | Scope                                                                  |
| ---- | -------------------------------------------------------------------- | ----- | ---------------------------------------------------------------------- |
| 1    | [part-1-backend-setup.md](./2026-04-21-fase2-part1-backend-setup.md) | 1–5   | TeamRole, TypeScript, Migrations, Models, Routes                       |
| 2    | [part-2-controllers.md](./2026-04-21-fase2-part2-controllers.md)     | 6–10  | AcademicYear, Grade, Subject, Classroom, TeacherAssignment controllers |
| 3    | [part-3-frontend.md](./2026-04-21-fase2-part3-frontend.md)           | 11–17 | React pages, Sidebar update, CI check                                  |

---

## Task Summary

- [ ] **Task 1** — Extend TeamRole Enum (add Teacher/Student/Parent, update 8 test files)
- [ ] **Task 2** — Create `resources/js/types/academic.ts` (8 TypeScript interfaces)
- [ ] **Task 3** — Database Migrations (8 tables: academic_years, semesters, grades, subjects, classrooms, student_enrollments, teacher_assignments, guardians)
- [ ] **Task 4** — Models + Factories + GuardianRelationship Enum + Team relations
- [ ] **Task 5** — Routes (`routes/academic.php` + update `routes/web.php`)
- [ ] **Task 6** — AcademicYearController (12 methods incl. semester CRUD) + tests
- [ ] **Task 7** — GradeController (5 methods) + tests
- [ ] **Task 8** — SubjectController (5 methods) + tests
- [ ] **Task 9** — ClassroomController (9 methods incl. enroll/unenroll) + tests
- [ ] **Task 10** — TeacherAssignmentController (3 methods) + tests
- [ ] **Task 11** — React pages: `academic/years/` (5 files)
- [ ] **Task 12** — React pages: `academic/grades/` (3 files)
- [ ] **Task 13** — React pages: `academic/subjects/` (3 files)
- [ ] **Task 14** — React pages: `academic/classrooms/` (4 files)
- [ ] **Task 15** — React pages: `academic/assignments/` (1 file)
- [ ] **Task 16** — Update `app-sidebar.tsx` (add academic nav group)
- [ ] **Task 17** — CI Check (pint + types:check + pest)

---

## Key Conventions (apply throughout all parts)

### PHP Controller pattern

```php
// Method WITH model binding — include string $currentTeam param:
public function edit(Request $request, string $currentTeam, AcademicYear $year): Response
{
    $team = $request->user()->currentTeam;
    abort_if($year->team_id !== $team->id, 403);
    return Inertia::render('academic/years/edit', ['year' => $year]);
}

// Method WITHOUT model binding (index, create, store):
public function index(Request $request): Response
{
    $team = $request->user()->currentTeam;
    // ...
}

// Store — use to_route() with model (URL defaults handle current_team):
return to_route('academic.years.edit', $year);

// Toast flash:
Inertia::flash('toast', ['type' => 'success', 'message' => 'Pesan sukses.']);
```

### Model pattern

```php
#[Fillable(['team_id', 'name', 'is_active'])]
class AcademicYear extends Model
{
    use HasFactory;
    protected function casts(): array { return ['is_active' => 'boolean']; }
    // Use 'boolean' not 'bool'
}
```

### Migration pattern

```php
$table->foreignId('team_id')->constrained()->cascadeOnDelete();
```

### Test pattern

```php
use RefreshDatabase, WithoutMiddleware; // use withoutVite() not WithoutMiddleware
// actually:
beforeEach(fn () => $this->withoutVite());
// Use assertInertia for Inertia responses
```

### React / Wayfinder pattern

```tsx
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
const { currentTeam } = usePage().props;
const teamSlug = currentTeam?.slug ?? '';

// Delete:
router.delete(
    AcademicYearController.destroy.url({ current_team: teamSlug, year: id }),
    {
        preserveScroll: true,
        onFinish: () => setConfirmOpen(false),
    },
);
```

### After any PHP change

```bash
./vendor/bin/pint --dirty --format agent
```
