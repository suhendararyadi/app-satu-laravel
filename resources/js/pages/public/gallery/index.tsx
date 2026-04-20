import { Link } from '@inertiajs/react';
import type { Gallery, School } from '@/types/school';

interface Props {
    school: School;
    galleries: Gallery[];
}

export default function PublicGalleryIndex({ school, galleries }: Props) {
    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-8 text-3xl font-bold text-gray-900">
                    Galeri
                </h1>

                {galleries.length === 0 ? (
                    <p className="text-gray-500">Belum ada galeri.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {galleries.map((gallery) => {
                            const thumbnail = gallery.images?.[0];

                            return (
                                <Link
                                    key={gallery.id}
                                    href={`/schools/${school.slug}/gallery/${gallery.id}`}
                                    className="group block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
                                >
                                    {thumbnail ? (
                                        <img
                                            src={`/storage/${thumbnail.image_path}`}
                                            alt={gallery.title}
                                            className="h-48 w-full object-cover"
                                        />
                                    ) : (
                                        <div className="flex h-48 items-center justify-center bg-gray-100 text-sm text-gray-400">
                                            Tidak ada gambar
                                        </div>
                                    )}
                                    <div className="p-4">
                                        <h2 className="font-semibold text-gray-900 group-hover:text-blue-600">
                                            {gallery.title}
                                        </h2>
                                        <p className="mt-1 text-sm text-gray-500">
                                            {gallery.images_count ??
                                                gallery.images?.length ??
                                                0}{' '}
                                            foto
                                        </p>
                                    </div>
                                </Link>
                            );
                        })}
                    </div>
                )}
            </div>
        </div>
    );
}
