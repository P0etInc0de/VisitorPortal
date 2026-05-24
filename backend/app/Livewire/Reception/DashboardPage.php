<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

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
        $this->authorize('view', $visit);
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
        $this->authorize('view', $visit);
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
        $this->authorize('view', $visit);
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
        $user = auth()->user();
        abort_unless($user instanceof User, 403);

        return Visit::query()
            ->visibleTo($user)
            ->with([
                'host:id,first_name,name',
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
        $unpreparedBadges = $this->unpreparedBadgesCount();

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
                'label' => __('Ausweise vorzubereiten'),
                'value' => (string) $unpreparedBadges,
                'meta' => __('Nicht gedruckte Ausweise im Vorbereitungszeitraum'),
            ],
        ];
    }

    private function unpreparedBadgesCount(): int
    {
        $user = auth()->user();
        abort_unless($user instanceof User, 403);
        $referenceNow = now();

        return (int) Visit::query()
            ->visibleTo($user)
            ->where('status', VisitStatusEnum::Planned->value)
            ->where('scheduled_from', '<=', $referenceNow->copy()->addHours($this->badgePreparationWindowHours()))
            ->where('scheduled_until', '>=', $referenceNow)
            ->withCount([
                'visitors as unprepared_badges_count' => fn ($query) => $query->whereNull('visit_visitor.badge_printed_at'),
            ])
            ->get()
            ->sum('unprepared_badges_count');
    }

    private function badgePreparationWindowHours(): int
    {
        return max(0, (int) config('reception.badge_preparation_window_hours', 48));
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
                'host' => trim((string) ($visit->host?->fullName ?? '')) ?: '-',
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
        $canOperate = app(VisitActionService::class)->canOperate($visit);

        return [
            'row_key' => 'visit-'.$visit->id.'-visitor-'.$visitor->id,
            'visitor_id' => $visitor->id,
            'name' => trim($visitor->first_name.' '.$visitor->name),
            'company' => $visitor->company,
            'status_label' => $status['label'],
            'status_class' => $status['class'],
            'badge_ready' => filled($pivot->badge_printed_at),
            'can_print_badge' => $canOperate,
            'can_check_in' => $canOperate && (blank($pivot->checked_in_at) || $isCheckedOut),
            'check_in_label' => $isCheckedOut ? __('Erneut einchecken') : __('Check-in'),
            'can_check_out' => $canOperate && filled($pivot->checked_in_at) && blank($pivot->checked_out_at),
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
