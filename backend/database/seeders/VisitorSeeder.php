<?php

namespace Database\Seeders;

use App\Enums\VisitorStatusEnum;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        $this->createOrUpdateVisitor('Max', 'Mustermann', 'max.mustermann@example.com', '111111111', 'Beispiel GmbH', VisitorStatusEnum::Planned, '');
        $this->createOrUpdateVisitor('Anna', 'Becker', 'anna.becker@example.com', '222222222', 'ACME AG', VisitorStatusEnum::Planned, '');
        $this->createOrUpdateVisitor('Peter', 'Maurer', 'peter.maurer@example.com', '333333333', 'Maurer Industrie', VisitorStatusEnum::Planned, '');
        $this->createOrUpdateVisitor('Sven', 'Maurer', 'sven.maurer@example.com', '444444444', 'Musterwerke Süd', VisitorStatusEnum::Planned, '');
    }

    private function createOrUpdateVisitor(
        string $firstName,
        string $lastName,
        string $email,
        string $phone,
        ?string $company,
        VisitorStatusEnum $status,
        ?string $notes
    ): void {
        Visitor::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'last_name' => $lastName,
                'phone' => $phone,
                'company' => $company,
                'status' => $status,
                'notes' => $notes,
            ],
        );
    }
}
