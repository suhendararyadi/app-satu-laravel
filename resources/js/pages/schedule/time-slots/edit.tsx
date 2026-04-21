import { Head, Link, useForm, usePage } from '@inertiajs/react';
import TimeSlotController from '@/actions/App/Http/Controllers/Schedule/TimeSlotController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { TimeSlot } from '@/types/schedule';

interface Props {
    timeSlot: TimeSlot;
}

export default function Edit({ timeSlot }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = currentTeam?.slug ?? '';

    const form = useForm({
        name: timeSlot.name,
        start_time: timeSlot.start_time,
        end_time: timeSlot.end_time,
        sort_order: String(timeSlot.sort_order),
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(
            TimeSlotController.update.url({
                current_team: teamSlug,
                timeSlot: timeSlot.id,
            }),
        );
    }

    return (
        <>
            <Head title="Edit Jam Pelajaran" />
            <div className="px-4 py-6">
                <div className="max-w-lg space-y-6">
                    <h1 className="text-2xl font-bold">Edit Jam Pelajaran</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input
                                id="name"
                                value={form.data.name}
                                onChange={(e) =>
                                    form.setData('name', e.target.value)
                                }
                            />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="start_time">Mulai</Label>
                            <Input
                                id="start_time"
                                type="time"
                                value={form.data.start_time}
                                onChange={(e) =>
                                    form.setData('start_time', e.target.value)
                                }
                            />
                            <InputError message={form.errors.start_time} />
                        </div>
                        <div>
                            <Label htmlFor="end_time">Selesai</Label>
                            <Input
                                id="end_time"
                                type="time"
                                value={form.data.end_time}
                                onChange={(e) =>
                                    form.setData('end_time', e.target.value)
                                }
                            />
                            <InputError message={form.errors.end_time} />
                        </div>
                        <div>
                            <Label htmlFor="sort_order">Urutan</Label>
                            <Input
                                id="sort_order"
                                type="number"
                                value={form.data.sort_order}
                                onChange={(e) =>
                                    form.setData('sort_order', e.target.value)
                                }
                            />
                            <InputError message={form.errors.sort_order} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>
                                Simpan
                            </Button>
                            <Button type="button" variant="outline" asChild>
                                <Link
                                    href={TimeSlotController.index.url(
                                        teamSlug,
                                    )}
                                >
                                    Kembali
                                </Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
