<x-app-layout>
    <x-slot name="header">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
            {{ __('Seite bearbeiten') }}
        </h1>
    </x-slot>
    <x-bp.card title="{{ __('Seite bearbeiten') }}">
        <form id="editMonitorSlide" method="POST" action="{{ route('monitors.slides.update',[$monitor, $slide]) }}" enctype="multipart/form-data">
            @csrf
            @method('PUT')
            <div class="grid gap-4">
                <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{$slide->heading}}" class="h-11"/>
                <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" value="{{$slide->subheading}}" class="h-11"/>

                @include('monitorSlides.partials.page-settings', [
                    'slideNumber' => $slide->slide_number,
                    'toggleValues' => [
                        ['label' => __('Seite aktiv'), 'name' => 'is_active', 'checked' => $slide->is_active],
                        ['label' => __('Logo Anzeigen'), 'name' => 'show_logo', 'checked' => $slide->show_logo],
                        ['label' => __('Datum Anzeigen'), 'name' => 'show_date', 'checked' => $slide->show_date],
                    ],
                ])
                {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time" checked="{{$slide->show_time}}"/>--}}

                @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => $slide])

                <div class="grid gap-3 rounded-2xl border border-slate-200 bg-slate-50 p-3.5 md:grid-cols-[minmax(0,1fr)_auto] md:items-end">
                    <x-bp.input label="{{ __('Eigenen Namen hinzufügen') }}" name="manual_name" id="manualNameInput" value="" placeholder="{{ __('Name für Begrüßungsseite') }}" onkeydown="if (event.key === 'Enter') { event.preventDefault(); addManualVisitor(); }" class="h-11"/>
                    <button type="button" class="btn btn-outline btn-sm rounded-xl" onclick="addManualVisitor()">{{ __('Zur Liste hinzufügen') }}</button>
                </div>

                <div>
                    <label class="form-control w-full">
                        <span class="label-text text-sm font-medium text-base-content">{{ __('Ausgewählte Besucher') }}</span>
                    </label>

                    <div id="selectedVisitors" class="flex min-h-14 flex-wrap gap-2 rounded-xl border border-slate-200 bg-base-100 p-2.5">
                        <!-- selected visitors are put here  -->
                    </div>
                    <span class="mt-1 block text-sm text-red-500 font-medium" id="visitorError" ></span>
                </div>

                {{-- Hidden inputs for the POST request of the form to laravel --}}
                <input type="hidden" id="visitorsInput" name="visitors" />
            </div>

            <div class="mt-5 flex justify-end gap-3">
                <a type="button" class="btn-sm rounded-xl btn" href="{{ route('monitors.edit', $monitor) }}#monitor-pages">{{ __('Abbrechen') }}</a>
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
                            onclick='addVisitor(@json(["key" => "visit-{$visitor->id}", "id" => $visitor->id, "name" => trim($visitor->title." ".$visitor->first_name." ".$visitor->name)]))'
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
    var selectedVisitors = @js(json_decode(old('visitors', 'null'), true) ?? $slide->visitors);
    const errorMessage = document.getElementById('visitorError');
    const manualNameInput = document.getElementById('manualNameInput');

    selectedVisitors = selectedVisitors.map((visitor, index) => ({
        key: visitor.id ? `visit-${visitor.id}` : `manual-${index}`,
        id: visitor.id ?? null,
        name: visitor.name,
    }));

    function addVisitor(visitor) {
        const trimmedName = (visitor.name ?? '').trim();

        if (!trimmedName) return;
        if (selectedVisitors.find(v => (visitor.id && v.id === visitor.id) || (!visitor.id && v.name.toLowerCase() === trimmedName.toLowerCase()))) return;

        if (selectedVisitors.length >= 6){
            errorMessage.textContent = "{{ __('Maximal 6 Besucher je Seite') }}"
            return;
        }else{
            errorMessage.textContent = "";
        }

        selectedVisitors.push({
            key: visitor.key,
            id: visitor.id ?? null,
            name: trimmedName,
        });
        renderVisitors();
    }

    function addManualVisitor() {
        addVisitor({
            key: `manual-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            id: null,
            name: manualNameInput.value,
        });

        manualNameInput.value = '';
    }

    function removeVisitor(key) {
        selectedVisitors = selectedVisitors.filter(v => v.key !== key);
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

            const name = document.createElement('span');
            name.textContent = visitor.name;

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = '✕';
            button.addEventListener('click', () => removeVisitor(visitor.key));

            chip.appendChild(name);
            chip.appendChild(button);
            container.appendChild(chip);
        });

        //Save IDs of selected visitors to hidden input
        input.value = JSON.stringify(selectedVisitors.map(({ id, name }) => ({ id, name })));
    }
    renderVisitors();
</script>
