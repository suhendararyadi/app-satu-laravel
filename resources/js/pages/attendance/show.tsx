import { Head, Link, usePage } from '@inertiajs/react';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
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
import type { Attendance, AttendanceRecord } from '@/types/schedule';

interface Props {
    attendance: Attendance & {
        classroom: { name: string };
        subject: { name: string } | null;
        semester: { name: string };
        records: (AttendanceRecord & { user: { name: string } })[];
    };
}

const statusColor: Record<
    string,
    'default' | 'secondary' | 'destructive' | 'outline'
> = {
    hadir: 'default',
    sakit: 'secondary',
    izin: 'outline',
    alpa: 'destructive',
};

export default function Show({ attendance }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Detail Absensi" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                Detail Absensi
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {attendance.date} · {attendance.classroom.name}{' '}
                                · {attendance.subject?.name ?? 'Harian'} ·{' '}
                                {attendance.semester.name}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={AttendanceController.edit.url({
                                        current_team: teamSlug,
                                        attendance: attendance.id,
                                    })}
                                >
                                    Edit
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link
                                    href={AttendanceController.index.url(
                                        teamSlug,
                                    )}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama Siswa</TableHead>
                                <TableHead>Status</TableHead>
                                <TableHead>Catatan</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {attendance.records.map((record) => (
                                <TableRow key={record.id}>
                                    <TableCell>{record.user?.name}</TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                statusColor[record.status] ??
                                                'default'
                                            }
                                        >
                                            {record.status}
                                        </Badge>
                                    </TableCell>
                                    <TableCell>{record.notes ?? '-'}</TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>
                </div>
            </div>
        </>
    );
}
