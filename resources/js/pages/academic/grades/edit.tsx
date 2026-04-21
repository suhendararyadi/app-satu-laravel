import { Head, Link, useForm, usePage } from '@inertiajs/react';
import GradeController from '@/actions/App/Http/Controllers/Academic/GradeController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Grade } from '@/types/academic';

interface Props {
    grade: Grade;
}

export default function Edit({ grade }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: grade.name,
        order: String(grade.order),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            GradeController.update.url({
                current_team: teamSlug,
                grade: grade.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Tingkat Kelas" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Tingkat Kelas</h1>
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
                            <Label htmlFor="order">Urutan</Label>
                            <Input
                                id="order"
                                type="number"
                                value={form.data.order}
                                onChange={(e) =>
                                    form.setData('order', e.target.value)
                                }
                            />
                            <InputError message={form.errors.order} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={GradeController.index.url(teamSlug)}
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
