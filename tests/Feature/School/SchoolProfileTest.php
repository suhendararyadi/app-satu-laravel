<?php

use App\Enums\TeamRole;
use App\Models\Team;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('owner can access school profile edit page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get(route('cms.school.profile.edit'))
        ->assertOk();
});

test('admin can access school profile edit page', function () {
    $team = Team::factory()->create();
    $admin = User::factory()->create();
    $team->members()->attach($admin, ['role' => TeamRole::Admin->value]);
    $admin->switchTeam($team);

    $this->actingAs($admin)
        ->get(route('cms.school.profile.edit'))
        ->assertOk();
});

test('member gets 403 on school profile edit page', function () {
    $team = Team::factory()->create();
    $member = User::factory()->create();
    $team->members()->attach($member, ['role' => TeamRole::Member->value]);
    $member->switchTeam($team);

    $this->actingAs($member)
        ->get(route('cms.school.profile.edit'))
        ->assertForbidden();
});

test('guest is redirected to login from school profile edit page', function () {
    $user = User::factory()->create();

    $this->get(route('cms.school.profile.edit'))
        ->assertRedirect(route('login'));
});

test('owner can update school profile', function () {
    $user = User::factory()->create();
    $team = $user->currentTeam;

    $this->actingAs($user)
        ->patch(route('cms.school.profile.update'), [
            'city' => 'Jakarta',
            'province' => 'DKI Jakarta',
            'phone' => '021-1234567',
        ])
        ->assertRedirect(route('cms.school.profile.edit'));

    $this->assertDatabaseHas('teams', [
        'id' => $team->id,
        'city' => 'Jakarta',
        'province' => 'DKI Jakarta',
        'phone' => '021-1234567',
    ]);
});

test('logo is stored and logo_path updated when uploading logo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    $logo = UploadedFile::fake()->image('logo.jpg');

    $this->actingAs($user)
        ->patch(route('cms.school.profile.update'), [
            'logo' => $logo,
        ])
        ->assertRedirect(route('cms.school.profile.edit'));

    $team->refresh();

    expect($team->logo_path)->not->toBeNull();
    Storage::disk('public')->assertExists($team->logo_path);
});

test('old logo is deleted when uploading new logo', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $team = $user->currentTeam;

    // Store initial logo
    $oldPath = 'logos/old-logo.jpg';
    Storage::disk('public')->put($oldPath, 'fake content');
    $team->update(['logo_path' => $oldPath]);

    $newLogo = UploadedFile::fake()->image('new-logo.jpg');

    $this->actingAs($user)
        ->patch(route('cms.school.profile.update'), [
            'logo' => $newLogo,
        ])
        ->assertRedirect(route('cms.school.profile.edit'));

    Storage::disk('public')->assertMissing($oldPath);

    $team->refresh();
    expect($team->logo_path)->not->toBe($oldPath);
});
