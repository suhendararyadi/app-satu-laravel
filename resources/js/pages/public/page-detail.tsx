import type { Page, School } from '@/types/school';

interface Props {
    school: School;
    page: Page;
}

export default function PublicPageDetail({ page }: Props) {
    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-4xl">
                <h1 className="mb-8 text-3xl font-bold text-gray-900">
                    {page.title}
                </h1>
                <div
                    className="prose prose-gray max-w-none"
                    dangerouslySetInnerHTML={{ __html: page.content }}
                />
            </div>
        </div>
    );
}
