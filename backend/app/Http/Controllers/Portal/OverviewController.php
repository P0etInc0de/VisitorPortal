<?php

namespace App\Http\Controllers\Portal;

use App\Enums\VisitStatusEnum;
use App\Http\Controllers\Controller;
use App\Models\Monitor;
use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;

class OverviewController extends Controller
{
    public function index(Request $request): View
    {
        $user = $request->user();

        if (! $user instanceof User) {
            abort(403);
        }

        $now = now();
        $upcomingWindowEnd = now()->copy()->addDays(30);

        $this->authorizeAny(['viewGeneral', 'view'], Monitor::class);

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
            ->whereNotIn('status', [VisitStatusEnum::Canceled->value, VisitStatusEnum::Completed->value])
            ->where('scheduled_from', '<=', $upcomingWindowEnd)
            ->where(function ($query) use ($now) {
                $query->where('scheduled_until', '>=', $now)
                    ->orWhere(function ($query) use ($now) {
                        $query->whereNull('scheduled_until')
                            ->where('scheduled_from', '>=', $now);
                    });
            })
            ->limit(15)
            ->get();

        $nextVisit = $upcomingVisits->first();

        $weeklyVisits = (clone $baseQuery)
            ->whereBetween('scheduled_from', [now()->startOfWeek(), now()->endOfWeek()])
            ->get();

        $visitCards = $upcomingVisits
            ->map(function (Visit $visit) {
                $previewNames = $visit->visitors
                    ->map(fn (Visitor $visitor) => $this->visitorDisplayName($visitor))
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

        return view('portal.overview', [
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
            'notifications' => $this->notificationCards($user),
            'visits' => $visitCards,
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notificationCards(User $user): array
    {
        return $user->notifications()
            ->latest()
            ->limit(12)
            ->get()
            ->map(fn (DatabaseNotification $notification) => $this->mapNotificationCard($notification))
            ->filter()
            ->sort(function (array $left, array $right): int {
                if ($left['sort_is_read'] !== $right['sort_is_read']) {
                    return $left['sort_is_read'] <=> $right['sort_is_read'];
                }

                return $right['sort_created_at'] <=> $left['sort_created_at'];
            })
            ->take(6)
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function mapNotificationCard(DatabaseNotification $notification): ?array
    {
        $title = $this->notificationTitle($notification);
        $message = $this->notificationMessage($notification);

        if (! filled($title) || ! filled($message)) {
            return null;
        }

        return [
            'id' => $notification->id,
            'title' => $title,
            'message' => $message,
            'action_url' => $this->notificationActionUrl($notification),
            'action_label' => $this->notificationActionLabel($notification),
            'is_read' => filled($notification->read_at),
            'created_at' => $notification->created_at?->format('d.m.Y H:i'),
            'sort_is_read' => filled($notification->read_at) ? 1 : 0,
            'sort_created_at' => $notification->created_at?->timestamp ?? 0,
        ];
    }

    private function notificationTitle(DatabaseNotification $notification): string
    {
        return $this->translateNotificationText($notification, 'title')
            ?? $this->notificationTypeTitle($notification)
            ?? Arr::get($notification->data, 'title')
            ?? __('Benachrichtigung');
    }

    private function notificationMessage(DatabaseNotification $notification): ?string
    {
        return $this->translateNotificationText($notification, 'message')
            ?? $this->notificationTypeMessage($notification)
            ?? Arr::get($notification->data, 'message');
    }

    private function notificationActionLabel(DatabaseNotification $notification): ?string
    {
        if (! filled($this->notificationActionUrl($notification))) {
            return null;
        }

        return $this->translateNotificationText($notification, 'action_label')
            ?? $this->notificationTypeActionLabel($notification)
            ?? Arr::get($notification->data, 'action_label')
            ?? __('Weitere Informationen');
    }

    private function notificationActionUrl(DatabaseNotification $notification): ?string
    {
        if (Arr::get($notification->data, 'type') === 'guest_checked_in') {
            $visitId = Arr::get($notification->data, 'context.visit_id');

            if (filled($visitId)) {
                return route('portal.visits.show', $visitId, absolute: false);
            }
        }

        return $this->normalizeNotificationUrl(Arr::get($notification->data, 'action_url'));
    }

    private function notificationTypeTitle(DatabaseNotification $notification): ?string
    {
        return match (Arr::get($notification->data, 'type')) {
            'guest_checked_in' => __('Besuch eingetroffen'),
            default => null,
        };
    }

    private function notificationTypeMessage(DatabaseNotification $notification): ?string
    {
        return match (Arr::get($notification->data, 'type')) {
            'guest_checked_in' => filled($name = $this->notificationVisitorName($notification))
                ? __('Gast :name ist soeben eingetroffen.', ['name' => $name])
                : null,
            default => null,
        };
    }

    private function notificationTypeActionLabel(DatabaseNotification $notification): ?string
    {
        return match (Arr::get($notification->data, 'type')) {
            'guest_checked_in' => __('Weitere Informationen'),
            default => null,
        };
    }

    private function notificationVisitorName(DatabaseNotification $notification): ?string
    {
        $visitorName = trim((string) Arr::get($notification->data, 'context.visitor_name'));

        if ($visitorName !== '') {
            return $visitorName;
        }

        $visitorId = Arr::get($notification->data, 'context.visitor_id');

        if (! filled($visitorId)) {
            return null;
        }

        $visitor = Visitor::query()->find($visitorId);

        return $visitor ? $this->visitorDisplayName($visitor) : null;
    }

    private function translateNotificationText(DatabaseNotification $notification, string $prefix): ?string
    {
        $key = Arr::get($notification->data, $prefix.'_key');

        if (! filled($key)) {
            return null;
        }

        $replacements = Arr::get($notification->data, $prefix.'_replacements', []);

        return __($key, is_array($replacements) ? $replacements : []);
    }

    private function normalizeNotificationUrl(?string $url): ?string
    {
        $url = trim((string) $url);

        if ($url === '') {
            return null;
        }

        if (str_starts_with($url, '/')) {
            return $url;
        }

        $parts = parse_url($url);

        if (! is_array($parts)) {
            return $url;
        }

        $host = $parts['host'] ?? null;

        if ($host === request()->getHost() || $host === 'localhost') {
            $relativeUrl = $parts['path'] ?? '/';

            if (isset($parts['query'])) {
                $relativeUrl .= '?'.$parts['query'];
            }

            if (isset($parts['fragment'])) {
                $relativeUrl .= '#'.$parts['fragment'];
            }

            return $relativeUrl;
        }

        return $url;
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
            'draft' => 'badge-neutral badge-outline',
            'completed' => 'badge-success badge-outline',
            'canceled' => 'badge-neutral badge-outline',
            default => 'badge-warning badge-outline',
        };
    }

    private function visitorDisplayName(Visitor $visitor): string
    {
        $fullName = trim(implode(' ', array_filter([
            $visitor->first_name,
            $visitor->name,
        ])));

        if ($fullName !== '') {
            return $fullName;
        }

        foreach ([$visitor->company, $visitor->email] as $fallback) {
            $fallback = trim((string) $fallback);

            if ($fallback !== '') {
                return $fallback;
            }
        }

        return '–';
    }
}
