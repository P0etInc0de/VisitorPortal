<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
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
        $defaultPassword = 'Passwort123!';

        $this->createOrUpdateUser(
            name: 'Admin',
            first_name: 'Demo 2',
            gender: GenderEnum::Male,
            title: null,
            email: 'admin@demo.local',
            password: $defaultPassword,
            department: 'IT/Administration',
        )->assignRole($roleAdmin);

        $this->createOrUpdateUser(
            name: 'Empfang',
            first_name: 'Demo 1',
            gender: GenderEnum::Female,
            title: null,
            email: 'empfang@demo.local',
            password: $defaultPassword,
            department: 'Empfang',
        );

        $this->createOrUpdateUser(
            name: 'Mitarbeiter',
            first_name: 'Demo 3',
            gender: GenderEnum::Male,
            title: null,
            email: 'mitarbeiter@demo.local',
            password: $defaultPassword,
            department: 'Vertrieb',
        )->assignRole($roleEmployee);

        $this->command?->info('DemoSeeder ausgeführt.');
        $this->command?->info('Demo-Login (alle): Passwort123!');
        $this->command?->line('- admin@demo.local');
        $this->command?->line('- empfang@demo.local');
        $this->command?->line('- mitarbeiter@demo.local');
    }

    private function createOrUpdateUser(
        string $name,
        string $first_name,
        GenderEnum $gender,
        ?string $title,
        string $email,
        string $password,
        string $department,
    ): User|Model {
        // search if department name matches any department in the departments table and get id as foreign key
        $dept = Department::query()->where('name', $department)->first();
        $data = [
            'name' => $name,
            'first_name' => $first_name,
            'gender' => $gender,
            'title' => $title,
            'email_verified_at' => now(),
            'password' => Hash::make($password),
            'department_id' => $dept?->id,
        ];

        return User::query()->updateOrCreate(
            ['email' => $email],
            $data
        );
    }
}
