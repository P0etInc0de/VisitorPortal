<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Support\Facades\Hash;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            ['name' => 'Admin User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00',]
        );

        User::firstOrCreate(
            ['email' => 'manager@example.com'],
            ['name' => 'Manager User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00',]
        );

        User::firstOrCreate(
            ['email' => 'receptionist@example.com'],
            ['name' => 'Receptionist User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00',]
        );

        User::firstOrCreate(
            ['email' => 'user@example.com'],
            ['name' => 'Regular User', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00',]
        );

        User::firstOrCreate(
            ['email' => 'monitor@example.com'],
            ['name' => 'Welcome Monitor', 'password' => Hash::make('password'), 'email_verified_at' => '2024-01-01 00:00:00',]
        );
    }
}
