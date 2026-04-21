<?php

use App\Http\Controllers\Students\StudentController;
use App\Http\Controllers\Students\StudentImportController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('students.')
    ->group(function () {
        Route::get('students', [StudentController::class, 'index'])->name('index');

        // Static import routes BEFORE {user} wildcard
        Route::get('students/import', [StudentImportController::class, 'create'])->name('import');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('import.store');
        Route::get('students/import/template', [StudentImportController::class, 'template'])->name('import.template');

        // Wildcard after static routes
        Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');
    });
