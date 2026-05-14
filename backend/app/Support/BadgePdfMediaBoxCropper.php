<?php

namespace App\Support;

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
