export const visitForm = (config) => ({
    beginDate: config.beginDate ?? '',
    beginTime: config.beginTime ?? '',
    endDate: config.endDate ?? '',
    endTime: config.endTime ?? '',
    endDateTouched: Boolean(config.endDateTouched),
    participants: config.participants ?? [],
    existingVisitors: config.existingVisitors ?? [],
    userOptions: config.userOptions ?? [],
    hostId: config.hostId ?? '',
    responsibleUserId: config.responsibleUserId ?? '',
    userDropdownOpen: null,
    userSearch: {
        host: '',
        responsible: '',
    },
    recurrenceEnabled: Boolean(config.recurrenceEnabled),
    recurrenceFrequency: config.recurrenceFrequency ?? '',
    recurrenceEndType: config.recurrenceEndType ?? '',
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

    composeDateTime(dateValue, timeValue) {
        if (!dateValue || !timeValue) {
            return '';
        }

        return `${dateValue}T${timeValue}`;
    },

    userLabel(field) {
        const selectedId = field === 'host' ? this.hostId : this.responsibleUserId;
        const user = this.userOptions.find((option) => String(option.id) === String(selectedId));

        return user?.label ?? '';
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

            if (String(this.responsibleUserId) === String(user.id)) {
                this.responsibleUserId = '';
            }
        } else {
            this.responsibleUserId = String(user.id);
        }

        this.userSearch[field] = '';
        this.userDropdownOpen = null;
    },

    clearUserSearch(field) {
        this.userSearch[field] = '';
        this.$nextTick(() => this.$refs[`${field}UserSearch`]?.focus());
    },

    clearResponsibleUser() {
        this.responsibleUserId = '';
        this.userSearch.responsible = '';
        this.userDropdownOpen = null;
    },

    filteredUsers(field) {
        const term = (this.userSearch[field] ?? '').trim().toLowerCase();
        const excludedId = field === 'host' ? this.responsibleUserId : this.hostId;

        return this.userOptions
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
                    visitor.email,
                    visitor.phone,
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
            email: visitor.email ?? '',
            phone: visitor.phone ?? '',
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

        return /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email);
    },

    isDraftParticipantComplete() {
        return Boolean(
            (this.draftParticipant.first_name ?? '').trim()
            && (this.draftParticipant.name ?? '').trim()
            && (this.draftParticipant.company ?? '').trim()
            && this.draftParticipantEmailIsValid()
            && (this.draftParticipant.phone ?? '').trim()
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
