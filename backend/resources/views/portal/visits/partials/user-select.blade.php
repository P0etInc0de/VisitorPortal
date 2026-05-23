@php
    $hasError = $errors->has($name);
@endphp

<div class="form-control {{ $gridClass }}">
    <div class="label px-0 pb-2">
        <span class="label-text text-sm font-medium text-base-content">
            {{ $label }} @if ($required ?? false) {{ $requiredMarker }} @endif
        </span>
    </div>

    <input type="hidden" name="{{ $name }}" value="{{ $initialValue }}" :value="{{ $selectedModel }}">

    <div class="relative" @click.outside="userDropdownOpen === '{{ $field }}' && (userDropdownOpen = null)">
        <button
            type="button"
            class="input input-bordered flex w-full items-center justify-between gap-3 text-left font-normal {{ $hasError ? 'input-error' : '' }}"
            :aria-expanded="userDropdownOpen === '{{ $field }}'"
            @click="toggleUserDropdown('{{ $field }}')"
            @keydown.escape="userDropdownOpen = null"
        >
            <span class="truncate" x-text="userLabel('{{ $field }}') || {{ \Illuminate\Support\Js::from($placeholder) }}"></span>
            <svg class="h-4 w-4 shrink-0 text-base-content/60" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 0 1 1.06.02L10 11.17l3.71-3.94a.75.75 0 1 1 1.08 1.04l-4.25 4.5a.75.75 0 0 1-1.08 0l-4.25-4.5a.75.75 0 0 1 .02-1.06Z" clip-rule="evenodd" />
            </svg>
        </button>

        <div
            class="absolute z-40 mt-2 w-full rounded-2xl border border-base-300 bg-base-100 p-2 shadow-xl"
            x-cloak
            x-show="userDropdownOpen === '{{ $field }}'"
        >
            <div class="relative">
                <input
                    type="text"
                    class="input input-bordered w-full pr-10"
                    x-ref="{{ $field }}UserSearch"
                    x-model="userSearch.{{ $field }}"
                    placeholder="{{ __('Namen suchen') }}"
                    @keydown.enter.prevent="selectFirstFilteredUser('{{ $field }}')"
                    @keydown.escape.prevent="userDropdownOpen = null"
                >
                <button
                    type="button"
                    class="absolute inset-y-0 right-3 flex items-center text-base-content/50 hover:text-base-content"
                    x-cloak
                    x-show="userSearch.{{ $field }}.length > 0"
                    @click="clearUserSearch('{{ $field }}')"
                    aria-label="{{ __('Suche leeren') }}"
                >
                    &times;
                </button>
            </div>

            <div class="mt-2 max-h-60 overflow-auto">
                @if ($allowEmpty ?? false)
                    <button
                        type="button"
                        class="w-full rounded-xl px-3 py-2 text-left text-sm text-base-content/70 hover:bg-base-200"
                        @click="clearSubstituteUser()"
                    >
                        -
                    </button>
                @endif

                <template x-for="user in filteredUsers('{{ $field }}')" :key="user.id">
                    <button
                        type="button"
                        class="w-full rounded-xl px-3 py-2 text-left text-sm hover:bg-base-200"
                        :class="String({{ $selectedModel }}) === String(user.id) ? 'bg-base-200 font-semibold' : ''"
                        @click="selectUser('{{ $field }}', user)"
                        x-text="user.label"
                    ></button>
                </template>

                <div class="px-3 py-2 text-sm text-base-content/60" x-show="filteredUsers('{{ $field }}').length === 0">
                    {{ __('Keine Treffer') }}
                </div>
            </div>
        </div>
    </div>

    @if ($hasError)
        <div class="mt-2 text-sm text-error">{{ $errors->first($name) }}</div>
    @endif
</div>
