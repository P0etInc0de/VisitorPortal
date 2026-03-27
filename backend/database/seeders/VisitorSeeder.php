<?php

namespace Database\Seeders;

use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    /**
     * Seeder for visitors table
     */
    public function run(): void
    {
        $this->createOrUpdateVisitor('Rainer', 'Winkler', 'rainer.winkler@dache.com', '123456789', 'Altschauer', 'planned', 'Löwen im Gardden');
        $this->createOrUpdateVisitor('Johannes', 'Statue', 'jostat2017@gmail.com', '354873540', 'Neuschauer', 'canceled', 'Hallo, Johannes Statue am Apparat');
        $this->createOrUpdateVisitor('Domenikus', 'Zeisus', 'dom.zeis@gmx.de', '3466456', 'Laubheim', 'checked_in', 'Ernschthaft?');
        $this->createOrUpdateVisitor('Johannes', 'Würfel', 'jo.wue@gmail.com', '355666', 'WürfelGmbH', 'checked_out', 'Würfel essen');
    }

    private function createOrUpdateVisitor(string $first_name, string $last_name, string $email, string $phone, ?string $company, string $status, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email,], //search criteria
            ['first_name'=>$first_name, 'last_name' => $last_name, 'phone' => $phone,'company' => $company,'status' => $status, 'notes'=>$notes] //update values
        );
    }
}
