// resources/js/components/empty-state.tsx
import { Link } from '@inertiajs/react';
import type { LucideIcon } from 'lucide-react';
import { InboxIcon } from 'lucide-react';

import { Button } from '@/components/ui/button';

interface Action {
    label: string;
    href: string;
}

interface Props {
    icon?: LucideIcon;
    title: string;
    description?: string;
    action?: Action;
}

export default function EmptyState({
    icon: Icon = InboxIcon,
    title,
    description,
    action,
}: Props) {
    return (
        <div className="flex flex-col items-center justify-center py-16 text-center">
            <div className="mb-4 rounded-full bg-muted p-4">
                <Icon className="size-8 text-muted-foreground" />
            </div>
            <h3 className="text-base font-semibold">{title}</h3>
            {description && (
                <p className="mt-1 max-w-sm text-sm text-muted-foreground">
                    {description}
                </p>
            )}
            {action && (
                <Button asChild className="mt-4">
                    <Link href={action.href}>{action.label}</Link>
                </Button>
            )}
        </div>
    );
}
