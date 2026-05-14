<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Support\BadgePdfDimensions;
use App\Support\BadgePdfMediaBoxCropper;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitorBadgeViewTest extends TestCase
{
    public function test_default_badge_design_renders_standard_layout_without_photo_or_qr_slots(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge();

        $this->assertStringContainsString('badge badge--standard', $html);
        $this->assertStringContainsString(__('Besucherausweis'), $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringContainsString('Unternehmen:', $html);
        $this->assertStringContainsString('VisitorPortal GmbH', $html);
        $this->assertStringContainsString('14.05.2026', $html);
        $this->assertStringContainsString('09:00 bis 10:00 Uhr', $html);
        $this->assertStringNotContainsString('Name:', $html);
        $this->assertStringNotContainsString('Firma:', $html);
        $this->assertStringNotContainsString('class="badge-photo', $html);
        $this->assertStringNotContainsString('class="qr-code', $html);
    }

    public function test_standard_badge_keeps_unternehmen_label(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge();

        $this->assertStringContainsString('Unternehmen:', $html);
        $this->assertStringNotContainsString('Firma:', $html);
    }

    public function test_badge_accent_color_is_configurable(): void
    {
        config()->set('branding.badge_design', 'standard');
        config()->set('branding.badge_accent_color', '#123456');

        $html = $this->renderBadge();

        $this->assertStringContainsString(rawurlencode('stop-color="#123456"'), $html);
    }

    public function test_invalid_badge_accent_color_falls_back_to_default(): void
    {
        config()->set('branding.badge_design', 'standard');
        config()->set('branding.badge_accent_color', 'not-a-color');

        $html = $this->renderBadge();

        $this->assertStringContainsString(rawurlencode('stop-color="#ff8a00"'), $html);
    }

    public function test_badge_html_uses_measured_pdf_media_box_size(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge();
        $width = BadgePdfDimensions::cssMediaWidth();
        $height = BadgePdfDimensions::cssMediaHeight();

        $this->assertStringContainsString("size: {$width} {$height};", $html);
        $this->assertStringContainsString("width: {$width};", $html);
        $this->assertStringContainsString("height: {$height};", $html);
        $this->assertStringContainsString('/ 100% 100% no-repeat', $html);
        $this->assertStringContainsString('background: url("data:image/svg+xml,', $html);
        $this->assertStringContainsString(rawurlencode('viewBox="21.993 21.878 856.014 540.244"'), $html);
        $this->assertStringContainsString('background: transparent;', $html);
        $this->assertStringNotContainsString('page-edge-fill', $html);
        $this->assertStringNotContainsString('accent-edge-fill', $html);
        $this->assertStringNotContainsString('badge-accent-bar', $html);
        $this->assertStringNotContainsString('badge-accent-slope', $html);
        $this->assertStringNotContainsString('badge-bottom-wave', $html);
        $this->assertStringNotContainsString('badge-surface', $html);
        $this->assertStringNotContainsString('<svg', $html);
        $this->assertStringNotContainsString('stop-color=', $html);
        $this->assertStringNotContainsString('calc(', $html);
        $this->assertStringNotContainsString('var(', $html);
        $this->assertStringNotContainsString('--badge-accent', $html);
        $this->assertSame(242.88, BadgePdfDimensions::REQUEST_WIDTH_PT);
        $this->assertSame(153.12, BadgePdfDimensions::REQUEST_HEIGHT_PT);
        $this->assertSame(1.0, BadgePdfDimensions::FINAL_MEDIA_LOWER_Y_PT);
    }

    public function test_photo_qr_badge_design_renders_prepared_photo_and_qr_slots(): void
    {
        config()->set('branding.badge_design', 'photo_qr');

        $html = $this->renderBadge();

        $this->assertStringContainsString('badge badge--photo_qr', $html);
        $this->assertStringContainsString('<div class="badge-photo badge-photo--placeholder">Foto</div>', $html);
        $this->assertStringContainsString('<div class="qr-code qr-code--placeholder">QR</div>', $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringNotContainsString('Name:', $html);
    }

    public function test_unknown_badge_design_falls_back_to_standard_layout(): void
    {
        config()->set('branding.badge_design', 'unknown');

        $html = $this->renderBadge();

        $this->assertStringContainsString('badge badge--standard', $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringNotContainsString('Name:', $html);
        $this->assertStringNotContainsString('class="badge-photo', $html);
    }

    public function test_badge_pdf_media_box_crop_removes_bottom_renderer_band_without_shifting_offsets(): void
    {
        $pdf = "2 0 obj\n<</MediaBox [0 0 242.87999 154.080002]\n/Contents 9 0 R>>\nendobj";

        $cropped = BadgePdfMediaBoxCropper::cropBottomWhitespace($pdf);

        $this->assertSame(strlen($pdf), strlen($cropped));
        $this->assertStringContainsString('/MediaBox [0 1 242.87999 154.080002]', $cropped);
        $this->assertStringNotContainsString('/MediaBox [0 0 242.87999 154.080002]', $cropped);
    }

    private function renderBadge(): string
    {
        $visitor = new Visitor;
        $visitor->title = 'Dr.';
        $visitor->first_name = 'Anna';
        $visitor->name = 'Ausweis';
        $visitor->company = 'VisitorPortal GmbH';

        $responsibleUser = new User;
        $responsibleUser->first_name = 'Erika';
        $responsibleUser->name = 'Empfang';

        $visit = new Visit;
        $visit->scheduled_from = Carbon::create(2026, 5, 14, 9, 0, 0, config('app.timezone'));
        $visit->scheduled_until = Carbon::create(2026, 5, 14, 10, 0, 0, config('app.timezone'));
        $visit->setRelation('responsibleUser', $responsibleUser);

        return view('pdf.visitor_badge', [
            'visit' => $visit,
            'visitor' => $visitor,
        ])->render();
    }
}
