<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature;

use App\Models\User;
use App\Models\Visit;
use App\Models\Visitor;
use App\Support\BadgePdfDimensions;
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
        $this->assertStringContainsString(__('Unternehmen:'), $html);
        $this->assertStringContainsString('VisitorPortal GmbH', $html);
        $this->assertStringContainsString('14.05.2026', $html);
        $this->assertStringContainsString(e('09:00 '.__('bis').' 10:00 '.__('Uhr')), $html);
        $this->assertStringNotContainsString(__('Name:'), $html);
        $this->assertStringNotContainsString('Firma:', $html);
        $this->assertStringNotContainsString('class="badge-photo', $html);
        $this->assertStringNotContainsString('class="qr-code', $html);
    }

    public function test_standard_badge_keeps_unternehmen_label(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge();

        $this->assertStringContainsString(__('Unternehmen:'), $html);
        $this->assertStringNotContainsString('Firma:', $html);
    }

    public function test_badge_uses_host_as_supervisor_when_substitute_exists(): void
    {
        config()->set('branding.badge_design', 'standard');

        $html = $this->renderBadge(
            hostFirstName: 'Ada',
            hostLastName: 'Avery',
            substituteFirstName: 'Rita',
            substituteLastName: 'Reed'
        );

        $this->assertStringContainsString('Ada Avery', $html);
        $this->assertStringNotContainsString('Rita Reed', $html);
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
        $this->assertStringContainsString('<div class="badge-photo badge-photo--placeholder">'.__('Foto').'</div>', $html);
        $this->assertStringContainsString('<div class="qr-code qr-code--placeholder">QR</div>', $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringNotContainsString(__('Name:'), $html);
    }

    public function test_unknown_badge_design_falls_back_to_standard_layout(): void
    {
        config()->set('branding.badge_design', 'unknown');

        $html = $this->renderBadge();

        $this->assertStringContainsString('badge badge--standard', $html);
        $this->assertStringContainsString('Dr. Anna Ausweis', $html);
        $this->assertStringNotContainsString(__('Name:'), $html);
        $this->assertStringNotContainsString('class="badge-photo', $html);
    }

    private function renderBadge(
        string $hostFirstName = 'Hanna',
        string $hostLastName = 'Host',
        string $substituteFirstName = 'Erika',
        string $substituteLastName = 'Empfang'
    ): string {
        $visitor = new Visitor;
        $visitor->title = 'Dr.';
        $visitor->first_name = 'Anna';
        $visitor->name = 'Ausweis';
        $visitor->company = 'VisitorPortal GmbH';

        $host = new User;
        $host->first_name = $hostFirstName;
        $host->name = $hostLastName;

        $substituteUser = new User;
        $substituteUser->first_name = $substituteFirstName;
        $substituteUser->name = $substituteLastName;

        $visit = new Visit;
        $visit->scheduled_from = Carbon::create(2026, 5, 14, 9, 0, 0, config('app.timezone'));
        $visit->scheduled_until = Carbon::create(2026, 5, 14, 10, 0, 0, config('app.timezone'));
        $visit->setRelation('host', $host);
        $visit->setRelation('substituteUser', $substituteUser);

        return view('pdf.visitor_badge', [
            'visit' => $visit,
            'visitor' => $visitor,
        ])->render();
    }
}
