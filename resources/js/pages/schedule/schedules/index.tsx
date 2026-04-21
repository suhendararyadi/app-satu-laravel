import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
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
import type { Schedule } from '@/types/schedule';

interface Props {
    schedules: Schedule[];
}

export default function Index({ schedules }: Props) {
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
            ScheduleController.destroy.url({
                current_team: teamSlug,
                schedule: deleteId,
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
            <Head title="Jadwal Pelajaran" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Jadwal Pelajaran</h1>
                        <Button asChild>
                            <Link
                                href={ScheduleController.create.url(teamSlug)}
                            >
                                Tambah Jadwal
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Hari</TableHead>
                                <TableHead>Jam</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Mapel</TableHead>
                                <TableHead>Guru</TableHead>
                                <TableHead>Ruang</TableHead>
                                <TableHead className="w-32">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {schedules.map((schedule) => (
                                <TableRow key={schedule.id}>
                                    <TableCell>
                                        {schedule.day_of_week}
                                    </TableCell>
                                    <TableCell>
                                        {(
                                            schedule.time_slot as {
                                                name?: string;
                                            }
                                        )?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(
                                            schedule.classroom as {
                                                name?: string;
                                            }
                                        )?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(schedule.subject as { name?: string })
                                            ?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(schedule.teacher as { name?: string })
                                            ?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {schedule.room ?? '-'}
                                    </TableCell>
                                    <TableCell className="space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={ScheduleController.edit.url(
                                                    {
                                                        current_team: teamSlug,
                                                        schedule: schedule.id,
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
                                                handleDelete(schedule.id)
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
