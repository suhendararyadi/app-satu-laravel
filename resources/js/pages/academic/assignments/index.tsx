import { Head, router, useForm, usePage } from '@inertiajs/react';
import { ClipboardIcon } from 'lucide-react';
import { useState } from 'react';

import TeacherAssignmentController from '@/actions/App/Http/Controllers/Academic/TeacherAssignmentController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type {
    AcademicYear,
    Classroom,
    Grade,
    Subject,
    TeacherAssignment,
} from '@/types/academic';

interface Teacher {
    id: number;
    name: string;
}

interface Props {
    assignments: TeacherAssignment[];
    academicYears: AcademicYear[];
    subjects: Subject[];
    classrooms: Classroom[];
    teachers: Teacher[];
}

export default function Index({
    assignments,
    academicYears,
    subjects,
    classrooms,
    teachers,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);

    const form = useForm({
        academic_year_id: '',
        subject_id: '',
        classroom_id: '',
        user_id: '',
    });

    function submitStore(e: React.FormEvent) {
        e.preventDefault();
        form.post(TeacherAssignmentController.store.url(teamSlug), {
            onSuccess: () => form.reset(),
        });
    }

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            TeacherAssignmentController.destroy.url({
                current_team: teamSlug,
                assignment: deleteId,
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
            <Head title="Penugasan Guru" />
            <div className="px-4 py-6">
                <div className="space-y-8">
                    <PageHeader title="Penugasan Guru" />

                    <DataTableWrapper
                        loading={false}
                        isEmpty={assignments.length === 0}
                        emptyState={{
                            icon: ClipboardIcon,
                            title: 'Belum ada penugasan guru',
                            description: 'Tambah penugasan guru menggunakan form di bawah.',
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Guru</TableHead>
                                    <TableHead>Mata Pelajaran</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Tahun Ajaran</TableHead>
                                    <TableHead className="w-24">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assignments.map((assignment) => (
                                    <TableRow key={assignment.id}>
                                        <TableCell>
                                            {(assignment.user as Teacher | undefined)?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {(assignment.subject as Subject | undefined)?.name ??
                                                '—'}
                                        </TableCell>
                                        <TableCell>
                                            {(() => {
                                                const classroom = assignment.classroom as
                                                    | (Classroom & { grade?: Grade })
                                                    | undefined;

                                                if (!classroom) {
                                                    return '—';
                                                }

                                                return classroom.grade
                                                    ? `${classroom.grade.name} - ${classroom.name}`
                                                    : classroom.name;
                                            })()}
                                        </TableCell>
                                        <TableCell>
                                            {(assignment.academic_year as AcademicYear | undefined)
                                                ?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => {
                                                    setDeleteId(assignment.id);
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
                    </DataTableWrapper>

                    <div className="max-w-lg space-y-4">
                        <h2 className="text-lg font-semibold">Tambah Penugasan</h2>
                        <form onSubmit={submitStore} className="space-y-4">
                            <div>
                                <Label htmlFor="academic_year_id">Tahun Ajaran</Label>
                                <Select
                                    value={form.data.academic_year_id}
                                    onValueChange={(v) => form.setData('academic_year_id', v)}
                                >
                                    <SelectTrigger id="academic_year_id">
                                        <SelectValue placeholder="Pilih tahun ajaran" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {academicYears.map((year) => (
                                            <SelectItem key={year.id} value={String(year.id)}>
                                                {year.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Mata Pelajaran</Label>
                                <Select
                                    value={form.data.subject_id}
                                    onValueChange={(v) => form.setData('subject_id', v)}
                                >
                                    <SelectTrigger id="subject_id">
                                        <SelectValue placeholder="Pilih mata pelajaran" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {subjects.map((subject) => (
                                            <SelectItem key={subject.id} value={String(subject.id)}>
                                                {subject.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
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
                                        {classrooms.map((classroom) => {
                                            const grade = classroom.grade as Grade | undefined;

                                            return (
                                                <SelectItem
                                                    key={classroom.id}
                                                    value={String(classroom.id)}
                                                >
                                                    {grade
                                                        ? `${grade.name} - ${classroom.name}`
                                                        : classroom.name}
                                                </SelectItem>
                                            );
                                        })}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="user_id">Guru</Label>
                                <Select
                                    value={form.data.user_id}
                                    onValueChange={(v) => form.setData('user_id', v)}
                                >
                                    <SelectTrigger id="user_id">
                                        <SelectValue placeholder="Pilih guru" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {teachers.map((teacher) => (
                                            <SelectItem
                                                key={teacher.id}
                                                value={String(teacher.id)}
                                            >
                                                {teacher.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                        </form>
                    </div>

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
