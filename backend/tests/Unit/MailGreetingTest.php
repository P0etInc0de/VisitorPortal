<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Tests\Unit;

use App\Enums\GenderEnum;
use App\Enums\SalutationEnum;
use App\Models\User;
use App\Models\Visitor;
use App\Support\MailGreeting;
use Tests\TestCase;

class MailGreetingTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        app()->setLocale('de');
    }

    public function test_user_greeting_omits_missing_title_spacing(): void
    {
        $user = new User([
            'first_name' => 'Ada',
            'name' => 'Lovelace',
            'gender' => GenderEnum::Male,
        ]);

        $this->assertSame('Guten Tag Herr Lovelace,', MailGreeting::forUser($user));
        $this->assertStringNotContainsString('  ', MailGreeting::forUser($user));
    }

    public function test_user_greeting_includes_title(): void
    {
        $user = new User([
            'first_name' => 'Marie',
            'name' => 'Curie',
            'title' => 'Prof. Dr.',
            'gender' => GenderEnum::Female,
        ]);

        $this->assertSame('Guten Tag Frau Prof. Dr. Curie,', MailGreeting::forUser($user));
    }

    public function test_user_greeting_uses_full_name_for_neutral_gender(): void
    {
        $user = new User([
            'first_name' => 'Alex',
            'name' => 'Morgan',
            'gender' => GenderEnum::Not_Specified,
        ]);

        $this->assertSame('Guten Tag Alex Morgan,', MailGreeting::forUser($user));
    }

    public function test_visitor_greeting_uses_correct_feminine_form(): void
    {
        $visitor = new Visitor([
            'first_name' => 'Lise',
            'name' => 'Meitner',
            'title' => 'Dr.',
            'salutation' => SalutationEnum::Ms,
        ]);

        $this->assertSame('Sehr geehrte Frau Dr. Meitner,', MailGreeting::forVisitor($visitor));
    }

    public function test_visitor_greeting_supports_male_and_neutral_salutations(): void
    {
        $maleVisitor = new Visitor([
            'first_name' => 'Max',
            'name' => 'Planck',
            'salutation' => SalutationEnum::Mr,
        ]);
        $neutralVisitor = new Visitor([
            'first_name' => 'Sam',
            'name' => 'Taylor',
            'salutation' => SalutationEnum::NotSpecified,
        ]);

        $this->assertSame('Sehr geehrter Herr Planck,', MailGreeting::forVisitor($maleVisitor));
        $this->assertSame('Guten Tag Sam Taylor,', MailGreeting::forVisitor($neutralVisitor));
        $this->assertSame('Guten Tag Max Planck,', MailGreeting::forVisitor($maleVisitor, formal: false));
    }

    public function test_mail_templates_use_central_greeting(): void
    {
        $templates = [
            resource_path('views/mail/host/guestCheckedIn.blade.php'),
            resource_path('views/mail/host/visitCreated.blade.php'),
            resource_path('views/mail/host/visitReminderDaily.blade.php'),
            resource_path('views/mail/guest/visitCreated.blade.php'),
        ];

        foreach ($templates as $template) {
            $contents = file_get_contents($template);

            $this->assertIsString($contents);
            $this->assertStringContainsString('{{ $greeting }}', $contents);
            $this->assertStringNotContainsString('GenderEnum', $contents);
            $this->assertStringNotContainsString('SalutationEnum', $contents);
            $this->assertStringNotContainsString('$user->gender', $contents);
            $this->assertStringNotContainsString('$visitor->salutation', $contents);
        }
    }
}
