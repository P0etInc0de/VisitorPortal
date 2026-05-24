<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Auth\Sso\DTO;

final readonly class OidcIdentity
{
    /**
     * @param  list<string>  $groups
     * @param  array<string, mixed>  $claims
     */
    public function __construct(
        public string $issuer,
        public string $subject,
        public ?string $email,
        public bool $emailVerified,
        public ?string $displayName,
        public array $groups,
        public array $claims,
    ) {}
}
