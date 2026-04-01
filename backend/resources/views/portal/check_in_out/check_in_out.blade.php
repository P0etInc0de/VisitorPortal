@php
    $summary = [
        ['label' => __('Gast'), 'value' => 'Max Mustermann'],
        ['label' => __('Email'), 'value' => 'Beispiel@gmail.com'],
    ];
@endphp

<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Besucher Ein- oder Auschecken') }}</h1>
            </div>
        </div>
    </x-slot>
    <div class="bp-content">
        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div>
                        <form method="post" action="{{route('portal.check_in_out.store')}}">
                            @csrf
                            <div class="grid gap-5 md:grid-cols-2">

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Gastname') }}
                                        </span>
                                    </div>
                                    <input type="text" name="name" placeholder="Name" class="input input-bordered w-full rounded-2xl" required>
                                </label>

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Email') }}
                                        </span>
                                    </div>
                                    <input type="text" name="email" placeholder="Beispiel@gmail.com" class="input input-bordered w-full rounded-2xl" required>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="submit" name="action" value="check_out" class="btn btn-outline rounded-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Check-Out') }}
                                </button>

                                <button type="submit" name="action" value="check_in" class="btn btn-primary rounded-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                                    </svg>
                                    {{ __('Check-In') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
