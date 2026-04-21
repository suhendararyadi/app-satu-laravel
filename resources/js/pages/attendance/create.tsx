import { Head, Link, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { ATTENDANCE_STATUSES } from '@/types/schedule';

interface SelectOption {
    id: number;
    name: string;
}

interface Props {
    classrooms: SelectOption[];
    semesters: SelectOption[];
    subjects: SelectOption[];
}

interface RecordRow {
    student_user_id: number;
    name: string;
    status: string;
    notes: string;
}

export default function Create({ classrooms, semesters, subjects }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [rows, setRows] = useState<RecordRow[]>([]);

    const form = useForm<{
        classroom_id: string;
        date: string;
        subject_id: string;
        semester_id: string;
        records: { student_user_id: number; status: string; notes: string }[];
    }>({
        classroom_id: '',
        date: new Date().toISOString().split('T')[0],
        subject_id: '',
        semester_id: '',
        records: [],
    });

    function handleClassroomChange(value: string) {
        form.setData('classroom_id', value);
        setRows([]);
    }

    function updateRow(index: number, field: keyof RecordRow, value: string) {
        const updated = rows.map((r, i) =>
            i === index ? { ...r, [field]: value } : r,
        );
        setRows(updated);
        form.setData(
            'records',
            updated.map(({ student_user_id, status, notes }) => ({
                student_user_id,
                status,
                notes,
            })),
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AttendanceController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Input Absensi" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-6">
                    <h1 className="text-2xl font-bold">Input Absensi</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="classroom_id">Kelas</Label>
                                <Select
                                    value={form.data.classroom_id}
                                    onValueChange={handleClassroomChange}
                                >
                                    <SelectTrigger id="classroom_id">
                                        <SelectValue placeholder="Pilih kelas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {classrooms.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={String(c.id)}
                                            >
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.classroom_id}
                                />
                            </div>
                            <div>
                                <Label htmlFor="date">Tanggal</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) =>
                                        form.setData('date', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.date} />
                            </div>
                            <div>
                                <Label htmlFor="semester_id">Semester</Label>
                                <Select
                                    value={form.data.semester_id}
                                    onValueChange={(v) =>
                                        form.setData('semester_id', v)
                                    }
                                >
                                    <SelectTrigger id="semester_id">
                                        <SelectValue placeholder="Pilih semester" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {semesters.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.semester_id} />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">
                                    Mata Pelajaran (opsional)
                                </Label>
                                <Select
                                    value={form.data.subject_id}
                                    onValueChange={(v) =>
                                        form.setData('subject_id', v)
                                    }
                                >
                                    <SelectTrigger id="subject_id">
                                        <SelectValue placeholder="Pilih mapel / harian" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {subjects.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.subject_id} />
                            </div>
                        </div>

                        {rows.length > 0 && (
                            <div className="space-y-2">
                                <h2 className="font-semibold">Daftar Siswa</h2>
                                {rows.map((row, i) => (
                                    <div
                                        key={row.student_user_id}
                                        className="flex items-center gap-2"
                                    >
                                        <span className="w-40 text-sm">
                                            {row.name}
                                        </span>
                                        <Select
                                            value={row.status}
                                            onValueChange={(v) =>
                                                updateRow(i, 'status', v)
                                            }
                                        >
                                            <SelectTrigger className="w-28">
                                                <SelectValue />
                                            </SelectTrigger>
                                            <SelectContent>
                                                {ATTENDANCE_STATUSES.map(
                                                    (s) => (
                                                        <SelectItem
                                                            key={s}
                                                            value={s}
                                                        >
                                                            {s}
                                                        </SelectItem>
                                                    ),
                                                )}
                                            </SelectContent>
                                        </Select>
                                        <Input
                                            placeholder="Catatan"
                                            value={row.notes}
                                            onChange={(e) =>
                                                updateRow(
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
                        )}

                        <InputError message={form.errors.records} />

                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={AttendanceController.index.url(
                                        teamSlug,
                                    )}
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
