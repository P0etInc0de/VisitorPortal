@php use Carbon\Carbon; @endphp

<x-mail.message_component :message="$message">
# Termin für Ihren Besuch

Guten Tag {{$visitor->first_name}} {{$visitor->last_name}},

wir freuen und sehr über Ihren Besuch.

<x-mail::table>
    |Datum   | Start | Ende |
    | :-----------: | :-----------: | :------------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} |
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    Weiter Informationen
</x-mail::button>

</x-mail.message_component>
