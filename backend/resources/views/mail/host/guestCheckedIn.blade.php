@php
    use App\Enums\GenderEnum;
@endphp

<x-mail.message_component :message="$message">

# {{__('Besuch eingetroffen')}}

@if($user_gender == GenderEnum::Male)
{{__('Hallo Herr')}} {{ $user_name }},
@elseif($user_gender == GenderEnum::Female)
{{__('Hallo Frau')}} {{ $user_name }},
@else
{{__('Hallo')}},
@endif

{{__('soeben ist folgender Besucher bei uns eingetroffen')}}:

<x-mail::table>
    | {{__('Vorname')}}       | {{__('Nachname')}}      | {{__('Firma')}}         |
    | :-----------: | :-----------: | :----------:  |
    | {{$visitor->first_name}} | {{$visitor->last_name}} | {{$visitor->company}}
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
