<?php

use App\Http\Controllers\Monitor\MonitorController;
use App\Http\Controllers\Monitor\MonitorSlidesController;
use App\Http\Controllers\Portal\DashboardController as PortalDashboardController;
use App\Http\Controllers\Portal\VisitController as PortalVisitController;
use App\Http\Controllers\Reception\DashboardController as ReceptionDashboardController;
use App\Http\Controllers\Reception\VisitController as ReceptionVisitController;
use App\Http\Controllers\Reception\VisitParticipantActionController;
use App\Http\Middleware\CheckMonitorAutoGeneration;
use App\Livewire\Portal\CheckInOutBoard;
use App\Livewire\Portal\VisitShowPage;
use App\Livewire\Profile\UserPermissionsPage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
})->name('home');

Route::get('/lang', function (Request $request) {
    $locale = $request->input('locale');

    if (in_array($locale, ['de', 'en'])) {
        session(['locale' => $locale]);
    }

    return redirect()->to(url()->previous());
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

    Route::prefix('portal')->name('portal.')->group(function () {
        Route::prefix('visits')->name('visits.')->group(function () {
            Route::get('/create', [PortalVisitController::class, 'create'])->name('create');
            Route::post('/', [PortalVisitController::class, 'store'])->name('store');

            Route::get('/{visit}', VisitShowPage::class)
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

        Route::view('/my-visits', 'portal.my-visits')->name('my-visits');
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

    Route::get('monitors/{monitor}/fallback/edit', [MonitorController::class, 'editFallbackPage'])->name('monitors.fallback.edit');
    Route::put('monitors/{monitor}/fallback', [MonitorController::class, 'updateFallbackPage'])->name('monitors.fallback.update');
    Route::resource('monitors', MonitorController::class);
    Route::resource('monitors.slides', MonitorSlidesController::class)
        ->middleware(CheckMonitorAutoGeneration::class);

    Route::get('/user-permissions', UserPermissionsPage::class)->name('user-permissions');
});

require __DIR__.'/auth.php';
