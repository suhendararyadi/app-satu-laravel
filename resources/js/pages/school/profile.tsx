import { Head, useForm } from '@inertiajs/react';
import SchoolProfileController from '@/actions/App/Http/Controllers/School/SchoolProfileController';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Select, SelectContent, SelectItem, SelectTrigger, SelectValue } from '@/components/ui/select';
import type { School } from '@/types/school';

interface Props {
    team: School;
}

export default function SchoolProfile({ team }: Props) {
    const form = useForm({
        npsn: team.npsn ?? '',
        school_type: team.school_type ?? '',
        address: team.address ?? '',
        city: team.city ?? '',
        province: team.province ?? '',
        postal_code: team.postal_code ?? '',
        phone: team.phone ?? '',
        email: team.email ?? '',
        accreditation: team.accreditation ?? '',
        principal_name: team.principal_name ?? '',
        founded_year: team.founded_year?.toString() ?? '',
        vision: team.vision ?? '',
        mission: team.mission ?? '',
        description: team.description ?? '',
        logo: null as File | null,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(SchoolProfileController.update.url(team.slug), {
            forceFormData: true,
            preserveScroll: true,
        });
    }

    return (
        <>
            <Head title="Profil Sekolah" />

            <div className="px-4 py-6">
                <Heading
                    title="Profil Sekolah"
                    description="Kelola informasi dan profil sekolah"
                />

                <form onSubmit={submit} className="mt-6 space-y-8">
                    {/* Grid Fields */}
                    <div className="grid grid-cols-1 gap-6 md:grid-cols-2">
                        {/* Left Column */}
                        <div className="space-y-4">
                            {/* Nama Sekolah - readonly */}
                            <div className="grid gap-2">
                                <Label htmlFor="name">Nama Sekolah</Label>
                                <Input
                                    id="name"
                                    value={team.name}
                                    readOnly
                                    disabled
                                    className="bg-muted"
                                />
                                <p className="text-muted-foreground text-xs">
                                    Nama sekolah dikelola melalui pengaturan tim.
                                </p>
                            </div>

                            {/* NPSN */}
                            <div className="grid gap-2">
                                <Label htmlFor="npsn">NPSN</Label>
                                <Input
                                    id="npsn"
                                    value={form.data.npsn}
                                    onChange={(e) => form.setData('npsn', e.target.value)}
                                    placeholder="Nomor Pokok Sekolah Nasional"
                                />
                                <InputError message={form.errors.npsn} />
                            </div>

                            {/* Jenis Sekolah */}
                            <div className="grid gap-2">
                                <Label htmlFor="school_type">Jenis Sekolah</Label>
                                <Select
                                    value={form.data.school_type}
                                    onValueChange={(value) => form.setData('school_type', value)}
                                >
                                    <SelectTrigger id="school_type">
                                        <SelectValue placeholder="Pilih Jenis" />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="Sma">SMA</SelectItem>
                                        <SelectItem value="Smk">SMK</SelectItem>
                                        <SelectItem value="Ma">MA</SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={form.errors.school_type} />
                            </div>

                            {/* Akreditasi */}
                            <div className="grid gap-2">
                                <Label htmlFor="accreditation">Akreditasi</Label>
                                <Input
                                    id="accreditation"
                                    value={form.data.accreditation}
                                    onChange={(e) => form.setData('accreditation', e.target.value)}
                                    placeholder="Contoh: A, B, C"
                                />
                                <InputError message={form.errors.accreditation} />
                            </div>

                            {/* Tahun Berdiri */}
                            <div className="grid gap-2">
                                <Label htmlFor="founded_year">Tahun Berdiri</Label>
                                <Input
                                    id="founded_year"
                                    type="number"
                                    value={form.data.founded_year}
                                    onChange={(e) => form.setData('founded_year', e.target.value)}
                                    placeholder="Contoh: 1985"
                                />
                                <InputError message={form.errors.founded_year} />
                            </div>

                            {/* Kepala Sekolah */}
                            <div className="grid gap-2">
                                <Label htmlFor="principal_name">Kepala Sekolah</Label>
                                <Input
                                    id="principal_name"
                                    value={form.data.principal_name}
                                    onChange={(e) => form.setData('principal_name', e.target.value)}
                                    placeholder="Nama kepala sekolah"
                                />
                                <InputError message={form.errors.principal_name} />
                            </div>
                        </div>

                        {/* Right Column */}
                        <div className="space-y-4">
                            {/* Alamat */}
                            <div className="grid gap-2">
                                <Label htmlFor="address">Alamat</Label>
                                <textarea
                                    id="address"
                                    value={form.data.address}
                                    onChange={(e) => form.setData('address', e.target.value)}
                                    placeholder="Alamat lengkap sekolah"
                                    rows={3}
                                    className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                                />
                                <InputError message={form.errors.address} />
                            </div>

                            {/* Kota */}
                            <div className="grid gap-2">
                                <Label htmlFor="city">Kota</Label>
                                <Input
                                    id="city"
                                    value={form.data.city}
                                    onChange={(e) => form.setData('city', e.target.value)}
                                    placeholder="Nama kota"
                                />
                                <InputError message={form.errors.city} />
                            </div>

                            {/* Provinsi */}
                            <div className="grid gap-2">
                                <Label htmlFor="province">Provinsi</Label>
                                <Input
                                    id="province"
                                    value={form.data.province}
                                    onChange={(e) => form.setData('province', e.target.value)}
                                    placeholder="Nama provinsi"
                                />
                                <InputError message={form.errors.province} />
                            </div>

                            {/* Kode Pos */}
                            <div className="grid gap-2">
                                <Label htmlFor="postal_code">Kode Pos</Label>
                                <Input
                                    id="postal_code"
                                    value={form.data.postal_code}
                                    onChange={(e) => form.setData('postal_code', e.target.value)}
                                    placeholder="Kode pos"
                                />
                                <InputError message={form.errors.postal_code} />
                            </div>

                            {/* Telepon */}
                            <div className="grid gap-2">
                                <Label htmlFor="phone">Telepon</Label>
                                <Input
                                    id="phone"
                                    value={form.data.phone}
                                    onChange={(e) => form.setData('phone', e.target.value)}
                                    placeholder="Nomor telepon sekolah"
                                />
                                <InputError message={form.errors.phone} />
                            </div>

                            {/* Email */}
                            <div className="grid gap-2">
                                <Label htmlFor="email">Email</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    value={form.data.email}
                                    onChange={(e) => form.setData('email', e.target.value)}
                                    placeholder="Email sekolah"
                                />
                                <InputError message={form.errors.email} />
                            </div>
                        </div>
                    </div>

                    {/* Full-width Fields */}
                    <div className="space-y-4">
                        {/* Visi */}
                        <div className="grid gap-2">
                            <Label htmlFor="vision">Visi</Label>
                            <textarea
                                id="vision"
                                value={form.data.vision}
                                onChange={(e) => form.setData('vision', e.target.value)}
                                placeholder="Visi sekolah"
                                rows={3}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={form.errors.vision} />
                        </div>

                        {/* Misi */}
                        <div className="grid gap-2">
                            <Label htmlFor="mission">Misi</Label>
                            <textarea
                                id="mission"
                                value={form.data.mission}
                                onChange={(e) => form.setData('mission', e.target.value)}
                                placeholder="Misi sekolah"
                                rows={4}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={form.errors.mission} />
                        </div>

                        {/* Deskripsi */}
                        <div className="grid gap-2">
                            <Label htmlFor="description">Deskripsi</Label>
                            <textarea
                                id="description"
                                value={form.data.description}
                                onChange={(e) => form.setData('description', e.target.value)}
                                placeholder="Deskripsi singkat tentang sekolah"
                                rows={4}
                                className="border-input bg-background ring-offset-background placeholder:text-muted-foreground focus-visible:ring-ring flex min-h-[80px] w-full rounded-md border px-3 py-2 text-sm focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50"
                            />
                            <InputError message={form.errors.description} />
                        </div>
                    </div>

                    {/* Logo Upload */}
                    <div className="space-y-4">
                        <Heading variant="small" title="Logo Sekolah" description="Upload logo sekolah dalam format gambar" />

                        {team.logo_path && (
                            <div className="flex items-center gap-4">
                                <img
                                    src={'/storage/' + team.logo_path}
                                    alt="Logo sekolah"
                                    className="h-20 w-20 rounded-md object-contain border"
                                />
                                <p className="text-muted-foreground text-sm">Logo saat ini</p>
                            </div>
                        )}

                        <div className="grid gap-2">
                            <Label htmlFor="logo">Logo Baru</Label>
                            <Input
                                id="logo"
                                type="file"
                                accept="image/*"
                                onChange={(e) => form.setData('logo', e.target.files?.[0] ?? null)}
                            />
                            <InputError message={form.errors.logo} />
                        </div>
                    </div>

                    <div className="flex items-center gap-4">
                        <Button type="submit" disabled={form.processing}>
                            Simpan
                        </Button>
                    </div>
                </form>
            </div>
        </>
    );
}

SchoolProfile.layout = (props: { team?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Profil Sekolah',
            href: props.team ? SchoolProfileController.edit.url(props.team.slug) : '/',
        },
    ],
});
