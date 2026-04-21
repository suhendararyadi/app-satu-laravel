import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import StudentImportController from '@/actions/App/Http/Controllers/Students/StudentImportController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import { Button } from '@/components/ui/button';
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

interface StudentClassroom {
    id: number;
    name: string;
    student_number: string | null;
}

interface Student {
    id: number;
    name: string;
    email: string;
    joined_at: string;
    classrooms: StudentClassroom[];
}

interface ClassroomOption {
    id: number;
    name: string;
}

interface Props {
    students: Student[];
    classrooms: ClassroomOption[];
}

export default function StudentsIndex({ students, classrooms }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [classroomFilter, setClassroomFilter] = useState<string>('all');

    const filtered =
        classroomFilter === 'all'
            ? students
            : students.filter((s) =>
                  s.classrooms.some((c) => String(c.id) === classroomFilter),
              );

    function confirmDelete() {
        if (!deleteId) {
            return;
        }

        router.delete(
            StudentController.destroy.url({
                current_team: teamSlug,
                user: deleteId,
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
            <Head title="Manajemen Siswa" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <div className="flex items-center justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                Manajemen Siswa
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {students.length} siswa terdaftar
                            </p>
                        </div>
                        <Button asChild>
                            <Link
                                href={StudentImportController.create.url(
                                    teamSlug,
                                )}
                            >
                                Import Siswa
                            </Link>
                        </Button>
                    </div>

                    <div className="flex items-center gap-3">
                        <span className="text-sm text-muted-foreground">
                            Filter kelas:
                        </span>
                        <Select
                            value={classroomFilter}
                            onValueChange={setClassroomFilter}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Semua kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">Semua kelas</SelectItem>
                                {classrooms.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <Table>
                        <TableHeader>
                            <TableRow>
                                <TableHead>Nama</TableHead>
                                <TableHead>Email</TableHead>
                                <TableHead>Kelas</TableHead>
                                <TableHead>NIS</TableHead>
                                <TableHead className="w-24">Aksi</TableHead>
                            </TableRow>
                        </TableHeader>
                        <TableBody>
                            {filtered.map((student) => (
                                <TableRow key={student.id}>
                                    <TableCell>{student.name}</TableCell>
                                    <TableCell className="text-muted-foreground">
                                        {student.email}
                                    </TableCell>
                                    <TableCell>
                                        {student.classrooms.length > 0
                                            ? student.classrooms
                                                  .map((c) => c.name)
                                                  .join(', ')
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        {student.classrooms.length > 0
                                            ? (student.classrooms[0]
                                                  .student_number ?? '—')
                                            : '—'}
                                    </TableCell>
                                    <TableCell>
                                        <Button
                                            size="sm"
                                            variant="destructive"
                                            onClick={() => {
                                                setDeleteId(student.id);
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

                    {filtered.length === 0 && (
                        <p className="py-8 text-center text-sm text-muted-foreground">
                            Tidak ada siswa ditemukan.
                        </p>
                    )}
                </div>
            </div>

            <ConfirmDeleteDialog
                open={confirmOpen}
                onOpenChange={setConfirmOpen}
                onConfirm={confirmDelete}
            />
        </>
    );
}
