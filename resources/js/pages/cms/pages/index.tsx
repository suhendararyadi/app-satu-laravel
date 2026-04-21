import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileTextIcon } from 'lucide-react';
import { useState } from 'react';

import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
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
        if (!pendingPage) {
            return;
        }

        router.delete(
            PageController.destroy.url({
                current_team: teamSlug,
                page: pendingPage.id,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setPendingPage(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Halaman CMS" />

            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Halaman"
                        description="Kelola halaman statis website sekolah"
                        action={
                            <Button asChild>
                                <Link
                                    href={PageController.create.url(teamSlug)}
                                >
                                    Tambah Halaman
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={pages.length === 0}
                        emptyState={{
                            icon: FileTextIcon,
                            title: 'Belum ada halaman',
                            description:
                                'Tambah halaman statis pertama untuk website sekolah.',
                            action: {
                                label: 'Tambah Halaman',
                                href: PageController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Slug</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead>Urutan</TableHead>
                                    <TableHead>Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {pages.map((page) => (
                                    <TableRow key={page.id}>
                                        <TableCell className="font-medium">
                                            {page.title}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {page.slug}
                                        </TableCell>
                                        <TableCell>
                                            {page.is_published ? (
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
                                            {page.sort_order}
                                        </TableCell>
                                        <TableCell>
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
                        setPendingPage(null);
                    }
                }}
                title={`Hapus halaman "${pendingPage?.title}"?`}
                onConfirm={executeDelete}
            />
        </>
    );
}
