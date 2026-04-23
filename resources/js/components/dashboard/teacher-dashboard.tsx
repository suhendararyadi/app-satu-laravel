import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TeacherDashboardData } from '@/types/dashboard';

type Props = {
    data: TeacherDashboardData;
};

export default function TeacherDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Kelas Diampu */}
            <Card>
                <CardHeader>
                    <CardTitle>Kelas Diampu</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.my_classrooms.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada kelas yang diampu.</p>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {data.my_classrooms.map((c) => (
                                <div
                                    key={c.id}
                                    className="rounded-lg border p-4"
                                >
                                    <p className="font-semibold">{c.name}</p>
                                    <p className="text-sm text-muted-foreground">{c.grade ?? '-'}</p>
                                    <p className="mt-1 text-sm">
                                        <span className="font-medium">{c.student_count}</span> siswa
                                    </p>
                                </div>
                            ))}
                        </div>
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
                        <p className="text-sm text-muted-foreground">Tidak ada jadwal hari ini.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.schedule_today.map((s) => (
                                <li key={s.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{s.subject ?? '-'}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {s.classroom ?? '-'}{s.room ? ` · ${s.room}` : ''}
                                        </p>
                                    </div>
                                    {s.time_slot && (
                                        <Badge variant="outline">{s.time_slot}</Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Penilaian Pending */}
            <Card>
                <CardHeader>
                    <CardTitle>Penilaian Belum Lengkap</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.pending_assessments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Semua penilaian sudah lengkap.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.pending_assessments.map((a) => (
                                <li key={a.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{a.title}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {a.classroom ?? '-'} · {a.subject ?? '-'} · {a.date ?? '-'}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {a.scored}/{a.total}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
