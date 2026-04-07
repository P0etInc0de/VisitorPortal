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
        $this->createOrUpdateVisitor('Rainer', 'Winkler','Dr.', GenderEnum::Female,'rainer.winkler@dache.com', '123456789', 'Altschauer', VisitorStatusEnum::Planned, 'Löwen im Gardden');
        $this->createOrUpdateVisitor('Johannes', 'Statue', 'Dr. Dr. Dr. Dr.', GenderEnum::Male,'jostat2017@gmail.com', '354873540', 'Neuschauer', VisitorStatusEnum::Cancelled, 'Hallo, Johannes Statue am Apparat');
        $this->createOrUpdateVisitor('Domenikus', 'Zeisus', 'Dr.', GenderEnum::Other, 'dom.zeis@gmx.de', '3466456', 'Laubheim', VisitorStatusEnum::Checked_In, 'Ernschthaft?');
        $this->createOrUpdateVisitor('Johannes', 'Würfel', 'Prof. Dr. ',GenderEnum::Female,'jo.wue@gmail.com', '355666', 'WürfelGmbH', VisitorStatusEnum::Checked_Out, 'Würfel essen');
    }

    private function createOrUpdateVisitor(string $first_name, string $last_name, string $title, GenderEnum $gender, string $email, string $phone, ?string $company, VisitorStatusEnum $status, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email], // search criteria
            ['first_name' => $first_name, 'name' => $last_name, 'title' => $title, 'gender' => $gender, 'phone' => $phone, 'company' => $company, 'status' => $status, 'notes' => $notes] // update values
        );
    }
}
