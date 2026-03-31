<x-mail::message>
# Neuen Besuch erstellt

Sie haben soeben folgenden Besuch erstellt:

<x-mail::table>
    | Eigenschaft   | Information   |
    | ------------- | :-----------: |
    | Name          | test          |
    | Firma         | test          |
</x-mail::table>

<x-mail::button :url="''">
    Weiter Informationen
</x-mail::button>

Thanks,<br>
{{ config('app.name') }}
</x-mail::message>
