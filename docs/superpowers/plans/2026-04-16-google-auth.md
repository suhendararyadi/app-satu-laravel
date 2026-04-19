# Google OAuth Authentication Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Add "Continue with Google" login/register via Laravel Socialite, with auto-merge for existing accounts and connect/disconnect in security settings.

**Architecture:** A `social_accounts` table stores provider credentials separate from the `users` table. A single `SocialAuthController` handles redirect, callback (login + connect mode auto-detected by `Auth::check()`), and disconnect. No second redirect URI needed in Google Console.

**Tech Stack:** `laravel/socialite`, existing `CreateTeam` action, Inertia.js forms, Sonner toast via `Inertia::flash()`.

---

## File Map

| File                                                       | Action                                                                   |
| ---------------------------------------------------------- | ------------------------------------------------------------------------ |
| `database/migrations/..._create_social_accounts_table.php` | Create                                                                   |
| `database/migrations/..._make_users_password_nullable.php` | Create                                                                   |
| `app/Models/SocialAccount.php`                             | Create                                                                   |
| `app/Models/User.php`                                      | Modify — add `socialAccounts()`, `hasPassword()`, `connectedProviders()` |
| `database/factories/UserFactory.php`                       | Modify — add `withoutPassword()` state                                   |
| `app/Http/Controllers/Auth/SocialAuthController.php`       | Create                                                                   |
| `routes/web.php`                                           | Modify — add OAuth routes                                                |
| `config/services.php`                                      | Modify — add Google credentials                                          |
| `.env.example`                                             | Modify — add `GOOGLE_*` vars                                             |
| `app/Http/Controllers/Settings/SecurityController.php`     | Modify — pass `connectedProviders`, `hasPassword` props                  |
| `resources/js/components/google-auth-button.tsx`           | Create                                                                   |
| `resources/js/pages/auth/login.tsx`                        | Modify — add Google button                                               |
| `resources/js/pages/auth/register.tsx`                     | Modify — add Google button                                               |
| `resources/js/pages/settings/security.tsx`                 | Modify — add Connected Accounts section                                  |
| `tests/Feature/Auth/SocialAuthTest.php`                    | Create                                                                   |
| `tests/Unit/Models/SocialAccountTest.php`                  | Create                                                                   |

---

## Task 1: Install Socialite and configure credentials

**Files:**

- Modify: `config/services.php`
- Modify: `.env.example`

- [ ] **Step 1: Install Socialite**

```bash
composer require laravel/socialite
```

Expected: `laravel/socialite` added to `composer.json` and installed.

- [ ] **Step 2: Add Google config to `config/services.php`**

```php
// Add inside the return array, after the 'slack' entry:
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

- [ ] **Step 3: Add env vars to `.env.example`**

Add at the end of `.env.example`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Also copy these lines to your local `.env` and fill in real values from Google Cloud Console (OAuth 2.0 Client ID, redirect URI must match exactly).

- [ ] **Step 4: Commit**

```bash
git add composer.json composer.lock config/services.php .env.example
git commit -m "feat: install laravel/socialite and add Google OAuth config"
```

---

## Task 2: Migration — create `social_accounts` table

**Files:**

- Create: `database/migrations/2026_04_16_100000_create_social_accounts_table.php`
- Create: `tests/Feature/Auth/SocialAuthTest.php` (skeleton only, tests grow in Task 6)

- [ ] **Step 1: Write a failing schema test**

Create `tests/Feature/Auth/SocialAuthTest.php`:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('social_accounts table has expected columns', function () {
    expect(Schema::hasTable('social_accounts'))->toBeTrue();
    expect(Schema::hasColumns('social_accounts', [
        'id', 'user_id', 'provider', 'provider_id',
        'token', 'refresh_token', 'token_expires_at',
        'created_at', 'updated_at',
    ]))->toBeTrue();
});
```

- [ ] **Step 2: Run to confirm it fails**

```bash
./vendor/bin/pest --filter "social_accounts table has expected columns"
```

Expected: FAIL — table does not exist.

- [ ] **Step 3: Create the migration**

```bash
php artisan make:migration create_social_accounts_table
```

Replace the generated file contents with:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('social_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_id');
            $table->text('token')->nullable();
            $table->text('refresh_token')->nullable();
            $table->timestamp('token_expires_at')->nullable();
            $table->timestamps();

            $table->unique(['provider', 'provider_id']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('social_accounts');
    }
};
```

- [ ] **Step 4: Run the test to confirm it passes**

```bash
./vendor/bin/pest --filter "social_accounts table has expected columns"
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add database/migrations/ tests/Feature/Auth/SocialAuthTest.php
git commit -m "feat: add social_accounts migration"
```

---

## Task 3: Migration — make `users.password` nullable

**Files:**

- Create: `database/migrations/2026_04_16_100001_make_users_password_nullable.php`
- Modify: `database/factories/UserFactory.php` — add `withoutPassword()` state

- [ ] **Step 1: Write a failing test**

Add to `tests/Feature/Auth/SocialAuthTest.php`:

```php
test('users password column is nullable', function () {
    expect(DB::select("pragma table_info('users')"))->toContain(
        fn ($col) => $col->name === 'password' && $col->notnull == 0
    );
});
```

Actually SQLite pragma checking is fragile. Use a simpler approach — just verify a user can be saved with null password:

```php
test('user can be created without a password', function () {
    $user = User::factory()->withoutPassword()->create();

    expect($user->password)->toBeNull();
});
```

- [ ] **Step 2: Add `withoutPassword()` state to `UserFactory`**

In `database/factories/UserFactory.php`, add after the `unverified()` method:

```php
/**
 * Indicate that the user has no password (e.g. social-only account).
 */
public function withoutPassword(): static
{
    return $this->state(fn (array $attributes) => [
        'password' => null,
    ]);
}
```

- [ ] **Step 3: Run test to confirm it fails (because password is NOT NULL yet)**

```bash
./vendor/bin/pest --filter "user can be created without a password"
```

Expected: FAIL — database integrity error.

- [ ] **Step 4: Create the migration**

```bash
php artisan make:migration make_users_password_nullable
```

Replace contents:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('password')->nullable(false)->change();
        });
    }
};
```

- [ ] **Step 5: Run test to confirm it passes**

```bash
./vendor/bin/pest --filter "user can be created without a password"
```

Expected: PASS.

- [ ] **Step 6: Commit**

```bash
git add database/migrations/ database/factories/UserFactory.php tests/Feature/Auth/SocialAuthTest.php
git commit -m "feat: make users.password nullable for social-only accounts"
```

---

## Task 4: SocialAccount model

**Files:**

- Create: `app/Models/SocialAccount.php`
- Create: `tests/Unit/Models/SocialAccountTest.php`

- [ ] **Step 1: Write failing unit test**

Create `tests/Unit/Models/SocialAccountTest.php`:

```php
<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('social account belongs to a user', function () {
    $user = User::factory()->create();
    $account = SocialAccount::create([
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'google-id-123',
    ]);

    expect($account->user)->toBeInstanceOf(User::class);
    expect($account->user->id)->toBe($user->id);
});

test('social account is fillable with expected fields', function () {
    $user = User::factory()->create();
    $account = SocialAccount::create([
        'user_id'          => $user->id,
        'provider'         => 'google',
        'provider_id'      => 'google-id-456',
        'token'            => 'tok',
        'refresh_token'    => 'ref',
        'token_expires_at' => now()->addHour(),
    ]);

    expect($account->provider)->toBe('google');
    expect($account->token)->toBe('tok');
});
```

- [ ] **Step 2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Unit/Models/SocialAccountTest.php
```

Expected: FAIL — class not found.

- [ ] **Step 3: Create the model**

Create `app/Models/SocialAccount.php`:

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SocialAccount extends Model
{
    protected $fillable = [
        'user_id',
        'provider',
        'provider_id',
        'token',
        'refresh_token',
        'token_expires_at',
    ];

    protected $casts = [
        'token_expires_at' => 'datetime',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Unit/Models/SocialAccountTest.php
```

Expected: PASS (2 tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/SocialAccount.php tests/Unit/Models/SocialAccountTest.php
git commit -m "feat: add SocialAccount model"
```

---

## Task 5: Update User model

**Files:**

- Modify: `app/Models/User.php`

- [ ] **Step 1: Write failing tests**

Add to `tests/Unit/Models/SocialAccountTest.php`:

```php
test('user has many social accounts', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id'     => $user->id,
        'provider'    => 'google',
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
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'g-1',
    ]);

    expect($user->connectedProviders()->toArray())->toBe(['google']);
});
```

- [ ] **Step 2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Unit/Models/SocialAccountTest.php
```

Expected: FAIL — method/relation not found.

- [ ] **Step 3: Update `app/Models/User.php`**

Add imports at the top (after existing `use` statements):

```php
use App\Models\SocialAccount;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;
```

Add these methods inside the `User` class (after the `casts()` method):

```php
/**
 * @return HasMany<SocialAccount, $this>
 */
public function socialAccounts(): HasMany
{
    return $this->hasMany(SocialAccount::class);
}

public function hasPassword(): bool
{
    return ! is_null($this->password);
}

/**
 * @return Collection<int, string>
 */
public function connectedProviders(): Collection
{
    return $this->socialAccounts()->pluck('provider');
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Unit/Models/SocialAccountTest.php
```

Expected: PASS (all tests).

- [ ] **Step 5: Commit**

```bash
git add app/Models/User.php tests/Unit/Models/SocialAccountTest.php
git commit -m "feat: add socialAccounts relation and helpers to User model"
```

---

## Task 6: SocialAuthController

**Files:**

- Create: `app/Http/Controllers/Auth/SocialAuthController.php`
- Modify: `tests/Feature/Auth/SocialAuthTest.php`

- [ ] **Step 1: Write failing feature tests**

Replace the contents of `tests/Feature/Auth/SocialAuthTest.php` with:

```php
<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;

uses(RefreshDatabase::class);

// ─── Helpers ─────────────────────────────────────────────────────────────────

function fakeSocialiteUser(
    string $id = 'google-id-123',
    string $email = 'social@example.com',
    string $name = 'Social User',
): object {
    return new class ($id, $email, $name) {
        public string $token = 'fake-token';
        public ?string $refreshToken = null;
        public ?int $expiresIn = 3600;

        public function __construct(
            private string $id,
            private string $email,
            private string $name,
        ) {}

        public function getId(): string { return $this->id; }
        public function getEmail(): string { return $this->email; }
        public function getName(): string { return $this->name; }
    };
}

function mockSocialiteDriver(object $socialUser): void
{
    $provider = Mockery::mock(\Laravel\Socialite\Contracts\Provider::class);
    $provider->shouldReceive('user')->andReturn($socialUser);
    $provider->shouldReceive('redirect')->andReturn(redirect('/'));

    Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
}

// ─── Schema tests ─────────────────────────────────────────────────────────────

test('social_accounts table has expected columns', function () {
    expect(\Illuminate\Support\Facades\Schema::hasTable('social_accounts'))->toBeTrue();
    expect(\Illuminate\Support\Facades\Schema::hasColumns('social_accounts', [
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
        'user_id'     => $user->id,
        'provider'    => 'google',
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
        'user_id'     => $otherUser->id,
        'provider'    => 'google',
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
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'google-id-123',
    ]);

    $response = $this->actingAs($user)->delete('/auth/google/disconnect');

    $response->assertRedirect();
    expect($user->fresh()->socialAccounts)->toHaveCount(0);
});

test('user cannot disconnect their only login method when no password set', function () {
    $user = User::factory()->withoutPassword()->create();
    SocialAccount::create([
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'google-id-123',
    ]);

    $response = $this->actingAs($user)->delete('/auth/google/disconnect');

    $response->assertRedirect();
    expect($user->fresh()->socialAccounts)->toHaveCount(1); // not deleted
});
```

- [ ] **Step 2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Auth/SocialAuthTest.php
```

Expected: FAIL — routes not found / controller not found.

- [ ] **Step 3: Create the controller**

Create `app/Http/Controllers/Auth/SocialAuthController.php`:

```php
<?php

namespace App\Http\Controllers\Auth;

use App\Actions\Teams\CreateTeam;
use App\Http\Controllers\Controller;
use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Laravel\Socialite\Facades\Socialite;

class SocialAuthController extends Controller
{
    private const ALLOWED_PROVIDERS = ['google'];

    public function __construct(private CreateTeam $createTeam) {}

    public function redirect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        return Socialite::driver($provider)->redirect();
    }

    public function callback(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        try {
            $socialUser = Socialite::driver($provider)->user();
        } catch (\Throwable) {
            return redirect()->route('login')
                ->with('error', 'Could not authenticate with '.ucfirst($provider).'. Please try again.');
        }

        if (Auth::check()) {
            return $this->handleConnect($provider, $socialUser);
        }

        return $this->handleLogin($provider, $socialUser);
    }

    public function disconnect(string $provider): RedirectResponse
    {
        abort_unless(in_array($provider, self::ALLOWED_PROVIDERS), 404);

        $user = Auth::user();

        $socialAccount = $user->socialAccounts()
            ->where('provider', $provider)
            ->first();

        abort_unless($socialAccount, 404);

        if (! $user->hasPassword() && $user->socialAccounts()->count() === 1) {
            return back()->with('error', 'You must set a password before disconnecting your only login method.');
        }

        $socialAccount->delete();

        return back()->with('success', ucfirst($provider).' account disconnected.');
    }

    private function handleLogin(string $provider, object $socialUser): RedirectResponse
    {
        $existingAccount = SocialAccount::where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->with('user')
            ->first();

        if ($existingAccount) {
            $this->updateToken($existingAccount, $socialUser);
            $user = $existingAccount->user;
            $this->loginAndSetTeam($user);

            return redirect()->route('dashboard');
        }

        $user = User::where('email', $socialUser->getEmail())->first();

        if ($user) {
            $this->createSocialAccount($user, $provider, $socialUser);
            $this->loginAndSetTeam($user);

            return redirect()->route('dashboard');
        }

        $user = User::create([
            'name'              => $socialUser->getName(),
            'email'             => $socialUser->getEmail(),
            'password'          => null,
            'email_verified_at' => now(),
        ]);

        $this->createSocialAccount($user, $provider, $socialUser);
        $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);
        $this->loginAndSetTeam($user);

        return redirect()->route('dashboard');
    }

    private function handleConnect(string $provider, object $socialUser): RedirectResponse
    {
        $user = Auth::user();

        $existing = SocialAccount::where('provider', $provider)
            ->where('provider_id', (string) $socialUser->getId())
            ->first();

        if ($existing && $existing->user_id !== $user->id) {
            return redirect()->route('security.edit')
                ->with('error', 'This '.ucfirst($provider).' account is already linked to another user.');
        }

        if ($existing && $existing->user_id === $user->id) {
            return redirect()->route('security.edit')
                ->with('success', ucfirst($provider).' account is already connected.');
        }

        $this->createSocialAccount($user, $provider, $socialUser);

        return redirect()->route('security.edit')
            ->with('success', ucfirst($provider).' account connected successfully.');
    }

    private function createSocialAccount(User $user, string $provider, object $socialUser): SocialAccount
    {
        return $user->socialAccounts()->create([
            'provider'         => $provider,
            'provider_id'      => (string) $socialUser->getId(),
            'token'            => $socialUser->token,
            'refresh_token'    => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }

    private function updateToken(SocialAccount $account, object $socialUser): void
    {
        $account->update([
            'token'            => $socialUser->token,
            'refresh_token'    => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }

    private function loginAndSetTeam(User $user): void
    {
        Auth::login($user, remember: true);
        URL::defaults(['current_team' => $user->currentTeam?->slug]);
    }
}
```

- [ ] **Step 4: Run tests — expect route-not-found failures (controller exists, routes don't yet)**

```bash
./vendor/bin/pest tests/Feature/Auth/SocialAuthTest.php
```

Expected: still failing on route errors. Routes added in next task.

- [ ] **Step 5: Commit controller (routes in Task 7)**

```bash
git add app/Http/Controllers/Auth/SocialAuthController.php tests/Feature/Auth/SocialAuthTest.php
git commit -m "feat: add SocialAuthController"
```

---

## Task 7: Add OAuth routes

**Files:**

- Modify: `routes/web.php`

- [ ] **Step 1: Add routes to `routes/web.php`**

Add after the existing `Route::middleware(['auth'])` block (before `require __DIR__.'/settings.php'`):

```php
// Google OAuth — single callback URI
use App\Http\Controllers\Auth\SocialAuthController;

Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.social.callback');
Route::middleware(['auth'])->group(function () {
    Route::delete('/auth/{provider}/disconnect', [SocialAuthController::class, 'disconnect'])
        ->name('auth.social.disconnect');
});
```

Make sure `use App\Http\Controllers\Auth\SocialAuthController;` is at the top of the file with the other `use` statements.

- [ ] **Step 2: Run the feature tests — all should now pass**

```bash
./vendor/bin/pest tests/Feature/Auth/SocialAuthTest.php
```

Expected: PASS (all tests).

- [ ] **Step 3: Regenerate Wayfinder (creates typed route/action helpers)**

```bash
npm run build
```

Expected: builds without errors. `resources/js/actions/App/Http/Controllers/Auth/SocialAuthController.ts` should be generated.

- [ ] **Step 4: Commit**

```bash
git add routes/web.php resources/js/actions/ resources/js/routes/
git commit -m "feat: add social auth routes and regenerate Wayfinder"
```

---

## Task 8: Update SecurityController to pass new props

**Files:**

- Modify: `app/Http/Controllers/Settings/SecurityController.php`

- [ ] **Step 1: Write failing test**

Create `tests/Feature/Settings/SecuritySocialTest.php`:

```php
<?php

use App\Models\SocialAccount;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('security page includes connectedProviders and hasPassword props', function () {
    $user = User::factory()->create();
    SocialAccount::create([
        'user_id'     => $user->id,
        'provider'    => 'google',
        'provider_id' => 'g-1',
    ]);

    $response = $this->actingAs($user)->get(route('security.edit'));

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

    $response = $this->actingAs($user)->get(route('security.edit'));

    $response->assertInertia(fn ($page) => $page
        ->where('hasPassword', false)
    );
});
```

- [ ] **Step 2: Run to confirm failure**

```bash
./vendor/bin/pest tests/Feature/Settings/SecuritySocialTest.php
```

Expected: FAIL — props not present.

- [ ] **Step 3: Update `SecurityController::edit()`**

In `app/Http/Controllers/Settings/SecurityController.php`, update the `edit()` method:

```php
public function edit(TwoFactorAuthenticationRequest $request): Response
{
    $props = [
        'canManageTwoFactor' => Features::canManageTwoFactorAuthentication(),
        'connectedProviders' => $request->user()->connectedProviders()->values()->all(),
        'hasPassword'        => $request->user()->hasPassword(),
    ];

    if (Features::canManageTwoFactorAuthentication()) {
        $request->ensureStateIsValid();

        $props['twoFactorEnabled'] = $request->user()->hasEnabledTwoFactorAuthentication();
        $props['requiresConfirmation'] = Features::optionEnabled(Features::twoFactorAuthentication(), 'confirm');
    }

    return Inertia::render('settings/security', $props);
}
```

- [ ] **Step 4: Run tests to confirm they pass**

```bash
./vendor/bin/pest tests/Feature/Settings/SecuritySocialTest.php
```

Expected: PASS.

- [ ] **Step 5: Commit**

```bash
git add app/Http/Controllers/Settings/SecurityController.php tests/Feature/Settings/SecuritySocialTest.php
git commit -m "feat: pass connectedProviders and hasPassword to security settings page"
```

---

## Task 9: Google button component

**Files:**

- Create: `resources/js/components/google-auth-button.tsx`

- [ ] **Step 1: Create the component**

Create `resources/js/components/google-auth-button.tsx`:

```tsx
type Props = {
    label?: string;
    className?: string;
};

export default function GoogleAuthButton({
    label = 'Continue with Google',
    className = '',
}: Props) {
    return (
        <a
            href="/auth/google/redirect"
            className={`inline-flex w-full items-center justify-center gap-2 rounded-md border border-input bg-background px-4 py-2 text-sm font-medium shadow-sm transition-colors hover:bg-accent hover:text-accent-foreground focus-visible:ring-1 focus-visible:ring-ring focus-visible:outline-none ${className}`}
        >
            <svg
                xmlns="http://www.w3.org/2000/svg"
                viewBox="0 0 24 24"
                aria-hidden="true"
                className="h-4 w-4"
            >
                <path
                    d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                    fill="#4285F4"
                />
                <path
                    d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                    fill="#34A853"
                />
                <path
                    d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                    fill="#FBBC05"
                />
                <path
                    d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                    fill="#EA4335"
                />
            </svg>
            {label}
        </a>
    );
}
```

- [ ] **Step 2: Run TypeScript check**

```bash
npm run types:check
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/google-auth-button.tsx
git commit -m "feat: add GoogleAuthButton component"
```

---

## Task 10: Add Google button to Login page

**Files:**

- Modify: `resources/js/pages/auth/login.tsx`

- [ ] **Step 1: Update `login.tsx`**

Add the import at the top (with other imports, in alphabetical order):

```tsx
import GoogleAuthButton from '@/components/google-auth-button';
```

Add the divider and button **after** the closing `</Form>` tag and **before** the `{status && ...}` block:

```tsx
<div className="relative">
    <div className="absolute inset-0 flex items-center">
        <span className="w-full border-t" />
    </div>
    <div className="relative flex justify-center text-xs uppercase">
        <span className="bg-background px-2 text-muted-foreground">
            Or continue with
        </span>
    </div>
</div>

<GoogleAuthButton />
```

The final structure around that area should look like:

```tsx
            </Form>

            <div className="relative">
                <div className="absolute inset-0 flex items-center">
                    <span className="w-full border-t" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-background px-2 text-muted-foreground">
                        Or continue with
                    </span>
                </div>
            </div>

            <GoogleAuthButton />

            {status && (
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/auth/login.tsx
git commit -m "feat: add Google auth button to login page"
```

---

## Task 11: Add Google button to Register page

**Files:**

- Modify: `resources/js/pages/auth/register.tsx`

- [ ] **Step 1: Update `register.tsx`**

Add the import:

```tsx
import GoogleAuthButton from '@/components/google-auth-button';
```

Add the divider and button **after** the closing `</Form>` tag:

```tsx
            </Form>

            <div className="relative">
                <div className="absolute inset-0 flex items-center">
                    <span className="w-full border-t" />
                </div>
                <div className="relative flex justify-center text-xs uppercase">
                    <span className="bg-background px-2 text-muted-foreground">
                        Or continue with
                    </span>
                </div>
            </div>

            <GoogleAuthButton label="Sign up with Google" />
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/auth/register.tsx
git commit -m "feat: add Google auth button to register page"
```

---

## Task 12: Connected Accounts section in Security settings

**Files:**

- Modify: `resources/js/pages/settings/security.tsx`

- [ ] **Step 1: Update `security.tsx`**

**Add imports** (maintain alphabetical order within groups):

```tsx
import type { InertiaFormProps } from '@inertiajs/react';
```

Wait — no new Inertia imports needed if using `router.delete`. Add only:

```tsx
import { router } from '@inertiajs/react';
```

Add this import alongside the existing `import { Form, Head } from '@inertiajs/react';` — update that line to:

```tsx
import { Form, Head, router } from '@inertiajs/react';
```

**Update the `Props` type** to include:

```tsx
type Props = {
    canManageTwoFactor?: boolean;
    requiresConfirmation?: boolean;
    twoFactorEnabled?: boolean;
    connectedProviders?: string[];
    hasPassword?: boolean;
};
```

**Update the function signature**:

```tsx
export default function Security({
    canManageTwoFactor = false,
    requiresConfirmation = false,
    twoFactorEnabled = false,
    connectedProviders = [],
    hasPassword = true,
}: Props) {
```

**Add the Connected Accounts section** at the very bottom, inside the outer fragment but after the `{canManageTwoFactor && ...}` block and before the closing `</>`:

```tsx
<div className="space-y-6">
    <Heading
        variant="small"
        title="Connected accounts"
        description="Manage social login connections for your account"
    />

    <div className="space-y-3">
        {connectedProviders.includes('google') ? (
            <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="flex items-center gap-3">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        className="h-5 w-5"
                    >
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335"
                        />
                    </svg>
                    <div>
                        <p className="text-sm font-medium">Google</p>
                        <p className="text-xs text-muted-foreground">
                            Connected
                        </p>
                    </div>
                </div>
                <Button
                    variant="outline"
                    size="sm"
                    disabled={!hasPassword && connectedProviders.length === 1}
                    title={
                        !hasPassword && connectedProviders.length === 1
                            ? 'Set a password before disconnecting your only login method'
                            : undefined
                    }
                    onClick={() =>
                        router.delete('/auth/google/disconnect', {
                            preserveScroll: true,
                        })
                    }
                >
                    Disconnect
                </Button>
            </div>
        ) : (
            <div className="flex items-center justify-between rounded-lg border p-4">
                <div className="flex items-center gap-3">
                    <svg
                        xmlns="http://www.w3.org/2000/svg"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                        className="h-5 w-5 opacity-50"
                    >
                        <path
                            d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"
                            fill="#4285F4"
                        />
                        <path
                            d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"
                            fill="#34A853"
                        />
                        <path
                            d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"
                            fill="#FBBC05"
                        />
                        <path
                            d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"
                            fill="#EA4335"
                        />
                    </svg>
                    <div>
                        <p className="text-sm font-medium">Google</p>
                        <p className="text-xs text-muted-foreground">
                            Not connected
                        </p>
                    </div>
                </div>
                <Button variant="outline" size="sm" asChild>
                    <a href="/auth/google/redirect">Connect</a>
                </Button>
            </div>
        )}
    </div>
</div>
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Fix any import-order or type errors reported.

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/settings/security.tsx
git commit -m "feat: add Connected Accounts section to security settings"
```

---

## Task 13: Full CI check

- [ ] **Step 1: Run full test suite**

```bash
./vendor/bin/pest
```

Expected: all tests PASS.

- [ ] **Step 2: Run full CI check**

```bash
composer ci:check
```

Expected: lint, format, types, and tests all pass.

- [ ] **Step 3: Fix any issues**

If `npm run format:check` fails, run `npm run format` then re-check.  
If `npm run lint:check` fails, run `npm run lint` then re-check.  
If `composer lint:check` fails, run `composer lint` then re-check.

- [ ] **Step 4: Final commit if any formatting fixes were applied**

```bash
git add -A
git commit -m "style: apply formatter fixes"
```

---

## Setup reminder

Before testing in browser, make sure `.env` has real Google credentials:

```
GOOGLE_CLIENT_ID=<from Google Cloud Console>
GOOGLE_CLIENT_SECRET=<from Google Cloud Console>
GOOGLE_REDIRECT_URI=http://app-satu.test/auth/google/callback
```

In Google Cloud Console → OAuth 2.0 Client → Authorized redirect URIs, add exactly the value of `GOOGLE_REDIRECT_URI`.
