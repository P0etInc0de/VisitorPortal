<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col items-start gap-4 lg:flex-row lg:justify-between">
            <div>
                <h1 class="text-4xl font-bold leading-none tracking-tight text-base-content lg:text-5xl">{{ __('Meine Berechtigungen') }}</h1>
            </div>
        </div>
    </x-slot>

    <div class="grid gap-5">
        <section class="rounded-3xl border border-base-300 bg-base-100 p-5 shadow-sm sm:p-6">
            <div class="flex flex-col gap-3">
                <h2 class="text-2xl font-bold tracking-tight text-base-content">{{ $data['name'] }}</h2>
                <div class="flex flex-wrap gap-2">
                    @foreach ($data['roles'] as $role)
                        <span class="badge badge-outline px-3 py-2 text-sm font-semibold">{{ $role }}</span>
                    @endforeach
                </div>
            </div>
        </section>

        <section class="grid grid-cols-1 gap-4 xl:grid-cols-3">
            @forelse ($data['permissions'] as $resource => $permissions)
                <div class="rounded-3xl border border-base-300 bg-base-100 p-4 shadow-sm sm:p-5">
                    <h3 class="mb-4 text-sm font-semibold uppercase tracking-[0.18em] text-base-content/55">{{ $resource }}</h3>
                    <ul class="space-y-2.5">
                        @foreach ($permissions as $permission)
                            <li class="flex items-center gap-2 text-sm text-base-content/80">
                                <span class="text-primary">✓</span>
                                <span>{{ $permission }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <div class="rounded-3xl border border-base-300 bg-base-100 p-4 text-sm text-base-content/65 shadow-sm">{{ __('Keine Berechtigungen zugewiesen.') }}</div>
            @endforelse
        </section>
    </div>
</x-app-layout>
