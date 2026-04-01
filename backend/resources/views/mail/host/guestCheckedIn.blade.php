<x-mail.message_component :message="$message">

# Besuch eingetroffen

Soeben ist folgender Besuch bei uns eingetroffen

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
