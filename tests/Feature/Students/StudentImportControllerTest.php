<?php

use App\Enums\TeamRole;
use App\Imports\StudentImport;
use App\Models\Academic\AcademicYear;
use App\Models\Academic\Classroom;
use App\Models\Academic\Grade;
use App\Models\Team;
use App\Models\User;
use App\Notifications\Students\WelcomeStudent;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Maatwebsite\Excel\Facades\Excel;

beforeEach(function () {
    $this->withoutVite();
});

function makeImportSetup(): array
{
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);
    $owner->switchTeam($team);

    return [$owner, $team];
}

it('shows the import form page', function () {
    [$owner] = makeImportSetup();

    $this->actingAs($owner)
        ->get(route('students.import'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('students/import/create')
            ->has('classrooms')
        );
});

it('downloads import template', function () {
    [$owner] = makeImportSetup();

    Excel::fake();

    $this->actingAs($owner)
        ->get(route('students.import.template'))
        ->assertOk();

    Excel::assertDownloaded('template-import-siswa.xlsx');
});

it('imports students from uploaded excel', function () {
    [$owner, $team] = makeImportSetup();

    Notification::fake();
    Excel::fake();

    $file = UploadedFile::fake()->create('students.xlsx', 10, 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');

    $this->actingAs($owner)
        ->post(route('students.import.store'), ['file' => $file])
        ->assertRedirect(route('students.import'))
        ->assertSessionHas('import_result');

    Excel::assertImported('students.xlsx');
});

it('import requires a file', function () {
    [$owner] = makeImportSetup();

    $this->actingAs($owner)
        ->post(route('students.import.store'), [])
        ->assertSessionHasErrors('file');
});

it('StudentImport creates user and adds to team', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $import = new StudentImport($team, null);
    $import->collection(collect([
        collect(['nama' => 'Budi', 'email' => 'budi@test.com', 'nis' => '99']),
    ]));

    expect(User::where('email', 'budi@test.com')->exists())->toBeTrue();
    expect($team->members()->where('users.email', 'budi@test.com')->exists())->toBeTrue();
    expect($import->getResult()['imported'])->toBe(1);
    expect($import->getResult()['skipped'])->toBe(0);

    Notification::assertSentTo(
        User::where('email', 'budi@test.com')->first(),
        WelcomeStudent::class,
    );
});

it('StudentImport skips existing email', function () {
    Notification::fake();

    $existing = User::factory()->create(['email' => 'ada@test.com']);
    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $import = new StudentImport($team, null);
    $import->collection(collect([
        collect(['nama' => 'Ada', 'email' => 'ada@test.com', 'nis' => '']),
    ]));

    expect($import->getResult()['skipped'])->toBe(1);
    expect($import->getResult()['imported'])->toBe(0);
    expect($team->members()->where('users.id', $existing->id)->exists())->toBeFalse();
});

it('StudentImport enrolls student when classroom given', function () {
    Notification::fake();

    $owner = User::factory()->create();
    $team = Team::factory()->create();
    $team->members()->attach($owner, ['role' => TeamRole::Owner->value]);

    $year = AcademicYear::factory()->for($team)->create();
    $grade = Grade::factory()->for($team)->create();
    $classroom = Classroom::factory()
        ->for($team)->for($year, 'academicYear')->for($grade, 'grade')->create();

    $import = new StudentImport($team, $classroom->id);
    $import->collection(collect([
        collect(['nama' => 'Cici', 'email' => 'cici@test.com', 'nis' => '007']),
    ]));

    $user = User::where('email', 'cici@test.com')->first();
    expect($user->enrollments()->where('classroom_id', $classroom->id)->exists())->toBeTrue();
});
