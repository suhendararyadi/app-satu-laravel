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
