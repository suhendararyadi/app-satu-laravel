import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import ClassroomController from '@/actions/App/Http/Controllers/Academic/ClassroomController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
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
    StudentEnrollment,
} from '@/types/academic';

interface StudentOption {
    id: number;
    name: string;
    email: string;
}

interface Props {
    classroom: Classroom;
    students: StudentOption[];
}

export default function Show({ classroom, students }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [unenrollId, setUnenrollId] = useState<number | null>(null);

    const form = useForm({ user_id: '', student_number: '' });

    const enrollments = (classroom.enrollments ?? []) as StudentEnrollment[];
    const grade = classroom.grade as Grade | undefined;
    const year = classroom.academic_year as AcademicYear | undefined;

    function submitEnroll(e: React.FormEvent) {
        e.preventDefault();
        form.post(
            ClassroomController.enrollStudent.url({
                current_team: teamSlug,
                classroom: classroom.id,
            }),
            { onSuccess: () => form.reset() },
        );
    }

    function confirmUnenroll() {
        if (!unenrollId) {
            return;
        }

        router.delete(
            ClassroomController.unenrollStudent.url({
                current_team: teamSlug,
                classroom: classroom.id,
                enrollment: unenrollId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setUnenrollId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title={classroom.name} />
            <div className="px-4 py-6">
                <div className="space-y-8">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                {classroom.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {grade?.name} — {year?.name}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link
                                href={ClassroomController.index.url(teamSlug)}
                            >
                                Kembali
                            </Link>
                        </Button>
                    </div>

                    <div className="space-y-3">
                        <h2 className="text-lg font-semibold">Daftar Siswa</h2>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>NIS</TableHead>
                                    <TableHead className="w-24">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {enrollments.map((enrollment) => (
                                    <TableRow key={enrollment.id}>
                                        <TableCell>
                                            {(
                                                enrollment.user as
                                                    | { name: string }
                                                    | undefined
                                            )?.name ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            {enrollment.student_number ?? '—'}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => {
                                                    setUnenrollId(
                                                        enrollment.id,
                                                    );
                                                    setConfirmOpen(true);
                                                }}
                                            >
                                                Keluarkan
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </div>

                    <div className="max-w-md space-y-4">
                        <h2 className="text-lg font-semibold">Tambah Siswa</h2>
                        <form onSubmit={submitEnroll} className="space-y-4">
                            <div>
                                <Label htmlFor="user_id">Siswa</Label>
                                <Select
                                    value={form.data.user_id}
                                    onValueChange={(v) =>
                                        form.setData('user_id', v)
                                    }
                                >
                                    <SelectTrigger id="user_id">
                                        <SelectValue placeholder="Pilih siswa" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {students.map((student) => (
                                            <SelectItem
                                                key={student.id}
                                                value={String(student.id)}
                                            >
                                                {student.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                            </div>
                            <div>
                                <Label htmlFor="student_number">
                                    NIS (opsional)
                                </Label>
                                <Input
                                    id="student_number"
                                    value={form.data.student_number}
                                    onChange={(e) =>
                                        form.setData(
                                            'student_number',
                                            e.target.value,
                                        )
                                    }
                                />
                            </div>
                            <Button type="submit" disabled={form.processing}>
                                Tambahkan
                            </Button>
                        </form>
                    </div>

                    <ConfirmDeleteDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        onConfirm={confirmUnenroll}
                    />
                </div>
            </div>
        </>
    );
}
