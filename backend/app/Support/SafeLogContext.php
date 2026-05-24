<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Database\Eloquent\Model;

final class SafeLogContext
{
    public static function authorization(mixed $user, string $ability, mixed $arguments = null): array
    {
        return [
            'user_id' => $user?->id,
            'ability' => $ability,
            ...self::resource($arguments),
        ];
    }

    public static function resource(mixed $arguments): array
    {
        foreach (is_array($arguments) ? $arguments : [$arguments] as $argument) {
            if ($argument instanceof Model) {
                return [
                    'resource_type' => class_basename($argument),
                    'resource_id' => $argument->getKey(),
                ];
            }

            if (is_string($argument) && class_exists($argument)) {
                return [
                    'resource_type' => class_basename($argument),
                    'resource_id' => null,
                ];
            }
        }

        return [
            'resource_type' => null,
            'resource_id' => null,
        ];
    }
}
