<?php

namespace App\Imports;

use App\Enums\TeamRole;
use App\Models\Academic\StudentEnrollment;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Students\WelcomeStudent;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class StudentImport
{
    /** @var array{imported: int, skipped: int, errors: string[]} */
    private array $result = ['imported' => 0, 'skipped' => 0, 'errors' => []];

    public function __construct(
        private readonly Team $team,
        private readonly ?int $classroomId = null,
    ) {}

    public function importFromFile(string $filePath): void
    {
        $spreadsheet = IOFactory::load($filePath);
        $rows = $spreadsheet->getActiveSheet()->toArray(null, true, true, false);

        if (count($rows) < 2) {
            return;
        }

        /** @var array<int, string|null> $rawHeaders */
        $rawHeaders = $rows[0];
        $headers = array_map(fn ($h) => mb_strtolower((string) ($h ?? '')), $rawHeaders);

        $data = collect(array_slice($rows, 1))
            ->map(fn ($row) => collect(array_combine($headers, $row)));

        $this->collection($data);
    }

    /**
     * @param  Collection<int, Collection<string, mixed>>  $rows
     */
    public function collection(Collection $rows): void
    {
        foreach ($rows as $index => $row) {
            $email = trim((string) ($row['email'] ?? ''));
            $name = trim((string) ($row['nama'] ?? ''));
            $nis = trim((string) ($row['nis'] ?? '')) ?: null;

            if ($email === '') {
                $this->result['errors'][] = 'Baris '.($index + 2).': kolom Email kosong, dilewati.';

                continue;
            }

            if (User::where('email', $email)->exists()) {
                $this->result['skipped']++;

                continue;
            }

            $temporaryPassword = Str::random(12);

            $user = DB::transaction(function () use ($email, $name, $temporaryPassword, $nis) {
                $user = User::create([
                    'name' => $name ?: $email,
                    'email' => $email,
                    'password' => $temporaryPassword,
                    'email_verified_at' => now(),
                ]);

                $this->team->members()->attach($user->id, ['role' => TeamRole::Student->value]);

                if ($this->classroomId !== null) {
                    StudentEnrollment::create([
                        'classroom_id' => $this->classroomId,
                        'user_id' => $user->id,
                        'student_number' => $nis,
                    ]);
                }

                return $user;
            });

            Notification::send($user, new WelcomeStudent($this->team, $temporaryPassword));

            $this->result['imported']++;
        }
    }

    /**
     * @return array{imported: int, skipped: int, errors: string[]}
     */
    public function getResult(): array
    {
        return $this->result;
    }
}
