# Fase 3 Part 2: Controllers + Tests

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Implementasi 3 controller (TimeSlot, Schedule, Attendance) beserta Form Requests dan feature tests Pest.

**Parent plan:** `docs/superpowers/plans/2026-04-21-fase3-penjadwalan-absensi.md`

**Prerequisite:** Part 1 selesai (migrations, models, routes sudah ada).

---

## Task 5: TimeSlotController + Tests

**Files:**
- Create: `app/Http/Controllers/Schedule/TimeSlotController.php`
- Create: `app/Http/Requests/Schedule/StoreTimeSlotRequest.php`
- Create: `app/Http/Requests/Schedule/UpdateTimeSlotRequest.php`
- Create: `tests/Feature/Schedule/TimeSlotControllerTest.php`

- [ ] **Step 1: Create Form Requests**

```bash
php artisan make:request Schedule/StoreTimeSlotRequest --no-interaction
php artisan make:request Schedule/UpdateTimeSlotRequest --no-interaction
```

`app/Http/Requests/Schedule/StoreTimeSlotRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

`app/Http/Requests/Schedule/UpdateTimeSlotRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateTimeSlotRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:50'],
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'sort_order' => ['required', 'integer', 'min:0'],
        ];
    }
}
```

- [ ] **Step 2: Create TimeSlotController**

```bash
php artisan make:controller Schedule/TimeSlotController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreTimeSlotRequest;
use App\Http\Requests\Schedule\UpdateTimeSlotRequest;
use App\Models\Schedule\TimeSlot;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class TimeSlotController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $timeSlots = $team->timeSlots()->orderBy('sort_order')->get();

        return Inertia::render('schedule/time-slots/index', ['timeSlots' => $timeSlots]);
    }

    public function create(): Response
    {
        return Inertia::render('schedule/time-slots/create');
    }

    public function store(StoreTimeSlotRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $timeSlot = $team->timeSlots()->create($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil dibuat.']);

        return to_route('schedule.time-slots.edit', $timeSlot);
    }

    public function edit(Request $request, string $currentTeam, TimeSlot $timeSlot): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);

        return Inertia::render('schedule/time-slots/edit', ['timeSlot' => $timeSlot]);
    }

    public function update(UpdateTimeSlotRequest $request, string $currentTeam, TimeSlot $timeSlot): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);
        $timeSlot->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil diperbarui.']);

        return to_route('schedule.time-slots.edit', $timeSlot);
    }

    public function destroy(Request $request, string $currentTeam, TimeSlot $timeSlot): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($timeSlot->team_id !== $team->id, 403);
        $timeSlot->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jam pelajaran berhasil dihapus.']);

        return to_route('schedule.time-slots.index');
    }
}
```

- [ ] **Step 3: Add timeSlots relation to Team model**

Buka `app/Models/Team.php`, tambahkan import dan relasi:

```php
use App\Models\Schedule\TimeSlot;

// di dalam class Team:
public function timeSlots(): HasMany
{
    return $this->hasMany(TimeSlot::class);
}
```

- [ ] **Step 4: Write tests**

```bash
php artisan make:test Schedule/TimeSlotControllerTest --pest --no-interaction
```

```php
<?php

use App\Enums\TeamRole;
use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeTimeSlotUser(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('lists time slots', function () {
    [$owner, $team] = makeTimeSlotUser();
    TimeSlot::factory()->count(3)->for($team)->create();

    $this->actingAs($owner)
        ->get(route('schedule.time-slots.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('schedule/time-slots/index')
            ->has('timeSlots', 3)
        );
});

it('shows create time slot form', function () {
    [$owner] = makeTimeSlotUser();

    $this->actingAs($owner)
        ->get(route('schedule.time-slots.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->component('schedule/time-slots/create'));
});

it('stores time slot', function () {
    [$owner, $team] = makeTimeSlotUser();

    $this->actingAs($owner)
        ->post(route('schedule.time-slots.store'), [
            'name' => 'Jam 1',
            'start_time' => '07:00',
            'end_time' => '07:45',
            'sort_order' => 1,
        ])
        ->assertRedirect();

    expect($team->timeSlots()->count())->toBe(1);
});

it('validates time slot store rules', function () {
    [$owner] = makeTimeSlotUser();

    $this->actingAs($owner)
        ->post(route('schedule.time-slots.store'), [
            'name' => '',
            'start_time' => 'invalid',
            'end_time' => '',
            'sort_order' => -1,
        ])
        ->assertSessionHasErrors(['name', 'start_time', 'end_time', 'sort_order']);
});

it('shows edit time slot form', function () {
    [$owner, $team] = makeTimeSlotUser();
    $timeSlot = TimeSlot::factory()->for($team)->create();

    $this->actingAs($owner)
        ->get(route('schedule.time-slots.edit', $timeSlot))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('schedule/time-slots/edit')
            ->has('timeSlot')
        );
});

it('updates time slot', function () {
    [$owner, $team] = makeTimeSlotUser();
    $timeSlot = TimeSlot::factory()->for($team)->create(['name' => 'Jam Lama']);

    $this->actingAs($owner)
        ->patch(route('schedule.time-slots.update', $timeSlot), [
            'name' => 'Jam Baru',
            'start_time' => '07:00',
            'end_time' => '07:45',
            'sort_order' => 1,
        ])
        ->assertRedirect();

    expect($timeSlot->fresh()->name)->toBe('Jam Baru');
});

it('deletes time slot', function () {
    [$owner, $team] = makeTimeSlotUser();
    $timeSlot = TimeSlot::factory()->for($team)->create();

    $this->actingAs($owner)
        ->delete(route('schedule.time-slots.destroy', $timeSlot))
        ->assertRedirect(route('schedule.time-slots.index'));

    expect($team->timeSlots()->count())->toBe(0);
});

it('returns 403 for time slot belonging to another team', function () {
    [$owner] = makeTimeSlotUser();
    $other = TimeSlot::factory()->create();

    $this->actingAs($owner)
        ->get(route('schedule.time-slots.edit', $other))
        ->assertForbidden();
});
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=TimeSlotControllerTest
```

Expected: semua passing.

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Schedule/TimeSlotController.php \
        app/Http/Requests/Schedule/ \
        app/Models/Team.php \
        tests/Feature/Schedule/TimeSlotControllerTest.php
git commit -m "feat(fase3): add TimeSlotController with tests"
```

---

## Task 6: ScheduleController + Tests

**Files:**
- Create: `app/Http/Controllers/Schedule/ScheduleController.php`
- Create: `app/Http/Requests/Schedule/StoreScheduleRequest.php`
- Create: `app/Http/Requests/Schedule/UpdateScheduleRequest.php`
- Create: `tests/Feature/Schedule/ScheduleControllerTest.php`

- [ ] **Step 1: Create Form Requests**

```bash
php artisan make:request Schedule/StoreScheduleRequest --no-interaction
php artisan make:request Schedule/UpdateScheduleRequest --no-interaction
```

`app/Http/Requests/Schedule/StoreScheduleRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'room' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

`app/Http/Requests/Schedule/UpdateScheduleRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'subject_id' => ['required', 'integer', 'exists:subjects,id'],
            'teacher_user_id' => ['required', 'integer', 'exists:users,id'],
            'day_of_week' => ['required', Rule::in(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'])],
            'time_slot_id' => ['required', 'integer', 'exists:time_slots,id'],
            'room' => ['nullable', 'string', 'max:100'],
        ];
    }
}
```

- [ ] **Step 2: Create ScheduleController**

```bash
php artisan make:controller Schedule/ScheduleController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreScheduleRequest;
use App\Http\Requests\Schedule\UpdateScheduleRequest;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ScheduleController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $schedules = Schedule::query()
            ->where('team_id', $team->id)
            ->with(['semester', 'classroom', 'subject', 'teacher', 'timeSlot'])
            ->orderBy('day_of_week')
            ->orderBy('time_slot_id')
            ->get();

        return Inertia::render('schedule/schedules/index', ['schedules' => $schedules]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('schedule/schedules/create', [
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'teachers' => User::whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))->get(['id', 'name']),
            'timeSlots' => TimeSlot::where('team_id', $team->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function store(StoreScheduleRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        $schedule = Schedule::create(array_merge($request->validated(), ['team_id' => $team->id]));
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil dibuat.']);

        return to_route('schedule.schedules.edit', $schedule);
    }

    public function edit(Request $request, string $currentTeam, Schedule $schedule): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);

        return Inertia::render('schedule/schedules/edit', [
            'schedule' => $schedule,
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
            'teachers' => User::whereHas('teams', fn ($q) => $q->where('teams.id', $team->id))->get(['id', 'name']),
            'timeSlots' => TimeSlot::where('team_id', $team->id)->orderBy('sort_order')->get(),
        ]);
    }

    public function update(UpdateScheduleRequest $request, string $currentTeam, Schedule $schedule): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);
        $schedule->update($request->validated());
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil diperbarui.']);

        return to_route('schedule.schedules.edit', $schedule);
    }

    public function destroy(Request $request, string $currentTeam, Schedule $schedule): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($schedule->team_id !== $team->id, 403);
        $schedule->delete();
        Inertia::flash('toast', ['type' => 'success', 'message' => 'Jadwal berhasil dihapus.']);

        return to_route('schedule.schedules.index');
    }
}
```

- [ ] **Step 3: Write tests**

```bash
php artisan make:test Schedule/ScheduleControllerTest --pest --no-interaction
```

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeScheduleContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $year = AcademicYear::factory()->for($team)->create(['is_active' => true]);
    $semester = Semester::factory()->for($year, 'academicYear')->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $subject = Subject::factory()->for($team)->create();
    $timeSlot = TimeSlot::factory()->for($team)->create();

    return [$owner, $team, $semester, $classroom, $subject, $timeSlot];
}

it('lists schedules', function () {
    [$owner, $team, $semester, $classroom, $subject, $timeSlot] = makeScheduleContext();

    Schedule::factory()->create([
        'team_id' => $team->id,
        'semester_id' => $semester->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_user_id' => $owner->id,
        'day_of_week' => 'Senin',
        'time_slot_id' => $timeSlot->id,
    ]);

    $this->actingAs($owner)
        ->get(route('schedule.schedules.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('schedule/schedules/index')
            ->has('schedules', 1)
        );
});

it('shows create schedule form', function () {
    [$owner] = makeScheduleContext();

    $this->actingAs($owner)
        ->get(route('schedule.schedules.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('schedule/schedules/create')
            ->has('semesters')
            ->has('classrooms')
            ->has('subjects')
            ->has('teachers')
            ->has('timeSlots')
        );
});

it('stores schedule', function () {
    [$owner, $team, $semester, $classroom, $subject, $timeSlot] = makeScheduleContext();

    $this->actingAs($owner)
        ->post(route('schedule.schedules.store'), [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $owner->id,
            'day_of_week' => 'Senin',
            'time_slot_id' => $timeSlot->id,
            'room' => 'R101',
        ])
        ->assertRedirect();

    expect(Schedule::where('team_id', $team->id)->count())->toBe(1);
});

it('validates schedule store rules', function () {
    [$owner] = makeScheduleContext();

    $this->actingAs($owner)
        ->post(route('schedule.schedules.store'), [])
        ->assertSessionHasErrors(['semester_id', 'classroom_id', 'subject_id', 'teacher_user_id', 'day_of_week', 'time_slot_id']);
});

it('updates schedule', function () {
    [$owner, $team, $semester, $classroom, $subject, $timeSlot] = makeScheduleContext();
    $schedule = Schedule::factory()->create([
        'team_id' => $team->id,
        'semester_id' => $semester->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_user_id' => $owner->id,
        'day_of_week' => 'Senin',
        'time_slot_id' => $timeSlot->id,
        'room' => null,
    ]);

    $this->actingAs($owner)
        ->patch(route('schedule.schedules.update', $schedule), [
            'semester_id' => $semester->id,
            'classroom_id' => $classroom->id,
            'subject_id' => $subject->id,
            'teacher_user_id' => $owner->id,
            'day_of_week' => 'Selasa',
            'time_slot_id' => $timeSlot->id,
            'room' => 'R202',
        ])
        ->assertRedirect();

    expect($schedule->fresh()->day_of_week)->toBe('Selasa');
});

it('deletes schedule', function () {
    [$owner, $team, $semester, $classroom, $subject, $timeSlot] = makeScheduleContext();
    $schedule = Schedule::factory()->create([
        'team_id' => $team->id,
        'semester_id' => $semester->id,
        'classroom_id' => $classroom->id,
        'subject_id' => $subject->id,
        'teacher_user_id' => $owner->id,
        'day_of_week' => 'Senin',
        'time_slot_id' => $timeSlot->id,
    ]);

    $this->actingAs($owner)
        ->delete(route('schedule.schedules.destroy', $schedule))
        ->assertRedirect(route('schedule.schedules.index'));

    expect(Schedule::where('team_id', $team->id)->count())->toBe(0);
});

it('returns 403 for schedule belonging to another team', function () {
    [$owner] = makeScheduleContext();
    $other = Schedule::factory()->create();

    $this->actingAs($owner)
        ->get(route('schedule.schedules.edit', $other))
        ->assertForbidden();
});
```

- [ ] **Step 4: Run tests**

```bash
php artisan test --compact --filter=ScheduleControllerTest
```

Expected: semua passing.

- [ ] **Step 5: Run pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 6: Commit**

```bash
git add app/Http/Controllers/Schedule/ScheduleController.php \
        app/Http/Requests/Schedule/StoreScheduleRequest.php \
        app/Http/Requests/Schedule/UpdateScheduleRequest.php \
        tests/Feature/Schedule/ScheduleControllerTest.php
git commit -m "feat(fase3): add ScheduleController with tests"
```

---

## Task 7: AttendanceController + Tests

**Files:**
- Create: `app/Http/Controllers/Schedule/AttendanceController.php`
- Create: `app/Http/Requests/Schedule/StoreAttendanceRequest.php`
- Create: `app/Http/Requests/Schedule/UpdateAttendanceRequest.php`
- Create: `tests/Feature/Schedule/AttendanceControllerTest.php`

- [ ] **Step 1: Create Form Requests**

```bash
php artisan make:request Schedule/StoreAttendanceRequest --no-interaction
php artisan make:request Schedule/UpdateAttendanceRequest --no-interaction
```

`app/Http/Requests/Schedule/StoreAttendanceRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'classroom_id' => ['required', 'integer', 'exists:classrooms,id'],
            'date' => ['required', 'date'],
            'subject_id' => ['nullable', 'integer', 'exists:subjects,id'],
            'semester_id' => ['required', 'integer', 'exists:semesters,id'],
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

`app/Http/Requests/Schedule/UpdateAttendanceRequest.php`:

```php
<?php

namespace App\Http\Requests\Schedule;

use App\Enums\AttendanceStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateAttendanceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'records' => ['required', 'array', 'min:1'],
            'records.*.student_user_id' => ['required', 'integer', 'exists:users,id'],
            'records.*.status' => ['required', Rule::enum(AttendanceStatus::class)],
            'records.*.notes' => ['nullable', 'string', 'max:255'],
        ];
    }
}
```

- [ ] **Step 2: Create AttendanceController**

```bash
php artisan make:controller Schedule/AttendanceController --no-interaction
```

```php
<?php

namespace App\Http\Controllers\Schedule;

use App\Http\Controllers\Controller;
use App\Http\Requests\Schedule\StoreAttendanceRequest;
use App\Http\Requests\Schedule\UpdateAttendanceRequest;
use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Academic\StudentEnrollment;
use App\Models\Schedule\Attendance;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AttendanceController extends Controller
{
    public function index(Request $request): Response
    {
        $team = $request->user()->currentTeam;
        $attendances = Attendance::query()
            ->where('team_id', $team->id)
            ->with(['classroom', 'subject', 'semester'])
            ->orderByDesc('date')
            ->paginate(20);

        return Inertia::render('attendance/index', ['attendances' => $attendances]);
    }

    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('attendance/create', [
            'classrooms' => Classroom::where('team_id', $team->id)->get(),
            'semesters' => Semester::whereHas('academicYear', fn ($q) => $q->where('team_id', $team->id))->get(),
            'subjects' => Subject::where('team_id', $team->id)->get(),
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        $attendance = Attendance::create([
            'team_id' => $team->id,
            'classroom_id' => $request->classroom_id,
            'date' => $request->date,
            'subject_id' => $request->subject_id,
            'semester_id' => $request->semester_id,
            'recorded_by' => $request->user()->id,
        ]);

        foreach ($request->records as $record) {
            $attendance->records()->create($record);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil disimpan.']);

        return to_route('schedule.attendance.show', $attendance);
    }

    public function show(Request $request, string $currentTeam, Attendance $attendance): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        $attendance->load(['classroom', 'subject', 'semester', 'records.student']);

        return Inertia::render('attendance/show', ['attendance' => $attendance]);
    }

    public function edit(Request $request, string $currentTeam, Attendance $attendance): Response
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        $attendance->load(['records']);

        // Load enrolled students for this classroom
        $students = StudentEnrollment::where('classroom_id', $attendance->classroom_id)
            ->with('student:id,name')
            ->get()
            ->map(fn ($e) => ['id' => $e->student_user_id, 'name' => $e->student->name ?? '']);

        return Inertia::render('attendance/edit', [
            'attendance' => $attendance,
            'students' => $students,
        ]);
    }

    public function update(UpdateAttendanceRequest $request, string $currentTeam, Attendance $attendance): RedirectResponse
    {
        $team = $request->user()->currentTeam;
        abort_if($attendance->team_id !== $team->id, 403);

        // Delete old records and re-insert
        $attendance->records()->delete();
        foreach ($request->records as $record) {
            $attendance->records()->create($record);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => 'Absensi berhasil diperbarui.']);

        return to_route('schedule.attendance.show', $attendance);
    }
}
```

- [ ] **Step 3: Add student relation to StudentEnrollment model**

Buka `app/Models/Academic/StudentEnrollment.php`, pastikan ada relasi `student()`:

```php
public function student(): BelongsTo
{
    return $this->belongsTo(User::class, 'student_user_id');
}
```

- [ ] **Step 4: Write tests**

```bash
php artisan make:test Schedule/AttendanceControllerTest --pest --no-interaction
```

```php
<?php

use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Team;
use App\Models\User;

beforeEach(fn () => $this->withoutVite());

function makeAttendanceContext(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    $year = AcademicYear::factory()->for($team)->create(['is_active' => true]);
    $semester = Semester::factory()->for($year, 'academicYear')->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()->for($team)->for($year, 'academicYear')->for($grade)->create();
    $subject = Subject::factory()->for($team)->create();

    $student = User::factory()->create();
    StudentEnrollment::factory()->create([
        'classroom_id' => $classroom->id,
        'student_user_id' => $student->id,
    ]);

    return [$owner, $team, $semester, $classroom, $subject, $student];
}

it('lists attendances', function () {
    [$owner, $team, $semester, $classroom] = makeAttendanceContext();

    Attendance::factory()->create([
        'team_id' => $team->id,
        'classroom_id' => $classroom->id,
        'semester_id' => $semester->id,
        'recorded_by' => $owner->id,
        'date' => '2026-04-21',
    ]);

    $this->actingAs($owner)
        ->get(route('schedule.attendance.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/index')
            ->has('attendances')
        );
});

it('shows create attendance form', function () {
    [$owner] = makeAttendanceContext();

    $this->actingAs($owner)
        ->get(route('schedule.attendance.create'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/create')
            ->has('classrooms')
            ->has('semesters')
            ->has('subjects')
        );
});

it('stores attendance with records', function () {
    [$owner, $team, $semester, $classroom, $subject, $student] = makeAttendanceContext();

    $this->actingAs($owner)
        ->post(route('schedule.attendance.store'), [
            'classroom_id' => $classroom->id,
            'date' => '2026-04-21',
            'subject_id' => null,
            'semester_id' => $semester->id,
            'records' => [
                ['student_user_id' => $student->id, 'status' => 'hadir', 'notes' => null],
            ],
        ])
        ->assertRedirect();

    expect(Attendance::where('team_id', $team->id)->count())->toBe(1);
    expect(AttendanceRecord::count())->toBe(1);
});

it('validates attendance store rules', function () {
    [$owner] = makeAttendanceContext();

    $this->actingAs($owner)
        ->post(route('schedule.attendance.store'), [])
        ->assertSessionHasErrors(['classroom_id', 'date', 'semester_id', 'records']);
});

it('shows attendance detail', function () {
    [$owner, $team, $semester, $classroom] = makeAttendanceContext();
    $attendance = Attendance::factory()->create([
        'team_id' => $team->id,
        'classroom_id' => $classroom->id,
        'semester_id' => $semester->id,
        'recorded_by' => $owner->id,
        'date' => '2026-04-21',
    ]);

    $this->actingAs($owner)
        ->get(route('schedule.attendance.show', $attendance))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('attendance/show')
            ->has('attendance')
        );
});

it('returns 403 for attendance belonging to another team', function () {
    [$owner] = makeAttendanceContext();
    $other = Attendance::factory()->create();

    $this->actingAs($owner)
        ->get(route('schedule.attendance.show', $other))
        ->assertForbidden();
});
```

- [ ] **Step 5: Run tests**

```bash
php artisan test --compact --filter=AttendanceControllerTest
```

Expected: semua passing.

- [ ] **Step 6: Run pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 7: Commit**

```bash
git add app/Http/Controllers/Schedule/AttendanceController.php \
        app/Http/Requests/Schedule/StoreAttendanceRequest.php \
        app/Http/Requests/Schedule/UpdateAttendanceRequest.php \
        tests/Feature/Schedule/AttendanceControllerTest.php
git commit -m "feat(fase3): add AttendanceController with tests"
```
