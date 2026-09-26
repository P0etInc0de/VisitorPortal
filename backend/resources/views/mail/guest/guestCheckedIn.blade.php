@php
    use Carbon\Carbon;
    $privacyNoticeUrl = config('privacy.notice_url');
 @endphp

<x-mail.message_component :message="$message">
# {{__('Ihr Check-in wurde bestätigt')}}

{{ $greeting }}

{{__('vielen Dank für Ihre Unterschrift. Ihr Besuch ist jetzt eingecheckt')}}.

<x-mail::table>
    |{{__('Datum')}}   | {{__('Start')}} | {{__('Ende')}} |
    | :-----------: | :-----------: | :------------: |
    | {{ Carbon::parse($visit->scheduled_from)->format('d.m.Y') }} | {{ Carbon::parse($visit->scheduled_from)->format('H:i') }} | {{ Carbon::parse($visit->scheduled_until)->format('H:i') }} |
</x-mail::table>

@if($signature)
{{ __('Ihre Unterschrift ist als Anhang beigefügt.') }}
@endif

@if($privacyNoticeUrl)
{{ __('Datenschutzhinweise') }}: {{ $privacyNoticeUrl }}
@endif

</x-mail.message_component>
