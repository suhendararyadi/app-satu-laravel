<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('social account belongs to a user', function () {
    $user = User::factory()->create();
    $account = SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id-123',
    ]);

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($user->id);
});

test('social account is fillable with expected fields', function () {
    $user = User::factory()->create();
    $account = SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id-456',
        'token' => 'tok',
        'refresh_token' => 'ref',
        'token_expires_at' => now()->addHour(),
    ]);

    expect($account->provider)->toBe('google');
    expect($account->token)->toBe('tok');
});

test('user has many social accounts', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'g-1',
    ]);

    expect($user->socialAccounts)->toHaveCount(1);
});

test('user hasPassword returns true when password set', function () {
    $user = User::factory()->create();

    expect($user->hasPassword())->toBeTrue();
});

test('user hasPassword returns false when password is null', function () {
    $user = User::factory()->withoutPassword()->create();

    expect($user->hasPassword())->toBeFalse();
});

test('user connectedProviders returns list of provider strings', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'g-1',
    ]);

    expect($user->connectedProviders()->toArray())->toBe(['google']);
});
