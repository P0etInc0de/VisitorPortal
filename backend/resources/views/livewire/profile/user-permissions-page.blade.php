<div class="grid gap-4">
    <section class="rounded-3xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
        <div class="flex flex-col gap-4">
            <div class="flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
                <div>
                    <p class="mt-2 max-w-3xl text-sm text-base-content/70">
                        {{ __('Hier siehst du kompakt, welche Rollen du hast und was du im Portal konkret machen darfst.') }}
                    </p>
                    <p class="mt-2 max-w-3xl text-sm text-base-content/60">
                        {{ __('Hinweis: Der Zugriff auf den Admin-Bereich wird rollenbasiert vergeben, zum Beispiel über die Rolle admin.') }}
                    </p>

                    <div class="mt-3 flex flex-wrap gap-2">
                        @forelse ($summary['roles'] as $role)
                            <span class="badge badge-primary badge-outline rounded-full px-3 py-2 text-sm font-semibold">{{ $role }}</span>
                        @empty
                            <span class="text-sm text-base-content/65">{{ __('Es ist aktuell keine Rolle zugewiesen.') }}</span>
                        @endforelse
                    </div>
                </div>

                <div class="flex flex-wrap gap-2 xl:justify-end">
                    <span class="badge badge-ghost rounded-full px-3 py-2 text-sm font-medium">{{ $summary['role_count'] }} {{ __('Rollen') }}</span>
                    <span class="badge badge-ghost rounded-full px-3 py-2 text-sm font-medium">{{ $summary['resource_count'] }} {{ __('Bereiche') }}</span>
                    <span class="badge badge-ghost rounded-full px-3 py-2 text-sm font-medium">{{ $summary['permission_count'] }} {{ __('Erlaubte Aktionen') }}</span>
                </div>
            </div>

            <div class="border-t border-base-300 pt-4">
                <div class="flex flex-col gap-3 lg:flex-row lg:items-center lg:justify-between">
                    <div>
                        <h2 class="text-lg font-semibold tracking-tight text-base-content">{{ __('Suche') }}</h2>
                        <p class="mt-1 text-sm text-base-content/70">{{ __('Suche nach Bereichen oder Aktionen wie Besuche, Benutzer, Anlegen oder Bearbeiten.') }}</p>
                    </div>

                    <label class="form-control w-full lg:max-w-sm">
                        <span class="sr-only">{{ __('Berechtigungen suchen') }}</span>
                        <input
                            type="text"
                            wire:model.live.debounce.250ms="search"
                            class="input input-bordered w-full"
                            placeholder="{{ __('Bereich oder Aktion suchen') }}"
                        >
                    </label>
                </div>
            </div>
        </div>
    </section>

    @if ($permissionGroups->isEmpty())
        <section class="rounded-3xl border border-dashed border-base-300 bg-base-100 p-8 text-center shadow-sm">
            <div class="text-base font-medium text-base-content">{{ __('Keine passenden Berechtigungen gefunden.') }}</div>
            <p class="mt-2 text-sm text-base-content/65">{{ __('Passe den Suchbegriff an, um andere Bereiche oder Aktionen zu sehen.') }}</p>
        </section>
    @else
        <section class="grid grid-cols-1 gap-4 2xl:grid-cols-2">
            @foreach ($permissionGroups as $group)
                @php($shouldOpen = trim($search) !== '')

                <details class="group rounded-3xl border border-base-300 bg-base-100 shadow-sm" @if ($shouldOpen) open @endif>
                    <summary class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3 sm:px-5 sm:py-4">
                        <div class="min-w-0">
                            <h3 class="text-lg font-semibold tracking-tight text-base-content">{{ $group['resource_label'] }}</h3>
                            <p class="mt-0.5 text-sm text-base-content/65">{{ $group['count'] }} {{ __('Erlaubte Aktionen') }}</p>
                        </div>

                        <div class="flex items-center gap-3">
                            <span class="hidden text-xs font-medium text-base-content/45 sm:inline">{{ __('Details') }}</span>
                            <svg class="h-4 w-4 shrink-0 text-base-content/45 transition-transform duration-200 group-open:rotate-180" viewBox="0 0 20 20" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path d="M5 7.5L10 12.5L15 7.5" stroke-linecap="round" stroke-linejoin="round" />
                            </svg>
                        </div>
                    </summary>

                    <div class="border-t border-base-300 px-4 py-3 sm:px-5 sm:py-4">
                        <div class="space-y-2">
                            @foreach ($group['items'] as $item)
                                <div class="flex gap-3 rounded-2xl border border-base-300 bg-base-100/60 px-3 py-2.5">
                                    <div class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-full bg-primary/10 text-xs font-bold text-primary">✓</div>

                                    <div class="min-w-0">
                                        <div class="text-sm font-semibold leading-5 text-base-content">{{ $item['action_label'] }}</div>
                                        <p class="mt-0.5 text-sm leading-5 text-base-content/72">{{ $item['description'] }}</p>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </details>
            @endforeach
        </section>
    @endif
</div>
