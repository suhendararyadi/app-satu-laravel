import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
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
import type {
    AssessmentCategory,
    Classroom,
    Semester,
    Subject,
} from '@/types/academic';

interface Props {
    classrooms: Classroom[];
    subjects: Subject[];
    semesters: Semester[];
    categories: AssessmentCategory[];
}

export default function AssessmentCreate({
    classrooms,
    subjects,
    semesters,
    categories,
}: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        classroom_id: '',
        subject_id: '',
        semester_id: '',
        assessment_category_id: '',
        title: '',
        max_score: '100',
        date: new Date().toISOString().split('T')[0],
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AssessmentController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Assessment" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Assessment</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="classroom_id">Kelas</Label>
                                <Select
                                    value={form.data.classroom_id}
                                    onValueChange={(v) =>
                                        form.setData('classroom_id', v)
                                    }
                                >
                                    <SelectTrigger id="classroom_id">
                                        <SelectValue placeholder="Pilih kelas" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {classrooms.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={String(c.id)}
                                            >
                                                {c.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.classroom_id}
                                />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">
                                    Mata Pelajaran
                                </Label>
                                <Select
                                    value={form.data.subject_id}
                                    onValueChange={(v) =>
                                        form.setData('subject_id', v)
                                    }
                                >
                                    <SelectTrigger id="subject_id">
                                        <SelectValue placeholder="Pilih mapel" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {subjects.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.subject_id} />
                            </div>
                            <div>
                                <Label htmlFor="semester_id">Semester</Label>
                                <Select
                                    value={form.data.semester_id}
                                    onValueChange={(v) =>
                                        form.setData('semester_id', v)
                                    }
                                >
                                    <SelectTrigger id="semester_id">
                                        <SelectValue placeholder="Pilih semester" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {semesters.map((s) => (
                                            <SelectItem
                                                key={s.id}
                                                value={String(s.id)}
                                            >
                                                {s.name}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.semester_id} />
                            </div>
                            <div>
                                <Label htmlFor="assessment_category_id">
                                    Kategori
                                </Label>
                                <Select
                                    value={form.data.assessment_category_id}
                                    onValueChange={(v) =>
                                        form.setData(
                                            'assessment_category_id',
                                            v,
                                        )
                                    }
                                >
                                    <SelectTrigger id="assessment_category_id">
                                        <SelectValue placeholder="Pilih kategori" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {categories.map((c) => (
                                            <SelectItem
                                                key={c.id}
                                                value={String(c.id)}
                                            >
                                                {c.name} ({c.weight}%)
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError
                                    message={form.errors.assessment_category_id}
                                />
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input
                                    id="title"
                                    value={form.data.title}
                                    onChange={(e) =>
                                        form.setData('title', e.target.value)
                                    }
                                    placeholder="UTS Matematika Kelas X"
                                />
                                <InputError message={form.errors.title} />
                            </div>
                            <div>
                                <Label htmlFor="max_score">
                                    Nilai Maksimal
                                </Label>
                                <Input
                                    id="max_score"
                                    type="number"
                                    min={0}
                                    step={0.01}
                                    value={form.data.max_score}
                                    onChange={(e) =>
                                        form.setData(
                                            'max_score',
                                            e.target.value,
                                        )
                                    }
                                />
                                <InputError message={form.errors.max_score} />
                            </div>
                            <div>
                                <Label htmlFor="date">Tanggal</Label>
                                <Input
                                    id="date"
                                    type="date"
                                    value={form.data.date}
                                    onChange={(e) =>
                                        form.setData('date', e.target.value)
                                    }
                                />
                                <InputError message={form.errors.date} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={AssessmentController.index.url(
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
