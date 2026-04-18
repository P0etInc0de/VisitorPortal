document.addEventListener('DOMContentLoaded', () => {
    const page = document.querySelector('.bp-av-page');

    if (!page || page.dataset.bpAjaxReady === '1') {
        return;
    }

    page.dataset.bpAjaxReady = '1';

    const createLogger = () => {
        let debugEnabled = false;

        try {
            debugEnabled = window.localStorage?.getItem('bp:all-visits:debug') === '1';
        } catch (error) {
            debugEnabled = false;
        }

        const prefix = '[AllVisits]';

        const write = (level, message, context = {}) => {
            if ((level === 'debug' || level === 'info') && !debugEnabled) {
                return;
            }

            const method = level === 'debug'
                ? 'debug'
                : level === 'info'
                    ? 'info'
                    : level === 'warn'
                        ? 'warn'
                        : 'error';

            console[method](`${prefix} ${message}`, context);
        };

        return {
            debug: (message, context = {}) => write('debug', message, context),
            info: (message, context = {}) => write('info', message, context),
            warn: (message, context = {}) => write('warn', message, context),
            error: (message, context = {}) => write('error', message, context),
        };
    };

    const logger = createLogger();
    const rowToggleTimers = new WeakMap();

    const getVisitIdFromElement = (element) => {
        if (!element) {
            return null;
        }

        return element.getAttribute('data-bp-visit-id')
            || element.closest('[data-bp-visit-entry]')?.getAttribute('data-bp-visit-id')
            || null;
    };

    const findVisitEntryById = (visitId) => {
        if (!visitId) {
            return null;
        }

        return document.querySelector(`[data-bp-visit-entry][data-bp-visit-id="${String(visitId)}"]`);
    };

    const toggleVisitRow = (row, forceExpanded = null) => {
        if (!row) {
            return false;
        }

        const targetId = row.getAttribute('data-bp-toggle-details');
        const target = targetId ? document.getElementById(targetId) : null;

        if (!target) {
            return false;
        }

        const isExpanded = row.getAttribute('aria-expanded') === 'true';
        const nextExpanded = forceExpanded === null ? !isExpanded : forceExpanded;

        row.setAttribute('aria-expanded', nextExpanded ? 'true' : 'false');
        row.closest('.bp-av-entry')?.classList.toggle('bp-av-entry-expanded', nextExpanded);
        target.hidden = !nextExpanded;

        return nextExpanded;
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

    const createFragmentFromHtml = (html) => {
        const template = document.createElement('template');
        template.innerHTML = html.trim();

        return template.content.firstElementChild;
    };

    const createStateStore = () => {
        const state = {
            expandedVisitIds: new Set(),
            selectedVisitIds: new Set(),
            listScrollTop: 0,
            listScrollLeft: 0,
        };

        const cloneSnapshot = () => ({
            expandedVisitIds: new Set(state.expandedVisitIds),
            selectedVisitIds: new Set(state.selectedVisitIds),
            listScrollTop: state.listScrollTop,
            listScrollLeft: state.listScrollLeft,
        });

        const rememberScrollPositions = () => {
            const tableBody = document.querySelector('.bp-av-table-body');
            const tableWrap = document.querySelector('.bp-av-table-wrap');

            state.listScrollTop = tableBody ? tableBody.scrollTop : 0;
            state.listScrollLeft = tableWrap ? tableWrap.scrollLeft : 0;
        };

        return {
            syncFromDom() {
                state.expandedVisitIds.clear();
                state.selectedVisitIds.clear();

                document.querySelectorAll('[data-bp-visit-entry]').forEach((entry) => {
                    const visitId = entry.getAttribute('data-bp-visit-id');

                    if (!visitId) {
                        return;
                    }

                    const row = entry.querySelector('[data-bp-visit-row]');
                    const checkbox = entry.querySelector('[data-bp-select-item]');
                    const targetId = row?.getAttribute('data-bp-toggle-details');
                    const details = targetId ? document.getElementById(targetId) : null;
                    const isExpanded = row?.getAttribute('aria-expanded') === 'true' || Boolean(details && !details.hidden);

                    if (isExpanded) {
                        state.expandedVisitIds.add(String(visitId));
                    }

                    if (checkbox?.checked) {
                        state.selectedVisitIds.add(String(visitId));
                    }
                });

                rememberScrollPositions();
            },
            snapshot() {
                rememberScrollPositions();
                return cloneSnapshot();
            },
            captureDomSnapshot() {
                this.syncFromDom();
                return cloneSnapshot();
            },
            rememberExpandedState(visitId, isExpanded) {
                if (!visitId) {
                    return;
                }

                if (isExpanded) {
                    state.expandedVisitIds.add(String(visitId));
                } else {
                    state.expandedVisitIds.delete(String(visitId));
                }
            },
            rememberSelectionState(visitId, isSelected) {
                if (!visitId) {
                    return;
                }

                if (isSelected) {
                    state.selectedVisitIds.add(String(visitId));
                } else {
                    state.selectedVisitIds.delete(String(visitId));
                }
            },
            rememberCurrentScroll() {
                rememberScrollPositions();
            },
            restore(snapshot = null) {
                const source = snapshot ?? cloneSnapshot();

                document.querySelectorAll('[data-bp-visit-entry]').forEach((entry) => {
                    const visitId = entry.getAttribute('data-bp-visit-id');
                    const row = entry.querySelector('[data-bp-visit-row]');
                    const checkbox = entry.querySelector('[data-bp-select-item]');

                    if (checkbox) {
                        checkbox.checked = Boolean(visitId && source.selectedVisitIds.has(String(visitId)));
                    }

                    if (row) {
                        toggleVisitRow(row, Boolean(visitId && source.expandedVisitIds.has(String(visitId))));
                    }
                });

                const tableBody = document.querySelector('.bp-av-table-body');
                const tableWrap = document.querySelector('.bp-av-table-wrap');

                if (tableBody) {
                    tableBody.scrollTop = source.listScrollTop;
                }

                if (tableWrap) {
                    tableWrap.scrollLeft = source.listScrollLeft;
                }

                syncSelectAllState();
                syncActionButtonWidth();

                state.expandedVisitIds = new Set(source.expandedVisitIds);
                state.selectedVisitIds = new Set(source.selectedVisitIds);
                state.listScrollTop = source.listScrollTop;
                state.listScrollLeft = source.listScrollLeft;
            },
        };
    };

    const stateStore = createStateStore();

    const delay = (ms) => new Promise((resolve) => {
        window.setTimeout(resolve, ms);
    });

    const ensureBadgeDownloadFrame = () => {
        let frame = document.getElementById('bp-av-badge-download-frame');

        if (frame) {
            return frame;
        }

        frame = document.createElement('iframe');
        frame.id = 'bp-av-badge-download-frame';
        frame.name = 'bpAvBadgeDownloadFrame';
        frame.hidden = true;
        frame.setAttribute('aria-hidden', 'true');
        frame.tabIndex = -1;
        frame.src = 'about:blank';
        document.body.appendChild(frame);

        return frame;
    };

    const waitForFrameLoad = (frame, timeoutMs = 8000) => new Promise((resolve) => {
        let settled = false;

        const cleanup = () => {
            frame.removeEventListener('load', handleLoad);
            window.clearTimeout(timeoutId);
        };

        const finish = () => {
            if (settled) {
                return;
            }

            settled = true;
            cleanup();
            resolve();
        };

        const handleLoad = () => {
            finish();
        };

        const timeoutId = window.setTimeout(() => {
            finish();
        }, timeoutMs);

        frame.addEventListener('load', handleLoad, { once: true });
    });

    const submitFormToFrame = (form, frameName) => {
        const previousTarget = form.getAttribute('target');

        form.setAttribute('target', frameName);
        HTMLFormElement.prototype.submit.call(form);

        if (previousTarget === null) {
            form.removeAttribute('target');
        } else {
            form.setAttribute('target', previousTarget);
        }
    };

    const extractVisitEntryHtml = (visitStackHtml, visitId) => {
        const visitStack = createFragmentFromHtml(visitStackHtml);

        if (!visitStack) {
            return null;
        }

        const nextEntry = visitStack.querySelector(`[data-bp-visit-entry][data-bp-visit-id="${String(visitId)}"]`);

        return nextEntry ? nextEntry.outerHTML : null;
    };

    const applyStateSnapshot = (snapshot) => {
        stateStore.restore(snapshot);
    };

    const replaceVisitEntry = (currentEntry, nextEntryHtml, snapshot = null) => {
        const nextEntry = createFragmentFromHtml(nextEntryHtml);

        if (!currentEntry || !nextEntry || !nextEntry.matches('[data-bp-visit-entry]')) {
            return false;
        }

        const stateSnapshot = snapshot ?? stateStore.snapshot();

        currentEntry.replaceWith(nextEntry);
        applyStateSnapshot(stateSnapshot);

        return true;
    };

    const refreshVisitEntryFromCurrentList = async (visitId, snapshot = null) => {
        const currentEntry = findVisitEntryById(visitId);

        if (!currentEntry) {
            logger.warn('Refresh skipped because the current visit entry was not found.', { visitId });
            return false;
        }

        logger.debug('Refreshing visit entry from the current list view.', { visitId });

        const stateSnapshot = snapshot ?? stateStore.snapshot();
        const response = await fetch(window.location.href, {
            method: 'GET',
            credentials: 'same-origin',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-BP-Context': 'reception-all-visits-list',
                'Accept': 'application/json',
            },
        });

        logger.debug('Visit entry refresh response received.', {
            visitId,
            ok: response.ok,
            status: response.status,
        });

        if (!response.ok) {
            throw new Error(`Refresh request failed with status ${response.status}`);
        }

        const data = await response.json();
        const nextEntryHtml = data.visit_stack_html ? extractVisitEntryHtml(data.visit_stack_html, visitId) : null;

        logger.debug('Visit entry refresh payload parsed.', {
            visitId,
            hasVisitStackHtml: Boolean(data.visit_stack_html),
            hasEntryHtml: Boolean(nextEntryHtml),
        });

        if (!nextEntryHtml) {
            throw new Error('Missing refreshed entry html');
        }

        const replaced = replaceVisitEntry(currentEntry, nextEntryHtml, stateSnapshot);

        logger.debug('Visit entry refresh replacement finished.', {
            visitId,
            replaced,
        });

        return replaced;
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
        const stateSnapshot = stateStore.captureDomSnapshot();

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

        applyStateSnapshot(stateSnapshot);
    };

    syncActionButtonWidth();
    syncSelectAllState();
    stateStore.syncFromDom();

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
            const expanded = toggleVisitRow(row);
            const visitId = getVisitIdFromElement(row);

            stateStore.rememberExpandedState(visitId, expanded);
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
                stateStore.rememberSelectionState(getVisitIdFromElement(checkbox), checkbox.checked);
            });
            syncSelectAllState();
            return;
        }

        if (event.target.matches('[data-bp-select-item]')) {
            stateStore.rememberSelectionState(getVisitIdFromElement(event.target), event.target.checked);
            syncSelectAllState();
        }
    });

    document.addEventListener('scroll', (event) => {
        if (event.target.matches('.bp-av-table-body, .bp-av-table-wrap')) {
            stateStore.rememberCurrentScroll();
        }
    }, true);

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

        const badgeForm = event.target.closest('form[data-bp-badge-download]');

        if (badgeForm) {
            event.preventDefault();

            const visitId = getVisitIdFromElement(badgeForm);
            const submitButton = badgeForm.querySelector('button[type="submit"]');
            const stateSnapshot = stateStore.captureDomSnapshot();

            if (!visitId) {
                HTMLFormElement.prototype.submit.call(badgeForm);
                return;
            }

            if (submitButton) {
                submitButton.disabled = true;
            }

            try {
                const frame = ensureBadgeDownloadFrame();
                const loadPromise = waitForFrameLoad(frame);

                submitFormToFrame(badgeForm, frame.name);

                await Promise.race([loadPromise, delay(1200)]);

                try {
                    await refreshVisitEntryFromCurrentList(visitId, stateSnapshot);
                } catch (error) {
                    applyStateSnapshot(stateSnapshot);
                }
            } catch (error) {
                HTMLFormElement.prototype.submit.call(badgeForm);
                return;
            } finally {
                if (submitButton) {
                    submitButton.disabled = false;
                }
            }

            return;
        }

        const form = event.target.closest('form[data-bp-ajax="participant-action"]');

        if (!form) {
            return;
        }

        event.preventDefault();

        const submitButton = form.querySelector('button[type="submit"]');
        const visitId = getVisitIdFromElement(form);
        const initialVisitEntry = form.closest('[data-bp-visit-entry]') || findVisitEntryById(visitId);
        const stateSnapshot = stateStore.captureDomSnapshot();

        if (!visitId) {
            logger.error('Participant action aborted because the visit id is missing.', {
                action: form.action,
            });
            return;
        }

        logger.debug('Participant action started.', {
            visitId,
            action: form.action,
            hasInitialVisitEntry: Boolean(initialVisitEntry),
        });

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

            logger.debug('Participant action response received.', {
                visitId,
                action: form.action,
                ok: response.ok,
                status: response.status,
                redirected: response.redirected,
            });

            if (response.redirected && response.url && !response.url.includes('/reception')) {
                window.location.href = response.url;
                return;
            }

            if (!response.ok) {
                throw new Error(`Request failed with status ${response.status}`);
            }

            const data = await response.json();
            const hasEntryHtml = typeof data.entry_html === 'string' && data.entry_html.trim() !== '';

            logger.debug('Participant action payload parsed.', {
                visitId,
                hasEntryHtml,
            });

            if (hasEntryHtml) {
                const currentEntry = findVisitEntryById(visitId) || initialVisitEntry;

                logger.debug('Trying direct visit entry replacement after participant action.', {
                    visitId,
                    hasCurrentEntry: Boolean(currentEntry),
                });

                if (currentEntry) {
                    const replaced = replaceVisitEntry(currentEntry, data.entry_html, stateSnapshot);

                    logger.debug('Direct visit entry replacement finished.', {
                        visitId,
                        replaced,
                    });

                    if (replaced) {
                        return;
                    }
                }
            }

            logger.warn('Direct participant action update did not replace the visit entry. Trying list-based refresh.', {
                visitId,
                hasEntryHtml,
            });

            const refreshed = await refreshVisitEntryFromCurrentList(visitId, stateSnapshot);

            if (!refreshed) {
                logger.warn('Participant action finished, but the visit entry could not be refreshed. Keeping the current page state.', {
                    visitId,
                });
                applyStateSnapshot(stateSnapshot);
            }
        } catch (error) {
            logger.error('Participant action request failed. Trying list-based refresh instead of a full page reload.', {
                visitId,
                action: form.action,
                message: error instanceof Error ? error.message : String(error),
            });

            try {
                const refreshed = await refreshVisitEntryFromCurrentList(visitId, stateSnapshot);

                if (!refreshed) {
                    logger.warn('Participant action refresh fallback did not update the visit entry. Keeping the current page state.', {
                        visitId,
                    });
                    applyStateSnapshot(stateSnapshot);
                }
            } catch (refreshError) {
                logger.error('Participant action refresh fallback failed. Keeping the current page state without a full page reload.', {
                    visitId,
                    message: refreshError instanceof Error ? refreshError.message : String(refreshError),
                });
                applyStateSnapshot(stateSnapshot);
            }
        } finally {
            if (submitButton) {
                submitButton.disabled = false;
            }
        }
    }, true);
});
