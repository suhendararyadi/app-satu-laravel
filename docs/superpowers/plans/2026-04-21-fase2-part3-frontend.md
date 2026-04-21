# Fase 2: Foundation Akademik — Part 3: Frontend (Tasks 11–17)

> Back to index: [2026-04-21-fase2-foundation-akademik.md](./2026-04-21-fase2-foundation-akademik.md)

> **Note on Wayfinder:** Action files in `resources/js/actions/` are auto-generated. Run `npm run build` once after Task 5 (routes) before implementing frontend tasks, so imports resolve correctly.

---

## Task 11: React Pages — academic/years/ (5 files)

- [ ] Create `resources/js/pages/academic/years/index.tsx`:

```tsx
import type { PageProps } from '@/types';
import type { AcademicYear } from '@/types/academic';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
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
import { Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
    years: AcademicYear[];
}

export default function Index({ years }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    function handleDelete(id: number) {
        setDeleteId(id);
        setConfirmOpen(true);
    }

    function confirmDelete() {
        if (!deleteId) return;
        router.delete(
            AcademicYearController.destroy.url({
                current_team: teamSlug,
                year: deleteId,
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
        <div className="space-y-4">
            <div className="flex items-center justify-between">
                <h1 className="text-2xl font-bold">Tahun Ajaran</h1>
                <Button asChild>
                    <Link href={AcademicYearController.create.url(teamSlug)}>
                        Tambah Tahun Ajaran
                    </Link>
                </Button>
            </div>

            <Table>
                <TableHeader>
                    <TableRow>
                        <TableHead>Nama</TableHead>
                        <TableHead>Tahun</TableHead>
                        <TableHead>Semester</TableHead>
                        <TableHead>Status</TableHead>
                        <TableHead className="w-48">Aksi</TableHead>
                    </TableRow>
                </TableHeader>
                <TableBody>
                    {years.map((year) => (
                        <TableRow key={year.id}>
                            <TableCell className="font-medium">
                                {year.name}
                            </TableCell>
                            <TableCell>
                                {year.start_year}/{year.end_year}
                            </TableCell>
                            <TableCell>
                                {year.semesters?.length ?? 0} semester
                            </TableCell>
                            <TableCell>
                                {year.is_active ? (
                                    <Badge variant="default">Aktif</Badge>
                                ) : (
                                    <Badge variant="secondary">
                                        Tidak Aktif
                                    </Badge>
                                )}
                            </TableCell>
                            <TableCell className="space-x-2">
                                {!year.is_active && (
                                    <Button
                                        size="sm"
                                        variant="outline"
                                        onClick={() =>
                                            router.post(
                                                AcademicYearController.activate.url(
                                                    {
                                                        current_team: teamSlug,
                                                        year: year.id,
                                                    },
                                                ),
                                                {},
                                                { preserveScroll: true },
                                            )
                                        }
                                    >
                                        Aktifkan
                                    </Button>
                                )}
                                <Button size="sm" variant="outline" asChild>
                                    <Link
                                        href={AcademicYearController.edit.url({
                                            current_team: teamSlug,
                                            year: year.id,
                                        })}
                                    >
                                        Edit
                                    </Link>
                                </Button>
                                <Button
                                    size="sm"
                                    variant="destructive"
                                    onClick={() => handleDelete(year.id)}
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
    );
}
```

- [ ] Create `resources/js/pages/academic/years/create.tsx`:

```tsx
import type { PageProps } from '@/types';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/react';

export default function Create(_props: PageProps) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({ name: '', start_year: '', end_year: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AcademicYearController.store.url(teamSlug));
    }

    return (
        <div className="max-w-lg space-y-6">
            <h1 className="text-2xl font-bold">Tambah Tahun Ajaran</h1>
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
                    <Label htmlFor="start_year">Tahun Mulai</Label>
                    <Input
                        id="start_year"
                        type="number"
                        value={form.data.start_year}
                        onChange={(e) =>
                            form.setData('start_year', e.target.value)
                        }
                    />
                    <InputError message={form.errors.start_year} />
                </div>
                <div>
                    <Label htmlFor="end_year">Tahun Selesai</Label>
                    <Input
                        id="end_year"
                        type="number"
                        value={form.data.end_year}
                        onChange={(e) =>
                            form.setData('end_year', e.target.value)
                        }
                    />
                    <InputError message={form.errors.end_year} />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={AcademicYearController.index.url(teamSlug)}>
                            Batal
                        </Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
```

- [ ] Create `resources/js/pages/academic/years/edit.tsx`:

```tsx
import type { PageProps } from '@/types';
import type { AcademicYear } from '@/types/academic';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import { ConfirmDeleteDialog } from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

interface Props extends PageProps {
    year: AcademicYear;
}

export default function Edit({ year }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteSemesterId, setDeleteSemesterId] = useState<number | null>(
        null,
    );

    const form = useForm({
        name: year.name,
        start_year: String(year.start_year),
        end_year: String(year.end_year),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            AcademicYearController.update.url({
                current_team: teamSlug,
                year: year.id,
            }),
        );
    }

    function confirmDeleteSemester() {
        if (!deleteSemesterId) return;
        router.delete(
            AcademicYearController.destroySemester.url({
                current_team: teamSlug,
                year: year.id,
                semester: deleteSemesterId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteSemesterId(null);
                },
            },
        );
    }

    return (
        <div className="max-w-2xl space-y-8">
            <h1 className="text-2xl font-bold">Edit Tahun Ajaran</h1>

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
                    <Label htmlFor="start_year">Tahun Mulai</Label>
                    <Input
                        id="start_year"
                        type="number"
                        value={form.data.start_year}
                        onChange={(e) =>
                            form.setData('start_year', e.target.value)
                        }
                    />
                    <InputError message={form.errors.start_year} />
                </div>
                <div>
                    <Label htmlFor="end_year">Tahun Selesai</Label>
                    <Input
                        id="end_year"
                        type="number"
                        value={form.data.end_year}
                        onChange={(e) =>
                            form.setData('end_year', e.target.value)
                        }
                    />
                    <InputError message={form.errors.end_year} />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link href={AcademicYearController.index.url(teamSlug)}>
                            Kembali
                        </Link>
                    </Button>
                </div>
            </form>

            <div className="space-y-3">
                <div className="flex items-center justify-between">
                    <h2 className="text-lg font-semibold">Semester</h2>
                    <Button size="sm" asChild>
                        <Link
                            href={AcademicYearController.createSemester.url({
                                current_team: teamSlug,
                                year: year.id,
                            })}
                        >
                            Tambah Semester
                        </Link>
                    </Button>
                </div>
                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama</TableHead>
                            <TableHead>Urutan</TableHead>
                            <TableHead className="w-32">Aksi</TableHead>
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {(year.semesters ?? []).map((semester) => (
                            <TableRow key={semester.id}>
                                <TableCell>{semester.name}</TableCell>
                                <TableCell>{semester.order}</TableCell>
                                <TableCell className="space-x-2">
                                    <Button size="sm" variant="outline" asChild>
                                        <Link
                                            href={AcademicYearController.editSemester.url(
                                                {
                                                    current_team: teamSlug,
                                                    year: year.id,
                                                    semester: semester.id,
                                                },
                                            )}
                                        >
                                            Edit
                                        </Link>
                                    </Button>
                                    <Button
                                        size="sm"
                                        variant="destructive"
                                        onClick={() => {
                                            setDeleteSemesterId(semester.id);
                                            setConfirmOpen(true);
                                        }}
                                    >
                                        Hapus
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                onConfirm={confirmDeleteSemester}
            />
        </div>
    );
}
```

- [ ] Create `resources/js/pages/academic/years/semester-create.tsx`:

```tsx
import type { PageProps } from '@/types';
import type { AcademicYear } from '@/types/academic';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/react';

interface Props extends PageProps {
    year: AcademicYear;
}

export default function SemesterCreate({ year }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const form = useForm({ name: '', order: '1' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(
            AcademicYearController.storeSemester.url({
                current_team: teamSlug,
                year: year.id,
            }),
        );
    }

    return (
        <div className="max-w-lg space-y-6">
            <h1 className="text-2xl font-bold">
                Tambah Semester — {year.name}
            </h1>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">Nama Semester</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div>
                    <Label htmlFor="order">Urutan</Label>
                    <Input
                        id="order"
                        type="number"
                        value={form.data.order}
                        onChange={(e) => form.setData('order', e.target.value)}
                    />
                    <InputError message={form.errors.order} />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link
                            href={AcademicYearController.edit.url({
                                current_team: teamSlug,
                                year: year.id,
                            })}
                        >
                            Batal
                        </Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
```

- [ ] Create `resources/js/pages/academic/years/semester-edit.tsx`:

```tsx
import type { PageProps } from '@/types';
import type { AcademicYear, Semester } from '@/types/academic';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Link, useForm, usePage } from '@inertiajs/react';

interface Props extends PageProps {
    year: AcademicYear;
    semester: Semester;
}

export default function SemesterEdit({ year, semester }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const form = useForm({
        name: semester.name,
        order: String(semester.order),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            AcademicYearController.updateSemester.url({
                current_team: teamSlug,
                year: year.id,
                semester: semester.id,
            }),
        );
    }

    return (
        <div className="max-w-lg space-y-6">
            <h1 className="text-2xl font-bold">Edit Semester — {year.name}</h1>
            <form onSubmit={submit} className="space-y-4">
                <div>
                    <Label htmlFor="name">Nama Semester</Label>
                    <Input
                        id="name"
                        value={form.data.name}
                        onChange={(e) => form.setData('name', e.target.value)}
                    />
                    <InputError message={form.errors.name} />
                </div>
                <div>
                    <Label htmlFor="order">Urutan</Label>
                    <Input
                        id="order"
                        type="number"
                        value={form.data.order}
                        onChange={(e) => form.setData('order', e.target.value)}
                    />
                    <InputError message={form.errors.order} />
                </div>
                <div className="flex gap-2">
                    <Button type="submit" disabled={form.processing}>
                        Simpan
                    </Button>
                    <Button type="button" variant="outline" asChild>
                        <Link
                            href={AcademicYearController.edit.url({
                                current_team: teamSlug,
                                year: year.id,
                            })}
                        >
                            Batal
                        </Link>
                    </Button>
                </div>
            </form>
        </div>
    );
}
```

---

## Task 12: React Pages — academic/grades/ (3 files)

- [ ] Create `resources/js/pages/academic/grades/index.tsx` — table of grades (name, order) with edit/delete actions and "Tambah Tingkat" button. Pattern identical to years/index.tsx but for `Grade` type and `GradeController`.

- [ ] Create `resources/js/pages/academic/grades/create.tsx` — form with `name` (text) and `order` (number) fields. `form.post(GradeController.store.url(teamSlug))`.

- [ ] Create `resources/js/pages/academic/grades/edit.tsx` — pre-filled form with `name` and `order`. `form.patch(GradeController.update.url({ current_team: teamSlug, grade: grade.id }))`.

All three files follow exact same pattern as years pages. Import `GradeController from '@/actions/App/Http/Controllers/Academic/GradeController'` and `Grade from '@/types/academic'`.

---

## Task 13: React Pages — academic/subjects/ (3 files)

- [ ] Create `resources/js/pages/academic/subjects/index.tsx` — table with `name`, `code` (show `—` if null), edit/delete actions.

- [ ] Create `resources/js/pages/academic/subjects/create.tsx` — form with `name` (required) and `code` (optional). `form.post(SubjectController.store.url(teamSlug))`.

- [ ] Create `resources/js/pages/academic/subjects/edit.tsx` — pre-filled form with `name` and `code`. `form.patch(SubjectController.update.url(...))`.

Import `SubjectController from '@/actions/App/Http/Controllers/Academic/SubjectController'` and `Subject from '@/types/academic'`.

---

## Task 14: React Pages — academic/classrooms/ (4 files)

- [ ] Create `resources/js/pages/academic/classrooms/index.tsx` — table with `name`, `grade.name`, `academic_year.name`; create/show/delete actions.

- [ ] Create `resources/js/pages/academic/classrooms/create.tsx`:

```tsx
// Key fields: name (text), academic_year_id (select from academicYears), grade_id (select from grades)
// form.post(ClassroomController.store.url(teamSlug))

interface Props extends PageProps {
    academicYears: AcademicYear[];
    grades: Grade[];
}
```

- [ ] Create `resources/js/pages/academic/classrooms/edit.tsx` — pre-filled form with selects for academicYear and grade.

- [ ] Create `resources/js/pages/academic/classrooms/show.tsx`:

```tsx
// Shows: classroom info (name, year, grade)
// Lists enrolled students (enrollments.user.name, student_number)
// "Tambah Siswa" inline form: select user_id from students list, optional student_number input
// Unenroll button per row

interface Props extends PageProps {
    classroom: Classroom; // includes enrollments.user
    students: Array<{ id: number; name: string; email: string }>;
}

// Enroll form: useForm({ user_id: '', student_number: '' })
// Submit: form.post(ClassroomController.enrollStudent.url({ current_team: teamSlug, classroom: classroom.id }))
// Unenroll: router.delete(ClassroomController.unenrollStudent.url({ current_team: teamSlug, classroom: classroom.id, enrollment: id }))
```

---

## Task 15: React Pages — academic/assignments/ (1 file)

- [ ] Create `resources/js/pages/academic/assignments/index.tsx`:

```tsx
// Page shows two sections:
// 1. Table of existing assignments: teacher name, subject name, classroom name (with grade), academic year, delete button
// 2. "Tambah Penugasan" form below table with 4 selects:
//    - academic_year_id (select from academicYears)
//    - subject_id (select from subjects)
//    - classroom_id (select from classrooms, show "grade.name - classroom.name")
//    - user_id (select from teachers, show name)

interface Props extends PageProps {
    assignments: TeacherAssignment[];
    academicYears: AcademicYear[];
    subjects: Subject[];
    classrooms: Classroom[]; // includes grade
    teachers: Array<{ id: number; name: string }>;
}

// Add form: useForm({ academic_year_id: '', subject_id: '', classroom_id: '', user_id: '' })
// Submit: form.post(TeacherAssignmentController.store.url(teamSlug))
// Delete: router.delete(TeacherAssignmentController.destroy.url({ current_team: teamSlug, assignment: id }))
```

---

## Task 16: Update app-sidebar.tsx

- [ ] Open `resources/js/components/app-sidebar.tsx`
- [ ] Add imports at the top (after existing controller imports):

```tsx
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import ClassroomController from '@/actions/App/Http/Controllers/Academic/ClassroomController';
import GradeController from '@/actions/App/Http/Controllers/Academic/GradeController';
import SubjectController from '@/actions/App/Http/Controllers/Academic/SubjectController';
import TeacherAssignmentController from '@/actions/App/Http/Controllers/Academic/TeacherAssignmentController';
```

- [ ] Add icon imports to the lucide-react import line: `BookOpenCheck, Calendar, ClipboardList, GraduationCap, Layers, Users`

- [ ] Inside the component, add `academicNavGroups` (after the existing cms/settings groups):

```tsx
const academicNavGroups = [
    {
        title: 'Akademik',
        icon: GraduationCap,
        items: [
            {
                title: 'Tahun Ajaran',
                href: AcademicYearController.index.url(slug),
                icon: Calendar,
            },
            {
                title: 'Tingkat',
                href: GradeController.index.url(slug),
                icon: Layers,
            },
            {
                title: 'Mata Pelajaran',
                href: SubjectController.index.url(slug),
                icon: BookOpenCheck,
            },
            {
                title: 'Kelas',
                href: ClassroomController.index.url(slug),
                icon: Users,
            },
            {
                title: 'Penugasan Guru',
                href: TeacherAssignmentController.index.url(slug),
                icon: ClipboardList,
            },
        ],
    },
];
```

- [ ] Add `<NavGroups groups={academicNavGroups} label="Akademik" />` in the sidebar JSX, below the CMS NavGroups block
- [ ] Run `npm run types:check`

---

## Task 17: CI Check

- [ ] Run `./vendor/bin/pint --dirty --format agent`
- [ ] Run `npm run types:check` — fix any TypeScript errors before proceeding
- [ ] Run `npm run lint:check` — fix ESLint errors if any
- [ ] Run `./vendor/bin/pest` — all tests must pass

If any test fails, fix before marking complete. Do not skip failing tests.
