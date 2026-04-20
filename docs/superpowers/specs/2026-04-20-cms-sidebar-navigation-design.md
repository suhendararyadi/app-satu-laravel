# Design: CMS Sidebar Navigation

**Date:** 2026-04-20
**Status:** Approved

## Overview

Tambah menu CMS ke sidebar navigasi utama (`AppSidebar`) agar pengguna bisa mengakses fitur Fase 1 (Profil Sekolah, Halaman, Artikel, Galeri) langsung dari sidebar tanpa harus mengetik URL manual.

## Architecture

### Komponen yang Diubah / Dibuat

| File                                      | Action | Keterangan                          |
| ----------------------------------------- | ------ | ----------------------------------- |
| `resources/js/types/navigation.ts`        | Edit   | Tambah type `NavGroup`              |
| `resources/js/components/nav-group.tsx`   | Create | Komponen collapsible group baru     |
| `resources/js/components/app-sidebar.tsx` | Edit   | Tambah data + render CMS navigation |

### Type Baru: `NavGroup`

```ts
export type NavGroup = {
    title: string;
    icon?: LucideIcon | null;
    items: NavItem[];
};
```

`NavGroup` berbeda dari `NavItem` — ia punya `items` (array of `NavItem`) dan tidak punya `href` sendiri. Ia hanya berfungsi sebagai collapsible parent.

### Komponen Baru: `NavGroup`

Komponen `NavGroup` merender satu atau lebih `NavGroup` menggunakan shadcn `Collapsible` + `SidebarMenuSub`:

```
SidebarGroup
  SidebarGroupLabel  ← "Manajemen Konten"
  SidebarMenu
    Collapsible (per group)
      SidebarMenuItem
        CollapsibleTrigger → SidebarMenuButton (parent row, dengan icon + chevron)
        CollapsibleContent
          SidebarMenuSub
            SidebarMenuSubItem (per child NavItem)
              SidebarMenuSubButton → Link
```

- Active state parent: aktif jika salah satu child sedang aktif (menggunakan `useCurrentUrl`)
- Collapsible default: `defaultOpen={true}` agar item langsung terlihat saat pertama dibuka

### Struktur Sidebar Akhir

```
AppSidebar
├── [Platform]
│    └── Dashboard
│
├── [Sekolah]          ← NavMain baru, label "Sekolah"
│    └── Profil Sekolah
│
├── [Konten]           ← NavGroup, label "Konten"
│    └── ▶ Manajemen Konten (collapsible)
│           ├── Halaman
│           ├── Artikel
│           └── Galeri
│
└── Footer (Repository · Docs · NavUser)
```

**Catatan:** `NavMain` saat ini memiliki label hardcoded `"Platform"`. Label ini akan tetap untuk bagian Dashboard. Bagian Profil Sekolah akan menggunakan komponen `NavMain` kedua dengan label berbeda, atau alternatifnya dirender langsung di `AppSidebar` menggunakan `SidebarGroup` terpisah.

## Data (di `app-sidebar.tsx`)

```ts
// Flat item - Profil Sekolah
const schoolNavItems: NavItem[] = [
    {
        title: 'Profil Sekolah',
        href: SchoolProfileController.edit.url(slug),
        icon: School,
    },
];

// Collapsible group - CMS
const cmsNavGroups: NavGroup[] = [
    {
        title: 'Manajemen Konten',
        icon: FolderOpen,
        items: [
            {
                title: 'Halaman',
                href: PageController.index.url(slug),
                icon: FileText,
            },
            {
                title: 'Artikel',
                href: PostController.index.url(slug),
                icon: Newspaper,
            },
            {
                title: 'Galeri',
                href: GalleryController.index.url(slug),
                icon: Images,
            },
        ],
    },
];
```

Icons dari `lucide-react`: `School`, `FolderOpen`, `FileText`, `Newspaper`, `Images`.

## Wayfinder

URL di-generate melalui Wayfinder actions yang sudah ada:

- `SchoolProfileController.edit.url(slug)` — `@/actions/App/Http/Controllers/School/SchoolProfileController`
- `PageController.index.url(slug)` — `@/actions/App/Http/Controllers/CMS/PageController`
- `PostController.index.url(slug)` — `@/actions/App/Http/Controllers/CMS/PostController`
- `GalleryController.index.url(slug)` — `@/actions/App/Http/Controllers/CMS/GalleryController`

## Error Handling

- Jika `currentTeam` null (edge case), semua href fallback ke `'/'`

## Testing

Tidak ada test PHP yang perlu dibuat — ini adalah perubahan frontend-only. Verifikasi dilakukan manual dengan membuka browser dan memastikan:

1. Semua item muncul di sidebar
2. Active state bekerja saat halaman CMS dibuka
3. Collapsible bisa dibuka/tutup
4. URL-nya benar untuk setiap item
