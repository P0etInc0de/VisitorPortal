@php
    $privacyNoticeUrl = config('privacy.notice_url');
@endphp

<x-mail.message_component :message="$message">
# {{ __('Erinnerung an Ihren Besuch') }}

{{ __('dies ist eine Erinnerung an Ihren bevorstehenden Besuch.') }}

{{ __('Bitte melden Sie sich bei Ihrer Ankunft am Empfang.') }}

@if($privacyNoticeUrl)
{{ __('Datenschutzhinweise') }}: {{ $privacyNoticeUrl }}
@endif

</x-mail.message_component>
