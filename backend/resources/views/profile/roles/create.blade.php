<x-app-layout>
    <div class="mx-auto max-w-3xl px-4 py-10 sm:px-6">

        {{-- Header --}}
        <div class="mb-8">
            <h1 class="text-2xl font-semibold tracking-tight">{{ __('Rolle erstellen') }}</h1>
            <p class="mt-1 text-sm text-base-content/60">{{ __('Erstelle eine neue Rolle und weise Berechtigungen zu.') }}</p>
        </div>

        <form method="POST" action="{{ route('roles.store') }}">
            @csrf

            {{-- Role name --}}
            <div class="mb-6 rounded-2xl border border-base-300/70 bg-base-100 px-6 py-5">
                <h2 class="mb-4 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
                    {{ __('Rollenname') }}
                </h2>

                <input
                    type="text"
                    name="name"
                    value="{{ old('name') }}"
                    placeholder="{{ __('z. B. Empfang, Manager …') }}"
                    class="input input-bordered w-full rounded-xl"
                    required
                />

                @error('name')
                    <p class="mt-2 text-sm text-error">{{ $message }}</p>
                @enderror
            </div>

            {{-- Permissions --}}
            <div class="mb-6 rounded-2xl border border-base-300/70 bg-base-100 px-6 py-5">
                <h2 class="mb-4 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
                    {{ __('Berechtigungen') }}
                </h2>

                @if ($permissions->isEmpty())
                    <p class="text-sm text-base-content/50">{{ __('Keine Berechtigungen verfügbar.') }}</p>
                @else
                    @foreach ($permissions->groupBy(fn($p) => explode(' ', $p->name)[1] ?? 'general') as $group => $groupPerms)
                        <div class="mb-5 last:mb-0">
                            <p class="mb-2 text-sm font-semibold capitalize">{{ $group }}</p>
                            <div class="space-y-2">
                                @foreach ($groupPerms as $permission)
                                    <label class="flex cursor-pointer items-center gap-3 rounded-xl border border-base-300/60 px-4 py-3 transition hover:bg-base-200/50">
                                        <input
                                            type="checkbox"
                                            name="permissions[]"
                                            value="{{ $permission->name }}"
                                            class="checkbox checkbox-sm rounded-lg"
                                            {{ in_array($permission->name, old('permissions', [])) ? 'checked' : '' }}
                                        />
                                        <span class="text-sm">{{ $permission->name }}</span>
                                    </label>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                @endif
            </div>

            {{-- Success message --}}
            @if (session('success'))
                <div class="mb-6 rounded-2xl border border-success/30 bg-success/10 px-6 py-4">
                    <p class="text-sm font-medium text-success">{{ session('success') }}</p>
                </div>
            @endif

            {{-- Submit --}}
            <div class="flex justify-end">
                <button type="submit" class="btn btn-neutral rounded-xl px-6">
                    {{ __('Rolle erstellen') }}
                </button>
            </div>

        </form>
    </div>
</x-app-layout>
