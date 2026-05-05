<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;

class VisitParticipantActionController extends Controller
{
    public function printBadge(Visit $visit, Visitor $visitor, VisitActionService $visitActionService)
    {
		$this->authorize('print', Visitor::class);

        $visit->loadMissing('responsibleUser');
        $participant = $visitActionService->printBadge($visit, $visitor);

        Log::channel('web')->info('Visit participant badge downloaded', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => auth()->id(),
        ]);

        return Pdf::view('pdf.visitor_badge', [
            'visit' => $visit,
            'visitor' => $participant,
        ])
            ->driver('gotenberg')
            ->paperSize(85.6, 53.98, 'mm')
            ->margins(0, 0, 0, 0)
            ->name(__('Besucherausweis_').trim(($participant->first_name ?? '').'_'.($participant->name ?? 'Gast')).'.pdf')
            ->download();
    }

    public function checkIn(Request $request, Visit $visit, Visitor $visitor, VisitActionService $visitActionService): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

		$this->authorize('checkIn', Visitor::class);

        $visitActionService->checkInParticipant($visit, $visitor, $user);

        Log::channel('web')->info('Visit participant checked in', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => $user->id,
        ]);

        return back();
    }

    public function checkOut(Request $request, Visit $visit, Visitor $visitor, VisitActionService $visitActionService): RedirectResponse
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

		$this->authorize('checkOut', Visitor::class);

        $visitActionService->checkOutParticipant($visit, $visitor, $user);

        Log::channel('web')->info('Visit participant checked out', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => $user->id,
        ]);

        return back();
    }
}
