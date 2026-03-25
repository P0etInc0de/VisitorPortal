<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('public.home');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::view('/dashboard', 'portal.dashboard')->name('dashboard');

    Route::view('/portal/visits/create', 'portal.visits.create')->name('portal.visits.create');
    Route::view('/portal/visits/1', 'portal.visits.show')->name('portal.visits.show');

    Route::view('/reception', 'reception.dashboard')->name('reception.dashboard');

    Route::view('/monitor/editor', 'monitor.editor')->name('monitor.editor');
    Route::view('/monitor/live', 'monitor.show')->name('monitor.show');
});

require __DIR__.'/auth.php';
