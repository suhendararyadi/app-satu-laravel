# Fase 4: Penilaian & Rapor — Part 4a: TypeScript Types + Assessment Pages

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development or superpowers:executing-plans.

**Prerequisites:** Part 3 complete + `npm run build` done (Wayfinder files exist).

**Wayfinder imports to use:**
- `import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController'`
- `import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController'`
- `import ReportCardController from '@/actions/App/Http/Controllers/Academic/ReportCardController'`

---

## Task 10: TypeScript Types

**Files:**
- Modify: `resources/js/types/academic.ts` — append new interfaces

- [ ] **Step 1: Append to end of `resources/js/types/academic.ts`**

```typescript
export interface AssessmentCategory {
    id: number;
    team_id: number;
    name: string;
    weight: string; // decimal comes as string from Laravel
    assessments_count?: number;
    created_at: string;
    updated_at: string;
}

export interface Assessment {
    id: number;
    team_id: number;
    classroom_id: number;
    subject_id: number;
    semester_id: number;
    assessment_category_id: number;
    title: string;
    max_score: string; // decimal as string
    date: string;
    teacher_user_id: number;
    classroom?: Classroom;
    subject?: Subject;
    semester?: Semester;
    category?: AssessmentCategory;
    scores_filled?: number;
    scores_total?: number;
    created_at: string;
    updated_at: string;
}

export interface Score {
    student_user_id: number;
    name: string;
    score: string | null;
    notes: string | null;
}

export interface ReportCard {
    id: number;
    team_id: number;
    semester_id: number;
    classroom_id: number;
    student_user_id: number;
    generated_by: number;
    homeroom_notes: string | null;
    generated_at: string | null;
    student?: { id: number; name: string; email: string };
    classroom?: Classroom;
    semester?: Semester;
    generated_by_user?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}

export interface SubjectGrade {
    subject_id: number;
    subject_name: string;
    category_scores: Record<number, number>;
    final_grade: number;
}
```

- [ ] **Step 2: Commit**

```bash
git add resources/js/types/academic.ts
git commit -m "feat: add TypeScript types for assessment categories, assessments, scores, and report cards"
```

---

## Task 11: assessment-categories/index.tsx

**Files:**
- Create: `resources/js/pages/academic/assessment-categories/index.tsx`
- Create: `resources/js/pages/academic/assessment-categories/create.tsx`
- Create: `resources/js/pages/academic/assessment-categories/edit.tsx`

- [ ] **Step 1: Create index page**

Full content of `resources/js/pages/academic/assessment-categories/index.tsx`:

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { TagIcon } from 'lucide-react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';
import type { AssessmentCategory } from '@/types/academic';

interface Props {
    categories: AssessmentCategory[];
    total_weight: string;
}

export default function AssessmentCategoryIndex({ categories, total_weight }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';
    const totalNum = parseFloat(total_weight);

    function handleDelete(id: number) {
        if (!confirm('Hapus kategori ini?')) return;
        router.delete(
            AssessmentCategoryController.destroy.url({ current_team: teamSlug, assessmentCategory: id }),
        );
    }

    return (
        <>
            <Head title="Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Kategori Penilaian"
                        action={
                            <Button asChild>
                                <Link href={AssessmentCategoryController.create.url(teamSlug)}>
                                    Tambah Kategori
                                </Link>
                            </Button>
                        }
                    />

                    {totalNum !== 100 && (
                        <div className="rounded-md border border-yellow-300 bg-yellow-50 px-4 py-3 text-sm text-yellow-800">
                            Total bobot saat ini <strong>{totalNum}%</strong>. Harus tepat 100% agar nilai akhir dapat dihitung.
                        </div>
                    )}

                    <DataTableWrapper
                        loading={false}
                        isEmpty={categories.length === 0}
                        emptyState={{
                            icon: TagIcon,
                            title: 'Belum ada kategori penilaian',
                            description: 'Buat kategori seperti Tugas, UH, UTS, UAS.',
                            action: {
                                label: 'Tambah Kategori',
                                href: AssessmentCategoryController.create.url(teamSlug),
                            },
                        }}
                    >
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama</TableHead>
                                    <TableHead>Bobot (%)</TableHead>
                                    <TableHead>Jumlah Assessment</TableHead>
                                    <TableHead className="w-32">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {categories.map((cat) => (
                                    <TableRow key={cat.id}>
                                        <TableCell className="font-medium">{cat.name}</TableCell>
                                        <TableCell>
                                            <Badge variant={totalNum === 100 ? 'default' : 'destructive'}>
                                                {cat.weight}%
                                            </Badge>
                                        </TableCell>
                                        <TableCell>{cat.assessments_count ?? 0}</TableCell>
                                        <TableCell>
                                            <div className="flex gap-2">
                                                <Button size="sm" variant="outline" asChild>
                                                    <Link href={AssessmentCategoryController.edit.url({ current_team: teamSlug, assessmentCategory: cat.id })}>
                                                        Edit
                                                    </Link>
                                                </Button>
                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    disabled={(cat.assessments_count ?? 0) > 0}
                                                    onClick={() => handleDelete(cat.id)}
                                                >
                                                    Hapus
                                                </Button>
                                            </div>
                                        </TableCell>
                                    </TableRow>
                                ))}
                                <TableRow className="bg-muted/50 font-semibold">
                                    <TableCell>Total</TableCell>
                                    <TableCell>
                                        <Badge variant={totalNum === 100 ? 'default' : 'destructive'}>
                                            {totalNum}%
                                        </Badge>
                                    </TableCell>
                                    <TableCell colSpan={2} />
                                </TableRow>
                            </TableBody>
                        </Table>
                    </DataTableWrapper>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create create.tsx**

Full content of `resources/js/pages/academic/assessment-categories/create.tsx`:

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';

export default function AssessmentCategoryCreate() {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({ name: '', weight: '' });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AssessmentCategoryController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="max-w-md space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Kategori Penilaian</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} placeholder="UTS" />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="weight">Bobot (%)</Label>
                            <Input id="weight" type="number" min={0} max={100} step={0.01} value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)} placeholder="25" />
                            <InputError message={form.errors.weight} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>Simpan</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={AssessmentCategoryController.index.url(teamSlug)}>Batal</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Create edit.tsx**

Full content of `resources/js/pages/academic/assessment-categories/edit.tsx`:

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentCategoryController from '@/actions/App/Http/Controllers/Academic/AssessmentCategoryController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import type { AssessmentCategory } from '@/types/academic';

interface Props {
    category: AssessmentCategory;
}

export default function AssessmentCategoryEdit({ category }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({ name: category.name, weight: category.weight });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(AssessmentCategoryController.update.url({ current_team: teamSlug, assessmentCategory: category.id }));
    }

    return (
        <>
            <Head title="Edit Kategori Penilaian" />
            <div className="px-4 py-6">
                <div className="max-w-md space-y-6">
                    <h1 className="text-2xl font-bold">Edit Kategori Penilaian</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div>
                            <Label htmlFor="name">Nama</Label>
                            <Input id="name" value={form.data.name} onChange={(e) => form.setData('name', e.target.value)} />
                            <InputError message={form.errors.name} />
                        </div>
                        <div>
                            <Label htmlFor="weight">Bobot (%)</Label>
                            <Input id="weight" type="number" min={0} max={100} step={0.01} value={form.data.weight} onChange={(e) => form.setData('weight', e.target.value)} />
                            <InputError message={form.errors.weight} />
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>Perbarui</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={AssessmentCategoryController.index.url(teamSlug)}>Batal</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 4: Commit**

```bash
git add resources/js/pages/academic/assessment-categories/
git commit -m "feat: add assessment-categories frontend pages (index, create, edit)"
```

---

## Task 12: Assessments Pages (index, create, edit, show)

**Files:**
- Create: `resources/js/pages/academic/assessments/index.tsx`
- Create: `resources/js/pages/academic/assessments/create.tsx`
- Create: `resources/js/pages/academic/assessments/edit.tsx`
- Create: `resources/js/pages/academic/assessments/show.tsx`

- [ ] **Step 1: Create assessments/index.tsx**

```tsx
import { Head, Link, router, usePage } from '@inertiajs/react';
import { ClipboardIcon } from 'lucide-react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import DataTableWrapper from '@/components/data-table-wrapper';
import PageHeader from '@/components/page-header';
import { Button } from '@/components/ui/button';
import { Pagination } from '@/components/ui/pagination';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import type { Assessment, Classroom, Semester } from '@/types/academic';

interface PaginationMeta {
    current_page: number; last_page: number; per_page: number;
    total: number; from: number | null; to: number | null;
}
interface PaginatedAssessments extends PaginationMeta { data: Assessment[]; }

interface Props {
    assessments: PaginatedAssessments;
    classrooms: Classroom[];
    semesters: Semester[];
    filters: { classroom_id?: string; semester_id?: string };
}

export default function AssessmentIndex({ assessments, classrooms, semesters, filters }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    function applyFilter(key: string, value: string) {
        router.get(AssessmentController.index.url(teamSlug), { ...filters, [key]: value }, { preserveState: true, replace: true });
    }

    return (
        <>
            <Head title="Daftar Penilaian" />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <PageHeader
                        title="Daftar Penilaian"
                        action={
                            <Button asChild>
                                <Link href={AssessmentController.create.url(teamSlug)}>Tambah Assessment</Link>
                            </Button>
                        }
                    />

                    <div className="flex gap-4">
                        <Select value={filters.classroom_id ?? ''} onValueChange={(v) => applyFilter('classroom_id', v)}>
                            <SelectTrigger className="w-48"><SelectValue placeholder="Semua Kelas" /></SelectTrigger>
                            <SelectContent>
                                {classrooms.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                        <Select value={filters.semester_id ?? ''} onValueChange={(v) => applyFilter('semester_id', v)}>
                            <SelectTrigger className="w-48"><SelectValue placeholder="Semua Semester" /></SelectTrigger>
                            <SelectContent>
                                {semesters.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}
                            </SelectContent>
                        </Select>
                    </div>

                    <DataTableWrapper loading={false} isEmpty={assessments.data.length === 0} emptyState={{ icon: ClipboardIcon, title: 'Belum ada assessment', description: 'Buat assessment pertama untuk kelas Anda.', action: { label: 'Tambah Assessment', href: AssessmentController.create.url(teamSlug) } }}>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Kategori</TableHead>
                                    <TableHead>Mapel</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                    <TableHead>Nilai Terisi</TableHead>
                                    <TableHead className="w-24">Aksi</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {assessments.data.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell>
                                            <Link href={AssessmentController.show.url({ current_team: teamSlug, assessment: a.id })} className="font-medium hover:underline">
                                                {a.title}
                                            </Link>
                                        </TableCell>
                                        <TableCell>{(a.category as { name?: string })?.name ?? '-'}</TableCell>
                                        <TableCell>{(a.subject as { name?: string })?.name ?? '-'}</TableCell>
                                        <TableCell>{(a.classroom as { name?: string })?.name ?? '-'}</TableCell>
                                        <TableCell>{a.date}</TableCell>
                                        <TableCell>{a.scores_filled ?? 0} / {a.scores_total ?? 0}</TableCell>
                                        <TableCell>
                                            <Button size="sm" variant="outline" asChild>
                                                <Link href={AssessmentController.edit.url({ current_team: teamSlug, assessment: a.id })}>Edit</Link>
                                            </Button>
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </DataTableWrapper>
                    <Pagination meta={assessments} />
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 2: Create assessments/create.tsx**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { AssessmentCategory, Classroom, Semester, Subject } from '@/types/academic';

interface Props {
    classrooms: Classroom[];
    subjects: Subject[];
    semesters: Semester[];
    categories: AssessmentCategory[];
}

export default function AssessmentCreate({ classrooms, subjects, semesters, categories }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        classroom_id: '', subject_id: '', semester_id: '',
        assessment_category_id: '', title: '',
        max_score: '100', date: new Date().toISOString().split('T')[0],
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AssessmentController.store.url(teamSlug));
    }

    return (
        <>
            <Head title="Tambah Assessment" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-6">
                    <h1 className="text-2xl font-bold">Tambah Assessment</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label htmlFor="classroom_id">Kelas</Label>
                                <Select value={form.data.classroom_id} onValueChange={(v) => form.setData('classroom_id', v)}>
                                    <SelectTrigger id="classroom_id"><SelectValue placeholder="Pilih kelas" /></SelectTrigger>
                                    <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.classroom_id} />
                            </div>
                            <div>
                                <Label htmlFor="subject_id">Mata Pelajaran</Label>
                                <Select value={form.data.subject_id} onValueChange={(v) => form.setData('subject_id', v)}>
                                    <SelectTrigger id="subject_id"><SelectValue placeholder="Pilih mapel" /></SelectTrigger>
                                    <SelectContent>{subjects.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.subject_id} />
                            </div>
                            <div>
                                <Label htmlFor="semester_id">Semester</Label>
                                <Select value={form.data.semester_id} onValueChange={(v) => form.setData('semester_id', v)}>
                                    <SelectTrigger id="semester_id"><SelectValue placeholder="Pilih semester" /></SelectTrigger>
                                    <SelectContent>{semesters.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.semester_id} />
                            </div>
                            <div>
                                <Label htmlFor="assessment_category_id">Kategori</Label>
                                <Select value={form.data.assessment_category_id} onValueChange={(v) => form.setData('assessment_category_id', v)}>
                                    <SelectTrigger id="assessment_category_id"><SelectValue placeholder="Pilih kategori" /></SelectTrigger>
                                    <SelectContent>{categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name} ({c.weight}%)</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.assessment_category_id} />
                            </div>
                            <div className="col-span-2">
                                <Label htmlFor="title">Judul</Label>
                                <Input id="title" value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} placeholder="UTS Matematika Kelas X" />
                                <InputError message={form.errors.title} />
                            </div>
                            <div>
                                <Label htmlFor="max_score">Nilai Maksimal</Label>
                                <Input id="max_score" type="number" min={0} step={0.01} value={form.data.max_score} onChange={(e) => form.setData('max_score', e.target.value)} />
                                <InputError message={form.errors.max_score} />
                            </div>
                            <div>
                                <Label htmlFor="date">Tanggal</Label>
                                <Input id="date" type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                                <InputError message={form.errors.date} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>Simpan</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={AssessmentController.index.url(teamSlug)}>Batal</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 3: Create assessments/edit.tsx**

Same structure as create but uses `patch` and pre-fills values. Full content:

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import {
    Select, SelectContent, SelectItem, SelectTrigger, SelectValue,
} from '@/components/ui/select';
import type { Assessment, AssessmentCategory, Classroom, Semester, Subject } from '@/types/academic';

interface Props {
    assessment: Assessment;
    classrooms: Classroom[];
    subjects: Subject[];
    semesters: Semester[];
    categories: AssessmentCategory[];
}

export default function AssessmentEdit({ assessment, classrooms, subjects, semesters, categories }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({
        classroom_id: String(assessment.classroom_id),
        subject_id: String(assessment.subject_id),
        semester_id: String(assessment.semester_id),
        assessment_category_id: String(assessment.assessment_category_id),
        title: assessment.title,
        max_score: assessment.max_score,
        date: assessment.date,
    });

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.patch(AssessmentController.update.url({ current_team: teamSlug, assessment: assessment.id }));
    }

    return (
        <>
            <Head title="Edit Assessment" />
            <div className="px-4 py-6">
                <div className="max-w-2xl space-y-6">
                    <h1 className="text-2xl font-bold">Edit Assessment</h1>
                    <form onSubmit={submit} className="space-y-4">
                        <div className="grid grid-cols-2 gap-4">
                            <div>
                                <Label>Kelas</Label>
                                <Select value={form.data.classroom_id} onValueChange={(v) => form.setData('classroom_id', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>{classrooms.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.classroom_id} />
                            </div>
                            <div>
                                <Label>Mata Pelajaran</Label>
                                <Select value={form.data.subject_id} onValueChange={(v) => form.setData('subject_id', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>{subjects.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.subject_id} />
                            </div>
                            <div>
                                <Label>Semester</Label>
                                <Select value={form.data.semester_id} onValueChange={(v) => form.setData('semester_id', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>{semesters.map((s) => <SelectItem key={s.id} value={String(s.id)}>{s.name}</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.semester_id} />
                            </div>
                            <div>
                                <Label>Kategori</Label>
                                <Select value={form.data.assessment_category_id} onValueChange={(v) => form.setData('assessment_category_id', v)}>
                                    <SelectTrigger><SelectValue /></SelectTrigger>
                                    <SelectContent>{categories.map((c) => <SelectItem key={c.id} value={String(c.id)}>{c.name} ({c.weight}%)</SelectItem>)}</SelectContent>
                                </Select>
                                <InputError message={form.errors.assessment_category_id} />
                            </div>
                            <div className="col-span-2">
                                <Label>Judul</Label>
                                <Input value={form.data.title} onChange={(e) => form.setData('title', e.target.value)} />
                                <InputError message={form.errors.title} />
                            </div>
                            <div>
                                <Label>Nilai Maksimal</Label>
                                <Input type="number" min={0} step={0.01} value={form.data.max_score} onChange={(e) => form.setData('max_score', e.target.value)} />
                                <InputError message={form.errors.max_score} />
                            </div>
                            <div>
                                <Label>Tanggal</Label>
                                <Input type="date" value={form.data.date} onChange={(e) => form.setData('date', e.target.value)} />
                                <InputError message={form.errors.date} />
                            </div>
                        </div>
                        <div className="flex gap-2">
                            <Button type="submit" disabled={form.processing}>Perbarui</Button>
                            <Button type="button" variant="outline" asChild>
                                <Link href={AssessmentController.show.url({ current_team: teamSlug, assessment: assessment.id })}>Batal</Link>
                            </Button>
                        </div>
                    </form>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 4: Create assessments/show.tsx (batch score input)**

```tsx
import { Head, Link, useForm, usePage } from '@inertiajs/react';

import AssessmentController from '@/actions/App/Http/Controllers/Academic/AssessmentController';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import {
    Table, TableBody, TableCell, TableHead, TableHeader, TableRow,
} from '@/components/ui/table';
import type { Assessment, Score } from '@/types/academic';

interface Props {
    assessment: Assessment;
    scores: Score[];
}

export default function AssessmentShow({ assessment, scores }: Props) {
    const { currentTeam } = usePage().props;
    const teamSlug = (currentTeam as { slug: string } | null)?.slug ?? '';

    const form = useForm({ scores: scores.map((s) => ({ student_user_id: s.student_user_id, score: s.score ?? '', notes: s.notes ?? '' })) });

    function updateRow(index: number, field: 'score' | 'notes', value: string) {
        const updated = form.data.scores.map((r, i) => i === index ? { ...r, [field]: value } : r);
        form.setData('scores', updated);
    }

    function submit(e: React.FormEvent) {
        e.preventDefault();
        form.post(AssessmentController.storeScores.url({ current_team: teamSlug, assessment: assessment.id }));
    }

    const classroom = assessment.classroom as { name?: string };
    const subject = assessment.subject as { name?: string };
    const semester = assessment.semester as { name?: string };
    const category = assessment.category as { name?: string };

    return (
        <>
            <Head title={assessment.title} />
            <div className="px-4 py-6">
                <div className="space-y-6">
                    <div className="flex items-start justify-between">
                        <div>
                            <h1 className="text-2xl font-bold">{assessment.title}</h1>
                            <p className="mt-1 text-sm text-muted-foreground">
                                {classroom?.name} · {subject?.name} · {category?.name} · {semester?.name} · {assessment.date}
                            </p>
                            <p className="text-sm text-muted-foreground">Nilai Maks: {assessment.max_score}</p>
                        </div>
                        <div className="flex gap-2">
                            <Button variant="outline" asChild>
                                <Link href={AssessmentController.edit.url({ current_team: teamSlug, assessment: assessment.id })}>Edit</Link>
                            </Button>
                            <Button variant="outline" asChild>
                                <Link href={AssessmentController.index.url(teamSlug)}>Kembali</Link>
                            </Button>
                        </div>
                    </div>

                    <form onSubmit={submit} className="space-y-4">
                        <h2 className="text-lg font-semibold">Input Nilai</h2>
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Nama Siswa</TableHead>
                                    <TableHead className="w-32">Nilai (0–{assessment.max_score})</TableHead>
                                    <TableHead>Catatan</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {form.data.scores.map((row, i) => (
                                    <TableRow key={row.student_user_id}>
                                        <TableCell className="font-medium">{scores[i].name}</TableCell>
                                        <TableCell>
                                            <Input
                                                type="number"
                                                min={0}
                                                max={parseFloat(String(assessment.max_score))}
                                                step={0.01}
                                                value={row.score}
                                                onChange={(e) => updateRow(i, 'score', e.target.value)}
                                                className="w-28"
                                            />
                                            <InputError message={form.errors[`scores.${i}.score` as keyof typeof form.errors]} />
                                        </TableCell>
                                        <TableCell>
                                            <Input
                                                value={row.notes}
                                                onChange={(e) => updateRow(i, 'notes', e.target.value)}
                                                placeholder="Catatan (opsional)"
                                            />
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                        <InputError message={form.errors.scores} />
                        <Button type="submit" disabled={form.processing}>Simpan Semua Nilai</Button>
                    </form>
                </div>
            </div>
        </>
    );
}
```

- [ ] **Step 5: Run type check**

```bash
npm run types:check
```

Fix any TypeScript errors before continuing.

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/academic/assessments/
git commit -m "feat: add assessments frontend pages (index, create, edit, show)"
```
