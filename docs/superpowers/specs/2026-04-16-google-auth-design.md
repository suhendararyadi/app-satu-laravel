# Google OAuth Authentication — Design Spec

**Date:** 2026-04-16  
**Stack:** Laravel 13 + Fortify + React 19 + Inertia.js v3

---

## Overview

Add "Continue with Google" login/register to the app using **Laravel Socialite**, backed by a separate `social_accounts` table (extensible to future providers). Users who sign up via Google do not require a password. If a Google email matches an existing account, the accounts are merged automatically.

---

## Database

### Migration 1 — `social_accounts` table

```
social_accounts
  id              bigIncrements
  user_id         FK → users.id (cascade delete)
  provider        string          e.g. 'google'
  provider_id     string          Google's user ID
  token           text, nullable  OAuth access token
  refresh_token   text, nullable
  token_expires_at timestamp, nullable
  created_at / updated_at

  UNIQUE (provider, provider_id)
  INDEX  (user_id)
```

### Migration 2 — make `users.password` nullable

`users.password` is currently `string NOT NULL`. It must become nullable to support Google-only accounts.

---

## Models

### `SocialAccount` (new)

- `belongsTo(User::class)`
- Fillable: `user_id`, `provider`, `provider_id`, `token`, `refresh_token`, `token_expires_at`

### `User` (updated)

- Add: `hasMany(SocialAccount::class)`
- Add helper: `hasPassword(): bool` — returns `!is_null($this->password)`
- Add helper: `connectedProviders(): Collection` — returns collection of provider strings from related social_accounts

---

## Backend

### Install

```bash
composer require laravel/socialite
```

Add to `config/services.php`:

```php
'google' => [
    'client_id'     => env('GOOGLE_CLIENT_ID'),
    'client_secret' => env('GOOGLE_CLIENT_SECRET'),
    'redirect'      => env('GOOGLE_REDIRECT_URI'),
],
```

Add to `.env` / `.env.example`:

```
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI=http://localhost/auth/google/callback
```

### `SocialAuthController`

**`redirect(string $provider)`**

- Validate `$provider` is in an allowed list (`['google']`)
- Return `Socialite::driver($provider)->redirect()`

**`callback(string $provider)`**

- Validate `$provider`
- Call `Socialite::driver($provider)->user()` (wrapped in try/catch — on failure, redirect to login with error)
- Resolution logic:
    1. Find `SocialAccount` by `(provider, provider_id)` → if found, log in the associated user
    2. Else, find `User` by email →
        - If found: create `SocialAccount` linking to that user, log in (auto-merge)
        - If not found: create new `User` (`password = null`, `email_verified_at = now()`), create `SocialAccount`, create default team (reuse existing `CreateTeam` action), log in
- On success: redirect to `route('dashboard', ['current_team' => $user->currentTeam])` — matches Fortify's post-login behavior
- On failure: redirect back to login with flashed error message

**`callback(string $provider)`** _(handles both login AND connect — single redirect URI)_

Google Console only allows registering one redirect URI easily. A single `/auth/{provider}/callback` route is used for both login and account-linking. The controller differentiates by checking `Auth::check()`:

- If **not authenticated** → login/register flow (see above)
- If **authenticated** → connect flow:
    - Fetch provider user
    - If `(provider, provider_id)` already linked to a **different** user → redirect to `settings.security` with error
    - If already linked to the **current** user → redirect with "already connected" message
    - Otherwise: create `SocialAccount` for the authenticated user, redirect to `settings.security` with success

**`disconnect(string $provider)`** _(authenticated only, DELETE)_

- Guard: if user has no password AND has only 1 social account → abort with error ("You must set a password before disconnecting your only login method")
- Delete the `SocialAccount` record
- Redirect back with success message

### Routes (added to `routes/web.php`)

```php
// OAuth — single callback URI registered in Google Console
Route::get('/auth/{provider}/redirect', [SocialAuthController::class, 'redirect'])
    ->name('auth.social.redirect');
Route::get('/auth/{provider}/callback', [SocialAuthController::class, 'callback'])
    ->name('auth.social.callback');

// Disconnect (auth required)
Route::middleware(['auth'])->group(function () {
    Route::delete('/auth/{provider}/disconnect', [SocialAuthController::class, 'disconnect'])
        ->name('auth.social.disconnect');
});
```

> **Google Console:** Register only `GOOGLE_REDIRECT_URI` (e.g. `http://app-satu.test/auth/google/callback`). No second URI needed.

---

## Frontend

### Login page (`resources/js/pages/auth/login.tsx`)

Below the existing form, add:

```
─── Or continue with ───

[ G  Continue with Google ]
```

The Google button is an `<a>` tag (not Inertia `<Link>`) pointing to `/auth/google/redirect` (full page redirect required for OAuth).

### Register page (`resources/js/pages/auth/register.tsx`)

Same divider + Google button as Login, placed below the "Create account" button section.

### Security settings page (`resources/js/pages/settings/security.tsx`)

New section **"Connected Accounts"** at the bottom:

- For each provider in `connectedProviders`:
    - Show provider icon + name
    - "Disconnect" button (calls DELETE via Inertia form)
    - Button is disabled with tooltip if `!hasPassword && connectedProviders.length === 1`
- If Google is not connected:
    - Show "Connect Google" button → links to `/auth/google/redirect` (same redirect route, connect mode auto-detected on callback)

Props passed from `SecurityController::edit()` (additions):

```ts
connectedProviders: string[]   // e.g. ['google']
hasPassword: boolean
```

---

## Error Handling

| Scenario                                            | Behavior                                             |
| --------------------------------------------------- | ---------------------------------------------------- |
| OAuth callback error / user denies                  | Redirect to login with `error` flash message         |
| Email taken by different Google ID                  | Auto-merge: link new social account to existing user |
| Connect: provider already linked to another account | Redirect to security settings with error flash       |
| Disconnect with no password and last social account | Return error, do not disconnect                      |

---

## Testing

- Unit test: `SocialAccount` model relationships
- Feature test: `SocialAuthController` callback — new user creation, auto-merge, login
- Feature test: connect / disconnect flows with edge cases (no password guard)
- No frontend test suite exists in this project — tested manually
