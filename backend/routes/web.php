<?php

use Illuminate\Support\Facades\Route;

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
    });

    Route::prefix('reception')->name('reception.')->group(function () {
        Route::view('/', 'reception.dashboard')->name('dashboard');
        Route::view('/all-visits', 'reception.all-visits')->name('all-visits');
    });

    Route::prefix('monitor')->name('monitor.')->group(function () {
        Route::view('/', 'monitor.show')->name('show');
        Route::view('/live', 'monitor.show')->name('live');
        Route::view('/editor', 'monitor.editor')->name('editor');
    });
});

require __DIR__ . '/auth.php';
