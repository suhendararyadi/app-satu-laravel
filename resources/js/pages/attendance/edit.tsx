import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ATTENDANCE_STATUSES } from '@/types/schedule';
import type { Attendance, AttendanceRecord } from '@/types/schedule';

interface StudentRow {
    id: number;
    name: string;
}

interface Props {
    attendance: Attendance & {
        records: AttendanceRecord[];
    };
    students: StudentRow[];
}

export default function Edit({ attendance, students }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const initialRecords = students.map((s) => {
        const existing = attendance.records.find(
            (r) => r.student_user_id === s.id,
        );

        return {
            student_user_id: s.id,
            status: existing?.status ?? 'hadir',
            notes: existing?.notes ?? '',
        };
    });

    const form = useForm<{
        records: { student_user_id: number; status: string; notes: string }[];
    }>({ records: initialRecords });

    function updateRecord(
        index: number,
        field: 'status' | 'notes',
        value: string,
    ) {
        const updated = form.data.records.map((r, i) =>
            i === index ? { ...r, [field]: value } : r,
        );
        form.setData('records', updated);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            AttendanceController.update.url({
                current_team: teamSlug,
                attendance: attendance.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Absensi" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-6">
                    <h1 className="text-2xl font-bold">Edit Absensi</h1>
                    <p className="text-sm text-muted-foreground">
                        {attendance.date}
                    </p>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            {students.map((student, i) => (
                                <div
                                    key={student.id}
                                    className="flex items-center gap-2"
                                >
                                    <span className="w-40 text-sm">
                                        {student.name}
                                    </span>
                                    <Select
                                        value={
                                            form.data.records[i]?.status ??
                                            'hadir'
                                        }
                                        onValueChange={(v) =>
                                            updateRecord(i, 'status', v)
                                        }
                                    >
                                        <SelectTrigger className="w-28">
                                            <SelectValue />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {ATTENDANCE_STATUSES.map((s) => (
                                                <SelectItem key={s} value={s}>
                                                    {s}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                    <Input
                                        placeholder="Catatan"
                                        value={
                                            form.data.records[i]?.notes ?? ''
                                        }
                                        onChange={(e) =>
                                            updateRecord(
                                                i,
                                                'notes',
                                                e.target.value,
                                            )
                                        }
                                        className="flex-1"
                                    />
                                </div>
                            ))}
                        </div>

                        <InputError message={form.errors.records} />

                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={AttendanceController.show.url({
                                        current_team: teamSlug,
                                        attendance: attendance.id,
                                    })}
                                >
                                    Batal
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
