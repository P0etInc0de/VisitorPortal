<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Besucherausweis drucken') }}</h1>
            </div>
        </div>
    </x-slot>

    @if(session('success'))
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            class="fixed top-5 right-5 z-50 max-w-sm w-full"
        >
            <div class="bg-base-100 border-l-4 border-success shadow-xl rounded-xl p-4 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-success" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>

                <div class="text-sm">
                    <p class="font-semibold">{{ __('Erfolgreich!') }}</p>
                    <p class="text-base-content/70">{{ session('success') }}</p>
                </div>

                <button @click="show = false" class="ml-auto text-base-content/40">✕</button>
            </div>
        </div>
    @endif

    @if(session('error')||$errors->any())
        <div
            x-data="{ show: true }"
            x-init="setTimeout(() => show = false, 5000)"
            x-show="show"
            x-transition
            class="fixed top-5 right-5 z-50 max-w-sm w-full"
        >
            <div class="bg-base-100 border-l-4 border-error shadow-xl rounded-xl p-4 flex items-center gap-3">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 text-error" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                </svg>

                <div class="text-sm">
                    <p class="font-semibold">{{ __('Woops!') }}</p>
                    <p class="text-base-content/70">{{ __('Etwas ist schiefgelaufen') }}</p>
                </div>

                <button @click="show = false" class="ml-auto text-base-content/40">✕</button>
            </div>
        </div>
    @endif

    <div class="bp-content">
        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-3xl border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div>
                        <form method="post" action="{{route('reception.badge')}}">
                            @csrf
                            <div class="grid gap-5 md:grid-cols-2">

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Gastname') }}</span>
                                    </div>
                                    <input type="text" name="name" placeholder="Name" class="input input-bordered w-full rounded-2xl" required>
                                </label>

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">{{ __('Email') }}</span>
                                    </div>
                                    <input type="text" name="email" placeholder="Beispiel@gmail.com" class="input input-bordered w-full rounded-2xl" required>
                                </label>

                                <label class="form-control w-full md:col-span-2 ">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Druckformat auswählen') }}
                                        </span>
                                    </div>
                                    <select name="paper_format" class="select select-bordered w-full rounded-2xl" required>
                                        <option value="a4" selected>{{ __('DIN A4 (Standard)') }}</option>
                                        <option value="badge">{{ __('Ausweisformat (85x55mm)') }}</option>
                                    </select>
                                </label>
                            </div>

                            <div class="mt-6 flex justify-end gap-3">
                                <button type="submit" name="action" value="check_out" class="btn btn-primary rounded-2xl">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" />
                                    </svg>
                                    {{ __('PDF herunterladen') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
