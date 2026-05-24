<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

class PortalNotificationData
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public static function make(
        string $type,
        string $titleKey,
        string $messageKey,
        array $messageReplacements = [],
        ?string $actionUrl = null,
        ?string $actionLabelKey = null,
        array $actionLabelReplacements = [],
        array $context = [],
    ): array {
        return array_filter([
            'type' => $type,
            'title_key' => $titleKey,
            'message_key' => $messageKey,
            'message_replacements' => $messageReplacements,
            'action_url' => $actionUrl,
            'action_label_key' => $actionLabelKey,
            'action_label_replacements' => $actionLabelReplacements,
            'context' => $context,
        ], static fn (mixed $value): bool => $value !== null && $value !== []);
    }
}
