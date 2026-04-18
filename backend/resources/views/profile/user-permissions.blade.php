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
            <h2 class="text-xl font-semibold tracking-tight text-base-content">
                {{ $data['name'] }}
            </h2>

            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($data['roles'] as $role)
                    <span class="inline-flex items-center rounded-full border border-primary/15 bg-primary/10 px-2.5 py-1 text-xs font-semibold text-primary">
                        {{ $role }}
                    </span>
                @endforeach
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($data['permissions'] as $resource => $permissions)
                <article class="bp-panel p-5">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-[0.18em] text-base-content/50">
                        {{ $resource }}
                    </h3>

                    <ul class="space-y-2">
                        @foreach ($permissions as $permission)
                            <li class="flex items-center gap-2 text-sm text-base-content/80">
                                <span class="text-success">✓</span>
                                <span>{{ $permission }}</span>
                            </li>
                        @endforeach
                    </ul>
                </article>
            @empty
                <section class="bp-panel p-5">
                    <p class="text-sm text-base-content/65">{{ __('No permissions assigned.') }}</p>
                </section>
            @endforelse
        </section>
    </div>
</x-app-layout>
