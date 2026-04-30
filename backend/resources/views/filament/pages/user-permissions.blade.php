{{-- resources/views/filament/pages/user-permissions.blade.php --}}
<x-filament-panels::page>
    @php $data = $this->getUserData() @endphp

    {{-- User name and roles --}}
    <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6 mb-6">
        <h2 class="text-base font-semibold text-gray-950 dark:text-white">
            {{ $data['name'] }}
        </h2>
        <div class="flex flex-wrap gap-2 mt-3">
            @foreach ($data['roles'] as $role)
                <span class="inline-flex items-center rounded-md px-2 py-1 text-xs font-medium ring-1 ring-inset
                             bg-primary-50 text-primary-700 ring-primary-600/20
                             dark:bg-primary-400/10 dark:text-primary-400 dark:ring-primary-400/20">
                    {{ $role }}
                </span>
            @endforeach
        </div>
    </div>

    {{-- Permissions grouped by resource --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($data['permissions'] as $resource => $permissions)
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <h3 class="text-xs font-medium uppercase tracking-widest text-gray-500 dark:text-gray-400 mb-4">
                    {{ $resource }}
                </h3>
                <ul class="space-y-2">
                    @foreach ($permissions as $permission)
						<li class="flex items-center gap-2 text-sm text-gray-700 dark:text-gray-200">
                                ✓ {{ $permission }}
                    	</li>
                    @endforeach
                </ul>
            </div>
        @empty
            <div class="fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10 p-6">
                <p class="text-sm text-gray-500 dark:text-gray-400">{{ __('Keine Berechtigungen zugewiesen.') }}</p>
            </div>
        @endforelse
    </div>
</x-filament-panels::page>
