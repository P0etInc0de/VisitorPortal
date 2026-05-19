<x-guest-layout>
    <div class="rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm sm:p-7">
        <div class="mb-6">
            <p class="text-sm font-medium uppercase tracking-[0.22em] text-base-content/45">{{ __('Sicherheitsprüfung') }}</p>
            <h1 class="mt-2 text-2xl font-semibold tracking-tight">{{ $title }}</h1>
            <p class="mt-3 text-sm leading-7 text-base-content/70">
                {{ $description }}
            </p>
        </div>

        @php($allowedMethods = $allowedMethods ?? ['totp', 'recovery_code'])

        <form method="POST" action="{{ $action }}" class="space-y-5">
            @csrf

            <div>
                <x-input-label for="code" :value="__('Authenticator-Code')" />
                <x-text-input id="code" class="mt-1 w-full" type="text" name="code" inputmode="numeric" autocomplete="one-time-code" autofocus />
                <x-input-error :messages="$errors->get('code')" class="mt-2" />
            </div>

            @if (in_array('recovery_code', $allowedMethods, true))
                <div class="divider text-xs uppercase tracking-[0.18em] text-base-content/45">{{ __('oder') }}</div>

                <div>
                    <x-input-label for="recovery_code" :value="__('Recovery Code')" />
                    <x-text-input id="recovery_code" class="mt-1 w-full" type="text" name="recovery_code" autocomplete="one-time-code" />
                    <x-input-error :messages="$errors->get('recovery_code')" class="mt-2" />
                </div>
            @endif

            <div class="flex flex-col gap-3 pt-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    @if ($showLogout)
                        <button type="submit" form="security-mfa-challenge-logout" class="btn btn-outline rounded-2xl">
                            {{ __('Abmelden') }}
                        </button>
                    @elseif (! empty($backUrl))
                        <a href="{{ $backUrl }}" class="btn btn-outline rounded-2xl">
                            {{ __('Zurück') }}
                        </a>
                    @endif
                </div>

                <x-primary-button>
                    {{ $button }}
                </x-primary-button>
            </div>
        </form>

        @if ($showLogout)
            <form id="security-mfa-challenge-logout" method="POST" action="{{ route('logout') }}">
                @csrf
            </form>
        @endif
    </div>
</x-guest-layout>
