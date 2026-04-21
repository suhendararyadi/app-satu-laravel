# Fase 3: Penjadwalan & Kehadiran — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Membangun modul penjadwalan pelajaran dan sistem absensi SmartSchool: jam pelajaran, jadwal per kelas/guru, input absensi harian, dan rekap kehadiran.

**Architecture:** Multi-tenant (team-scoped), semua model di-scope ke `team_id`. Route group baru `schedule.*` dan `attendance.*` di bawah `EnsureTeamMembership`. Frontend Inertia React pages di `resources/js/pages/schedule/` dan `resources/js/pages/attendance/`.

**Tech Stack:** Laravel 13 + Pest + Inertia.js v3 + React 19 + TypeScript + Tailwind CSS v4 + shadcn/ui + Wayfinder

**Design Spec:** `docs/superpowers/specs/2026-04-20-smartschool-design.md` (Fase 3 section)

---

## Plan Parts

Split menjadi 3 part file untuk manageability. Execute in order:

| Part | File | Tasks | Scope |
|------|------|-------|-------|
| 1 | [part1-backend-setup.md](./2026-04-21-fase3-part1-backend-setup.md) | 1–4 | TypeScript types, Migrations, Models+Factories, Routes |
| 2 | [part2-controllers.md](./2026-04-21-fase3-part2-controllers.md) | 5–7 | TimeSlot, Schedule, Attendance controllers + tests |
| 3 | [part3-frontend.md](./2026-04-21-fase3-part3-frontend.md) | 8–13 | React pages, Sidebar update, CI check |

---

## Task Summary

- [ ] **Task 1** — TypeScript types (`resources/js/types/schedule.ts`)
- [ ] **Task 2** — Database Migrations (4 tables: time_slots, schedules, attendances, attendance_records)
- [ ] **Task 3** — Models + Factories (TimeSlot, Schedule, Attendance, AttendanceRecord + enums)
- [ ] **Task 4** — Routes (`routes/schedule.php` + update `routes/web.php`)
- [ ] **Task 5** — TimeSlotController (5 methods) + tests
- [ ] **Task 6** — ScheduleController (7 methods: CRUD + view per kelas/guru) + tests
- [ ] **Task 7** — AttendanceController (6 methods: input, rekap, per-siswa) + tests
- [ ] **Task 8** — React pages: `schedule/time-slots/` (3 files: index, create, edit)
- [ ] **Task 9** — React pages: `schedule/schedules/` (3 files: index, create, edit)
- [ ] **Task 10** — React pages: `attendance/` (3 files: index, record, show)
- [ ] **Task 11** — Update `app-sidebar.tsx` (add Jadwal & Absensi nav group)
- [ ] **Task 12** — Wayfinder regenerate + CI Check (pint + types:check + lint + pest)

---

## Key Conventions (apply throughout all parts)

### PHP Controller pattern

```php
// Method WITH model binding — include string $currentTeam param:
public function edit(Request $request, string $currentTeam, Schedule $schedule): Response
{
    $team = $request->user()->currentTeam;
    abort_if($schedule->team_id !== $team->id, 403);
    return Inertia::render('schedule/schedules/edit', ['schedule' => $schedule]);
}

// Method WITHOUT model binding (index, create):
public function index(Request $request): Response
{
    $team = $request->user()->currentTeam;
    // ...
}

// Store redirect:
return to_route('schedule.schedules.index');

// Flash toast:
Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal disimpan.']);
```

### Model pattern

```php
#[Fillable(['team_id', 'name', 'start_time', 'end_time', 'sort_order'])]
class TimeSlot extends Model
{
    use HasFactory;
    protected function casts(): array
    {
        return ['start_time' => 'datetime:H:i', 'end_time' => 'datetime:H:i'];
    }
}
```

### Migration pattern

```php
$table->foreignId('team_id')->constrained()->cascadeOnDelete();
$table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
```

### Test pattern

```php
beforeEach(fn () => $this->withoutVite());

it('lists time slots for team', function () {
    $team = Team::factory()->create();
    $user = User::factory()->create();
    $team->members()->attach($user, ['role' => 'admin']);
    $user->update(['current_team_id' => $team->id]);

    TimeSlot::factory()->count(3)->for($team)->create();

    $this->actingAs($user)
        ->get(route('schedule.time-slots.index', $team->slug))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('schedule/time-slots/index')
            ->has('timeSlots', 3)
        );
});
```

### React / Wayfinder pattern

```tsx
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
const { currentTeam } = usePage().props;
const teamSlug = currentTeam?.slug ?? '';

<Link href={TimeSlotController.index.url(teamSlug)}>Jam Pelajaran</Link>
```

### After any PHP change

```bash
./vendor/bin/pint --dirty --format agent
```
