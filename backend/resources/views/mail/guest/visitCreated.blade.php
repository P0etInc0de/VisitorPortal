@php
    use Carbon\Carbon;
    use \App\Enums\SalutationEnum;
    $privacyNoticeUrl = config('privacy.notice_url');
 @endphp

<x-mail.message_component :message="$message">
# {{__('Termin für Ihren Besuch')}}

@if($visitor->salutation == SalutationEnum::Mr)
{{__('Sehr geehrter Herr')}} {{$visitor->title . ' '}} {{ $visitor->name }},
@elseif($visitor->salutation == SalutationEnum::Ms)
{{__('Sehr geehrter Frau')}} {{$visitor->title . ' '}}{{ $visitor->name  }},
@else
{{__('Guten Tag')}} {{$visitor->title. ' '}}{{$visitor->first_name}} {{$visitor->name}},
@endif

{{__('wir freuen uns sehr über Ihren Besuch')}}.

<x-mail::table>
    |{{__('Datum')}}   | {{__('Start')}} | {{__('Ende')}} |
    | :-----------: | :-----------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} |
</x-mail::table>

@if($privacyNoticeUrl)
{{ __('Datenschutzhinweise') }}: {{ $privacyNoticeUrl }}
@endif

</x-mail.message_component>
