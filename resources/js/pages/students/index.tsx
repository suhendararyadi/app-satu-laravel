import { Head, Link, router, usePage } from '@inertiajs/react';
import { UsersIcon } from 'lucide-react';
import { useEffect, useState } from 'react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import StudentImportController from '@/actions/App/Http/Controllers/Students/StudentImportController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Pagination } from '@/components/ui/pagination';
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

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface PaginatedStudents extends PaginationMeta {
    data: Student[];
}

interface Props {
    students: PaginatedStudents;
    classrooms: ClassroomOption[];
    filters: {
        search: string;
    };
}

export default function StudentsIndex({
    students,
    classrooms,
    filters,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteId, setDeleteId] = useState<number | null>(null);
    const [search, setSearch] = useState(filters.search ?? '');
    const [loading, setLoading] = useState(false);

    // Debounce search
    useEffect(() => {
        const timer = setTimeout(() => {
            if (search !== filters.search) {
                setLoading(true);
                router.get(
                    StudentController.index.url(teamSlug),
                    { search: search || undefined },
                    {
                        preserveState: true,
                        preserveScroll: true,
                        onFinish: () => setLoading(false),
                    },
                );
            }
        }, 350);

        return () => clearTimeout(timer);
    }, [search]);

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
                    <PageHeader
                        title="Manajemen Siswa"
                        action={
                            <Button asChild>
                                <Link
                                    href={StudentImportController.create.url(
                                        teamSlug,
                                    )}
                                >
                                    Import Siswa
                                </Link>
                            </Button>
                        }
                    />

                    <div className="flex items-center gap-3">
                        <Input
                            placeholder="Cari nama atau email..."
                            value={search}
                            onChange={(e) => setSearch(e.target.value)}
                            className="max-w-xs"
                        />
                        <Select defaultValue="all">
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

                    <DataTableWrapper
                        loading={loading}
                        isEmpty={students.data.length === 0}
                        emptyState={{
                            icon: UsersIcon,
                            title: 'Belum ada siswa',
                            description:
                                'Import data siswa untuk mulai mengelola kelas.',
                            action: {
                                label: 'Import Siswa',
                                href: StudentImportController.create.url(
                                    teamSlug,
                                ),
                            },
                        }}
                    >
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
                                {students.data.map((student) => (
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
                    </DataTableWrapper>

                    <Pagination
                        meta={students}
                        preserveParams={{ search: search || undefined }}
                    />
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
