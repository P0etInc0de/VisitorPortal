{{-- resources/views/user-permissions.blade.php --}}
<x-app-layout>

    <x-slot name="header">
        <h2 class="font-semibold text-xl text-gray-800 leading-tight">
            My Permissions
        </h2>
    </x-slot>

    <div class="container mx-auto py-8 px-4">

        {{-- User name and roles --}}
        <div class="mb-6">
            <h2 class="text-xl font-bold text-gray-900">
                {{ $data['name'] }}
            </h2>
            <div class="flex gap-2 mt-1">
                @foreach ($data['roles'] as $role)
                    <span class="px-2 py-1 text-xs font-semibold rounded-full
                                 bg-blue-100 text-blue-700">
                        {{ $role }}
                    </span>
                @endforeach
            </div>
        </div>

        {{-- Permissions grouped by resource --}}
        <div class="grid grid-cols-1 gap-4 md:grid-cols-2 lg:grid-cols-3">
            @forelse ($data['permissions'] as $resource => $permissions)
                <div class="rounded-xl border border-gray-200
                            bg-white p-4 shadow-sm">
                    <h3 class="mb-3 text-sm font-semibold uppercase tracking-wide
                                text-gray-500">
                        {{ $resource }}
                    </h3>
                    <ul class="space-y-2">
                        @foreach ($permissions as $permission)
                            <li class="flex items-center gap-2 text-sm text-gray-700">
                                ✓ {{ $permission }}
                            </li>
                        @endforeach
                    </ul>
                </div>
            @empty
                <p class="text-gray-500">No permissions assigned.</p>
            @endforelse
        </div>

    </div>

</x-app-layout>
