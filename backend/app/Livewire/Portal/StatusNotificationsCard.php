<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Livewire\Portal;

use App\Models\User;
use App\Models\Visitor;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Support\Arr;
use Livewire\Component;

class StatusNotificationsCard extends Component
{
    public function toggleRead(string $notificationId): void
    {
        $notification = $this->notification($notificationId);

        if (filled($notification->read_at)) {
            $notification->forceFill(['read_at' => null])->save();

            return;
        }

        $notification->markAsRead();
    }

    public function deleteNotification(string $notificationId): void
    {
        $this->notification($notificationId)->delete();
    }

    public function render()
    {
        return view('livewire.portal.status-notifications-card', [
            'notifications' => $this->notificationCards(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function notificationCards(): array
    {
        return $this->user()->notifications()
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

    private function notification(string $notificationId): DatabaseNotification
    {
        return $this->user()->notifications()->findOrFail($notificationId);
    }

    private function user(): User
    {
        $user = auth()->user();

        if (! $user instanceof User) {
            abort(403);
        }

        return $user;
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
