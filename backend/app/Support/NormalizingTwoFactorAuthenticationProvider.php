<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Contracts\Cache\Repository;
use Laravel\Fortify\TwoFactorAuthenticationProvider;
use PragmaRX\Google2FA\Google2FA;

final class NormalizingTwoFactorAuthenticationProvider extends TwoFactorAuthenticationProvider
{
    public function __construct(
        Google2FA $engine,
        ?Repository $cache,
        private readonly MfaCodeNormalizer $normalizer,
    ) {
        parent::__construct($engine, $cache);
    }

    public function verify($secret, $code)
    {
        $normalizedCode = $this->normalizer->normalizeTotp($code);

        if ($normalizedCode === null) {
            return false;
        }

        return parent::verify($secret, $normalizedCode);
    }
}
