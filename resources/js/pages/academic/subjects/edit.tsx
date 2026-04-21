import { Head, Link, useForm, usePage } from '@inertiajs/react';
import SubjectController from '@/actions/App/Http/Controllers/Academic/SubjectController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Subject } from '@/types/academic';

interface Props {
    subject: Subject;
}

export default function Edit({ subject }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: subject.name,
        code: subject.code ?? '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            SubjectController.update.url({
                current_team: teamSlug,
                subject: subject.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Mata Pelajaran" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Mata Pelajaran</h1>
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
                            <Label htmlFor="code">Kode (opsional)</Label>
                            <Input
                                id="code"
                                value={form.data.code}
                                onChange={(e) =>
                                    form.setData('code', e.target.value)
                                }
                            />
                            <InputError message={form.errors.code} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={SubjectController.index.url(teamSlug)}
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
