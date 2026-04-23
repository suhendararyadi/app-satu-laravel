import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileTextIcon } from 'lucide-react';

import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
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
import type { Classroom, Semester } from '@/types/academic';

interface StudentRow {
    user_id: number;
    name: string;
    report_card_id: number | null;
    has_report_card: boolean;
}

interface Props {
    classrooms: Classroom[];
    semesters: Semester[];
    students: StudentRow[];
    filters: { classroom_id?: string; semester_id?: string };
}

export default function ReportCardIndex({
    classrooms,
    semesters,
    students,
    filters,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    function applyFilter(key: string, value: string) {
        router.get(
            ReportCardController.index.url(teamSlug),
            { ...filters, [key]: value },
            { preserveState: true, replace: true },
        );
    }

    function generateAll() {
        const missing = students.filter((s) => !s.has_report_card);

        if (missing.length === 0) {
            return;
        }

        if (!confirm(`Generate ${missing.length} rapor yang belum ada?`)) {
            return;
        }

        missing.forEach((s) => {
            router.post(
                ReportCardController.store.url(teamSlug),
                {
                    semester_id: filters.semester_id,
                    classroom_id: filters.classroom_id,
                    student_user_id: s.user_id,
                },
                { preserveScroll: true },
            );
        });
    }

    const canGenerate = filters.classroom_id && filters.semester_id;

    return (
        <>
            <Head title="Rapor" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Rapor"
                        action={
                            canGenerate &&
                            students.some((s) => !s.has_report_card) ? (
                                <Button onClick={generateAll}>
                                    Generate Semua Rapor Kelas
                                </Button>
                            ) : undefined
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
                                <SelectValue placeholder="Pilih Kelas" />
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
                                <SelectValue placeholder="Pilih Semester" />
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

                    {canGenerate && students.length === 0 && (
                        <p className="text-sm text-muted-foreground">
                            Tidak ada siswa terdaftar di kelas ini.
                        </p>
                    )}

                    {students.length > 0 && (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Siswa</TableHead>
                                    <TableHead>Status Rapor</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((s) => (
                                    <TableRow key={s.user_id}>
                                        <TableCell className="font-medium">
                                            {s.name}
                                        </TableCell>
                                        <TableCell>
                                            {s.has_report_card ? (
                                                <Badge variant="default">
                                                    Sudah
                                                </Badge>
                                            ) : (
                                                <Badge variant="outline">
                                                    Belum
                                                </Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {s.has_report_card &&
                                            s.report_card_id ? (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link
                                                        href={ReportCardController.show.url(
                                                            {
                                                                current_team:
                                                                    teamSlug,
                                                                reportCard:
                                                                    s.report_card_id,
                                                            },
                                                        )}
                                                    >
                                                        Lihat
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() =>
                                                        router.post(
                                                            ReportCardController.store.url(
                                                                teamSlug,
                                                            ),
                                                            {
                                                                semester_id:
                                                                    filters.semester_id,
                                                                classroom_id:
                                                                    filters.classroom_id,
                                                                student_user_id:
                                                                    s.user_id,
                                                            },
                                                        )
                                                    }
                                                >
                                                    Generate
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    {!canGenerate && (
                        <div className="flex flex-col items-center gap-3 py-16 text-center text-muted-foreground">
                            <FileTextIcon className="h-10 w-10 opacity-40" />
                            <p>
                                Pilih kelas dan semester untuk melihat daftar
                                rapor.
                            </p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
