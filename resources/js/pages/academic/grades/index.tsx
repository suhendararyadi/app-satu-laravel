import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import GradeController from '@/actions/App/Http/Controllers/Academic/GradeController';
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
import type { Grade } from '@/types/academic';

interface Props {
    grades: Grade[];
}

export default function Index({ grades }: Props) {
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
            GradeController.destroy.url({
                current_team: teamSlug,
                grade: deleteId,
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
            <Head title="Tingkat Kelas" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Tingkat Kelas</h1>
                        <Button asChild>
                            <Link href={GradeController.create.url(teamSlug)}>
                                Tambah Tingkat
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Urutan</TableHead>
                                <TableHead className="w-32">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {grades.map((grade) => (
                                <TableRow key={grade.id}>
                                    <TableCell className="font-medium">
                                        {grade.name}
                                    </TableCell>
                                    <TableCell>{grade.order}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={GradeController.edit.url({
                                                    current_team: teamSlug,
                                                    grade: grade.id,
                                                })}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() =>
                                                handleDelete(grade.id)
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
