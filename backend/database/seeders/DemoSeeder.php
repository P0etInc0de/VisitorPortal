<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        $hasRoleColumn = Schema::hasColumn('users', 'role');
        $hasDepartmentColumn = Schema::hasColumn('users', 'department');

        $defaultPassword = 'Passwort123!';

        $this->createOrUpdateUser(
            name: 'Admin Demo',
            email: 'admin@demo.local',
            password: $defaultPassword,
            role: 'admin',
            department: 'IT/Administration',
            hasRoleColumn: $hasRoleColumn,
            hasDepartmentColumn: $hasDepartmentColumn,
        );

        $this->createOrUpdateUser(
            name: 'Empfang Demo',
            email: 'empfang@demo.local',
            password: $defaultPassword,
            role: 'reception',
            department: 'Empfang',
            hasRoleColumn: $hasRoleColumn,
            hasDepartmentColumn: $hasDepartmentColumn,
        );

        $this->createOrUpdateUser(
            name: 'Mitarbeiter Demo',
            email: 'mitarbeiter@demo.local',
            password: $defaultPassword,
            role: 'employee',
            department: 'Vertrieb',
            hasRoleColumn: $hasRoleColumn,
            hasDepartmentColumn: $hasDepartmentColumn,
        );

        $this->command?->info('DemoSeeder ausgeführt.');
        $this->command?->info('Demo-Login (alle): Passwort123!');
        $this->command?->line('- admin@demo.local');
        $this->command?->line('- empfang@demo.local');
        $this->command?->line('- mitarbeiter@demo.local');
    }

    private function createOrUpdateUser(
        string $name,
        string $email,
        string $password,
        string $role,
        string $department,
        bool $hasRoleColumn,
        bool $hasDepartmentColumn
    ): void {
        $data = [
            'name' => $name,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ];

        if ($hasRoleColumn) {
            $data['role'] = $role;
        }

        if ($hasDepartmentColumn) {
            $data['department'] = $department;
        }

        User::query()->updateOrCreate(
            ['email' => $email],
            $data
        );
    }
}
