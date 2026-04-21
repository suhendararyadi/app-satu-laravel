import { Head, Link, usePage } from '@inertiajs/react';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Attendance } from '@/types/schedule';

interface PaginatedAttendances {
    data: Attendance[];
    current_page: number;
    last_page: number;
}

interface Props {
    attendances: PaginatedAttendances;
}

export default function Index({ attendances }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Absensi" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Absensi</h1>
                        <Button asChild>
                            <Link
                                href={AttendanceController.create.url(teamSlug)}
                            >
                                Input Absensi
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Tanggal</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Mapel</TableHead>
                                <TableHead>Semester</TableHead>
                                <TableHead className="w-24">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attendances.data.map((att) => (
                                <TableRow key={att.id}>
                                    <TableCell>{att.date}</TableCell>
                                    <TableCell>
                                        {(att.classroom as { name?: string })
                                            ?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(att.subject as { name?: string })
                                            ?.name ?? 'Harian'}
                                    </TableCell>
                                    <TableCell>
                                        {(att.semester as { name?: string })
                                            ?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            size="sm"
                                            variant="outline"
                                            asChild
                                        >
                                            <Link
                                                href={AttendanceController.show.url(
                                                    {
                                                        current_team: teamSlug,
                                                        attendance: att.id,
                                                    },
                                                )}
                                            >
                                                Detail
                                            </Link>
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}
