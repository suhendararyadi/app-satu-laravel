# Fase 7: Dashboard Role-Based — Part 2: Frontend Implementation Plan

> **For agentic workers:** REQUIRED SUB-SKILL: Use superpowers:subagent-driven-development (recommended) or superpowers:executing-plans to implement this plan task-by-task. Steps use checkbox (`- [ ]`) syntax for tracking.

**Goal:** Replace placeholder dashboard with four role-specific React components (Admin, Teacher, Student, Parent) driven by TypeScript types that match the backend response shape.

**Architecture:** New `resources/js/types/dashboard.ts` defines a discriminated-union `DashboardProps`. Four components in `resources/js/components/dashboard/` each receive their typed data. `resources/js/pages/dashboard.tsx` dispatches to the correct component based on the `role` prop.

**Tech Stack:** React 19, TypeScript, Inertia.js v3, Tailwind CSS v4, shadcn/ui (`Card`, `Badge`, `Table`).

**Prerequisites:** Part 1 backend plan must be complete (backend returns `role` + `data` props).

---

## File Map

**New:**
- `resources/js/types/dashboard.ts` — TypeScript types for all roles
- `resources/js/components/dashboard/admin-dashboard.tsx` — Owner/Admin component
- `resources/js/components/dashboard/teacher-dashboard.tsx` — Teacher component
- `resources/js/components/dashboard/student-dashboard.tsx` — Student component
- `resources/js/components/dashboard/parent-dashboard.tsx` — Parent component

**Modified:**
- `resources/js/pages/dashboard.tsx` — update props type + dispatch to role components

---

## Verification Commands

After each task run:
```bash
npm run types:check
npm run lint:check
```

After all tasks run:
```bash
npm run format:check
```

If `format:check` fails, run `npm run format` to auto-fix.

---

## Task 1: TypeScript types

**Files:**
- Create: `resources/js/types/dashboard.ts`

- [ ] **Step 1: Create the types file**

Create `resources/js/types/dashboard.ts`:

```typescript
export type AttendanceSummary = {
    hadir: number;
    sakit: number;
    izin: number;
    alpa: number;
};

// ─── Admin / Owner ────────────────────────────────────────────────────────────

export type RecentAssessment = {
    id: number;
    title: string;
    classroom: string | null;
    subject: string | null;
    date: string | null;
};

export type AdminDashboardData = {
    total_students: number;
    total_teachers: number;
    total_classrooms: number;
    attendance_today: AttendanceSummary & { date: string };
    recent_assessments: RecentAssessment[];
};

// ─── Teacher ─────────────────────────────────────────────────────────────────

export type TeacherClassroom = {
    id: number;
    name: string;
    grade: string | null;
    student_count: number;
};

export type TodaySchedule = {
    id: number;
    subject: string | null;
    room: string | null;
    time_slot: string | null;
    classroom?: string | null;
};

export type PendingAssessment = {
    id: number;
    title: string;
    classroom: string | null;
    subject: string | null;
    date: string | null;
    scored: number;
    total: number;
};

export type TeacherDashboardData = {
    my_classrooms: TeacherClassroom[];
    schedule_today: TodaySchedule[];
    pending_assessments: PendingAssessment[];
};

// ─── Student ──────────────────────────────────────────────────────────────────

export type StudentClassroom = {
    id: number;
    name: string;
    grade: string | null;
};

export type RecentScore = {
    id: number;
    score: number;
    assessment_title: string | null;
    subject: string | null;
    max_score: number;
};

export type StudentDashboardData = {
    classroom: StudentClassroom | null;
    schedule_today: TodaySchedule[];
    recent_scores: RecentScore[];
    attendance_summary: AttendanceSummary;
};

// ─── Parent ───────────────────────────────────────────────────────────────────

export type ChildStudent = {
    id: number;
    name: string;
    email: string;
};

export type ChildData = {
    student: ChildStudent;
    classroom: StudentClassroom | null;
    recent_scores: RecentScore[];
    attendance_summary: AttendanceSummary;
};

export type ParentDashboardData = {
    children: ChildData[];
};

// ─── Discriminated union ──────────────────────────────────────────────────────

export type DashboardProps =
    | { hasSchoolTeam: false }
    | { hasSchoolTeam: true; role: 'owner' | 'admin'; data: AdminDashboardData }
    | { hasSchoolTeam: true; role: 'teacher'; data: TeacherDashboardData }
    | { hasSchoolTeam: true; role: 'student'; data: StudentDashboardData }
    | { hasSchoolTeam: true; role: 'parent'; data: ParentDashboardData };
```

- [ ] **Step 2: Check types compile**

```bash
npm run types:check
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/types/dashboard.ts
git commit -m "feat: add TypeScript types for role-based dashboard"
```

---

## Task 2: AdminDashboard component

**Files:**
- Create: `resources/js/components/dashboard/admin-dashboard.tsx`

Content: 3 stat cards (Siswa/Guru/Kelas) + Kehadiran Hari Ini (4 status) + 5 Penilaian Terbaru.

- [ ] **Step 1: Create the component**

```bash
mkdir -p resources/js/components/dashboard
```

Create `resources/js/components/dashboard/admin-dashboard.tsx`:

```tsx
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table';
import type { AdminDashboardData } from '@/types/dashboard';

type Props = {
    data: AdminDashboardData;
};

const attendanceConfig = [
    { key: 'hadir', label: 'Hadir', className: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    { key: 'sakit', label: 'Sakit', className: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    { key: 'izin', label: 'Izin', className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    { key: 'alpa', label: 'Alpa', className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
] as const;

export default function AdminDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Stat cards */}
            <div className="grid gap-4 md:grid-cols-3">
                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Siswa</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">{data.total_students}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Guru</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">{data.total_teachers}</p>
                    </CardContent>
                </Card>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-sm font-medium text-muted-foreground">Total Kelas</CardTitle>
                    </CardHeader>
                    <CardContent>
                        <p className="text-3xl font-bold">{data.total_classrooms}</p>
                    </CardContent>
                </Card>
            </div>

            {/* Attendance today */}
            <Card>
                <CardHeader>
                    <CardTitle>Kehadiran Hari Ini</CardTitle>
                    <p className="text-sm text-muted-foreground">{data.attendance_today.date}</p>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-2">
                                <Badge className={className}>{label}</Badge>
                                <span className="font-semibold">{data.attendance_today[key]}</span>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>

            {/* Recent assessments */}
            <Card>
                <CardHeader>
                    <CardTitle>Penilaian Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.recent_assessments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada penilaian.</p>
                    ) : (
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead>Judul</TableHead>
                                    <TableHead>Kelas</TableHead>
                                    <TableHead>Mata Pelajaran</TableHead>
                                    <TableHead>Tanggal</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {data.recent_assessments.map((a) => (
                                    <TableRow key={a.id}>
                                        <TableCell>{a.title}</TableCell>
                                        <TableCell>{a.classroom ?? '-'}</TableCell>
                                        <TableCell>{a.subject ?? '-'}</TableCell>
                                        <TableCell>{a.date ?? '-'}</TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
```

- [ ] **Step 2: Check types**

```bash
npm run types:check
```

Expected: No errors

- [ ] **Step 3: Check lint**

```bash
npm run lint:check
```

Expected: No errors

- [ ] **Step 4: Commit**

```bash
git add resources/js/components/dashboard/admin-dashboard.tsx
git commit -m "feat: add AdminDashboard component"
```

---

## Task 3: TeacherDashboard component

**Files:**
- Create: `resources/js/components/dashboard/teacher-dashboard.tsx`

Content: Kelas Diampu cards + Jadwal Hari Ini list + Penilaian Pending list.

- [ ] **Step 1: Create the component**

Create `resources/js/components/dashboard/teacher-dashboard.tsx`:

```tsx
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { TeacherDashboardData } from '@/types/dashboard';

type Props = {
    data: TeacherDashboardData;
};

export default function TeacherDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Kelas Diampu */}
            <Card>
                <CardHeader>
                    <CardTitle>Kelas Diampu</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.my_classrooms.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada kelas yang diampu.</p>
                    ) : (
                        <div className="grid gap-3 md:grid-cols-2 lg:grid-cols-3">
                            {data.my_classrooms.map((c) => (
                                <div
                                    key={c.id}
                                    className="rounded-lg border p-4"
                                >
                                    <p className="font-semibold">{c.name}</p>
                                    <p className="text-sm text-muted-foreground">{c.grade ?? '-'}</p>
                                    <p className="mt-1 text-sm">
                                        <span className="font-medium">{c.student_count}</span> siswa
                                    </p>
                                </div>
                            ))}
                        </div>
                    )}
                </CardContent>
            </Card>

            {/* Jadwal Hari Ini */}
            <Card>
                <CardHeader>
                    <CardTitle>Jadwal Hari Ini</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.schedule_today.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Tidak ada jadwal hari ini.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.schedule_today.map((s) => (
                                <li key={s.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{s.subject ?? '-'}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {s.classroom ?? '-'}{s.room ? ` · ${s.room}` : ''}
                                        </p>
                                    </div>
                                    {s.time_slot && (
                                        <Badge variant="outline">{s.time_slot}</Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Penilaian Pending */}
            <Card>
                <CardHeader>
                    <CardTitle>Penilaian Belum Lengkap</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.pending_assessments.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Semua penilaian sudah lengkap.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.pending_assessments.map((a) => (
                                <li key={a.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{a.title}</p>
                                        <p className="text-sm text-muted-foreground">
                                            {a.classroom ?? '-'} · {a.subject ?? '-'} · {a.date ?? '-'}
                                        </p>
                                    </div>
                                    <Badge variant="secondary">
                                        {a.scored}/{a.total}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>
        </div>
    );
}
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/dashboard/teacher-dashboard.tsx
git commit -m "feat: add TeacherDashboard component"
```

---

## Task 4: StudentDashboard component

**Files:**
- Create: `resources/js/components/dashboard/student-dashboard.tsx`

Content: Kelas card + Jadwal Hari Ini list + 5 Nilai Terbaru list + Rekap Kehadiran badges.

- [ ] **Step 1: Create the component**

Create `resources/js/components/dashboard/student-dashboard.tsx`:

```tsx
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { StudentDashboardData } from '@/types/dashboard';

type Props = {
    data: StudentDashboardData;
};

const attendanceConfig = [
    { key: 'hadir', label: 'Hadir', className: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    { key: 'sakit', label: 'Sakit', className: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    { key: 'izin', label: 'Izin', className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    { key: 'alpa', label: 'Alpa', className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
] as const;

export default function StudentDashboard({ data }: Props) {
    return (
        <div className="space-y-6">
            {/* Kelas info */}
            <Card>
                <CardHeader>
                    <CardTitle>Kelas Saya</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.classroom ? (
                        <div>
                            <p className="text-xl font-semibold">{data.classroom.name}</p>
                            <p className="text-sm text-muted-foreground">{data.classroom.grade ?? '-'}</p>
                        </div>
                    ) : (
                        <p className="text-sm text-muted-foreground">Belum terdaftar di kelas manapun.</p>
                    )}
                </CardContent>
            </Card>

            {/* Jadwal Hari Ini */}
            <Card>
                <CardHeader>
                    <CardTitle>Jadwal Hari Ini</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.schedule_today.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Tidak ada jadwal hari ini.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.schedule_today.map((s) => (
                                <li key={s.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{s.subject ?? '-'}</p>
                                        {s.room && (
                                            <p className="text-sm text-muted-foreground">{s.room}</p>
                                        )}
                                    </div>
                                    {s.time_slot && (
                                        <Badge variant="outline">{s.time_slot}</Badge>
                                    )}
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Nilai Terbaru */}
            <Card>
                <CardHeader>
                    <CardTitle>Nilai Terbaru</CardTitle>
                </CardHeader>
                <CardContent>
                    {data.recent_scores.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada nilai.</p>
                    ) : (
                        <ul className="space-y-2">
                            {data.recent_scores.map((s) => (
                                <li key={s.id} className="flex items-center justify-between rounded-md border px-4 py-2">
                                    <div>
                                        <p className="font-medium">{s.assessment_title ?? '-'}</p>
                                        <p className="text-sm text-muted-foreground">{s.subject ?? '-'}</p>
                                    </div>
                                    <Badge variant="secondary">
                                        {s.score}/{s.max_score}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </CardContent>
            </Card>

            {/* Rekap Kehadiran */}
            <Card>
                <CardHeader>
                    <CardTitle>Rekap Kehadiran</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="flex flex-wrap gap-3">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-2">
                                <Badge className={className}>{label}</Badge>
                                <span className="font-semibold">{data.attendance_summary[key]}</span>
                            </div>
                        ))}
                    </div>
                </CardContent>
            </Card>
        </div>
    );
}
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/dashboard/student-dashboard.tsx
git commit -m "feat: add StudentDashboard component"
```

---

## Task 5: ParentDashboard component

**Files:**
- Create: `resources/js/components/dashboard/parent-dashboard.tsx`

Content: Per-anak card with student name, kelas, 3 nilai terbaru, rekap kehadiran.

- [ ] **Step 1: Create the component**

Create `resources/js/components/dashboard/parent-dashboard.tsx`:

```tsx
import { Badge } from '@/components/ui/badge';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import type { ChildData, ParentDashboardData } from '@/types/dashboard';

type Props = {
    data: ParentDashboardData;
};

const attendanceConfig = [
    { key: 'hadir', label: 'Hadir', className: 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-200' },
    { key: 'sakit', label: 'Sakit', className: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-200' },
    { key: 'izin', label: 'Izin', className: 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-200' },
    { key: 'alpa', label: 'Alpa', className: 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-200' },
] as const;

function ChildCard({ child }: { child: ChildData }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>{child.student.name}</CardTitle>
                {child.classroom && (
                    <p className="text-sm text-muted-foreground">
                        {child.classroom.name}
                        {child.classroom.grade ? ` · ${child.classroom.grade}` : ''}
                    </p>
                )}
            </CardHeader>
            <CardContent className="space-y-4">
                {/* Nilai Terbaru */}
                <div>
                    <p className="mb-2 text-sm font-semibold">Nilai Terbaru</p>
                    {child.recent_scores.length === 0 ? (
                        <p className="text-sm text-muted-foreground">Belum ada nilai.</p>
                    ) : (
                        <ul className="space-y-1">
                            {child.recent_scores.map((s) => (
                                <li key={s.id} className="flex items-center justify-between text-sm">
                                    <span className="text-muted-foreground">
                                        {s.assessment_title ?? '-'} ({s.subject ?? '-'})
                                    </span>
                                    <Badge variant="secondary">
                                        {s.score}/{s.max_score}
                                    </Badge>
                                </li>
                            ))}
                        </ul>
                    )}
                </div>

                {/* Rekap Kehadiran */}
                <div>
                    <p className="mb-2 text-sm font-semibold">Rekap Kehadiran</p>
                    <div className="flex flex-wrap gap-2">
                        {attendanceConfig.map(({ key, label, className }) => (
                            <div key={key} className="flex items-center gap-1">
                                <Badge className={className}>{label}</Badge>
                                <span className="text-sm font-semibold">{child.attendance_summary[key]}</span>
                            </div>
                        ))}
                    </div>
                </div>
            </CardContent>
        </Card>
    );
}

export default function ParentDashboard({ data }: Props) {
    if (data.children.length === 0) {
        return (
            <Card>
                <CardContent className="py-8 text-center">
                    <p className="text-muted-foreground">Tidak ada data anak yang terdaftar di sekolah ini.</p>
                </CardContent>
            </Card>
        );
    }

    return (
        <div className="space-y-6">
            {data.children.map((child) => (
                <ChildCard key={child.student.id} child={child} />
            ))}
        </div>
    );
}
```

- [ ] **Step 2: Check types and lint**

```bash
npm run types:check && npm run lint:check
```

Expected: No errors

- [ ] **Step 3: Commit**

```bash
git add resources/js/components/dashboard/parent-dashboard.tsx
git commit -m "feat: add ParentDashboard component"
```

---

## Task 6: Update dashboard.tsx page

**Files:**
- Modify: `resources/js/pages/dashboard.tsx`

Replace the existing placeholder content with the discriminated-union dispatch.

- [ ] **Step 1: Replace dashboard.tsx**

Replace `resources/js/pages/dashboard.tsx` entirely:

```tsx
import { Head } from '@inertiajs/react';

import AdminDashboard from '@/components/dashboard/admin-dashboard';
import ParentDashboard from '@/components/dashboard/parent-dashboard';
import StudentDashboard from '@/components/dashboard/student-dashboard';
import TeacherDashboard from '@/components/dashboard/teacher-dashboard';
import OnboardingBanner from '@/components/onboarding-banner';
import { dashboard } from '@/routes';
import type { DashboardProps } from '@/types/dashboard';

export default function Dashboard(props: DashboardProps) {
    return (
        <>
            <Head title="Dashboard" />
            <div className="flex h-full flex-1 flex-col gap-4 overflow-x-auto rounded-xl p-4">
                {!props.hasSchoolTeam && <OnboardingBanner />}

                {props.hasSchoolTeam && props.role === 'owner' && <AdminDashboard data={props.data} />}
                {props.hasSchoolTeam && props.role === 'admin' && <AdminDashboard data={props.data} />}
                {props.hasSchoolTeam && props.role === 'teacher' && <TeacherDashboard data={props.data} />}
                {props.hasSchoolTeam && props.role === 'student' && <StudentDashboard data={props.data} />}
                {props.hasSchoolTeam && props.role === 'parent' && <ParentDashboard data={props.data} />}
            </div>
        </>
    );
}

Dashboard.layout = (props: { currentTeam?: { slug: string } | null }) => ({
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: props.currentTeam ? dashboard(props.currentTeam.slug) : '/',
        },
    ],
});
```

- [ ] **Step 2: Check types**

```bash
npm run types:check
```

Expected: No errors

- [ ] **Step 3: Check lint**

```bash
npm run lint:check
```

Expected: No errors

- [ ] **Step 4: Check format**

```bash
npm run format:check
```

If it fails, run `npm run format` and re-check.

- [ ] **Step 5: Run full PHP test suite to ensure backend still passes**

```bash
./vendor/bin/pest --compact
```

Expected: All tests pass

- [ ] **Step 6: Commit**

```bash
git add resources/js/pages/dashboard.tsx
git commit -m "feat: wire up role-based dashboard page with typed components"
```
