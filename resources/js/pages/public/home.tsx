import { Link } from '@inertiajs/react';
import type { Page, Post, School } from '@/types/school';

interface Props {
    school: School;
    recentPosts: Post[];
    pages: Page[];
}

export default function PublicHome({ school, recentPosts, pages }: Props) {
    return (
        <div>
            {/* Hero Section */}
            <section className="bg-gradient-to-br from-blue-50 to-indigo-100 px-6 py-20">
                <div className="mx-auto max-w-4xl text-center">
                    {school.logo_path && (
                        <img
                            src={`/storage/${school.logo_path}`}
                            alt={school.name}
                            className="mx-auto mb-6 h-24 w-24 rounded-full object-cover shadow-md"
                        />
                    )}
                    <h1 className="text-4xl font-bold text-gray-900">{school.name}</h1>
                    {school.description && (
                        <p className="mt-4 text-lg text-gray-600">{school.description}</p>
                    )}
                    {school.city && school.province && (
                        <p className="mt-2 text-sm text-gray-500">
                            {school.city}, {school.province}
                        </p>
                    )}
                </div>
            </section>

            {/* Recent Posts Section */}
            {recentPosts.length > 0 && (
                <section className="px-6 py-16">
                    <div className="mx-auto max-w-6xl">
                        <div className="mb-8 flex items-center justify-between">
                            <h2 className="text-2xl font-bold text-gray-900">Berita Terbaru</h2>
                            <Link
                                href={`/schools/${school.slug}/news`}
                                className="text-sm text-blue-600 hover:underline"
                            >
                                Lihat Semua
                            </Link>
                        </div>
                        <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                            {recentPosts.map((post) => (
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
                                        <div className="flex h-48 items-center justify-center bg-gray-100 text-gray-400">
                                            Tidak ada gambar
                                        </div>
                                    )}
                                    <div className="p-4">
                                        <h3 className="font-semibold text-gray-900 group-hover:text-blue-600">
                                            {post.title}
                                        </h3>
                                        {post.excerpt && (
                                            <p className="mt-1 line-clamp-2 text-sm text-gray-500">{post.excerpt}</p>
                                        )}
                                        {post.published_at && (
                                            <p className="mt-2 text-xs text-gray-400">
                                                {new Date(post.published_at).toLocaleDateString('id-ID', {
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
                    </div>
                </section>
            )}

            {/* Pages Section */}
            {pages.length > 0 && (
                <section className="bg-gray-50 px-6 py-16">
                    <div className="mx-auto max-w-6xl">
                        <h2 className="mb-8 text-2xl font-bold text-gray-900">Halaman</h2>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            {pages.map((page) => (
                                <Link
                                    key={page.id}
                                    href={`/schools/${school.slug}/pages/${page.slug}`}
                                    className="block rounded-lg border border-gray-200 bg-white p-4 shadow-sm transition hover:shadow-md hover:text-blue-600"
                                >
                                    <span className="font-medium text-gray-900 hover:text-blue-600">{page.title}</span>
                                </Link>
                            ))}
                        </div>
                    </div>
                </section>
            )}
        </div>
    );
}
