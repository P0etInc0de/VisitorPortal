<?php

namespace App\Http\Controllers\Reception;

use App\Enums\VisitStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
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
            'visit' => $visit,
        ])
            ->driver('gotenberg')
            ->paperSize(85.6, 53.98, 'mm')
            ->name(__('Besucherausweis_').$visitor->name.'.pdf')
            ->download();
    }

    public function checkIn(Request $request, Visit $visit, Visitor $visitor): RedirectResponse|JsonResponse
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

        return $this->buildResponse($request, $visit, $visitor);
    }

    public function checkOut(Request $request, Visit $visit, Visitor $visitor): RedirectResponse|JsonResponse
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

        return $this->buildResponse($request, $visit, $visitor);
    }

    private function buildResponse(Request $request, Visit $visit, Visitor $visitor): RedirectResponse|JsonResponse
    {
        if (! $request->ajax() || ! $request->expectsJson()) {
            return back();
        }

        $freshVisit = $this->loadVisitForResponse($visit);
        $mappedParticipant = $this->mapParticipantForResponse($freshVisit, $visitor->id);

        $payload = [
            'participant' => $mappedParticipant,
        ];

        if ($request->header('X-BP-Context') === 'reception-all-visits') {
            $mappedVisit = $this->mapVisitForAllVisits($freshVisit);
            $payload['visit_id'] = $freshVisit->id;
            $payload['entry_html'] = view('reception.partials.all-visits.visit-entry', [
                'visit' => $mappedVisit,
                'index' => 0,
                'weekdayMap' => [1 => 'Mo', 2 => 'Di', 3 => 'Mi', 4 => 'Do', 5 => 'Fr', 6 => 'Sa', 7 => 'So'],
                'detailId' => 'bp-av-details-visit-'.$freshVisit->id,
            ])->render();
        }

        return response()->json($payload);
    }

    private function loadVisitForResponse(Visit $visit): Visit
    {
        return Visit::query()
            ->with([
                'host:id,first_name,name',
                'visitors' => function ($query) {
                    $query->orderBy('first_name')->orderBy('name');
                },
            ])
            ->findOrFail($visit->id);
    }

    private function mapVisitForAllVisits(Visit $visit): array
    {
        $participants = $visit->visitors
            ->map(function (Visitor $visitor) use ($visit) {
                return $this->mapParticipant($visit, $visitor);
            })
            ->values();

        return [
            'id' => $visit->id,
            'title' => $visit->title ?: __('Besuch'),
            'time' => optional($visit->scheduled_from)->format('H:i'),
            'date' => optional($visit->scheduled_from)->format('d.m.'),
            'scheduled_from' => optional($visit->scheduled_from)->format('d.m.Y H:i'),
            'scheduled_until' => optional($visit->scheduled_until)->format('d.m.Y H:i'),
            'host' => trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? '')),
            'host_display' => trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? '')),
            'status' => $this->resolveVisitStatusLabel($visit->status),
            'notes' => $visit->notes,
            'participants' => $participants,
            'participants_count' => $participants->count(),
        ];
    }

    private function mapParticipantForResponse(Visit $visit, int $visitorId): ?array
    {
        $visitor = $visit->visitors->firstWhere('id', $visitorId);

        if (! $visitor) {
            return null;
        }

        return $this->mapParticipant($visit, $visitor);
    }

    private function mapParticipant(Visit $visit, Visitor $visitor): array
    {
        $pivot = $visitor->pivot;
        $status = $this->resolveParticipantStatus($visit->status, $pivot);
        $isCheckedOut = filled($pivot->checked_out_at);

        return [
            'row_key' => 'visit-'.$visit->id.'-visitor-'.$visitor->id,
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'name' => trim($visitor->first_name.' '.$visitor->name),
            'company' => $visitor->company,
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'badge_ready' => filled($pivot->badge_printed_at),
            'can_print_badge' => blank($pivot->badge_printed_at),
            'can_check_in' => blank($pivot->checked_in_at) || $isCheckedOut,
            'check_in_label' => $isCheckedOut ? __('Erneut einchecken') : __('Check-in'),
            'can_check_out' => filled($pivot->checked_in_at) && blank($pivot->checked_out_at),
            'badge_url' => route('reception.participants.badge', [$visit->id, $visitor->id]),
            'check_in_url' => route('reception.participants.check-in', [$visit->id, $visitor->id]),
            'check_out_url' => route('reception.participants.check-out', [$visit->id, $visitor->id]),
        ];
    }

    private function resolveParticipantStatus(string $visitStatus, object $pivot): array
    {
        if (filled($pivot->checked_out_at)) {
            return [
                'label' => __('Ausgecheckt'),
                'class' => 'badge-neutral badge-outline',
            ];
        }

        if (filled($pivot->checked_in_at)) {
            return [
                'label' => __('Eingecheckt'),
                'class' => 'badge-success badge-outline',
            ];
        }

        if (filled($pivot->badge_printed_at)) {
            return [
                'label' => __('Badge bereit'),
                'class' => 'badge-info badge-outline',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return [
                'label' => __('Entwurf'),
                'class' => 'badge-error badge-outline',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Canceled->value) {
            return [
                'label' => __('Abgesagt'),
                'class' => 'badge-neutral',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'badge-warning badge-outline',
        ];
    }

    private function resolveVisitStatusLabel(string $status): string
    {
        return match ($status) {
            VisitStatusEnum::Draft->value => VisitStatusEnum::Draft->label(),
            VisitStatusEnum::Planned->value => VisitStatusEnum::Planned->label(),
            VisitStatusEnum::Completed->value => VisitStatusEnum::Completed->label(),
            VisitStatusEnum::Canceled->value => VisitStatusEnum::Canceled->label(),
            default => __($status),
        };
    }

    private function resolveParticipant(Visit $visit, Visitor $visitor): Visitor
    {
        return $visit->visitors()
            ->where('visitors.id', $visitor->id)
            ->firstOrFail();
    }
}
