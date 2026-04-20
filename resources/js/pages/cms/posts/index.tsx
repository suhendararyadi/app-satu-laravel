import { Head, Link, router, usePage } from '@inertiajs/react';
import * as PostController from '@/actions/App/Http/Controllers/CMS/PostController';
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

    function handleDelete(post: Post) {
        if (!window.confirm(`Hapus artikel "${post.title}"?`)) {
            return;
        }

        router.delete(
            PostController.destroy.url({
                current_team: teamSlug,
                post: post.id,
            }),
            {
                preserveScroll: true,
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
        </>
    );
}
