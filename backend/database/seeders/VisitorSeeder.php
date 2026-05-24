<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Enums\SalutationEnum;
use App\Models\Visitor;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo visitors are disabled in production.');
        }

        $visitors = [
            ['Alice', 'Reed', null, SalutationEnum::Ms, 'alice.reed@example.com', $this->demoPhone(0), 'Example Industries', null],
            ['Dorothy', 'Lane', null, SalutationEnum::Ms, 'dorothy.lane@example.org', $this->demoPhone(1), 'Northbridge Consulting', null],
            ['Clara', 'Bennett', null, SalutationEnum::Ms, 'clara.bennett@example.net', $this->demoPhone(2), 'Greenfield Engineering', null],
            ['Arthur', 'Blake', null, SalutationEnum::Mr, 'arthur.blake@example.com', $this->demoPhone(3), 'Blue River Systems', null],
            ['Emily', 'Wright', null, SalutationEnum::Ms, 'emily.wright@example.org', $this->demoPhone(4), 'Silver Oak Manufacturing', null],
            ['John', 'Spencer', null, SalutationEnum::Mr, 'john.spencer@example.net', $this->demoPhone(5), 'Sample Logistics', null],
            ['Jane', 'Miller', null, SalutationEnum::Ms, 'jane.miller@example.com', null, 'DemoTech Solutions', null],
            ['Thomas', 'Green', null, SalutationEnum::Mr, 'thomas.green@example.org', $this->demoPhone(6), 'Clearwater Labs', null],
            ['Lucy', 'Walker', null, SalutationEnum::Ms, 'lucy.walker@example.net', $this->demoPhone(7), 'Horizon Components', null],
            ['Walter', 'Brooks', null, SalutationEnum::Mr, 'walter.brooks@example.com', $this->demoPhone(8), 'Rivergate Solutions', null],
        ];

        $faker = FakerFactory::create('en_US');
        $faker->seed(20260516);

        foreach ($this->fakerVisitors($faker, 50, count($visitors)) as $visitor) {
            $visitors[] = $visitor;
        }

        foreach ($visitors as [$firstName, $name, $title, $salutation, $email, $phone, $company, $notes]) {
            $this->createOrUpdateVisitor($firstName, $name, $title, $salutation, $email, $phone, $company, $notes);
        }

        $this->command?->info('VisitorSeeder ausgeführt: 60 Demo-Gäste angelegt/aktualisiert.');
    }

    /**
     * @return array<int, array{string, string, ?string, SalutationEnum, string, ?string, string, ?string}>
     */
    private function fakerVisitors(Generator $faker, int $count, int $phoneOffset): array
    {
        $domains = ['example.org', 'example.com', 'example.net'];
        $companyPrefixes = ['Atlas', 'Northstar', 'Greenfield', 'Blue River', 'Clearwater', 'Horizon', 'Silver Oak', 'Rivergate', 'Brightline', 'Cedar'];
        $companySuffixes = ['Systems', 'Components', 'Consulting', 'Engineering', 'Logistics', 'Manufacturing', 'Labs', 'Services'];
        $visitors = [];

        for ($index = 1; $index <= $count; $index++) {
            $visitors[] = [
                $faker->firstName(),
                $faker->lastName(),
                $index % 13 === 0 ? 'Dr.' : null,
                $faker->randomElement([SalutationEnum::Mr, SalutationEnum::Ms, SalutationEnum::NotSpecified]),
                sprintf('visitor%02d@%s', $index, $domains[($index - 1) % count($domains)]),
                $index % 10 === 0 ? null : $this->demoPhone($phoneOffset + $index),
                $companyPrefixes[($index - 1) % count($companyPrefixes)].' '.$companySuffixes[intdiv($index - 1, count($companyPrefixes)) % count($companySuffixes)],
                null,
            ];
        }

        return $visitors;
    }

    private function demoPhone(int $index): string
    {
        return sprintf('+493023125%03d', $index % 100);
    }

    private function createOrUpdateVisitor(string $firstName, string $name, ?string $title, SalutationEnum $salutation, string $email, ?string $phone, ?string $company, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'name' => $name,
                'title' => $title,
                'salutation' => $salutation,
                'phone' => $phone,
                'company' => $company,
                'notes' => $notes,
            ]
        );
    }
}
