<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $defaultPassword = 'Passwort123!';

        $users = [
            ['User', 'Admin', GenderEnum::Male, null, 'admin@demo.local', 'IT/Administration', 'admin'],
            ['User', 'Empfang', GenderEnum::Female, null, 'empfang@demo.local', 'Empfang', 'receptionist'],
            ['User', 'Mitarbeiter', GenderEnum::Male, null, 'mitarbeiter@demo.local', 'Vertrieb', 'user'],
            ['User', 'Manager', GenderEnum::Male, null, 'manager@demo.local', 'Management', 'manager'],
            ['User', 'WelcomeMonitor', GenderEnum::Not_Specified, null, 'welcomemonitor@demo.local', 'Empfang', 'welcome monitor'],
            ['Bilbo', 'Baggins', GenderEnum::Male, null, 'bilbo.baggins@demo.local', 'Management', 'manager'],
            ['Hermione', 'Granger', GenderEnum::Female, null, 'hermione.granger@demo.local', 'Forschung & Entwicklung', 'user'],
            ['Matilda', 'Wormwood', GenderEnum::Female, null, 'matilda.wormwood@demo.local', 'Bibliothek', 'user'],
            ['Atticus', 'Finch', GenderEnum::Male, null, 'atticus.finch@demo.local', 'Management', 'manager'],
            ['Elizabeth', 'Bennet', GenderEnum::Female, null, 'elizabeth.bennet@demo.local', 'Vertrieb', 'user'],
            ['Sherlock', 'Holmes', GenderEnum::Male, null, 'sherlock.holmes@demo.local', 'Forschung & Entwicklung', 'user'],
            ['Anne', 'Shirley', GenderEnum::Female, null, 'anne.shirley@demo.local', 'Küche', 'user'],
            ['Samwise', 'Gamgee', GenderEnum::Male, null, 'samwise.gamgee@demo.local', 'Facility', 'user'],
            ['Lucy', 'Pevensie', GenderEnum::Female, null, 'lucy.pevensie@demo.local', 'Empfang', 'receptionist'],
            ['Arthur', 'Dent', GenderEnum::Male, null, 'arthur.dent@demo.local', 'IT/Administration', 'user'],
            ['Jo', 'March', GenderEnum::Female, null, 'jo.march@demo.local', 'Bibliothek', 'user'],
        ];

        foreach ($users as [$firstName, $name, $gender, $title, $email, $department, $role]) {
            $this->createOrUpdateUser($name, $firstName, $gender, $title, $email, $defaultPassword, $department, $role);
        }

        $this->command?->info('UserSeeder ausgeführt: 16 Demo-Benutzer angelegt/aktualisiert.');
        $this->command?->info('Demo-Login (alle): Passwort123!');
        $this->command?->line('- admin@demo.local');
        $this->command?->line('- empfang@demo.local');
        $this->command?->line('- mitarbeiter@demo.local');
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
        $dept = Department::query()->where('name', $department)->first();
        $user = User::query()->updateOrCreate(
            ['email' => $email],
            [
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
