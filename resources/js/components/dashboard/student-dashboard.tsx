import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { StudentDashboardData } from '@/types/dashboard';

type Props = {
    data: StudentDashboardData;
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

export default function StudentDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Kelas info */}
            <Card>
                <CardHeader>
                    <CardTitle>Kelas Saya</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.classroom ? (
                        <div>
                            <p className="text-xl font-semibold">
                                {data.classroom.name}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                {data.classroom.grade ?? '-'}
                            </p>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">
                            Belum terdaftar di kelas manapun.
                        </p>
                    )}
                </CardContent>
            </Card>

            {/* Jadwal Hari Ini */}
            <Card>
                <CardHeader>
                    <CardTitle>Jadwal Hari Ini</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.schedule_today.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada jadwal hari ini.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {data.schedule_today.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex items-center justify-between rounded-md border px-4 py-2"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {s.subject ?? '-'}
                                        </p>
                                        {s.room && (
                                            <p className="text-sm text-muted-foreground">
                                                {s.room}
                                            </p>
                                        )}
                                    </div>
                                    {s.time_slot && (
                                        <Badge variant="outline">
                                            {s.time_slot}
                                        </Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Nilai Terbaru */}
            <Card>
                <CardHeader>
                    <CardTitle>Nilai Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.recent_scores.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada nilai.
                        </p>
                    ) : (
                        <ul className="space-y-2">
                            {data.recent_scores.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex items-center justify-between rounded-md border px-4 py-2"
                                >
                                    <div>
                                        <p className="font-medium">
                                            {s.assessment_title ?? '-'}
                                        </p>
                                        <p className="text-sm text-muted-foreground">
                                            {s.subject ?? '-'}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {s.score}/{s.max_score}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Rekap Kehadiran */}
            <Card>
                <CardHeader>
                    <CardTitle>Rekap Kehadiran</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-2">
                                <Badge className={className}>{label}</Badge>
                                <span className="font-semibold">
                                    {data.attendance_summary[key]}
                                </span>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
