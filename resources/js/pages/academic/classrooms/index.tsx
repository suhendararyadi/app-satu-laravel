import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ClassroomController from '@/actions/App/Http/Controllers/Academic/ClassroomController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AcademicYear, Classroom, Grade } from '@/types/academic';

interface Props {
    classrooms: Classroom[];
}

export default function Index({ classrooms }: Props) {
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
            ClassroomController.destroy.url({
                current_team: teamSlug,
                classroom: deleteId,
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
            <Head title="Kelas" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Kelas</h1>
                        <Button asChild>
                            <Link
                                href={ClassroomController.create.url(teamSlug)}
                            >
                                Tambah Kelas
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Tingkat</TableHead>
                                <TableHead>Tahun Ajaran</TableHead>
                                <TableHead className="w-40">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {classrooms.map((classroom) => (
                                <TableRow key={classroom.id}>
                                    <TableCell className="font-medium">
                                        {classroom.name}
                                    </TableCell>
                                    <TableCell>
                                        {(classroom.grade as Grade | undefined)
                                            ?.name ?? '—'}
                                    </TableCell>
                                    <TableCell>
                                        {(
                                            classroom.academic_year as
                                                | AcademicYear
                                                | undefined
                                        )?.name ?? '—'}
                                    </TableCell>
                                    <TableCell className="space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={ClassroomController.show.url(
                                                    {
                                                        current_team: teamSlug,
                                                        classroom: classroom.id,
                                                    },
                                                )}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={ClassroomController.edit.url(
                                                    {
                                                        current_team: teamSlug,
                                                        classroom: classroom.id,
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
                                                handleDelete(classroom.id)
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
