<?php

use App\Http\Controllers\School\SchoolProfileController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('cms.')
    ->group(function () {
        // School Profile
        Route::get('school/profile', [SchoolProfileController::class, 'edit'])->name('school.profile.edit');
        Route::patch('school/profile', [SchoolProfileController::class, 'update'])->name('school.profile.update');
    });
