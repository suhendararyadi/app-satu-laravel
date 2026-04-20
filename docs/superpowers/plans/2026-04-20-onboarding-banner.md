# Onboarding Banner Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tampilkan banner informatif di dashboard untuk user yang belum memiliki team sekolah, dengan tombol yang langsung membuka modal pembuatan team.

**Architecture:** Backend `DashboardController` mengirim prop `hasSchoolTeam` (boolean) ke halaman dashboard. Frontend merender komponen `OnboardingBanner` jika prop tersebut `false`. Banner menggunakan `Alert` shadcn dan membungkus `CreateTeamModal` yang sudah ada.

**Tech Stack:** Laravel 13, Inertia.js v3, React 19, TypeScript, Tailwind CSS v4, shadcn/ui (Alert), lucide-react

---

## File Map

| File                                            | Action     | Tanggung Jawab                           |
| ----------------------------------------------- | ---------- | ---------------------------------------- |
| `app/Http/Controllers/DashboardController.php`  | Buat baru  | Kirim `hasSchoolTeam` prop ke Inertia    |
| `routes/web.php`                                | Modifikasi | Ganti `Route::inertia` dengan controller |
| `tests/Feature/DashboardTest.php`               | Modifikasi | Tambah 2 test untuk prop `hasSchoolTeam` |
| `resources/js/components/onboarding-banner.tsx` | Buat baru  | Komponen banner dengan tombol buka modal |
| `resources/js/pages/dashboard.tsx`              | Modifikasi | Terima prop, render banner kondisional   |

---

## Task 1: Backend — DashboardController, route, dan tests

**Files:**

- Modify: `tests/Feature/DashboardTest.php`
- Create: `app/Http/Controllers/DashboardController.php`
- Modify: `routes/web.php`

- [ ] **Step 1: Tambah dua test yang failing ke DashboardTest.php**

Buka `tests/Feature/DashboardTest.php` dan tambahkan dua test berikut di bawah test yang sudah ada:

```php
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

    $response = $this
        ->actingAs($user)
        ->get(route('dashboard'));

    $response->assertOk();
});

test('dashboard passes hasSchoolTeam false when user has no school team', function () {
    $user = User::factory()->create();
    // User::factory() auto-creates a personal team only (is_personal=true)

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
    $schoolTeam = Team::factory()->create(); // default is_personal=false

    $schoolTeam->members()->attach($user, ['role' => TeamRole::Owner->value]);

    $this->withoutVite()
        ->actingAs($user)
        ->get(route('dashboard'))
        ->assertInertia(fn ($page) => $page
            ->component('dashboard')
            ->where('hasSchoolTeam', true)
        );
});
```

- [ ] **Step 2: Jalankan dua test baru — pastikan fail**

```bash
./vendor/bin/pest --filter "hasSchoolTeam"
```

Expected output: 2 tests FAIL dengan error seperti `Property [hasSchoolTeam] is not present`.

- [ ] **Step 3: Buat DashboardController**

```bash
php artisan make:controller DashboardController --no-interaction
```

Ganti isi file `app/Http/Controllers/DashboardController.php` dengan:

```php
<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();

        return Inertia::render('dashboard', [
            'hasSchoolTeam' => $user->teams()->where('is_personal', false)->exists(),
        ]);
    }
}
```

- [ ] **Step 4: Update route di routes/web.php**

Buka `routes/web.php`. Tambahkan import `DashboardController` di bagian atas (setelah `use` lainnya):

```php
use App\Http\Controllers\DashboardController;
```

Ganti baris:

```php
Route::inertia('dashboard', 'dashboard')->name('dashboard');
```

dengan:

```php
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
```

- [ ] **Step 5: Jalankan semua test di DashboardTest — pastikan pass**

```bash
./vendor/bin/pest tests/Feature/DashboardTest.php --compact
```

Expected output: 4 tests PASS.

- [ ] **Step 6: Jalankan seluruh test suite — pastikan tidak ada regresi**

```bash
php artisan test --compact
```

Expected output: semua test pass (sebelumnya 145, sekarang 147).

- [ ] **Step 7: Format PHP dengan Pint**

```bash
./vendor/bin/pint --dirty --format agent
```

- [ ] **Step 8: Commit**

```bash
git add app/Http/Controllers/DashboardController.php routes/web.php tests/Feature/DashboardTest.php
git commit -m "feat: add DashboardController with hasSchoolTeam prop"
```

---

## Task 2: Komponen OnboardingBanner

**Files:**

- Create: `resources/js/components/onboarding-banner.tsx`

- [ ] **Step 1: Buat file onboarding-banner.tsx**

Buat file `resources/js/components/onboarding-banner.tsx` dengan isi:

```tsx
import { School } from 'lucide-react';
import CreateTeamModal from '@/components/create-team-modal';
import { Alert, AlertDescription, AlertTitle } from '@/components/ui/alert';
import { Button } from '@/components/ui/button';

export default function OnboardingBanner() {
    return (
        <Alert>
            <School />
            <AlertTitle>Buat team sekolah</AlertTitle>
            <AlertDescription>
                <div className="flex items-center justify-between gap-4">
                    <span>
                        Buat team sekolah untuk mulai mengelola website sekolah
                        Anda. Team sekolah memungkinkan Anda mengelola konten,
                        profil, dan anggota tim.
                    </span>
                    <CreateTeamModal>
                        <Button size="sm" className="shrink-0">
                            Buat Team Sekolah
                        </Button>
                    </CreateTeamModal>
                </div>
            </AlertDescription>
        </Alert>
    );
}
```

- [ ] **Step 2: Cek TypeScript errors**

```bash
npm run types:check
```

Expected: tidak ada error baru yang berasal dari `onboarding-banner.tsx`. (Error pre-existing di file lain boleh diabaikan — cek bahwa jumlah error tidak bertambah.)

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/onboarding-banner.tsx
git commit -m "feat: add OnboardingBanner component"
```

---

## Task 3: Update dashboard.tsx

**Files:**

- Modify: `resources/js/pages/dashboard.tsx`

- [ ] **Step 1: Update dashboard.tsx untuk terima prop dan render banner**

Ganti seluruh isi `resources/js/pages/dashboard.tsx` dengan:

```tsx
import { Head } from '@inertiajs/react';
import OnboardingBanner from '@/components/onboarding-banner';
import { PlaceholderPattern } from '@/components/ui/placeholder-pattern';
import { dashboard } from '@/routes';

type Props = {
    hasSchoolTeam: boolean;
};

export default function Dashboard({ hasSchoolTeam }: Props) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {!hasSchoolTeam && <OnboardingBanner />}
                <div className="grid auto-rows-min gap-4 md:grid-cols-3">
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                    <div className="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                        <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                    </div>
                </div>
                <div className="relative min-h-[100vh] flex-1 overflow-hidden rounded-xl border border-sidebar-border/70 md:min-h-min dark:border-sidebar-border">
                    <PlaceholderPattern className="absolute inset-0 size-full stroke-neutral-900/20 dark:stroke-neutral-100/20" />
                </div>
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
```

- [ ] **Step 2: Cek TypeScript errors**

```bash
npm run types:check
```

Expected: tidak ada error baru dari `dashboard.tsx`.

- [ ] **Step 3: Jalankan test suite sekali lagi untuk konfirmasi**

```bash
php artisan test --compact
```

Expected: semua test pass (147 tests).

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "feat: show onboarding banner on dashboard when no school team"
```
