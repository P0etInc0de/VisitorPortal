<?php

namespace Database\Seeders;

use App\Enums\SalutationEnum;
use App\Models\Visitor;
use Illuminate\Database\Seeder;

class VisitorSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production')) {
            throw new \RuntimeException('Demo visitors are disabled in production.');
        }

        $visitors = [
            ['Sherlock', 'Holmes', null, SalutationEnum::Mr, 'sherlock.holmes@baker.example', '+49 7300 100001', 'Baker Street Consulting', 'Bringt eine Lupe mit.'],
            ['John', 'Watson', 'Dr.', SalutationEnum::Mr, 'john.watson@baker.example', '+49 7300 100002', 'Baker Street Consulting', 'Medizinischer Blick auf jeden Kaffee.'],
            ['Irene', 'Adler', null, SalutationEnum::Ms, 'irene.adler@opera.example', '+49 7300 100003', 'Royal Opera Archives', 'Bitte diskret empfangen.'],
            ['Elizabeth', 'Bennet', null, SalutationEnum::Ms, 'elizabeth.bennet@pemberley.example', '+49 7300 100004', 'Pemberley Projects', 'Fragt sehr gute Rückfragen.'],
            ['Fitzwilliam', 'Darcy', null, SalutationEnum::Mr, 'fitzwilliam.darcy@pemberley.example', '+49 7300 100005', 'Pemberley Projects', 'Wirkt erst reserviert, ist aber vorbereitet.'],
            ['Jane', 'Eyre', null, SalutationEnum::Ms, 'jane.eyre@thornfield.example', '+49 7300 100006', 'Thornfield Education', null],
            ['Atticus', 'Finch', null, SalutationEnum::Mr, 'atticus.finch@maycomb.example', '+49 7300 100007', 'Maycomb Legal', 'Besucht den Compliance-Termin.'],
            ['Scout', 'Finch', null, SalutationEnum::Ms, 'scout.finch@maycomb.example', '+49 7300 100008', 'Maycomb Legal', 'Neugierig, freundlich, schnell.'],
            ['Huckleberry', 'Finn', null, SalutationEnum::Mr, 'huck.finn@river.example', '+49 7300 100009', 'River Logistics', 'Kommt eventuell mit nassen Schuhen.'],
            ['Tom', 'Sawyer', null, SalutationEnum::Mr, 'tom.sawyer@river.example', '+49 7300 100010', 'Fence Painting Inc.', 'Hat kreative Vorschläge zur Arbeitsteilung.'],
            ['Alice', 'Liddell', null, SalutationEnum::Ms, 'alice.liddell@wonder.example', '+49 7300 100011', 'Wonderland Labs', 'Bitte nicht dem weißen Kaninchen folgen lassen.'],
            ['Dorothy', 'Gale', null, SalutationEnum::Ms, 'dorothy.gale@oz.example', '+49 7300 100012', 'Emerald City Travel', 'Rote Schuhe, klare Agenda.'],
            ['Toto', 'Gale', null, SalutationEnum::NotSpecified, 'toto.gale@oz.example', '+49 7300 100013', 'Emerald City Travel', 'Hund. Bitte Wassernapf am Empfang bereitstellen.'],
            ['Matilda', 'Wormwood', null, SalutationEnum::Ms, 'matilda.wormwood@library.example', '+49 7300 100014', 'Tiny Library Club', 'Liest die Agenda vor dem Termin.'],
            ['Jennifer', 'Honey', 'Ms.', SalutationEnum::Ms, 'miss.honey@library.example', '+49 7300 100015', 'Tiny Library Club', 'Sehr geduldig bei Workshops.'],
            ['Bilbo', 'Baggins', null, SalutationEnum::Mr, 'bilbo.baggins@shire.example', '+49 7300 100016', 'Shire Snacks', 'Mag kurze Wege und zweite Frühstücke.'],
            ['Frodo', 'Baggins', null, SalutationEnum::Mr, 'frodo.baggins@shire.example', '+49 7300 100017', 'Shire Snacks', 'Bitte keine schweren Ringe aushändigen.'],
            ['Samwise', 'Gamgee', null, SalutationEnum::Mr, 'samwise.gamgee@shire.example', '+49 7300 100018', 'Shire Garden Supply', 'Kennt sich mit Kartoffeln aus.'],
            ['Hermione', 'Granger', null, SalutationEnum::Ms, 'hermione.granger@hogwarts.example', '+49 7300 100019', 'Hogwarts Research', 'Hat drei Termine direkt nacheinander.'],
            ['Ron', 'Weasley', null, SalutationEnum::Mr, 'ron.weasley@hogwarts.example', '+49 7300 100020', 'Hogwarts Logistics', 'Bitte Snack-Schale auffüllen.'],
            ['Luna', 'Lovegood', null, SalutationEnum::Ms, 'luna.lovegood@quibbler.example', '+49 7300 100021', 'Quibbler Ideas', 'Sieht Lösungen, wo andere nur Flipcharts sehen.'],
            ['Arthur', 'Dent', null, SalutationEnum::Mr, 'arthur.dent@galaxy.example', '+49 7300 100022', 'Infinite Improbability Ltd.', 'Handtuch nicht vergessen.'],
            ['Ford', 'Prefect', null, SalutationEnum::Mr, 'ford.prefect@galaxy.example', '+49 7300 100023', 'Infinite Improbability Ltd.', 'Recherchiert Gebäude für Reiseführer.'],
            ['Lyra', 'Belacqua', null, SalutationEnum::Ms, 'lyra.belacqua@north.example', '+49 7300 100024', 'Northern Compass', 'Kommt mit Kompass und vielen Fragen.'],
            ['Mary', 'Poppins', null, SalutationEnum::Ms, 'mary.poppins@cherry.example', '+49 7300 100025', 'Cherry Tree Services', 'Tasche kann größer sein als erwartet.'],
            ['Anne', 'Shirley', null, SalutationEnum::Ms, 'anne.shirley@green-gables.example', '+49 7300 100026', 'Green Gables Creative', 'Bringt Begeisterung für Whiteboards mit.'],
            ['Jo', 'March', null, SalutationEnum::Ms, 'jo.march@orchard.example', '+49 7300 100027', 'Orchard House Editorial', 'Schreibt das Protokoll schneller als alle reden.'],
            ['Long John', 'Silver', null, SalutationEnum::Mr, 'longjohn.silver@treasure.example', '+49 7300 100028', 'Treasure Map Co.', 'Barrierefreien Zugang einplanen.'],
            ['Pippi', 'Langstrumpf', null, SalutationEnum::Ms, 'pippi.langstrumpf@villa.example', '+49 7300 100029', 'Villa Kunterbunt', 'Sehr stark, sehr freundlich.'],
            ['Willy', 'Wonka', null, SalutationEnum::NotSpecified, 'willy.wonka@chocolate.example', '+49 7300 100030', 'Chocolate Factory', 'Muster nur nach Freigabe verteilen.'],
        ];

        foreach ($visitors as [$firstName, $name, $title, $salutation, $email, $phone, $company, $notes]) {
            $this->createOrUpdateVisitor($firstName, $name, $title, $salutation, $email, $phone, $company, $notes);
        }

        $this->command?->info('VisitorSeeder ausgeführt: 30 Demo-Gäste angelegt/aktualisiert.');
    }

    private function createOrUpdateVisitor(string $firstName, string $name, ?string $title, SalutationEnum $salutation, string $email, string $phone, ?string $company, ?string $notes): void
    {
        Visitor::query()->updateOrCreate(
            ['email' => $email],
            [
                'first_name' => $firstName,
                'name' => $name,
                'title' => $title,
                'salutation' => $salutation,
                'phone' => $phone,
                'company' => $company,
                'notes' => $notes,
            ]
        );
    }
}
