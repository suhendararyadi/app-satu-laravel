import type { School } from '@/types/school';

interface Props {
    school: School;
}

export default function PublicContact({ school }: Props) {
    return (
        <div className="px-6 py-16">
            <div className="mx-auto max-w-4xl">
                <h1 className="mb-8 text-3xl font-bold text-gray-900">
                    Kontak
                </h1>

                <div className="mb-8 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    {school.address && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <h2 className="mb-1 text-sm font-semibold tracking-wide text-gray-400 uppercase">
                                Alamat
                            </h2>
                            <p className="text-gray-700">{school.address}</p>
                            {school.city && (
                                <p className="mt-1 text-sm text-gray-500">
                                    {school.city}
                                    {school.province
                                        ? `, ${school.province}`
                                        : ''}
                                    {school.postal_code
                                        ? ` ${school.postal_code}`
                                        : ''}
                                </p>
                            )}
                        </div>
                    )}

                    {school.phone && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <h2 className="mb-1 text-sm font-semibold tracking-wide text-gray-400 uppercase">
                                Telepon
                            </h2>
                            <a
                                href={`tel:${school.phone}`}
                                className="text-blue-600 hover:underline"
                            >
                                {school.phone}
                            </a>
                        </div>
                    )}

                    {school.email && (
                        <div className="rounded-lg border border-gray-200 bg-white p-5 shadow-sm">
                            <h2 className="mb-1 text-sm font-semibold tracking-wide text-gray-400 uppercase">
                                Email
                            </h2>
                            <a
                                href={`mailto:${school.email}`}
                                className="text-blue-600 hover:underline"
                            >
                                {school.email}
                            </a>
                        </div>
                    )}
                </div>

                {/* Map placeholder */}
                <div className="flex h-64 items-center justify-center rounded-lg bg-gray-100 text-gray-500">
                    Peta belum tersedia
                </div>
            </div>
        </div>
    );
}
