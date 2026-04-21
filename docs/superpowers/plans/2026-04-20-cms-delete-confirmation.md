# CMS Delete Confirmation Implementation Plan

**Status:** Complete
**Completed:** 2026-04-20

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace all four `window.confirm()` calls in CMS pages with a styled AlertDialog confirmation modal that matches the app's design system.

**Architecture:** Install shadcn's `AlertDialog` primitive, wrap it in a single reusable `ConfirmDeleteDialog` component, then update the four CMS pages to use the new component with a two-step state pattern (open pending-item dialog → execute delete on confirm).

**Tech Stack:** React 19, TypeScript, shadcn/ui (AlertDialog), Inertia.js v3, Tailwind CSS v4

---

## File Map

| File                                                | Action                    | Responsibility                                  |
| --------------------------------------------------- | ------------------------- | ----------------------------------------------- |
| `resources/js/components/ui/alert-dialog.tsx`       | CREATE (shadcn-generated) | AlertDialog primitives                          |
| `resources/js/components/ui/button.tsx`             | OVERWRITE (shadcn update) | Minor style tweaks + new size variants          |
| `resources/js/components/confirm-delete-dialog.tsx` | CREATE                    | Reusable wrapper around AlertDialog             |
| `resources/js/pages/cms/pages/index.tsx`            | MODIFY                    | Replace window.confirm with ConfirmDeleteDialog |
| `resources/js/pages/cms/posts/index.tsx`            | MODIFY                    | Replace window.confirm with ConfirmDeleteDialog |
| `resources/js/pages/cms/galleries/index.tsx`        | MODIFY                    | Replace window.confirm with ConfirmDeleteDialog |
| `resources/js/pages/cms/galleries/edit.tsx`         | MODIFY                    | Replace window.confirm with ConfirmDeleteDialog |

---

## Task 1: Install shadcn AlertDialog

**Files:**

- Create: `resources/js/components/ui/alert-dialog.tsx`
- Overwrite: `resources/js/components/ui/button.tsx` (minor style updates from shadcn)

- [ ] **Step 1: Run shadcn add alert-dialog**

```bash
npx shadcn@latest add alert-dialog --yes
```

Expected output:

```
✔ resources/js/components/ui/alert-dialog.tsx  create
✔ resources/js/components/ui/button.tsx        overwrite
```

- [ ] **Step 2: Verify files were created/updated**

```bash
ls resources/js/components/ui/alert-dialog.tsx
```

Expected: file exists.

- [ ] **Step 3: Run TypeScript check to confirm no new errors**

```bash
npm run types:check 2>&1 | grep -c "error TS"
```

Expected: same count as before (18) — no new errors from the shadcn install.

- [ ] **Step 4: Run existing CMS tests to confirm nothing broke**

```bash
php artisan test --compact tests/Feature/CMS/
```

Expected: 32 passed.

- [ ] **Step 5: Commit**

```bash
git add resources/js/components/ui/alert-dialog.tsx resources/js/components/ui/button.tsx
git commit -m "chore: add shadcn alert-dialog component"
```

---

## Task 2: Create ConfirmDeleteDialog component

**Files:**

- Create: `resources/js/components/confirm-delete-dialog.tsx`

- [ ] **Step 1: Create the component**

Create `resources/js/components/confirm-delete-dialog.tsx` with this content:

```tsx
import {
    AlertDialog,
    AlertDialogAction,
    AlertDialogCancel,
    AlertDialogContent,
    AlertDialogDescription,
    AlertDialogFooter,
    AlertDialogHeader,
    AlertDialogTitle,
} from '@/components/ui/alert-dialog';

interface Props {
    open: boolean;
    onOpenChange: (open: boolean) => void;
    title: string;
    description?: string;
    onConfirm: () => void;
    processing?: boolean;
}

export default function ConfirmDeleteDialog({
    open,
    onOpenChange,
    title,
    description = 'Tindakan ini tidak bisa dibatalkan.',
    onConfirm,
    processing = false,
}: Props) {
    return (
        <AlertDialog open={open} onOpenChange={onOpenChange}>
            <AlertDialogContent>
                <AlertDialogHeader>
                    <AlertDialogTitle>{title}</AlertDialogTitle>
                    <AlertDialogDescription>
                        {description}
                    </AlertDialogDescription>
                </AlertDialogHeader>
                <AlertDialogFooter>
                    <AlertDialogCancel disabled={processing}>
                        Batal
                    </AlertDialogCancel>
                    <AlertDialogAction
                        onClick={onConfirm}
                        disabled={processing}
                        className="bg-destructive text-white hover:bg-destructive/90"
                    >
                        Hapus
                    </AlertDialogAction>
                </AlertDialogFooter>
            </AlertDialogContent>
        </AlertDialog>
    );
}
```

- [ ] **Step 2: Run TypeScript check to confirm no type errors**

```bash
npm run types:check 2>&1 | grep "confirm-delete-dialog"
```

Expected: no output (no errors in the new file).

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/confirm-delete-dialog.tsx
git commit -m "feat: add ConfirmDeleteDialog reusable component"
```

---

## Task 3: Update cms/pages/index.tsx

**Files:**

- Modify: `resources/js/pages/cms/pages/index.tsx`

- [ ] **Step 1: Replace the file content**

Replace `resources/js/pages/cms/pages/index.tsx` with:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Page } from '@/types/school';

interface Props {
    pages: Page[];
}

export default function CmsPagesIndex({ pages }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingPage, setPendingPage] = useState<Page | null>(null);

    function handleDelete(page: Page) {
        setPendingPage(page);
        setConfirmOpen(true);
    }

    function executeDelete() {
        if (!pendingPage) return;

        router.delete(
            PageController.destroy.url({
                current_team: teamSlug,
                page: pendingPage.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => setConfirmOpen(false),
            },
        );
    }

    return (
        <>
            <Head title="Halaman CMS" />

            <div className="px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Halaman"
                        description="Kelola halaman statis website sekolah"
                    />
                    <Button asChild>
                        <Link href={PageController.create.url(teamSlug)}>
                            Tambah Halaman
                        </Link>
                    </Button>
                </div>

                <div className="mt-6 overflow-hidden rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    Judul
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Slug
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Urutan
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pages.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada halaman.
                                    </td>
                                </tr>
                            )}
                            {pages.map((page) => (
                                <tr key={page.id} className="hover:bg-muted/50">
                                    <td className="px-4 py-3 font-medium">
                                        {page.title}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {page.slug}
                                    </td>
                                    <td className="px-4 py-3">
                                        {page.is_published ? (
                                            <Badge variant="default">
                                                Terbit
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                Draft
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {page.sort_order}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={PageController.edit.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            page: page.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() =>
                                                    handleDelete(page)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title={`Hapus halaman "${pendingPage?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check on this file**

```bash
npm run types:check 2>&1 | grep "cms/pages/index"
```

Expected: no output (no errors).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/cms/pages/index.tsx
git commit -m "feat: use ConfirmDeleteDialog in cms/pages"
```

---

## Task 4: Update cms/posts/index.tsx

**Files:**

- Modify: `resources/js/pages/cms/posts/index.tsx`

- [ ] **Step 1: Replace the file content**

Replace `resources/js/pages/cms/posts/index.tsx` with:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import * as PostController from '@/actions/App/Http/Controllers/CMS/PostController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Post } from '@/types/school';

interface Props {
    posts: Post[];
}

export default function CmsPostsIndex({ posts }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingPost, setPendingPost] = useState<Post | null>(null);

    function handleDelete(post: Post) {
        setPendingPost(post);
        setConfirmOpen(true);
    }

    function executeDelete() {
        if (!pendingPost) return;

        router.delete(
            PostController.destroy.url({
                current_team: teamSlug,
                post: pendingPost.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => setConfirmOpen(false),
            },
        );
    }

    return (
        <>
            <Head title="Artikel CMS" />

            <div className="px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Artikel"
                        description="Kelola artikel dan berita website sekolah"
                    />
                    <Button asChild>
                        <Link href={PostController.create.url(teamSlug)}>
                            Tulis Artikel
                        </Link>
                    </Button>
                </div>

                <div className="mt-6 overflow-hidden rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">
                                    Judul
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Penulis
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Status
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Tanggal Publish
                                </th>
                                <th className="px-4 py-3 text-left font-medium">
                                    Aksi
                                </th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {posts.length === 0 && (
                                <tr>
                                    <td
                                        colSpan={5}
                                        className="px-4 py-6 text-center text-muted-foreground"
                                    >
                                        Belum ada artikel.
                                    </td>
                                </tr>
                            )}
                            {posts.map((post) => (
                                <tr key={post.id} className="hover:bg-muted/50">
                                    <td className="px-4 py-3 font-medium">
                                        {post.title}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {post.author.name}
                                    </td>
                                    <td className="px-4 py-3">
                                        {post.is_published ? (
                                            <Badge variant="default">
                                                Terbit
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                Draft
                                            </Badge>
                                        )}
                                    </td>
                                    <td className="px-4 py-3 text-muted-foreground">
                                        {post.published_at ?? '-'}
                                    </td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={PostController.edit.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            post: post.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() =>
                                                    handleDelete(post)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title={`Hapus artikel "${pendingPost?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check on this file**

```bash
npm run types:check 2>&1 | grep "cms/posts/index"
```

Expected: no output (no errors).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/cms/posts/index.tsx
git commit -m "feat: use ConfirmDeleteDialog in cms/posts"
```

---

## Task 5: Update cms/galleries/index.tsx

**Files:**

- Modify: `resources/js/pages/cms/galleries/index.tsx`

- [ ] **Step 1: Replace the file content**

Replace `resources/js/pages/cms/galleries/index.tsx` with:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import * as GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Gallery } from '@/types/school';

interface Props {
    galleries: Gallery[];
}

export default function CmsGalleriesIndex({ galleries }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingGallery, setPendingGallery] = useState<Gallery | null>(null);

    function handleDelete(gallery: Gallery) {
        setPendingGallery(gallery);
        setConfirmOpen(true);
    }

    function executeDelete() {
        if (!pendingGallery) return;

        router.delete(
            GalleryController.destroy.url({
                current_team: teamSlug,
                gallery: pendingGallery.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => setConfirmOpen(false),
            },
        );
    }

    return (
        <>
            <Head title="Galeri CMS" />

            <div className="px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading
                        title="Galeri"
                        description="Kelola galeri foto website sekolah"
                    />
                    <Button asChild>
                        <Link href={GalleryController.create.url(teamSlug)}>
                            Buat Galeri
                        </Link>
                    </Button>
                </div>

                {galleries.length === 0 ? (
                    <p className="mt-6 text-center text-muted-foreground">
                        Belum ada galeri.
                    </p>
                ) : (
                    <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {galleries.map((gallery) => {
                            const thumbnail = gallery.images?.[0];
                            const imageCount =
                                gallery.images_count ??
                                gallery.images?.length ??
                                0;

                            return (
                                <div
                                    key={gallery.id}
                                    className="overflow-hidden rounded-lg border"
                                >
                                    {thumbnail ? (
                                        <img
                                            src={
                                                '/storage/' +
                                                thumbnail.image_path
                                            }
                                            alt={gallery.title}
                                            className="h-40 w-full object-cover"
                                        />
                                    ) : (
                                        <div className="h-40 w-full bg-muted" />
                                    )}

                                    <div className="p-4">
                                        <h3 className="font-semibold">
                                            {gallery.title}
                                        </h3>
                                        <p className="mt-1 text-sm text-muted-foreground">
                                            {imageCount} foto
                                        </p>

                                        <div className="mt-2">
                                            {gallery.is_published ? (
                                                <Badge variant="default">
                                                    Terbit
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Draft
                                                </Badge>
                                            )}
                                        </div>

                                        <div className="mt-4 flex items-center gap-2">
                                            <Button
                                                asChild
                                                size="sm"
                                                variant="outline"
                                            >
                                                <Link
                                                    href={GalleryController.edit.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            gallery: gallery.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() =>
                                                    handleDelete(gallery)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </div>
                                </div>
                            );
                        })}
                    </div>
                )}
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title={`Hapus galeri "${pendingGallery?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check on this file**

```bash
npm run types:check 2>&1 | grep "cms/galleries/index"
```

Expected: no output (no errors).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/cms/galleries/index.tsx
git commit -m "feat: use ConfirmDeleteDialog in cms/galleries"
```

---

## Task 6: Update cms/galleries/edit.tsx (image delete)

**Files:**

- Modify: `resources/js/pages/cms/galleries/edit.tsx`

- [ ] **Step 1: Replace the file content**

Replace `resources/js/pages/cms/galleries/edit.tsx` with:

```tsx
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import * as GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Gallery, GalleryImage } from '@/types/school';

interface Props {
    gallery: Gallery;
}

export default function CmsGalleriesEdit({ gallery }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        title: gallery.title,
        description: gallery.description ?? '',
        is_published: gallery.is_published,
    });

    const [imageFile, setImageFile] = useState<File | null>(null);
    const [caption, setCaption] = useState('');

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [pendingImage, setPendingImage] = useState<GalleryImage | null>(null);

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.put(
            GalleryController.update.url({
                current_team: teamSlug,
                gallery: gallery.id,
            }),
        );
    }

    function uploadImage(e: React.FormEvent) {
        e.preventDefault();

        const data = new FormData();

        if (imageFile) {
            data.append('image', imageFile);
        }

        if (caption) {
            data.append('caption', caption);
        }

        router.post(
            GalleryController.storeImage.url({
                current_team: teamSlug,
                gallery: gallery.id,
            }),
            data,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => {
                    setImageFile(null);
                    setCaption('');
                },
            },
        );
    }

    function handleDeleteImage(image: GalleryImage) {
        setPendingImage(image);
        setConfirmOpen(true);
    }

    function executeDeleteImage() {
        if (!pendingImage) return;

        router.delete(
            GalleryController.destroyImage.url({
                current_team: teamSlug,
                gallery: gallery.id,
                image: pendingImage.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => setConfirmOpen(false),
            },
        );
    }

    return (
        <>
            <Head title="Edit Galeri" />

            <div className="px-4 py-6">
                <Heading
                    title="Edit Galeri"
                    description="Ubah informasi galeri dan kelola foto"
                />

                <form onSubmit={submit} className="mt-6 max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) =>
                                form.setData('title', e.target.value)
                            }
                            placeholder="Judul galeri"
                            required
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="description">Deskripsi</Label>
                        <textarea
                            id="description"
                            value={form.data.description}
                            onChange={(e) =>
                                form.setData('description', e.target.value)
                            }
                            placeholder="Deskripsi galeri (opsional)"
                            rows={3}
                            className="flex min-h-[80px] w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background placeholder:text-muted-foreground focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.description} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="is_published"
                            checked={form.data.is_published}
                            onCheckedChange={(checked) =>
                                form.setData('is_published', checked === true)
                            }
                        />
                        <Label htmlFor="is_published">Diterbitkan</Label>
                        <InputError message={form.errors.is_published} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={GalleryController.index.url(teamSlug)}>
                                Batal
                            </Link>
                        </Button>
                    </div>
                </form>

                <div className="mt-10">
                    <h2 className="text-lg font-semibold">Foto Galeri</h2>

                    {gallery.images.length === 0 ? (
                        <p className="mt-4 text-sm text-muted-foreground">
                            Belum ada foto.
                        </p>
                    ) : (
                        <div className="mt-4 grid grid-cols-2 gap-4 sm:grid-cols-3 lg:grid-cols-4">
                            {gallery.images.map((image) => (
                                <div
                                    key={image.id}
                                    className="overflow-hidden rounded-md border"
                                >
                                    <img
                                        src={'/storage/' + image.image_path}
                                        alt={image.caption ?? ''}
                                        className="h-32 w-full object-cover"
                                    />
                                    <div className="p-2">
                                        {image.caption && (
                                            <p className="mb-2 truncate text-xs text-muted-foreground">
                                                {image.caption}
                                            </p>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            className="w-full"
                                            onClick={() =>
                                                handleDeleteImage(image)
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </div>
                                </div>
                            ))}
                        </div>
                    )}

                    <div className="mt-6 max-w-md">
                        <h3 className="font-medium">Upload Foto Baru</h3>
                        <form onSubmit={uploadImage} className="mt-4 space-y-4">
                            <div className="grid gap-2">
                                <Label htmlFor="image_file">Pilih Foto</Label>
                                <input
                                    id="image_file"
                                    type="file"
                                    accept="image/*"
                                    onChange={(e) =>
                                        setImageFile(
                                            e.target.files?.[0] ?? null,
                                        )
                                    }
                                    className="flex w-full rounded-md border border-input bg-background px-3 py-2 text-sm ring-offset-background focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="caption">
                                    Keterangan (opsional)
                                </Label>
                                <Input
                                    id="caption"
                                    value={caption}
                                    onChange={(e) => setCaption(e.target.value)}
                                    placeholder="Keterangan foto"
                                />
                            </div>

                            <Button type="submit" disabled={!imageFile}>
                                Upload
                            </Button>
                        </form>
                    </div>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                title="Hapus foto ini?"
                onConfirm={executeDeleteImage}
            />
        </>
    );
}
```

- [ ] **Step 2: Run TypeScript check on this file**

```bash
npm run types:check 2>&1 | grep "cms/galleries/edit"
```

Expected: no output (no errors).

- [ ] **Step 3: Commit**

```bash
git add resources/js/pages/cms/galleries/edit.tsx
git commit -m "feat: use ConfirmDeleteDialog in cms/galleries/edit"
```

---

## Task 7: Final verification

- [ ] **Step 1: Run all CMS tests**

```bash
php artisan test --compact tests/Feature/CMS/
```

Expected: 32 passed.

- [ ] **Step 2: Run full TypeScript check and confirm no new errors**

```bash
npm run types:check 2>&1 | grep -c "error TS"
```

Expected: 18 (same as before — pre-existing errors in auth/settings/teams are unchanged).

- [ ] **Step 3: Run PHP linter**

```bash
vendor/bin/pint --dirty --format agent
```

Expected: no PHP files changed (we only touched TypeScript/React files).

- [ ] **Step 4: Run full test suite**

```bash
php artisan test --compact
```

Expected: 145 passed.
