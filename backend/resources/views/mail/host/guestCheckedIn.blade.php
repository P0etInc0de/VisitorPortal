<x-mail.message_component :message="$message">

# {{__('Besuch eingetroffen')}}

{{ $greeting }}

{{__('soeben ist folgender Besucher bei uns eingetroffen')}}:

<x-mail::table>
    | {{__('Vorname')}}       | {{__('Nachname')}}      | {{__('Titel')}} |  {{__('Firma')}}         |
    | :-----------: | :-----------: | :----------:  | :----------:  |
    | {{$visitor->first_name}} | {{$visitor->name}} | {{$visitor->title}} |{{$visitor->company}}
</x-mail::table>

<x-mail::button :url='route("overview")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
