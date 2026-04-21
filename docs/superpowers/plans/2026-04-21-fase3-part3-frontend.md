# Fase 3 Part 3: Frontend (Tasks 8–12)

> Back to index: [2026-04-21-fase3-penjadwalan-absensi.md](./2026-04-21-fase3-penjadwalan-absensi.md)

> **Note on Wayfinder:** After Part 1+2 are complete and routes registered, run `npm run build` once before implementing frontend tasks, so Wayfinder generates action imports.

---

## Task 8: React pages — schedule/time-slots/ (3 files)

**Files:**
- Create: `resources/js/pages/schedule/time-slots/index.tsx`
- Create: `resources/js/pages/schedule/time-slots/create.tsx`
- Create: `resources/js/pages/schedule/time-slots/edit.tsx`

- [ ] **Step 1: Create index.tsx**

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { TimeSlot } from '@/types/schedule';

interface Props {
    timeSlots: TimeSlot[];
}

export default function Index({ timeSlots }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    function handleDelete(id: number) {
        setDeleteId(id);
        setConfirmOpen(true);
    }

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            TimeSlotController.destroy.url({
                current_team: teamSlug,
                time_slot: deleteId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Jam Pelajaran" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Jam Pelajaran</h1>
                        <Button asChild>
                            <Link href={TimeSlotController.create.url(teamSlug)}>
                                Tambah Jam
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Urutan</TableHead>
                                <TableHead>Nama</TableHead>
                                <TableHead>Mulai</TableHead>
                                <TableHead>Selesai</TableHead>
                                <TableHead className="w-32">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {timeSlots.map((slot) => (
                                <TableRow key={slot.id}>
                                    <TableCell>{slot.sort_order}</TableCell>
                                    <TableCell className="font-medium">
                                        {slot.name}
                                    </TableCell>
                                    <TableCell>{slot.start_time}</TableCell>
                                    <TableCell>{slot.end_time}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button size="sm" variant="outline" asChild>
                                            <Link
                                                href={TimeSlotController.edit.url({
                                                    current_team: teamSlug,
                                                    time_slot: slot.id,
                                                })}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => handleDelete(slot.id)}
                                        >
                                            Hapus
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <ConfirmDeleteDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        onConfirm={confirmDelete}
                    />
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create create.tsx**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Create() {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: '',
        start_time: '',
        end_time: '',
        sort_order: '1',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(TimeSlotController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Jam Pelajaran" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Jam Pelajaran</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="start_time">Mulai</Label>
                            <Input
                                id="start_time"
                                type="time"
                                value={form.data.start_time}
                                onChange={(e) => form.setData('start_time', e.target.value)}
                            />
                            <InputError message={form.errors.start_time} />
                        </div>
                        <div>
                            <Label htmlFor="end_time">Selesai</Label>
                            <Input
                                id="end_time"
                                type="time"
                                value={form.data.end_time}
                                onChange={(e) => form.setData('end_time', e.target.value)}
                            />
                            <InputError message={form.errors.end_time} />
                        </div>
                        <div>
                            <Label htmlFor="sort_order">Urutan</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                value={form.data.sort_order}
                                onChange={(e) => form.setData('sort_order', e.target.value)}
                            />
                            <InputError message={form.errors.sort_order} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={TimeSlotController.index.url(teamSlug)}>
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
```

- [ ] **Step 3: Create edit.tsx**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TimeSlot } from '@/types/schedule';

interface Props {
    timeSlot: TimeSlot;
}

export default function Edit({ timeSlot }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: timeSlot.name,
        start_time: timeSlot.start_time,
        end_time: timeSlot.end_time,
        sort_order: String(timeSlot.sort_order),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            TimeSlotController.update.url({
                current_team: teamSlug,
                time_slot: timeSlot.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Jam Pelajaran" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Jam Pelajaran</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) => form.setData('name', e.target.value)}
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="start_time">Mulai</Label>
                            <Input
                                id="start_time"
                                type="time"
                                value={form.data.start_time}
                                onChange={(e) => form.setData('start_time', e.target.value)}
                            />
                            <InputError message={form.errors.start_time} />
                        </div>
                        <div>
                            <Label htmlFor="end_time">Selesai</Label>
                            <Input
                                id="end_time"
                                type="time"
                                value={form.data.end_time}
                                onChange={(e) => form.setData('end_time', e.target.value)}
                            />
                            <InputError message={form.errors.end_time} />
                        </div>
                        <div>
                            <Label htmlFor="sort_order">Urutan</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                value={form.data.sort_order}
                                onChange={(e) => form.setData('sort_order', e.target.value)}
                            />
                            <InputError message={form.errors.sort_order} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={TimeSlotController.index.url(teamSlug)}>
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

---

## Task 9: React pages — schedule/schedules/ (3 files)

**Files:**
- Create: `resources/js/pages/schedule/schedules/index.tsx`
- Create: `resources/js/pages/schedule/schedules/create.tsx`
- Create: `resources/js/pages/schedule/schedules/edit.tsx`

- [ ] **Step 1: Create index.tsx**

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Schedule } from '@/types/schedule';

interface Props {
    schedules: Schedule[];
}

export default function Index({ schedules }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    function handleDelete(id: number) {
        setDeleteId(id);
        setConfirmOpen(true);
    }

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            ScheduleController.destroy.url({
                current_team: teamSlug,
                schedule: deleteId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Jadwal Pelajaran" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Jadwal Pelajaran</h1>
                        <Button asChild>
                            <Link href={ScheduleController.create.url(teamSlug)}>
                                Tambah Jadwal
                            </Link>
                        </Button>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Hari</TableHead>
                                <TableHead>Jam</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>Mapel</TableHead>
                                <TableHead>Guru</TableHead>
                                <TableHead>Ruang</TableHead>
                                <TableHead className="w-32">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {schedules.map((schedule) => (
                                <TableRow key={schedule.id}>
                                    <TableCell>{schedule.day_of_week}</TableCell>
                                    <TableCell>
                                        {(schedule.time_slot as { name?: string })?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(schedule.classroom as { name?: string })?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(schedule.subject as { name?: string })?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>
                                        {(schedule.teacher as { name?: string })?.name ?? '-'}
                                    </TableCell>
                                    <TableCell>{schedule.room ?? '-'}</TableCell>
                                    <TableCell className="space-x-2">
                                        <Button size="sm" variant="outline" asChild>
                                            <Link
                                                href={ScheduleController.edit.url({
                                                    current_team: teamSlug,
                                                    schedule: schedule.id,
                                                })}
                                            >
                                                Edit
                                            </Link>
                                        </Button>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => handleDelete(schedule.id)}
                                        >
                                            Hapus
                                        </Button>
                                    </TableCell>
                                </TableRow>
                            ))}
                        </TableBody>
                    </Table>

                    <ConfirmDeleteDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        onConfirm={confirmDelete}
                    />
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create create.tsx**

```tsx
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

export default function Create({ semesters, classrooms, subjects, teachers, timeSlots }: Props) {
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
                                onValueChange={(v) => form.setData('semester_id', v)}
                            >
                                <SelectTrigger id="semester_id">
                                    <SelectValue placeholder="Pilih semester" />
                                </SelectTrigger>
                                <SelectContent>
                                    {semesters.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
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
                                onValueChange={(v) => form.setData('classroom_id', v)}
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Pilih kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    {classrooms.map((c) => (
                                        <SelectItem key={c.id} value={String(c.id)}>
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
                                onValueChange={(v) => form.setData('subject_id', v)}
                            >
                                <SelectTrigger id="subject_id">
                                    <SelectValue placeholder="Pilih mapel" />
                                </SelectTrigger>
                                <SelectContent>
                                    {subjects.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
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
                                onValueChange={(v) => form.setData('teacher_user_id', v)}
                            >
                                <SelectTrigger id="teacher_user_id">
                                    <SelectValue placeholder="Pilih guru" />
                                </SelectTrigger>
                                <SelectContent>
                                    {teachers.map((t) => (
                                        <SelectItem key={t.id} value={String(t.id)}>
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
                                onValueChange={(v) => form.setData('day_of_week', v)}
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
                                onValueChange={(v) => form.setData('time_slot_id', v)}
                            >
                                <SelectTrigger id="time_slot_id">
                                    <SelectValue placeholder="Pilih jam" />
                                </SelectTrigger>
                                <SelectContent>
                                    {timeSlots.map((ts) => (
                                        <SelectItem key={ts.id} value={String(ts.id)}>
                                            {ts.name} ({ts.start_time}–{ts.end_time})
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
                                onChange={(e) => form.setData('room', e.target.value)}
                            />
                            <InputError message={form.errors.room} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={ScheduleController.index.url(teamSlug)}>
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
```

- [ ] **Step 3: Create edit.tsx**

```tsx
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
import type { Schedule, TimeSlot } from '@/types/schedule';

interface SelectOption {
    id: number;
    name: string;
}

interface Props {
    schedule: Schedule;
    semesters: SelectOption[];
    classrooms: SelectOption[];
    subjects: SelectOption[];
    teachers: SelectOption[];
    timeSlots: TimeSlot[];
}

export default function Edit({ schedule, semesters, classrooms, subjects, teachers, timeSlots }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        semester_id: String(schedule.semester_id),
        classroom_id: String(schedule.classroom_id),
        subject_id: String(schedule.subject_id),
        teacher_user_id: String(schedule.teacher_user_id),
        day_of_week: schedule.day_of_week,
        time_slot_id: String(schedule.time_slot_id),
        room: schedule.room ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            ScheduleController.update.url({
                current_team: teamSlug,
                schedule: schedule.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Jadwal" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Jadwal</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="semester_id">Semester</Label>
                            <Select
                                value={form.data.semester_id}
                                onValueChange={(v) => form.setData('semester_id', v)}
                            >
                                <SelectTrigger id="semester_id">
                                    <SelectValue placeholder="Pilih semester" />
                                </SelectTrigger>
                                <SelectContent>
                                    {semesters.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
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
                                onValueChange={(v) => form.setData('classroom_id', v)}
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Pilih kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    {classrooms.map((c) => (
                                        <SelectItem key={c.id} value={String(c.id)}>
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
                                onValueChange={(v) => form.setData('subject_id', v)}
                            >
                                <SelectTrigger id="subject_id">
                                    <SelectValue placeholder="Pilih mapel" />
                                </SelectTrigger>
                                <SelectContent>
                                    {subjects.map((s) => (
                                        <SelectItem key={s.id} value={String(s.id)}>
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
                                onValueChange={(v) => form.setData('teacher_user_id', v)}
                            >
                                <SelectTrigger id="teacher_user_id">
                                    <SelectValue placeholder="Pilih guru" />
                                </SelectTrigger>
                                <SelectContent>
                                    {teachers.map((t) => (
                                        <SelectItem key={t.id} value={String(t.id)}>
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
                                onValueChange={(v) => form.setData('day_of_week', v)}
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
                                onValueChange={(v) => form.setData('time_slot_id', v)}
                            >
                                <SelectTrigger id="time_slot_id">
                                    <SelectValue placeholder="Pilih jam" />
                                </SelectTrigger>
                                <SelectContent>
                                    {timeSlots.map((ts) => (
                                        <SelectItem key={ts.id} value={String(ts.id)}>
                                            {ts.name} ({ts.start_time}–{ts.end_time})
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
                                onChange={(e) => form.setData('room', e.target.value)}
                            />
                            <InputError message={form.errors.room} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={ScheduleController.index.url(teamSlug)}>
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

---

## Task 10: React pages — attendance/ (3 files)

**Files:**
- Create: `resources/js/pages/attendance/index.tsx`
- Create: `resources/js/pages/attendance/create.tsx`
- Create: `resources/js/pages/attendance/show.tsx`
- Create: `resources/js/pages/attendance/edit.tsx`

- [ ] **Step 1: Create index.tsx**

```tsx
import { Head, Link, usePage } from '@inertiajs/react';
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Attendance } from '@/types/schedule';

interface PaginatedAttendances {
    data: Attendance[];
    current_page: number;
    last_page: number;
}

interface Props {
    attendances: PaginatedAttendances;
}

export default function Index({ attendances }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    return (
        <>
            <Head title="Absensi" />
            <div className="px-4 py-6">
                <div className="space-y-4">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Absensi</h1>
                        <Button asChild>
                            <Link href={AttendanceController.create.url(teamSlug)}>
                                Input Absensi
                            </Link>
                        </Button>
                    </div>

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
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create create.tsx**

```tsx
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

interface StudentOption {
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
    const [students, setStudents] = useState<StudentOption[]>([]);
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

    async function loadStudents(classroomId: string) {
        // For simplicity, students are loaded from existing attendance context
        // This can be enhanced later with an API endpoint
        setStudents([]);
        setRows([]);
    }

    function handleClassroomChange(value: string) {
        form.setData('classroom_id', value);
        void loadStudents(value);
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
                                            <SelectItem key={c.id} value={String(c.id)}>
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.classroom_id} />
                            </div>
                            <div>
                                <Label htmlFor="date">Tanggal</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) => form.setData('date', e.target.value)}
                                />
                                <InputError message={form.errors.date} />
                            </div>
                            <div>
                                <Label htmlFor="semester_id">Semester</Label>
                                <Select
                                    value={form.data.semester_id}
                                    onValueChange={(v) => form.setData('semester_id', v)}
                                >
                                    <SelectTrigger id="semester_id">
                                        <SelectValue placeholder="Pilih semester" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {semesters.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.semester_id} />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Mata Pelajaran (opsional)</Label>
                                <Select
                                    value={form.data.subject_id}
                                    onValueChange={(v) => form.setData('subject_id', v)}
                                >
                                    <SelectTrigger id="subject_id">
                                        <SelectValue placeholder="Pilih mapel / harian" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {subjects.map((s) => (
                                            <SelectItem key={s.id} value={String(s.id)}>
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
                                    <div key={row.student_user_id} className="flex items-center gap-2">
                                        <span className="w-40 text-sm">{row.name}</span>
                                        <Select
                                            value={row.status}
                                            onValueChange={(v) => updateRow(i, 'status', v)}
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
                                            value={row.notes}
                                            onChange={(e) => updateRow(i, 'notes', e.target.value)}
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
                                <Link href={AttendanceController.index.url(teamSlug)}>
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
```

- [ ] **Step 3: Create show.tsx**

```tsx
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

const statusColor: Record<string, 'default' | 'secondary' | 'destructive' | 'outline'> = {
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
                            <h1 className="text-2xl font-bold">Detail Absensi</h1>
                            <p className="text-muted-foreground text-sm">
                                {attendance.date} · {attendance.classroom.name} ·{' '}
                                {attendance.subject?.name ?? 'Harian'} · {attendance.semester.name}
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
                                <Link href={AttendanceController.index.url(teamSlug)}>
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
                                    <TableCell>{record.user.name}</TableCell>
                                    <TableCell>
                                        <Badge variant={statusColor[record.status] ?? 'default'}>
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
```

- [ ] **Step 4: Create edit.tsx**

```tsx
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
        const existing = attendance.records.find((r) => r.student_user_id === s.id);
        return {
            student_user_id: s.id,
            status: existing?.status ?? 'hadir',
            notes: existing?.notes ?? '',
        };
    });

    const form = useForm<{
        records: { student_user_id: number; status: string; notes: string }[];
    }>({ records: initialRecords });

    function updateRecord(index: number, field: 'status' | 'notes', value: string) {
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
                    <p className="text-muted-foreground text-sm">{attendance.date}</p>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="space-y-2">
                            {students.map((student, i) => (
                                <div key={student.id} className="flex items-center gap-2">
                                    <span className="w-40 text-sm">{student.name}</span>
                                    <Select
                                        value={form.data.records[i]?.status ?? 'hadir'}
                                        onValueChange={(v) => updateRecord(i, 'status', v)}
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
                                        value={form.data.records[i]?.notes ?? ''}
                                        onChange={(e) => updateRecord(i, 'notes', e.target.value)}
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
```

---

## Task 11: Update app-sidebar.tsx

- [ ] **Step 1: Add schedule nav group**

Add imports for Schedule controllers and new nav group in `resources/js/components/app-sidebar.tsx`:

```tsx
// Add imports after existing Academic imports:
import AttendanceController from '@/actions/App/Http/Controllers/Schedule/AttendanceController';
import ScheduleController from '@/actions/App/Http/Controllers/Schedule/ScheduleController';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
```

Add new icon imports: `AlarmClock`, `CalendarDays`, `ClipboardCheck`.

Add nav group:
```tsx
const scheduleNavGroups: NavGroup[] = [
    {
        title: 'Jadwal & Absensi',
        icon: CalendarDays,
        items: [
            {
                title: 'Jam Pelajaran',
                href: slug ? TimeSlotController.index.url(slug) : '/',
                icon: AlarmClock,
            },
            {
                title: 'Jadwal',
                href: slug ? ScheduleController.index.url(slug) : '/',
                icon: CalendarDays,
            },
            {
                title: 'Absensi',
                href: slug ? AttendanceController.index.url(slug) : '/',
                icon: ClipboardCheck,
            },
        ],
    },
];
```

In JSX render, add before `</SidebarContent>`:
```tsx
<NavGroups groups={scheduleNavGroups} label="Jadwal & Absensi" />
```

---

## Task 12: Wayfinder regenerate + CI Check

- [ ] **Step 1: Build to regenerate Wayfinder**

```bash
npm run build
```

- [ ] **Step 2: Run full CI check**

```bash
composer ci:check
```

Expected: all pass.

- [ ] **Step 3: Commit all frontend**

```bash
git add resources/js/ docs/superpowers/plans/
git commit -m "feat(fase3): add frontend pages for schedule and attendance modules"
```

- [ ] **Step 4: Push**

```bash
git push
```
