<x-mail.message_component :message="$message">

# Besuch eingetroffen

@if($user_gender == 'male')
Hallo Herr {{ $user_name }},
@elseif($user_gender == 'female')
Hallo Frau {{ $user_name }},
@else
Hallo,
@endif

Soeben ist folgender Besucher bei uns eingetroffen

<x-mail::table>
    | Vorname       | Nachname      | Firma         |
    | :-----------: | :-----------: | :----------:  |
    | {{$visitor->first_name}} | {{$visitor->last_name}} | {{$visitor->company}}
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    Weiter Informationen
</x-mail::button>

</x-mail.message_component>
