# Fase 1: CMS & Profil Sekolah — Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Extend the existing Team-based multi-tenant app with school profile management and a public-facing CMS (pages, posts, galleries) for each school.

**Architecture:** Team = School (extend existing, tidak rename). School fields ditambah ke `teams` table. CMS content di-scope ke `team_id`. Public website accessible via `/schools/{slug}/...` atau custom domain.

**Tech Stack:** Laravel 13, React 19, Inertia.js v3, TypeScript, Tailwind CSS v4, Pest, Wayfinder

---

## Context & Decisions

- `Team` model menggunakan `#[Fillable([...])]` attribute (bukan `$fillable` property)
- `Team::getRouteKeyName()` return `'slug'` — route binding pakai slug
- `EnsureTeamMembership::class . ':admin'` — restrict ke Owner & Admin
- Controller akses current team via `$request->user()->currentTeam` (bukan route binding)
- CMS controllers: manual check `abort_if($resource->team_id !== $team->id, 403)`
- Public routes: `{team:slug}` implicit model binding; `abort_if($team->is_personal, 404)` di setiap action
- `ResolveSchoolDomain` middleware di-register via `$middleware->prepend()` di `bootstrap/app.php`
- File storage: `Storage::disk('public')` untuk logo, featured images, gallery images
- Rich text: `<textarea>` biasa (upgrade ke Tiptap di fase berikutnya)
- NPSN: varchar(8) (bukan 10) karena NPSN Indonesia 8 digit

---

## Task 1: SchoolType Enum

- [ ] Buat `app/Enums/SchoolType.php` dengan cases: `SMA`, `SMK`, `MA`
    - Implement `BackedEnum` dengan string values: `'SMA'`, `'SMK'`, `'MA'`
    - Tambahkan method `label(): string` untuk display name Indonesia

**Verification:** `php artisan tinker --execute 'echo \App\Enums\SchoolType::SMA->label();'`

---

## Task 2: Teams Table Migration + Team Model Update + TeamFactory Update

- [ ] Buat migration `add_school_fields_to_teams_table`:
    ```
    npsn               varchar(8) nullable unique
    school_type        string nullable
    address            text nullable
    city               varchar(100) nullable
    province           varchar(100) nullable
    postal_code        varchar(10) nullable
    phone              varchar(20) nullable
    email              varchar(255) nullable
    logo_path          string nullable
    accreditation      varchar(5) nullable
    principal_name     varchar(100) nullable
    founded_year       unsignedSmallInteger nullable
    vision             text nullable
    mission            text nullable
    description        text nullable
    website_theme      string nullable default('default')
    custom_domain      string nullable unique
    ```
- [ ] Update `app/Models/Team.php`:
    - Tambahkan semua field baru ke `#[Fillable([...])]` attribute
    - Tambahkan `casts()` method: `school_type` → `SchoolType::class`
    - Tambahkan relationships: `pages()` HasMany, `posts()` HasMany, `galleries()` HasMany
- [ ] Update `database/factories/TeamFactory.php`:
    - Tambahkan `school()` factory state yang isi semua school fields dengan faker data
- [ ] Tulis test di `tests/Feature/Teams/TeamSchoolProfileTest.php`:
    - School fields tersimpan dengan benar
    - `school_type` di-cast ke `SchoolType` enum
    - Factory `school()` state berfungsi

**Verification:** `php artisan test --compact --filter=TeamSchoolProfileTest`

---

## Task 3: Page Model + Migration + Factory

- [ ] Buat migration `create_pages_table`:
    ```
    id
    team_id         FK → teams (cascadeOnDelete)
    title           varchar(255)
    slug            varchar(255)
    content         longText
    is_published    boolean default(false)
    sort_order      unsignedSmallInteger default(0)
    meta_description varchar(500) nullable
    timestamps
    unique(team_id, slug)
    ```
- [ ] Buat `app/Models/Page.php`:
    - `#[Fillable([...])]` attribute
    - `casts()`: `is_published` → `bool`
    - `team()` BelongsTo relationship
    - `scopePublished(Builder $query)` local scope
- [ ] Buat `database/factories/PageFactory.php`
- [ ] Tulis test di `tests/Feature/CMS/PageModelTest.php`:
    - Factory works
    - `scopePublished` hanya return published pages
    - Unique constraint (team_id, slug) throws exception on duplicate

**Verification:** `php artisan test --compact --filter=PageModelTest`

---

## Task 4: Post Model + Migration + Factory

- [ ] Buat migration `create_posts_table`:
    ```
    id
    team_id             FK → teams (cascadeOnDelete)
    author_id           FK → users (restrictOnDelete)
    title               varchar(255)
    slug                varchar(255)
    excerpt             text nullable
    content             longText
    featured_image_path string nullable
    is_published        boolean default(false)
    published_at        timestamp nullable
    meta_description    varchar(500) nullable
    timestamps
    unique(team_id, slug)
    ```
- [ ] Buat `app/Models/Post.php`:
    - `#[Fillable([...])]` attribute
    - `casts()`: `is_published` → `bool`, `published_at` → `datetime`
    - `team()` BelongsTo, `author()` BelongsTo → User
    - `scopePublished(Builder $query)` local scope
- [ ] Buat `database/factories/PostFactory.php`
- [ ] Tulis test di `tests/Feature/CMS/PostModelTest.php`:
    - Factory works
    - `scopePublished` berfungsi
    - `author()` relationship benar

**Verification:** `php artisan test --compact --filter=PostModelTest`

---

## Task 5: Gallery + GalleryImage Models + Migrations + Factories

- [ ] Buat migration `create_galleries_table`:
    ```
    id
    team_id      FK → teams (cascadeOnDelete)
    title        varchar(255)
    description  text nullable
    is_published boolean default(false)
    timestamps
    ```
- [ ] Buat migration `create_gallery_images_table`:
    ```
    id
    gallery_id  FK → galleries (cascadeOnDelete)
    image_path  string
    caption     varchar(255) nullable
    sort_order  unsignedSmallInteger default(0)
    timestamps
    ```
- [ ] Buat `app/Models/Gallery.php`:
    - `#[Fillable([...])]` attribute
    - `casts()`: `is_published` → `bool`
    - `team()` BelongsTo, `images()` HasMany → GalleryImage
    - `scopePublished(Builder $query)`
- [ ] Buat `app/Models/GalleryImage.php`:
    - `#[Fillable([...])]` attribute
    - `gallery()` BelongsTo relationship
- [ ] Buat `database/factories/GalleryFactory.php` dan `GalleryImageFactory.php`
- [ ] Tulis test di `tests/Feature/CMS/GalleryModelTest.php`:
    - Factory works
    - `images()` relationship works
    - Cascade delete: hapus gallery → gallery images ikut terhapus

**Verification:** `php artisan test --compact --filter=GalleryModelTest`

---

## Task 6: School Profile Backend

- [ ] Buat `app/Http/Requests/School/UpdateSchoolProfileRequest.php`:
    - `authorize()`: return `$this->user()->teamRole($this->user()->currentTeam)->isAtLeast(TeamRole::Admin)`
    - Validation rules:
        ```
        npsn            nullable|string|size:8|unique:teams,npsn,{currentTeam->id}
        school_type     nullable|enum:SchoolType
        address         nullable|string|max:500
        city            nullable|string|max:100
        province        nullable|string|max:100
        postal_code     nullable|string|max:10
        phone           nullable|string|max:20
        email           nullable|email|max:255
        logo            nullable|image|mimes:jpg,jpeg,png,webp|max:2048
        accreditation   nullable|string|max:5
        principal_name  nullable|string|max:100
        founded_year    nullable|integer|min:1900|max:{year}
        vision          nullable|string
        mission         nullable|string
        description     nullable|string
        website_theme   nullable|string|in:default,modern,classic
        custom_domain   nullable|string|max:255|unique:teams,custom_domain,{currentTeam->id}
        ```
- [ ] Buat `app/Http/Controllers/School/SchoolProfileController.php`:
    - `edit(Request $request)`: return `Inertia::render('school/profile', ['team' => $request->user()->currentTeam])`
    - `update(UpdateSchoolProfileRequest $request)`:
        - Handle logo upload: `Storage::disk('public')->put('logos', $request->file('logo'))`, hapus logo lama jika ada
        - `$team->update([...validated data...])`
        - Return `to_route('school.profile.edit')` dengan flash success toast
- [ ] Buat `routes/cms.php`:
    ```php
    Route::middleware(['auth', 'verified', EnsureTeamMembership::class . ':admin'])
        ->prefix('/{current_team}')
        ->name('cms.')
        ->group(function () {
            // School Profile
            Route::get('school/profile', [SchoolProfileController::class, 'edit'])->name('school.profile.edit');
            Route::patch('school/profile', [SchoolProfileController::class, 'update'])->name('school.profile.update');
        });
    ```
- [ ] Tambahkan `require __DIR__.'/cms.php';` di akhir `routes/web.php`
- [ ] Tulis test di `tests/Feature/School/SchoolProfileTest.php`:
    - Owner dapat akses halaman edit
    - Admin dapat akses halaman edit
    - Member (non-admin) mendapat 403
    - Guest mendapat redirect ke login
    - Update profil menyimpan data dengan benar
    - Logo upload: file tersimpan, `logo_path` terupdate
    - Logo lama dihapus saat upload logo baru

**Verification:** `php artisan test --compact --filter=SchoolProfileTest`

---

## Task 7: CMS Pages Backend

- [ ] Buat `app/Http/Requests/CMS/StorePageRequest.php`:
    - `authorize()`: cek `teamRole()->isAtLeast(Admin)`
    - Rules: `title` required string max:255, `content` required string, `is_published` boolean, `sort_order` integer min:0, `meta_description` nullable string max:500
- [ ] Buat `app/Http/Requests/CMS/UpdatePageRequest.php` (rules sama dengan Store)
- [ ] Buat `app/Http/Controllers/CMS/PageController.php`:
    - Semua actions: `$team = $request->user()->currentTeam;`
    - `index`: `$pages = $team->pages()->orderBy('sort_order')->get()`; render `cms/pages/index`
    - `create`: render `cms/pages/create`
    - `store`: generate slug dari title (private helper), `abort_if` slug duplicate, `$team->pages()->create([...])`, redirect `cms.pages.index` dengan flash success
    - `edit(Page $page)`: `abort_if($page->team_id !== $team->id, 403)`, render `cms/pages/edit`
    - `update(UpdatePageRequest, Page $page)`: team check, update, redirect dengan flash
    - `destroy(Request, Page $page)`: team check, delete, redirect dengan flash
- [ ] Tambahkan page resource routes di `routes/cms.php`:
    ```php
    Route::resource('cms/pages', PageController::class)
        ->names('cms.pages')
        ->except(['show']);
    ```
- [ ] Tulis test di `tests/Feature/CMS/PageControllerTest.php`:
    - Index: admin melihat daftar pages timnya saja
    - Store: halaman baru tersimpan, slug auto-generated
    - Store: duplicate slug → error validasi
    - Update: data terupdate
    - Destroy: page terhapus
    - Auth: member mendapat 403

**Verification:** `php artisan test --compact --filter=PageControllerTest`

---

## Task 8: CMS Posts Backend

- [ ] Buat `app/Http/Requests/CMS/StorePostRequest.php`:
    - Rules: `title` required string max:255, `excerpt` nullable string, `content` required string, `featured_image` nullable image max:2048, `is_published` boolean, `published_at` nullable date, `meta_description` nullable string max:500
- [ ] Buat `app/Http/Requests/CMS/UpdatePostRequest.php` (rules sama)
- [ ] Buat `app/Http/Controllers/CMS/PostController.php`:
    - `index`: `$team->posts()->with('author')->latest()->get()`, render `cms/posts/index`
    - `create`: render `cms/posts/create`
    - `store`: slug generation, featured image upload ke `Storage::disk('public')->put('posts', ...)`, `$team->posts()->create([..., 'author_id' => auth()->id()])`, redirect
    - `edit(Post $post)`: team check, render `cms/posts/edit`
    - `update`: team check, handle featured image (upload baru + hapus lama), update, redirect
    - `destroy`: team check, hapus file, delete record, redirect
- [ ] Tambahkan post resource routes di `routes/cms.php`:
    ```php
    Route::resource('cms/posts', PostController::class)
        ->names('cms.posts')
        ->except(['show']);
    ```
- [ ] Tulis test di `tests/Feature/CMS/PostControllerTest.php`:
    - CRUD works
    - Featured image upload tersimpan, path tersimpan di DB
    - Author di-set ke authenticated user
    - Member mendapat 403

**Verification:** `php artisan test --compact --filter=PostControllerTest`

---

## Task 9: CMS Galleries Backend

- [ ] Buat `app/Http/Requests/CMS/StoreGalleryRequest.php`:
    - Rules: `title` required string max:255, `description` nullable string, `is_published` boolean
- [ ] Buat `app/Http/Requests/CMS/UpdateGalleryRequest.php` (rules sama)
- [ ] Buat `app/Http/Controllers/CMS/GalleryController.php`:
    - `index`: `$team->galleries()->with('images')->latest()->get()`, render `cms/galleries/index`
    - `create`: render `cms/galleries/create`
    - `store`: `$team->galleries()->create([...])`, redirect
    - `edit(Gallery $gallery)`: team check, load `images`, render `cms/galleries/edit`
    - `update`: team check, update, redirect
    - `destroy`: team check, hapus semua gallery images dari storage, delete record, redirect
    - `storeImage(Request $request, Gallery $gallery)`: team check, validate `image` required image max:4096, upload, `$gallery->images()->create([...])`, return JSON `{id, image_url, caption, sort_order}`
    - `destroyImage(Request $request, Gallery $gallery, GalleryImage $image)`: team check, `abort_if($image->gallery_id !== $gallery->id, 403)`, hapus file, delete record, return `204`
- [ ] Tambahkan gallery routes di `routes/cms.php`:
    ```php
    Route::resource('cms/galleries', GalleryController::class)
        ->names('cms.galleries')
        ->except(['show']);
    Route::post('cms/galleries/{gallery}/images', [GalleryController::class, 'storeImage'])->name('cms.galleries.images.store');
    Route::delete('cms/galleries/{gallery}/images/{image}', [GalleryController::class, 'destroyImage'])->name('cms.galleries.images.destroy');
    ```
- [ ] Tulis test di `tests/Feature/CMS/GalleryControllerTest.php`:
    - CRUD gallery works
    - `storeImage`: image upload, record tersimpan, JSON response benar
    - `destroyImage`: file terhapus, record terhapus
    - Member mendapat 403

**Verification:** `php artisan test --compact --filter=GalleryControllerTest`

---

## Task 10: Public Website Backend

- [ ] Buat `app/Http/Middleware/ResolveSchoolDomain.php`:
    - Check `$request->getHost()` terhadap `teams.custom_domain`
    - Jika match: rewrite `$request->server->set('REQUEST_URI', '/schools/'.$team->slug.$request->getPathInfo())`
    - Jika tidak match: lanjutkan request normal
- [ ] Register di `bootstrap/app.php`:
    ```php
    ->withMiddleware(function (Middleware $middleware) {
        $middleware->prepend(ResolveSchoolDomain::class);
        // ... existing middleware
    })
    ```
- [ ] Buat `app/Http/Controllers/Public/PublicSchoolController.php`:
    - Semua methods terima `Team $team` (implicit binding via slug)
    - Semua methods: `abort_if($team->is_personal, 404)` di baris pertama
    - `home(Team $team)`: load latest 3 posts (published), load pages (published), render `public/home`
    - `page(Team $team, Page $page)`: `abort_if(!$page->is_published, 404)`, team check, render `public/page-detail`
    - `news(Team $team)`: published posts, paginate(9), render `public/news/index`
    - `post(Team $team, Post $post)`: published check, team check, render `public/news/show`
    - `gallery(Team $team)`: published galleries with images count, render `public/gallery/index`
    - `galleryDetail(Team $team, Gallery $gallery)`: published check, team check, load images, render `public/gallery/show`
    - `contact(Team $team)`: render `public/contact`
- [ ] Buat `routes/public.php`:
    ```php
    Route::prefix('/schools/{team:slug}')
        ->name('public.school.')
        ->group(function () {
            Route::get('/', [PublicSchoolController::class, 'home'])->name('home');
            Route::get('/pages/{page:slug}', [PublicSchoolController::class, 'page'])->name('page');
            Route::get('/news', [PublicSchoolController::class, 'news'])->name('news');
            Route::get('/news/{post:slug}', [PublicSchoolController::class, 'post'])->name('post');
            Route::get('/gallery', [PublicSchoolController::class, 'gallery'])->name('gallery');
            Route::get('/gallery/{gallery}', [PublicSchoolController::class, 'galleryDetail'])->name('gallery.detail');
            Route::get('/contact', [PublicSchoolController::class, 'contact'])->name('contact');
        });
    ```
- [ ] Tambahkan `require __DIR__.'/public.php';` di `routes/web.php`
- [ ] Tulis test di `tests/Feature/Public/PublicSchoolControllerTest.php`:
    - Semua 7 halaman render dengan `200` untuk team non-personal
    - Personal team mendapat `404`
    - Unpublished page/post/gallery mendapat `404`
    - Custom domain resolution: request dengan host custom_domain di-forward ke school routes

**Verification:** `php artisan test --compact --filter=PublicSchoolControllerTest`

---

## Task 11: Frontend Foundation

- [ ] Buat `resources/js/types/school.ts` — TypeScript interfaces:
    ```typescript
    export interface School {
        /* semua team + school fields */
    }
    export interface Page {
        id: number;
        title: string;
        slug: string;
        content: string;
        is_published: boolean;
        sort_order: number;
        meta_description: string | null;
        created_at: string;
        updated_at: string;
    }
    export interface Post {
        id: number;
        title: string;
        slug: string;
        excerpt: string | null;
        content: string;
        featured_image_path: string | null;
        is_published: boolean;
        published_at: string | null;
        author: { id: number; name: string };
        meta_description: string | null;
        created_at: string;
        updated_at: string;
    }
    export interface Gallery {
        id: number;
        title: string;
        description: string | null;
        is_published: boolean;
        images: GalleryImage[];
        images_count?: number;
        created_at: string;
        updated_at: string;
    }
    export interface GalleryImage {
        id: number;
        image_path: string;
        caption: string | null;
        sort_order: number;
    }
    ```
- [ ] Buat `resources/js/layouts/public-layout.tsx`:
    - Navbar dengan nama sekolah + menu navigasi (Home, Halaman, Berita, Galeri, Kontak)
    - Footer sederhana
    - Terima `school: School` dan `pages: Page[]` sebagai props dari Inertia shared data atau page props
- [ ] Update `resources/js/app.tsx` — tambahkan case untuk public layout:
    - `name.startsWith('public/')` → `PublicLayout`
- [ ] Run `npm run build` untuk generate Wayfinder types setelah semua routes ditambahkan

**Verification:** `npm run types:check` — no TypeScript errors

---

## Task 12: School Profile Frontend

- [ ] Buat `resources/js/pages/school/profile.tsx`:
    - Terima props: `team: School` dari Inertia
    - Layout: `AppLayout` + `SettingsLayout` (ikut pola `settings/*` pages)
    - Form menggunakan `useForm` dari Inertia
    - Grid 2-kolom untuk fields:
        - Kiri: Nama Sekolah (readonly, dari team.name), NPSN, Jenis Sekolah (select SchoolType), Akreditasi, Tahun Berdiri, Kepala Sekolah
        - Kanan: Alamat, Kota, Provinsi, Kode Pos, Telepon, Email
    - Full-width: Visi, Misi, Deskripsi (textarea)
    - Logo upload: preview image jika ada, input file
    - Submit dengan `patch` method ke Wayfinder action `cms.school.profile.update`
    - Error handling per field

**Verification:** Visual check + `npm run types:check`

---

## Task 13: CMS Pages Frontend

- [ ] Buat `resources/js/pages/cms/pages/index.tsx`:
    - Terima props: `pages: Page[]`
    - Table dengan kolom: Judul, Slug, Status (badge), Urutan, Actions (Edit, Delete)
    - Tombol "Tambah Halaman" → link ke `create`
    - Delete: konfirmasi dialog → `router.delete()`
- [ ] Buat `resources/js/pages/cms/pages/create.tsx`:
    - Form: Judul, Slug (auto-fill dari judul tapi editable), Konten (textarea), Is Published (checkbox), Sort Order, Meta Description
    - Submit → `router.post()` ke Wayfinder action
- [ ] Buat `resources/js/pages/cms/pages/edit.tsx`:
    - Sama dengan create tapi pre-filled dengan data existing
    - Submit → `router.patch()`

**Verification:** `npm run types:check`

---

## Task 14: CMS Posts Frontend

- [ ] Buat `resources/js/pages/cms/posts/index.tsx`:
    - Table dengan kolom: Judul, Penulis, Status (badge), Tanggal Publish, Actions
    - Tombol "Tulis Artikel"
    - Delete dengan konfirmasi
- [ ] Buat `resources/js/pages/cms/posts/create.tsx`:
    - Form: Judul, Slug (auto-fill), Kutipan (excerpt), Konten (textarea), Featured Image (file input + preview), Is Published (checkbox), Published At (datetime-local input), Meta Description
    - Submit dengan `multipart/form-data` (karena ada file upload) via `useForm`
- [ ] Buat `resources/js/pages/cms/posts/edit.tsx`:
    - Sama dengan create, tampilkan featured image lama jika ada
    - Method `_method: 'PATCH'` untuk form submission

**Verification:** `npm run types:check`

---

## Task 15: CMS Galleries Frontend

- [ ] Buat `resources/js/pages/cms/galleries/index.tsx`:
    - Grid card view untuk setiap gallery
    - Setiap card: thumbnail (gambar pertama atau placeholder), judul, jumlah gambar, status badge, actions
    - Tombol "Buat Galeri"
- [ ] Buat `resources/js/pages/cms/galleries/create.tsx`:
    - Form: Judul, Deskripsi, Is Published checkbox
- [ ] Buat `resources/js/pages/cms/galleries/edit.tsx`:
    - Form update gallery (title, description, published)
    - Grid gambar existing dengan tombol delete per gambar (XHR delete via `router.delete()`)
    - Upload section: file input untuk tambah gambar baru (XHR post)
    - Optimistic update untuk preview upload

**Verification:** `npm run types:check`

---

## Task 16: Public Website Frontend

- [ ] Buat `resources/js/pages/public/home.tsx`:
    - Hero section: nama sekolah, deskripsi singkat, logo
    - Seksi "Berita Terbaru": grid 3 post cards (featured image, judul, excerpt, tanggal)
    - Seksi "Halaman": list links ke static pages
- [ ] Buat `resources/js/pages/public/page-detail.tsx`:
    - Layout penuh dengan judul + konten (render sebagai `dangerouslySetInnerHTML` atau plain text)
- [ ] Buat `resources/js/pages/public/news/index.tsx`:
    - Grid post cards dengan pagination
- [ ] Buat `resources/js/pages/public/news/show.tsx`:
    - Featured image full-width, judul, meta (author, tanggal), konten
- [ ] Buat `resources/js/pages/public/gallery/index.tsx`:
    - Grid gallery cards dengan thumbnail + judul + jumlah gambar
- [ ] Buat `resources/js/pages/public/gallery/show.tsx`:
    - Masonry/grid layout untuk semua gambar dalam gallery
    - Lightbox sederhana (klik gambar → fullscreen)
- [ ] Buat `resources/js/pages/public/contact.tsx`:
    - Info kontak: alamat, telepon, email sekolah (dari team data)
    - Google Maps embed placeholder (iframe dengan koordinat atau teks "Peta belum tersedia")

**Verification:** `npm run types:check` + visual check di browser

---

## Final Verification

- [ ] `php artisan test --compact` — semua test hijau
- [ ] `npm run lint:check` — no ESLint errors
- [ ] `npm run types:check` — no TypeScript errors
- [ ] `npm run format:check` — no Prettier errors
- [ ] `vendor/bin/pint --test` — no Pint errors
- [ ] Jalankan `composer dev` dan test manual di browser: school profile edit, CMS CRUD, public website
- [ ] Commit dengan message: `feat: fase 1 - CMS & profil sekolah`
