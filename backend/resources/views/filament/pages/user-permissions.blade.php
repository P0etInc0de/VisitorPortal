<?php

<x-filament-panels::page>

    @php $data = $this->getUserData() @endphp

    {{-- User header --}}
    <div class="flex items-center gap-4 mb-6">
        <div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white">
                {{ $data['name'] }}
            </h2>
            <div class="flex gap-2 mt-1">
                @foreach ($data['roles'] as $role)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                 bg-primary-100 text-primary-700
                                 dark:bg-primary-900 dark:text-primary-300">
                        {{ $role }}
                    </span>
                @endforeach
            </div>
        </div>
    </div>

    {{-- Permissions grouped by resource --}}
    <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
        @forelse ($data['permissions'] as $resource => $permissions)
            <div class="rounded-xl border border-gray-200 dark:border-gray-700
                        bg-white dark:bg-gray-900 p-4 shadow-sm">

                <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide
                            text-gray-500 dark:text-gray-400">
                    {{ $resource }}
                </h3>

                <ul class="space-y-2">
                    @foreach ($permissions as $permission)
                        <li class="flex items-center gap-2 text-sm
                                   text-gray-700 dark:text-gray-300">
                            <x-heroicon-o-check-circle class="w-4 h-4 text-success-500" />
                            {{ $permission }}
                        </li>
                    @endforeach
                </ul>

            </div>
        @empty
            <p class="text-gray-500 dark:text-gray-400">
                No permissions assigned yet.
            </p>
        @endforelse
    </div>

</x-filament-panels::page>
