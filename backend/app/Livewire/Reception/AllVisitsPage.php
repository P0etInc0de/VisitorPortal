<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Reception;

use App\Enums\VisitStatusEnum;
use App\Livewire\Concerns\RateLimitsSearch;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Services\VisitActionService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AllVisitsPage extends Component
{
    use AuthorizesRequests;
    use RateLimitsSearch;

    public string $search = '';

    public string $activeRange = 'week';

    public string $dateFrom = '';

    public string $dateTo = '';

    public int $interactionToken = 0;

    /**
     * @var array<int>
     */
    public array $expandedVisitIds = [];

    protected $queryString = [
        'search' => ['as' => 'q', 'except' => ''],
        'activeRange' => ['as' => 'range', 'except' => 'week'],
        'dateFrom' => ['as' => 'from', 'except' => ''],
        'dateTo' => ['as' => 'to', 'except' => ''],
    ];

    public function mount(): void
    {
        $this->authorize('viewAny', Visit::class);

        $this->search = trim($this->search);
        $this->activeRange = $this->normalizeRange($this->activeRange);
        [$from, $to, $fromValue, $toValue] = $this->resolveRange($this->activeRange, $this->dateFrom, $this->dateTo);

        if ($from || $to) {
            $this->dateFrom = $fromValue;
            $this->dateTo = $toValue;
        }
    }

    public function setRange(string $range): void
    {
        $range = $this->normalizeRange($range);
        $this->activeRange = $range;

        [$from, $to, $fromValue, $toValue] = $this->resolveRange($range);
        $this->dateFrom = $fromValue;
        $this->dateTo = $toValue;
        $this->interactionToken++;
    }

    public function applyCustomRange(): void
    {
        $this->activeRange = 'custom';

        [$from, $to, $fromValue, $toValue] = $this->resolveRange('custom', $this->dateFrom, $this->dateTo);
        $this->dateFrom = $fromValue;
        $this->dateTo = $toValue;
        $this->interactionToken++;
    }

    public function resetFilters(): void
    {
        $this->search = '';
        $this->expandedVisitIds = [];
        $this->setRange('week');
    }

    public function toggleExpanded(int $visitId): void
    {
        if (in_array($visitId, $this->expandedVisitIds, true)) {
            $this->expandedVisitIds = array_values(array_filter(
                $this->expandedVisitIds,
                static fn (int $expandedVisitId): bool => $expandedVisitId !== $visitId,
            ));

            $this->interactionToken++;

            return;
        }

        $this->expandedVisitIds[] = $visitId;
        $this->expandedVisitIds = array_values(array_unique(array_map('intval', $this->expandedVisitIds)));
        $this->interactionToken++;
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

        $pivot = $participant->pivot;

        if (blank($pivot->checked_in_at) || filled($pivot->checked_out_at)) {
            app(VisitActionService::class)->checkInParticipant($visit, $participant, $user);

            Log::channel('web')->info(
                filled($pivot->checked_out_at) ? 'Visit participant checked in again' : 'Visit participant checked in',
                [
                    'visit_id' => $visitId,
                    'visitor_id' => $visitorId,
                    'user_id' => $user->id,
                ]
            );

            $this->interactionToken++;
        }
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

        if (filled($participant->pivot->checked_in_at) && blank($participant->pivot->checked_out_at)) {
            app(VisitActionService::class)->checkOutParticipant($visit, $participant, $user);

            Log::channel('web')->info('Visit participant checked out', [
                'visit_id' => $visitId,
                'visitor_id' => $visitorId,
                'user_id' => $user->id,
            ]);

            $this->interactionToken++;
        }
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

        Log::channel('web')->info('Visit participant badge marked as printed from all visits page', [
            'visit_id' => $visitId,
            'visitor_id' => $visitorId,
            'user_id' => auth()->id(),
        ]);

        $this->interactionToken++;
    }

    public function render()
    {
        return view('livewire.reception.all-visits-page', [
            'ranges' => $this->ranges(),
            'visits' => $this->visits(),
        ]);
    }

    /**
     * @return array<string, string>
     */
    public function ranges(): array
    {
        return [
            'today' => __('Heute'),
            'week' => __('Diese Woche'),
            'next-week' => __('Nächste Woche'),
            'custom' => __('Benutzerdefiniert'),
            ...($this->canViewArchive() ? ['all' => __('Alle')] : []),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function visits(): array
    {
        [$from, $until] = $this->resolveRange($this->activeRange, $this->dateFrom, $this->dateTo);
        $search = trim($this->search);

        if ($search !== '') {
            $this->enforceSearchRateLimit('all-visits-page');
        }

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
            ->when($from && $until, function ($query) use ($from, $until) {
                $query->whereBetween('scheduled_from', [$from, $until]);
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('title', 'like', '%'.$search.'%')
                        ->orWhere('notes', 'like', '%'.$search.'%')
                        ->orWhereHas('host', function ($query) use ($search) {
                            $query->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        })
                        ->orWhereHas('visitors', function ($query) use ($search) {
                            $query->where('first_name', 'like', '%'.$search.'%')
                                ->orWhere('name', 'like', '%'.$search.'%')
                                ->orWhere('title', 'like', '%'.$search.'%')
                                ->orWhere('company', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderBy('scheduled_from')
            ->limit(500)
            ->get()
            ->map(function (Visit $visit): array {
                $participants = $visit->visitors
                    ->map(function (Visitor $visitor) use ($visit): array {
                        return $this->mapParticipant($visit, $visitor);
                    })
                    ->values()
                    ->all();

                $scheduledFrom = $visit->scheduled_from instanceof Carbon ? $visit->scheduled_from : null;
                $status = $this->resolveVisitStatusMeta($visit->status);
                $hostDisplay = trim(($visit->host?->first_name ?? '').' '.($visit->host?->name ?? ''));

                return [
                    'id' => $visit->id,
                    'title' => $visit->title ?: __('Besuch'),
                    'time' => $scheduledFrom?->format('H:i'),
                    'date' => $scheduledFrom?->format('d.m.'),
                    'scheduled_from' => $scheduledFrom?->format('d.m.Y H:i'),
                    'scheduled_until' => optional($visit->scheduled_until)->format('d.m.Y H:i'),
                    'date_time_label' => $this->formatVisitDateTimeLabel($scheduledFrom),
                    'year_label' => $scheduledFrom?->format('Y') ?? __('Ohne Jahr'),
                    'is_recurring' => filled($visit->recurring_visit_series_id),
                    'recurrence_is_modified' => (bool) $visit->recurrence_is_modified,
                    'host' => $hostDisplay,
                    'host_display' => $hostDisplay,
                    'status' => $status['label'],
                    'status_label' => $status['label'],
                    'status_class' => $status['class'],
                    'notes' => $visit->notes,
                    'note_text' => trim((string) $visit->notes),
                    'participants' => $participants,
                    'participants_count' => count($participants),
                    'open_url' => route('portal.visits.show', $visit->id),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function mapParticipant(Visit $visit, Visitor $visitor): array
    {
        $pivot = $visitor->pivot;
        $status = $this->resolveParticipantStatus($visit->status, $pivot);
        $isCheckedOut = filled($pivot->checked_out_at);
        $canOperate = app(VisitActionService::class)->canOperate($visit);

        return [
            'row_key' => 'visit-'.$visit->id.'-visitor-'.$visitor->id,
            'visit_id' => $visit->id,
            'visitor_id' => $visitor->id,
            'title' => $visitor->title,
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

    /**
     * @return array{label: string, class: string}
     */
    private function resolveParticipantStatus(string $visitStatus, object $pivot): array
    {
        if (filled($pivot->checked_out_at)) {
            return [
                'label' => __('Ausgecheckt'),
                'class' => 'text-base-content/60',
            ];
        }

        if (filled($pivot->checked_in_at)) {
            return [
                'label' => __('Eingecheckt'),
                'class' => 'text-success',
            ];
        }

        if (filled($pivot->badge_printed_at)) {
            return [
                'label' => __('Ausweis bereit'),
                'class' => 'id-card-ready-text',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return [
                'label' => __('Entwurf'),
                'class' => 'text-base-content/60',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Canceled->value) {
            return [
                'label' => __('Abgesagt'),
                'class' => 'text-error',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'text-warning',
        ];
    }

    /**
     * @return array{label: string, class: string}
     */
    private function resolveVisitStatusMeta(string $status): array
    {
        return match ($status) {
            VisitStatusEnum::Draft->value => ['label' => VisitStatusEnum::Draft->label(), 'class' => 'text-base-content/60'],
            VisitStatusEnum::Planned->value => ['label' => VisitStatusEnum::Planned->label(), 'class' => 'text-warning'],
            VisitStatusEnum::Completed->value => ['label' => VisitStatusEnum::Completed->label(), 'class' => 'text-success'],
            VisitStatusEnum::Canceled->value => ['label' => VisitStatusEnum::Canceled->label(), 'class' => 'text-error'],
            default => ['label' => __($status), 'class' => ''],
        };
    }

    private function formatVisitDateTimeLabel(?Carbon $scheduledFrom): ?string
    {
        if (! $scheduledFrom) {
            return null;
        }

        return $scheduledFrom->locale(app()->getLocale())->translatedFormat('D, d.m.').' | '.$scheduledFrom->format('H:i').' '.__('Uhr');
    }

    /**
     * @return array{0: Carbon|null, 1: Carbon|null, 2: string, 3: string}
     */
    private function resolveRange(string $range, string $dateFromInput = '', string $dateToInput = ''): array
    {
        $range = $this->normalizeRange($range);
        $fromDate = $this->parseDateInput($dateFromInput, false);
        $untilDate = $this->parseDateInput($dateToInput, true);

        if ($fromDate && $untilDate) {
            if ($fromDate->diffInDays($untilDate) > 180 && ! $this->canViewArchive()) {
                $untilDate = $fromDate->copy()->addDays(180)->endOfDay();
            }

            return [$fromDate, $untilDate, $fromDate->toDateString(), $untilDate->toDateString()];
        }

        return match ($range) {
            'today' => [now()->startOfDay(), now()->endOfDay(), now()->toDateString(), now()->toDateString()],
            'next-week' => [
                now()->copy()->addWeek()->startOfWeek(),
                now()->copy()->addWeek()->endOfWeek(),
                now()->copy()->addWeek()->startOfWeek()->toDateString(),
                now()->copy()->addWeek()->endOfWeek()->toDateString(),
            ],
            'all' => $this->canViewArchive() ? [null, null, '', ''] : [now()->startOfWeek(), now()->endOfWeek(), now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()],
            default => [
                now()->startOfWeek(),
                now()->endOfWeek(),
                now()->startOfWeek()->toDateString(),
                now()->endOfWeek()->toDateString(),
            ],
        };
    }

    private function parseDateInput(string $value, bool $endOfDay = false): ?Carbon
    {
        if ($value === '') {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('Y-m-d', $value);
        } catch (\Throwable) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function normalizeRange(string $range): string
    {
        if ($range === 'all' && ! $this->canViewArchive()) {
            return 'week';
        }

        return array_key_exists($range, $this->ranges()) || $range === 'custom'
            ? $range
            : 'week';
    }

    private function canViewArchive(): bool
    {
        return auth()->user()?->can('ViewArchive:Visit') ?? false;
    }

    private function loadVisitParticipantPair(int $visitId, int $visitorId): Visit
    {
        return Visit::query()
            ->with([
                'visitors' => function ($query) use ($visitorId) {
                    $query->where('visitors.id', $visitorId);
                },
            ])
            ->findOrFail($visitId);
    }
}
