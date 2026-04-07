@php use Carbon\Carbon; @endphp

<x-mail.message_component :message="$message">
# {{__('Termin für Ihren Besuch')}}

{{__('Guten Tag')}} {{$visitor->first_name}} {{$visitor->last_name}},

{{__('wir freuen und sehr über Ihren Besuch')}}.

<x-mail::table>
    |{{__('Datum')}}   | {{__('Start')}} | {{__('Ende')}} |
    | :-----------: | :-----------: | :------------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} |
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weiter Informationen')}}
</x-mail::button>

</x-mail.message_component>
