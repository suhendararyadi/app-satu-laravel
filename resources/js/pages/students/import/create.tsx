import { Head, Link, useForm, usePage } from '@inertiajs/react';

import StudentController from '@/actions/App/Http/Controllers/Students/StudentController';
import StudentImportController from '@/actions/App/Http/Controllers/Students/StudentImportController';
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

interface ImportResult {
    imported: number;
    skipped: number;
    errors: string[];
}

interface Props {
    classrooms: ClassroomOption[];
    import_result?: ImportResult | null;
}

export default function StudentImportCreate({ classrooms, import_result }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm<{ file: File | null; classroom_id: string }>({
        file: null,
        classroom_id: '',
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(StudentImportController.store.url(teamSlug), {
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Import Siswa" />
            <div className="px-4 py-6">
                <div className="max-w-xl space-y-6">
                    <div className="flex items-center justify-between">
                        <h1 className="text-2xl font-bold">Import Siswa</h1>
                        <Button variant="outline" asChild>
                            <Link href={StudentController.index.url(teamSlug)}>Kembali</Link>
                        </Button>
                    </div>

                    {import_result && (
                        <div className="space-y-2 rounded-md border p-4 text-sm">
                            <p className="font-semibold">Hasil Import</p>
                            <p className="text-green-600">
                                ✓ {import_result.imported} siswa berhasil diimport
                            </p>
                            <p className="text-yellow-600">
                                ⚠ {import_result.skipped} baris dilewati (email sudah ada)
                            </p>
                            {import_result.errors.length > 0 && (
                                <ul className="list-inside list-disc text-red-600">
                                    {import_result.errors.map((err, i) => (
                                        <li key={i}>{err}</li>
                                    ))}
                                </ul>
                            )}
                        </div>
                    )}

                    <form onSubmit={submit} className="space-y-5">
                        <div className="space-y-1.5">
                            <Label htmlFor="classroom_id">Kelas (opsional)</Label>
                            <Select
                                value={form.data.classroom_id}
                                onValueChange={(v) => form.setData('classroom_id', v)}
                            >
                                <SelectTrigger id="classroom_id">
                                    <SelectValue placeholder="Pilih kelas (opsional)" />
                                </SelectTrigger>
                                <SelectContent>
                                    {classrooms.map((c) => (
                                        <SelectItem key={c.id} value={String(c.id)}>
                                            {c.name}
                                        </SelectItem>
                                    ))}
                                </SelectContent>
                            </Select>
                        </div>

                        <div className="space-y-1.5">
                            <Label htmlFor="file">File Excel (.xlsx, .xls)</Label>
                            <Input
                                id="file"
                                type="file"
                                accept=".xlsx,.xls,.csv"
                                onChange={(e) => form.setData('file', e.target.files?.[0] ?? null)}
                            />
                            {form.errors.file && (
                                <p className="text-destructive text-sm">{form.errors.file}</p>
                            )}
                        </div>

                        <div className="flex items-center gap-3">
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? 'Mengimport...' : 'Import'}
                            </Button>
                            <Button variant="outline" asChild>
                                <a href={StudentImportController.template.url(teamSlug)}>
                                    Download Template
                                </a>
                            </Button>
                        </div>
                    </form>

                    <div className="bg-muted space-y-1 rounded-md p-4 text-sm">
                        <p className="font-medium">Format file Excel:</p>
                        <ul className="text-muted-foreground list-inside list-disc">
                            <li>
                                Kolom wajib: <strong>Nama</strong>, <strong>Email</strong>,{' '}
                                <strong>NIS</strong>
                            </li>
                            <li>Baris pertama adalah header</li>
                            <li>Email yang sudah terdaftar akan dilewati</li>
                            <li>Password sementara akan dikirim ke email siswa</li>
                        </ul>
                    </div>
                </div>
            </div>
        </>
    );
}
