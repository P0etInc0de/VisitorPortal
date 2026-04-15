<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;
use Spatie\LaravelPdf\Facades\Pdf;

class VisitParticipantActionController extends Controller
{
    public function printBadge(Visit $visit, Visitor $visitor)
    {
        $participant = $this->resolveParticipant($visit, $visitor);

        if (blank($participant->pivot->badge_printed_at)) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'badge_printed_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Technical log for the current implementation. Participant actions may later
        // also be written to a dedicated activity log.
        Log::channel('web')->info('Visit participant badge downloaded', [
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'user_id' => auth()->id(),
        ]);

        return Pdf::view('pdf.visitor_badge', [
            'visitor' => $visitor,
        ])
            ->driver('gotenberg')
            ->format('A4')
            ->landscape()
            ->name(__('Besucherausweis_').$visitor->last_name.'.pdf')
            ->download();
    }

    public function checkIn(Visit $visit, Visitor $visitor): RedirectResponse
    {
        $participant = $this->resolveParticipant($visit, $visitor);
        $pivot = $participant->pivot;

        if (blank($pivot->checked_in_at) || filled($pivot->checked_out_at)) {
            $payload = [
                'checked_in_at' => now(),
                'checked_in_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ];

            if (filled($pivot->checked_out_at)) {
                $payload['checked_out_at'] = null;
                $payload['checked_out_by_user_id'] = null;
            }

            $visit->visitors()->updateExistingPivot($visitor->id, $payload);

            Log::channel('web')->info(
                filled($pivot->checked_out_at) ? 'Visit participant checked in again' : 'Visit participant checked in',
                [
                    'visit_id' => $visit->id,
                    'visitor_id' => $visitor->id,
                    'user_id' => auth()->id(),
                ]
            );
        }

        return back();
    }

    public function checkOut(Visit $visit, Visitor $visitor): RedirectResponse
    {
        $participant = $this->resolveParticipant($visit, $visitor);

        if (filled($participant->pivot->checked_in_at) && blank($participant->pivot->checked_out_at)) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_out_at' => now(),
                'checked_out_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ]);

            Log::channel('web')->info('Visit participant checked out', [
                'visit_id' => $visit->id,
                'visitor_id' => $visitor->id,
                'user_id' => auth()->id(),
            ]);
        }

        return back();
    }

    private function resolveParticipant(Visit $visit, Visitor $visitor): Visitor
    {
        return $visit->visitors()
            ->where('visitors.id', $visitor->id)
            ->firstOrFail();
    }
}
