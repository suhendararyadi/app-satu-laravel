<?php

namespace App\Http\Controllers\Students;

use App\Exports\StudentTemplateExport;
use App\Http\Controllers\Controller;
use App\Imports\StudentImport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentImportController extends Controller
{
    public function create(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        return Inertia::render('students/import/create', [
            'classrooms' => $team->classrooms()->select(['id', 'name'])->orderBy('name')->get(),
            'import_result' => session('import_result'),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $team = $request->user()->currentTeam;

        $request->validate([
            'file' => ['required', 'file', 'mimes:xlsx,xls,csv', 'max:5120'],
            'classroom_id' => [
                'nullable',
                'integer',
                function (string $attribute, mixed $value, \Closure $fail) use ($team) {
                    if ($value !== null && ! $team->classrooms()->where('id', $value)->exists()) {
                        $fail('Kelas tidak valid.');
                    }
                },
            ],
        ]);
        $classroomId = $request->integer('classroom_id') ?: null;

        $import = new StudentImport($team, $classroomId);
        $import->importFromFile($request->file('file')->getRealPath());
        $result = $import->getResult();

        return redirect()
            ->route('students.import')
            ->with('import_result', $result);
    }

    public function template(): BinaryFileResponse
    {
        return (new StudentTemplateExport)->download();
    }
}
