<x-app-layout>
    <x-slot name="header">
        <div class="bp-page-header">
            <div>
                <h1 class="bp-page-title">{{ __('Meine Berechtigungen') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="bp-profile-stack">
        <section class="bp-panel p-6 sm:p-7">
            <h2 class="text-2xl font-bold tracking-tight text-base-content">
                {{ $data['name'] }}
            </h2>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($data['roles'] as $role)
                    <span class="bp-role-pill">{{ $role }}</span>
                @endforeach
            </div>
        </section>

        <section>
            <div class="grid grid-cols-1 gap-4 md:grid-cols-2 xl:grid-cols-3">
                @forelse ($data['permissions'] as $resource => $permissions)
                    <div class="bp-permission-card">
                        <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-base-content/45">
                            {{ $resource }}
                        </h3>

                        <ul class="space-y-2">
                            @foreach ($permissions as $permission)
                                <li class="flex items-center gap-2 text-sm text-base-content/80">
                                    <span class="text-primary">✓</span>
                                    <span>{{ $permission }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @empty
                    <div class="bp-permission-card md:col-span-2 xl:col-span-3">
                        <p class="bp-text-muted">{{ __('No permissions assigned.') }}</p>
                    </div>
                @endforelse
            </div>
        </section>
    </div>
</x-app-layout>
