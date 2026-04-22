import { Head, Link, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Pagination } from '@/components/ui/pagination';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

interface StudentDetail {
    id: number;
    name: string;
    email: string;
    joined_at: string;
}

interface EnrollmentDetail {
    classroom_name: string;
    student_number: string | null;
    grade_name: string;
    academic_year_name: string;
}

interface AttendanceSummaryItem {
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    count: number;
}

interface AttendanceRecordItem {
    date: string;
    subject_name: string | null;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes: string | null;
}

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface PaginatedAttendanceRecords extends PaginationMeta {
    data: AttendanceRecordItem[];
}

interface GuardianItem {
    name: string;
    email: string;
    relationship_label: string;
}

interface Props {
    student: StudentDetail;
    enrollment: EnrollmentDetail | null;
    attendance_summary: AttendanceSummaryItem[];
    attendance_records: PaginatedAttendanceRecords;
    guardians: GuardianItem[];
}

const statusColors: Record<string, string> = {
    hadir: 'bg-green-100 text-green-800',
    sakit: 'bg-yellow-100 text-yellow-800',
    izin: 'bg-blue-100 text-blue-800',
    alpa: 'bg-red-100 text-red-800',
};

const statusLabels: Record<string, string> = {
    hadir: 'Hadir',
    sakit: 'Sakit',
    izin: 'Izin',
    alpa: 'Alpa',
};

export default function StudentShow({
    student,
    enrollment,
    attendance_summary,
    attendance_records,
    guardians,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    return (
        <>
            <Head title={student.name} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                {student.name}
                            </h1>
                            <p className="text-muted-foreground text-sm">
                                {student.email}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={StudentController.index.url(teamSlug)}
                                >
                                    Kembali
                                </Link>
                            </Button>
                            <Button asChild>
                                <Link
                                    href={StudentController.edit.url({
                                        current_team: teamSlug,
                                        user: student.id,
                                    })}
                                >
                                    Edit
                                </Link>
                            </Button>
                        </div>
                    </div>

                    {/* Profil */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Profil</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <dl className="grid grid-cols-2 gap-4">
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Email
                                    </dt>
                                    <dd className="font-medium">
                                        {student.email}
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-muted-foreground text-sm">
                                        Bergabung
                                    </dt>
                                    <dd className="font-medium">
                                        {new Date(
                                            student.joined_at,
                                        ).toLocaleDateString('id-ID', {
                                            day: 'numeric',
                                            month: 'long',
                                            year: 'numeric',
                                        })}
                                    </dd>
                                </div>
                            </dl>
                        </CardContent>
                    </Card>

                    {/* Info Kelas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Info Kelas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            {enrollment ? (
                                <dl className="grid grid-cols-2 gap-4">
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Kelas
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.classroom_name}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            NIS
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.student_number ?? '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Tingkat
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.grade_name}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-muted-foreground text-sm">
                                            Tahun Ajaran
                                        </dt>
                                        <dd className="font-medium">
                                            {enrollment.academic_year_name}
                                        </dd>
                                    </div>
                                </dl>
                            ) : (
                                <p className="text-muted-foreground text-sm">
                                    Belum terdaftar di kelas manapun.
                                </p>
                            )}
                        </CardContent>
                    </Card>

                    {/* Kehadiran */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Kehadiran</h2>

                        <div className="flex gap-3">
                            {attendance_summary.map((item) => (
                                <div
                                    key={item.status}
                                    className={`rounded-lg px-4 py-2 text-center ${statusColors[item.status]}`}
                                >
                                    <div className="text-2xl font-bold">
                                        {item.count}
                                    </div>
                                    <div className="text-xs font-medium">
                                        {statusLabels[item.status]}
                                    </div>
                                </div>
                            ))}
                        </div>

                        {attendance_records.data.length > 0 ? (
                            <>
                                <Table>
                                    <TableHeader>
                                        <TableRow>
                                            <TableHead>Tanggal</TableHead>
                                            <TableHead>
                                                Mata Pelajaran
                                            </TableHead>
                                            <TableHead>Status</TableHead>
                                            <TableHead>Catatan</TableHead>
                                        </TableRow>
                                    </TableHeader>
                                    <TableBody>
                                        {attendance_records.data.map(
                                            (record, idx) => (
                                                <TableRow key={idx}>
                                                    <TableCell>
                                                        {new Date(
                                                            record.date,
                                                        ).toLocaleDateString(
                                                            'id-ID',
                                                            {
                                                                day: 'numeric',
                                                                month: 'short',
                                                                year: 'numeric',
                                                            },
                                                        )}
                                                    </TableCell>
                                                    <TableCell>
                                                        {record.subject_name ??
                                                            '—'}
                                                    </TableCell>
                                                    <TableCell>
                                                        <span
                                                            className={`rounded px-2 py-0.5 text-xs font-medium ${statusColors[record.status]}`}
                                                        >
                                                            {
                                                                statusLabels[
                                                                    record
                                                                        .status
                                                                ]
                                                            }
                                                        </span>
                                                    </TableCell>
                                                    <TableCell className="text-muted-foreground">
                                                        {record.notes ?? '—'}
                                                    </TableCell>
                                                </TableRow>
                                            ),
                                        )}
                                    </TableBody>
                                </Table>
                                <Pagination meta={attendance_records} />
                            </>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Belum ada data kehadiran.
                            </p>
                        )}
                    </div>

                    {/* Wali */}
                    <div className="space-y-4">
                        <h2 className="text-lg font-semibold">Data Wali</h2>
                        {guardians.length > 0 ? (
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Nama</TableHead>
                                        <TableHead>Email</TableHead>
                                        <TableHead>Hubungan</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {guardians.map((g, idx) => (
                                        <TableRow key={idx}>
                                            <TableCell className="font-medium">
                                                {g.name}
                                            </TableCell>
                                            <TableCell className="text-muted-foreground">
                                                {g.email}
                                            </TableCell>
                                            <TableCell>
                                                {g.relationship_label}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                </TableBody>
                            </Table>
                        ) : (
                            <p className="text-muted-foreground text-sm">
                                Belum ada data wali.
                            </p>
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}
