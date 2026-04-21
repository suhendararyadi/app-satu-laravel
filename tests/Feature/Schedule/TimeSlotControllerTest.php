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
