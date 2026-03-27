<?php

// adjust ressources and methods!

Route::middleware(['auth', 'permission:view visitors'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});

Route::middleware(['auth', 'permission:edit visitors'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});

Route::middleware(['auth', 'permission:delete visitors'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});
