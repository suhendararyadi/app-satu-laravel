import { Head, Link, router, usePage } from '@inertiajs/react';
import { BookOpenIcon } from 'lucide-react';
import { useState } from 'react';

import SubjectController from '@/actions/App/Http/Controllers/Academic/SubjectController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Subject } from '@/types/academic';

interface Props {
    subjects: Subject[];
}

export default function Index({ subjects }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    function handleDelete(id: number) {
        setDeleteId(id);
        setConfirmOpen(true);
    }

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            SubjectController.destroy.url({
                current_team: teamSlug,
                subject: deleteId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Mata Pelajaran" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Mata Pelajaran"
                        action={
                            <Button asChild>
                                <Link
                                    href={SubjectController.create.url(
                                        teamSlug,
                                    )}
                                >
                                    Tambah Mata Pelajaran
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={subjects.length === 0}
                        emptyState={{
                            icon: BookOpenIcon,
                            title: 'Belum ada mata pelajaran',
                            description: 'Tambah mata pelajaran untuk memulai.',
                            action: {
                                label: 'Tambah Mata Pelajaran',
                                href: SubjectController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Kode</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {subjects.map((subject) => (
                                    <TableRow key={subject.id}>
                                        <TableCell className="font-medium">
                                            {subject.name}
                                        </TableCell>
                                        <TableCell>
                                            {subject.code ?? '—'}
                                        </TableCell>
                                        <TableCell className="space-x-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={SubjectController.edit.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            subject: subject.id,
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
                                                    handleDelete(subject.id)
                                                }
                                            >
                                                Hapus
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableWrapper>

                    <ConfirmDeleteDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        onConfirm={confirmDelete}
                    />
                </div>
            </div>
        </>
    );
}
