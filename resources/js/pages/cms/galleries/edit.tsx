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
        if (!pendingImage) {
            return;
        }

        router.delete(
            GalleryController.destroyImage.url({
                current_team: teamSlug,
                gallery: gallery.id,
                image: pendingImage.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setPendingImage(null);
                },
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
                onOpenChange={(open) => {
                    setConfirmOpen(open);

                    if (!open) {
                        setPendingImage(null);
                    }
                }}
                title="Hapus foto ini?"
                onConfirm={executeDeleteImage}
            />
        </>
    );
}
