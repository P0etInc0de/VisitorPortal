<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

final class VisitorContactRequirement
{
    public const OPTIONAL = 'optional';

    public const REQUIRE_ONE = 'require_one';

    public const REQUIRE_EMAIL = 'require_email';

    public const REQUIRE_PHONE = 'require_phone';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return [
            self::OPTIONAL,
            self::REQUIRE_ONE,
            self::REQUIRE_EMAIL,
            self::REQUIRE_PHONE,
        ];
    }

    public static function current(): string
    {
        $value = (string) config('privacy.visitor_contact_requirement', self::OPTIONAL);

        return in_array($value, self::values(), true) ? $value : self::OPTIONAL;
    }

    public static function requiresEmail(?string $requirement = null): bool
    {
        return ($requirement ?? self::current()) === self::REQUIRE_EMAIL;
    }

    public static function requiresPhone(?string $requirement = null): bool
    {
        return ($requirement ?? self::current()) === self::REQUIRE_PHONE;
    }

    public static function requiresOne(?string $requirement = null): bool
    {
        return ($requirement ?? self::current()) === self::REQUIRE_ONE;
    }
}
