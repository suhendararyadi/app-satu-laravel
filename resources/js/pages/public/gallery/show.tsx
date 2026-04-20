import { useState } from 'react';
import type { Gallery, GalleryImage, School } from '@/types/school';

interface Props {
    school: School;
    gallery: Gallery;
}

export default function PublicGalleryShow({ gallery }: Props) {
    const [selectedImage, setSelectedImage] = useState<GalleryImage | null>(
        null,
    );

    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-2 text-3xl font-bold text-gray-900">
                    {gallery.title}
                </h1>
                {gallery.description && (
                    <p className="mb-8 text-gray-500">{gallery.description}</p>
                )}

                {gallery.images.length === 0 ? (
                    <p className="text-gray-500">Belum ada foto.</p>
                ) : (
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                        {gallery.images.map((image) => (
                            <button
                                key={image.id}
                                type="button"
                                className="overflow-hidden rounded-lg border border-gray-200 shadow-sm transition hover:shadow-md focus:ring-2 focus:ring-blue-500 focus:outline-none"
                                onClick={() => setSelectedImage(image)}
                            >
                                <img
                                    src={`/storage/${image.image_path}`}
                                    alt={image.caption ?? gallery.title}
                                    className="h-48 w-full object-cover"
                                />
                                {image.caption && (
                                    <p className="px-3 py-2 text-sm text-gray-600">
                                        {image.caption}
                                    </p>
                                )}
                            </button>
                        ))}
                    </div>
                )}
            </div>

            {/* Lightbox */}
            {selectedImage && (
                <div
                    className="fixed inset-0 z-50 flex items-center justify-center bg-black/80 p-4"
                    onClick={() => setSelectedImage(null)}
                >
                    <div
                        className="relative max-h-full max-w-4xl"
                        onClick={(e) => e.stopPropagation()}
                    >
                        <button
                            type="button"
                            className="absolute -top-3 -right-3 flex h-8 w-8 items-center justify-center rounded-full bg-white text-gray-900 shadow hover:bg-gray-100"
                            onClick={() => setSelectedImage(null)}
                        >
                            &times;
                        </button>
                        <img
                            src={`/storage/${selectedImage.image_path}`}
                            alt={selectedImage.caption ?? gallery.title}
                            className="max-h-[85vh] max-w-full rounded-lg object-contain"
                        />
                        {selectedImage.caption && (
                            <p className="mt-2 text-center text-sm text-white">
                                {selectedImage.caption}
                            </p>
                        )}
                    </div>
                </div>
            )}
        </div>
    );
}
