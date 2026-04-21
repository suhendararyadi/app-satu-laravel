// resources/js/components/data-table-wrapper.tsx
import type { ReactNode } from 'react';

import EmptyState from '@/components/empty-state';
import { Spinner } from '@/components/ui/spinner';
import type { LucideIcon } from 'lucide-react';

interface EmptyStateConfig {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: { label: string; href: string };
}

interface Props {
    loading: boolean;
    isEmpty: boolean;
    emptyState: EmptyStateConfig;
    children: ReactNode;
}

export default function DataTableWrapper({
    loading,
    isEmpty,
    emptyState,
    children,
}: Props) {
    if (isEmpty && !loading) {
        return <EmptyState {...emptyState} />;
    }

    return (
        <div className="relative">
            {loading && (
                <div className="absolute inset-0 z-10 flex items-center justify-center rounded-md bg-background/60">
                    <div className="flex flex-col items-center gap-2">
                        <Spinner className="size-6" />
                        <span className="text-xs text-muted-foreground">Memuat...</span>
                    </div>
                </div>
            )}
            <div className={loading ? 'pointer-events-none opacity-50' : undefined}>
                {children}
            </div>
        </div>
    );
}
