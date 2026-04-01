@php use Carbon\Carbon; @endphp
<x-mail.message_component :message="$message">
# Anstehende Besuche

@if($gender == 'male')
Guten Morgen Herr {{ $last_name }},
@elseif($gender == 'female')
Guten Morgen Frau {{ $last_name }},
@else
Guten Morgen,
@endif

Sie haben heute Folgende Besuche:

<x-mail::table>
    | Start | Ende | Notizen |
    | :-----------: | :------------: | :------------: |
    @foreach($visitCollection as $visit)
        | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} | {{ $visit->notes }} |
    @endforeach

</x-mail::table>

<x-mail::button :url={{ route('dashboard') }}>
    Weiter Informationen
</x-mail::button>

</x-mail.message_component>
