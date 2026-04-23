import { Head, Link, useForm, usePage } from '@inertiajs/react';

import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import { Textarea } from '@/components/ui/textarea';
import type {
    AssessmentCategory,
    ReportCard,
    SubjectGrade,
} from '@/types/academic';

interface AttendanceSummaryItem {
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    count: number;
}

interface Props {
    report_card: ReportCard;
    categories: AssessmentCategory[];
    subject_grades: SubjectGrade[];
    overall_average: number;
    attendance_summary: AttendanceSummaryItem[];
}

const statusColors: Record<string, string> = {
    hadir: 'bg-green-100 text-green-800',
    sakit: 'bg-yellow-100 text-yellow-800',
    izin: 'bg-blue-100 text-blue-800',
    alpa: 'bg-red-100 text-red-800',
};
const statusLabels: Record<string, string> = {
    hadir: 'Hadir',
    sakit: 'Sakit',
    izin: 'Izin',
    alpa: 'Alpa',
};

export default function ReportCardShow({
    report_card,
    categories,
    subject_grades,
    overall_average,
    attendance_summary,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const notesForm = useForm({
        homeroom_notes: report_card.homeroom_notes ?? '',
    });

    function submitNotes(e: React.FormEvent) {
        e.preventDefault();
        notesForm.patch(
            ReportCardController.update.url({
                current_team: teamSlug,
                reportCard: report_card.id,
            }),
        );
    }

    const student = report_card.student as
        | { name?: string; email?: string }
        | undefined;
    const classroom = report_card.classroom as { name?: string } | undefined;
    const semester = report_card.semester as { name?: string } | undefined;
    // Laravel serializes the 'generatedBy' relation as 'generated_by' (snake_case) which overrides the FK integer
    const generatedBy = (
        report_card as unknown as { generated_by: { name?: string } | null }
    ).generated_by;

    return (
        <>
            <Head title={`Rapor — ${student?.name ?? ''}`} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                {student?.name}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {classroom?.name} · {semester?.name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Digenerate oleh{' '}
                                {typeof generatedBy === 'object' &&
                                    generatedBy?.name}{' '}
                                pada{' '}
                                {report_card.generated_at
                                    ? new Date(
                                          report_card.generated_at,
                                      ).toLocaleString('id-ID')
                                    : '-'}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link
                                href={ReportCardController.index.url(teamSlug)}
                            >
                                Kembali
                            </Link>
                        </Button>
                    </div>

                    {/* Nilai */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Nilai Akademik</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Mata Pelajaran</TableHead>
                                        {categories.map((cat) => (
                                            <TableHead key={cat.id}>
                                                {cat.name} ({cat.weight}%)
                                            </TableHead>
                                        ))}
                                        <TableHead className="font-bold">
                                            Nilai Akhir
                                        </TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subject_grades.map((sg) => (
                                        <TableRow key={sg.subject_id}>
                                            <TableCell className="font-medium">
                                                {sg.subject_name}
                                            </TableCell>
                                            {categories.map((cat) => (
                                                <TableCell key={cat.id}>
                                                    {sg.category_scores[
                                                        cat.id
                                                    ] ?? 0}
                                                </TableCell>
                                            ))}
                                            <TableCell className="font-bold">
                                                {sg.final_grade}
                                            </TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-muted/50 font-semibold">
                                        <TableCell>Rata-rata</TableCell>
                                        {categories.map((cat) => (
                                            <TableCell key={cat.id} />
                                        ))}
                                        <TableCell className="font-bold">
                                            {overall_average}
                                        </TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Kehadiran */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Rekap Kehadiran</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <div className="flex gap-3">
                                {(
                                    ['hadir', 'sakit', 'izin', 'alpa'] as const
                                ).map((status) => {
                                    const item = attendance_summary.find(
                                        (a) => a.status === status,
                                    );

                                    return (
                                        <div
                                            key={status}
                                            className={`rounded-lg px-4 py-2 text-center ${statusColors[status]}`}
                                        >
                                            <div className="text-2xl font-bold">
                                                {item?.count ?? 0}
                                            </div>
                                            <div className="text-xs font-medium">
                                                {statusLabels[status]}
                                            </div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Catatan Wali Kelas */}
                    <Card>
                        <CardHeader>
                            <CardTitle>Catatan Wali Kelas</CardTitle>
                        </CardHeader>
                        <CardContent>
                            <form onSubmit={submitNotes} className="space-y-4">
                                <div>
                                    <Label htmlFor="homeroom_notes">
                                        Catatan
                                    </Label>
                                    <Textarea
                                        id="homeroom_notes"
                                        value={notesForm.data.homeroom_notes}
                                        onChange={(e) =>
                                            notesForm.setData(
                                                'homeroom_notes',
                                                e.target.value,
                                            )
                                        }
                                        placeholder="Catatan wali kelas untuk siswa ini..."
                                        rows={4}
                                    />
                                    <InputError
                                        message={
                                            notesForm.errors.homeroom_notes
                                        }
                                    />
                                </div>
                                <Button
                                    type="submit"
                                    disabled={notesForm.processing}
                                >
                                    Simpan Catatan
                                </Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
