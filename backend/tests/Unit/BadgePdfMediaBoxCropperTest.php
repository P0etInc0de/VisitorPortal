<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use App\Support\BadgePdfMediaBoxCropper;
use PHPUnit\Framework\TestCase;

class BadgePdfMediaBoxCropperTest extends TestCase
{
    public function test_it_trims_the_media_box_bottom_edge_without_touching_the_pdf_structure(): void
    {
        // Test the MediaBox amputation directly: Chromium's renderer artifact
        // cannot be reliably asserted with pixel-color matching because a valid
        // badge design may intentionally contain white areas.
        foreach ([
            '/MediaBox [0 0 242.88 154.08]' => '/MediaBox [0 1 242.88 154.08]',
            '/MediaBox [0 0 242.87999 154.080002]' => '/MediaBox [0 1 242.87999 154.080002]',
        ] as $originalMediaBox => $croppedMediaBox) {
            $pdf = $this->dummyPdfWithMediaBox($originalMediaBox);

            $cropped = BadgePdfMediaBoxCropper::cropBottomWhitespace($pdf);

            $this->assertSame(strlen($pdf), strlen($cropped));
            $this->assertStringContainsString($croppedMediaBox, $cropped);
            $this->assertStringNotContainsString($originalMediaBox, $cropped);
            $this->assertStringContainsString('<< /Type /Catalog /Pages 2 0 R >>', $cropped);
            $this->assertStringContainsString('<< /Root 1 0 R /Size 5 >>', $cropped);
            $this->assertStringContainsString("stream\nq\nQ\nendstream", $cropped);
            $this->assertStringContainsString("startxref\n123", $cropped);
            $this->assertSame($pdf, str_replace($croppedMediaBox, $originalMediaBox, $cropped));
        }
    }

    public function test_it_leaves_unrecognized_pdf_bytes_unchanged(): void
    {
        $pdf = $this->dummyPdfWithMediaBox('/MediaBox [0 0 200 100]');

        $this->assertSame($pdf, BadgePdfMediaBoxCropper::cropBottomWhitespace($pdf));
    }

    private function dummyPdfWithMediaBox(string $mediaBox): string
    {
        return implode("\n", [
            '%PDF-1.4',
            '1 0 obj',
            '<< /Type /Catalog /Pages 2 0 R >>',
            'endobj',
            '2 0 obj',
            '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
            'endobj',
            '3 0 obj',
            "<< /Type /Page /Parent 2 0 R {$mediaBox} /Contents 4 0 R >>",
            'endobj',
            '4 0 obj',
            '<< /Length 3 >>',
            'stream',
            'q',
            'Q',
            'endstream',
            'endobj',
            'xref',
            '0 5',
            '0000000000 65535 f ',
            'trailer',
            '<< /Root 1 0 R /Size 5 >>',
            'startxref',
            '123',
            '%%EOF',
            '',
        ]);
    }
}
