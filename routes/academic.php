<?php

use App\Http\Controllers\Academic\AcademicYearController;
use App\Http\Controllers\Academic\AssessmentCategoryController;
use App\Http\Controllers\Academic\AssessmentController;
use App\Http\Controllers\Academic\ClassroomController;
use App\Http\Controllers\Academic\GradeController;
use App\Http\Controllers\Academic\ReportCardController;
use App\Http\Controllers\Academic\SubjectController;
use App\Http\Controllers\Academic\TeacherAssignmentController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('academic.')
    ->group(function () {
        // Academic Years + Semesters
        Route::resource('academic/years', AcademicYearController::class)->except(['show']);
        Route::post('academic/years/{year}/activate', [AcademicYearController::class, 'activate'])
            ->name('years.activate');
        Route::get('academic/years/{year}/semesters/create', [AcademicYearController::class, 'createSemester'])
            ->name('years.semesters.create');
        Route::post('academic/years/{year}/semesters', [AcademicYearController::class, 'storeSemester'])
            ->name('years.semesters.store');
        Route::get('academic/years/{year}/semesters/{semester}/edit', [AcademicYearController::class, 'editSemester'])
            ->name('years.semesters.edit');
        Route::patch('academic/years/{year}/semesters/{semester}', [AcademicYearController::class, 'updateSemester'])
            ->name('years.semesters.update');
        Route::delete('academic/years/{year}/semesters/{semester}', [AcademicYearController::class, 'destroySemester'])
            ->name('years.semesters.destroy');

        // Grades
        Route::resource('academic/grades', GradeController::class)->except(['show']);

        // Subjects
        Route::resource('academic/subjects', SubjectController::class)->except(['show']);

        // Classrooms + Enrollment
        Route::resource('academic/classrooms', ClassroomController::class);
        Route::post('academic/classrooms/{classroom}/enroll', [ClassroomController::class, 'enrollStudent'])
            ->name('classrooms.enroll');
        Route::delete('academic/classrooms/{classroom}/enroll/{enrollment}', [ClassroomController::class, 'unenrollStudent'])
            ->name('classrooms.unenroll');

        // Teacher Assignments
        Route::get('academic/assignments', [TeacherAssignmentController::class, 'index'])
            ->name('assignments.index');
        Route::post('academic/assignments', [TeacherAssignmentController::class, 'store'])
            ->name('assignments.store');
        Route::delete('academic/assignments/{assignment}', [TeacherAssignmentController::class, 'destroy'])
            ->name('assignments.destroy');

        // Assessment Categories
        Route::resource('academic/assessment-categories', AssessmentCategoryController::class)
            ->parameters(['assessment-categories' => 'assessmentCategory'])
            ->except(['show']);
    });

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':teacher'])
    ->prefix('/{current_team}')
    ->name('academic.')
    ->group(function () {
        // Assessments
        Route::resource('academic/assessments', AssessmentController::class);
        Route::post('academic/assessments/{assessment}/scores', [AssessmentController::class, 'storeScores'])
            ->name('assessments.scores.store');

        // Report Cards
        Route::resource('academic/report-cards', ReportCardController::class)
            ->parameters(['report-cards' => 'reportCard'])
            ->only(['index', 'show', 'store', 'update']);
    });
