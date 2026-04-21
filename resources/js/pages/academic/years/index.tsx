import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
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
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Tahun Ajaran</h1>
                        <Button asChild>
                            <Link
                                href={AcademicYearController.create.url(
                                    teamSlug,
                                )}
                            >
                                Tambah Tahun Ajaran
                            </Link>
                        </Button>
                    </div>

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
                                    <TableCell className="font-medium">
                                        {year.name}
                                    </TableCell>
                                    <TableCell>
                                        {year.start_year}/{year.end_year}
                                    </TableCell>
                                    <TableCell>
                                        {year.semesters?.length ?? 0} semester
                                    </TableCell>
                                    <TableCell>
                                        {year.is_active ? (
                                            <Badge variant="default">
                                                Aktif
                                            </Badge>
                                        ) : (
                                            <Badge variant="secondary">
                                                Tidak Aktif
                                            </Badge>
                                        )}
                                    </TableCell>
                                    <TableCell className="space-x-2">
                                        {!year.is_active && (
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                onClick={() =>
                                                    router.post(
                                                        AcademicYearController.activate.url(
                                                            {
                                                                current_team:
                                                                    teamSlug,
                                                                year: year.id,
                                                            },
                                                        ),
                                                        {},
                                                        {
                                                            preserveScroll: true,
                                                        },
                                                    )
                                                }
                                            >
                                                Aktifkan
                                            </Button>
                                        )}
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={AcademicYearController.edit.url(
                                                    {
                                                        current_team: teamSlug,
                                                        year: year.id,
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
                                                handleDelete(year.id)
                                            }
                                        >
                                            Hapus
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

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
