<?php

namespace App\Livewire\Reception;

use App\Enums\VisitStatusEnum;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Livewire\Component;

class AllVisitsPage extends Component
{
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
        $this->setRange('all');
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
        $visit = $this->loadVisitParticipantPair($visitId, $visitorId);
        $participant = $visit->visitors->firstWhere('id', $visitorId);

        if (! $participant) {
            return;
        }

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

            $visit->visitors()->updateExistingPivot($visitorId, $payload);

            Log::channel('web')->info(
                filled($pivot->checked_out_at) ? 'Visit participant checked in again' : 'Visit participant checked in',
                [
                    'visit_id' => $visitId,
                    'visitor_id' => $visitorId,
                    'user_id' => auth()->id(),
                ]
            );

            $this->interactionToken++;
        }
    }

    public function checkOut(int $visitId, int $visitorId): void
    {
        $visit = $this->loadVisitParticipantPair($visitId, $visitorId);
        $participant = $visit->visitors->firstWhere('id', $visitorId);

        if (! $participant) {
            return;
        }

        if (filled($participant->pivot->checked_in_at) && blank($participant->pivot->checked_out_at)) {
            $visit->visitors()->updateExistingPivot($visitorId, [
                'checked_out_at' => now(),
                'checked_out_by_user_id' => auth()->id(),
                'updated_at' => now(),
            ]);

            Log::channel('web')->info('Visit participant checked out', [
                'visit_id' => $visitId,
                'visitor_id' => $visitorId,
                'user_id' => auth()->id(),
            ]);

            $this->interactionToken++;
        }
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
            'all' => __('Alle'),
        ];
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function visits(): array
    {
        [$from, $until] = $this->resolveRange($this->activeRange, $this->dateFrom, $this->dateTo);
        $search = trim($this->search);

        return Visit::query()
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
                                ->orWhere('company', 'like', '%'.$search.'%')
                                ->orWhereRaw("concat(first_name, ' ', name) like ?", ['%'.$search.'%']);
                        });
                });
            })
            ->orderBy('scheduled_from')
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

        return [
            'row_key' => 'visit-'.$visit->id.'-visitor-'.$visitor->id,
            'visit_id' => $visit->id,
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

    /**
     * @return array{label: string, class: string}
     */
    private function resolveParticipantStatus(string $visitStatus, object $pivot): array
    {
        if (filled($pivot->checked_out_at)) {
            return [
                'label' => __('Ausgecheckt'),
                'class' => 'bp-status-muted',
            ];
        }

        if (filled($pivot->checked_in_at)) {
            return [
                'label' => __('Eingecheckt'),
                'class' => 'bp-status-success',
            ];
        }

        if (filled($pivot->badge_printed_at)) {
            return [
                'label' => __('Badge bereit'),
                'class' => 'bp-status-success',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Draft->value) {
            return [
                'label' => __('Entwurf'),
                'class' => 'bp-status-muted',
            ];
        }

        if ($visitStatus === VisitStatusEnum::Canceled->value) {
            return [
                'label' => __('Abgesagt'),
                'class' => 'bp-status-danger',
            ];
        }

        return [
            'label' => __('Geplant'),
            'class' => 'bp-status-warning',
        ];
    }

    /**
     * @return array{label: string, class: string}
     */
    private function resolveVisitStatusMeta(string $status): array
    {
        return match ($status) {
            VisitStatusEnum::Draft->value => ['label' => VisitStatusEnum::Draft->label(), 'class' => 'bp-status-muted'],
            VisitStatusEnum::Planned->value => ['label' => VisitStatusEnum::Planned->label(), 'class' => 'bp-status-warning'],
            VisitStatusEnum::Completed->value => ['label' => VisitStatusEnum::Completed->label(), 'class' => 'bp-status-success'],
            VisitStatusEnum::Canceled->value => ['label' => VisitStatusEnum::Canceled->label(), 'class' => 'bp-status-danger'],
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
            'all' => [null, null, '', ''],
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
        return array_key_exists($range, $this->ranges()) || $range === 'custom'
            ? $range
            : 'week';
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
