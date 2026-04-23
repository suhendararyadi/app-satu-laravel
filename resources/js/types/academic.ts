export interface AcademicYear {
    id: number;
    team_id: number;
    name: string;
    start_year: number;
    end_year: number;
    is_active: boolean;
    semesters?: Semester[];
    created_at: string;
    updated_at: string;
}

export interface Semester {
    id: number;
    academic_year_id: number;
    name: string;
    order: number;
    is_active: boolean;
    created_at: string;
    updated_at: string;
}

export interface Grade {
    id: number;
    team_id: number;
    name: string;
    order: number;
    created_at: string;
    updated_at: string;
}

export interface Subject {
    id: number;
    team_id: number;
    name: string;
    code: string | null;
    created_at: string;
    updated_at: string;
}

export interface Classroom {
    id: number;
    team_id: number;
    academic_year_id: number;
    grade_id: number;
    name: string;
    academic_year?: AcademicYear;
    grade?: Grade;
    enrollments?: StudentEnrollment[];
    created_at: string;
    updated_at: string;
}

export interface StudentEnrollment {
    id: number;
    classroom_id: number;
    user_id: number;
    student_number: string | null;
    user?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

export interface TeacherAssignment {
    id: number;
    team_id: number;
    academic_year_id: number;
    subject_id: number;
    classroom_id: number;
    user_id: number;
    academic_year?: AcademicYear;
    subject?: Subject;
    classroom?: Classroom;
    user?: { id: number; name: string; email: string };
    created_at: string;
    updated_at: string;
}

export interface Guardian {
    id: number;
    student_id: number;
    guardian_id: number;
    relationship: 'ayah' | 'ibu' | 'wali';
    student?: { id: number; name: string };
    guardian?: { id: number; name: string };
    created_at: string;
    updated_at: string;
}

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
    created_at: string;
    updated_at: string;
}

export interface SubjectGrade {
    subject_id: number;
    subject_name: string;
    category_scores: Record<number, number>;
    final_grade: number;
}
