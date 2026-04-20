<?php

use App\Http\Controllers\CMS\PageController;
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

        // CMS Pages
        Route::resource('cms/pages', PageController::class)
            ->names([
                'index' => 'pages.index',
                'create' => 'pages.create',
                'store' => 'pages.store',
                'edit' => 'pages.edit',
                'update' => 'pages.update',
                'destroy' => 'pages.destroy',
            ])
            ->except(['show']);
    });
