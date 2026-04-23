import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { Assessment, Score } from '@/types/academic';

interface Props {
    assessment: Assessment;
    scores: Score[];
}

export default function AssessmentShow({ assessment, scores }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        scores: scores.map((s) => ({
            student_user_id: s.student_user_id,
            score: s.score ?? '',
            notes: s.notes ?? '',
        })),
    });

    function updateRow(index: number, field: 'score' | 'notes', value: string) {
        const updated = form.data.scores.map((r, i) =>
            i === index ? { ...r, [field]: value } : r,
        );
        form.setData('scores', updated);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(
            AssessmentController.storeScores.url({
                current_team: teamSlug,
                assessment: assessment.id,
            }),
        );
    }

    const classroom = assessment.classroom as { name?: string } | undefined;
    const subject = assessment.subject as { name?: string } | undefined;
    const semester = assessment.semester as { name?: string } | undefined;
    const category = assessment.category as { name?: string } | undefined;

    return (
        <>
            <Head title={assessment.title} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">
                                {assessment.title}
                            </h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {classroom?.name} · {subject?.name} ·{' '}
                                {category?.name} · {semester?.name} ·{' '}
                                {assessment.date}
                            </p>
                            <p className="text-sm text-muted-foreground">
                                Nilai Maks: {assessment.max_score}
                            </p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link
                                    href={AssessmentController.edit.url({
                                        current_team: teamSlug,
                                        assessment: assessment.id,
                                    })}
                                >
                                    Edit
                                </Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link
                                    href={AssessmentController.index.url(
                                        teamSlug,
                                    )}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <h2 className="text-lg font-semibold">Input Nilai</h2>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Siswa</TableHead>
                                    <TableHead className="w-32">
                                        Nilai (0–{assessment.max_score})
                                    </TableHead>
                                    <TableHead>Catatan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {form.data.scores.map((row, i) => (
                                    <TableRow key={row.student_user_id}>
                                        <TableCell className="font-medium">
                                            {scores[i].name}
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min={0}
                                                max={parseFloat(
                                                    String(
                                                        assessment.max_score,
                                                    ),
                                                )}
                                                step={0.01}
                                                value={row.score}
                                                onChange={(e) =>
                                                    updateRow(
                                                        i,
                                                        'score',
                                                        e.target.value,
                                                    )
                                                }
                                                className="w-28"
                                            />
                                            <InputError
                                                message={
                                                    (
                                                        form.errors as Record<
                                                            string,
                                                            string | undefined
                                                        >
                                                    )[`scores.${i}.score`]
                                                }
                                            />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={row.notes}
                                                onChange={(e) =>
                                                    updateRow(
                                                        i,
                                                        'notes',
                                                        e.target.value,
                                                    )
                                                }
                                                placeholder="Catatan (opsional)"
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <InputError
                            message={form.errors.scores as string | undefined}
                        />
                        <Button type="submit" disabled={form.processing}>
                            Simpan Semua Nilai
                        </Button>
                    </form>
                </div>
            </div>
        </>
    );
}
