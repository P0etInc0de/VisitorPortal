@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    $user = Auth::user();

    $roleNames = collect(method_exists($user, 'getRoleNames') ? $user->getRoleNames() : [__('Mitarbeitende')])
        ->map(fn ($role) => (string) $role)
        ->filter()
        ->values();

    if ($roleNames->isEmpty()) {
        $roleNames = collect([__('Mitarbeitende')]);
    }

    $navLinkClasses = function (bool $active): string {
        $base = 'flex items-center gap-3 rounded-2xl border px-4 py-3 text-base-content/75 transition-colors';

        return $active
            ? $base . ' border-primary/20 bg-primary/10 text-primary hover:bg-primary/15'
            : $base . ' border-transparent hover:bg-base-200 hover:text-base-content';
    };

    $normalizedRoles = $roleNames
        ->map(fn ($role) => \Illuminate\Support\Str::of($role)->lower()->replace(['ä', 'ö', 'ü', 'ß'], ['ae', 'oe', 'ue', 'ss'])->value())
        ->values();

    $hasAnyRole = function (array $needles) use ($normalizedRoles): bool {
        foreach ($needles as $needle) {
            foreach ($normalizedRoles as $role) {
                if (str_contains($role, $needle)) {
                    return true;
                }
            }
        }

        return false;
    };

    $canSeeAdmin = $hasAnyRole(['admin']);
	$canSeeReception = auth()->user()->hasAnyPermission(['ViewAny:Visit', 'ViewDepartment:Visit', 'ViewOwn:Visit']);
	$canSeeMonitor = auth()->user()->hasAnyPermission(['View:Monitor', 'Edit:Monitor']);
	$canSeeVisits = auth()->user()->hasAnyPermission(['Create:Visit', 'CheckIn:Visit', 'CheckOut:Visit']);
	$canSeeProfile = auth()->user()->hasAnyPermission(['ViewOwn:User', 'View:Role']);
@endphp

<div class="sticky top-0 z-30 border-b border-base-300/70 bg-base-100 [&_.btn]:min-h-11 lg:hidden">
    <div class="flex items-center justify-between px-4 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="btn btn-ghost btn-square rounded-xl"
                @click="navOpen = true"
                aria-label="{{ __('Navigation öffnen') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            <div class="flex min-w-0 items-center gap-3">
                @if ($hasLogo)
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="{{ __('Logo') }}"
                        width="108"
                        height="36"
                        loading="eager"
                        decoding="async"
                        fetchpriority="high"
                        class="block h-9 w-[6.75rem] shrink-0 object-cover object-center"
                    >
                @endif

                <p class="truncate text-sm font-semibold">{{ __('Besucherportal') }}</p>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm rounded-xl">
            {{ __('Start') }}
        </a>
    </div>
</div>

<aside class="hidden w-72 border-r border-base-300/70 bg-base-100 [&_.btn]:min-h-11 lg:flex lg:flex-col lg:px-4 lg:pt-4 lg:pb-4 xl:fixed xl:inset-y-0 xl:left-0 xl:z-30 xl:min-h-screen">
    <div class="border-b border-base-300/70 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-start gap-3">
            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Logo') }}"
                    width="120"
                    height="40"
                    loading="eager"
                    decoding="async"
                    fetchpriority="high"
                    class="block h-10 w-[7.5rem] shrink-0 object-cover object-center"
                >
            @endif

            <div class="min-w-0">
                <h1 class="text-[1.9rem] font-semibold tracking-tight">{{ __('Besucherportal') }}</h1>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-3 py-3">
        <nav class="space-y-3">
            <div>
                <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                    {{ __('Start') }}
                </div>

                <a href="{{ route('dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('dashboard')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v10h14V10" />
                    </svg>
                    <span>{{ __('Übersicht') }}</span>
                </a>
            </div>

			@if ($canSeeVisits)
            <div>
                <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                    {{ __('Portal') }}
                </div>

				@can('Create:Visit')
                <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    <span>{{ __('Besuch anlegen') }}</span>
                </a>
				@endcan

				@canany(['CheckIn:Visit', 'CheckOut:Visit'])
                <a href="{{ route('portal.check_in_out') }}" class="{{ $navLinkClasses(request()->routeIs('portal.check_in_out')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>{{ __('Check-In/Out') }}</span>
                </a>
				@endcanany
            </div>
			@endif

            @if ($canSeeReception)
                <div>
                    <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                        {{ __('Empfang') }}
                    </div>

                    <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h8" />
                        </svg>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('reception.all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span>{{ __('Alle Besuche') }}</span>
                    </a>
                </div>
            @endif

            @if ($canSeeMonitor && ($monitor = \App\Models\Monitor::query()->first()))
                <div>
                    <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                        {{ __('Monitor') }}
                    </div>

					@can('View:Monitor')
                    <a href="{{ route('monitors.show', $monitor) }}" class="{{ $navLinkClasses(request()->routeIs('monitors.show*')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="4" width="18" height="12" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 20h8M12 16v4" />
                        </svg>
                        <span>{{ __('Live-Anzeige') }}</span>
                    </a>
					@endcan

                    @can('Edit:Monitor')
                        <a href="{{ route('monitors.edit', $monitor) }}" class="{{ $navLinkClasses(request()->routeIs('monitors.edit*')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10-10a2.121 2.121 0 10-4-4L4 16v4z" />
                        </svg>
                        <span>{{ __('Editor') }}</span>
                    </a>
					@endcan
                </div>
            @endif

			@if ($canSeeProfile)
            <div>
                <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                    {{ __('Profil') }}
                </div>

				@can('viewOwn', auth()->user())
                <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0" />
                        <circle cx="12" cy="8" r="4" />
                    </svg>
                    <span>{{ __('Profil') }}</span>
                </a>
				@endcan

				@can('View:Role')
				<a href="{{ route('user-permissions') }}" class="{{ $navLinkClasses(request()->routeIs('user-permissions')) }}">
    				<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        				<path stroke-linecap="round" stroke-linejoin="round" d="M9 12l2 2 4-4" />
        				<path stroke-linecap="round" stroke-linejoin="round" d="M12 3a9 9 0 1 0 0 18A9 9 0 0 0 12 3Z" />
    				</svg>
   	 				<span>{{ __('Meine Berechtigungen') }}</span>
				</a>
				@endcan

                @if ($canSeeAdmin)
                    <a href="{{ url('/admin') }}" class="{{ $navLinkClasses(request()->is('admin') || request()->is('admin/*')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span>{{ __('Admin-Bereich') }}</span>
                    </a>
                @endif
            </div>
			@endif
        </nav>
    </div>

    <div class="border-t border-base-300/70 px-3 py-3">
        <div class="rounded-2xl border border-base-300/80 bg-base-100/85 px-4 py-4">
            <div class="font-semibold">{{ $user->name }}</div>
            <div class="mt-1 text-sm text-base-content/65">{{ $user->email }}</div>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-outline w-full rounded-2xl">{{ __('Abmelden') }}</button>
        </form>
    </div>
</aside>

<div
    x-cloak
    x-show="navOpen"
    class="fixed inset-0 z-40 bg-base-content/20 lg:hidden"
    @click.self="navOpen = false"
>
    <div class="absolute inset-y-0 left-0 flex w-80 max-w-[88vw] flex-col border-r border-base-300/70 bg-base-100">
        <div class="flex items-center justify-between border-b border-base-300/70 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                @if ($hasLogo)
                    <img
                        src="{{ asset('images/logo.png') }}"
                        alt="{{ __('Logo') }}"
                        width="108"
                        height="36"
                        loading="eager"
                        decoding="async"
                        fetchpriority="high"
                        class="block h-9 w-[6.75rem] shrink-0 object-cover object-center"
                    >
                @endif
                <span class="truncate text-base font-semibold">{{ __('Besucherportal') }}</span>
            </div>

            <button
                type="button"
                class="btn btn-ghost btn-square rounded-xl"
                @click="navOpen = false"
                aria-label="{{ __('Navigation schließen') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-5">
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('dashboard')) }}" @click="navOpen = false">{{ __('Übersicht') }}</a>
                <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}" @click="navOpen = false">{{ __('Besuch anlegen') }}</a>
                <a href="{{ route('portal.check_in_out') }}" class="{{ $navLinkClasses(request()->routeIs('portal.check_in_out')) }}" @click="navOpen = false">{{ __('Check-In/Out') }}</a>

                @if ($canSeeReception)
                    <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}" @click="navOpen = false">{{ __('Dashboard') }}</a>
                    <a href="{{ route('reception.all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits')) }}" @click="navOpen = false">{{ __('Alle Besuche') }}</a>
                @endif

                @if ($canSeeMonitor && ($monitor = \App\Models\Monitor::query()->first()))
                    <a href="{{ route('monitors.show', $monitor) }}" class="{{ $navLinkClasses(request()->routeIs('monitors.show*')) }}" @click="navOpen = false">{{ __('Live-Anzeige') }}</a>
                    <a href="{{ route('monitors.edit', $monitor) }}" class="{{ $navLinkClasses(request()->routeIs('monitors.edit*')) }}" @click="navOpen = false">{{ __('Editor') }}</a>
                @endif

                <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}" @click="navOpen = false">{{ __('Profil') }}</a>
                <a href="{{ route('user-permissions') }}" class="{{ $navLinkClasses(request()->routeIs('user-permissions')) }}" @click="navOpen = false">{{ __('Meine Berechtigungen') }}</a>

                @if ($canSeeAdmin)
                    <a href="{{ url('/admin') }}" class="{{ $navLinkClasses(request()->is('admin') || request()->is('admin/*')) }}" @click="navOpen = false">{{ __('Admin-Bereich') }}</a>
                @endif
            </nav>
        </div>

        <div class="border-t border-base-300 px-4 py-4">
            <div class="rounded-2xl border border-base-300/80 bg-base-100/80 px-4 py-4">
                <p class="text-sm font-semibold">{{ $user->name }}</p>
                <p class="mt-1 text-sm text-base-content/65">{{ $user->email }}</p>
            </div>

            <form method="POST" action="{{ route('logout') }}" class="mt-3">
                @csrf
                <button type="submit" class="btn btn-outline w-full rounded-xl">{{ __('Abmelden') }}</button>
            </form>
        </div>
    </div>
</div>
