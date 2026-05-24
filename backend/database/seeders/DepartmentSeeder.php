<?php

/**
 * SPDX-FileCopyrightText: 2026 Jonathan Läpple and VisitorPortal contributors
 *
 * SPDX-License-Identifier: GPL-3.0-or-later
 */

namespace Database\Seeders;

use App\Models\Department;
use App\Models\Site;
use Illuminate\Database\Seeder;

class DepartmentSeeder extends Seeder
{
    /**
     * Seeder for departments table
     */
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo departments are disabled in production.');
        }

        $this->createOrUpdateDept('IT/Administration', 'Achstetten', true); // NOTE: is active is true by default, can be changed here if wanted
        $this->createOrUpdateDept('Empfang', 'Achstetten', true);
        $this->createOrUpdateDept('Vertrieb', 'Achstetten', true);
        $this->createOrUpdateDept('Forschung & Entwicklung', 'Achstetten', true);
        $this->createOrUpdateDept('Bibliothek', 'Achstetten', true);
        $this->createOrUpdateDept('Facility', 'Achstetten', true);
        $this->createOrUpdateDept('Management', 'Achstetten', true);
        $this->createOrUpdateDept('Küche', 'Achstetten', true);
    }

    private function createOrUpdateDept(string $name, ?string $location, ?bool $is_active): void
    {
        $site = Site::default();

        Department::query()->updateOrCreate(
            ['site_id' => $site->id, 'name' => $name], // search criteria
            ['location' => $location, 'is_active' => $is_active] // update values
        );
    }
}
