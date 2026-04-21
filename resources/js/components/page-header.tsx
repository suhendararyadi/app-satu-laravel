import type { ReactNode } from 'react';

export default function PageHeader({
    title,
    description,
    action,
}: {
    title: string;
    description?: string;
    action?: ReactNode;
}) {
    return (
        <header className="flex items-start justify-between">
            <div className="space-y-0.5">
                <h1 className="text-2xl font-bold tracking-tight">{title}</h1>
                {description && (
                    <p className="text-sm text-muted-foreground">
                        {description}
                    </p>
                )}
            </div>
            {action && <div className="flex items-center gap-2">{action}</div>}
        </header>
    );
}
