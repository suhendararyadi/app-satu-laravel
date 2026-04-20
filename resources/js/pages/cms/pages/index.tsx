import { Head, Link, router, usePage } from '@inertiajs/react';
import PageController from '@/actions/App/Http/Controllers/CMS/PageController';
import Heading from '@/components/heading';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import type { Page } from '@/types/school';

interface Props {
    pages: Page[];
}

export default function CmsPagesIndex({ pages }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    function handleDelete(page: Page) {
        if (!window.confirm(`Hapus halaman "${page.title}"?`)) {
            return;
        }

        router.delete(PageController.destroy.url({ current_team: teamSlug, page: page.id }), {
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Halaman CMS" />

            <div className="px-4 py-6">
                <div className="flex items-center justify-between">
                    <Heading title="Halaman" description="Kelola halaman statis website sekolah" />
                    <Button asChild>
                        <Link href={PageController.create.url(teamSlug)}>Tambah Halaman</Link>
                    </Button>
                </div>

                <div className="mt-6 overflow-hidden rounded-md border">
                    <table className="w-full text-sm">
                        <thead className="bg-muted text-muted-foreground">
                            <tr>
                                <th className="px-4 py-3 text-left font-medium">Judul</th>
                                <th className="px-4 py-3 text-left font-medium">Slug</th>
                                <th className="px-4 py-3 text-left font-medium">Status</th>
                                <th className="px-4 py-3 text-left font-medium">Urutan</th>
                                <th className="px-4 py-3 text-left font-medium">Aksi</th>
                            </tr>
                        </thead>
                        <tbody className="divide-y">
                            {pages.length === 0 && (
                                <tr>
                                    <td colSpan={5} className="text-muted-foreground px-4 py-6 text-center">
                                        Belum ada halaman.
                                    </td>
                                </tr>
                            )}
                            {pages.map((page) => (
                                <tr key={page.id} className="hover:bg-muted/50">
                                    <td className="px-4 py-3 font-medium">{page.title}</td>
                                    <td className="text-muted-foreground px-4 py-3">{page.slug}</td>
                                    <td className="px-4 py-3">
                                        {page.is_published ? (
                                            <Badge variant="default">Terbit</Badge>
                                        ) : (
                                            <Badge variant="secondary">Draft</Badge>
                                        )}
                                    </td>
                                    <td className="text-muted-foreground px-4 py-3">{page.sort_order}</td>
                                    <td className="px-4 py-3">
                                        <div className="flex items-center gap-2">
                                            <Button asChild size="sm" variant="outline">
                                                <Link
                                                    href={PageController.edit.url({
                                                        current_team: teamSlug,
                                                        page: page.id,
                                                    })}
                                                >
                                                    Edit
                                                </Link>
                                            </Button>
                                            <Button
                                                size="sm"
                                                variant="destructive"
                                                onClick={() => handleDelete(page)}
                                            >
                                                Hapus
                                            </Button>
                                        </div>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>
                </div>
            </div>
        </>
    );
}
