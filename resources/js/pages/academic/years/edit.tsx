import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import ConfirmDeleteDialog from '@/components/confirm-delete-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AcademicYear } from '@/types/academic';

interface Props {
    year: AcademicYear;
}

export default function Edit({ year }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';
    const [confirmOpen, setConfirmOpen] = useState(false);
    const [deleteSemesterId, setDeleteSemesterId] = useState<number | null>(
        null,
    );

    const form = useForm({
        name: year.name,
        start_year: String(year.start_year),
        end_year: String(year.end_year),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            AcademicYearController.update.url({
                current_team: teamSlug,
                year: year.id,
            }),
        );
    }

    function confirmDeleteSemester() {
        if (!deleteSemesterId) {
            return;
        }

        router.delete(
            AcademicYearController.destroySemester.url({
                current_team: teamSlug,
                year: year.id,
                semester: deleteSemesterId,
            }),
            {
                preserveScroll: true,
                onFinish: () => {
                    setConfirmOpen(false);
                    setDeleteSemesterId(null);
                },
            },
        );
    }

    return (
        <>
            <Head title="Edit Tahun Ajaran" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-8">
                    <h1 className="text-2xl font-bold">Edit Tahun Ajaran</h1>

                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="start_year">Tahun Mulai</Label>
                            <Input
                                id="start_year"
                                type="number"
                                value={form.data.start_year}
                                onChange={(e) =>
                                    form.setData('start_year', e.target.value)
                                }
                            />
                            <InputError message={form.errors.start_year} />
                        </div>
                        <div>
                            <Label htmlFor="end_year">Tahun Selesai</Label>
                            <Input
                                id="end_year"
                                type="number"
                                value={form.data.end_year}
                                onChange={(e) =>
                                    form.setData('end_year', e.target.value)
                                }
                            />
                            <InputError message={form.errors.end_year} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={AcademicYearController.index.url(
                                        teamSlug,
                                    )}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>

                    <div className="space-y-3">
                        <div className="flex items-center justify-between">
                            <h2 className="text-lg font-semibold">Semester</h2>
                            <Button size="sm" asChild>
                                <Link
                                    href={AcademicYearController.createSemester.url(
                                        {
                                            current_team: teamSlug,
                                            year: year.id,
                                        },
                                    )}
                                >
                                    Tambah Semester
                                </Link>
                            </Button>
                        </div>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Urutan</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {(year.semesters ?? []).map((semester) => (
                                    <TableRow key={semester.id}>
                                        <TableCell>{semester.name}</TableCell>
                                        <TableCell>{semester.order}</TableCell>
                                        <TableCell className="space-x-2">
                                            <Button
                                                size="sm"
                                                variant="outline"
                                                asChild
                                            >
                                                <Link
                                                    href={AcademicYearController.editSemester.url(
                                                        {
                                                            current_team:
                                                                teamSlug,
                                                            year: year.id,
                                                            semester:
                                                                semester.id,
                                                        },
                                                    )}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => {
                                                    setDeleteSemesterId(
                                                        semester.id,
                                                    );
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
                    </div>

                    <ConfirmDeleteDialog
                        open={confirmOpen}
                        onOpenChange={setConfirmOpen}
                        onConfirm={confirmDeleteSemester}
                    />
                </div>
            </div>
        </>
    );
}
