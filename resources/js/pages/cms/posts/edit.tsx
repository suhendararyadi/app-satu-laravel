import { Head, Link, useForm, usePage } from '@inertiajs/react';
import * as PostController from '@/actions/App/Http/Controllers/CMS/PostController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { Post } from '@/types/school';

interface Props {
    post: Post;
}

export default function CmsPostsEdit({ post }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        title: post.title,
        slug: post.slug,
        excerpt: post.excerpt ?? '',
        content: post.content,
        featured_image: null as File | null,
        is_published: post.is_published,
        published_at: post.published_at ? post.published_at.slice(0, 16) : '',
        meta_description: post.meta_description ?? '',
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
        form.patch(PostController.update.url({ current_team: teamSlug, post: post.id }), {
            forceFormData: true,
        });
    }

    return (
        <>
            <Head title="Edit Artikel" />

            <div className="px-4 py-6">
                <Heading title="Edit Artikel" description="Ubah konten artikel website sekolah" />

                <form onSubmit={submit} className="mt-6 max-w-2xl space-y-6">
                    <div className="grid gap-2">
                        <Label htmlFor="title">Judul</Label>
                        <Input
                            id="title"
                            value={form.data.title}
                            onChange={(e) => handleTitleChange(e.target.value)}
                            placeholder="Judul artikel"
                        />
                        <InputError message={form.errors.title} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="slug">Slug</Label>
                        <Input
                            id="slug"
                            value={form.data.slug}
                            onChange={(e) => form.setData('slug', e.target.value)}
                            placeholder="slug-artikel"
                        />
                        <InputError message={form.errors.slug} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="excerpt">Kutipan (Excerpt)</Label>
                        <textarea
                            id="excerpt"
                            value={form.data.excerpt}
                            onChange={(e) => form.setData('excerpt', e.target.value)}
                            placeholder="Ringkasan singkat artikel (opsional)"
                            rows={3}
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.excerpt} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="content">Konten</Label>
                        <textarea
                            id="content"
                            value={form.data.content}
                            onChange={(e) => form.setData('content', e.target.value)}
                            placeholder="Isi artikel"
                            rows={8}
                            className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.content} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="featured_image">Featured Image</Label>
                        {post.featured_image_path && (
                            <img
                                src={'/storage/' + post.featured_image_path}
                                alt="Featured image saat ini"
                                className="mb-2 h-40 w-auto rounded-md object-cover"
                            />
                        )}
                        <input
                            id="featured_image"
                            type="file"
                            accept="image/*"
                            onChange={(e) => form.setData('featured_image', e.target.files?.[0] ?? null)}
                            className="border-input bg-background ring-offset-background focus-visible:ring-ring flex w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                        />
                        <InputError message={form.errors.featured_image} />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor="published_at">Tanggal Terbit</Label>
                        <Input
                            id="published_at"
                            type="datetime-local"
                            value={form.data.published_at}
                            onChange={(e) => form.setData('published_at', e.target.value)}
                        />
                        <InputError message={form.errors.published_at} />
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
                            <Link href={PostController.index.url(teamSlug)}>Batal</Link>
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}
