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
