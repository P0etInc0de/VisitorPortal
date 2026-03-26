<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        /*
        * The ShieldSeeder is auto generated:
        * - Scan the app/Filament folder for resources and generate CRUD permissions: `php artisan shield:generate --all`
        * - Auto-Generate a Seeder for the current shield database: `php artisan shield:seeder`
        */
        $this->call(ShieldSeeder::class);

        $this->call(DepartmentSeeder::class);

        $this->call(DemoSeeder::class);
    }
}
