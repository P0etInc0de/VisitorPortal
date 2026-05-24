<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Models\Department;
use App\Models\Site;
use App\Models\User;
use Faker\Factory as FakerFactory;
use Faker\Generator;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo users are disabled in production.');
        }

        // Demo credentials must never be available in production.
        $defaultPassword = 'ChangeMe-42!';

        $users = [
            ['Ada', 'Avery', GenderEnum::Female, null, 'admin@example.org', 'IT/Administration', 'admin'],
            ['Rita', 'Reed', GenderEnum::Female, null, 'reception@example.org', 'Empfang', 'receptionist'],
            ['Evan', 'Edwards', GenderEnum::Male, null, 'employee@example.org', 'Vertrieb', 'user'],
            ['Martha', 'Miller', GenderEnum::Female, null, 'manager@example.org', 'Management', 'manager'],
            ['Wendy', 'Walker', GenderEnum::Female, null, 'welcome@example.org', 'Empfang', 'welcome monitor'],
            ['Arthur', 'Alden', GenderEnum::Male, null, 'security@example.org', 'Facility', 'receptionist'],
        ];

        $faker = FakerFactory::create('en_US');
        $faker->seed(20260516);

        foreach ($this->fakerUsers($faker, 20) as $user) {
            $users[] = $user;
        }

        foreach ($users as [$firstName, $name, $gender, $title, $email, $department, $role]) {
            $this->createOrUpdateUser($name, $firstName, $gender, $title, $email, $defaultPassword, $department, $role);
        }

        $this->command?->info('UserSeeder ausgeführt: 26 Demo-Benutzer angelegt/aktualisiert.');
        $this->command?->info('Demo-Login (alle): ChangeMe-42!');
        $this->command?->line('- admin@example.org');
        $this->command?->line('- reception@example.org');
        $this->command?->line('- employee@example.org');
        $this->command?->line('- manager@example.org');
        $this->command?->line('- welcome@example.org');
        $this->command?->line('- security@example.org');
    }

    /**
     * @return array<int, array{string, string, GenderEnum, ?string, string, string, string}>
     */
    private function fakerUsers(Generator $faker, int $count): array
    {
        $departments = ['IT/Administration', 'Empfang', 'Vertrieb', 'Forschung & Entwicklung', 'Facility', 'Management'];
        $roles = ['user', 'user', 'user', 'manager', 'receptionist'];
        $domains = ['example.org', 'example.com', 'example.net'];
        $users = [];

        for ($index = 1; $index <= $count; $index++) {
            $gender = $faker->randomElement([GenderEnum::Female, GenderEnum::Male, GenderEnum::Not_Specified]);
            $users[] = [
                $faker->firstName(),
                $faker->lastName(),
                $gender,
                null,
                sprintf('demo.user%02d@%s', $index, $domains[($index - 1) % count($domains)]),
                $departments[($index - 1) % count($departments)],
                $roles[($index - 1) % count($roles)],
            ];
        }

        return $users;
    }

    private function createOrUpdateUser(
        string $name,
        string $firstName,
        GenderEnum $gender,
        ?string $title,
        string $email,
        string $password,
        string $department,
        ?string $role = null,
    ): void {
        $site = Site::default();
        $dept = Department::query()
            ->where('site_id', $site->id)
            ->where('name', $department)
            ->first();
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'site_id' => $site->id,
                'name' => $name,
                'first_name' => $firstName,
                'gender' => $gender,
                'title' => $title,
                'email_verified_at' => now(),
                'password' => Hash::make($password),
                'department_id' => $dept?->id,
            ]
        );

        if ($role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
            $user->syncRoles([$role]);
        }
    }
}
