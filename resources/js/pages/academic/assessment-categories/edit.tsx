import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AssessmentCategory } from '@/types/academic';

interface Props {
    category: AssessmentCategory;
}

export default function AssessmentCategoryEdit({ category }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({ name: category.name, weight: category.weight });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            AssessmentCategoryController.update.url({
                current_team: teamSlug,
                assessmentCategory: category.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="max-w-md space-y-6">
                    <h1 className="text-2xl font-bold">
                        Edit Kategori Penilaian
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
                            />
                            <InputError message={form.errors.weight} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Perbarui
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
