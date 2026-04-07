<?php

use Illuminate\Support\Facades\Route;
use \App\Http\Controllers\CheckInOutController;
use \App\Http\Controllers\VisitorBadgeController;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'portal.dashboard')->name('dashboard');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::prefix('visits')->name('visits.')->group(function () {
            Route::view('/create', 'portal.visits.create')->name('create');
            Route::view('/{visit}', 'portal.visits.show')
                ->whereNumber('visit')
                ->name('show');
        });
        Route::get('/check_in_out', [CheckInOutController::class, 'index'])->name('check_in_out'); //Routing of check in get using controller
        Route::get('/badge/check_in_out_all', [CheckInOutController::class, 'check_in_out_all'])->name('check_in_out.check_in_out_all');
        Route::post('/check_in_out', [CheckInOutController::class, 'store'])->name('check_in_out.store'); // Routing of check in post with filled form using controller
    });

    Route::prefix('reception')->name('reception.')->group(function () {
        Route::view('/', 'reception.dashboard')->name('dashboard');
        Route::view('/all-visits', 'reception.all-visits')->name('all-visits');
        Route::get('/badge', [VisitorBadgeController::class, 'index'])->name('badge');   //Routing of badge get using controller
        Route::get('/badge/download_all_badges', [VisitorBadgeController::class, 'download_all_badges'])->name('badge.download_all');
        Route::post('/badge', [VisitorBadgeController::class, 'download_badge'])->name('badge.store'); //Routing of badge post with filled form using controller

    });

    Route::prefix('monitor')->name('monitor.')->group(function () {
        Route::view('/', 'monitor.show')->name('show');
        Route::view('/live', 'monitor.show')->name('live');
        Route::view('/editor', 'monitor.editor')->name('editor');
    });
});

require __DIR__.'/auth.php';
