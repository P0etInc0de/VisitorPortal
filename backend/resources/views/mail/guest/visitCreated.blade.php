<x-mail::message>
# Termin für Ihren Besuch

Sehr geehrter [Kunde],

wir freuen und sehr über Ihren Besuch.

<x-mail::table>
    | Eigenschaft   | Information   |
    | ------------- | :-----------: |
    | Name          | test          |
    | Firma         | test          |
</x-mail::table>

<x-mail::button :url="''">
    Weiter Informationen
</x-mail::button>


Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
