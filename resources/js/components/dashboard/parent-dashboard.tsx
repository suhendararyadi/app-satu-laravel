import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ChildData, ParentDashboardData } from '@/types/dashboard';

type Props = {
    data: ParentDashboardData;
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

function ChildCard({ child }: { child: ChildData }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{child.student.name}</CardTitle>
                {child.classroom && (
                    <p className="text-sm text-muted-foreground">
                        {child.classroom.name}
                        {child.classroom.grade
                            ? ` · ${child.classroom.grade}`
                            : ''}
                    </p>
                )}
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Nilai Terbaru */}
                <div>
                    <p className="mb-2 text-sm font-semibold">Nilai Terbaru</p>
                    {child.recent_scores.length === 0 ? (
                        <p className="text-sm text-muted-foreground">
                            Belum ada nilai.
                        </p>
                    ) : (
                        <ul className="space-y-1">
                            {child.recent_scores.map((s) => (
                                <li
                                    key={s.id}
                                    className="flex items-center justify-between text-sm"
                                >
                                    <span className="text-muted-foreground">
                                        {s.assessment_title ?? '-'} (
                                        {s.subject ?? '-'})
                                    </span>
                                    <Badge variant="secondary">
                                        {s.score}/{s.max_score}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Rekap Kehadiran */}
                <div>
                    <p className="mb-2 text-sm font-semibold">
                        Rekap Kehadiran
                    </p>
                    <div className="flex flex-wrap gap-2">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-1">
                                <Badge className={className}>{label}</Badge>
                                <span className="text-sm font-semibold">
                                    {child.attendance_summary[key]}
                                </span>
                            </div>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ParentDashboard({ data }: Props) {
    if (data.children.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <p className="text-muted-foreground">
                        Tidak ada data anak yang terdaftar di sekolah ini.
                    </p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {data.children.map((child) => (
                <ChildCard key={child.student.id} child={child} />
            ))}
        </div>
    );
}
