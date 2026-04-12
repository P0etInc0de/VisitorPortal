<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Neue Seite erstellen') }}
                </h1>
            </div>
        </div>
    </x-slot>
<x-bp.card title="{{ __('Neue Seite') }}">

    <x-slot:actions>
        <button type="submit" class="btn btn-primary btn-sm rounded-xl">{{__('Speichern')}}</button>
    </x-slot:actions>

    <form method="POST" action="{{ route('monitor_slides.store') }}">
        @csrf
        <div class="grid gap-5 md:grid-cols-3">
            <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{ __('Herzlich willkommen') }}!"/>
            <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading"/>
            <x-bp.input label="{{ __('Seitennummer') }}" name="slide_number"/>

            <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
            <button type="submit" class="btn btn-primary btn-sm rounded-xl">{{__('Speichern')}}</button>
        </div>
    </form>

</x-bp.card>
</x-app-layout>
