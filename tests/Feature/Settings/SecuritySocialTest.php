<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('security page includes connectedProviders and hasPassword props', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'g-1',
    ]);

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'));

    $response->assertOk();
    $response->assertInertia(fn ($page) => $page
        ->has('connectedProviders')
        ->has('hasPassword')
        ->where('connectedProviders', ['google'])
        ->where('hasPassword', true)
    );
});

test('security page hasPassword is false for social-only user', function () {
    $user = User::factory()->withoutPassword()->create();

    $response = $this->actingAs($user)
        ->withSession(['auth.password_confirmed_at' => time()])
        ->get(route('security.edit'));

    $response->assertInertia(fn ($page) => $page
        ->where('hasPassword', false)
    );
});
