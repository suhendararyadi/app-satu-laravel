import { Head, useForm } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function Create() {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        weight: '',
    });

    function handleSubmit(e: React.FormEvent) {
        e.preventDefault();
        post(AssessmentCategoryController.store.url());
    }

    return (
        <>
            <Head title="Tambah Kategori Penilaian" />
            <div className="space-y-6">
                <PageHeader
                    title="Tambah Kategori Penilaian"
                    description="Buat kategori penilaian baru."
                />

                <form onSubmit={handleSubmit} className="max-w-lg space-y-4">
                    <div className="space-y-1">
                        <Label htmlFor="name">Nama</Label>
                        <Input
                            id="name"
                            value={data.name}
                            onChange={(e) => setData('name', e.target.value)}
                        />
                        {errors.name && <p className="text-sm text-red-500">{errors.name}</p>}
                    </div>

                    <div className="space-y-1">
                        <Label htmlFor="weight">Bobot (%)</Label>
                        <Input
                            id="weight"
                            type="number"
                            value={data.weight}
                            onChange={(e) => setData('weight', e.target.value)}
                        />
                        {errors.weight && <p className="text-sm text-red-500">{errors.weight}</p>}
                    </div>

                    <Button type="submit" disabled={processing}>
                        Simpan
                    </Button>
                </form>
            </div>
        </>
    );
}
