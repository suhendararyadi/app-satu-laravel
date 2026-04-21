import { Head, Link, router, usePage } from '@inertiajs/react';
import { ImageIcon } from 'lucide-react';
import { useState } from 'react';

import * as GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
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
        if (!pendingGallery) {
            return;
        }

        router.delete(
            GalleryController.destroy.url({
                current_team: teamSlug,
                gallery: pendingGallery.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setPendingGallery(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Galeri CMS" />

            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Galeri"
                        description="Kelola galeri foto website sekolah"
                        action={
                            <Button asChild>
                                <Link
                                    href={GalleryController.create.url(
                                        teamSlug,
                                    )}
                                >
                                    Buat Galeri
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={galleries.length === 0}
                        emptyState={{
                            icon: ImageIcon,
                            title: 'Belum ada galeri',
                            description:
                                'Buat galeri pertama untuk website sekolah.',
                            action: {
                                label: 'Buat Galeri',
                                href: GalleryController.create.url(teamSlug),
                            },
                        }}
                    >
                        <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
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
                                                                gallery:
                                                                    gallery.id,
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
                    </DataTableWrapper>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={(open) => {
                    setConfirmOpen(open);

                    if (!open) {
                        setPendingGallery(null);
                    }
                }}
                title={`Hapus galeri "${pendingGallery?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
