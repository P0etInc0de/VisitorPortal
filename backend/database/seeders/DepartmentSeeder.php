<?php

namespace Database\Seeders;

use App\Models\Department;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seeder for departments table
     */
    public function run(): void
    {
        $this->createOrUpdateDept('IT/Administration', 'Achstetten', true); // NOTE: is active is true by default, can be changed here if wanted
        $this->createOrUpdateDept('Empfang', 'Achstetten', true);
        $this->createOrUpdateDept('Vertrieb', null, true);
    }

    private function createOrUpdateDept(string $name, ?string $location, ?bool $is_active): void
    {
        Department::query()->updateOrCreate(
            ['name' => $name], // search criteria
            ['location' => $location, 'is_active' => $is_active] // update values
        );
    }
}
