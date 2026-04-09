@php
    use App\Enums\GenderEnum;
    use Carbon\Carbon;
 @endphp

<x-mail.message_component :message="$message">
# {{__('Neuer Besuch erstellt')}}

@if($user->gender == GenderEnum::Male)
{{__('Guten Tag Herr')}} {{$user->title . ' '}} {{ $user->name }},
@elseif($user->gender == GenderEnum::Female)
{{__('Guten Tag Frau')}} {{$user->title . ' '}} {{ $user->name }},
@else
{{__('Guten Tag')}} {{$user->title . ' '}}{{ $user->first_name }} {{ $user->name }},
@endif

{{__('folgender Besuch wurde für Sie registriert')}}:

## {{__('Besuch')}}
<x-mail::table>
    |{{__('Titel')}} | {{__('Datum')}}   | {{__('Start')}} | {{__('Ende')}} |
    | :-----------:| :-----------: | :-----------: | :------------: |
    | {{$visit->title}} | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} |
</x-mail::table>

## {{__('Besucher')}}
<x-mail::table>
    | {{__('Vorname')}}       | {{__('Nachname')}}      | {{__('Titel')}} |  {{__('Firma')}}         |
    | :-----------: | :-----------: | :----------:  | :----------:  |
    @foreach($visitors as $visitor)
        | {{$visitor->first_name}} | {{$visitor->name}} | {{$visitor->title}} |{{$visitor->company}}
    @endforeach
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
