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

        /** @var User $user */
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
            'name' => $socialUser->getName(),
            'email' => $socialUser->getEmail(),
            'password' => null,
        ]);
        $user->forceFill(['email_verified_at' => now()])->save();

        $this->createSocialAccount($user, $provider, $socialUser);
        $this->createTeam->handle($user, $user->name."'s Team", isPersonal: true);
        $this->loginAndSetTeam($user);

        return redirect()->route('dashboard');
    }

    private function handleConnect(string $provider, object $socialUser): RedirectResponse
    {
        /** @var User $user */
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
            'provider' => $provider,
            'provider_id' => (string) $socialUser->getId(),
            'token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
            'token_expires_at' => $socialUser->expiresIn
                ? now()->addSeconds($socialUser->expiresIn)
                : null,
        ]);
    }

    private function updateToken(SocialAccount $account, object $socialUser): void
    {
        $account->update([
            'token' => $socialUser->token,
            'refresh_token' => $socialUser->refreshToken,
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
