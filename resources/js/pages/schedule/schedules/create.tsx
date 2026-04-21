import { Head, Link, useForm, usePage } from '@inertiajs/react';
import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
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
import { DAYS_OF_WEEK } from '@/types/schedule';
import type { TimeSlot } from '@/types/schedule';

interface SelectOption {
    id: number;
    name: string;
}

interface Props {
    semesters: SelectOption[];
    classrooms: SelectOption[];
    subjects: SelectOption[];
    teachers: SelectOption[];
    timeSlots: TimeSlot[];
}

export default function Create({
    semesters,
    classrooms,
    subjects,
    teachers,
    timeSlots,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        semester_id: '',
        classroom_id: '',
        subject_id: '',
        teacher_user_id: '',
        day_of_week: '',
        time_slot_id: '',
        room: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(ScheduleController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Jadwal" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Jadwal</h1>
                    <form onSubmit={submit} className="space-y-4">
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
                            <Label htmlFor="classroom_id">Kelas</Label>
                            <Select
                                value={form.data.classroom_id}
                                onValueChange={(v) =>
                                    form.setData('classroom_id', v)
                                }
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
                            <InputError message={form.errors.classroom_id} />
                        </div>
                        <div>
                            <Label htmlFor="subject_id">Mata Pelajaran</Label>
                            <Select
                                value={form.data.subject_id}
                                onValueChange={(v) =>
                                    form.setData('subject_id', v)
                                }
                            >
                                <SelectTrigger id="subject_id">
                                    <SelectValue placeholder="Pilih mapel" />
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
                        <div>
                            <Label htmlFor="teacher_user_id">Guru</Label>
                            <Select
                                value={form.data.teacher_user_id}
                                onValueChange={(v) =>
                                    form.setData('teacher_user_id', v)
                                }
                            >
                                <SelectTrigger id="teacher_user_id">
                                    <SelectValue placeholder="Pilih guru" />
                                </SelectTrigger>
                                <SelectContent>
                                    {teachers.map((t) => (
                                        <SelectItem
                                            key={t.id}
                                            value={String(t.id)}
                                        >
                                            {t.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.teacher_user_id} />
                        </div>
                        <div>
                            <Label htmlFor="day_of_week">Hari</Label>
                            <Select
                                value={form.data.day_of_week}
                                onValueChange={(v) =>
                                    form.setData('day_of_week', v)
                                }
                            >
                                <SelectTrigger id="day_of_week">
                                    <SelectValue placeholder="Pilih hari" />
                                </SelectTrigger>
                                <SelectContent>
                                    {DAYS_OF_WEEK.map((d) => (
                                        <SelectItem key={d} value={d}>
                                            {d}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.day_of_week} />
                        </div>
                        <div>
                            <Label htmlFor="time_slot_id">Jam Pelajaran</Label>
                            <Select
                                value={form.data.time_slot_id}
                                onValueChange={(v) =>
                                    form.setData('time_slot_id', v)
                                }
                            >
                                <SelectTrigger id="time_slot_id">
                                    <SelectValue placeholder="Pilih jam" />
                                </SelectTrigger>
                                <SelectContent>
                                    {timeSlots.map((ts) => (
                                        <SelectItem
                                            key={ts.id}
                                            value={String(ts.id)}
                                        >
                                            {ts.name} ({ts.start_time}–
                                            {ts.end_time})
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.time_slot_id} />
                        </div>
                        <div>
                            <Label htmlFor="room">Ruang (opsional)</Label>
                            <Input
                                id="room"
                                value={form.data.room}
                                onChange={(e) =>
                                    form.setData('room', e.target.value)
                                }
                            />
                            <InputError message={form.errors.room} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={ScheduleController.index.url(
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
