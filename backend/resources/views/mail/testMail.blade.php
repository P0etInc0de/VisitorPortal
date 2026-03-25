@component('mail::message')

    This is a test email.

    @component('mail::button', ['url' => config('app.url')])
        Open Website
    @endcomponent

    Thanks,<br>
    {{ config('app.name') }}
@endcomponent
