<?php

namespace Database\Seeders;

use App\Enums\AttendanceStatus;
use App\Enums\GuardianRelationship;
use App\Enums\SchoolType;
use App\Enums\TeamRole;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Academic\Guardian;
use App\Models\Academic\Semester;
use App\Models\Academic\StudentEnrollment;
use App\Models\Academic\Subject;
use App\Models\Academic\TeacherAssignment;
use App\Models\Gallery;
use App\Models\GalleryImage;
use App\Models\Page;
use App\Models\Post;
use App\Models\Schedule\Attendance;
use App\Models\Schedule\AttendanceRecord;
use App\Models\Schedule\Schedule;
use App\Models\Schedule\TimeSlot;
use App\Models\Team;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoSeeder extends Seeder
{
    private Team $team;

    public function run(): void
    {
        $this->team = Team::where('slug', 'sman1-jakarta')->firstOrFail();

        $this->updateTeamProfile();

        [$academicYear, $semester] = $this->seedAcademicStructure();
        $grades = $this->seedGrades();
        $subjects = $this->seedSubjects();
        $teachers = $this->seedTeachers();
        $students = $this->seedStudents();
        $parents = $this->seedParents($students);
        $classrooms = $this->seedClassrooms($grades, $academicYear);
        $this->seedEnrollments($classrooms, $students);
        $this->seedTeacherAssignments($classrooms, $teachers, $subjects, $academicYear);
        $timeSlots = $this->seedTimeSlots();
        $this->seedSchedules($classrooms, $teachers, $subjects, $semester, $timeSlots);
        $this->seedAttendances($classrooms, $students, $teachers, $semester);
        $this->seedPosts($teachers);
        $this->seedPages();
        $this->seedGalleries();
    }

    // -------------------------------------------------------------------------
    // Team Profile
    // -------------------------------------------------------------------------

    private function updateTeamProfile(): void
    {
        $this->team->update([
            'npsn' => '20104801',
            'school_type' => SchoolType::Sma,
            'address' => 'Jl. Budi Utomo No. 7, Gambir',
            'city' => 'Jakarta Pusat',
            'province' => 'DKI Jakarta',
            'postal_code' => '10710',
            'phone' => '(021) 3456789',
            'email' => 'info@sman1jakarta.sch.id',
            'accreditation' => 'A',
            'principal_name' => 'Dr. Hj. Yanti Suhartiningsih, M.Pd.',
            'founded_year' => 1967,
            'vision' => 'Menjadi sekolah unggul yang menghasilkan lulusan beriman, berilmu, berkarakter, dan berdaya saing global.',
            'mission' => implode("\n", [
                '1. Melaksanakan pembelajaran berkualitas yang berpusat pada siswa.',
                '2. Membentuk karakter siswa yang berintegritas, mandiri, dan berbudaya.',
                '3. Mengembangkan prestasi akademik dan non-akademik siswa secara optimal.',
                '4. Menjalin kerjasama yang erat dengan orang tua, masyarakat, dan dunia industri.',
                '5. Mewujudkan lingkungan sekolah yang aman, bersih, dan kondusif.',
            ]),
            'description' => 'SMA Negeri 1 Jakarta adalah salah satu sekolah menengah atas terkemuka di ibu kota yang telah berdiri sejak tahun 1967. Dengan tradisi panjang dalam mencetak lulusan berprestasi, sekolah ini terus berkomitmen menghadirkan pendidikan berkualitas tinggi.',
            'website_theme' => 'default',
        ]);
    }

    // -------------------------------------------------------------------------
    // Academic Structure
    // -------------------------------------------------------------------------

    /**
     * @return array{AcademicYear, Semester}
     */
    private function seedAcademicStructure(): array
    {
        $academicYear = AcademicYear::create([
            'team_id' => $this->team->id,
            'name' => '2024/2025',
            'start_year' => 2024,
            'end_year' => 2025,
            'is_active' => true,
        ]);

        $semester = Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester 1',
            'order' => 1,
            'is_active' => true,
        ]);

        Semester::create([
            'academic_year_id' => $academicYear->id,
            'name' => 'Semester 2',
            'order' => 2,
            'is_active' => false,
        ]);

        return [$academicYear, $semester];
    }

    /**
     * @return Collection<int, Grade>
     */
    private function seedGrades(): Collection
    {
        $gradeData = [
            ['name' => 'X', 'order' => 1],
            ['name' => 'XI', 'order' => 2],
            ['name' => 'XII', 'order' => 3],
        ];

        return collect($gradeData)->map(fn (array $data) => Grade::create([
            'team_id' => $this->team->id,
            'name' => $data['name'],
            'order' => $data['order'],
        ]));
    }

    /**
     * @return Collection<string, Subject>
     */
    private function seedSubjects(): Collection
    {
        $subjectData = [
            ['name' => 'Matematika', 'code' => 'MAT'],
            ['name' => 'Fisika', 'code' => 'FIS'],
            ['name' => 'Kimia', 'code' => 'KIM'],
            ['name' => 'Biologi', 'code' => 'BIO'],
            ['name' => 'Bahasa Indonesia', 'code' => 'BIND'],
            ['name' => 'Bahasa Inggris', 'code' => 'BING'],
            ['name' => 'Sejarah Indonesia', 'code' => 'SEJAR'],
            ['name' => 'Geografi', 'code' => 'GEO'],
            ['name' => 'Ekonomi', 'code' => 'EKO'],
            ['name' => 'Sosiologi', 'code' => 'SOS'],
            ['name' => 'Pendidikan Agama Islam', 'code' => 'PAI'],
            ['name' => 'PPKn', 'code' => 'PPKN'],
            ['name' => 'Pendidikan Jasmani', 'code' => 'PJOK'],
            ['name' => 'Seni Budaya', 'code' => 'SENBUD'],
            ['name' => 'Informatika', 'code' => 'INF'],
        ];

        return collect($subjectData)->mapWithKeys(fn (array $data) => [
            $data['code'] => Subject::create([
                'team_id' => $this->team->id,
                'name' => $data['name'],
                'code' => $data['code'],
            ]),
        ]);
    }

    // -------------------------------------------------------------------------
    // Users
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, User>
     */
    private function seedTeachers(): Collection
    {
        $teacherData = [
            ['name' => 'Budi Santoso', 'email' => 'budi.santoso@demo.test'],
            ['name' => 'Sri Wahyuni', 'email' => 'sri.wahyuni@demo.test'],
            ['name' => 'Ahmad Fauzi', 'email' => 'ahmad.fauzi@demo.test'],
            ['name' => 'Dewi Rahayu', 'email' => 'dewi.rahayu@demo.test'],
            ['name' => 'Siti Aminah', 'email' => 'siti.aminah@demo.test'],
            ['name' => 'Hendra Wijaya', 'email' => 'hendra.wijaya@demo.test'],
            ['name' => 'Rina Kusuma', 'email' => 'rina.kusuma@demo.test'],
            ['name' => 'Bambang Eko', 'email' => 'bambang.eko@demo.test'],
            ['name' => 'Nurul Hidayah', 'email' => 'nurul.hidayah@demo.test'],
            ['name' => 'Agus Prasetyo', 'email' => 'agus.prasetyo@demo.test'],
        ];

        return collect($teacherData)->map(function (array $data) {
            $teacher = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);

            $this->team->members()->attach($teacher->id, [
                'role' => TeamRole::Teacher->value,
            ]);

            return $teacher;
        });
    }

    /**
     * @return Collection<int, User>
     */
    private function seedStudents(): Collection
    {
        $studentData = [
            ['name' => 'Alya Putri Sari', 'email' => 'alya.putri@demo.test'],
            ['name' => 'Rizky Aditya', 'email' => 'rizky.aditya@demo.test'],
            ['name' => 'Nadia Permata', 'email' => 'nadia.permata@demo.test'],
            ['name' => 'Farhan Ramadhan', 'email' => 'farhan.ramadhan@demo.test'],
            ['name' => 'Indira Salsabila', 'email' => 'indira.salsabila@demo.test'],
            ['name' => 'Dimas Pratama', 'email' => 'dimas.pratama@demo.test'],
            ['name' => 'Aulia Zahra', 'email' => 'aulia.zahra@demo.test'],
            ['name' => 'Bagas Saputra', 'email' => 'bagas.saputra@demo.test'],
            ['name' => 'Cindy Maharani', 'email' => 'cindy.maharani@demo.test'],
            ['name' => 'Eko Budi Santoso', 'email' => 'eko.budi@demo.test'],
            ['name' => 'Fira Andriani', 'email' => 'fira.andriani@demo.test'],
            ['name' => 'Gilang Kusuma', 'email' => 'gilang.kusuma@demo.test'],
            ['name' => 'Hana Safitri', 'email' => 'hana.safitri@demo.test'],
            ['name' => 'Ilham Nugraha', 'email' => 'ilham.nugraha@demo.test'],
            ['name' => 'Jasmine Putri', 'email' => 'jasmine.putri@demo.test'],
            ['name' => 'Kevin Wijaya', 'email' => 'kevin.wijaya@demo.test'],
            ['name' => 'Lestari Dewi', 'email' => 'lestari.dewi@demo.test'],
            ['name' => 'Muhammad Rafli', 'email' => 'muhammad.rafli@demo.test'],
            ['name' => 'Nindi Aprilia', 'email' => 'nindi.aprilia@demo.test'],
            ['name' => 'Oscar Firmansyah', 'email' => 'oscar.firmansyah@demo.test'],
            ['name' => 'Putri Rahayu', 'email' => 'putri.rahayu@demo.test'],
            ['name' => 'Qori Maulana', 'email' => 'qori.maulana@demo.test'],
            ['name' => 'Reza Kurniawan', 'email' => 'reza.kurniawan@demo.test'],
            ['name' => 'Sari Wulandari', 'email' => 'sari.wulandari@demo.test'],
            ['name' => 'Teguh Prayogo', 'email' => 'teguh.prayogo@demo.test'],
            ['name' => 'Ulfa Rahma', 'email' => 'ulfa.rahma@demo.test'],
            ['name' => 'Vino Ardiansyah', 'email' => 'vino.ardiansyah@demo.test'],
            ['name' => 'Winda Lestari', 'email' => 'winda.lestari@demo.test'],
            ['name' => 'Yoga Permana', 'email' => 'yoga.permana@demo.test'],
            ['name' => 'Zahra Novianti', 'email' => 'zahra.novianti@demo.test'],
        ];

        return collect($studentData)->map(function (array $data, int $index) {
            $student = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);

            $this->team->members()->attach($student->id, [
                'role' => TeamRole::Student->value,
            ]);

            return $student;
        });
    }

    /**
     * @param  Collection<int, User>  $students
     * @return Collection<int, User>
     */
    private function seedParents(Collection $students): Collection
    {
        $parentData = [
            ['name' => 'Hj. Siti Marlina', 'email' => 'siti.marlina@demo.test'],
            ['name' => 'Ir. Susanto Wibowo', 'email' => 'susanto.wibowo@demo.test'],
            ['name' => 'Dra. Mulyani Rahayu', 'email' => 'mulyani.rahayu@demo.test'],
            ['name' => 'H. Andi Kurniawan', 'email' => 'andi.kurniawan@demo.test'],
            ['name' => 'Yuli Handayani', 'email' => 'yuli.handayani@demo.test'],
            ['name' => 'Drs. Bambang Susilo', 'email' => 'bambang.susilo@demo.test'],
            ['name' => 'Ns. Retno Wijayanti', 'email' => 'retno.wijayanti@demo.test'],
            ['name' => 'H. Wahyudi Santoso', 'email' => 'wahyudi.santoso@demo.test'],
            ['name' => 'Endang Prasetyo', 'email' => 'endang.prasetyo@demo.test'],
            ['name' => 'Irma Suryani', 'email' => 'irma.suryani@demo.test'],
            ['name' => 'Dedi Firmansyah', 'email' => 'dedi.firmansyah@demo.test'],
            ['name' => 'Hj. Nuning Widiastuti', 'email' => 'nuning.widiastuti@demo.test'],
            ['name' => 'Agung Pramono', 'email' => 'agung.pramono@demo.test'],
            ['name' => 'Sri Lestari', 'email' => 'sri.lestari@demo.test'],
            ['name' => 'Tono Hariyadi', 'email' => 'tono.hariyadi@demo.test'],
        ];

        $relationships = [
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Ayah,
            GuardianRelationship::Ibu,
            GuardianRelationship::Wali,
        ];

        return collect($parentData)->map(function (array $data, int $index) use ($students, $relationships) {
            $parent = User::factory()->create([
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => Hash::make('password'),
            ]);

            $this->team->members()->attach($parent->id, [
                'role' => TeamRole::Parent->value,
            ]);

            // Link parent to a student (every other student)
            $student = $students->get($index * 2);
            if ($student) {
                Guardian::create([
                    'student_id' => $student->id,
                    'guardian_id' => $parent->id,
                    'relationship' => $relationships[$index]->value,
                ]);
            }

            return $parent;
        });
    }

    // -------------------------------------------------------------------------
    // Classrooms & Enrollments
    // -------------------------------------------------------------------------

    /**
     * @param  Collection<int, Grade>  $grades
     * @return Collection<string, Classroom>
     */
    private function seedClassrooms(Collection $grades, AcademicYear $academicYear): Collection
    {
        $gradeX = $grades->firstWhere('name', 'X');
        $gradeXI = $grades->firstWhere('name', 'XI');
        $gradeXII = $grades->firstWhere('name', 'XII');

        $classroomData = [
            ['name' => 'X-A', 'grade' => $gradeX],
            ['name' => 'X-B', 'grade' => $gradeX],
            ['name' => 'X-C', 'grade' => $gradeX],
            ['name' => 'XI-IPA-1', 'grade' => $gradeXI],
            ['name' => 'XI-IPA-2', 'grade' => $gradeXI],
            ['name' => 'XI-IPS-1', 'grade' => $gradeXI],
            ['name' => 'XII-IPA-1', 'grade' => $gradeXII],
            ['name' => 'XII-IPA-2', 'grade' => $gradeXII],
            ['name' => 'XII-IPS-1', 'grade' => $gradeXII],
        ];

        return collect($classroomData)->mapWithKeys(fn (array $data) => [
            $data['name'] => Classroom::create([
                'team_id' => $this->team->id,
                'academic_year_id' => $academicYear->id,
                'grade_id' => $data['grade']->id,
                'name' => $data['name'],
            ]),
        ]);
    }

    /**
     * @param  Collection<string, Classroom>  $classrooms
     * @param  Collection<int, User>  $students
     */
    private function seedEnrollments(Collection $classrooms, Collection $students): void
    {
        // Distribute 30 students across 9 classrooms
        $distribution = [
            'X-A' => [0, 1, 2, 3],
            'X-B' => [4, 5, 6, 7],
            'X-C' => [8, 9, 10],
            'XI-IPA-1' => [11, 12, 13],
            'XI-IPA-2' => [14, 15, 16],
            'XI-IPS-1' => [17, 18, 19],
            'XII-IPA-1' => [20, 21, 22, 23],
            'XII-IPA-2' => [24, 25, 26],
            'XII-IPS-1' => [27, 28, 29],
        ];

        foreach ($distribution as $classroomName => $studentIndexes) {
            $classroom = $classrooms->get($classroomName);

            foreach ($studentIndexes as $i => $studentIndex) {
                $student = $students->get($studentIndex);
                if (! $student) {
                    continue;
                }

                StudentEnrollment::create([
                    'classroom_id' => $classroom->id,
                    'user_id' => $student->id,
                    'student_number' => sprintf('%05d', ($studentIndex + 1)),
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Teacher Assignments
    // -------------------------------------------------------------------------

    /**
     * @param  Collection<string, Classroom>  $classrooms
     * @param  Collection<int, User>  $teachers
     * @param  Collection<string, Subject>  $subjects
     */
    private function seedTeacherAssignments(
        Collection $classrooms,
        Collection $teachers,
        Collection $subjects,
        AcademicYear $academicYear
    ): void {
        // Map teachers (by index) to subjects (by code)
        $assignments = [
            0 => ['MAT'],           // Budi Santoso → Matematika
            1 => ['FIS'],           // Sri Wahyuni → Fisika
            2 => ['KIM'],           // Ahmad Fauzi → Kimia
            3 => ['BIO'],           // Dewi Rahayu → Biologi
            4 => ['BIND'],          // Siti Aminah → Bahasa Indonesia
            5 => ['BING'],          // Hendra Wijaya → Bahasa Inggris
            6 => ['SEJAR', 'GEO'],  // Rina Kusuma → Sejarah + Geografi
            7 => ['EKO'],           // Bambang Eko → Ekonomi
            8 => ['SOS', 'PPKN'],   // Nurul Hidayah → Sosiologi + PPKn
            9 => ['PJOK', 'SENBUD', 'PAI', 'INF'], // Agus Prasetyo → PJOK + Senbud + PAI + Inf
        ];

        foreach ($assignments as $teacherIndex => $subjectCodes) {
            $teacher = $teachers->get($teacherIndex);

            foreach ($subjectCodes as $subjectCode) {
                $subject = $subjects->get($subjectCode);

                foreach ($classrooms as $classroom) {
                    TeacherAssignment::create([
                        'team_id' => $this->team->id,
                        'academic_year_id' => $academicYear->id,
                        'subject_id' => $subject->id,
                        'classroom_id' => $classroom->id,
                        'user_id' => $teacher->id,
                    ]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Time Slots & Schedules
    // -------------------------------------------------------------------------

    /**
     * @return Collection<int, TimeSlot>
     */
    private function seedTimeSlots(): Collection
    {
        $slotData = [
            ['name' => 'Jam 1', 'start_time' => '07:00', 'end_time' => '07:45', 'sort_order' => 1],
            ['name' => 'Jam 2', 'start_time' => '07:45', 'end_time' => '08:30', 'sort_order' => 2],
            ['name' => 'Jam 3', 'start_time' => '08:30', 'end_time' => '09:15', 'sort_order' => 3],
            ['name' => 'Jam 4', 'start_time' => '09:15', 'end_time' => '10:00', 'sort_order' => 4],
            ['name' => 'Jam 5', 'start_time' => '10:15', 'end_time' => '11:00', 'sort_order' => 5],
            ['name' => 'Jam 6', 'start_time' => '11:00', 'end_time' => '11:45', 'sort_order' => 6],
            ['name' => 'Jam 7', 'start_time' => '11:45', 'end_time' => '12:30', 'sort_order' => 7],
            ['name' => 'Jam 8', 'start_time' => '13:00', 'end_time' => '13:45', 'sort_order' => 8],
        ];

        return collect($slotData)->map(fn (array $data) => TimeSlot::create([
            'team_id' => $this->team->id,
            'name' => $data['name'],
            'start_time' => $data['start_time'],
            'end_time' => $data['end_time'],
            'sort_order' => $data['sort_order'],
        ]));
    }

    /**
     * @param  Collection<string, Classroom>  $classrooms
     * @param  Collection<int, User>  $teachers
     * @param  Collection<string, Subject>  $subjects
     * @param  Collection<int, TimeSlot>  $timeSlots
     */
    private function seedSchedules(
        Collection $classrooms,
        Collection $teachers,
        Collection $subjects,
        Semester $semester,
        Collection $timeSlots
    ): void {
        $days = ['Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat'];

        // Core subjects that every classroom gets scheduled
        $coreSchedule = [
            // [teacherIndex, subjectCode, day, timeSlotIndex, room]
            [0, 'MAT', 'Senin', 0, 'R.101'],
            [0, 'MAT', 'Rabu', 1, 'R.101'],
            [4, 'BIND', 'Senin', 2, 'R.102'],
            [4, 'BIND', 'Kamis', 3, 'R.102'],
            [5, 'BING', 'Selasa', 0, 'R.103'],
            [5, 'BING', 'Jumat', 1, 'R.103'],
        ];

        foreach ($classrooms as $classroom) {
            foreach ($coreSchedule as [$teacherIndex, $subjectCode, $day, $slotIndex, $room]) {
                Schedule::create([
                    'team_id' => $this->team->id,
                    'semester_id' => $semester->id,
                    'classroom_id' => $classroom->id,
                    'subject_id' => $subjects->get($subjectCode)->id,
                    'teacher_user_id' => $teachers->get($teacherIndex)->id,
                    'day_of_week' => $day,
                    'time_slot_id' => $timeSlots->get($slotIndex)->id,
                    'room' => $room,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Attendances
    // -------------------------------------------------------------------------

    /**
     * @param  Collection<string, Classroom>  $classrooms
     * @param  Collection<int, User>  $students
     * @param  Collection<int, User>  $teachers
     */
    private function seedAttendances(
        Collection $classrooms,
        Collection $students,
        Collection $teachers,
        Semester $semester
    ): void {
        // Student distribution mirrors seedEnrollments
        $distribution = [
            'X-A' => [0, 1, 2, 3],
            'X-B' => [4, 5, 6, 7],
            'X-C' => [8, 9, 10],
            'XI-IPA-1' => [11, 12, 13],
            'XI-IPA-2' => [14, 15, 16],
            'XI-IPS-1' => [17, 18, 19],
            'XII-IPA-1' => [20, 21, 22, 23],
            'XII-IPA-2' => [24, 25, 26],
            'XII-IPS-1' => [27, 28, 29],
        ];

        // Last 5 weekdays
        $dates = $this->lastWeekdays(5);

        // Mostly present, occasionally sick or excused
        $statusWeights = [
            AttendanceStatus::Hadir->value => 80,
            AttendanceStatus::Sakit->value => 10,
            AttendanceStatus::Izin->value => 7,
            AttendanceStatus::Alpa->value => 3,
        ];

        foreach ($distribution as $classroomName => $studentIndexes) {
            $classroom = $classrooms->get($classroomName);
            $recorder = $teachers->first();

            foreach ($dates as $date) {
                $attendance = Attendance::create([
                    'team_id' => $this->team->id,
                    'classroom_id' => $classroom->id,
                    'date' => $date,
                    'semester_id' => $semester->id,
                    'subject_id' => null,
                    'recorded_by' => $recorder->id,
                ]);

                foreach ($studentIndexes as $studentIndex) {
                    $student = $students->get($studentIndex);
                    if (! $student) {
                        continue;
                    }

                    AttendanceRecord::create([
                        'attendance_id' => $attendance->id,
                        'student_user_id' => $student->id,
                        'status' => $this->weightedRandom($statusWeights),
                        'notes' => null,
                    ]);
                }
            }
        }
    }

    // -------------------------------------------------------------------------
    // Content
    // -------------------------------------------------------------------------

    /**
     * @param  Collection<int, User>  $teachers
     */
    private function seedPosts(Collection $teachers): void
    {
        $author = $teachers->first();

        $posts = [
            [
                'title' => 'Selamat! 98% Siswa Kelas XII SMAN 1 Jakarta Dinyatakan Lulus',
                'excerpt' => 'Tingkat kelulusan SMAN 1 Jakarta mencapai 98% pada tahun ini, dengan 15 siswa berhasil meraih nilai sempurna.',
                'content' => "Dengan penuh rasa syukur, SMAN 1 Jakarta mengumumkan bahwa sebanyak 98% siswa kelas XII tahun ajaran 2023/2024 dinyatakan lulus ujian nasional.\n\nSebanyak 15 siswa berhasil meraih nilai sempurna di berbagai mata pelajaran. Pencapaian ini merupakan hasil kerja keras seluruh civitas akademika, mulai dari siswa, guru, hingga orang tua.\n\nKepala Sekolah Dr. Hj. Yanti Suhartiningsih, M.Pd. menyampaikan apresiasi setinggi-tingginya kepada seluruh siswa dan tenaga pengajar atas dedikasi yang luar biasa selama proses pembelajaran.",
                'published_at' => Carbon::now()->subDays(3),
            ],
            [
                'title' => 'Tim Olimpiade Matematika Raih Juara II Tingkat Nasional',
                'excerpt' => 'Tiga siswa SMAN 1 Jakarta berhasil meraih medali perak pada Olimpiade Matematika Nasional yang diselenggarakan di Bandung.',
                'content' => "Tim Olimpiade Matematika SMAN 1 Jakarta kembali mengharumkan nama sekolah di tingkat nasional. Tiga siswa terbaik kita, yaitu Alya Putri Sari, Rizky Aditya, dan Farhan Ramadhan, berhasil meraih Juara II pada ajang bergengsi Olimpiade Matematika Nasional.\n\nKompetisi ini diikuti oleh lebih dari 500 peserta dari seluruh penjuru Indonesia. Dengan persiapan intensif selama 3 bulan di bawah bimbingan Bapak Budi Santoso, S.Pd., tim kita mampu menunjukkan kemampuan terbaik.\n\nSelamat kepada para juara! Terus ukir prestasi dan jadilah kebanggaan sekolah.",
                'published_at' => Carbon::now()->subDays(7),
            ],
            [
                'title' => 'Pengumuman PPDB SMAN 1 Jakarta Tahun Ajaran 2025/2026',
                'excerpt' => 'Penerimaan Peserta Didik Baru SMAN 1 Jakarta tahun ajaran 2025/2026 resmi dibuka mulai 1 Juni 2025.',
                'content' => "SMAN 1 Jakarta dengan bangga mengumumkan dibukanya Penerimaan Peserta Didik Baru (PPDB) untuk tahun ajaran 2025/2026.\n\n**Jadwal PPDB:**\n- Pendaftaran Online: 1–14 Juni 2025\n- Verifikasi Berkas: 15–18 Juni 2025\n- Pengumuman Hasil: 20 Juni 2025\n- Daftar Ulang: 21–25 Juni 2025\n\n**Jalur Pendaftaran:**\n1. Jalur Zonasi (50% kuota)\n2. Jalur Afirmasi (15% kuota)\n3. Jalur Perpindahan Orang Tua (5% kuota)\n4. Jalur Prestasi (30% kuota)\n\nInformasi lengkap dapat diakses melalui website resmi sekolah.",
                'published_at' => Carbon::now()->subDays(14),
            ],
            [
                'title' => 'Kegiatan Pramuka Tingkat Wira: Membangun Karakter Generasi Muda',
                'excerpt' => 'SMAN 1 Jakarta menyelenggarakan Kemah Pramuka Tingkat Wira yang diikuti oleh 120 siswa kelas X dan XI.',
                'content' => "Kegiatan Kemah Pramuka Tingkat Wira SMAN 1 Jakarta berlangsung selama tiga hari dua malam di Bumi Perkemahan Cibubur. Kegiatan ini diikuti oleh 120 siswa kelas X dan XI.\n\nRangkaian kegiatan meliputi:\n- Navigasi darat dan pemetaan\n- Pertolongan pertama (P3K)\n- Kegiatan peduli lingkungan\n- Api unggun dan pentas seni\n- Outbound dan permainan tim\n\nMelalui kegiatan ini, diharapkan siswa dapat mengembangkan karakter kepemimpinan, kemandirian, dan rasa cinta tanah air.",
                'published_at' => Carbon::now()->subDays(21),
            ],
            [
                'title' => 'Peringatan Hari Pendidikan Nasional: Refleksi dan Semangat Baru',
                'excerpt' => 'SMAN 1 Jakarta memperingati Hari Pendidikan Nasional dengan upacara bendera dan berbagai kegiatan inspiratif.',
                'content' => "Dalam rangka memperingati Hari Pendidikan Nasional yang jatuh pada tanggal 2 Mei, SMAN 1 Jakarta menyelenggarakan berbagai kegiatan bermakna.\n\nKegiatan diawali dengan upacara bendera yang khidmat, dilanjutkan dengan seminar pendidikan bertema \"Merdeka Belajar untuk Indonesia Maju\". Beberapa alumni sukses turut hadir berbagi inspirasi dan motivasi kepada adik-adik mereka.\n\nMomen Hardiknas ini menjadi pengingat bagi seluruh civitas akademika untuk terus berjuang meningkatkan kualitas pendidikan demi masa depan bangsa yang lebih cerah.",
                'published_at' => Carbon::now()->subDays(30),
            ],
        ];

        foreach ($posts as $postData) {
            Post::create([
                'team_id' => $this->team->id,
                'author_id' => $author->id,
                'title' => $postData['title'],
                'slug' => Str::slug($postData['title']),
                'excerpt' => $postData['excerpt'],
                'content' => $postData['content'],
                'featured_image_path' => null,
                'is_published' => true,
                'published_at' => $postData['published_at'],
                'meta_description' => $postData['excerpt'],
            ]);
        }
    }

    private function seedPages(): void
    {
        $pages = [
            [
                'title' => 'Profil Sekolah',
                'slug' => 'profil-sekolah',
                'content' => "SMA Negeri 1 Jakarta berdiri sejak tahun 1967 dan menjadi salah satu sekolah menengah atas terkemuka di ibu kota Indonesia.\n\nBerlokasi di Jl. Budi Utomo No. 7, Gambir, Jakarta Pusat, sekolah ini telah melahirkan ribuan alumni yang kini berkiprah di berbagai bidang, baik di tingkat nasional maupun internasional.\n\nDengan akreditasi A dari Badan Akreditasi Nasional Sekolah/Madrasah (BAN-S/M), SMAN 1 Jakarta terus berkomitmen memberikan layanan pendidikan terbaik bagi generasi penerus bangsa.",
                'sort_order' => 1,
            ],
            [
                'title' => 'Visi dan Misi',
                'slug' => 'visi-dan-misi',
                'content' => "**VISI**\nMenjadi sekolah unggul yang menghasilkan lulusan beriman, berilmu, berkarakter, dan berdaya saing global.\n\n**MISI**\n1. Melaksanakan pembelajaran berkualitas yang berpusat pada siswa.\n2. Membentuk karakter siswa yang berintegritas, mandiri, dan berbudaya.\n3. Mengembangkan prestasi akademik dan non-akademik siswa secara optimal.\n4. Menjalin kerjasama yang erat dengan orang tua, masyarakat, dan dunia industri.\n5. Mewujudkan lingkungan sekolah yang aman, bersih, dan kondusif.",
                'sort_order' => 2,
            ],
            [
                'title' => 'Sejarah Sekolah',
                'slug' => 'sejarah-sekolah',
                'content' => "**Sejarah Singkat SMAN 1 Jakarta**\n\nSMA Negeri 1 Jakarta didirikan pada tahun 1967 oleh Pemerintah DKI Jakarta sebagai upaya memenuhi kebutuhan pendidikan menengah atas di kawasan pusat kota.\n\nPada masa awal berdirinya, sekolah ini menampung sekitar 200 siswa dengan 12 rombongan belajar. Seiring berjalannya waktu, SMAN 1 Jakarta terus berkembang menjadi salah satu sekolah favorit di Jakarta.\n\nHingga saat ini, SMAN 1 Jakarta telah meluluskan lebih dari 15.000 alumni yang tersebar di berbagai penjuru dunia, dengan prestasi yang membanggakan di berbagai bidang.",
                'sort_order' => 3,
            ],
            [
                'title' => 'Ekstrakurikuler',
                'slug' => 'ekstrakurikuler',
                'content' => "SMAN 1 Jakarta menyediakan berbagai kegiatan ekstrakurikuler untuk mengembangkan bakat dan minat siswa:\n\n**Akademik & Sains**\n- Olimpiade Matematika\n- Olimpiade Fisika\n- Olimpiade Kimia\n- Karya Ilmiah Remaja (KIR)\n\n**Seni & Budaya**\n- Paduan Suara\n- Tari Tradisional\n- Teater\n- Seni Rupa\n\n**Olahraga**\n- Basket\n- Voli\n- Futsal\n- Renang\n- Bulu Tangkis\n\n**Kepemimpinan & Sosial**\n- OSIS\n- Pramuka\n- PMR (Palang Merah Remaja)\n- Paskibra",
                'sort_order' => 4,
            ],
        ];

        foreach ($pages as $pageData) {
            Page::create([
                'team_id' => $this->team->id,
                'title' => $pageData['title'],
                'slug' => $pageData['slug'],
                'content' => $pageData['content'],
                'is_published' => true,
                'sort_order' => $pageData['sort_order'],
                'meta_description' => null,
            ]);
        }
    }

    private function seedGalleries(): void
    {
        $galleries = [
            [
                'title' => 'Wisuda Kelas XII 2023/2024',
                'description' => 'Momen bahagia wisuda dan perpisahan siswa kelas XII tahun ajaran 2023/2024.',
                'images' => [
                    'Prosesi wisuda di aula utama sekolah',
                    'Foto bersama dengan wali kelas',
                    'Penyerahan ijazah oleh kepala sekolah',
                    'Selebrasi kelulusan di halaman sekolah',
                ],
            ],
            [
                'title' => 'Olimpiade Sains Nasional 2024',
                'description' => 'Dokumentasi persiapan dan keikutsertaan tim olimpiade SMAN 1 Jakarta.',
                'images' => [
                    'Tim olimpiade matematika saat latihan',
                    'Kontingen SMAN 1 Jakarta di OSN 2024',
                    'Penyerahan medali juara II olimpiade matematika',
                ],
            ],
            [
                'title' => 'Kegiatan Ekstrakurikuler 2024',
                'description' => 'Berbagai momen kegiatan ekstrakurikuler siswa SMAN 1 Jakarta.',
                'images' => [
                    'Latihan paduan suara menjelang pentas',
                    'Tim basket saat bertanding',
                    'Kegiatan pramuka di bumi perkemahan',
                    'Penampilan tari tradisional saat pentas seni',
                    'Siswa PMR saat simulasi pertolongan pertama',
                ],
            ],
        ];

        foreach ($galleries as $galleryData) {
            $gallery = Gallery::create([
                'team_id' => $this->team->id,
                'title' => $galleryData['title'],
                'description' => $galleryData['description'],
                'is_published' => true,
            ]);

            foreach ($galleryData['images'] as $index => $caption) {
                GalleryImage::create([
                    'gallery_id' => $gallery->id,
                    'image_path' => 'images/demo/gallery-placeholder.jpg',
                    'caption' => $caption,
                    'sort_order' => $index + 1,
                ]);
            }
        }
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /**
     * Returns the last N weekdays as Carbon instances.
     *
     * @return array<int, Carbon>
     */
    private function lastWeekdays(int $count): array
    {
        $dates = [];
        $day = Carbon::today();

        while (count($dates) < $count) {
            $day = $day->copy()->subDay();
            if ($day->isWeekday()) {
                $dates[] = $day->toDateString();
            }
        }

        return $dates;
    }

    /**
     * Selects a random value from a weighted array.
     *
     * @param  array<string, int>  $weights  key => weight
     */
    private function weightedRandom(array $weights): string
    {
        $total = array_sum($weights);
        $rand = random_int(1, $total);
        $cumulative = 0;

        foreach ($weights as $value => $weight) {
            $cumulative += $weight;
            if ($rand <= $cumulative) {
                return $value;
            }
        }

        return array_key_first($weights);
    }
}
