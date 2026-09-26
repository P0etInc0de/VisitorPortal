<div>
    @if ($screen === 'home')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-4xl flex-col items-center justify-center gap-10 px-6 py-12">
            <div class="text-center">
                <h1 class="text-4xl font-bold tracking-tight sm:text-5xl">{{ __('Herzlich willkommen') }}</h1>
                <p class="mt-3 text-xl text-base-content/70">{{ __('Bitte wählen Sie Ihr Anliegen.') }}</p>
            </div>

            <div class="grid w-full gap-6 sm:grid-cols-2">
                <button type="button" wire:click="showSearch" class="btn btn-primary h-40 flex-col gap-2 rounded-3xl text-xl font-semibold">
                    {{ __('Ich habe einen Termin') }}
                </button>

                <button type="button" wire:click="showQr" class="btn btn-secondary h-40 flex-col gap-2 rounded-3xl text-xl font-semibold">
                    {{ __('QR-Code scannen') }}
                </button>

                @if ($walkInEnabled)
                    <button type="button" wire:click="showWalkIn" class="btn btn-outline h-40 flex-col gap-2 rounded-3xl text-xl font-semibold">
                        {{ __('Ich habe keinen Termin') }}
                    </button>
                @endif

                @if ($checkOutEnabled)
                    <button type="button" wire:click="showCheckout" class="btn btn-outline h-40 flex-col gap-2 rounded-3xl text-xl font-semibold">
                        {{ __('Auschecken') }}
                    </button>
                @endif
            </div>
        </div>
    @endif

    @if ($screen === 'signature')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-4xl flex-col gap-6 px-6 py-10">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('Unterschrift') }}</h2>
                <button type="button" wire:click="cancelSignature" class="btn btn-ghost btn-lg rounded-2xl">{{ __('Zurück') }}</button>
            </div>

            <p class="text-lg text-base-content/70">
                {{ __('Bitte unterschreiben Sie, um :name einzuchecken.', ['name' => $signatureContext]) }}
            </p>

            <div
                x-data
                x-init="window.kioskSignatureInit($refs.canvas, $refs.data)"
                class="rounded-3xl border border-base-300 bg-white p-3 shadow-sm"
            >
                <canvas
                    x-ref="canvas"
                    class="h-64 w-full touch-none select-none rounded-2xl bg-white"
                    style="touch-action: none; -webkit-user-select: none; -webkit-touch-callout: none;"
                ></canvas>
                <input type="hidden" x-ref="data" wire:model="signatureData">
            </div>

            @error('signature')
                <p class="text-error">{{ $message }}</p>
            @enderror

            <div class="flex flex-col gap-4 sm:flex-row">
                <button
                    type="button"
                    x-on:click="window.kioskSignatureClear($refs.canvas)"
                    class="btn btn-outline btn-lg rounded-2xl"
                >
                    {{ __('Löschen') }}
                </button>
                <button
                    type="button"
                    wire:click="confirmSignature"
                    class="btn btn-primary btn-lg flex-1 rounded-2xl text-xl"
                    wire:loading.attr="disabled"
                    wire:target="confirmSignature"
                >
                    {{ __('Bestätigen & einchecken') }}
                </button>
            </div>
        </div>
    @endif

    @if ($screen === 'success')
        <div
            class="mx-auto flex min-h-[80vh] w-full max-w-3xl flex-col items-center justify-center gap-8 px-6 py-12 text-center"
            x-data="{ timer: null }"
            x-init="timer = setTimeout(() => $wire.showHome(), {{ $autoResetSeconds * 1000 }})"
        >
            <div @class([
                'rounded-3xl border p-10 shadow-sm',
                'border-success/40 bg-success/10' => $statusTone === 'success',
                'border-info/40 bg-info/10' => $statusTone === 'info',
                'border-error/40 bg-error/10' => $statusTone === 'error',
            ])>
                <p class="text-2xl font-semibold sm:text-3xl">{{ $statusMessage }}</p>
            </div>

            <button type="button" wire:click="showHome" class="btn btn-primary btn-lg rounded-2xl px-10 text-xl">
                {{ __('Fertig') }}
            </button>
        </div>
    @endif

    @if ($screen === 'search')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-4xl flex-col gap-6 px-6 py-10">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('Check-in') }}</h2>
                <button type="button" wire:click="showHome" class="btn btn-ghost btn-lg rounded-2xl">{{ __('Zurück') }}</button>
            </div>

            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                class="input input-bordered input-lg w-full rounded-2xl text-2xl"
                placeholder="{{ __('Name oder Firma eingeben…') }}"
                autocomplete="off"
                autofocus
            >

            <div class="grid gap-4">
                @forelse ($checkInResults as $result)
                    <div class="flex flex-col gap-4 rounded-2xl border border-base-300 bg-base-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-xl font-semibold">{{ $result['display_name'] }}</div>
                            <div class="mt-1 text-base text-base-content/70">
                                {{ $result['company'] ?: '–' }} &middot; {{ $result['host'] ?: '–' }} &middot; {{ $result['visit_time'] }}
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="checkInVisitor({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                            class="btn btn-primary btn-lg rounded-2xl"
                        >
                            {{ __('Einchecken') }}
                        </button>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-base-300 px-4 py-8 text-center text-lg text-base-content/60">
                        @if (strlen(trim($search)) >= 2)
                            {{ __('Keine passenden Besuche gefunden.') }}
                        @else
                            {{ __('Bitte mindestens 2 Zeichen eingeben.') }}
                        @endif
                    </p>
                @endforelse
            </div>
        </div>
    @endif

    @if ($screen === 'qr')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-3xl flex-col items-center justify-center gap-8 px-6 py-12">
            <h2 class="text-center text-3xl font-bold tracking-tight">{{ __('QR-Code scannen') }}</h2>
            <p class="text-center text-lg text-base-content/70">
                {{ __('Halten Sie Ihren QR-Code vor den Scanner. Alternativ können Sie den Code eingeben.') }}
            </p>

            <form wire:submit="submitCode" class="w-full max-w-xl">
                <input
                    type="text"
                    wire:model="code"
                    class="input input-bordered input-lg w-full rounded-2xl text-center font-mono text-2xl"
                    placeholder="{{ __('Code eingeben') }}"
                    autocomplete="off"
                    autofocus
                >
                @error('code')
                    <p class="mt-3 text-center text-error">{{ $message }}</p>
                @enderror

                <div class="mt-6 flex flex-col gap-4 sm:flex-row">
                    <button type="submit" class="btn btn-primary btn-lg flex-1 rounded-2xl text-xl" wire:loading.attr="disabled" wire:target="submitCode">
                        {{ __('Einchecken') }}
                    </button>
                    <button type="button" wire:click="showHome" class="btn btn-ghost btn-lg flex-1 rounded-2xl text-xl">
                        {{ __('Zurück') }}
                    </button>
                </div>
            </form>
        </div>
    @endif

    @if ($screen === 'walkin')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-3xl flex-col gap-6 px-6 py-10">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('Ohne Termin anmelden') }}</h2>
                <button type="button" wire:click="showHome" class="btn btn-ghost btn-lg rounded-2xl">{{ __('Zurück') }}</button>
            </div>

            <form wire:submit="registerWalkIn" class="grid gap-5 rounded-3xl border border-base-300 bg-base-100 p-6 shadow-sm">
                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label px-0 pb-1.5"><span class="label-text text-lg font-medium">{{ __('Vorname') }}</span></label>
                        <input type="text" wire:model="walkIn.first_name" class="input input-bordered input-lg w-full rounded-2xl">
                        @error('walkIn.first_name') <p class="mt-2 text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label px-0 pb-1.5"><span class="label-text text-lg font-medium">{{ __('Nachname') }}</span></label>
                        <input type="text" wire:model="walkIn.name" class="input input-bordered input-lg w-full rounded-2xl">
                        @error('walkIn.name') <p class="mt-2 text-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div class="grid gap-5 sm:grid-cols-2">
                    <div>
                        <label class="label px-0 pb-1.5"><span class="label-text text-lg font-medium">{{ __('E-Mail') }}</span></label>
                        <input type="email" wire:model="walkIn.email" class="input input-bordered input-lg w-full rounded-2xl">
                        @error('walkIn.email') <p class="mt-2 text-error">{{ $message }}</p> @enderror
                    </div>
                    <div>
                        <label class="label px-0 pb-1.5"><span class="label-text text-lg font-medium">{{ __('Telefon') }}</span></label>
                        <input type="tel" wire:model="walkIn.phone" class="input input-bordered input-lg w-full rounded-2xl">
                        @error('walkIn.phone') <p class="mt-2 text-error">{{ $message }}</p> @enderror
                    </div>
                </div>

                <div>
                    <label class="label px-0 pb-1.5"><span class="label-text text-lg font-medium">{{ __('Firma') }}</span></label>
                    <input type="text" wire:model="walkIn.company" class="input input-bordered input-lg w-full rounded-2xl">
                    @error('walkIn.company') <p class="mt-2 text-error">{{ $message }}</p> @enderror
                </div>

                <button type="submit" class="btn btn-primary btn-lg w-full rounded-2xl text-xl" wire:loading.attr="disabled" wire:target="registerWalkIn">
                    {{ __('Anmelden & einchecken') }}
                </button>
            </form>
        </div>
    @endif

    @if ($screen === 'checkout')
        <div class="mx-auto flex min-h-[80vh] w-full max-w-4xl flex-col gap-6 px-6 py-10">
            <div class="flex items-center justify-between gap-4">
                <h2 class="text-3xl font-bold tracking-tight">{{ __('Auschecken') }}</h2>
                <button type="button" wire:click="showHome" class="btn btn-ghost btn-lg rounded-2xl">{{ __('Zurück') }}</button>
            </div>

            <input
                type="search"
                wire:model.live.debounce.400ms="search"
                class="input input-bordered input-lg w-full rounded-2xl text-2xl"
                placeholder="{{ __('Name oder Firma eingeben…') }}"
                autocomplete="off"
                autofocus
            >

            <div class="grid gap-4">
                @forelse ($checkOutResults as $result)
                    <div class="flex flex-col gap-4 rounded-2xl border border-base-300 bg-base-100 p-5 sm:flex-row sm:items-center sm:justify-between">
                        <div class="min-w-0">
                            <div class="text-xl font-semibold">{{ $result['display_name'] }}</div>
                            <div class="mt-1 text-base text-base-content/70">
                                {{ $result['company'] ?: '–' }} &middot; {{ $result['host'] ?: '–' }} &middot; {{ $result['visit_time'] }}
                            </div>
                        </div>
                        <button
                            type="button"
                            wire:click="checkOutVisitor({{ $result['visit_id'] }}, {{ $result['visitor_id'] }})"
                            class="btn btn-secondary btn-lg rounded-2xl"
                        >
                            {{ __('Auschecken') }}
                        </button>
                    </div>
                @empty
                    <p class="rounded-2xl border border-dashed border-base-300 px-4 py-8 text-center text-lg text-base-content/60">
                        @if (strlen(trim($search)) >= 2)
                            {{ __('Keine eingecheckten Gäste gefunden.') }}
                        @else
                            {{ __('Bitte mindestens 2 Zeichen eingeben.') }}
                        @endif
                    </p>
                @endforelse
            </div>
        </div>
    @endif
</div>
