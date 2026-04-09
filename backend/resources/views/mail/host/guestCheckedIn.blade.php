@php
    use App\Enums\GenderEnum;
@endphp

<x-mail.message_component :message="$message">

# {{__('Besuch eingetroffen')}}

@if($user->gender == GenderEnum::Male)
{{__('Guten Tag Herr')}} {{$user->title . ' '}} {{ $user->name }},
@elseif($user->gender == GenderEnum::Female)
{{__('Guten Tag Frau')}} {{$user->title . ' '}} {{ $user->name }},
@else
{{__('Guten Tag')}} {{$user->title . ' '}}{{ $user->first_name }} {{ $user->name }},
@endif

{{__('soeben ist folgender Besucher bei uns eingetroffen')}}:

<x-mail::table>
    | {{__('Vorname')}}       | {{__('Nachname')}}      | {{__('Titel')}} |  {{__('Firma')}}         |
    | :-----------: | :-----------: | :----------:  | :----------:  |
    | {{$visitor->first_name}} | {{$visitor->name}} | {{$visitor->title}} |{{$visitor->company}}
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
