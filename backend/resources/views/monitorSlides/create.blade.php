<x-app-layout>
    <x-slot name="header">
        <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
            {{ __('Neue Seite erstellen') }}
        </h1>
    </x-slot>
        <x-bp.card title="{{ __('Neue Seite') }}">
            <form id="createMonitorSlide" method="POST" action="{{ route('monitors.slides.store', [$monitor]) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid gap-5">
                    <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{ __('Herzlich willkommen!') }}"/>
                    <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" value="{{ __('Schön, dass Sie da sind!') }}"/>
                    <x-bp.input label="{{ __('Seitennummer') }}" type="number" name="slide_number" value="{{ $defaultSlideNumber }}"/>

                    <x-bp.toggle label="{{ __('Logo Anzeigen') }}" name="show_logo" checked="1"/>
                    <x-bp.toggle label="{{ __('Datum Anzeigen') }}" name="show_date" checked="1"/>
                    {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time"/>--}}

                    @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null])

                    <div>
                        <label class="form-control w-full">
                            <span class="label-text text-sm font-medium text-base-content">{{ __('Ausgewählte Besucher') }}</span>
                        </label>

                        <div id="selectedVisitors" class="flex flex-wrap gap-2 p-3 border rounded-xl bg-base-100 min-h-15">
                            <!-- selected visitors are put here  -->
                        </div>
                        <span class="mt-1 block text-sm text-red-500 font-medium" id="visitorError" ></span>
                    </div>

                        {{-- Hidden inputs for the POST request of the form to laravel --}}
                    <input type="hidden" id="visitorsInput" name="visitors" />
                    <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <a type="button" class="btn-sm rounded-xl btn" href="{{ route('monitors.edit', $monitor) }}">{{ __('Abbrechen') }}</a>
                    <input type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{ __('Speichern') }}"/>
                </div>
            </form>
        </x-bp.card>

        <x-bp.card :title="__('Heutige Besuche')">
                @foreach ($todayVisits as $visit)
                    <x-bp.list-item :title="$visit->title">
                        <x-slot:index>
                            {{ \Carbon\Carbon::parse($visit->scheduled_from)->format('H:i') }}
                        </x-slot:index>
                        <x-slot:items>
                            @foreach($visit->visitors as $visitor)
                                <button
                                    type="button"
                                    class="btn btn-xs btn-outline rounded-full"
                                    onclick="addVisitor({{ $visitor->id }}, '{{ $visitor->title.' ' }}{{ $visitor->first_name }} {{ $visitor->name }}')"
                                >
                                    + {{ $visitor->title.' ' }}{{ $visitor->first_name }} {{ $visitor->name }}
                                </button>
                            @endforeach
                        </x-slot:items>
                        <x-slot:status>
                            <span class="badge {{ $visit['statusClass'] }} rounded-full px-3 py-3 text-sm font-semibold">
                                    {{ $visit['status'] }}
                                </span>
                        </x-slot:status>
                    </x-bp.list-item>
                @endforeach
        </x-bp.card>
</x-app-layout>

<script>
    let selectedVisitors =  [];
    const errorMessage = document.getElementById('visitorError');

    function addVisitor(id, name) {
        if (selectedVisitors.find(v => v.id === id)) return;
        if (selectedVisitors.length >= 6){
            errorMessage.textContent = "{{ __('Maximal 6 Besucher je Seite') }}"
            return;
        }else{
            errorMessage.textContent = "";
        }

        selectedVisitors.push({ id, name });
        renderVisitors();
    }

    function removeVisitor(id) {
        selectedVisitors = selectedVisitors.filter(v => v.id !== id);
        renderVisitors();
        errorMessage.textContent = "";
    }

    function renderVisitors() {
        const container = document.getElementById('selectedVisitors');
        const input = document.getElementById('visitorsInput');

        container.innerHTML = '';

        selectedVisitors.forEach(visitor => {
            const chip = document.createElement('div');
            chip.className = "badge badge-primary gap-2 px-3 py-3 rounded-full";

            chip.innerHTML = `
                ${visitor.name}
                <button type="button" onclick="removeVisitor(${visitor.id})">✕</button>
            `;

            container.appendChild(chip);
        });

        //Save IDs of selected visitors to hidden input
        input.value = JSON.stringify(selectedVisitors);
    }
</script>
