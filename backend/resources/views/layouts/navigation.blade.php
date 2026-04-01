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

            @if ($hasLogo)
                <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" class="max-h-9 w-auto object-contain">
            @endif

            <div class="min-w-0">
                <p class="truncate text-sm font-semibold">{{ __('Besucherportal') }}</p>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-outline btn-sm rounded-xl">
            {{ __('Start') }}
        </a>
    </div>
</div>

<aside class="bp-sidebar fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-base-300/70 lg:flex lg:flex-col">
    <div class="border-b border-base-300/70 px-6 py-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-4">
            @if ($hasLogo)
                <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" class="max-h-12 w-auto object-contain">
            @endif

            <div class="min-w-0">
                <h1 class="truncate text-xl font-semibold tracking-tight">{{ __('Besucherportal') }}</h1>
            </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-5">
        <nav class="space-y-5">
            <div>
                <div class="mb-2 px-3 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
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
                <div class="mb-2 px-3 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
                    {{ __('Portal') }}
                </div>

                <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                    </svg>
                    <span>{{ __('Besuch anlegen') }}</span>
                </a>

                <a href="{{ route('portal.check_in_out') }}" class="{{ $navLinkClasses(request()->routeIs('portal.check_in_out')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 mr-1" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                    </svg>
                    <span>{{ __('Check-In/Out') }}</span>
                </a>

                {{-- @if ($canSeeReception) --}}
                    <a href="{{ route('reception.badge') }}" class="{{ $navLinkClasses(request()->routeIs('reception.badge')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 5v2m0 4v2m0 4v2M5 5a2 2 0 012-2h10a2 2 0 012 2v14a2 2 0 01-2 2H7a2 2 0 01-2-2V5z M10 9h3M10 13h3" />
                        </svg>
                        <span>{{ __('Ausweis drucken') }}</span>
                    </a>
                {{-- @endif --}}
            </div>

            @if ($canSeeReception)
                <div>
                    <div class="mb-2 px-3 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
                        {{ __('Empfang') }}
                    </div>

                    <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h8" />
                        </svg>
                        <span>{{ __('Dashboard') }}</span>
                    </a>

                    <a href="{{ \Illuminate\Support\Facades\Route::has('reception.all-visits') ? route('reception.all-visits') : url('/reception/all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits') || request()->is('reception/all-visits')) }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                        </svg>
                        <span>{{ __('Alle Besuche') }}</span>
                    </a>
                </div>
            @endif

            @if ($canSeeMonitor)
                <div>
                    <div class="mb-2 px-3 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
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
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10-10a2.12 2.12 0 1 0-3-3L5 17v3Z" />
                        </svg>
                        <span>{{ __('Editor') }}</span>
                    </a>
                </div>
            @endif

            <div>
                <div class="mb-2 px-3 text-xs font-medium uppercase tracking-[0.16em] text-base-content/45">
                    {{ __('Profil') }}
                </div>

                <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="bp-nav-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 1 0-16 0" />
                        <circle cx="12" cy="7" r="4" />
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

    <div class="border-t border-base-300/70 px-4 py-4">
        <div class="rounded-2xl border border-base-300/80 bg-base-100/80 px-4 py-4">
            <p class="text-sm font-semibold">{{ $user->name }}</p>
            <p class="mt-1 text-sm text-base-content/65">{{ $user->email }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-outline w-full rounded-xl">
                {{ __('Abmelden') }}
            </button>
        </form>
    </div>
</aside>

<div x-show="navOpen" x-transition.opacity class="fixed inset-0 z-50 bg-black/30 lg:hidden" style="display: none;" @click="navOpen = false"></div>

<aside
    x-show="navOpen"
    x-transition:enter="transition duration-200 ease-out"
    x-transition:enter-start="-translate-x-full"
    x-transition:enter-end="translate-x-0"
    x-transition:leave="transition duration-150 ease-in"
    x-transition:leave-start="translate-x-0"
    x-transition:leave-end="-translate-x-full"
    class="fixed inset-y-0 left-0 z-50 flex w-80 max-w-[88vw] flex-col border-r border-base-300 bg-base-100 lg:hidden"
    style="display: none;"
>
    <div class="flex items-start justify-between border-b border-base-300 px-4 py-4">
        <div class="min-w-0">
            <div class="flex items-center gap-3">
                @if ($hasLogo)
                    <img src="{{ asset('images/logo.png') }}" alt="{{ __('Logo') }}" class="max-h-8 w-auto object-contain">
                @endif
                <div>
                    <p class="truncate font-semibold">{{ __('Besucherportal') }}</p>
                </div>
            </div>
        </div>

        <button
            type="button"
            class="btn btn-ghost btn-square rounded-xl"
            @click="navOpen = false"
            aria-label="{{ __('Navigation schließen') }}"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
            </svg>
        </button>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-5">
        <nav class="space-y-2">
            <a href="{{ route('dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('dashboard')) }}" @click="navOpen = false">
                <span>{{ __('Übersicht') }}</span>
            </a>
            <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}" @click="navOpen = false">
                <span>{{ __('Besuch anlegen') }}</span>
            </a>
            @if ($canSeeReception)
            <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}" @click="navOpen = false">
                <span>{{ __('Dashboard') }}</span>
            </a>
            <a href="{{ \Illuminate\Support\Facades\Route::has('reception.all-visits') ? route('reception.all-visits') : url('/reception/all-visits') }}" class="{{ $navLinkClasses(request()->routeIs('reception.all-visits') || request()->is('reception/all-visits')) }}" @click="navOpen = false">
                <span>{{ __('Alle Besuche') }}</span>
            </a>
            @endif
            @if ($canSeeMonitor)
            <a href="{{ route('monitor.live') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.live')) }}" @click="navOpen = false">
                <span>{{ __('Live-Anzeige') }}</span>
            </a>
            <a href="{{ route('monitor.editor') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.editor')) }}" @click="navOpen = false">
                <span>{{ __('Editor') }}</span>
            </a>
            @endif
            @if ($canSeeAdmin)
            <a href="{{ url('/admin') }}" class="{{ $navLinkClasses(request()->is('admin') || request()->is('admin/*')) }}" @click="navOpen = false">
                <span>{{ __('Admin-Bereich') }}</span>
            </a>
            @endif
            <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}" @click="navOpen = false">
                <span>{{ __('Profil') }}</span>
            </a>
        </nav>
    </div>

    <div class="border-t border-base-300 px-4 py-4">
        <div class="rounded-2xl border border-base-300/80 bg-base-100/80 px-4 py-4">
            <p class="text-sm font-semibold">{{ $user->name }}</p>
            <p class="mt-1 text-sm text-base-content/65">{{ $user->email }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}" class="mt-3">
            @csrf
            <button type="submit" class="btn btn-outline w-full rounded-xl">
                {{ __('Abmelden') }}
            </button>
        </form>
    </div>
</aside>
