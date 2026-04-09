@php
    use App\Enums\GenderEnum;
    use Carbon\Carbon;
@endphp
<x-mail.message_component :message="$message">
# {{__('Anstehende Besuche')}}

@if($gender == GenderEnum::Male )
{{__('Guten Morgen Herr')}} {{ $last_name }},
@elseif($gender == GenderEnum::Female)
{{__('Guten Morgen Frau')}} {{ $last_name }},
@else
{{__('Guten Morgen')}},
@endif

{{__('Sie haben heute folgende Besuche')}}:

<x-mail::table>
    | {{__('Start')}} | {{__('Ende')}} | {{__('Besucher')}} | {{__('Notizen')}} |
    | :----: | :----: | :--------: | :----: |
    @foreach($visitCollection as $visit)
        | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} | @foreach($visitorCollection[$visit->id] as $v){{ $v->first_name }} {{ $v->last_name }}<br>@endforeach | {{ $visit->notes }} |
    @endforeach
</x-mail::table>

<x-mail::button :url='route("dashboard")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
