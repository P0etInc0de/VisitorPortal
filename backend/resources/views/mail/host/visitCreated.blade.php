@php
    use Carbon\Carbon;
 @endphp

<x-mail.message_component :message="$message">
# {{__('Neuer Besuch erstellt')}}

{{ $greeting }}

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

<x-mail::button :url='route("overview")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
