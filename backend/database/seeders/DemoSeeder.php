<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Make Roles
        $this->command?->info('Create Roles');

        $roleEmployee = Role::firstOrCreate(['name' => 'employee']);
        $roleAdmin = Role::firstOrCreate(['name' => 'super_admin']);

        // Make Users
        $hasDepartmentColumn = Schema::hasColumn('users', 'department');
        $defaultPassword = 'Passwort123!';

        $this->createOrUpdateUser(
            name: 'Admin Demo',
            email: 'admin@demo.local',
            password: $defaultPassword,
            department: 'IT/Administration',
            hasDepartmentColumn: $hasDepartmentColumn,
        )->assignRole($roleAdmin);

        $this->createOrUpdateUser(
            name: 'Empfang Demo',
            email: 'empfang@demo.local',
            password: $defaultPassword,
            department: 'Empfang',
            hasDepartmentColumn: $hasDepartmentColumn,
        );

        $this->createOrUpdateUser(
            name: 'Mitarbeiter Demo',
            email: 'mitarbeiter@demo.local',
            password: $defaultPassword,
            department: 'Vertrieb',
            hasDepartmentColumn: $hasDepartmentColumn,
        )->assignRole($roleEmployee);

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
        string $department,
        bool $hasDepartmentColumn
    ): User|Model {
        $data = [
            'name' => $name,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
        ];

        if ($hasDepartmentColumn) {
            $data['department'] = $department;
        }

        return User::query()->updateOrCreate(
            ['email' => $email],
            $data
        );
    }
}
