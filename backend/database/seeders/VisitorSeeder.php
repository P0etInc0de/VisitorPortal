<?php

namespace Database\Seeders;

use App\Enums\GenderEnum;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        $visitors = [
            ['Sherlock', 'Holmes', null, GenderEnum::Male, 'sherlock.holmes@baker.example', '+49 7300 100001', 'Baker Street Consulting', 'Bringt eine Lupe mit.'],
            ['John', 'Watson', 'Dr.', GenderEnum::Male, 'john.watson@baker.example', '+49 7300 100002', 'Baker Street Consulting', 'Medizinischer Blick auf jeden Kaffee.'],
            ['Irene', 'Adler', null, GenderEnum::Female, 'irene.adler@opera.example', '+49 7300 100003', 'Royal Opera Archives', 'Bitte diskret empfangen.'],
            ['Elizabeth', 'Bennet', null, GenderEnum::Female, 'elizabeth.bennet@pemberley.example', '+49 7300 100004', 'Pemberley Projects', 'Fragt sehr gute Rückfragen.'],
            ['Fitzwilliam', 'Darcy', null, GenderEnum::Male, 'fitzwilliam.darcy@pemberley.example', '+49 7300 100005', 'Pemberley Projects', 'Wirkt erst reserviert, ist aber vorbereitet.'],
            ['Jane', 'Eyre', null, GenderEnum::Female, 'jane.eyre@thornfield.example', '+49 7300 100006', 'Thornfield Education', null],
            ['Atticus', 'Finch', null, GenderEnum::Male, 'atticus.finch@maycomb.example', '+49 7300 100007', 'Maycomb Legal', 'Besucht den Compliance-Termin.'],
            ['Scout', 'Finch', null, GenderEnum::Female, 'scout.finch@maycomb.example', '+49 7300 100008', 'Maycomb Legal', 'Neugierig, freundlich, schnell.'],
            ['Huckleberry', 'Finn', null, GenderEnum::Male, 'huck.finn@river.example', '+49 7300 100009', 'River Logistics', 'Kommt eventuell mit nassen Schuhen.'],
            ['Tom', 'Sawyer', null, GenderEnum::Male, 'tom.sawyer@river.example', '+49 7300 100010', 'Fence Painting Inc.', 'Hat kreative Vorschläge zur Arbeitsteilung.'],
            ['Alice', 'Liddell', null, GenderEnum::Female, 'alice.liddell@wonder.example', '+49 7300 100011', 'Wonderland Labs', 'Bitte nicht dem weißen Kaninchen folgen lassen.'],
            ['Dorothy', 'Gale', null, GenderEnum::Female, 'dorothy.gale@oz.example', '+49 7300 100012', 'Emerald City Travel', 'Rote Schuhe, klare Agenda.'],
            ['Toto', 'Gale', null, GenderEnum::Other, 'toto.gale@oz.example', '+49 7300 100013', 'Emerald City Travel', 'Hund. Bitte Wassernapf am Empfang bereitstellen.'],
            ['Matilda', 'Wormwood', null, GenderEnum::Female, 'matilda.wormwood@library.example', '+49 7300 100014', 'Tiny Library Club', 'Liest die Agenda vor dem Termin.'],
            ['Jennifer', 'Honey', 'Ms.', GenderEnum::Female, 'miss.honey@library.example', '+49 7300 100015', 'Tiny Library Club', 'Sehr geduldig bei Workshops.'],
            ['Bilbo', 'Baggins', null, GenderEnum::Male, 'bilbo.baggins@shire.example', '+49 7300 100016', 'Shire Snacks', 'Mag kurze Wege und zweite Frühstücke.'],
            ['Frodo', 'Baggins', null, GenderEnum::Male, 'frodo.baggins@shire.example', '+49 7300 100017', 'Shire Snacks', 'Bitte keine schweren Ringe aushändigen.'],
            ['Samwise', 'Gamgee', null, GenderEnum::Male, 'samwise.gamgee@shire.example', '+49 7300 100018', 'Shire Garden Supply', 'Kennt sich mit Kartoffeln aus.'],
            ['Hermione', 'Granger', null, GenderEnum::Female, 'hermione.granger@hogwarts.example', '+49 7300 100019', 'Hogwarts Research', 'Hat drei Termine direkt nacheinander.'],
            ['Ron', 'Weasley', null, GenderEnum::Male, 'ron.weasley@hogwarts.example', '+49 7300 100020', 'Hogwarts Logistics', 'Bitte Snack-Schale auffüllen.'],
            ['Luna', 'Lovegood', null, GenderEnum::Female, 'luna.lovegood@quibbler.example', '+49 7300 100021', 'Quibbler Ideas', 'Sieht Lösungen, wo andere nur Flipcharts sehen.'],
            ['Arthur', 'Dent', null, GenderEnum::Male, 'arthur.dent@galaxy.example', '+49 7300 100022', 'Infinite Improbability Ltd.', 'Handtuch nicht vergessen.'],
            ['Ford', 'Prefect', null, GenderEnum::Male, 'ford.prefect@galaxy.example', '+49 7300 100023', 'Infinite Improbability Ltd.', 'Recherchiert Gebäude für Reiseführer.'],
            ['Lyra', 'Belacqua', null, GenderEnum::Female, 'lyra.belacqua@north.example', '+49 7300 100024', 'Northern Compass', 'Kommt mit Kompass und vielen Fragen.'],
            ['Mary', 'Poppins', null, GenderEnum::Female, 'mary.poppins@cherry.example', '+49 7300 100025', 'Cherry Tree Services', 'Tasche kann größer sein als erwartet.'],
            ['Anne', 'Shirley', null, GenderEnum::Female, 'anne.shirley@green-gables.example', '+49 7300 100026', 'Green Gables Creative', 'Bringt Begeisterung für Whiteboards mit.'],
            ['Jo', 'March', null, GenderEnum::Female, 'jo.march@orchard.example', '+49 7300 100027', 'Orchard House Editorial', 'Schreibt das Protokoll schneller als alle reden.'],
            ['Long John', 'Silver', null, GenderEnum::Male, 'longjohn.silver@treasure.example', '+49 7300 100028', 'Treasure Map Co.', 'Barrierefreien Zugang einplanen.'],
            ['Pippi', 'Langstrumpf', null, GenderEnum::Female, 'pippi.langstrumpf@villa.example', '+49 7300 100029', 'Villa Kunterbunt', 'Sehr stark, sehr freundlich.'],
            ['Willy', 'Wonka', null, GenderEnum::Other, 'willy.wonka@chocolate.example', '+49 7300 100030', 'Chocolate Factory', 'Muster nur nach Freigabe verteilen.'],
        ];

        foreach ($visitors as [$firstName, $name, $title, $gender, $email, $phone, $company, $notes]) {
            $this->createOrUpdateVisitor($firstName, $name, $title, $gender, $email, $phone, $company, $notes);
        }

        $this->command?->info('VisitorSeeder ausgeführt: 30 Demo-Gäste angelegt/aktualisiert.');
    }

    private function createOrUpdateVisitor(string $firstName, string $name, ?string $title, GenderEnum $gender, string $email, string $phone, ?string $company, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'name' => $name,
                'title' => $title,
                'gender' => $gender,
                'phone' => $phone,
                'company' => $company,
                'notes' => $notes,
            ]
        );
    }
}
