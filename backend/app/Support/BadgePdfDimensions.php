<?php

namespace App\Support;

final class BadgePdfDimensions
{
    /**
     * Keep the badge PDF in points, not millimeters.
     *
     * Gotenberg/Chromium quantizes requested paper sizes to internal point
     * increments. Requesting the physical ID-1 card size as 85.6mm x 53.98mm
     * produced a real PDF MediaBox of 242.88pt x 154.08pt, while the HTML was
     * still laid out at the original millimeter size. That mismatch left real
     * unpainted PDF page area visible as white stripes on the right/bottom.
     *
     * The stable contract is therefore:
     * - HTML/CSS uses the actual MediaBox size Chromium emits: 242.88pt x 154.08pt.
     * - The Gotenberg request uses 242.88pt x 153.12pt because Chromium rounds
     *   that request up to the exact 242.88pt x 154.08pt MediaBox.
     *
     * Do not switch these values back to millimeters unless the generated PDF
     * MediaBox is re-measured and the HTML size is updated to match it exactly.
     */
    public const MEDIA_WIDTH_PT = 242.88;

    public const MEDIA_HEIGHT_PT = 154.08;

    public const REQUEST_WIDTH_PT = 242.88;

    public const REQUEST_HEIGHT_PT = 153.12;

    public static function cssPt(float $value): string
    {
        return number_format($value, 2, '.', '').'pt';
    }
}
