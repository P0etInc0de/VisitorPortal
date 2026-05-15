<?php

namespace App\Livewire\Reception;

use App\Enums\VisitStatusEnum;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use Illuminate\Contracts\View\View;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class DashboardPage extends Component
{
    use AuthorizesRequests;

    public array $expandedParticipantVisitIds = [];

    public function toggleVisitParticipants(int $visitId): void
    {
        $expandedVisitIds = array_map('intval', $this->expandedParticipantVisitIds);

        if (in_array($visitId, $expandedVisitIds, true)) {
            $this->expandedParticipantVisitIds = array_values(array_diff($expandedVisitIds, [$visitId]));

            return;
        }

        $expandedVisitIds[] = $visitId;
        $this->expandedParticipantVisitIds = array_values(array_unique($expandedVisitIds));
    }

    public function checkIn(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('checkIn', Visitor::class);

        $visit = $this->loadVisitParticipantPair($visitId, $visitorId);
        $participant = $visit->visitors->firstWhere('id', $visitorId);
        $user = auth()->user();

        if (! $participant || ! $user instanceof User) {
            return;
        }

        app(VisitActionService::class)->checkInParticipant($visit, $participant, $user);

        Log::channel('web')->info('Visit participant checked in from dashboard', [
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'user_id' => $user->id,
        ]);
    }

    public function checkOut(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('checkOut', Visitor::class);

        $visit = $this->loadVisitParticipantPair($visitId, $visitorId);
        $participant = $visit->visitors->firstWhere('id', $visitorId);
        $user = auth()->user();

        if (! $participant || ! $user instanceof User) {
            return;
        }

        app(VisitActionService::class)->checkOutParticipant($visit, $participant, $user);

        Log::channel('web')->info('Visit participant checked out from dashboard', [
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'user_id' => $user->id,
        ]);
    }

    public function printBadge(int $visitId, int $visitorId): void
    {
        $this->authorize('viewAny', Visit::class);
        $this->authorize('print', Visitor::class);

        $visit = $this->loadVisitParticipantPair($visitId, $visitorId);
        $participant = $visit->visitors->firstWhere('id', $visitorId);

        if (! $participant) {
            return;
        }

        app(VisitActionService::class)->printBadge($visit, $participant);

        Log::channel('web')->info('Visit participant badge marked as printed from dashboard', [
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'user_id' => auth()->id(),
        ]);
    }

    public function render(): View
    {
        $this->authorize('viewAny', Visit::class);

        $todayVisits = $this->todayVisits();

        return view('livewire.reception.dashboard-page', [
            'stats' => $this->stats($todayVisits),
            'visits' => $this->visits($todayVisits),
        ]);
    }

    private function todayVisits()
    {
        return Visit::query()
            ->with([
                'host:id,name',
                'visitors' => function ($query) {
                    $query->orderBy('first_name')->orderBy('name');
                },
            ])
            ->whereBetween('scheduled_from', [now()->startOfDay(), now()->endOfDay()])
            ->orderBy('scheduled_from')
            ->get();
    }

    private function stats($todayVisits): array
    {
        $arrivalsToday = $todayVisits->sum(fn (Visit $visit) => $visit->visitors->count());
        $currentlyInHouse = $todayVisits->sum(function (Visit $visit) {
            return $visit->visitors->filter(function (Visitor $visitor) {
                return filled($visitor->pivot->checked_in_at) && blank($visitor->pivot->checked_out_at);
            })->count();
        });
        $openClarifications = $todayVisits->sum(function (Visit $visit) {
            return $visit->visitors->filter(function (Visitor $visitor) use ($visit) {
                return blank($visitor->pivot->badge_printed_at) || $visit->status === VisitStatusEnum::Draft->value;
            })->count();
        });

        return [
            [
                'label' => __('Ankünfte heute'),
                'value' => (string) $arrivalsToday,
                'meta' => __('Teilnehmende über alle heutigen Termine'),
            ],
            [
                'label' => __('Aktuell im Haus'),
                'value' => (string) $currentlyInHouse,
                'meta' => __('Personen mit Check-in ohne Check-out'),
            ],
            [
                'label' => __('Offene Klärungen'),
                'value' => (string) $openClarifications,
                'meta' => __('Ausweise, Entwürfe oder offene Vorbereitung'),
            ],
        ];
    }

    private function visits($todayVisits)
    {
        $expandedVisitIds = array_map('intval', $this->expandedParticipantVisitIds);

        return $todayVisits->map(function (Visit $visit) use ($expandedVisitIds) {
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
                'is_recurring' => filled($visit->recurring_visit_series_id),
                'recurrence_is_modified' => (bool) $visit->recurrence_is_modified,
                'host' => $visit->host?->name ?: '-',
                'notes' => $visit->notes,
                'visible_participants' => $participants->take(3)->values(),
                'hidden_participants' => $participants->slice(3)->values(),
                'hidden_count' => max(0, $participants->count() - 3),
                'participants_expanded' => in_array((int) $visit->id, $expandedVisitIds, true),
            ];
        });
    }

    private function mapParticipant(Visit $visit, Visitor $visitor): array
    {
        $pivot = $visitor->pivot;
        $status = $this->resolveParticipantStatus($visit->status, $pivot);
        $isCheckedOut = filled($pivot->checked_out_at);

        return [
            'row_key' => 'visit-'.$visit->id.'-visitor-'.$visitor->id,
            'visitor_id' => $visitor->id,
            'name' => trim($visitor->first_name.' '.$visitor->name),
            'company' => $visitor->company,
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'badge_ready' => filled($pivot->badge_printed_at),
            'can_print_badge' => true,
            'can_check_in' => blank($pivot->checked_in_at) || $isCheckedOut,
            'check_in_label' => $isCheckedOut ? __('Erneut einchecken') : __('Check-in'),
            'can_check_out' => filled($pivot->checked_in_at) && blank($pivot->checked_out_at),
            'badge_url' => route('reception.participants.badge', [$visit->id, $visitor->id]),
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
                'label' => __('Ausweis bereit'),
                'class' => 'badge-info badge-outline',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return [
                'label' => __('Entwurf'),
                'class' => 'badge-error badge-outline',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'badge-warning badge-outline',
        ];
    }

    private function loadVisitParticipantPair(int $visitId, int $visitorId): Visit
    {
        return Visit::query()
            ->with(['visitors' => function ($query) use ($visitorId) {
                $query->where('visitors.id', $visitorId);
            }])
            ->whereKey($visitId)
            ->firstOrFail();
    }
}
