<x-guest-layout>
    <div class="overflow-hidden rounded-3xl border border-base-300 bg-base-100 shadow-sm">
        <div class="border-b border-base-300/70 px-7 pb-6 pt-6 sm:px-9">
            <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Recovery Codes speichern') }}</p>
            <h1 class="mt-2 text-3xl font-semibold tracking-tight text-base-content">{{ __('Recovery Codes') }}</h1>
            <p class="mt-3 text-sm leading-7 text-base-content/70">
                {{ __('Bewahre diese Codes sicher auf. Jeder Code kann nur einmal verwendet werden.') }}
            </p>
        </div>

        <div class="grid gap-5 p-7 sm:p-9">
            @if (session('status'))
                <div class="alert alert-success rounded-2xl">
                    <span>
                        @if (session('status') === 'two-factor-authentication-confirmed')
                            {{ __('Zwei-Faktor-Authentifizierung wurde aktiviert. Speichere jetzt deine Recovery Codes.') }}
                        @else
                            {{ __(session('status')) }}
                        @endif
                    </span>
                </div>
            @endif

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
</x-guest-layout>
