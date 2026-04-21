<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

use function Pest\Laravel\actingAs;

beforeEach(function () {
    $this->team = Team::factory()->create();
    $this->admin = User::factory()->create();
    $this->team->members()->attach($this->admin->id, ['role' => TeamRole::Admin->value]);
    $this->admin->update(['current_team_id' => $this->team->id]);
    $this->admin = $this->admin->fresh();
});

it('returns paginated students', function () {
    $students = User::factory()->count(20)->create();
    foreach ($students as $student) {
        $this->team->members()->attach($student->id, ['role' => TeamRole::Student->value]);
    }

    actingAs($this->admin)
        ->get(route('students.index', ['current_team' => $this->team->slug]))
        ->assertInertia(fn ($page) => $page
            ->component('students/index')
            ->has('students.data')
            ->has('students.current_page')
            ->has('students.last_page')
            ->has('students.total')
            ->where('students.per_page', 15)
        );
});

it('filters students by search query', function () {
    $budi = User::factory()->create(['name' => 'Budi Santoso']);
    $sari = User::factory()->create(['name' => 'Sari Dewi']);
    $this->team->members()->attach($budi->id, ['role' => TeamRole::Student->value]);
    $this->team->members()->attach($sari->id, ['role' => TeamRole::Student->value]);

    actingAs($this->admin)
        ->get(route('students.index', ['current_team' => $this->team->slug, 'search' => 'Budi']))
        ->assertInertia(fn ($page) => $page
            ->component('students/index')
            ->where('students.total', 1)
            ->where('students.data.0.name', 'Budi Santoso')
        );
});
