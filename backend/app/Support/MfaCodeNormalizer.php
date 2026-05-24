<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

final class MfaCodeNormalizer
{
    public function normalizeTotp(mixed $code): ?string
    {
        if (! is_scalar($code) && ! $code instanceof \Stringable) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', '', (string) $code) ?? '';

        return preg_match('/^\d{6}$/', $normalized) === 1
            ? $normalized
            : null;
    }

    public function normalizeRecoveryCode(mixed $code): ?string
    {
        if (! is_scalar($code) && ! $code instanceof \Stringable) {
            return null;
        }

        $normalized = preg_replace('/\s+/u', '', (string) $code) ?? '';

        return preg_match('/^[A-Za-z0-9]{10}-[A-Za-z0-9]{10}$/', $normalized) === 1
            ? $normalized
            : null;
    }

    public function hasInput(mixed $code): bool
    {
        if (! is_scalar($code) && ! $code instanceof \Stringable) {
            return false;
        }

        return (preg_replace('/\s+/u', '', (string) $code) ?? '') !== '';
    }
}
