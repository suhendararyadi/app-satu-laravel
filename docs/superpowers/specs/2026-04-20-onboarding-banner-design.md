# Onboarding Banner Design

**Date:** 2026-04-20
**Feature:** Onboarding banner untuk user yang belum memiliki team sekolah

## Overview

User yang baru mendaftar (atau user lama) yang belum memiliki team sekolah tidak mendapat panduan untuk membuat satu. Fitur ini menambahkan banner informatif di halaman Dashboard yang mengarahkan user untuk membuat team sekolah pertama mereka.

## Trigger Condition

Banner ditampilkan jika user **tidak memiliki satu pun team dengan `is_personal = false`**. Kondisi ini berlaku untuk:

- User baru yang baru selesai registrasi
- User lama yang belum pernah membuat team sekolah

Banner hilang secara natural saat user berhasil membuat team sekolah — karena `TeamController.store` meredirect ke `teams.edit`, dashboard tidak dirender ulang dengan kondisi yang sama.

## Architecture

### Backend

**Buat `DashboardController`** di `app/Http/Controllers/DashboardController.php`:

```php
public function index(Request $request): Response
{
    $user = $request->user();

    return Inertia::render('dashboard', [
        'hasSchoolTeam' => $user->teams()->where('is_personal', false)->exists(),
    ]);
}
```

**Ubah route** di `routes/web.php`:

```php
// Sebelum:
Route::inertia('dashboard', 'dashboard')->name('dashboard');

// Sesudah:
Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');
```

### Frontend

**Komponen baru: `resources/js/components/onboarding-banner.tsx`**

- Menggunakan komponen `Alert` dari `ui/alert` (sudah tersedia)
- Ikon `School` dari lucide-react
- Tombol "Buat Team Sekolah" membungkus `CreateTeamModal` (sudah tersedia)
- Tidak memerlukan state dismiss — banner hilang natural saat redirect terjadi

Tampilan banner:

```
┌─────────────────────────────────────────────────────────────────┐
│  🏫  Buat team sekolah untuk mulai mengelola website sekolah    │
│      Anda. Team sekolah memungkinkan Anda mengelola konten,     │
│      profil, dan anggota tim.              [Buat Team Sekolah]  │
└─────────────────────────────────────────────────────────────────┘
```

**Update `resources/js/pages/dashboard.tsx`:**

- Terima prop `hasSchoolTeam: boolean`
- Render `<OnboardingBanner />` di atas konten jika `!hasSchoolTeam`

## Data Flow

```
User buka /dashboard
    → DashboardController::index()
    → cek $user->teams()->where('is_personal', false)->exists()
    → kirim hasSchoolTeam: bool ke Inertia
    → dashboard.tsx render banner jika false
    → user klik "Buat Team Sekolah"
    → CreateTeamModal terbuka
    → user submit form
    → TeamController::store() → redirect ke teams.edit
    → banner hilang (dashboard tidak dirender ulang)
```

## Files Changed

| File                                            | Action                             |
| ----------------------------------------------- | ---------------------------------- |
| `app/Http/Controllers/DashboardController.php`  | Buat baru                          |
| `routes/web.php`                                | Ubah `Route::inertia` → controller |
| `resources/js/components/onboarding-banner.tsx` | Buat baru                          |
| `resources/js/pages/dashboard.tsx`              | Tambah prop + render banner        |

## Testing

Dua Pest feature test di `tests/Feature/DashboardTest.php`:

1. **`dashboard_shows_onboarding_banner_when_user_has_no_school_team`**
    - User dengan hanya personal team (`is_personal=true`) → `GET /{team}/dashboard`
    - Assert: Inertia prop `hasSchoolTeam` bernilai `false`

2. **`dashboard_does_not_show_onboarding_banner_when_user_has_school_team`**
    - User dengan school team (`is_personal=false`) yang aktif → `GET /{team}/dashboard`
    - Assert: Inertia prop `hasSchoolTeam` bernilai `true`

## Out of Scope

- Multi-step onboarding (isi profil sekolah, buat konten)
- Banner di halaman selain Dashboard
- Dismiss/hide banner tanpa membuat team
- Onboarding untuk role non-Owner
