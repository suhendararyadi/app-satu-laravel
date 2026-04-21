# Fase 3 Part 1: Backend Setup — TypeScript, Migrations, Models, Routes

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Setup backend foundation Fase 3: TypeScript types, 4 migrations, 4 models+factories, enum AttendanceStatus, dan routes.

**Parent plan:** `docs/superpowers/plans/2026-04-21-fase3-penjadwalan-absensi.md`

---

## Task 1: TypeScript Types

**Files:**
- Create: `resources/js/types/schedule.ts`

- [ ] **Step 1: Create type file**

```typescript
// resources/js/types/schedule.ts

export interface TimeSlot {
    id: number;
    team_id: number;
    name: string;
    start_time: string; // "07:00"
    end_time: string;   // "07:45"
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface Schedule {
    id: number;
    team_id: number;
    semester_id: number;
    classroom_id: number;
    subject_id: number;
    teacher_user_id: number;
    day_of_week: 'Senin' | 'Selasa' | 'Rabu' | 'Kamis' | 'Jumat' | 'Sabtu';
    time_slot_id: number;
    room: string | null;
    created_at: string;
    updated_at: string;
    // relations (optional, loaded when needed)
    semester?: unknown;
    classroom?: unknown;
    subject?: unknown;
    teacher?: unknown;
    time_slot?: unknown;
}

export interface Attendance {
    id: number;
    team_id: number;
    classroom_id: number;
    date: string; // "2026-04-21"
    subject_id: number | null;
    semester_id: number;
    recorded_by: number;
    created_at: string;
    updated_at: string;
    // relations
    classroom?: unknown;
    subject?: unknown;
    semester?: unknown;
    records?: AttendanceRecord[];
}

export interface AttendanceRecord {
    id: number;
    attendance_id: number;
    student_user_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes: string | null;
    created_at: string;
    updated_at: string;
    // relations
    user?: unknown;
}

export const DAYS_OF_WEEK = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'] as const;
export type DayOfWeek = typeof DAYS_OF_WEEK[number];

export const ATTENDANCE_STATUSES = ['hadir', 'sakit', 'izin', 'alpa'] as const;
export type AttendanceStatus = typeof ATTENDANCE_STATUSES[number];
```

- [ ] **Step 2: Verify file exists**

```bash
cat resources/js/types/schedule.ts
```

Expected: file tercetak tanpa error.

- [ ] **Step 3: Commit**

```bash
git add resources/js/types/schedule.ts
git commit -m "feat(fase3): add TypeScript types for schedule and attendance"
```

---

## Task 2: Database Migrations

**Files:**
- Create: `database/migrations/2026_04_21_100000_create_time_slots_table.php`
- Create: `database/migrations/2026_04_21_100001_create_schedules_table.php`
- Create: `database/migrations/2026_04_21_100002_create_attendances_table.php`
- Create: `database/migrations/2026_04_21_100003_create_attendance_records_table.php`

- [ ] **Step 1: Create time_slots migration**

```bash
php artisan make:migration create_time_slots_table --no-interaction
```

Edit file yang baru dibuat:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // "Jam 1", "Jam 2"
            $table->time('start_time');
            $table->time('end_time');
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_slots');
    }
};
```

- [ ] **Step 2: Create schedules migration**

```bash
php artisan make:migration create_schedules_table --no-interaction
```

Edit file yang baru dibuat:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('teacher_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('day_of_week', ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']);
            $table->foreignId('time_slot_id')->constrained()->cascadeOnDelete();
            $table->string('room', 100)->nullable();
            $table->timestamps();

            $table->unique(['semester_id', 'classroom_id', 'day_of_week', 'time_slot_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('schedules');
    }
};
```

- [ ] **Step 3: Create attendances migration**

```bash
php artisan make:migration create_attendances_table --no-interaction
```

Edit file yang baru dibuat:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->foreignId('subject_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('semester_id')->constrained('semesters')->cascadeOnDelete();
            $table->foreignId('recorded_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();

            // Satu absensi per kelas per hari per mapel (null = absensi harian)
            $table->unique(['classroom_id', 'date', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
```

- [ ] **Step 4: Create attendance_records migration**

```bash
php artisan make:migration create_attendance_records_table --no-interaction
```

Edit file yang baru dibuat:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('attendance_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('status', ['hadir', 'sakit', 'izin', 'alpa'])->default('hadir');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['attendance_id', 'student_user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
    }
};
```

- [ ] **Step 5: Run migrations**

```bash
php artisan migrate --no-interaction
```

Expected: 4 tabel baru dibuat tanpa error.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/
git commit -m "feat(fase3): add migrations for time_slots, schedules, attendances, attendance_records"
```

---

## Task 3: Models, Factories, dan Enum

**Files:**
- Create: `app/Enums/AttendanceStatus.php`
- Create: `app/Models/Schedule/TimeSlot.php`
- Create: `app/Models/Schedule/Schedule.php`
- Create: `app/Models/Schedule/Attendance.php`
- Create: `app/Models/Schedule/AttendanceRecord.php`
- Create: `database/factories/Schedule/TimeSlotFactory.php`
- Create: `database/factories/Schedule/ScheduleFactory.php`
- Create: `database/factories/Schedule/AttendanceFactory.php`
- Create: `database/factories/Schedule/AttendanceRecordFactory.php`

- [ ] **Step 1: Create AttendanceStatus enum**

```bash
php artisan make:enum AttendanceStatus --no-interaction
```

```php
<?php

namespace App\Enums;

enum AttendanceStatus: string
{
    case Hadir = 'hadir';
    case Sakit = 'sakit';
    case Izin = 'izin';
    case Alpa = 'alpa';
}
```

- [ ] **Step 2: Create TimeSlot model**

```bash
php artisan make:model Schedule/TimeSlot --no-interaction
```

```php
<?php

namespace App\Models\Schedule;

use App\Models\Team;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'name', 'start_time', 'end_time', 'sort_order'])]
class TimeSlot extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }
}
```

- [ ] **Step 3: Create Schedule model**

```bash
php artisan make:model Schedule/Schedule --no-interaction
```

```php
<?php

namespace App\Models\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['team_id', 'semester_id', 'classroom_id', 'subject_id', 'teacher_user_id', 'day_of_week', 'time_slot_id', 'room'])]
class Schedule extends Model
{
    use HasFactory;

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_user_id');
    }

    public function timeSlot(): BelongsTo
    {
        return $this->belongsTo(TimeSlot::class);
    }
}
```

- [ ] **Step 4: Create Attendance model**

```bash
php artisan make:model Schedule/Attendance --no-interaction
```

```php
<?php

namespace App\Models\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['team_id', 'classroom_id', 'date', 'subject_id', 'semester_id', 'recorded_by'])]
class Attendance extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['date' => 'date'];
    }

    public function team(): BelongsTo
    {
        return $this->belongsTo(Team::class);
    }

    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class);
    }

    public function semester(): BelongsTo
    {
        return $this->belongsTo(Semester::class);
    }

    public function recorder(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function records(): HasMany
    {
        return $this->hasMany(AttendanceRecord::class);
    }
}
```

- [ ] **Step 5: Create AttendanceRecord model**

```bash
php artisan make:model Schedule/AttendanceRecord --no-interaction
```

```php
<?php

namespace App\Models\Schedule;

use App\Enums\AttendanceStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['attendance_id', 'student_user_id', 'status', 'notes'])]
class AttendanceRecord extends Model
{
    use HasFactory;

    protected function casts(): array
    {
        return ['status' => AttendanceStatus::class];
    }

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_user_id');
    }
}
```

- [ ] **Step 6: Create TimeSlotFactory**

```bash
php artisan make:factory Schedule/TimeSlotFactory --model=Schedule/TimeSlot --no-interaction
```

```php
<?php

namespace Database\Factories\Schedule;

use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TimeSlot>
 */
class TimeSlotFactory extends Factory
{
    protected $model = TimeSlot::class;

    public function definition(): array
    {
        static $order = 1;

        return [
            'team_id' => Team::factory(),
            'name' => 'Jam '.$order++,
            'start_time' => fake()->time('H:i'),
            'end_time' => fake()->time('H:i'),
            'sort_order' => $order,
        ];
    }
}
```

- [ ] **Step 7: Create ScheduleFactory**

```bash
php artisan make:factory Schedule/ScheduleFactory --model=Schedule/Schedule --no-interaction
```

```php
<?php

namespace Database\Factories\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Academic\Subject;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Schedule>
 */
class ScheduleFactory extends Factory
{
    protected $model = Schedule::class;

    public function definition(): array
    {
        $team = Team::factory()->create();

        return [
            'team_id' => $team->id,
            'semester_id' => Semester::factory(),
            'classroom_id' => Classroom::factory(),
            'subject_id' => Subject::factory(),
            'teacher_user_id' => User::factory(),
            'day_of_week' => fake()->randomElement(['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu']),
            'time_slot_id' => TimeSlot::factory()->for($team),
            'room' => fake()->optional()->word(),
        ];
    }
}
```

- [ ] **Step 8: Create AttendanceFactory**

```bash
php artisan make:factory Schedule/AttendanceFactory --model=Schedule/Attendance --no-interaction
```

```php
<?php

namespace Database\Factories\Schedule;

use App\Models\Academic\Classroom;
use App\Models\Academic\Semester;
use App\Models\Schedule\Attendance;
use App\Models\Team;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    public function definition(): array
    {
        return [
            'team_id' => Team::factory(),
            'classroom_id' => Classroom::factory(),
            'date' => fake()->dateTimeBetween('-1 month', 'now')->format('Y-m-d'),
            'subject_id' => null,
            'semester_id' => Semester::factory(),
            'recorded_by' => User::factory(),
        ];
    }
}
```

- [ ] **Step 9: Create AttendanceRecordFactory**

```bash
php artisan make:factory Schedule/AttendanceRecordFactory --model=Schedule/AttendanceRecord --no-interaction
```

```php
<?php

namespace Database\Factories\Schedule;

use App\Enums\AttendanceStatus;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AttendanceRecord>
 */
class AttendanceRecordFactory extends Factory
{
    protected $model = AttendanceRecord::class;

    public function definition(): array
    {
        return [
            'attendance_id' => Attendance::factory(),
            'student_user_id' => User::factory(),
            'status' => fake()->randomElement(AttendanceStatus::cases())->value,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
```

- [ ] **Step 10: Run pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 11: Verify factories work**

```bash
php artisan tinker --execute 'use App\Models\Schedule\TimeSlot; TimeSlot::factory()->make()->toArray();'
```

Expected: array TimeSlot tercetak tanpa error.

- [ ] **Step 12: Commit**

```bash
git add app/Enums/AttendanceStatus.php app/Models/Schedule/ database/factories/Schedule/
git commit -m "feat(fase3): add AttendanceStatus enum, models and factories for schedule module"
```

---

## Task 4: Routes

**Files:**
- Create: `routes/schedule.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Create routes/schedule.php**

```php
<?php

use App\Http\Controllers\Schedule\AttendanceController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Schedule\TimeSlotController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('schedule.')
    ->group(function () {
        // Jam Pelajaran
        Route::resource('schedule/time-slots', TimeSlotController::class)
            ->except(['show'])
            ->parameters(['time-slots' => 'timeSlot']);

        // Jadwal
        Route::resource('schedule/schedules', ScheduleController::class)
            ->except(['show']);

        // Absensi
        Route::get('attendance', [AttendanceController::class, 'index'])
            ->name('attendance.index');
        Route::get('attendance/create', [AttendanceController::class, 'create'])
            ->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])
            ->name('attendance.store');
        Route::get('attendance/{attendance}', [AttendanceController::class, 'show'])
            ->name('attendance.show');
        Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
            ->name('attendance.edit');
        Route::patch('attendance/{attendance}', [AttendanceController::class, 'update'])
            ->name('attendance.update');
    });
```

- [ ] **Step 2: Update routes/web.php — tambahkan require schedule.php**

Cari baris `require __DIR__.'/academic.php';` lalu tambahkan setelahnya:

```php
require __DIR__.'/schedule.php';
```

- [ ] **Step 3: Verify routes terdaftar**

```bash
php artisan route:list --name=schedule. --except-vendor
```

Expected: ~12 route `schedule.*` terdaftar.

- [ ] **Step 4: Run pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 5: Commit**

```bash
git add routes/schedule.php routes/web.php
git commit -m "feat(fase3): add schedule and attendance routes"
```
