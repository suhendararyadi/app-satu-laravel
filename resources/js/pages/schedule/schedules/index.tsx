import { Head, Link, router, usePage } from '@inertiajs/react';
import { CalendarDaysIcon } from 'lucide-react';
import { useState } from 'react';

import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
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
                <div className="space-y-6">
                    <PageHeader
                        title="Jadwal Pelajaran"
                        action={
                            <Button asChild>
                                <Link href={ScheduleController.create.url(teamSlug)}>
                                    Tambah Jadwal
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={schedules.length === 0}
                        emptyState={{
                            icon: CalendarDaysIcon,
                            title: 'Belum ada jadwal pelajaran',
                            description: 'Tambah jadwal pelajaran untuk memulai.',
                            action: {
                                label: 'Tambah Jadwal',
                                href: ScheduleController.create.url(teamSlug),
                            },
                        }}
                    >
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
                                        <TableCell>{schedule.day_of_week}</TableCell>
                                        <TableCell>
                                            {(schedule.time_slot as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(schedule.classroom as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(schedule.subject as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(schedule.teacher as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>{schedule.room ?? '-'}</TableCell>
                                        <TableCell className="space-x-2">
                                            <Button size="sm" variant="outline" asChild>
                                                <Link
                                                    href={ScheduleController.edit.url({
                                                        current_team: teamSlug,
                                                        schedule: schedule.id,
                                                    })}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDelete(schedule.id)}
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
