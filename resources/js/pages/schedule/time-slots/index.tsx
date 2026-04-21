import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClockIcon } from 'lucide-react';
import { useState } from 'react';

import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
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
import type { TimeSlot } from '@/types/schedule';

interface Props {
    timeSlots: TimeSlot[];
}

export default function Index({ timeSlots }: Props) {
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
            TimeSlotController.destroy.url({
                current_team: teamSlug,
                timeSlot: deleteId,
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
            <Head title="Jam Pelajaran" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Jam Pelajaran"
                        action={
                            <Button asChild>
                                <Link href={TimeSlotController.create.url(teamSlug)}>
                                    Tambah Jam
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={timeSlots.length === 0}
                        emptyState={{
                            icon: ClockIcon,
                            title: 'Belum ada jam pelajaran',
                            description: 'Tambah jam pelajaran untuk memulai.',
                            action: {
                                label: 'Tambah Jam',
                                href: TimeSlotController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Urutan</TableHead>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Mulai</TableHead>
                                    <TableHead>Selesai</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {timeSlots.map((slot) => (
                                    <TableRow key={slot.id}>
                                        <TableCell>{slot.sort_order}</TableCell>
                                        <TableCell className="font-medium">{slot.name}</TableCell>
                                        <TableCell>{slot.start_time}</TableCell>
                                        <TableCell>{slot.end_time}</TableCell>
                                        <TableCell className="space-x-2">
                                            <Button size="sm" variant="outline" asChild>
                                                <Link
                                                    href={TimeSlotController.edit.url({
                                                        current_team: teamSlug,
                                                        timeSlot: slot.id,
                                                    })}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDelete(slot.id)}
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
