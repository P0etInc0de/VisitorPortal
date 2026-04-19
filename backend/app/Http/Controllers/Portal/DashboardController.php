<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();
		if(!($user->can('ViewAny:Visit') || $user->can('ViewDepartment:Visit') || $user->can('ViewOwn:Visit') || $user->hasRole('welcome monitor')))
		{
			abort(403);
		}

        $baseQuery = Visit::query()
            ->with([
                'host:id,name',
                'visitors' => fn ($query) => $query->orderBy('first_name')->orderBy('name'),
            ])
            ->where('user_id', $user->id)
            ->orderBy('scheduled_from');

        $todayVisits = (clone $baseQuery)
            ->whereBetween('scheduled_from', [now()->startOfDay(), now()->endOfDay()])
            ->get();

        $upcomingVisits = (clone $baseQuery)
            ->where('scheduled_until', '>=', now())
            ->take(6)
            ->get();

        $nextVisit = $upcomingVisits->first();

        $weeklyVisits = (clone $baseQuery)
            ->whereBetween('scheduled_from', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $notes = $upcomingVisits
            ->flatMap(function (Visit $visit) {
                return $visit->visitors
                    ->filter(fn (Visitor $visitor) => blank($visitor->pivot?->badge_printed_at))
                    ->map(fn (Visitor $visitor) => __('Badge noch offen für :name bei :title', [
                        'name' => $visitor->display_name,
                        'title' => $visit->display_title,
                    ]));
            })
            ->take(3)
            ->values();

        $visitCards = $upcomingVisits
            ->map(function (Visit $visit) {
                $previewNames = $visit->visitors
                    ->map(fn (Visitor $visitor) => $visitor->display_name)
                    ->filter(fn (?string $name) => filled($name) && $name !== '–')
                    ->take(3)
                    ->values();

                $visibleParticipantCount = $previewNames->count();
                $remainingParticipantCount = max($visit->participant_count - $visibleParticipantCount, 0);

                return [
                    'visit' => $visit,
                    'time' => $visit->scheduled_from?->format('H:i') ?? '–',
                    'date' => $visit->scheduled_from?->format('d.m.') ?? '–',
                    'title' => $visit->display_title,
                    'host' => $visit->host?->name ?? __('Nicht gesetzt'),
                    'note' => $visit->notes,
                    'participant_preview' => $previewNames,
                    'remaining_participants' => $remainingParticipantCount,
                    'participant_count' => $visit->participant_count,
                    'status_label' => $this->visitStatusLabel((string) $visit->status),
                    'status_class' => $this->visitStatusClass((string) $visit->status),
                ];
            })
            ->values();

        return view('portal.dashboard', [
            'stats' => [
                [
                    'label' => __('Meine Besuche heute'),
                    'value' => (string) $todayVisits->count(),
                    'meta' => $todayVisits->contains(
                        fn (Visit $visit) => $visit->scheduled_from && $visit->scheduled_from->between(now(), now()->copy()->addHour())
                    )
                        ? __('1 Termin startet in der nächsten Stunde.')
                        : __('Keine unmittelbaren Termine.'),
                ],
                [
                    'label' => __('Nächster Termin'),
                    'value' => $nextVisit?->scheduled_from?->format('H:i') ?? '–',
                    'meta' => $nextVisit
                        ? __(':title · :count Teilnehmende', [
                            'title' => $nextVisit->display_title,
                            'count' => $nextVisit->participant_count,
                        ])
                        : __('Kein Termin geplant'),
                ],
                [
                    'label' => __('Diese Woche'),
                    'value' => (string) $weeklyVisits->count(),
                    'meta' => __(':planned geplant · :draft Entwürfe', [
                        'planned' => $weeklyVisits->where('status', 'planned')->count(),
                        'draft' => $weeklyVisits->where('status', 'draft')->count(),
                    ]),
                ],
            ],
            'visits' => $visitCards,
            'notes' => $notes,
        ]);
    }

    private function visitStatusLabel(string $status): string
    {
        return match ($status) {
            'draft' => __('Entwurf'),
            'completed' => __('Abgeschlossen'),
            'canceled' => __('Abgesagt'),
            default => __('Geplant'),
        };
    }

    private function visitStatusClass(string $status): string
    {
        return match ($status) {
            'draft' => 'bp-status-draft',
            'completed' => 'bp-status-active',
            'canceled' => 'bp-status-neutral',
            default => 'bp-status-planned',
        };
    }
}
