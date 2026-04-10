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
        return $active
            ? 'bp-nav-link bp-nav-link-active'
            : 'bp-nav-link';
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

    $canSeeReception = $hasAnyRole(['empfang', 'reception', 'admin']);
    $canSeeMonitor = $hasAnyRole(['empfang', 'reception', 'admin', 'monitor']);
    $canSeeAdmin = $hasAnyRole(['admin']);
@endphp

<div class="sticky top-0 z-30 border-b border-base-300/70 bp-topbar-surface lg:hidden">
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
                    <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" width="140" height="36" class="max-h-9 w-auto object-contain" style="display:block;height:36px;width:auto;max-width:140px;object-fit:contain;">
                @endif

                <p class="truncate text-sm font-semibold">{{ __('Besucherportal') }}</p>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm rounded-xl">
            {{ __('Start') }}
        </a>
    </div>
</div>

<aside class="bp-sidebar fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-base-300/70 lg:flex lg:flex-col">
    <div class="border-b border-base-300/70 px-4 py-4">
        <a href="{{ route('dashboard') }}" class="flex flex-col items-start gap-3">
            @if ($hasLogo)
                <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" width="160" height="40" class="max-h-10 w-auto object-contain" style="display:block;height:40px;width:auto;max-width:160px;object-fit:contain;">
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
                    <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v10h14V10" />
                    </svg>
                    <span>{{ __('Übersicht') }}</span>
                </a>
            </div>

            <div>
                <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                    {{ __('Portal') }}
                </div>

                <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    <span>{{ __('Besuch anlegen') }}</span>
                </a>
            </div>

            @if ($canSeeReception)
                <div>
                    <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                        {{ __('Empfang') }}
                    </div>

                    <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h8" />
                        </svg>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ route('reception.all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span>{{ __('Alle Besuche') }}</span>
                    </a>
                </div>
            @endif

            @if ($canSeeMonitor)
                <div>
                    <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                        {{ __('Monitor') }}
                    </div>

                    <a href="{{ route('monitor.live') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.live')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <rect x="3" y="4" width="18" height="12" rx="2" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M8 20h8M12 16v4" />
                        </svg>
                        <span>{{ __('Live-Anzeige') }}</span>
                    </a>

                    <a href="{{ route('monitor.editor') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.editor')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10-10a2.121 2.121 0 10-4-4L4 16v4z" />
                        </svg>
                        <span>{{ __('Editor') }}</span>
                    </a>
                </div>
            @endif

            <div>
                <div class="mb-1 px-3 text-[11px] font-medium uppercase tracking-[0.18em] text-base-content/42">
                    {{ __('Profil') }}
                </div>

                <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 10-16 0" />
                        <circle cx="12" cy="8" r="4" />
                    </svg>
                    <span>{{ __('Profil') }}</span>
                </a>

                @if ($canSeeAdmin)
                    <a href="{{ url('/admin') }}" class="{{ $navLinkClasses(request()->is('admin') || request()->is('admin/*')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span>{{ __('Admin-Bereich') }}</span>
                    </a>
                @endif
            </div>
        </nav>
    </div>

    <div class="border-t border-base-300/70 px-3 py-3">
        <div class="bp-admin-card">
            <div class="bp-admin-label">{{ $user->name }}</div>
            <div class="bp-admin-mail">{{ $user->email }}</div>
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
    class="fixed inset-0 z-40 bg-slate-950/30 backdrop-blur-[1px] lg:hidden"
    @click.self="navOpen = false"
>
    <div class="absolute inset-y-0 left-0 flex w-80 max-w-[88vw] flex-col border-r border-base-300/70 bg-white shadow-2xl">
        <div class="flex items-center justify-between border-b border-base-300/70 px-5 py-4">
            <div class="flex min-w-0 items-center gap-3">
                @if ($hasLogo)
                    <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" width="140" height="36" class="max-h-9 w-auto object-contain" style="display:block;height:36px;width:auto;max-width:140px;object-fit:contain;">
                @endif
                <span class="truncate text-base font-semibold">{{ __('Besucherportal') }}</span>
            </div>

            <button type="button" class="btn btn-ghost btn-square rounded-xl" @click="navOpen = false" aria-label="{{ __('Navigation schließen') }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>

        <div class="flex-1 overflow-y-auto px-4 py-4">
            <nav class="space-y-2">
                <a href="{{ route('dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('dashboard')) }}">{{ __('Übersicht') }}</a>
                <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}">{{ __('Besuch anlegen') }}</a>

                @if ($canSeeReception)
                    <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}">{{ __('Dashboard') }}</a>
                    <a href="{{ route('reception.all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits')) }}">{{ __('Alle Besuche') }}</a>
                @endif

                @if ($canSeeMonitor)
                    <a href="{{ route('monitor.live') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.live')) }}">{{ __('Live-Anzeige') }}</a>
                    <a href="{{ route('monitor.editor') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.editor')) }}">{{ __('Editor') }}</a>
                @endif

                @if ($canSeeAdmin)
                    <a href="{{ url('/admin') }}" class="{{ $navLinkClasses(request()->is('admin') || request()->is('admin/*')) }}">{{ __('Admin-Bereich') }}</a>
                @endif

                <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}">{{ __('Profil') }}</a>
            </nav>
        </div>
    </div>
</div>
