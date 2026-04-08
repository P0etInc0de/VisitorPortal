<?php

namespace App\Http\Controllers\Reception;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;

class DashboardController extends Controller
{
    public function index(): View
    {
        $todayStart = now()->startOfDay();
        $todayEnd = now()->endOfDay();

        $todayVisits = Visit::query()
            ->with([
                'host:id,name',
                'visitors' => function ($query) {
                    $query->orderBy('first_name')->orderBy('last_name');
                },
            ])
            ->whereBetween('scheduled_from', [$todayStart, $todayEnd])
            ->orderBy('scheduled_from')
            ->get();

        $arrivalsToday = $todayVisits->sum(fn (Visit $visit) => $visit->visitors->count());

        $currentlyInHouse = $todayVisits->sum(function (Visit $visit) {
            return $visit->visitors->filter(function (Visitor $visitor) {
                return filled($visitor->pivot->checked_in_at) && blank($visitor->pivot->checked_out_at);
            })->count();
        });

        $openClarifications = $todayVisits->sum(function (Visit $visit) {
            return $visit->visitors->filter(function (Visitor $visitor) use ($visit) {
                return blank($visitor->pivot->badge_printed_at) || $visit->status === 'draft';
            })->count();
        });

        $stats = [
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
                'meta' => __('Badge, Entwürfe oder offene Vorbereitung'),
            ],
        ];

        $visits = $todayVisits->map(function (Visit $visit) {
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
                'host' => $visit->host?->name ?: '-',
                'notes' => $visit->notes,
                'visible_participants' => $participants->take(3)->values(),
                'hidden_participants' => $participants->slice(3)->values(),
                'hidden_count' => max(0, $participants->count() - 3),
            ];
        });

        return view('reception.dashboard', [
            'stats' => $stats,
            'visits' => $visits,
        ]);
    }

    private function mapParticipant(Visit $visit, Visitor $visitor): array
    {
        $pivot = $visitor->pivot;
        $status = $this->resolveParticipantStatus($visit->status, $pivot);
        $isCheckedOut = filled($pivot->checked_out_at);

        return [
            'row_key' => 'visit-' . $visit->id . '-visitor-' . $visitor->id,
            'visitor_id' => $visitor->id,
            'name' => trim($visitor->first_name . ' ' . $visitor->last_name),
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

        if ($visitStatus === 'draft') {
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
}
