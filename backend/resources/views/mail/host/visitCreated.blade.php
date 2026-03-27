<x-mail.message_component :message="$message">
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

</x-mail.message_component>
