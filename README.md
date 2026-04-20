# Laravel React Starter Kit

Boilerplate siap pakai untuk membangun aplikasi web modern dengan Laravel, React, dan Inertia.js. Dilengkapi autentikasi lengkap, Google OAuth, manajemen tim, dan siap di-deploy ke Laravel Cloud.

![PHP](https://img.shields.io/badge/PHP-8.4-777BB4?logo=php&logoColor=white)
![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)
![React](https://img.shields.io/badge/React-19-61DAFB?logo=react&logoColor=white)
![TypeScript](https://img.shields.io/badge/TypeScript-5.7-3178C6?logo=typescript&logoColor=white)
![Tailwind CSS](https://img.shields.io/badge/Tailwind_CSS-4-06B6D4?logo=tailwindcss&logoColor=white)
![Inertia.js](https://img.shields.io/badge/Inertia.js-3-9553E9?logo=inertia&logoColor=white)

---

## Stack

| Layer         | Teknologi                             |
| ------------- | ------------------------------------- |
| Backend       | Laravel 13, PHP 8.4                   |
| Frontend      | React 19, TypeScript, Tailwind CSS v4 |
| Routing SPA   | Inertia.js v3                         |
| Autentikasi   | Laravel Fortify                       |
| OAuth         | Laravel Socialite (Google)            |
| UI Components | shadcn/ui (Radix primitives)          |
| Testing       | Pest v4                               |
| Database      | SQLite (lokal), PostgreSQL (produksi) |

---

## Fitur

### Autentikasi

- Login dan registrasi dengan email & password
- Login dan registrasi dengan **Google OAuth**
- Reset password via email
- Verifikasi email
- **Two-Factor Authentication (2FA)** dengan TOTP dan kode recovery
- Konfirmasi password untuk aksi sensitif

### Pengaturan Akun

- Update profil (nama, email)
- Ganti password
- Hapus akun
- Kelola akun Google yang terhubung (connect/disconnect)

### Manajemen Tim

- Buat dan kelola beberapa tim
- Undang anggota via email
- Atur peran anggota (admin, member)
- Pindah antar tim (team switcher)
- URL di-scope per tim (`/{team-slug}/...`)

### Developer Experience

- **Laravel Wayfinder** — fungsi TypeScript auto-generated untuk semua route Laravel
- **React Compiler** — optimasi render otomatis tanpa manual `useMemo`/`useCallback`
- Strict TypeScript dengan ESLint + Prettier
- CI via GitHub Actions (lint, format, type-check, test)
- Pest tests untuk semua fitur utama (92 test)

---

## Prerequisites

Pastikan sudah ter-install:

- **PHP** >= 8.4
- **Composer** >= 2
- **Node.js** >= 22
- **npm** >= 10
- **SQLite** (sudah termasuk di PHP biasanya)

Direkomendasikan menggunakan **[Laravel Herd](https://herd.laravel.com)** untuk local development di macOS/Windows.

---

## Instalasi

**1. Clone dan masuk ke direktori:**

```bash
git clone https://github.com/suhendararyadi/app-satu-laravel.git nama-project
cd nama-project
```

**2. Jalankan setup otomatis:**

```bash
composer setup
```

Perintah ini akan: install PHP dependencies, install Node dependencies, copy `.env.example` ke `.env`, generate `APP_KEY`, jalankan migration, dan build assets.

**3. Mulai development server:**

```bash
composer dev
```

Aplikasi berjalan di `http://localhost:8000`.

---

## Konfigurasi

Buka file `.env` dan sesuaikan nilai berikut:

### Wajib

```env
APP_NAME="Nama Aplikasi Kamu"
APP_URL=http://localhost:8000
```

### Google OAuth (opsional, aktifkan jika diperlukan)

Daftarkan aplikasi di [Google Cloud Console](https://console.cloud.google.com) → **APIs & Services → Credentials → Create OAuth 2.0 Client ID**.

```env
GOOGLE_CLIENT_ID=your-client-id.apps.googleusercontent.com
GOOGLE_CLIENT_SECRET=your-client-secret
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
```

Tambahkan `http://localhost:8000/auth/google/callback` ke **Authorized redirect URIs** di Google Console.

### Mail (untuk verifikasi email dan undangan tim)

```env
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=your-username
MAIL_PASSWORD=your-password
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

Untuk development, bisa gunakan [Mailtrap](https://mailtrap.io) atau biarkan default `log` (email ditulis ke `storage/logs/laravel.log`).

---

## Perintah Penting

### Development

```bash
composer dev          # Jalankan semua server sekaligus (artisan + queue + vite)
composer setup        # Setup awal project (jalankan sekali setelah clone)
```

### Testing

```bash
php artisan test      # Jalankan semua test (termasuk pint check)
./vendor/bin/pest     # Jalankan Pest langsung (skip pint)
./vendor/bin/pest --filter "nama test"  # Jalankan test tertentu
```

### Lint & Format

```bash
composer lint         # Fix PHP code style dengan Pint
npm run lint          # Fix TypeScript/TSX dengan ESLint
npm run format        # Format frontend dengan Prettier
npm run types:check   # Cek TypeScript tanpa compile
```

### CI Check (semua sekaligus)

```bash
composer ci:check     # lint → format → types → test
```

---

## Struktur Direktori

```
app/
├── Actions/Fortify/        # Logic registrasi dan update profil
├── Http/Controllers/
│   ├── Auth/               # SocialAuthController (Google OAuth)
│   ├── Settings/           # Profile, Security
│   └── Teams/              # Team, Member, Invitation
├── Models/                 # User, Team, Membership, TeamInvitation, SocialAccount
└── Policies/               # TeamPolicy

resources/js/
├── pages/
│   ├── auth/               # Login, Register, Reset Password, 2FA, dll
│   ├── settings/           # Profile, Security, Appearance
│   └── teams/              # Team list, Team edit
├── components/             # Komponen reusable
├── layouts/                # AppLayout, AuthLayout, SettingsLayout
└── types/                  # TypeScript type definitions

database/migrations/        # Semua migration termasuk social_accounts
routes/
├── web.php                 # Route utama + OAuth
└── settings.php            # Route settings
```

---

## Deployment ke Laravel Cloud

### 1. Push ke GitHub

```bash
git add .
git commit -m "feat: your changes"
git push
```

### 2. Buat project di Laravel Cloud

1. Buka [cloud.laravel.com](https://cloud.laravel.com) dan login dengan GitHub
2. **New Project** → pilih repo → region **Asia Pacific (Singapore)**
3. Di bagian Database, pilih **Serverless PostgreSQL**
4. Klik **Deploy**

### 3. Set environment variables

Tambahkan vars berikut di **Settings → Environment** (vars `APP_ENV`, `APP_URL`, `DB_*` sudah di-inject otomatis oleh Cloud):

```env
APP_KEY=             ← generate dengan: php artisan key:generate --show
APP_NAME=            ← nama aplikasi kamu
LOG_CHANNEL=stderr
LOG_LEVEL=error
SESSION_DRIVER=database
CACHE_STORE=database
QUEUE_CONNECTION=sync
GOOGLE_CLIENT_ID=    ← dari Google Console
GOOGLE_CLIENT_SECRET=← dari Google Console
GOOGLE_REDIRECT_URI= ← https://domain-kamu.laravel.cloud/auth/google/callback
```

### 4. Update Google Console

Tambahkan domain Cloud ke **Authorized redirect URIs** dan **Authorized JavaScript origins** di Google Cloud Console.

---

## Arsitektur Autentikasi Google OAuth

Menggunakan **satu callback URI** untuk dua skenario:

| Skenario         | Kondisi          | Hasil                             |
| ---------------- | ---------------- | --------------------------------- |
| Login / Register | User belum login | Masuk atau buat akun baru         |
| Connect akun     | User sudah login | Hubungkan Google ke akun yang ada |

Data OAuth disimpan di tabel `social_accounts` (terpisah dari `users`) sehingga mendukung multiple provider di masa depan.

**Auto-merge email:** Jika Google OAuth menghasilkan email yang sudah terdaftar, akun otomatis digabungkan tanpa perlu membuat akun baru.

---

## Membuat Project Baru dari Template

1. Buka repo di GitHub
2. Klik **"Use this template"** → **"Create a new repository"**
3. Clone repo baru dan jalankan `composer setup`

---

## Lisensi

[MIT](https://opensource.org/licenses/MIT)
