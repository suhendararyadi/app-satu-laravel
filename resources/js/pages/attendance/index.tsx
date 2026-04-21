import { Head, Link, usePage } from '@inertiajs/react';
import { ClipboardListIcon } from 'lucide-react';

import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Attendance } from '@/types/schedule';

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface PaginatedAttendances extends PaginationMeta {
    data: Attendance[];
}

interface Props {
    attendances: PaginatedAttendances;
}

export default function Index({ attendances }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    return (
        <>
            <Head title="Absensi" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Absensi"
                        action={
                            <Button asChild>
                                <Link href={AttendanceController.create.url(teamSlug)}>
                                    Input Absensi
                                </Link>
                            </Button>
                        }
                    />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={attendances.data.length === 0}
                        emptyState={{
                            icon: ClipboardListIcon,
                            title: 'Belum ada data absensi',
                            description: 'Mulai input absensi untuk kelas Anda.',
                            action: {
                                label: 'Input Absensi',
                                href: AttendanceController.create.url(teamSlug),
                            },
                        }}
                    >
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
                                            {(att.classroom as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(att.subject as { name?: string })?.name ?? 'Harian'}
                                        </TableCell>
                                        <TableCell>
                                            {(att.semester as { name?: string })?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="outline" asChild>
                                                <Link
                                                    href={AttendanceController.show.url({
                                                        current_team: teamSlug,
                                                        attendance: att.id,
                                                    })}
                                                >
                                                    Detail
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableWrapper>

                    <Pagination meta={attendances} />
                </div>
            </div>
        </>
    );
}
