# Fase 4: Penilaian & Rapor — Part 4b: Report Cards Pages + Sidebar

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Prerequisites:** Part 4a complete.

---

## Task 13: Report Cards Pages (index, show)

**Files:**
- Create: `resources/js/pages/academic/report-cards/index.tsx`
- Create: `resources/js/pages/academic/report-cards/show.tsx`

- [ ] **Step 1: Create report-cards/index.tsx**

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { FileTextIcon } from 'lucide-react';

import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import type { Classroom, Semester } from '@/types/academic';

interface StudentRow {
    user_id: number;
    name: string;
    report_card_id: number | null;
    has_report_card: boolean;
}

interface Props {
    classrooms: Classroom[];
    semesters: Semester[];
    students: StudentRow[];
    filters: { classroom_id?: string; semester_id?: string };
}

export default function ReportCardIndex({ classrooms, semesters, students, filters }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    function applyFilter(key: string, value: string) {
        router.get(
            ReportCardController.index.url(teamSlug),
            { ...filters, [key]: value },
            { preserveState: true, replace: true },
        );
    }

    function generateAll() {
        const missing = students.filter((s) => !s.has_report_card);
        if (missing.length === 0) return;
        if (!confirm(`Generate ${missing.length} rapor yang belum ada?`)) return;

        missing.forEach((s) => {
            router.post(
                ReportCardController.store.url(teamSlug),
                {
                    semester_id: filters.semester_id,
                    classroom_id: filters.classroom_id,
                    student_user_id: s.user_id,
                },
                { preserveScroll: true },
            );
        });
    }

    const canGenerate = filters.classroom_id && filters.semester_id;

    return (
        <>
            <Head title="Rapor" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Rapor"
                        action={
                            canGenerate && students.some((s) => !s.has_report_card) ? (
                                <Button onClick={generateAll}>
                                    Generate Semua Rapor Kelas
                                </Button>
                            ) : undefined
                        }
                    />

                    <div className="flex gap-4">
                        <Select value={filters.classroom_id ?? ''} onValueChange={(v) => applyFilter('classroom_id', v)}>
                            <SelectTrigger className="w-48"><SelectValue placeholder="Pilih Kelas" /></SelectTrigger>
                            <SelectContent>
                                {classrooms.map((c) => (
                                    <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                        <Select value={filters.semester_id ?? ''} onValueChange={(v) => applyFilter('semester_id', v)}>
                            <SelectTrigger className="w-48"><SelectValue placeholder="Pilih Semester" /></SelectTrigger>
                            <SelectContent>
                                {semesters.map((s) => (
                                    <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>
                                ))}
                            </SelectContent>
                        </Select>
                    </div>

                    {canGenerate && students.length === 0 && (
                        <p className="text-sm text-muted-foreground">Tidak ada siswa terdaftar di kelas ini.</p>
                    )}

                    {students.length > 0 && (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Siswa</TableHead>
                                    <TableHead>Status Rapor</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {students.map((s) => (
                                    <TableRow key={s.user_id}>
                                        <TableCell className="font-medium">{s.name}</TableCell>
                                        <TableCell>
                                            {s.has_report_card ? (
                                                <Badge variant="default">Sudah</Badge>
                                            ) : (
                                                <Badge variant="outline">Belum</Badge>
                                            )}
                                        </TableCell>
                                        <TableCell>
                                            {s.has_report_card && s.report_card_id ? (
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link href={ReportCardController.show.url({ current_team: teamSlug, reportCard: s.report_card_id })}>
                                                        Lihat
                                                    </Link>
                                                </Button>
                                            ) : (
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    onClick={() => router.post(ReportCardController.store.url(teamSlug), {
                                                        semester_id: filters.semester_id,
                                                        classroom_id: filters.classroom_id,
                                                        student_user_id: s.user_id,
                                                    })}
                                                >
                                                    Generate
                                                </Button>
                                            )}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}

                    {!canGenerate && (
                        <div className="flex flex-col items-center gap-3 py-16 text-center text-muted-foreground">
                            <FileTextIcon className="h-10 w-10 opacity-40" />
                            <p>Pilih kelas dan semester untuk melihat daftar rapor.</p>
                        </div>
                    )}
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create report-cards/show.tsx**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import type { AssessmentCategory, ReportCard, SubjectGrade } from '@/types/academic';

interface AttendanceSummaryItem {
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    count: number;
}

interface Props {
    report_card: ReportCard;
    categories: AssessmentCategory[];
    subject_grades: SubjectGrade[];
    overall_average: number;
    attendance_summary: AttendanceSummaryItem[];
}

const statusColors: Record<string, string> = {
    hadir: 'bg-green-100 text-green-800',
    sakit: 'bg-yellow-100 text-yellow-800',
    izin: 'bg-blue-100 text-blue-800',
    alpa: 'bg-red-100 text-red-800',
};
const statusLabels: Record<string, string> = { hadir: 'Hadir', sakit: 'Sakit', izin: 'Izin', alpa: 'Alpa' };

export default function ReportCardShow({ report_card, categories, subject_grades, overall_average, attendance_summary }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const notesForm = useForm({ homeroom_notes: report_card.homeroom_notes ?? '' });

    function submitNotes(e: React.FormEvent) {
        e.preventDefault();
        notesForm.patch(ReportCardController.update.url({ current_team: teamSlug, reportCard: report_card.id }));
    }

    function regenerate() {
        const student = report_card.student as { id: number } | null;
        const classroom = report_card.classroom as { id: number } | null;
        if (!student || !classroom) return;
        window.location.href = '#'; // replaced by router.post below
    }

    const student = report_card.student as { name?: string; email?: string } | null;
    const classroom = report_card.classroom as { name?: string } | null;
    const semester = report_card.semester as { name?: string } | null;
    const generatedBy = report_card.generatedBy as { name?: string } | null;

    return (
        <>
            <Head title={`Rapor — ${student?.name ?? ''}`} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    {/* Header */}
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">{student?.name}</h1>
                            <p className="text-sm text-muted-foreground">
                                {classroom?.name} · {semester?.name}
                            </p>
                            <p className="text-xs text-muted-foreground">
                                Digenerate oleh {generatedBy?.name} pada{' '}
                                {report_card.generated_at
                                    ? new Date(report_card.generated_at).toLocaleString('id-ID')
                                    : '-'}
                            </p>
                        </div>
                        <Button variant="outline" asChild>
                            <Link href={ReportCardController.index.url(teamSlug)}>Kembali</Link>
                        </Button>
                    </div>

                    {/* Nilai */}
                    <Card>
                        <CardHeader><CardTitle>Nilai Akademik</CardTitle></CardHeader>
                        <CardContent>
                            <Table>
                                <TableHeader>
                                    <TableRow>
                                        <TableHead>Mata Pelajaran</TableHead>
                                        {categories.map((cat) => (
                                            <TableHead key={cat.id}>{cat.name} ({cat.weight}%)</TableHead>
                                        ))}
                                        <TableHead className="font-bold">Nilai Akhir</TableHead>
                                    </TableRow>
                                </TableHeader>
                                <TableBody>
                                    {subject_grades.map((sg) => (
                                        <TableRow key={sg.subject_id}>
                                            <TableCell className="font-medium">{sg.subject_name}</TableCell>
                                            {categories.map((cat) => (
                                                <TableCell key={cat.id}>
                                                    {sg.category_scores[cat.id] ?? 0}
                                                </TableCell>
                                            ))}
                                            <TableCell className="font-bold">{sg.final_grade}</TableCell>
                                        </TableRow>
                                    ))}
                                    <TableRow className="bg-muted/50 font-semibold">
                                        <TableCell>Rata-rata</TableCell>
                                        {categories.map((cat) => <TableCell key={cat.id} />)}
                                        <TableCell className="font-bold">{overall_average}</TableCell>
                                    </TableRow>
                                </TableBody>
                            </Table>
                        </CardContent>
                    </Card>

                    {/* Kehadiran */}
                    <Card>
                        <CardHeader><CardTitle>Rekap Kehadiran</CardTitle></CardHeader>
                        <CardContent>
                            <div className="flex gap-3">
                                {(['hadir', 'sakit', 'izin', 'alpa'] as const).map((status) => {
                                    const item = attendance_summary.find((a) => a.status === status);
                                    return (
                                        <div key={status} className={`rounded-lg px-4 py-2 text-center ${statusColors[status]}`}>
                                            <div className="text-2xl font-bold">{item?.count ?? 0}</div>
                                            <div className="text-xs font-medium">{statusLabels[status]}</div>
                                        </div>
                                    );
                                })}
                            </div>
                        </CardContent>
                    </Card>

                    {/* Catatan Wali Kelas */}
                    <Card>
                        <CardHeader><CardTitle>Catatan Wali Kelas</CardTitle></CardHeader>
                        <CardContent>
                            <form onSubmit={submitNotes} className="space-y-4">
                                <div>
                                    <Label htmlFor="homeroom_notes">Catatan</Label>
                                    <Textarea
                                        id="homeroom_notes"
                                        value={notesForm.data.homeroom_notes}
                                        onChange={(e) => notesForm.setData('homeroom_notes', e.target.value)}
                                        placeholder="Catatan wali kelas untuk siswa ini..."
                                        rows={4}
                                    />
                                    <InputError message={notesForm.errors.homeroom_notes} />
                                </div>
                                <Button type="submit" disabled={notesForm.processing}>Simpan Catatan</Button>
                            </form>
                        </CardContent>
                    </Card>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Run type check**

```bash
npm run types:check
```

Fix any TypeScript errors before continuing.

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/academic/report-cards/
git commit -m "feat: add report-cards frontend pages (index, show)"
```

---

## Task 14: Sidebar Update

**Files:**
- Modify: `resources/js/components/app-sidebar.tsx`

- [ ] **Step 1: Add imports to app-sidebar.tsx**

Add these 3 imports after the existing Academic controller imports (around line 26):

```typescript
import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController';
```

Also add `ClipboardCheck, FileSpreadsheet, Tag` to the lucide-react import (or use existing icons). Suggested icon mapping:
- Kategori Penilaian → `Tag`
- Daftar Penilaian → `ClipboardCheck`  
- Rapor → `FileSpreadsheet`

Final lucide-react import line should include: `Tag, ClipboardCheck, FileSpreadsheet` (add to existing import).

- [ ] **Step 2: Add penilaianNavGroups array**

Add this after the `scheduleNavGroups` definition and before `cmsNavGroups`:

```typescript
    const penilaianNavGroups: NavGroup[] = [
        {
            title: 'Penilaian',
            icon: FileSpreadsheet,
            items: [
                {
                    title: 'Kategori Penilaian',
                    href: slug ? AssessmentCategoryController.index.url(slug) : '/',
                    icon: Tag,
                },
                {
                    title: 'Daftar Penilaian',
                    href: slug ? AssessmentController.index.url(slug) : '/',
                    icon: ClipboardCheck,
                },
                {
                    title: 'Rapor',
                    href: slug ? ReportCardController.index.url(slug) : '/',
                    icon: FileSpreadsheet,
                },
            ],
        },
    ];
```

- [ ] **Step 3: Add NavGroups to SidebarContent**

In the JSX, add this after the existing `<NavGroups groups={scheduleNavGroups} ... />` line:

```tsx
                <NavGroups groups={penilaianNavGroups} label="Penilaian" />
```

- [ ] **Step 4: Run type check + lint**

```bash
npm run types:check
npm run lint:check
```

Fix errors if any.

- [ ] **Step 5: Final build**

```bash
npm run build
```

Expected: build succeeds, no errors.

- [ ] **Step 6: Run all tests**

```bash
php artisan test --compact
```

Expected: all tests pass.

- [ ] **Step 7: Commit**

```bash
git add resources/js/components/app-sidebar.tsx
git commit -m "feat: add Penilaian nav group to sidebar (assessment categories, assessments, report cards)"
```

---

## Final Verification

- [ ] Run full CI check: `composer ci:check`
- [ ] Confirm test count increased by ~23 from baseline (257 → ~280 tests)
