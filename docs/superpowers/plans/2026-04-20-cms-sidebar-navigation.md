# CMS Sidebar Navigation Implementation Plan

**Status:** Complete
**Completed:** 2026-04-20

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Tambah menu CMS ke sidebar navigasi utama agar pengguna bisa mengakses Profil Sekolah, Halaman, Artikel, dan Galeri langsung dari sidebar.

**Architecture:** Extend `NavItem` type dengan `NavGroup` baru, buat komponen `NavGroups` untuk collapsible section, update `AppSidebar` dengan data CMS. Tidak ada perubahan backend.

**Tech Stack:** React 19, Inertia.js v3, Tailwind CSS v4, Lucide React, shadcn/ui Sidebar + Collapsible, Laravel Wayfinder

---

### Task 1: Tambah `NavGroup` type + extend `NavMain` dengan `label` prop

**Files:**

- Modify: `resources/js/types/navigation.ts`
- Modify: `resources/js/components/nav-main.tsx`

- [ ] **Step 1: Tambah `NavGroup` type ke `navigation.ts`**

Ganti seluruh isi `resources/js/types/navigation.ts` dengan:

```ts
import type { InertiaLinkProps } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';

export type BreadcrumbItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
};

export type NavItem = {
    title: string;
    href: NonNullable<InertiaLinkProps['href']>;
    icon?: LucideIcon | null;
    isActive?: boolean;
};

export type NavGroup = {
    title: string;
    icon?: LucideIcon | null;
    items: NavItem[];
};
```

- [ ] **Step 2: Tambah `label` prop ke `NavMain`**

Ganti seluruh isi `resources/js/components/nav-main.tsx` dengan:

```tsx
import { Link } from '@inertiajs/react';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavItem } from '@/types';

export function NavMain({
    items = [],
    label = 'Platform',
}: {
    items: NavItem[];
    label?: string;
}) {
    const { isCurrentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            <SidebarGroupLabel>{label}</SidebarGroupLabel>
            <SidebarMenu>
                {items.map((item) => (
                    <SidebarMenuItem key={item.title}>
                        <SidebarMenuButton
                            asChild
                            isActive={isCurrentUrl(item.href)}
                            tooltip={{ children: item.title }}
                        >
                            <Link href={item.href} prefetch>
                                {item.icon && <item.icon />}
                                <span>{item.title}</span>
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                ))}
            </SidebarMenu>
        </SidebarGroup>
    );
}
```

- [ ] **Step 3: Pastikan TypeScript tidak error**

```bash
npm run types:check
```

Expected: no errors.

- [ ] **Step 4: Commit**

```bash
git add resources/js/types/navigation.ts resources/js/components/nav-main.tsx
git commit -m "feat: add NavGroup type and label prop to NavMain"
```

---

### Task 2: Buat komponen `NavGroups`

**Files:**

- Create: `resources/js/components/nav-groups.tsx`

- [ ] **Step 1: Buat file `nav-groups.tsx`**

Buat `resources/js/components/nav-groups.tsx` dengan isi:

```tsx
import { Link } from '@inertiajs/react';
import { ChevronRight } from 'lucide-react';
import {
    Collapsible,
    CollapsibleContent,
    CollapsibleTrigger,
} from '@/components/ui/collapsible';
import {
    SidebarGroup,
    SidebarGroupLabel,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
    SidebarMenuSub,
    SidebarMenuSubButton,
    SidebarMenuSubItem,
} from '@/components/ui/sidebar';
import { useCurrentUrl } from '@/hooks/use-current-url';
import type { NavGroup } from '@/types';

export function NavGroups({
    groups = [],
    label,
}: {
    groups: NavGroup[];
    label?: string;
}) {
    const { isCurrentOrParentUrl } = useCurrentUrl();

    return (
        <SidebarGroup className="px-2 py-0">
            {label && <SidebarGroupLabel>{label}</SidebarGroupLabel>}
            <SidebarMenu>
                {groups.map((group) => {
                    const isGroupActive = group.items.some((item) =>
                        isCurrentOrParentUrl(item.href),
                    );

                    return (
                        <Collapsible
                            key={group.title}
                            defaultOpen={isGroupActive}
                            className="group/collapsible"
                        >
                            <SidebarMenuItem>
                                <CollapsibleTrigger asChild>
                                    <SidebarMenuButton
                                        isActive={isGroupActive}
                                        tooltip={{ children: group.title }}
                                    >
                                        {group.icon && <group.icon />}
                                        <span>{group.title}</span>
                                        <ChevronRight className="ml-auto transition-transform group-data-[state=open]/collapsible:rotate-90" />
                                    </SidebarMenuButton>
                                </CollapsibleTrigger>
                                <CollapsibleContent>
                                    <SidebarMenuSub>
                                        {group.items.map((item) => (
                                            <SidebarMenuSubItem
                                                key={item.title}
                                            >
                                                <SidebarMenuSubButton
                                                    asChild
                                                    isActive={isCurrentOrParentUrl(
                                                        item.href,
                                                    )}
                                                >
                                                    <Link
                                                        href={item.href}
                                                        prefetch
                                                    >
                                                        {item.icon && (
                                                            <item.icon />
                                                        )}
                                                        <span>
                                                            {item.title}
                                                        </span>
                                                    </Link>
                                                </SidebarMenuSubButton>
                                            </SidebarMenuSubItem>
                                        ))}
                                    </SidebarMenuSub>
                                </CollapsibleContent>
                            </SidebarMenuItem>
                        </Collapsible>
                    );
                })}
            </SidebarMenu>
        </SidebarGroup>
    );
}
```

- [ ] **Step 2: Pastikan TypeScript tidak error**

```bash
npm run types:check
```

Expected: no errors.

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/nav-groups.tsx
git commit -m "feat: add NavGroups collapsible sidebar component"
```

---

### Task 3: Update `AppSidebar` dengan data CMS

**Files:**

- Modify: `resources/js/components/app-sidebar.tsx`

- [ ] **Step 1: Ganti seluruh isi `app-sidebar.tsx`**

```tsx
import { Link, usePage } from '@inertiajs/react';
import {
    BookOpen,
    FileText,
    FolderGit2,
    Images,
    LayoutGrid,
    Newspaper,
    School,
} from 'lucide-react';
import { NavGroups } from '@/components/nav-groups';
import AppLogo from '@/components/app-logo';
import { NavFooter } from '@/components/nav-footer';
import { NavMain } from '@/components/nav-main';
import { NavUser } from '@/components/nav-user';
import { TeamSwitcher } from '@/components/team-switcher';
import {
    Sidebar,
    SidebarContent,
    SidebarFooter,
    SidebarHeader,
    SidebarMenu,
    SidebarMenuButton,
    SidebarMenuItem,
} from '@/components/ui/sidebar';
import GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
import PostController from '@/actions/App/Http/Controllers/CMS/PostController';
import SchoolProfileController from '@/actions/App/Http/Controllers/School/SchoolProfileController';
import { dashboard } from '@/routes';
import type { NavGroup, NavItem } from '@/types';

export function AppSidebar() {
    const page = usePage();
    const slug = page.props.currentTeam?.slug ?? '';
    const dashboardUrl = slug ? dashboard(slug) : '/';

    const mainNavItems: NavItem[] = [
        {
            title: 'Dashboard',
            href: dashboardUrl,
            icon: LayoutGrid,
        },
    ];

    const schoolNavItems: NavItem[] = [
        {
            title: 'Profil Sekolah',
            href: slug ? SchoolProfileController.edit.url(slug) : '/',
            icon: School,
        },
    ];

    const cmsNavGroups: NavGroup[] = [
        {
            title: 'Manajemen Konten',
            icon: FileText,
            items: [
                {
                    title: 'Halaman',
                    href: slug ? PageController.index.url(slug) : '/',
                    icon: FileText,
                },
                {
                    title: 'Artikel',
                    href: slug ? PostController.index.url(slug) : '/',
                    icon: Newspaper,
                },
                {
                    title: 'Galeri',
                    href: slug ? GalleryController.index.url(slug) : '/',
                    icon: Images,
                },
            ],
        },
    ];

    const footerNavItems: NavItem[] = [
        {
            title: 'Repository',
            href: 'https://github.com/laravel/react-starter-kit',
            icon: FolderGit2,
        },
        {
            title: 'Documentation',
            href: 'https://laravel.com/docs/starter-kits#react',
            icon: BookOpen,
        },
    ];

    return (
        <Sidebar collapsible="icon" variant="inset">
            <SidebarHeader>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <SidebarMenuButton size="lg" asChild>
                            <Link href={dashboardUrl} prefetch>
                                <AppLogo />
                            </Link>
                        </SidebarMenuButton>
                    </SidebarMenuItem>
                </SidebarMenu>
                <SidebarMenu>
                    <SidebarMenuItem>
                        <TeamSwitcher />
                    </SidebarMenuItem>
                </SidebarMenu>
            </SidebarHeader>

            <SidebarContent>
                <NavMain items={mainNavItems} />
                <NavMain items={schoolNavItems} label="Sekolah" />
                <NavGroups groups={cmsNavGroups} label="Konten" />
            </SidebarContent>

            <SidebarFooter>
                <NavFooter items={footerNavItems} className="mt-auto" />
                <NavUser />
            </SidebarFooter>
        </Sidebar>
    );
}
```

- [ ] **Step 2: Pastikan TypeScript tidak error**

```bash
npm run types:check
```

Expected: no errors.

- [ ] **Step 3: Jalankan lint**

```bash
npm run lint
```

Expected: no errors.

- [ ] **Step 4: Jalankan PHP tests untuk pastikan tidak ada regression**

```bash
php artisan test --compact
```

Expected: `145 passed`.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/app-sidebar.tsx
git commit -m "feat: add CMS navigation to sidebar"
```

---

### Verification

Setelah semua task selesai, buka browser dan verifikasi:

1. Login → buka `http://app-satu.test/{team-slug}/dashboard`
2. Sidebar menampilkan: Dashboard, Profil Sekolah, dan grup "Manajemen Konten"
3. Klik "Manajemen Konten" → collapse/expand berjalan
4. Sub-items (Halaman, Artikel, Galeri) masing-masing mengarah ke URL yang benar
5. Active state highlight saat halaman CMS dibuka
6. Saat sidebar collapse ke icon mode, tooltip muncul saat hover
