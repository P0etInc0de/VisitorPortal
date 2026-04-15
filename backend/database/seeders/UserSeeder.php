<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['first_name' => 'Admin', 'name' => 'Admin User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00']
        );

        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            ['first_name' => 'Manager', 'name' => 'Manager User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00']
        );

        User::firstOrCreate(
            ['email' => 'receptionist@example.com'],
            ['first_name' => 'Receptionist', 'name' => 'Receptionist User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00']
        );

        User::firstOrCreate(
            ['email' => 'user@example.com'],
            ['first_name' => 'Regular', 'name' => 'Regular User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00']
        );

        User::firstOrCreate(
            ['email' => 'monitor@example.com'],
            ['first_name' => 'Welcome', 'name' => 'Welcome Monitor', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00']
        );

        // Assign roles to users
        User::where('email', 'admin@example.com')->first()->assignRole('admin');
        User::where('email', 'manager@example.com')->first()->assignRole('manager');
        User::where('email', 'receptionist@example.com')->first()->assignRole('receptionist');
        User::where('email', 'user@example.com')->first()->assignRole('user');
        User::where('email', 'monitor@example.com')->first()->assignRole('welcome monitor');
    }
}
