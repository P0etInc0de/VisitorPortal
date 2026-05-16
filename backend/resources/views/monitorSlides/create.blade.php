@php
    $defaultHeading = config('branding.monitor_slide_heading', 'Welcome!');
    $defaultSubheading = config('branding.monitor_fallback_subheading', "We're glad you're here.");
@endphp

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
                    <x-bp.input label="{{ __('Überschrift') }}" name="heading" :value="$defaultHeading" class="h-11"/>
                    <x-bp.input label="{{ __('Unterüberschrift') }}" name="subheading" :value="$defaultSubheading" class="h-11"/>

                    @include('monitorSlides.partials.page-settings', [
                        'slideNumber' => $defaultSlideNumber,
                        'displayMode' => $monitor->monitor_display_mode ?: \App\Models\Monitor::DEFAULT_MONITOR_DISPLAY_MODE,
                        'toggleValues' => [
                            ['label' => __('Seite aktiv'), 'name' => 'is_active', 'checked' => 1],
                            ['label' => __('Logo Anzeigen'), 'name' => 'show_logo', 'checked' => 1],
                            ['label' => __('Datum Anzeigen'), 'name' => 'show_date', 'checked' => 1],
                        ],
                    ])
                    {{--<x-bp.toggle label="{{ __('Zeit Anzeigen') }}" name="show_time"/>--}}

                    @include('monitorSlides.partials.background-settings', ['monitor' => $monitor, 'slide' => null])

                    @include('monitorSlides.partials.visitor-selection', [
                        'todayVisits' => $todayVisits,
                        'cancelUrl' => route('monitors.edit', $monitor).'#monitor-pages',
                        'submitLabel' => __('Speichern'),
                    ])

                    <input type="hidden" value="{{ $monitor->id }}" name="monitor_id"/>
                </div>
            </form>
        </x-bp.card>
</x-app-layout>

<script>
    let selectedVisitors = @js(json_decode(old('visitors', '[]'), true) ?? []);
    const visitorSources = @js($todayVisitors->mapWithKeys(fn ($visitor) => [(string) $visitor->id => [
        'title' => $visitor->title,
        'first_name' => $visitor->first_name,
        'name' => $visitor->name,
        'company' => $visitor->company,
    ]])->all());
    const errorMessage = document.getElementById('visitorError');
    const manualNameInput = document.getElementById('manualNameInput');
    const monitorDisplayModeSelect = document.getElementById('monitorDisplayMode');
    const maxManualVisitorNameLength = 50;
    const manualVisitorTooLongMessage = @js(__('Manuell hinzugefügte Namen dürfen maximal :count Zeichen lang sein.', ['count' => 50]));
    const displayModes = @js([
        'companyOnly' => \App\Models\Monitor::DISPLAY_COMPANY_ONLY,
        'titleFirstNameLastInitial' => \App\Models\Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
        'titleFirstInitialLastName' => \App\Models\Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME,
        'titleFullName' => \App\Models\Monitor::DISPLAY_TITLE_FULL_NAME,
    ]);

    selectedVisitors = selectedVisitors.map((visitor, index) => {
        const source = visitor.source ?? (visitor.id ? visitorSources[String(visitor.id)] ?? null : null);

        return {
            key: visitor.id ? `visit-${visitor.id}` : `manual-${index}`,
            id: visitor.id ?? null,
            name: source ? formatDisplayName(source) : visitor.name,
            source,
            manual: !source,
        };
    });

    selectedVisitors = deduplicateSelectedVisitors(selectedVisitors);

    monitorDisplayModeSelect?.addEventListener('change', () => {
        selectedVisitors = selectedVisitors.map(visitor => {
            if (!visitor.source) return visitor;

            return {
                ...visitor,
                name: formatDisplayName(visitor.source),
            };
        });

        selectedVisitors = deduplicateSelectedVisitors(selectedVisitors);
        renderVisitors();
    });

    function initial(value) {
        const trimmed = (value ?? '').trim();

        return trimmed ? `${trimmed.charAt(0)}.` : '';
    }

    function joinParts(parts) {
        return parts.filter(part => (part ?? '').trim() !== '').join(' ').trim();
    }

    function formatDisplayName(source) {
        const title = (source.title ?? '').trim();
        const firstName = (source.first_name ?? '').trim();
        const lastName = (source.name ?? '').trim();
        const company = (source.company ?? '').trim();
        const mode = monitorDisplayModeSelect?.value ?? displayModes.titleFirstNameLastInitial;

        if (mode === displayModes.companyOnly) {
            return company || @js(__('Gast'));
        }

        if (mode === displayModes.titleFirstInitialLastName) {
            return joinParts([title, initial(firstName), lastName]) || company || @js(__('Gast'));
        }

        if (mode === displayModes.titleFullName) {
            return joinParts([title, firstName, lastName]) || company || @js(__('Gast'));
        }

        return joinParts([title, firstName, initial(lastName)]) || company || @js(__('Gast'));
    }

    function deduplicateSelectedVisitors(visitors) {
        const seen = new Set();

        return visitors.filter(visitor => {
            const key = visitor.name.trim().toLowerCase();

            if (seen.has(key)) return false;

            seen.add(key);
            return true;
        });
    }

    function addVisitor(visitor) {
        const trimmedName = (visitor.source ? formatDisplayName(visitor.source) : visitor.name ?? '').trim();

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
            source: visitor.source ?? null,
            manual: !visitor.source,
        });

        selectedVisitors = deduplicateSelectedVisitors(selectedVisitors);
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
