<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="px-7 pb-2 pt-6 text-center sm:px-9">
            <h1 class="text-3xl font-semibold tracking-tight text-base-content">{{ __('Recovery Codes') }}</h1>
            <div class="mt-5 border-t border-base-300/70"></div>
        </div>

        <div class="grid gap-5 p-7 sm:p-9">
            <div class="alert alert-success rounded-2xl">
                <span>
                    @if (session('status'))
                        @if (session('status') === 'two-factor-authentication-confirmed')
                            {{ __('Zwei-Faktor-Authentifizierung wurde aktiviert. Speichere jetzt deine Recovery Codes. Bewahre diese Codes sicher auf!') }}
                        @else
                            {{ __(session('status')) }}
                        @endif
                        <br>
                    @endif
                    {{ __('Jeder Code kann nur einmal verwendet werden.') }}
                </span>
            </div>

            <div class="grid gap-2 sm:grid-cols-2">
                @foreach ($recoveryCodes as $code)
                    <code class="rounded-2xl border border-base-300 bg-base-200 px-4 py-3 text-sm">{{ $code }}</code>
                @endforeach
            </div>

            <div class="flex flex-col gap-3 border-t border-base-300 pt-5 sm:flex-row">
                <form method="POST" action="{{ route('security.mfa.continue') }}">
                    @csrf
                    <button type="submit" class="btn btn-primary rounded-2xl">
                        {{ __('Weiter') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="btn btn-ghost rounded-2xl">
                        {{ __('Abmelden') }}
                    </button>
                </form>
            </div>
        </div>
    </div>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</x-guest-layout>
