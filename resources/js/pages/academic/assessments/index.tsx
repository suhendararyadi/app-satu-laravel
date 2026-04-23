import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardIcon } from 'lucide-react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
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
import type { Assessment, Classroom, Semester } from '@/types/academic';

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface PaginatedAssessments extends PaginationMeta {
    data: Assessment[];
}

interface Props {
    assessments: PaginatedAssessments;
    classrooms: Classroom[];
    semesters: Semester[];
    filters: { classroom_id?: string; semester_id?: string };
}

export default function AssessmentIndex({
    assessments,
    classrooms,
    semesters,
    filters,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    function applyFilter(key: string, value: string) {
        router.get(
            AssessmentController.index.url(teamSlug),
            { ...filters, [key]: value },
            { preserveState: true, replace: true },
        );
    }

    return (
        <>
            <Head title="Daftar Penilaian" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Daftar Penilaian"
                        action={
                            <Button asChild>
                                <Link
                                    href={AssessmentController.create.url(
                                        teamSlug,
                                    )}
                                >
                                    Tambah Assessment
                                </Link>
                            </Button>
                        }
                    />

                    <div className="flex gap-4">
                        <Select
                            value={filters.classroom_id ?? ''}
                            onValueChange={(v) =>
                                applyFilter('classroom_id', v)
                            }
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Semua Kelas" />
                            </SelectTrigger>
                            <SelectContent>
                                {classrooms.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>
                                        {c.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select
                            value={filters.semester_id ?? ''}
                            onValueChange={(v) => applyFilter('semester_id', v)}
                        >
                            <SelectTrigger className="w-48">
                                <SelectValue placeholder="Semua Semester" />
                            </SelectTrigger>
                            <SelectContent>
                                {semesters.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>
                                        {s.name}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    <DataTableWrapper
                        loading={false}
                        isEmpty={assessments.data.length === 0}
                        emptyState={{
                            icon: ClipboardIcon,
                            title: 'Belum ada assessment',
                            description:
                                'Buat assessment pertama untuk kelas Anda.',
                            action: {
                                label: 'Tambah Assessment',
                                href: AssessmentController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Mapel</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Nilai Terisi</TableHead>
                                    <TableHead className="w-24">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assessments.data.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell>
                                            <Link
                                                href={AssessmentController.show.url(
                                                    {
                                                        current_team: teamSlug,
                                                        assessment: a.id,
                                                    },
                                                )}
                                                className="font-medium hover:underline"
                                            >
                                                {a.title}
                                            </Link>
                                        </TableCell>
                                        <TableCell>
                                            {(
                                                a.category as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(
                                                a.subject as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>
                                            {(
                                                a.classroom as
                                                    | { name?: string }
                                                    | undefined
                                            )?.name ?? '-'}
                                        </TableCell>
                                        <TableCell>{a.date}</TableCell>
                                        <TableCell>
                                            {a.scores_filled ?? 0} /{' '}
                                            {a.scores_total ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={AssessmentController.edit.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            assessment: a.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableWrapper>
                    <Pagination meta={assessments} />
                </div>
            </div>
        </>
    );
}
