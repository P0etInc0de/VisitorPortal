<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Feature\Kiosk;

use App\Enums\VisitStatusEnum;
use App\Livewire\Kiosk\KioskTerminal;
use App\Models\Kiosk;
use App\Models\Site;
use App\Models\Visit;
use App\Models\Visitor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;
use Tests\TestCase;

class KioskTerminalTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_kiosk_token_returns_not_found(): void
    {
        $this->get(route('kiosk.show', 'does-not-exist'))->assertNotFound();
    }

    public function test_inactive_kiosk_returns_not_found(): void
    {
        $kiosk = Kiosk::factory()->create(['is_active' => false]);

        $this->get(route('kiosk.show', $kiosk->token))->assertNotFound();
    }

    public function test_active_kiosk_renders_publicly_without_login(): void
    {
        $kiosk = Kiosk::factory()->create();

        $this->get(route('kiosk.show', $kiosk->token))
            ->assertOk()
            ->assertSee(__('Herzlich willkommen'));
    }

    public function test_guest_can_check_in_via_search_without_a_user_account(): void
    {
        $kiosk = Kiosk::factory()->create();
        [$visit, $visitor] = $this->plannedVisit($kiosk, 'Anna', 'Beispiel');

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showSearch')
            ->set('search', 'Anna')
            ->assertSee('Anna Beispiel')
            ->call('checkInVisitor', $visit->id, $visitor->id)
            ->assertSet('screen', 'success');

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;

        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNull($pivot->checked_in_by_user_id);
    }

    public function test_guest_can_check_in_with_a_qr_code(): void
    {
        $kiosk = Kiosk::factory()->create();
        [$visit, $visitor] = $this->plannedVisit($kiosk, 'Berta', 'Code');

        $this->assertNotNull($visit->check_in_code);

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showQr')
            ->set('code', $visit->check_in_code)
            ->call('submitCode')
            ->assertSet('screen', 'success');

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;

        $this->assertNotNull($pivot->checked_in_at);
    }

    public function test_qr_code_from_another_site_is_rejected(): void
    {
        $kiosk = Kiosk::factory()->create();
        $otherSite = Site::factory()->create();
        $otherVisit = Visit::factory()->create([
            'site_id' => $otherSite->id,
            'status' => VisitStatusEnum::Planned->value,
        ]);

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showQr')
            ->set('code', $otherVisit->check_in_code)
            ->call('submitCode')
            ->assertHasErrors('code');
    }

    public function test_guest_can_check_out_again(): void
    {
        $kiosk = Kiosk::factory()->create();
        [$visit, $visitor] = $this->plannedVisit($kiosk, 'Carla', 'Checkout');
        $visit->visitors()->updateExistingPivot($visitor->id, ['checked_in_at' => now()]);

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showCheckout')
            ->set('search', 'Carla')
            ->call('checkOutVisitor', $visit->id, $visitor->id)
            ->assertSet('screen', 'success');

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;

        $this->assertNotNull($pivot->checked_out_at);
    }

    public function test_walk_in_self_registration_creates_and_checks_in_a_visit(): void
    {
        $kiosk = Kiosk::factory()->create();
        $host = $kiosk->walkInHost;

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showWalkIn')
            ->set('walkIn.first_name', 'Dora')
            ->set('walkIn.name', 'Direkt')
            ->set('walkIn.company', 'Acme')
            ->call('registerWalkIn')
            ->assertSet('screen', 'success');

        $visit = Visit::query()->where('is_walk_in', true)->sole();

        $this->assertSame($kiosk->site_id, $visit->site_id);
        $this->assertSame($host->id, $visit->host_user_id);

        $visitor = $visit->visitors()->sole();

        $this->assertSame('Dora', $visitor->first_name);
        $this->assertSame('Acme', $visitor->company);
        $this->assertNotNull($visitor->pivot->checked_in_at);
    }

    public function test_walk_in_option_is_hidden_without_a_configured_host(): void
    {
        $kiosk = Kiosk::factory()->create(['walk_in_host_user_id' => null]);

        $this->get(route('kiosk.show', $kiosk->token))
            ->assertOk()
            ->assertDontSee(__('Ich habe keinen Termin'));
    }

    public function test_search_is_scoped_to_the_kiosk_site(): void
    {
        $kiosk = Kiosk::factory()->create();
        $otherSite = Site::factory()->create();
        $this->plannedVisitForSite($otherSite, 'Zoe', 'Fremd');

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showSearch')
            ->set('search', 'Zoe')
            ->assertDontSee('Zoe Fremd');
    }

    public function test_search_only_lists_matching_participants(): void
    {
        $kiosk = Kiosk::factory()->create();
        $visit = Visit::factory()->create([
            'site_id' => $kiosk->site_id,
            'scheduled_from' => now(),
            'scheduled_until' => now()->addHour(),
            'status' => VisitStatusEnum::Planned->value,
        ]);

        $others = collect(range(1, 20))->map(fn (int $index): Visitor => Visitor::factory()->create([
            'title' => '',
            'first_name' => 'Gast',
            'name' => 'Nummer '.$index,
        ]));

        $needle = Visitor::factory()->create([
            'title' => '',
            'first_name' => 'Zzz',
            'name' => 'Needle',
        ]);

        $visit->visitors()->attach($others->pluck('id')->all());
        $visit->visitors()->attach($needle->id);

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showSearch')
            ->set('search', 'Needle')
            ->assertSee('Zzz Needle')
            ->assertDontSee('Gast Nummer 1');
    }

    public function test_check_in_requires_a_signature_when_enabled(): void
    {
        $kiosk = Kiosk::factory()->create(['require_signature' => true]);
        [$visit, $visitor] = $this->plannedVisit($kiosk, 'Signe', 'Stift');

        $component = Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showSearch')
            ->set('search', 'Signe')
            ->call('checkInVisitor', $visit->id, $visitor->id)
            ->assertSet('screen', 'signature');

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;
        $this->assertNull($pivot->checked_in_at);

        $component
            ->call('confirmSignature')
            ->assertHasErrors('signature');

        $component
            ->set('signatureData', 'data:image/png;base64,'.base64_encode('signature'))
            ->call('confirmSignature')
            ->assertSet('screen', 'success')
            ->assertHasNoErrors();

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;
        $this->assertNotNull($pivot->checked_in_at);
        $this->assertNotNull($pivot->signed_at);
        $this->assertStringStartsWith('data:image/png;base64,', (string) $pivot->signature_data);
    }

    public function test_walk_in_requires_a_signature_when_enabled(): void
    {
        $kiosk = Kiosk::factory()->create(['require_signature' => true]);

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showWalkIn')
            ->set('walkIn.first_name', 'Wanda')
            ->set('walkIn.name', 'Walkin')
            ->call('registerWalkIn')
            ->assertSet('screen', 'signature');
    }

    public function test_signature_flow_can_be_cancelled(): void
    {
        $kiosk = Kiosk::factory()->create(['require_signature' => true]);
        [$visit, $visitor] = $this->plannedVisit($kiosk, 'Cara', 'Cancel');

        Livewire::test(KioskTerminal::class, ['token' => $kiosk->token])
            ->call('showSearch')
            ->set('search', 'Cara')
            ->call('checkInVisitor', $visit->id, $visitor->id)
            ->assertSet('screen', 'signature')
            ->call('cancelSignature')
            ->assertSet('screen', 'home');

        $pivot = $visit->visitors()->where('visitor_id', $visitor->id)->first()->pivot;
        $this->assertNull($pivot->checked_in_at);
    }

    /**
     * @return array{0: Visit, 1: Visitor}
     */
    private function plannedVisit(Kiosk $kiosk, string $firstName, string $lastName): array
    {
        return $this->plannedVisitForSite($kiosk->site, $firstName, $lastName);
    }

    /**
     * @return array{0: Visit, 1: Visitor}
     */
    private function plannedVisitForSite(Site $site, string $firstName, string $lastName): array
    {
        $visit = Visit::factory()->create([
            'site_id' => $site->id,
            'scheduled_from' => now(),
            'scheduled_until' => now()->addHour(),
            'status' => VisitStatusEnum::Planned->value,
        ]);

        $visitor = Visitor::factory()->create([
            'title' => '',
            'first_name' => $firstName,
            'name' => $lastName,
        ]);

        $visit->visitors()->attach($visitor->id);

        return [$visit, $visitor];
    }
}
