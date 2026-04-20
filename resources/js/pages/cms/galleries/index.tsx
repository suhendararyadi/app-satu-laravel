import { Head, Link, router, usePage } from '@inertiajs/react';
import * as GalleryController from '@/actions/App/Http/Controllers/CMS/GalleryController';
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

    function handleDelete(gallery: Gallery) {
        if (!window.confirm(`Hapus galeri "${gallery.title}"?`)) {
            return;
        }

        router.delete(GalleryController.destroy.url({ current_team: teamSlug, gallery: gallery.id }), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Galeri CMS" />

            <div className="px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Galeri" description="Kelola galeri foto website sekolah" />
                    <Button asChild>
                        <Link href={GalleryController.create.url(teamSlug)}>Buat Galeri</Link>
                    </Button>
                </div>

                {galleries.length === 0 ? (
                    <p className="text-muted-foreground mt-6 text-center">Belum ada galeri.</p>
                ) : (
                    <div className="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        {galleries.map((gallery) => {
                            const thumbnail = gallery.images?.[0];
                            const imageCount = gallery.images_count ?? gallery.images?.length ?? 0;

                            return (
                                <div key={gallery.id} className="rounded-lg border overflow-hidden">
                                    {thumbnail ? (
                                        <img
                                            src={'/storage/' + thumbnail.image_path}
                                            alt={gallery.title}
                                            className="h-40 w-full object-cover"
                                        />
                                    ) : (
                                        <div className="bg-muted h-40 w-full" />
                                    )}

                                    <div className="p-4">
                                        <h3 className="font-semibold">{gallery.title}</h3>
                                        <p className="text-muted-foreground mt-1 text-sm">{imageCount} foto</p>

                                        <div className="mt-2">
                                            {gallery.is_published ? (
                                                <Badge variant="default">Terbit</Badge>
                                            ) : (
                                                <Badge variant="secondary">Draft</Badge>
                                            )}
                                        </div>

                                        <div className="mt-4 flex items-center gap-2">
                                            <Button asChild size="sm" variant="outline">
                                                <Link
                                                    href={GalleryController.edit.url({
                                                        current_team: teamSlug,
                                                        gallery: gallery.id,
                                                    })}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDelete(gallery)}
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
        </>
    );
}
