<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Department;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
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
            name: 'Admin Demo',
            gender: 'male',
            email: 'admin@demo.local',
            password: $defaultPassword,
            department: 'IT/Administration',
        )->assignRole($roleAdmin);

        $this->createOrUpdateUser(
            name: 'Empfang Demo',
            gender: 'male',
            email: 'empfang@demo.local',
            password: $defaultPassword,
            department: 'Empfang',
        );

        $this->createOrUpdateUser(
            name: 'Mitarbeiter Demo',
            gender: 'male',
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
        string $gender,
        string $email,
        string $password,
        string $department,
    ):User|Model {
        //search if department name matches any department in the departments table and get id as foreign key
        $dept = Department::query()->where('name', $department)->first();
        $data = [
            'name' => $name,
            'gender' => $gender,
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
