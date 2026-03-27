<x-mail.message :message="$message">
# Anstehende Besuche

Guten Morgen [Mitarbeiter],

Sie bekommen heute Folgende Besuche:

<x-mail::table>
    | Name          | Uhrzeit       | Notizen       |
    | ------------- | :-----------: | ------------: |
    | Col 2 is      | Centered      | test          |
    | Col 3 is      | Right-Aligned | test          |
</x-mail::table>

<x-mail::button :url="''">
    Weiter Informationen
</x-mail::button>

</x-mail.message>
