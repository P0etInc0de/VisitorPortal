@php
    use Carbon\Carbon;
    $privacyNoticeUrl = config('privacy.notice_url');
 @endphp

<x-mail.message_component :message="$message">
# {{__('Termin für Ihren Besuch')}}

{{ $greeting }}

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
