document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.bp-av-page');

    if (!page || page.dataset.bpAjaxReady === '1') {
        return;
    }

    page.dataset.bpAjaxReady = '1';

    const rowToggleTimers = new WeakMap();

    const toggleVisitRow = (row, forceExpanded = null) => {
        if (!row) {
            return;
        }

        const targetId = row.getAttribute('data-bp-toggle-details');
        const target = targetId ? document.getElementById(targetId) : null;

        if (!target) {
            return;
        }

        const isExpanded = row.getAttribute('aria-expanded') === 'true';
        const nextExpanded = forceExpanded === null ? !isExpanded : forceExpanded;

        row.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
        row.closest('.bp-av-entry')?.classList.toggle('bp-av-entry-expanded', nextExpanded);
        target.hidden = !nextExpanded;
    };

    const syncSelectAllState = () => {
        const items = Array.from(document.querySelectorAll('[data-bp-select-item]'));
        const selectAllBox = document.querySelector('[data-bp-select-all]');

        if (selectAllBox) {
            selectAllBox.checked = items.length > 0 && items.every((checkbox) => checkbox.checked);
        }
    };

    const syncActionButtonWidth = () => {
        const pageRoot = document.querySelector('.bp-av-page');

        if (!pageRoot) {
            return;
        }

        const buttons = Array.from(document.querySelectorAll('.bp-av-action-cell .bp-av-row-btn'));

        if (buttons.length === 0) {
            pageRoot.style.removeProperty('--bp-av-action-width');
            return;
        }

        const probe = document.createElement('button');
        probe.type = 'button';
        probe.className = 'btn btn-primary btn-sm';
        probe.style.position = 'absolute';
        probe.style.visibility = 'hidden';
        probe.style.pointerEvents = 'none';
        probe.style.left = '-9999px';
        probe.style.top = '-9999px';
        probe.style.width = 'auto';
        probe.style.minWidth = '0';
        probe.style.height = '1.72rem';
        probe.style.paddingLeft = '.56rem';
        probe.style.paddingRight = '.56rem';
        probe.style.borderRadius = '.82rem';
        probe.style.whiteSpace = 'nowrap';
        document.body.appendChild(probe);

        let maxWidth = 0;

        buttons.forEach((button) => {
            probe.textContent = button.textContent.trim();
            maxWidth = Math.max(maxWidth, Math.ceil(probe.getBoundingClientRect().width));
        });

        probe.remove();

        if (maxWidth > 0) {
            pageRoot.style.setProperty('--bp-av-action-width', `${maxWidth}px`);
        }
    };

    const extractVisitEntryState = (entry) => {
        if (!entry) {
            return {
                isExpanded: false,
                isSelected: false,
            };
        }

        return {
            isExpanded: entry.querySelector('[data-bp-visit-row]')?.getAttribute('aria-expanded') === 'true',
            isSelected: entry.querySelector('[data-bp-select-item]')?.checked ?? false,
        };
    };

    const restoreVisitEntryState = (entry, state) => {
        if (!entry || !state) {
            return;
        }

        const checkbox = entry.querySelector('[data-bp-select-item]');
        const row = entry.querySelector('[data-bp-visit-row]');

        if (checkbox) {
            checkbox.checked = Boolean(state.isSelected);
        }

        if (row) {
            toggleVisitRow(row, Boolean(state.isExpanded));
        }
    };

    const createFragmentFromHtml = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();

        return template.content.firstElementChild;
    };

    const replaceVisitEntry = (currentEntry, nextEntryHtml) => {
        const nextEntry = createFragmentFromHtml(nextEntryHtml);

        if (!currentEntry || !nextEntry || !nextEntry.matches('[data-bp-visit-entry]')) {
            return false;
        }

        const state = extractVisitEntryState(currentEntry);
        currentEntry.replaceWith(nextEntry);
        restoreVisitEntryState(nextEntry, state);
        syncSelectAllState();
        syncActionButtonWidth();

        return true;
    };

    const captureListState = () => {
        const tableBody = document.querySelector('.bp-av-table-body');
        const entryStates = new Map();

        document.querySelectorAll('[data-bp-visit-entry]').forEach((entry) => {
            const visitId = entry.getAttribute('data-bp-visit-id');

            if (visitId) {
                entryStates.set(visitId, extractVisitEntryState(entry));
            }
        });

        return {
            entryStates,
            listScrollTop: tableBody ? tableBody.scrollTop : 0,
        };
    };

    const restoreListState = (state) => {
        if (!state) {
            syncSelectAllState();
            syncActionButtonWidth();
            return;
        }

        document.querySelectorAll('[data-bp-visit-entry]').forEach((entry) => {
            const visitId = entry.getAttribute('data-bp-visit-id');
            const entryState = visitId ? state.entryStates.get(visitId) : null;

            if (entryState) {
                restoreVisitEntryState(entry, entryState);
            }
        });

        const tableBody = document.querySelector('.bp-av-table-body');

        if (tableBody) {
            tableBody.scrollTop = state.listScrollTop;
        }

        syncSelectAllState();
        syncActionButtonWidth();
    };

    const replaceFragment = (fragmentName, nextHtml) => {
        const current = document.querySelector(`[data-bp-fragment="${fragmentName}"]`);
        const next = createFragmentFromHtml(nextHtml);

        if (!current || !next || next.getAttribute('data-bp-fragment') !== fragmentName) {
            return false;
        }

        current.replaceWith(next);

        return true;
    };

    const loadListFragments = async (targetUrl) => {
        const listState = captureListState();

        const response = await fetch(targetUrl, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-BP-Context': 'reception-all-visits-list',
                'Accept': 'application/json',
            },
        });

        if (response.redirected && response.url) {
            window.location.href = response.url;
            return;
        }

        if (!response.ok) {
            throw new Error('Request failed');
        }

        const data = await response.json();

        if (!data.toolbar_html || !data.filter_bar_html || !data.visit_stack_html) {
            throw new Error('Missing fragment html');
        }

        const toolbarReplaced = replaceFragment('toolbar', data.toolbar_html);
        const filterBarReplaced = replaceFragment('filter-bar', data.filter_bar_html);
        const visitStackReplaced = replaceFragment('visit-stack', data.visit_stack_html);

        if (!toolbarReplaced || !filterBarReplaced || !visitStackReplaced) {
            throw new Error('Fragment replacement failed');
        }

        window.history.replaceState({}, '', targetUrl);
        restoreListState(listState);
    };

    syncActionButtonWidth();
    syncSelectAllState();

    document.addEventListener('click', (event) => {
        const row = event.target.closest('[data-bp-visit-row]');

        if (!row || event.target.closest('[data-bp-select-item], [data-bp-select-all], .bp-av-entry-details, a, button, input, select, textarea, label')) {
            return;
        }

        const existingTimer = rowToggleTimers.get(row);

        if (existingTimer) {
            window.clearTimeout(existingTimer);
        }

        const timerId = window.setTimeout(() => {
            toggleVisitRow(row);
            rowToggleTimers.delete(row);
        }, 220);

        rowToggleTimers.set(row, timerId);
    });

    document.addEventListener('click', async (event) => {
        const ajaxLink = event.target.closest('.bp-av-filter-bar a, .bp-av-date-form a');

        if (!ajaxLink) {
            return;
        }

        event.preventDefault();

        const targetUrl = ajaxLink.href;

        try {
            await loadListFragments(targetUrl);
        } catch (error) {
            window.location.href = targetUrl;
        }
    });

    document.addEventListener('dblclick', (event) => {
        const row = event.target.closest('[data-bp-visit-row]');

        if (!row || event.target.closest('[data-bp-select-item], [data-bp-select-all], .bp-av-entry-details, a, button, input, select, textarea, label')) {
            return;
        }

        const existingTimer = rowToggleTimers.get(row);

        if (existingTimer) {
            window.clearTimeout(existingTimer);
            rowToggleTimers.delete(row);
        }

        const openUrl = row.getAttribute('data-bp-open-url');

        if (openUrl) {
            window.location.href = openUrl;
        }
    });

    document.addEventListener('change', (event) => {
        const selectAll = event.target.closest('[data-bp-select-all]');

        if (selectAll) {
            document.querySelectorAll('[data-bp-select-item]').forEach((checkbox) => {
                checkbox.checked = selectAll.checked;
            });
            return;
        }

        if (event.target.matches('[data-bp-select-item]')) {
            syncSelectAllState();
        }
    });

    document.addEventListener('submit', async (event) => {
        const dateForm = event.target.closest('.bp-av-date-form');

        if (dateForm) {
            event.preventDefault();

            const fromInput = dateForm.querySelector('input[name="from"]');
            const toInput = dateForm.querySelector('input[name="to"]');
            const fromValue = fromInput ? fromInput.value.trim() : '';
            const toValue = toInput ? toInput.value.trim() : '';

            const targetUrl = fromValue === '' && toValue === ''
                ? (dateForm.dataset.bpResetUrl || dateForm.action)
                : `${dateForm.action}?${new URLSearchParams(new FormData(dateForm)).toString()}`;

            try {
                await loadListFragments(targetUrl);
            } catch (error) {
                window.location.href = targetUrl;
            }

            return;
        }

        const searchForm = event.target.closest('.bp-av-search-form');

        if (searchForm) {
            event.preventDefault();

            const targetUrl = `${searchForm.action}?${new URLSearchParams(new FormData(searchForm)).toString()}`;

            try {
                await loadListFragments(targetUrl);
            } catch (error) {
                window.location.href = targetUrl;
            }

            return;
        }

        const form = event.target.closest('form[data-bp-ajax="participant-action"]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const visitEntry = form.closest('[data-bp-visit-entry]');

        if (!visitEntry) {
            form.submit();
            return;
        }

        if (submitButton) {
            submitButton.disabled = true;
        }

        try {
            const response = await fetch(form.action, {
                method: 'POST',
                body: new FormData(form),
                credentials: 'same-origin',
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-BP-Context': 'reception-all-visits',
                    'Accept': 'application/json',
                },
            });

            if (response.redirected && response.url && !response.url.includes('/reception')) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                throw new Error('Request failed');
            }

            const data = await response.json();

            if (!data.entry_html) {
                throw new Error('Missing entry html');
            }

            const replaced = replaceVisitEntry(visitEntry, data.entry_html);

            if (!replaced) {
                throw new Error('Entry replacement failed');
            }
        } catch (error) {
            form.submit();
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }, true);
});
