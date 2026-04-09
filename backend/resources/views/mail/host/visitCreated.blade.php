@php
    use App\Enums\GenderEnum;
    use Carbon\Carbon;
 @endphp

<x-mail.message_component :message="$message">
# {{__('Neuer Besuch erstellt')}}

@if($user_gender == GenderEnum::Male )
{{__('Hallo Herr')}} {{ $user_name }},
@elseif($user_gender == GenderEnum::Female)
{{__('Hallo Frau')}} {{ $user_name }},
@else
{{__('Hallo')}},
@endif

{{__('folgender Besuch wurde für Sie registriert')}}:

## {{__('Besuch')}}
<x-mail::table>
    |{{__('Datum')}}   | {{__('Start')}} | {{__('Ende')}} | {{__('Notizen')}} |
    | :-----------: | :-----------: | :------------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} | {{ $visit->notes }} |
</x-mail::table>

## {{__('Besucher')}}
<x-mail::table>
    | {{__('Vorname')}}       | {{__('Nachname')}}      | {{__('Firma')}}         |
    | :-----------: | :-----------: | :----------:  |
    @foreach($visitors as $visitor)
        | {{$visitor->first_name}} | {{$visitor->last_name}} | {{$visitor->company}}
    @endforeach
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
