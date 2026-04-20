import { Link } from '@inertiajs/react';
import type { PropsWithChildren } from 'react';
import type { Page, School } from '@/types/school';

interface PublicLayoutProps {
    school: School;
    pages?: Page[];
}

export default function PublicLayout({
    school,
    pages = [],
    children,
}: PropsWithChildren<PublicLayoutProps>) {
    return (
        <div className="flex min-h-screen flex-col">
            <nav className="border-b border-gray-200 bg-white px-6 py-4">
                <div className="mx-auto flex max-w-6xl items-center justify-between">
                    <Link
                        href={`/schools/${school.slug}`}
                        className="text-xl font-bold text-gray-900"
                    >
                        {school.name}
                    </Link>
                    <div className="flex items-center gap-6">
                        <Link
                            href={`/schools/${school.slug}`}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            Home
                        </Link>
                        {pages.map((page) => (
                            <Link
                                key={page.id}
                                href={`/schools/${school.slug}/pages/${page.slug}`}
                                className="text-gray-600 hover:text-gray-900"
                            >
                                {page.title}
                            </Link>
                        ))}
                        <Link
                            href={`/schools/${school.slug}/news`}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            Berita
                        </Link>
                        <Link
                            href={`/schools/${school.slug}/gallery`}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            Galeri
                        </Link>
                        <Link
                            href={`/schools/${school.slug}/contact`}
                            className="text-gray-600 hover:text-gray-900"
                        >
                            Kontak
                        </Link>
                    </div>
                </div>
            </nav>
            <main className="flex-1">{children}</main>
            <footer className="border-t border-gray-200 bg-gray-50 px-6 py-8 text-center text-sm text-gray-500">
                &copy; {new Date().getFullYear()} {school.name}. All rights
                reserved.
            </footer>
        </div>
    );
}
