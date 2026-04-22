import { Head, Link, useForm, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
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

interface ClassroomOption {
    id: number;
    name: string;
}

interface StudentData {
    id: number;
    name: string;
    email: string;
}

interface EnrollmentData {
    classroom_id: number;
    student_number: string | null;
}

interface Props {
    student: StudentData;
    enrollment: EnrollmentData | null;
    classrooms: ClassroomOption[];
}

export default function Edit({ student, enrollment, classrooms }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        name: student.name,
        email: student.email,
        student_number: enrollment?.student_number ?? '',
        classroom_id: enrollment ? String(enrollment.classroom_id) : '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            StudentController.update.url({
                current_team: teamSlug,
                user: student.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Siswa" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Siswa</h1>
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
                            <Label htmlFor="email">Email</Label>
                            <Input
                                id="email"
                                type="email"
                                value={form.data.email}
                                onChange={(e) =>
                                    form.setData('email', e.target.value)
                                }
                            />
                            <InputError message={form.errors.email} />
                        </div>
                        <div>
                            <Label htmlFor="student_number">
                                NIS (Nomor Induk Siswa)
                            </Label>
                            <Input
                                id="student_number"
                                value={form.data.student_number}
                                onChange={(e) =>
                                    form.setData(
                                        'student_number',
                                        e.target.value,
                                    )
                                }
                                placeholder="Opsional"
                            />
                            <InputError message={form.errors.student_number} />
                        </div>
                        <div>
                            <Label htmlFor="classroom_id">Kelas</Label>
                            <Select
                                value={form.data.classroom_id || 'none'}
                                onValueChange={(v) =>
                                    form.setData(
                                        'classroom_id',
                                        v === 'none' ? '' : v,
                                    )
                                }
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Tidak ada kelas" />
                                </SelectTrigger>
                                <SelectContent>
                                    <SelectItem value="none">
                                        Tidak ada kelas
                                    </SelectItem>
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
                            <InputError message={form.errors.classroom_id} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Perbarui
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={StudentController.index.url(teamSlug)}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
