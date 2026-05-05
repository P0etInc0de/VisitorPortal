@php
    use App\Enums\GenderEnum;
    use Carbon\Carbon;
@endphp
<x-mail.message_component :message="$message">
# {{__('Anstehende Besuche')}}

@if($user->gender == GenderEnum::Male)
{{__('Guten Tag Herr')}} {{$user->title . ' '}} {{ $user->name }},
@elseif($user->gender == GenderEnum::Female)
{{__('Guten Tag Frau')}} {{$user->title . ' '}} {{ $user->name }},
@else
{{__('Guten Tag')}} {{$user->title . ' '}}{{ $user->first_name }} {{ $user->name }},
@endif

{{__('Sie haben heute folgende Besuche')}}:

<x-mail::table>
    |{{__('Titel')}} | {{__('Start')}} | {{__('Ende')}} | {{__('Besucher')}} |
    | :-----------:| :-----------: | :-----------: | :------------: |
    @foreach($visitCollection as $visit)
        | {{$visit->title}} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} | @foreach($visitorCollection[$visit->id] as $v){{ $v->title.' ' }}{{ $v->first_name }} {{ $v->name }}<br>@endforeach |
    @endforeach
</x-mail::table>

<x-mail::button :url='route("overview")'>
    {{__('Weitere Informationen')}}
</x-mail::button>

</x-mail.message_component>
