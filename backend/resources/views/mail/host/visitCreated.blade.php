@php use Carbon\Carbon; @endphp
<x-mail.message_component :message="$message">
# Neuen Besuch erstellt

@if($user_gender == 'male' )
Hallo Herr {{ $user_name }},
@elseif($user_gender == 'female')
Hallo Frau {{ $user_name }},
@else
Hallo,
@endif

folgender Besuch wurde für Sie registriert:

## Besuch
<x-mail::table>
    |Datum   | Start | Ende | Notizen |
    | :-----------: | :-----------: | :------------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} | {{ $visit->notes }} |
</x-mail::table>

## Besucher
<x-mail::table>
    | Vorname       | Nachname      | Firma         |
    | :-----------: | :-----------: | :----------:  |
    @foreach($visitors as $visitor)
        | {{$visitor->first_name}} | {{$visitor->last_name}} | {{$visitor->company}}
    @endforeach
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    Weiter Informationen
</x-mail::button>

</x-mail.message_component>
