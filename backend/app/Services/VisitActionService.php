<?php

namespace App\Services;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;

class VisitActionService
{
    public function checkInParticipant(Visit $visit, Visitor $visitor, User $actionBy): Visitor
    {
        $participant = $this->resolveParticipant($visit, $visitor);
        $pivot = $participant->pivot;

        if (blank($pivot->checked_in_at) || filled($pivot->checked_out_at)) {
            $payload = [
                'checked_in_at' => now(),
                'checked_in_by_user_id' => $actionBy->id,
                'updated_at' => now(),
            ];

            if (filled($pivot->checked_out_at)) {
                $payload['checked_out_at'] = null;
                $payload['checked_out_by_user_id'] = null;
            }

            $visit->visitors()->updateExistingPivot($visitor->id, $payload);
        }

        return $this->resolveParticipant($visit, $visitor);
    }

    public function checkOutParticipant(Visit $visit, Visitor $visitor, User $actionBy): Visitor
    {
        $participant = $this->resolveParticipant($visit, $visitor);

        if (filled($participant->pivot->checked_in_at) && blank($participant->pivot->checked_out_at)) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_out_at' => now(),
                'checked_out_by_user_id' => $actionBy->id,
                'updated_at' => now(),
            ]);
        }

        return $this->resolveParticipant($visit, $visitor);
    }

    public function printBadge(Visit $visit, Visitor $visitor): Visitor
    {
        $participant = $this->resolveParticipant($visit, $visitor);

        if (blank($participant->pivot->badge_printed_at)) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'badge_printed_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return $this->resolveParticipant($visit, $visitor);
    }

    public function cancelCheckIn(Visit $visit, Visitor $visitor): Visitor
    {
        $participant = $this->resolveParticipant($visit, $visitor);

        if (filled($participant->pivot->checked_in_at) || filled($participant->pivot->checked_out_at)) {
            $visit->visitors()->updateExistingPivot($visitor->id, [
                'checked_in_at' => null,
                'checked_in_by_user_id' => null,
                'checked_out_at' => null,
                'checked_out_by_user_id' => null,
                'updated_at' => now(),
            ]);
        }

        return $this->resolveParticipant($visit, $visitor);
    }

    private function resolveParticipant(Visit $visit, Visitor $visitor): Visitor
    {
        return $visit->visitors()
            ->where('visitors.id', $visitor->id)
            ->firstOrFail();
    }
}
