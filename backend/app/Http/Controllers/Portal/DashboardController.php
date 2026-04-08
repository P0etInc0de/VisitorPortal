<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Visit;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        $baseQuery = Visit::query()
            ->with([
                'host:id,name',
                'visitors' => function ($query) {
                    $query->orderBy('first_name')->orderBy('last_name');
                },
            ])
            ->where('user_id', $user->id)
            ->orderBy('scheduled_from');

        $todayVisits = (clone $baseQuery)
            ->whereBetween('scheduled_from', [now()->startOfDay(), now()->endOfDay()])
            ->get();

        $upcomingVisits = (clone $baseQuery)
            ->where('scheduled_until', '>=', now())
            ->take(5)
            ->get();

        $nextVisit = $upcomingVisits->first();

        $weeklyVisits = (clone $baseQuery)
            ->whereBetween('scheduled_from', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $notes = $upcomingVisits
            ->flatMap(function (Visit $visit) {
                return $visit->visitors
                    ->filter(fn ($visitor) => blank($visitor->pivot?->badge_printed_at))
                    ->map(fn ($visitor) => __('Badge noch offen für :name bei :title', [
                        'name' => $visitor->full_name,
                        'title' => $visit->display_title,
                    ]));
            })
            ->take(3)
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
            'visits' => $upcomingVisits,
            'notes' => $notes,
        ]);
    }
}
