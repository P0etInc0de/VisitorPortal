<?php

use App\Http\Controllers\Monitor\MonitorController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\VisitController as PortalVisitController;
use App\Http\Controllers\Reception\DashboardController as ReceptionDashboardController;
use App\Http\Controllers\Reception\VisitController as ReceptionVisitController;
use App\Http\Controllers\Reception\VisitParticipantActionController;
use App\Http\Controllers\UserPermissionsController;
use App\Livewire\Portal\CheckInOutBoard;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::prefix('visits')->name('visits.')->group(function () {
            Route::get('/create', [PortalVisitController::class, 'create'])->name('create');
            Route::post('/', [PortalVisitController::class, 'store'])->name('store');
            Route::get('/{visit}', [PortalVisitController::class, 'show'])
                ->whereNumber('visit')
                ->name('show');
            Route::get('/{visit}/edit', [PortalVisitController::class, 'edit'])
                ->whereNumber('visit')
                ->name('edit');
            Route::match(['put', 'patch'], '/{visit}', [PortalVisitController::class, 'update'])
                ->whereNumber('visit')
                ->name('update');
            Route::post('/{visit}/cancel', [PortalVisitController::class, 'cancel'])
                ->whereNumber('visit')
                ->name('cancel');
            Route::post('/{visit}/reopen', [PortalVisitController::class, 'reopen'])
                ->whereNumber('visit')
                ->name('reopen');
            Route::patch('/{visit}/reschedule', [PortalVisitController::class, 'reschedule'])
                ->whereNumber('visit')
                ->name('reschedule');
        });

        Route::redirect('/check_in_out', '/portal/check-in-out');
        Route::get('/check-in-out', CheckInOutBoard::class)->name('check_in_out');
    });

    Route::prefix('reception')->name('reception.')->group(function () {
        Route::get('/', [ReceptionDashboardController::class, 'index'])->name('dashboard');
        Route::get('/all-visits', [ReceptionVisitController::class, 'index'])->name('all-visits');

        Route::post('/visits/{visit}/participants/{visitor}/badge', [VisitParticipantActionController::class, 'printBadge'])
            ->whereNumber('visit')
            ->whereNumber('visitor')
            ->name('participants.badge');

        Route::post('/visits/{visit}/participants/{visitor}/check-in', [VisitParticipantActionController::class, 'checkIn'])
            ->whereNumber('visit')
            ->whereNumber('visitor')
            ->name('participants.check-in');

        Route::post('/visits/{visit}/participants/{visitor}/check-out', [VisitParticipantActionController::class, 'checkOut'])
            ->whereNumber('visit')
            ->whereNumber('visitor')
            ->name('participants.check-out');
    });

    Route::prefix('monitor')->name('monitor.')->group(function () {
        Route::redirect('/', '/monitor/live');
        Route::get('/live', [MonitorController::class, 'show'])->name('live');
        Route::get('/editor', [MonitorController::class, 'edit'])->name('editor');
    });

    Route::get('/user-permissions', [UserPermissionsController::class, 'index'])->name('user-permissions');
});

require __DIR__.'/auth.php';
