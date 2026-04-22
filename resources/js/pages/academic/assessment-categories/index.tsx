import { Head, Link } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import PageHeader from '@/components/page-header';
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

export default function Index({ categories, total_weight }: Props) {
    return (
        <>
            <Head title="Kategori Penilaian" />
            <div className="space-y-6">
                <PageHeader
                    title="Kategori Penilaian"
                    description={`Total bobot: ${total_weight}%`}
                >
                    <Button asChild>
                        <Link href={AssessmentCategoryController.create.url()}>
                            Tambah Kategori
                        </Link>
                    </Button>
                </PageHeader>

                <Table>
                    <TableHeader>
                        <TableRow>
                            <TableHead>Nama</TableHead>
                            <TableHead>Bobot (%)</TableHead>
                            <TableHead>Jumlah Soal</TableHead>
                            <TableHead />
                        </TableRow>
                    </TableHeader>
                    <TableBody>
                        {categories.map((category) => (
                            <TableRow key={category.id}>
                                <TableCell>{category.name}</TableCell>
                                <TableCell>{category.weight}%</TableCell>
                                <TableCell>{category.assessments_count ?? 0}</TableCell>
                                <TableCell>
                                    <Button variant="outline" size="sm" asChild>
                                        <Link href={AssessmentCategoryController.edit.url({ assessmentCategory: category.id })}>
                                            Edit
                                        </Link>
                                    </Button>
                                </TableCell>
                            </TableRow>
                        ))}
                    </TableBody>
                </Table>
            </div>
        </>
    );
}
