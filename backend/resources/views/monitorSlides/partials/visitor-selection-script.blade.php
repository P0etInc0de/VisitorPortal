<script>
    let selectedVisitors = @js($initialVisitors);
    const visitorSources = @js($todayVisitors->mapWithKeys(fn ($visitor) => [(string) $visitor->id => [
        'title' => $visitor->title,
        'first_name' => $visitor->first_name,
        'name' => $visitor->name,
        'company' => $visitor->company,
    ]])->all());
    const errorMessage = document.getElementById('visitorError');
    const manualNameInput = document.getElementById('manualNameInput');
    const monitorDisplayModeSelect = document.getElementById('monitorDisplayMode');
    const visitorCount = document.getElementById('visitorCount');
    const visitorLimitHint = document.getElementById('visitorLimitHint');
    const maxSelectedVisitors = 6;
    const maxManualVisitorNameLength = 50;
    const manualVisitorTooLongMessage = @js(__('Manuell hinzugefügte Namen dürfen maximal :count Zeichen lang sein.', ['count' => 50]));
    const duplicateVisitorMessage = errorMessage?.dataset.duplicateMessage ?? @js(__('Besucher ist bereits hinzugefügt.'));
    const visitorLimitDescription = @js(__('Maximal 6 Besucher je Seite'));
    const visitorLimitReachedMessage = errorMessage?.dataset.limitMessage ?? @js(__('Maximal 6 Besucher je Seite erreicht.'));
    const visitorCountMessage = @js(__(':count von :max Besuchern ausgewählt', ['count' => '__COUNT__', 'max' => '__MAX__']));
    const displayModes = @js([
        'companyOnly' => \App\Models\Monitor::DISPLAY_COMPANY_ONLY,
        'titleFirstNameLastInitial' => \App\Models\Monitor::DISPLAY_TITLE_FIRST_NAME_LAST_INITIAL,
        'titleFirstInitialLastName' => \App\Models\Monitor::DISPLAY_TITLE_FIRST_INITIAL_LAST_NAME,
        'titleFullName' => \App\Models\Monitor::DISPLAY_TITLE_FULL_NAME,
    ]);
    let visitorFeedbackTimeout = null;

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

    selectedVisitors = deduplicateSelectedVisitors(selectedVisitors).slice(0, maxSelectedVisitors);

    monitorDisplayModeSelect?.addEventListener('change', () => {
        selectedVisitors = selectedVisitors.map(visitor => {
            if (!visitor.source) return visitor;

            return {
                ...visitor,
                name: formatDisplayName(visitor.source),
            };
        });

        selectedVisitors = deduplicateSelectedVisitors(selectedVisitors).slice(0, maxSelectedVisitors);
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

    function normalizedVisitorName(value) {
        return (value ?? '').trim().toLowerCase();
    }

    function deduplicateSelectedVisitors(visitors) {
        const seen = new Set();

        return visitors.filter(visitor => {
            const key = normalizedVisitorName(visitor.name);

            if (!key || seen.has(key)) return false;

            seen.add(key);
            return true;
        });
    }

    function visitorAlreadySelected(visitor, trimmedName) {
        const normalizedName = normalizedVisitorName(trimmedName);

        return selectedVisitors.some(selectedVisitor => {
            return (visitor.id && selectedVisitor.id === visitor.id)
                || normalizedVisitorName(selectedVisitor.name) === normalizedName;
        });
    }

    function showVisitorFeedback(message) {
        if (!errorMessage) return;

        if (visitorFeedbackTimeout) {
            window.clearTimeout(visitorFeedbackTimeout);
        }

        errorMessage.textContent = message;
        errorMessage.classList.remove('hidden');
        errorMessage.classList.add('block');

        visitorFeedbackTimeout = window.setTimeout(() => {
            clearVisitorFeedback();
        }, 4000);
    }

    function clearVisitorFeedback() {
        if (!errorMessage) return;

        if (visitorFeedbackTimeout) {
            window.clearTimeout(visitorFeedbackTimeout);
            visitorFeedbackTimeout = null;
        }

        errorMessage.textContent = '';
        errorMessage.classList.add('hidden');
        errorMessage.classList.remove('block');
    }

    function updateVisitorLimitState() {
        const count = selectedVisitors.length;

        if (visitorCount) {
            visitorCount.textContent = visitorCountMessage
                .replace('__COUNT__', String(count))
                .replace('__MAX__', String(maxSelectedVisitors));
        }

        if (!visitorLimitHint) return;

        if (count >= maxSelectedVisitors) {
            visitorLimitHint.textContent = visitorLimitReachedMessage;
            visitorLimitHint.classList.remove('text-base-content/60');
            visitorLimitHint.classList.add('text-warning');

            return;
        }

        visitorLimitHint.textContent = visitorLimitDescription;
        visitorLimitHint.classList.add('text-base-content/60');
        visitorLimitHint.classList.remove('text-warning');
    }

    function addVisitor(visitor) {
        const trimmedName = (visitor.source ? formatDisplayName(visitor.source) : visitor.name ?? '').trim();

        if (!trimmedName) return false;

        if (!visitor.id && trimmedName.length > maxManualVisitorNameLength) {
            showVisitorFeedback(manualVisitorTooLongMessage);
            return false;
        }

        if (visitorAlreadySelected(visitor, trimmedName)) {
            showVisitorFeedback(duplicateVisitorMessage);
            return false;
        }

        if (selectedVisitors.length >= maxSelectedVisitors) {
            showVisitorFeedback(visitorLimitReachedMessage);
            updateVisitorLimitState();
            return false;
        }

        selectedVisitors.push({
            key: visitor.key,
            id: visitor.id ?? null,
            name: trimmedName,
            source: visitor.source ?? null,
            manual: !visitor.source,
        });

        selectedVisitors = deduplicateSelectedVisitors(selectedVisitors).slice(0, maxSelectedVisitors);
        clearVisitorFeedback();
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
        clearVisitorFeedback();
    }

    function renderVisitors() {
        const container = document.getElementById('selectedVisitors');
        const input = document.getElementById('visitorsInput');

        container.innerHTML = '';

        selectedVisitors.forEach(visitor => {
            const chip = document.createElement('div');
            chip.className = 'badge badge-primary max-w-full gap-2 overflow-hidden rounded-full px-3 py-3';

            const name = document.createElement('span');
            name.className = 'max-w-[16rem] truncate sm:max-w-[28rem]';
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

        input.value = JSON.stringify(selectedVisitors.map(({ id, name }) => ({ id, name })));
        updateVisitorLimitState();
    }

    renderVisitors();
</script>
