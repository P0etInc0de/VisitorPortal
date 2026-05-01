<x-app-layout>
    <x-slot name="header">
        <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">
            {{ __('Neue Seite erstellen') }}
        </h1>
    </x-slot>
        <x-bp.card title="{{ __('Neue Seite') }}">
            <form id="createMonitorSlide" method="POST" action="{{ route('monitors.slides.store', [$monitor]) }}" enctype="multipart/form-data">
                @csrf
                <div class="grid gap-4">
                    <x-bp.input label="{{ __('Überschrift') }}" name="heading" value="{{ __('Herzlich willkommen!') }}" class="h-11"/>
                    <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" value="{{ __('Schön, dass Sie da sind!') }}" class="h-11"/>

                    @include('monitorSlides.partials.page-settings', [
                        'slideNumber' => $defaultSlideNumber,
                        'toggleValues' => [
                            ['label' => __('Seite aktiv'), 'name' => 'is_active', 'checked' => 1],
                            ['label' => __('Logo Anzeigen'), 'name' => 'show_logo', 'checked' => 1],
                            ['label' => __('Datum Anzeigen'), 'name' => 'show_date', 'checked' => 1],
                        ],
                    ])
                    {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time"/>--}}

                    @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null])

                    @include('monitorSlides.partials.visitor-selection', ['todayVisits' => $todayVisits])

                    <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
                </div>

                <div class="mt-3 flex justify-end gap-3">
                    <a type="button" class="btn btn-sm rounded-xl" href="{{ route('monitors.edit', $monitor) }}#monitor-pages">{{ __('Abbrechen') }}</a>
                    <input type="submit" class="btn btn-primary btn-sm rounded-xl" value="{{ __('Speichern') }}"/>
                </div>
            </form>
        </x-bp.card>
</x-app-layout>

<script>
    let selectedVisitors = @js(json_decode(old('visitors', '[]'), true) ?? []);
    const errorMessage = document.getElementById('visitorError');
    const manualNameInput = document.getElementById('manualNameInput');
    const maxManualVisitorNameLength = 50;
    const manualVisitorTooLongMessage = @js(__('Manuell hinzugefügte Namen dürfen maximal :count Zeichen lang sein.', ['count' => 50]));

    selectedVisitors = selectedVisitors.map((visitor, index) => ({
        key: visitor.id ? `visit-${visitor.id}` : `manual-${index}`,
        id: visitor.id ?? null,
        name: visitor.name,
    }));

    function addVisitor(visitor) {
        const trimmedName = (visitor.name ?? '').trim();

        if (!trimmedName) return false;

        if (!visitor.id && trimmedName.length > maxManualVisitorNameLength) {
            errorMessage.textContent = manualVisitorTooLongMessage;
            return false;
        }

        if (selectedVisitors.find(v => (visitor.id && v.id === visitor.id) || (!visitor.id && v.name.toLowerCase() === trimmedName.toLowerCase()))) return false;

        if (selectedVisitors.length >= 6){
            errorMessage.textContent = "{{ __('Maximal 6 Besucher je Seite') }}"
            return false;
        }else{
            errorMessage.textContent = "";
        }

        selectedVisitors.push({
            key: visitor.key,
            id: visitor.id ?? null,
            name: trimmedName,
        });
        renderVisitors();

        return true;
    }

    function addManualVisitor() {
        const wasAdded = addVisitor({
            key: `manual-${Date.now()}-${Math.random().toString(36).slice(2, 8)}`,
            id: null,
            name: manualNameInput.value,
        });

        if (wasAdded) {
            manualNameInput.value = '';
        }
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
            chip.className = "badge badge-primary max-w-full gap-2 overflow-hidden rounded-full px-3 py-3";

            const name = document.createElement('span');
            name.className = "max-w-[16rem] truncate sm:max-w-[28rem]";
            name.textContent = visitor.name;

            const button = document.createElement('button');
            button.type = 'button';
            button.textContent = '✕';
            button.className = 'shrink-0';
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
