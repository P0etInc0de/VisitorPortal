<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Enums\VisitorStatusEnum;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    /**
     * Seeder for visitors table
     */
    public function run(): void
    {
        $this->createOrUpdateVisitor('Max', 'Mustermann', 'Dr.', GenderEnum::Male, 'max.mustermann@musterfirma.de', '01574857788', 'Musterfirma GmbH', VisitorStatusEnum::Checked_In, 'Musternotiz');
        $this->createOrUpdateVisitor('Theo', 'Pünktlich', 'B.Sc.', GenderEnum::Male, 'theo.puenktlich@timeworks.de', '01516667788', 'TimeWorks', VisitorStatusEnum::Checked_Out, 'War schon da, bevor der Termin begann');
        $this->createOrUpdateVisitor('Frank', 'Check25', 'MBA', GenderEnum::Male, 'frank.check25@vergleichpro.de', '01730000013', 'VergleichPro', VisitorStatusEnum::Planned, 'Muss noch 24 Angebote vergleichen');
        $this->createOrUpdateVisitor('Lisa', 'Lohntsich', 'M.A.', GenderEnum::Female, 'lisa.lohntsich@discount24.de', '01730000002', 'Discount24', VisitorStatusEnum::Planned, 'Kommt nur, wenn sichs lohnt');
        $this->createOrUpdateVisitor('Sabine', 'Chaos', 'Dipl.-Kffr.', GenderEnum::Female, 'sabine.chaos@ordnungslos.de', '01710000005', 'Ordnungslos GmbH', VisitorStatusEnum::Canceled, 'Hat den Kalender verlegt');
        $this->createOrUpdateVisitor('Clara', 'Bergmann', 'Dr.', GenderEnum::Female, 'clara.bergmann@alpina-consult.de', '01711234567', 'Alpina Consult', VisitorStatusEnum::Checked_In, 'Strategiemeeting mit Aussicht');
    }

    private function createOrUpdateVisitor(string $first_name, string $name, string $title, GenderEnum $gender, string $email, string $phone, ?string $company, VisitorStatusEnum $status, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email], // search criteria
            [
                'first_name' => $first_name,
                'name' => $name,
                'title' => $title,
                'gender' => $gender,
                'phone' => $phone,
                'company' => $company,
                'status' => $status,
                'notes' => $notes] // update values
        );
    }
}
