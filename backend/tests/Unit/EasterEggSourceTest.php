<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use Tests\TestCase;

class EasterEggSourceTest extends TestCase
{
    public function test_easter_egg_config_is_disabled_outside_local_by_default(): void
    {
        $this->assertFalse((bool) config('visitorportal.easter_egg.enabled'));
        $this->assertFalse((bool) config('visitorportal.easter_egg.show_in_production'));
    }

    public function test_easter_egg_entry_is_only_loaded_through_configured_blade_gate(): void
    {
        foreach ([
            'app/Providers/Filament/AdminPanelProvider.php',
            'resources/views/layouts/app.blade.php',
            'resources/views/layouts/guest.blade.php',
            'resources/views/monitor/show.blade.php',
        ] as $view) {
            $source = $this->source($view);

            $this->assertStringContainsString("config('visitorportal.easter_egg.enabled')", $source);
            $this->assertStringContainsString("! app()->environment('production') || config('visitorportal.easter_egg.show_in_production')", $source);
            $this->assertStringContainsString("@vite('resources/js/easter-egg.ts')", $source);
        }
    }

    public function test_easter_egg_entry_stays_console_only_and_harmless(): void
    {
        $source = $this->source('resources/js/easter-egg.ts');
        preg_match_all('/console\.(\w+)/', $source, $consoleCalls);

        $this->assertSame(['log'], array_values(array_unique($consoleCalls[1])));

        foreach ([
            'hackthemainframe',
            'critical vulnerability detected',
            'access granted',
            'mainframe',
            'exploit',
            'hacking',
            'terminal',
            'vulnerability',
            'console.clear',
            'console.debug',
            'console.error',
            'console.warn',
            'document.cookie',
            'fetch(',
            'xmlhttprequest',
            'location.replace',
            'location.assign',
        ] as $fragment) {
            $this->assertStringNotContainsString($fragment, strtolower($source));
        }
    }

    public function test_easter_egg_uses_expanded_message_pool_with_dosed_repeats(): void
    {
        $source = $this->source('resources/js/easter-egg.ts');

        foreach ([
            'Badgebert sagt: Check-in ist kein Gefühl, sondern ein Status.',
            'Badgebert am Empfang: Erst suchen, dann einchecken, dann triumphieren.',
            'Badgebert stempelt: Ausweis wird nur für echte Teilnehmer erzeugt.',
            'Badgebert begrüßt: Heute auf dem Monitor, morgen in der Datenbankhistorie.',
            'Gatekeeper Badgebert: Sichtbarkeit ist nett, Policy ist besser.',
            'Pint war hier. Es sieht jetzt zumindest formatiert aus.',
        ] as $message) {
            $this->assertStringContainsString($message, $source);
        }

        $this->assertStringContainsString('const navigationCooldownMilliseconds = 90_000;', $source);
        $this->assertStringContainsString('const sessionMessageLimit = 18;', $source);
        $this->assertStringContainsString("document.addEventListener('livewire:navigated'", $source);
        $this->assertStringContainsString('message.used.${context}', $source);
    }

    public function test_old_runtime_channel_is_not_referenced(): void
    {
        $this->assertFileDoesNotExist(base_path('resources/js/runtime/channel.js'));
        $this->assertStringNotContainsString('./runtime/channel', $this->source('resources/js/app.js'));
    }

    private function source(string $path): string
    {
        return (string) file_get_contents(base_path($path));
    }
}
