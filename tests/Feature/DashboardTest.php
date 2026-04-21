<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;

test('guests are redirected to the login page', function () {
    $user = User::factory()->create();

    $response = $this->get(route('dashboard'));
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the dashboard', function () {
    $user = User::factory()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->has('hasSchoolTeam')
        );
});

test('dashboard passes hasSchoolTeam false when user has no school team', function () {
    $user = User::factory()->create();

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', false)
        );
});

test('dashboard passes hasSchoolTeam true when user has a school team', function () {
    $user = User::factory()->create();
    $schoolTeam = Team::factory()->create(['is_personal' => false]);

    $schoolTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
        );
});
