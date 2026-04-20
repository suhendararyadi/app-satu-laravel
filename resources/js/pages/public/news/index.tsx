import { Link } from '@inertiajs/react';
import type { Post, School } from '@/types/school';

interface PaginatedData<T> {
    data: T[];
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    links: { url: string | null; label: string; active: boolean }[];
}

interface Props {
    school: School;
    posts: PaginatedData<Post>;
}

export default function PublicNewsIndex({ school, posts }: Props) {
    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-6xl">
                <h1 className="mb-8 text-3xl font-bold text-gray-900">
                    Berita
                </h1>

                {posts.data.length === 0 ? (
                    <p className="text-gray-500">Belum ada berita.</p>
                ) : (
                    <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                        {posts.data.map((post) => (
                            <Link
                                key={post.id}
                                href={`/schools/${school.slug}/news/${post.slug}`}
                                className="group block overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition hover:shadow-md"
                            >
                                {post.featured_image_path ? (
                                    <img
                                        src={`/storage/${post.featured_image_path}`}
                                        alt={post.title}
                                        className="h-48 w-full object-cover"
                                    />
                                ) : (
                                    <div className="flex h-48 items-center justify-center bg-gray-100 text-sm text-gray-400">
                                        Tidak ada gambar
                                    </div>
                                )}
                                <div className="p-4">
                                    <h2 className="font-semibold text-gray-900 group-hover:text-blue-600">
                                        {post.title}
                                    </h2>
                                    {post.excerpt && (
                                        <p className="mt-1 line-clamp-2 text-sm text-gray-500">
                                            {post.excerpt}
                                        </p>
                                    )}
                                    {post.published_at && (
                                        <p className="mt-2 text-xs text-gray-400">
                                            {new Date(
                                                post.published_at,
                                            ).toLocaleDateString('id-ID', {
                                                year: 'numeric',
                                                month: 'long',
                                                day: 'numeric',
                                            })}
                                        </p>
                                    )}
                                </div>
                            </Link>
                        ))}
                    </div>
                )}

                {/* Pagination */}
                {posts.last_page > 1 && (
                    <div className="mt-10 flex flex-wrap justify-center gap-2">
                        {posts.links.map((link, index) => (
                            <span key={index}>
                                {link.url ? (
                                    <Link
                                        href={link.url}
                                        className={`rounded border px-3 py-1 text-sm transition ${
                                            link.active
                                                ? 'border-blue-600 bg-blue-600 text-white'
                                                : 'border-gray-300 text-gray-600 hover:border-blue-600 hover:text-blue-600'
                                        }`}
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                ) : (
                                    <span
                                        className="rounded border border-gray-200 px-3 py-1 text-sm text-gray-400"
                                        dangerouslySetInnerHTML={{
                                            __html: link.label,
                                        }}
                                    />
                                )}
                            </span>
                        ))}
                    </div>
                )}
            </div>
        </div>
    );
}
