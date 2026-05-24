<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use App\Enums\GenderEnum;
use App\Enums\SalutationEnum;
use App\Models\User;
use App\Models\Visitor;

class MailGreeting
{
    public static function forUser(User $user): string
    {
        return match (self::gender($user)) {
            GenderEnum::Male => self::line(__('Guten Tag Herr'), $user->title, $user->name),
            GenderEnum::Female => self::line(__('Guten Tag Frau'), $user->title, $user->name),
            default => self::line(__('Guten Tag'), $user->title, $user->first_name, $user->name),
        };
    }

    public static function forVisitor(Visitor $visitor, bool $formal = true): string
    {
        if (! $formal) {
            return self::line(__('Guten Tag'), $visitor->title, $visitor->first_name, $visitor->name);
        }

        return match (self::salutation($visitor)) {
            SalutationEnum::Mr => self::line(__('Sehr geehrter Herr'), $visitor->title, $visitor->name),
            SalutationEnum::Ms => self::line(__('Sehr geehrte Frau'), $visitor->title, $visitor->name),
            default => self::line(__('Guten Tag'), $visitor->title, $visitor->first_name, $visitor->name),
        };
    }

    private static function gender(User $user): ?GenderEnum
    {
        if ($user->gender instanceof GenderEnum) {
            return $user->gender;
        }

        return GenderEnum::tryFrom((string) $user->gender);
    }

    private static function salutation(Visitor $visitor): ?SalutationEnum
    {
        if ($visitor->salutation instanceof SalutationEnum) {
            return $visitor->salutation;
        }

        return SalutationEnum::tryFrom((string) $visitor->salutation);
    }

    private static function line(?string ...$parts): string
    {
        $text = trim(preg_replace('/\s+/', ' ', implode(' ', array_filter(
            array_map(static fn (?string $part): string => trim((string) $part), $parts),
            static fn (string $part): bool => $part !== ''
        ))) ?? '');

        return $text.',';
    }
}
