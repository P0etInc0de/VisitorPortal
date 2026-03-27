<?php

Route::middleware(['auth', 'permission:view welcome monitor'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});

Route::middleware(['auth', 'permission:edit welcome monitor'])->group(function () {
    Route::get('/users', [UserController::class, 'index']);
    Route::get('/users/{user}', [UserController::class, 'show']);
});
