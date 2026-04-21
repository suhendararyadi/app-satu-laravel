import { Head, Link, useForm, usePage } from '@inertiajs/react';
import ClassroomController from '@/actions/App/Http/Controllers/Academic/ClassroomController';
import InputError from '@/components/input-error';
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
import type { AcademicYear, Grade } from '@/types/academic';

interface Props {
    academicYears: AcademicYear[];
    grades: Grade[];
}

export default function Create({ academicYears, grades }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: '',
        academic_year_id: '',
        grade_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(ClassroomController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Kelas" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Kelas</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama Kelas</Label>
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
                            <Label htmlFor="academic_year_id">
                                Tahun Ajaran
                            </Label>
                            <Select
                                value={form.data.academic_year_id}
                                onValueChange={(v) =>
                                    form.setData('academic_year_id', v)
                                }
                            >
                                <SelectTrigger id="academic_year_id">
                                    <SelectValue placeholder="Pilih tahun ajaran" />
                                </SelectTrigger>
                                <SelectContent>
                                    {academicYears.map((year) => (
                                        <SelectItem
                                            key={year.id}
                                            value={String(year.id)}
                                        >
                                            {year.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError
                                message={form.errors.academic_year_id}
                            />
                        </div>
                        <div>
                            <Label htmlFor="grade_id">Tingkat</Label>
                            <Select
                                value={form.data.grade_id}
                                onValueChange={(v) =>
                                    form.setData('grade_id', v)
                                }
                            >
                                <SelectTrigger id="grade_id">
                                    <SelectValue placeholder="Pilih tingkat" />
                                </SelectTrigger>
                                <SelectContent>
                                    {grades.map((grade) => (
                                        <SelectItem
                                            key={grade.id}
                                            value={String(grade.id)}
                                        >
                                            {grade.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                            <InputError message={form.errors.grade_id} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={ClassroomController.index.url(
                                        teamSlug,
                                    )}
                                >
                                    Batal
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
