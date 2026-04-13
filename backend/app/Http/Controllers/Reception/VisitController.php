<?php

namespace App\Http\Controllers\Reception;

use App\Enums\VisitStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class VisitController extends Controller
{
    public function index(Request $request): View
    {
        $range = $request->string('range')->toString() ?: 'week';
        $search = trim((string) $request->string('q'));
        $dateFromInput = trim((string) $request->string('from'));
        $dateToInput = trim((string) $request->string('to'));

        [$from, $until, $dateFrom, $dateTo] = $this->resolveRange($range, $dateFromInput, $dateToInput);

        $query = Visit::query()
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
            ->orderBy('scheduled_from');

        $visits = $query->get()->map(function (Visit $visit) {
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
                'visible_participants' => $participants->take(3)->values(),
                'hidden_participants' => $participants->slice(3)->values(),
                'hidden_count' => max(0, $participants->count() - 3),
                'participants_count' => $participants->count(),
            ];
        });

        $stats = [
            [
                'label' => __('Nächste 7 Tage'),
                'value' => (string) $this->participantsCountForRange(now()->startOfDay(), now()->copy()->addDays(6)->endOfDay()),
                'meta' => __('Teilnehmende in den kommenden 7 Tagen'),
            ],
            [
                'label' => __('Diese Woche'),
                'value' => (string) $this->participantsCountForRange(now()->startOfWeek(), now()->endOfWeek()),
                'meta' => __('Alle Teilnehmenden der aktuellen Woche'),
            ],
            [
                'label' => __('Entwürfe und Klärungen'),
                'value' => (string) $this->openItemsCount(),
                'meta' => __('Entwürfe oder noch nicht vorbereitete Teilnehmende'),
            ],
        ];

        $ranges = [
            'today' => __('Heute'),
            'week' => __('Diese Woche'),
            'next-week' => __('Nächste Woche'),
            'all' => __('Alle'),
        ];

        return view('reception.all-visits', [
            'stats' => $stats,
            'visits' => $visits,
            'ranges' => $ranges,
            'activeRange' => $range,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    private function resolveRange(string $range, string $dateFromInput = '', string $dateToInput = ''): array
    {
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
        } catch (\Throwable $e) {
            return null;
        }

        return $endOfDay ? $date->endOfDay() : $date->startOfDay();
    }

    private function participantsCountForRange($from, $until): int
    {
        return Visit::query()
            ->withCount('visitors')
            ->whereBetween('scheduled_from', [$from, $until])
            ->get()
            ->sum('visitors_count');
    }

    private function openItemsCount(): int
    {
        return Visit::query()
            ->with('visitors')
            ->get()
            ->sum(function (Visit $visit) {
                if ($visit->status === VisitStatusEnum::Draft->value) {
                    return max(1, $visit->visitors->count());
                }

                return $visit->visitors->filter(function (Visitor $visitor) {
                    return blank($visitor->pivot->badge_printed_at);
                })->count();
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
}
