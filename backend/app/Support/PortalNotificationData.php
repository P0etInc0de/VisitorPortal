<?php

namespace App\Support;

class PortalNotificationData
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function make(
        string $type,
        string $title,
        string $message,
        ?string $actionUrl = null,
        ?string $actionLabel = null,
        array $context = [],
    ): array {
        return array_filter([
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'action_url' => $actionUrl,
            'action_label' => $actionLabel,
            'context' => $context,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
