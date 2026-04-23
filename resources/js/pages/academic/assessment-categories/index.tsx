import { Head, Link, router, usePage } from '@inertiajs/react';
import { TagIcon } from 'lucide-react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
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
import type { AssessmentCategory } from '@/types/academic';

interface Props {
    categories: AssessmentCategory[];
    total_weight: string;
}

export default function AssessmentCategoryIndex({
    categories,
    total_weight,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';
    const totalNum = parseFloat(total_weight);

    function handleDelete(id: number) {
        if (!confirm('Hapus kategori ini?')) {
            return;
        }

        router.delete(
            AssessmentCategoryController.destroy.url({
                current_team: teamSlug,
                assessmentCategory: id,
            }),
        );
    }

    return (
        <>
            <Head title="Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Kategori Penilaian"
                        action={
                            <Button asChild>
                                <Link
                                    href={AssessmentCategoryController.create.url(
                                        teamSlug,
                                    )}
                                >
                                    Tambah Kategori
                                </Link>
                            </Button>
                        }
                    />

                    {totalNum !== 100 && (
                        <div className="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            Total bobot saat ini <strong>{totalNum}%</strong>.
                            Harus tepat 100% agar nilai akhir dapat dihitung.
                        </div>
                    )}

                    <DataTableWrapper
                        loading={false}
                        isEmpty={categories.length === 0}
                        emptyState={{
                            icon: TagIcon,
                            title: 'Belum ada kategori penilaian',
                            description:
                                'Buat kategori seperti Tugas, UH, UTS, UAS.',
                            action: {
                                label: 'Tambah Kategori',
                                href: AssessmentCategoryController.create.url(
                                    teamSlug,
                                ),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Bobot (%)</TableHead>
                                    <TableHead>Jumlah Assessment</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {categories.map((cat) => (
                                    <TableRow key={cat.id}>
                                        <TableCell className="font-medium">
                                            {cat.name}
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant={
                                                    totalNum === 100
                                                        ? 'default'
                                                        : 'destructive'
                                                }
                                            >
                                                {cat.weight}%
                                            </Badge>
                                        </TableCell>
                                        <TableCell>
                                            {cat.assessments_count ?? 0}
                                        </TableCell>
                                        <TableCell>
                                            <div className="flex gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    asChild
                                                >
                                                    <Link
                                                        href={AssessmentCategoryController.edit.url(
                                                            {
                                                                current_team:
                                                                    teamSlug,
                                                                assessmentCategory:
                                                                    cat.id,
                                                            },
                                                        )}
                                                    >
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    disabled={
                                                        (cat.assessments_count ??
                                                            0) > 0
                                                    }
                                                    onClick={() =>
                                                        handleDelete(cat.id)
                                                    }
                                                >
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="bg-muted/50 font-semibold">
                                    <TableCell>Total</TableCell>
                                    <TableCell>
                                        <Badge
                                            variant={
                                                totalNum === 100
                                                    ? 'default'
                                                    : 'destructive'
                                            }
                                        >
                                            {totalNum}%
                                        </Badge>
                                    </TableCell>
                                    <TableCell colSpan={2} />
                                </TableRow>
                            </TableBody>
                        </Table>
                    </DataTableWrapper>
                </div>
            </div>
        </>
    );
}
