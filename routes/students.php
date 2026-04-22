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

        // Static routes BEFORE {user} wildcard
        Route::get('students/create', [StudentController::class, 'create'])->name('create');
        Route::post('students', [StudentController::class, 'store'])->name('store');
        Route::get('students/import', [StudentImportController::class, 'create'])->name('import');
        Route::post('students/import', [StudentImportController::class, 'store'])->name('import.store');
        Route::get('students/import/template', [StudentImportController::class, 'template'])->name('import.template');

        // Wildcard after static routes
        Route::get('students/{user}/edit', [StudentController::class, 'edit'])->name('edit');
        Route::patch('students/{user}', [StudentController::class, 'update'])->name('update');
        Route::delete('students/{user}', [StudentController::class, 'destroy'])->name('destroy');
    });
