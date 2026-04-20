<?php

use App\Http\Controllers\CMS\GalleryController;
use App\Http\Controllers\CMS\PageController;
use App\Http\Controllers\CMS\PostController;
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

        // CMS Posts
        Route::resource('cms/posts', PostController::class)
            ->names([
                'index' => 'posts.index',
                'create' => 'posts.create',
                'store' => 'posts.store',
                'edit' => 'posts.edit',
                'update' => 'posts.update',
                'destroy' => 'posts.destroy',
            ])
            ->except(['show']);

        // CMS Galleries
        Route::resource('cms/galleries', GalleryController::class)
            ->names([
                'index' => 'galleries.index',
                'create' => 'galleries.create',
                'store' => 'galleries.store',
                'edit' => 'galleries.edit',
                'update' => 'galleries.update',
                'destroy' => 'galleries.destroy',
            ])
            ->except(['show']);
        Route::post('cms/galleries/{gallery}/images', [GalleryController::class, 'storeImage'])->name('galleries.images.store');
        Route::delete('cms/galleries/{gallery}/images/{image}', [GalleryController::class, 'destroyImage'])->name('galleries.images.destroy');
    });
