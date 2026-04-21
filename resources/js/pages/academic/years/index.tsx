import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarIcon } from 'lucide-react';
import { useState } from 'react';

import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
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
import type { AcademicYear } from '@/types/academic';

interface Props {
    years: AcademicYear[];
}

export default function Index({ years }: Props) {
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
            AcademicYearController.destroy.url({
                current_team: teamSlug,
                year: deleteId,
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
            <Head title="Tahun Ajaran" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Tahun Ajaran"
                        action={
                            <Button asChild>
                                <Link href={AcademicYearController.create.url(teamSlug)}>
                                    Tambah Tahun Ajaran
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={years.length === 0}
                        emptyState={{
                            icon: CalendarIcon,
                            title: 'Belum ada tahun ajaran',
                            description: 'Tambah tahun ajaran untuk memulai.',
                            action: {
                                label: 'Tambah Tahun Ajaran',
                                href: AcademicYearController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Tahun</TableHead>
                                    <TableHead>Semester</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="w-48">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {years.map((year) => (
                                    <TableRow key={year.id}>
                                        <TableCell className="font-medium">{year.name}</TableCell>
                                        <TableCell>
                                            {year.start_year}/{year.end_year}
                                        </TableCell>
                                        <TableCell>
                                            {year.semesters?.length ?? 0} semester
                                        </TableCell>
                                        <TableCell>
                                            {year.is_active ? (
                                                <Badge variant="default">Aktif</Badge>
                                            ) : (
                                                <Badge variant="secondary">Tidak Aktif</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell className="space-x-2">
                                            {!year.is_active && (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        router.post(
                                                            AcademicYearController.activate.url({
                                                                current_team: teamSlug,
                                                                year: year.id,
                                                            }),
                                                            {},
                                                            { preserveScroll: true },
                                                        )
                                                    }
                                                >
                                                    Aktifkan
                                                </Button>
                                            )}
                                            <Button size="sm" variant="outline" asChild>
                                                <Link
                                                    href={AcademicYearController.edit.url({
                                                        current_team: teamSlug,
                                                        year: year.id,
                                                    })}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDelete(year.id)}
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
