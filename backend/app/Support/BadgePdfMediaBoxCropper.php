<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace App\Support;

/*
 * PDF RENDERER WORKAROUND:
 *
 * We intentionally manipulate the raw PDF bytes here and move the lower
 * MediaBox edge up by exactly 1pt. This is not a visual overlay, not a CSS
 * bleed, and not a layout trick. It is a physical crop of Chromium's broken
 * output geometry.
 *
 * WHY THIS EXISTS:
 * Chromium's PDF rendering pipeline has a severe paper-size quantization bug.
 * It accepts exact requested dimensions, internally converts them through
 * integer CSS-pixel layout bounds, and then emits the final PDF in PostScript
 * points. That conversion chain is not mathematically lossless. The result is
 * a PDF MediaBox that is slightly larger than the area Chromium actually
 * painted, consistently leaving a sub-point unpainted white band at the
 * bottom edge of the generated badge.
 *
 * WHY CSS CANNOT FIX THIS:
 * CSS bleeds, overdraws, enlarged backgrounds, expanded child nodes, and
 * similar "just paint past the edge" attempts do not work here. Chromium clips
 * root background paints and child surfaces to its internal integer-pixel
 * layout bounds before the final PDF MediaBox crop is applied. In other words,
 * the renderer cuts the paint area short first, then proudly exports a larger
 * page box afterward. That is Chromium's accounting mistake, not ours.
 *
 * WHY BYTE-LEVEL CROPPING IS THE CORRECT FIX:
 * Since Chromium has already thrown away the paint outside its integer-pixel
 * bounds by the time the PDF exists, there is nothing left for CSS to recover.
 * The only deterministic fix is to crop the emitted PDF box itself so the page
 * geometry matches the area Chromium actually painted. This 1pt MediaBox trim
 * removes Chromium's rendering artifact at the document-geometry level without
 * adding fake cover elements or hiding the problem behind another layer.
 *
 * Do not remove this crop unless Chromium/Gotenberg fixes the pixel-to-point
 * mismatch in the print pipeline and the generated badge PDF is re-measured.
 */

final class BadgePdfMediaBoxCropper
{
    /**
     * Trim the unpainted bottom band Chromium leaves inside the emitted MediaBox.
     *
     * Gotenberg/Chromium emits the badge page as 242.88pt x 154.08pt, but paints
     * only the integer CSS-pixel page area. Moving the lower MediaBox edge from
     * 0pt to 1pt crops that unpainted renderer band while keeping the painted top
     * edge and existing PDF byte offsets intact.
     */
    public static function cropBottomWhitespace(string $pdfContent): string
    {
        $replacements = [
            '/MediaBox [0 0 242.87999 154.080002]' => '/MediaBox [0 1 242.87999 154.080002]',
            '/MediaBox [0 0 242.88 154.08]' => '/MediaBox [0 1 242.88 154.08]',
        ];

        foreach ($replacements as $search => $replace) {
            if (strlen($search) !== strlen($replace)) {
                continue;
            }

            $position = strpos($pdfContent, $search);

            if ($position === false) {
                continue;
            }

            return substr_replace($pdfContent, $replace, $position, strlen($search));
        }

        return $pdfContent;
    }
}
