<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function fakeSocialiteUser(
    string $id = 'google-id-123',
    string $email = 'social@example.com',
    string $name = 'Social User',
): object {
    return new class($id, $email, $name)
    {
        public string $token = 'fake-token';

        public ?string $refreshToken = null;

        public ?int $expiresIn = 3600;

        public function __construct(
            private string $id,
            private string $email,
            private string $name,
        ) {}

        public function getId(): string
        {
            return $this->id;
        }

        public function getEmail(): string
        {
            return $this->email;
        }

        public function getName(): string
        {
            return $this->name;
        }
    };
}

function mockSocialiteDriver(object $socialUser): void
{
    $provider = Mockery::mock(Provider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    $provider->shouldReceive('redirect')->andReturn(redirect('/'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

// ─── Schema tests ─────────────────────────────────────────────────────────────

test('social_accounts table has expected columns', function () {
    expect(Schema::hasTable('social_accounts'))->toBeTrue();
    expect(Schema::hasColumns('social_accounts', [
        'id', 'user_id', 'provider', 'provider_id',
        'token', 'refresh_token', 'token_expires_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});

test('user can be created without a password', function () {
    $user = User::factory()->withoutPassword()->create();

    expect($user->password)->toBeNull();
});

// ─── Redirect ────────────────────────────────────────────────────────────────

test('redirect route sends user to google', function () {
    mockSocialiteDriver(fakeSocialiteUser());

    $response = $this->get('/auth/google/redirect');

    $response->assertRedirect();
});

test('redirect returns 404 for unknown provider', function () {
    $this->get('/auth/google-bad/redirect')->assertNotFound();
});

// ─── Callback: new user ───────────────────────────────────────────────────────

test('callback creates new user and logs them in', function () {
    mockSocialiteDriver(fakeSocialiteUser());

    $this->get('/auth/google/callback');

    $this->assertAuthenticated();

    $user = User::where('email', 'social@example.com')->first();
    expect($user)->not->toBeNull();
    expect($user->hasPassword())->toBeFalse();
    expect($user->email_verified_at)->not->toBeNull();
    expect($user->socialAccounts)->toHaveCount(1);
    expect($user->socialAccounts->first()->provider)->toBe('google');
    expect($user->personalTeam())->not->toBeNull();
});

// ─── Callback: returning user ─────────────────────────────────────────────────

test('callback logs in existing user with social account', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id-123',
    ]);

    mockSocialiteDriver(fakeSocialiteUser(id: 'google-id-123', email: $user->email));

    $this->get('/auth/google/callback');

    $this->assertAuthenticatedAs($user);
});

// ─── Callback: auto-merge ────────────────────────────────────────────────────

test('callback links google to existing password account', function () {
    $user = User::factory()->create(['email' => 'social@example.com']);

    mockSocialiteDriver(fakeSocialiteUser());

    $this->get('/auth/google/callback');

    $this->assertAuthenticatedAs($user);
    expect($user->fresh()->socialAccounts)->toHaveCount(1);
});

// ─── Callback: connect mode ───────────────────────────────────────────────────

test('callback in connect mode links google to authenticated user', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    mockSocialiteDriver(fakeSocialiteUser());

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('security.edit'));
    expect($user->fresh()->socialAccounts)->toHaveCount(1);
});

test('callback in connect mode rejects google account already linked to another user', function () {
    $otherUser = User::factory()->create();
    SocialAccount::create([
        'user_id' => $otherUser->id,
        'provider' => 'google',
        'provider_id' => 'google-id-123',
    ]);

    $user = User::factory()->create();
    $this->actingAs($user);

    mockSocialiteDriver(fakeSocialiteUser());

    $response = $this->get('/auth/google/callback');

    $response->assertRedirect(route('security.edit'));
    expect($user->fresh()->socialAccounts)->toHaveCount(0);
});

// ─── Disconnect ───────────────────────────────────────────────────────────────

test('user can disconnect google when they have a password', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id-123',
    ]);

    $response = $this->actingAs($user)->delete('/auth/google/disconnect');

    $response->assertRedirect();
    expect($user->fresh()->socialAccounts)->toHaveCount(0);
});

test('user cannot disconnect their only login method when no password set', function () {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::create([
        'user_id' => $user->id,
        'provider' => 'google',
        'provider_id' => 'google-id-123',
    ]);

    $response = $this->actingAs($user)->delete('/auth/google/disconnect');

    $response->assertRedirect();
    expect($user->fresh()->socialAccounts)->toHaveCount(1); // not deleted
});
