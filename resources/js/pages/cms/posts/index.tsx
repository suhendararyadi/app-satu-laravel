import { Head, Link, router, usePage } from '@inertiajs/react';
import { NewspaperIcon } from 'lucide-react';
import { useState } from 'react';

import * as PostController from '@/actions/App/Http/Controllers/CMS/PostController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
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
        if (!pendingPost) {
            return;
        }

        router.delete(
            PostController.destroy.url({
                current_team: teamSlug,
                post: pendingPost.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setPendingPost(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Artikel CMS" />

            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Artikel"
                        description="Kelola artikel dan berita website sekolah"
                        action={
                            <Button asChild>
                                <Link
                                    href={PostController.create.url(teamSlug)}
                                >
                                    Tulis Artikel
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={posts.length === 0}
                        emptyState={{
                            icon: NewspaperIcon,
                            title: 'Belum ada artikel',
                            description:
                                'Tulis artikel pertama untuk website sekolah.',
                            action: {
                                label: 'Tulis Artikel',
                                href: PostController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Penulis</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Tanggal Publish</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {posts.map((post) => (
                                    <TableRow key={post.id}>
                                        <TableCell className="font-medium">
                                            {post.title}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {post.author.name}
                                        </TableCell>
                                        <TableCell>
                                            {post.is_published ? (
                                                <Badge variant="default">
                                                    Terbit
                                                </Badge>
                                            ) : (
                                                <Badge variant="secondary">
                                                    Draft
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {post.published_at ?? '-'}
                                        </TableCell>
                                        <TableCell>
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
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableWrapper>
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={(open) => {
                    setConfirmOpen(open);

                    if (!open) {
                        setPendingPost(null);
                    }
                }}
                title={`Hapus artikel "${pendingPost?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
