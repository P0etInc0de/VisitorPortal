<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

use Illuminate\Mail\Mailables\Attachment;

final class SignatureAttachment
{
    /**
     * @return array<int, Attachment>
     */
    public static function fromDataUrl(?string $dataUrl, string $filename = 'unterschrift.png'): array
    {
        if (! is_string($dataUrl) || ! str_starts_with($dataUrl, 'data:image/')) {
            return [];
        }

        $separator = strpos($dataUrl, ',');

        if ($separator === false) {
            return [];
        }

        $bytes = base64_decode(substr($dataUrl, $separator + 1), true);

        if (! is_string($bytes) || $bytes === '') {
            return [];
        }

        return [
            Attachment::fromData(static fn (): string => $bytes, $filename)->withMime('image/png'),
        ];
    }
}
