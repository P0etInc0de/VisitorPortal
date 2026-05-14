<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class VisitorBadgeViewTest extends TestCase
{
    public function test_default_badge_design_renders_standard_layout_without_photo_or_qr_slots(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge();

        $this->assertStringContainsString('badge badge--standard', $html);
        $this->assertStringContainsString('Besucherausweis', $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringContainsString('Unternehmen:', $html);
        $this->assertStringContainsString('VisitorPortal GmbH', $html);
        $this->assertStringContainsString('14.05.2026 | 09:00 - 10:00 Uhr', $html);
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

        $this->assertStringContainsString('--badge-accent: #123456;', $html);
        $this->assertStringContainsString('fill="#123456"', $html);
    }

    public function test_invalid_badge_accent_color_falls_back_to_default(): void
    {
        config()->set('branding.badge_design', 'standard');
        config()->set('branding.badge_accent_color', 'not-a-color');

        $html = $this->renderBadge();

        $this->assertStringContainsString('--badge-accent: #ff8a00;', $html);
        $this->assertStringContainsString('fill="#ff8a00"', $html);
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
