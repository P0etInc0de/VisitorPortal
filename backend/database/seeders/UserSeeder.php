<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Seeder for the users table, creates 3 default users (one for each role)
     */
    public function run(): void
    {
        $defaultPassword = 'Passwort123!';

        // Create all Users
        $this->createOrUpdateUser(
            name: 'Admin',
            first_name: 'User',
            gender: GenderEnum::Male,
            title: null,
            email: 'admin@demo.local',
            password: $defaultPassword,
            department: 'IT/Administration',
            role: 'admin'
        );

        $this->createOrUpdateUser(
            name: 'Empfang',
            first_name: 'User',
            gender: GenderEnum::Female,
            title: null,
            email: 'empfang@demo.local',
            password: $defaultPassword,
            department: 'Empfang',
            role: 'receptionist',
        );

        $this->createOrUpdateUser(
            name: 'Mitarbeiter',
            first_name: 'User',
            gender: GenderEnum::Male,
            title: null,
            email: 'mitarbeiter@demo.local',
            password: $defaultPassword,
            department: 'Vertrieb',
            role: 'user',
        );

        $this->createOrUpdateUser(
            name: 'Manager',
            first_name: 'User',
            gender: GenderEnum::Male,
            title: null,
            email: 'Manager@demo.local',
            password: $defaultPassword,
            department: 'Vertrieb',
            role: 'manager',
        );

        $this->createOrUpdateUser(
            name: 'WelcomeMonitor',
            first_name: 'User',
            gender: GenderEnum::Male,
            title: null,
            email: 'WelcomeMonitor@demo.local',
            password: $defaultPassword,
            department: 'Vertrieb',
            role: 'welcome monitor',
        );

        $this->command?->info('UserSeeder ausgeführt.');
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
        ?string $role = null,
    ): void {
        // Search if department name matches any department in the departments table and get id as foreign key
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

        $user = User::query()->updateOrCreate(
            ['email' => $email],
            $data
        );

        if ($role) {
            $user->syncRoles([$role]);
        }
    }
}
