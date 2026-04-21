import type { ReactNode } from 'react';

interface Props {
    title: string;
    description?: string;
    action?: ReactNode;
}

export default function PageHeader({ title, description, action }: Props) {
    return (
        <div className="flex items-start justify-between">
            <div className="space-y-0.5">
                <h1 className="text-2xl font-bold">{title}</h1>
                {description && (
                    <p className="text-sm text-muted-foreground">{description}</p>
                )}
            </div>
            {action && <div className="flex items-center gap-2">{action}</div>}
        </div>
    );
}
