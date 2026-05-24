<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:items-end lg:justify-between">
            <div>
                <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Sicherheit') }}</p>
                <h1 class="mt-2 text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Recovery Codes') }}</h1>
            </div>

            <a href="{{ route('profile.security') }}" class="btn btn-outline rounded-2xl">
                {{ __('Zurück zur Sicherheit') }}
            </a>
        </div>
    </x-slot>

    <div class="grid gap-5">
        @if (session('status'))
            <div class="alert alert-success rounded-3xl">
                <span>
                    @if (session('status') === 'recovery-codes-generated')
                        {{ __('Neue Recovery Codes wurden erstellt.') }}
                    @else
                        {{ __(session('status')) }}
                    @endif
                </span>
            </div>
        @endif

        <div class="alert alert-info rounded-3xl">
            <span>{{ __('Diese Anzeige wurde durch eine frische Sicherheitsbestätigung freigeschaltet und ist nur kurzzeitig verfügbar.') }}</span>
        </div>

        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                <div>
                    <h2 class="text-xl font-semibold tracking-tight text-base-content">
                        {{ __('Einmalig verwendbare Ersatzcodes') }}
                    </h2>
                    <p class="mt-3 max-w-2xl text-sm leading-7 text-base-content/70">
                        {{ __('Bewahre diese Codes sicher auf. Jeder Code kann nur einmal verwendet werden, falls du keinen Zugriff auf deine Authenticator-App hast.') }}
                    </p>
                </div>

                <form method="POST" action="{{ route('profile.security.recovery-codes.regenerate') }}">
                    @csrf
                    <button type="submit" class="btn btn-outline rounded-2xl">
                        {{ __('Neue Codes erzeugen') }}
                    </button>
                </form>
            </div>

            <div class="mt-5 grid gap-2 sm:grid-cols-2 lg:grid-cols-4">
                @forelse ($recoveryCodes as $code)
                    <code class="rounded-2xl border border-base-300 bg-base-200 px-4 py-3 text-sm">{{ $code }}</code>
                @empty
                    <p class="text-sm text-base-content/70">{{ __('Es sind keine aktiven Recovery Codes vorhanden. Erzeuge neue Codes, um wieder Ersatzcodes zu haben.') }}</p>
                @endforelse
            </div>
        </section>
    </div>

    <script>
        window.addEventListener('pageshow', function (event) {
            if (event.persisted) {
                window.location.reload();
            }
        });
    </script>
</x-app-layout>
