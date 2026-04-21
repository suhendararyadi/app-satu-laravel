import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
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
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Jam Pelajaran</h1>
                        <Button asChild>
                            <Link
                                href={TimeSlotController.create.url(teamSlug)}
                            >
                                Tambah Jam
                            </Link>
                        </Button>
                    </div>

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
                                    <TableCell className="font-medium">
                                        {slot.name}
                                    </TableCell>
                                    <TableCell>{slot.start_time}</TableCell>
                                    <TableCell>{slot.end_time}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={TimeSlotController.edit.url(
                                                    {
                                                        current_team: teamSlug,
                                                        timeSlot: slot.id,
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
                                                handleDelete(slot.id)
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
