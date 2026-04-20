<?php

use App\Http\Controllers\Public\PublicSchoolController;
use Illuminate\Support\Facades\Route;

Route::prefix('/schools/{team:slug}')
    ->name('public.school.')
    ->group(function () {
        Route::get('/', [PublicSchoolController::class, 'home'])->name('home');
        Route::get('/pages/{page:slug}', [PublicSchoolController::class, 'page'])->name('page');
        Route::get('/news', [PublicSchoolController::class, 'news'])->name('news');
        Route::get('/news/{post:slug}', [PublicSchoolController::class, 'post'])->name('post');
        Route::get('/gallery', [PublicSchoolController::class, 'gallery'])->name('gallery');
        Route::get('/gallery/{gallery}', [PublicSchoolController::class, 'galleryDetail'])->name('gallery.detail');
        Route::get('/contact', [PublicSchoolController::class, 'contact'])->name('contact');
    });
