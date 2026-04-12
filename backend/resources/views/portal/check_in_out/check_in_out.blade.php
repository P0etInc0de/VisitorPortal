<x-app-layout>
    <x-slot name="header">
        <div class="bp-screen-head">
            <div>
                <h1 class="bp-screen-title">{{ __('Besucher Ein- oder Auschecken') }}</h1>
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
                    <p class="text-base-content/70">{{ session('error') }}</p>
                </div>

                <button @click="show = false" class="ml-auto text-base-content/40">✕</button>
            </div>
        </div>
    @endif

    <div class="bp-content" x-data="{ selectedVisitor: { name: '', email: ''}}">
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
                                    <input type="text" name="name" placeholder="Name" class="input input-bordered w-full rounded-2xl" x-model="selectedVisitor.name" required>
                                </label>

                                <label class="form-control w-full">
                                    <div class="label px-0 pb-2">
                                        <span class="label-text text-sm font-medium text-base-content">
                                            {{ __('Email') }}
                                        </span>
                                    </div>
                                    <input type="text" name="email" placeholder="Beispiel@gmail.com" class="input input-bordered w-full rounded-2xl" x-model="selectedVisitor.email" required>
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

        <section class="bp-main-grid bp-main-grid-wide">
            <article class="card rounded-[1.75rem] border border-base-300 bg-base-100 shadow-sm">
                <div class="card-body p-6 sm:p-8">
                    <div class="mb-4 flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                        <h2 class="text-xl font-semibold tracking-tight text-base-content">
                            {{ __('Anstehende Besuche') }}
                        </h2>

                        <a
                            href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.create') ? route('portal.visits.create') : url('/portal/visits/create') }}"
                            class="btn btn-outline btn-sm rounded-xl"
                        >
                            {{ __('Neuen Besuch anlegen') }}
                        </a>
                    </div>
                    <div class="space-y-4">
                        @foreach ($visits as $visit)
                            @php
                                $statusClassVisit = match ($visit->status) {
                                \App\Enums\VisitStatusEnum::Planned->value => 'badge-ghost badge-outline',
                                \App\Enums\VisitStatusEnum::Completed->value => 'badge-success badge-outline',
                                \App\Enums\VisitStatusEnum::Canceled->value => 'badge-error badge-outline',
                                default => 'badge-ghost badge-outline',
                            };

                            @endphp
                            <article x-data="{ open: false }" class="rounded-[1.5rem] border border-base-300 bg-base-100">
                                {{-- Visits --}}
                                <div class="px-5 py-4 flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between cursor-pointer bg-gray-100 rounded-[1.5rem]"
                                     @click="open = !open">
                                    <div class="flex gap-4">
                                        <div class="min-w-[72px] shrink-0">
                                            <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">
                                                {{ $visit->scheduled_from->format('H:i') }}
                                            </div>
                                            <div class="mt-1 text-sm text-base-content/60">
                                                {{ $visit->scheduled_from->format('d.m.y') }}
                                            </div>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="text-xl font-semibold tracking-tight text-base-content">
                                                {{ __('Ansprechpartner:' . ' ' . $visit->responsibleUser->name) }}
                                            </div>
                                            <div class="mt-3 text-base leading-6 text-base-content/80">
                                                {{ __($visit->notes) }}
                                            </div>
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                        <!-- Der Status-Badge bleibt oben -->
                                        <span class="badge {{ $statusClassVisit }} rounded-full px-3 py-3 text-sm font-semibold">
                                            {{$visit->status}}
                                        </span>
                                        <div class="flex flex-row gap-2">
                                            <a href="{{ route('portal.check_in_out.check_in_out_all', ['visit' => $visit->id, 'action' => 'check_in']) }}"
                                               class="btn btn-outline btn-sm rounded-xl">
                                                {{ __('Check-In ALL') }}
                                            </a>

                                            <a href="{{ route('portal.check_in_out.check_in_out_all', ['visit' => $visit->id, 'action' => 'check_out'])}}"
                                               class="btn btn-outline btn-sm rounded-xl">
                                                {{ __('Check-Out ALL') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>

                                {{-- Fold-out Area --}}
                                <div x-show="open" x-transition class="border-t border-base-300 p-4 space-y-3">
                                    @foreach ($visit->visitors as $visitor)
                                        @php
                                            $statusClassVisitor = match ($visitor->status){
                                            \App\Enums\VisitorStatusEnum::Planned => 'badge-ghost badge-outline',
                                            \App\Enums\VisitorStatusEnum::Checked_In => 'badge-success badge-outline',
                                            \App\Enums\VisitorStatusEnum::Checked_Out => 'badge-warning badge-outline',
                                            \App\Enums\VisitorStatusEnum::Canceled => 'badge-error badge-outline',

                                            };
                                        @endphp
                                        <article class="rounded-[1.25rem] border border-base-300 bg-base-100 px-4 py-3 cursor-pointer"
                                                 @click.stop='
                                                    selectedVisitor = {
                                                        name: @json($visitor->first_name . " " . $visitor->name),
                                                        email: @json($visitor->email)
                                                    }
                                                '>
                                            <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                                                <div class="flex gap-4">
                                                    <div class="min-w-0">
                                                        <div class="text-xl font-semibold tracking-tight text-base-content">
                                                            {{ $visitor->first_name . ' ' . $visitor->name }}
                                                        </div>
                                                        <div class="mt-1 text-lg text-base-content/75">
                                                            {{ $visitor->company }}
                                                        </div>

                                                        <div class="mt-3 text-base leading-6 text-base-content/80">
                                                            {{ $visitor->notes }}
                                                        </div>
                                                    </div>
                                                </div>

                                                <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                                                    <span class="badge {{ $statusClassVisitor }} rounded-full px-3 py-3 text-sm font-semibold">
                                                        {{ $visitor->status }}
                                                    </span>
                                                    <a
                                                        href="{{ \Illuminate\Support\Facades\Route::has('portal.visits.show') ? route('portal.visits.show', 1) : url('/portal/visits/1') }}"
                                                        class="btn btn-outline btn-sm rounded-xl"
                                                    >
                                                        {{ __('Details öffnen') }}
                                                    </a>
                                                </div>
                                            </div>
                                        </article>
                                    @endforeach
                                </div>
                            </article>
                        @endforeach
                    </div>
                </div>
            </article>
        </section>
    </div>
</x-app-layout>
