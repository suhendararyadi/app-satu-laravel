import { Head, Link, useForm, usePage } from '@inertiajs/react';
import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function CmsPagesCreate() {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        title: '',
        slug: '',
        content: '',
        is_published: false,
        sort_order: 0,
        meta_description: '',
    });

    function handleTitleChange(value: string) {
        form.setData('title', value);
        form.setData(
            'slug',
            value
                .toLowerCase()
                .replace(/[^a-z0-9]+/g, '-')
                .replace(/(^-|-$)/g, ''),
        );
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(PageController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Halaman" />

            <div className="px-4 py-6">
                <Heading title="Tambah Halaman" description="Buat halaman statis baru untuk website sekolah" />

                <form onSubmit={submit} className="mt-6 max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) => handleTitleChange(e.target.value)}
                            placeholder="Judul halaman"
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(e) => form.setData('slug', e.target.value)}
                            placeholder="slug-halaman"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="content">Konten</Label>
                        <textarea
                            id="content"
                            value={form.data.content}
                            onChange={(e) => form.setData('content', e.target.value)}
                            placeholder="Konten halaman"
                            rows={8}
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.content} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="sort_order">Urutan Tampil</Label>
                        <Input
                            id="sort_order"
                            type="number"
                            value={form.data.sort_order}
                            onChange={(e) => form.setData('sort_order', Number(e.target.value))}
                            placeholder="0"
                        />
                        <InputError message={form.errors.sort_order} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="meta_description">Meta Description</Label>
                        <textarea
                            id="meta_description"
                            value={form.data.meta_description}
                            onChange={(e) => form.setData('meta_description', e.target.value)}
                            placeholder="Deskripsi singkat untuk SEO (opsional)"
                            rows={3}
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.meta_description} />
                    </div>

                    <div className="flex items-center gap-3">
                        <Checkbox
                            id="is_published"
                            checked={form.data.is_published}
                            onCheckedChange={(checked) => form.setData('is_published', checked === true)}
                        />
                        <Label htmlFor="is_published">Diterbitkan</Label>
                        <InputError message={form.errors.is_published} />
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                        <Button asChild variant="outline">
                            <Link href={PageController.index.url(teamSlug)}>Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
