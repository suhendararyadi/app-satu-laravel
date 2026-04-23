import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function AssessmentCategoryCreate() {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({ name: '', weight: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AssessmentCategoryController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="max-w-md space-y-6">
                    <h1 className="text-2xl font-bold">
                        Tambah Kategori Penilaian
                    </h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                                placeholder="UTS"
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="weight">Bobot (%)</Label>
                            <Input
                                id="weight"
                                type="number"
                                min={0}
                                max={100}
                                step={0.01}
                                value={form.data.weight}
                                onChange={(e) =>
                                    form.setData('weight', e.target.value)
                                }
                                placeholder="25"
                            />
                            <InputError message={form.errors.weight} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={AssessmentCategoryController.index.url(
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
