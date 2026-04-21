<?php

use App\Http\Controllers\Schedule\AttendanceController;
use App\Http\Controllers\Schedule\ScheduleController;
use App\Http\Controllers\Schedule\TimeSlotController;
use App\Http\Middleware\EnsureTeamMembership;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified', EnsureTeamMembership::class.':admin'])
    ->prefix('/{current_team}')
    ->name('schedule.')
    ->group(function () {
        // Jam Pelajaran
        Route::resource('schedule/time-slots', TimeSlotController::class)
            ->except(['show'])
            ->parameters(['time-slots' => 'timeSlot']);

        // Jadwal
        Route::resource('schedule/schedules', ScheduleController::class)
            ->except(['show']);

        // Absensi
        Route::get('attendance', [AttendanceController::class, 'index'])
            ->name('attendance.index');
        Route::get('attendance/create', [AttendanceController::class, 'create'])
            ->name('attendance.create');
        Route::post('attendance', [AttendanceController::class, 'store'])
            ->name('attendance.store');
        Route::get('attendance/{attendance}', [AttendanceController::class, 'show'])
            ->name('attendance.show');
        Route::get('attendance/{attendance}/edit', [AttendanceController::class, 'edit'])
            ->name('attendance.edit');
        Route::patch('attendance/{attendance}', [AttendanceController::class, 'update'])
            ->name('attendance.update');
    });
