@php
    $summary = [
        ['label' => __('Gast'), 'value' => 'Max Mustermann'],
        ['label' => __('Firma'), 'value' => 'Beispiel GmbH'],
        ['label' => __('Datum'), 'value' => '30.03.2026'],
        ['label' => __('Zeit'), 'value' => '09:30 - 11:00'],
        ['label' => __('Host'), 'value' => 'Jonathan Läpple'],
        ['label' => __('Status'), 'value' => __('Entwurf')],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Besuch anlegen') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-content">
        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div class="grid gap-5 md:grid-cols-2">
                        <label class="form-control w-full">
                            <div class="label px-0 pb-2">
                                <span class="label-text text-sm font-medium text-base-content">
                                    {{ __('Gastname') }}
                                </span>
                            </div>
                            <input
                                type="text"
                                class="input input-bordered w-full rounded-2xl"
                                value="Max Mustermann"
                            >
                        </label>

                        <label class="form-control w-full">
                            <div class="label px-0 pb-2">
                                <span class="label-text text-sm font-medium text-base-content">
                                    {{ __('Firma') }}
                                </span>
                            </div>
                            <input
                                type="text"
                                class="input input-bordered w-full rounded-2xl"
                                value="Beispiel GmbH"
                            >
                        </label>

                        <label class="form-control w-full">
                            <div class="label px-0 pb-2">
                                <span class="label-text text-sm font-medium text-base-content">
                                    {{ __('Datum') }}
                                </span>
                            </div>
                            <input
                                type="date"
                                class="input input-bordered w-full rounded-2xl"
                                value="2026-03-30"
                            >
                        </label>

                        <label class="form-control w-full">
                            <div class="label px-0 pb-2">
                                <span class="label-text text-sm font-medium text-base-content">
                                    {{ __('Zeitfenster') }}
                                </span>
                            </div>
                            <input
                                type="text"
                                class="input input-bordered w-full rounded-2xl"
                                value="09:30 - 11:00"
                            >
                        </label>

                        <label class="form-control md:col-span-2">
                            <div class="label px-0 pb-2">
                                <span class="label-text text-sm font-medium text-base-content">
                                    {{ __('Notizen für Empfang und Ablauf') }}
                                </span>
                            </div>
                            <textarea class="textarea textarea-bordered min-h-36 w-full rounded-2xl"></textarea>
                        </label>
                    </div>

                    <div class="mt-6 flex justify-end gap-3">
                        <button type="button" class="btn btn-outline rounded-2xl">
                            {{ __('Cancel') }}
                        </button>
                        <button type="button" class="btn btn-primary rounded-2xl">
                            {{ __('Besuch speichern') }}
                        </button>
                    </div>
                </div>
            </article>

            <aside class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div class="space-y-5">
                        @foreach ($summary as $item)
                            <div class="flex items-start justify-between gap-4 border-b border-base-200 pb-4 last:border-b-0 last:pb-0">
                                <span class="text-sm text-base-content/65">{{ $item['label'] }}</span>
                                <span class="text-right font-semibold text-base-content">{{ $item['value'] }}</span>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>
        </section>
    </div>
</x-app-layout>
