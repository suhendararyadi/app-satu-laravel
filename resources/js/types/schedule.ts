// resources/js/types/schedule.ts

export interface TimeSlot {
    id: number;
    team_id: number;
    name: string;
    start_time: string; // "07:00"
    end_time: string; // "07:45"
    sort_order: number;
    created_at: string;
    updated_at: string;
}

export interface Schedule {
    id: number;
    team_id: number;
    semester_id: number;
    classroom_id: number;
    subject_id: number;
    teacher_user_id: number;
    day_of_week: 'Senin' | 'Selasa' | 'Rabu' | 'Kamis' | 'Jumat' | 'Sabtu';
    time_slot_id: number;
    room: string | null;
    created_at: string;
    updated_at: string;
    // relations (optional, loaded when needed)
    semester?: unknown;
    classroom?: unknown;
    subject?: unknown;
    teacher?: unknown;
    time_slot?: unknown;
}

export interface Attendance {
    id: number;
    team_id: number;
    classroom_id: number;
    date: string; // "2026-04-21"
    subject_id: number | null;
    semester_id: number;
    recorded_by: number;
    created_at: string;
    updated_at: string;
    // relations
    classroom?: unknown;
    subject?: unknown;
    semester?: unknown;
    records?: AttendanceRecord[];
}

export interface AttendanceRecord {
    id: number;
    attendance_id: number;
    student_user_id: number;
    status: 'hadir' | 'sakit' | 'izin' | 'alpa';
    notes: string | null;
    created_at: string;
    updated_at: string;
    // relations
    user?: { id: number; name: string };
}

export const DAYS_OF_WEEK = [
    'Senin',
    'Selasa',
    'Rabu',
    'Kamis',
    'Jumat',
    'Sabtu',
] as const;
export type DayOfWeek = (typeof DAYS_OF_WEEK)[number];

export const ATTENDANCE_STATUSES = ['hadir', 'sakit', 'izin', 'alpa'] as const;
export type AttendanceStatus = (typeof ATTENDANCE_STATUSES)[number];
