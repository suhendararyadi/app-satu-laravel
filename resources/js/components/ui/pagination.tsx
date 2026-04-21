import { router } from '@inertiajs/react';
import { ChevronLeftIcon, ChevronRightIcon, MoreHorizontalIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';
import { cn } from '@/lib/utils';

interface PaginationMeta {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
    from: number | null;
    to: number | null;
}

interface Props {
    meta: PaginationMeta;
    /** Extra query params to preserve saat pindah halaman (misal: search, filter) */
    preserveParams?: Record<string, string | number | null | undefined>;
    className?: string;
}

function getPages(current: number, last: number): (number | '...')[] {
    if (last <= 7) {
        return Array.from({ length: last }, (_, i) => i + 1);
    }
    const pages: (number | '...')[] = [1];
    if (current > 3) pages.push('...');
    for (let i = Math.max(2, current - 1); i <= Math.min(last - 1, current + 1); i++) {
        pages.push(i);
    }
    if (current < last - 2) pages.push('...');
    pages.push(last);
    return pages;
}

export function Pagination({ meta, preserveParams = {}, className }: Props) {
    if (meta.last_page <= 1) return null;

    function goTo(page: number) {
        router.get(
            window.location.pathname,
            { ...preserveParams, page },
            { preserveScroll: true, preserveState: true },
        );
    }

    const pages = getPages(meta.current_page, meta.last_page);

    return (
        <div className={cn('flex items-center justify-between', className)}>
            <p className="text-sm text-muted-foreground">
                {meta.from !== null && meta.to !== null
                    ? `Menampilkan ${meta.from}–${meta.to} dari ${meta.total} data`
                    : `Total ${meta.total} data`}
            </p>
            <div className="flex items-center gap-1">
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => goTo(meta.current_page - 1)}
                    disabled={meta.current_page === 1}
                >
                    <ChevronLeftIcon className="size-4" />
                </Button>
                {pages.map((p, i) =>
                    p === '...' ? (
                        <span key={`ellipsis-${i}`} className="px-1">
                            <MoreHorizontalIcon className="size-4 text-muted-foreground" />
                        </span>
                    ) : (
                        <Button
                            key={p}
                            variant={p === meta.current_page ? 'default' : 'outline'}
                            size="sm"
                            onClick={() => goTo(p)}
                        >
                            {p}
                        </Button>
                    ),
                )}
                <Button
                    variant="outline"
                    size="sm"
                    onClick={() => goTo(meta.current_page + 1)}
                    disabled={meta.current_page === meta.last_page}
                >
                    <ChevronRightIcon className="size-4" />
                </Button>
            </div>
        </div>
    );
}
