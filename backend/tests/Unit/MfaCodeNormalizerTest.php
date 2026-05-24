<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use App\Support\MfaCodeNormalizer;
use Tests\TestCase;

class MfaCodeNormalizerTest extends TestCase
{
    public function test_totp_codes_are_normalized_strictly(): void
    {
        $normalizer = new MfaCodeNormalizer;

        $this->assertSame('123456', $normalizer->normalizeTotp('123456'));
        $this->assertSame('123456', $normalizer->normalizeTotp('123 456'));
        $this->assertSame('123456', $normalizer->normalizeTotp('12 34 56'));
        $this->assertNull($normalizer->normalizeTotp('123-456'));
        $this->assertNull($normalizer->normalizeTotp('abc123'));
        $this->assertNull($normalizer->normalizeTotp('12345'));
        $this->assertNull($normalizer->normalizeTotp('1234567'));
    }

    public function test_recovery_codes_are_normalized_separately(): void
    {
        $normalizer = new MfaCodeNormalizer;

        $this->assertSame('abcdEF1234-ZYX987wvuT', $normalizer->normalizeRecoveryCode('abcdEF1234-ZYX987wvuT'));
        $this->assertSame('abcdEF1234-ZYX987wvuT', $normalizer->normalizeRecoveryCode('abcd EF1234 - ZYX987 wvuT'));
        $this->assertNull($normalizer->normalizeRecoveryCode('123 456'));
        $this->assertNull($normalizer->normalizeRecoveryCode('abcdEF1234ZYX987wvuT'));
        $this->assertNull($normalizer->normalizeRecoveryCode('abcdEF1234-ZYX987wvu!'));
    }
}
