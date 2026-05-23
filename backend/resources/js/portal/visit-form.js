export const visitForm = (config) => ({
    beginDate: config.beginDate ?? '',
    beginTime: config.beginTime ?? '',
    endDate: config.endDate ?? '',
    endTime: config.endTime ?? '',
    endDateTouched: Boolean(config.endDateTouched),
    participants: config.participants ?? [],
    existingVisitors: config.existingVisitors ?? [],
    hostUserOptions: config.hostUserOptions ?? config.userOptions ?? [],
    substituteUserOptions: config.substituteUserOptions ?? config.userOptions ?? [],
    siteOptions: config.siteOptions ?? [],
    siteId: config.siteId ?? '',
    hostId: config.hostId ?? '',
    substituteUserId: config.substituteUserId ?? '',
    userDropdownOpen: null,
    userSearch: {
        host: '',
        substitute: '',
    },
    recurrenceEnabled: Boolean(config.recurrenceEnabled),
    recurrenceFrequency: config.recurrenceFrequency ?? '',
    recurrenceEndType: config.recurrenceEndType ?? '',
    visitorContactRequirement: config.visitorContactRequirement ?? 'optional',
    addingParticipant: false,
    addingNewParticipant: false,
    search: '',
    emptyParticipantNameLabel: config.emptyParticipantNameLabel ?? '',
    draftParticipant: {
        visitor_id: null,
        title: '',
        first_name: '',
        name: '',
        email: '',
        phone: '',
        company: '',
        is_existing: false,
        is_open: false,
    },

    init() {
        this.siteId = String(this.siteId || this.siteOptions[0]?.id || '');
        this.ensureSelectedUsersForSite();
    },

    composeDateTime(dateValue, timeValue) {
        if (!dateValue || !timeValue) {
            return '';
        }

        return `${dateValue}T${timeValue}`;
    },

    userLabel(field) {
        const selectedId = field === 'host' ? this.hostId : this.substituteUserId;
        const user = this.userOptionsForField(field)
            .filter((option) => this.userCanAccessSelectedSite(option))
            .find((option) => String(option.id) === String(selectedId));

        return user?.label ?? '';
    },

    userOptionsForField(field) {
        return field === 'host' ? this.hostUserOptions : this.substituteUserOptions;
    },

    openUserDropdown(field) {
        this.userDropdownOpen = field;
        this.userSearch[field] = '';
        this.$nextTick(() => this.$refs[`${field}UserSearch`]?.focus());
    },

    toggleUserDropdown(field) {
        if (this.userDropdownOpen === field) {
            this.userDropdownOpen = null;

            return;
        }

        this.openUserDropdown(field);
    },

    selectUser(field, user) {
        if (field === 'host') {
            this.hostId = String(user.id);

            if (String(this.substituteUserId) === String(user.id)) {
                this.substituteUserId = '';
            }
        } else {
            this.substituteUserId = String(user.id);
        }

        this.userSearch[field] = '';
        this.userDropdownOpen = null;
    },

    selectSite(siteId) {
        this.siteId = String(siteId || '');
        this.ensureSelectedUsersForSite();
    },

    userCanAccessSelectedSite(user) {
        const siteIds = (user.site_ids ?? []).map((siteId) => String(siteId));

        return this.siteId === '' || siteIds.includes(String(this.siteId));
    },

    ensureSelectedUsersForSite() {
        const host = this.hostUserOptions.find((user) => String(user.id) === String(this.hostId));

        if (!host || !this.userCanAccessSelectedSite(host)) {
            const firstHost = this.hostUserOptions.find((user) => this.userCanAccessSelectedSite(user));
            this.hostId = firstHost ? String(firstHost.id) : '';
        }

        const substituteUser = this.substituteUserOptions.find((user) => String(user.id) === String(this.substituteUserId));

        if (!substituteUser || !this.userCanAccessSelectedSite(substituteUser) || String(this.substituteUserId) === String(this.hostId)) {
            this.substituteUserId = '';
        }
    },

    clearUserSearch(field) {
        this.userSearch[field] = '';
        this.$nextTick(() => this.$refs[`${field}UserSearch`]?.focus());
    },

    clearSubstituteUser() {
        this.substituteUserId = '';
        this.userSearch.substitute = '';
        this.userDropdownOpen = null;
    },

    filteredUsers(field) {
        const term = (this.userSearch[field] ?? '').trim().toLowerCase();
        const excludedId = field === 'host' ? this.substituteUserId : this.hostId;

        return this.userOptionsForField(field)
            .filter((user) => this.userCanAccessSelectedSite(user))
            .filter((user) => String(user.id) !== String(excludedId))
            .filter((user) => term === '' || String(user.search).toLowerCase().includes(term))
            .slice(0, 20);
    },

    selectFirstFilteredUser(field) {
        const firstUser = this.filteredUsers(field)[0];

        if (firstUser) {
            this.selectUser(field, firstUser);
        }
    },

    openAddParticipant() {
        this.addingParticipant = true;
        this.addingNewParticipant = false;
        this.search = '';
        this.resetDraftParticipant();
        this.$nextTick(() => this.$refs.participantSearch?.focus());
    },

    cancelAddParticipant() {
        this.addingParticipant = false;
        this.addingNewParticipant = false;
        this.search = '';
        this.resetDraftParticipant();
    },

    resetDraftParticipant() {
        this.draftParticipant = {
            visitor_id: null,
            title: '',
            first_name: '',
            name: '',
            email: '',
            phone: '',
            company: '',
            is_existing: false,
            is_open: false,
        };
    },

    searchResults() {
        const term = this.search.trim().toLowerCase();

        if (term.length === 0) {
            return [];
        }

        return this.existingVisitors
            .filter((visitor) => {
                const haystack = [
                    visitor.full_name,
                    visitor.title,
                    visitor.company,
                ]
                    .filter(Boolean)
                    .join(' ')
                    .toLowerCase();

                return haystack.includes(term);
            })
            .slice(0, 8);
    },

    hasParticipant(visitorId) {
        return this.participants.some((participant) => String(participant.visitor_id ?? '') === String(visitorId));
    },

    selectExistingVisitor(visitor) {
        if (this.hasParticipant(visitor.id)) {
            return;
        }

        this.participants.push({
            visitor_id: visitor.id,
            title: visitor.title ?? '',
            first_name: visitor.first_name ?? '',
            name: visitor.name ?? '',
            email: '',
            phone: '',
            company: visitor.company ?? '',
            is_existing: true,
            is_open: false,
        });

        this.cancelAddParticipant();
    },

    startNewParticipantFromSearch() {
        const term = this.search.trim();
        const parts = term.split(/\s+/).filter(Boolean);

        this.addingNewParticipant = true;
        this.draftParticipant = {
            visitor_id: null,
            title: '',
            first_name: parts[0] ?? '',
            name: parts.length > 1 ? parts.slice(1).join(' ') : '',
            email: '',
            phone: '',
            company: '',
            is_existing: false,
            is_open: true,
        };

        this.$nextTick(() => this.$refs.firstNameInput?.focus());
    },

    addDraftParticipant() {
        if (!this.isDraftParticipantComplete()) {
            return;
        }

        this.participants.push({
            visitor_id: null,
            title: (this.draftParticipant.title ?? '').trim(),
            first_name: (this.draftParticipant.first_name ?? '').trim(),
            name: (this.draftParticipant.name ?? '').trim(),
            email: (this.draftParticipant.email ?? '').trim(),
            phone: (this.draftParticipant.phone ?? '').trim(),
            company: (this.draftParticipant.company ?? '').trim(),
            is_existing: false,
            is_open: false,
        });

        this.cancelAddParticipant();
    },

    draftParticipantEmailIsValid() {
        const email = (this.draftParticipant.email ?? '').trim();

        if (email === '') {
            return true;
        }

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    draftParticipantContactIsComplete() {
        const email = (this.draftParticipant.email ?? '').trim();
        const phone = (this.draftParticipant.phone ?? '').trim();

        // Contact requirements are privacy-configurable and optional by default.
        switch (this.visitorContactRequirement) {
            case 'require_email':
                return email !== '';
            case 'require_phone':
                return phone !== '';
            case 'require_one':
                return email !== '' || phone !== '';
            default:
                return true;
        }
    },

    isDraftParticipantComplete() {
        return Boolean(
            (this.draftParticipant.first_name ?? '').trim()
            && (this.draftParticipant.name ?? '').trim()
            && this.draftParticipantEmailIsValid()
            && this.draftParticipantContactIsComplete()
        );
    },

    removeParticipant(index) {
        this.participants.splice(index, 1);
    },

    participantName(participant) {
        const title = (participant.title ?? '').trim();
        const firstName = (participant.first_name ?? '').trim();
        const lastName = (participant.name ?? '').trim();
        const fullName = `${firstName} ${lastName}`.trim();

        if (fullName !== '' && title !== '') {
            return `${fullName}, ${title}`;
        }

        return fullName || title || this.emptyParticipantNameLabel;
    },

    participantHasDetails(participant) {
        return Boolean(participant.company || participant.email || participant.phone);
    },

    toggleParticipant(index) {
        this.participants[index].is_open = !this.participants[index].is_open;
    },

    participantHiddenValue(value) {
        return value ?? '';
    },

    syncEndDateFromBegin() {
        if (!this.endDateTouched || !this.endDate) {
            this.endDate = this.beginDate;
        }
    },
});
