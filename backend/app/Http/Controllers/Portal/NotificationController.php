<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function toggleRead(Request $request, string $notification): RedirectResponse
    {
        $notificationRecord = $this->findNotification($request, $notification);

        if (filled($notificationRecord->read_at)) {
            $notificationRecord->forceFill(['read_at' => null])->save();
        } else {
            $notificationRecord->markAsRead();
        }

        return redirect()->route('overview');
    }

    public function destroy(Request $request, string $notification): RedirectResponse
    {
        $this->findNotification($request, $notification)->delete();

        return redirect()->route('overview');
    }

    private function findNotification(Request $request, string $notificationId): DatabaseNotification
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user->notifications()->findOrFail($notificationId);
    }
}
