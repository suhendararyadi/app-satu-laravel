import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AdminDashboardData } from '@/types/dashboard';

type Props = {
    data: AdminDashboardData;
};

const attendanceConfig = [
    {
        key: 'hadir',
        label: 'Hadir',
        className:
            'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200',
    },
    {
        key: 'sakit',
        label: 'Sakit',
        className:
            'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200',
    },
    {
        key: 'izin',
        label: 'Izin',
        className:
            'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200',
    },
    {
        key: 'alpa',
        label: 'Alpa',
        className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200',
    },
] as const;

export default function AdminDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Stat cards */}
            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total Siswa
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">
                            {data.total_students}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total Guru
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">
                            {data.total_teachers}
                        </p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">
                            Total Kelas
                        </CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">
                            {data.total_classrooms}
                        </p>
                    </CardContent>
                </Card>
            </div>

            {/* Attendance today */}
            <Card>
                <CardHeader>
                    <CardTitle>Kehadiran Hari Ini</CardTitle>
                    <p className="text-sm text-muted-foreground">
                        {data.attendance_today.date}
                    </p>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-2">
                                <Badge className={className}>{label}</Badge>
                                <span className="font-semibold">
                                    {data.attendance_today[key]}
                                </span>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            {/* Recent assessments */}
            <Card>
                <CardHeader>
                    <CardTitle>Penilaian Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.recent_assessments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada penilaian.
                        </p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Mata Pelajaran</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.recent_assessments.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell>{a.title}</TableCell>
                                        <TableCell>
                                            {a.classroom ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {a.subject ?? '-'}
                                        </TableCell>
                                        <TableCell>{a.date ?? '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
