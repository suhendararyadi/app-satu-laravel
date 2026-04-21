import { Head, Link, useForm, usePage } from '@inertiajs/react';
import AcademicYearController from '@/actions/App/Http/Controllers/Academic/AcademicYearController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
export default function Create() {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({ name: '', start_year: '', end_year: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AcademicYearController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Tahun Ajaran" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Tahun Ajaran</h1>
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
