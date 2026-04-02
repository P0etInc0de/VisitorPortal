<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UserPermissionsController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth'])->group(function () {
    Route::get('/user-permissions', [UserPermissionsController::class, 'index']);
});

require __DIR__.'/auth.php';
