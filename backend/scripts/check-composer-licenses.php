<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

declare(strict_types=1);

$lockPath = __DIR__.'/../composer.lock';

if (! is_file($lockPath)) {
    fwrite(STDERR, "composer.lock not found.\n");
    exit(1);
}

$lock = json_decode((string) file_get_contents($lockPath), true);

if (! is_array($lock)) {
    fwrite(STDERR, "composer.lock could not be parsed.\n");
    exit(1);
}

$allowedLicenses = [
    '0BSD',
    'Apache-2.0',
    'BSD-2-Clause',
    'BSD-3-Clause',
    'CC0-1.0',
    'GPL-3.0-only',
    'GPL-3.0-or-later',
    'ISC',
    'LGPL-2.1-or-later',
    'LGPL-3.0-only',
    'LGPL-3.0-or-later',
    'MIT',
    'Unlicense',
    'WTFPL',
    'Zlib',
];

$reviewLicenses = [
    'AGPL-1.0-only',
    'AGPL-1.0-or-later',
    'AGPL-3.0-only',
    'AGPL-3.0-or-later',
    'Commercial',
    'GPL-1.0-only',
    'GPL-1.0-or-later',
    'GPL-2.0-only',
    'LGPL-2.0-only',
    'NOASSERTION',
    'Proprietary',
    'SSPL-1.0',
    'UNKNOWN',
    'proprietary',
];

$allowed = array_fill_keys($allowedLicenses, true);
$review = array_fill_keys($reviewLicenses, true);
$failures = [];

foreach (['packages', 'packages-dev'] as $section) {
    foreach (($lock[$section] ?? []) as $package) {
        if (! is_array($package)) {
            continue;
        }

        $licenses = normalizeLicenses($package['license'] ?? []);
        $hasAllowedLicense = false;
        $needsReview = [];

        foreach ($licenses as $license) {
            if (isset($allowed[$license])) {
                $hasAllowedLicense = true;
            }

            if (isset($review[$license])) {
                $needsReview[] = $license;
            }
        }

        if ($hasAllowedLicense) {
            continue;
        }

        $failures[] = [
            'section' => $section,
            'name' => (string) ($package['name'] ?? 'unknown-package'),
            'licenses' => $licenses,
            'needs_review' => $needsReview,
        ];
    }
}

if ($failures === []) {
    echo "Composer license policy passed: all packages have a GPLv3-compatible license option.\n";
    exit(0);
}

fwrite(STDERR, "Composer license policy failed. Packages without a GPLv3-compatible license option:\n");

foreach ($failures as $failure) {
    $licenses = $failure['licenses'] === [] ? ['UNKNOWN'] : $failure['licenses'];
    $reviewSuffix = $failure['needs_review'] === [] ? '' : ' (needs review: '.implode(', ', $failure['needs_review']).')';

    fwrite(STDERR, sprintf(
        "- [%s] %s: %s%s\n",
        $failure['section'],
        $failure['name'],
        implode(', ', $licenses),
        $reviewSuffix,
    ));
}

exit(1);

/**
 * @return list<string>
 */
function normalizeLicenses(mixed $licenses): array
{
    if (! is_array($licenses) || $licenses === []) {
        return ['UNKNOWN'];
    }

    $normalized = [];

    foreach ($licenses as $license) {
        if (! is_string($license) || trim($license) === '') {
            continue;
        }

        $parts = preg_split('/\s+(?:OR|or|\|)\s+/', trim($license));

        foreach ($parts ?: [] as $part) {
            $part = trim($part, " \t\n\r\0\x0B()");

            if ($part !== '') {
                $normalized[] = $part;
            }
        }
    }

    return array_values(array_unique($normalized ?: ['UNKNOWN']));
}
