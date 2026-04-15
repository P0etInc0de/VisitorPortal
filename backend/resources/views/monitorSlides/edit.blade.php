<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <h1 class="text-3xl font-semibold tracking-tight text-slate-900">
                    {{ __('Seite bearbeiten') }}
                </h1>
            </div>
        </div>
    </x-slot>
    <div class="grid gap-6">
        <x-bp.card title="{{ __('Seite bearbeiten') }}">

            <x-slot:actions>
                <div>
                    <input form="editMonitorSlide" type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{__('Speichern')}}"/>
                    <a type="button" class="btn-sm rounded-xl btn" href="{{route('monitors.edit', $monitor)}}">{{__('Abbrechen')}}</a>
                </div>
            </x-slot:actions>

            <form id="editMonitorSlide" method="POST" action="{{ route('monitor_slides.update',$slide) }}">
                @csrf
                @method('PUT')
                <div class="grid gap-5 md:grid-cols-3">
                    <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{$slide->heading}}"/>
                    <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" value="{{$slide->subheading}}"/>
                    <x-bp.input label="{{ __('Seitennummer') }}" name="slide_number" value="{{ $slide->slide_number }}"/>

                    <x-bp.toggle label="{{ __('Logo Anzeigen') }}" name="show_logo" checked="{{$slide->show_logo}}"/>
                    <x-bp.toggle label="{{ __('Datum Anzeigen') }}" name="show_date" checked="{{$slide->show_date}}"/>
                    <x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time" checked="{{$slide->show_time}}"/>

                    <div class="md:col-span-3">
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
            </form>

        </x-bp.card>

        <x-bp.card title="{{ __('Heutige Besuche') }}">
            <div class="space-y-4">
                @foreach ($todayVisits as $visit)
                    <article class="rounded-3xl border border-base-300 bg-base-100 px-5 py-4">
                        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex gap-4">
                                <div class="min-w-18 shrink-0">
                                    <div class="text-2xl font-semibold leading-none tracking-tight text-base-content">
                                        {{ \Carbon\Carbon::parse($visit->scheduled_from)->format('H:i') }}
                                    </div>
                                </div>

                                <div class="min-w-0">
                                    <div class="text-xl font-semibold tracking-tight text-base-content">
                                        {{-- //$visit->title --}}
                                        Titel
                                    </div>
                                </div>

                                <div class="min-w-20">
                                    {{--Placeholder--}}
                                </div>

                                <div class="flex flex-wrap gap-2">
                                    @foreach($visit->visitors as $visitor)
                                        <button
                                            type="button"
                                            class="btn btn-xs btn-outline rounded-full"
                                            onclick="addVisitor({{ $visitor->id }}, '{{ $visitor->first_name }} {{ $visitor->last_name }}')"
                                        >
                                            + {{ $visitor->first_name }} {{ $visitor->last_name }}
                                        </button>
                                    @endforeach
                                </div>
                                <div id="visitorError" class="hidden mt-2 text-sm font-medium text-red-500"></div>
                            </div>

                            <div class="flex shrink-0 flex-col items-start gap-3 lg:items-end">
                            <span class="badge {{ $visit['statusClass'] }} rounded-full px-3 py-3 text-sm font-semibold">
                                {{ $visit['status'] }}
                            </span>

                                <div class="flex flex-wrap gap-2">
                                    {{-- Platz für ein Button --}}
                                </div>
                            </div>
                        </div>
                    </article>
                @endforeach
            </div>

        </x-bp.card>
    </div>
</x-app-layout>

<script>
    var selectedVisitors =  @js($slide->visitors);
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
    renderVisitors();
</script>
