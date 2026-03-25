@php
    $hasLogo = file_exists(public_path('images/logo.png'));

    $navLinkClasses = function (bool $active): string {
        return $active
            ? 'flex items-center gap-3 rounded-2xl bg-base-content px-4 py-3 text-base-100 shadow-sm'
            : 'flex items-center gap-3 rounded-2xl px-4 py-3 text-base-content/75 transition hover:bg-base-200 hover:text-base-content';
    };
@endphp

{{-- Mobile top bar --}}
<div class="sticky top-0 z-40 border-b border-base-300 bg-base-100/95 backdrop-blur lg:hidden">
    <div class="flex items-center justify-between px-4 py-4">
        <div class="flex min-w-0 items-center gap-3">
            <button
                type="button"
                class="btn btn-ghost btn-square rounded-2xl"
                @click="navOpen = true"
                aria-label="{{ __('Navigation öffnen') }}"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h16" />
                </svg>
            </button>

            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Logo') }}"
                    class="max-h-8 w-auto object-contain"
                >
            @endif

            <div class="min-w-0">
                <p class="truncate text-sm font-medium">{{ __('Besucherportal') }}</p>
                <p class="truncate text-xs text-base-content/60">{{ __('Internes Portal') }}</p>
            </div>
        </div>

        <a href="{{ route('dashboard') }}" class="btn btn-ghost rounded-2xl">
            {{ __('Dashboard') }}
        </a>
    </div>
</div>

{{-- Desktop sidebar --}}
<aside class="fixed inset-y-0 left-0 z-30 hidden w-72 border-r border-base-300 bg-base-100 lg:flex lg:flex-col">
    <div class="border-b border-base-300 px-6 py-6">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-4">
            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Logo') }}"
                    class="max-h-12 w-auto object-contain"
                >
            @endif

                <div class="min-w-0">
                    <h1 class="truncate text-xl font-semibold tracking-tight">
                        {{ __('Besucherportal') }}
                    </h1>
                </div>
        </a>
    </div>

    <div class="flex-1 overflow-y-auto px-4 py-5">

        <nav class="space-y-2">
            <a href="{{ route('dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('dashboard')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v10h14V10" />
                </svg>
                <span>{{ __('Dashboard') }}</span>
            </a>

            <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 5v14M5 12h14" />
                </svg>
                <span>{{ __('Besuch erfassen') }}</span>
            </a>

            <a href="{{ route('portal.visits.show') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.show')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11M4 6h.01M4 12h.01M4 18h.01" />
                </svg>
                <span>{{ __('Besuchsdetail') }}</span>
            </a>

            <div class="my-4 border-t border-base-300"></div>

            <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16M4 12h16M4 17h8" />
                </svg>
                <span>{{ __('Rezeption') }}</span>
            </a>

            <a href="{{ route('monitor.live') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.live')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <rect x="3" y="4" width="18" height="12" rx="2" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M8 20h8M12 16v4" />
                </svg>
                <span>{{ __('Monitor anzeigen') }}</span>
            </a>

            <a href="{{ route('monitor.editor') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.editor')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4 20h4l10-10a2.12 2.12 0 1 0-3-3L5 17v3Z" />
                </svg>
                <span>{{ __('Monitor Editor') }}</span>
            </a>

            <div class="my-4 border-t border-base-300"></div>

            <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20 21a8 8 0 1 0-16 0" />
                    <circle cx="12" cy="7" r="4" />
                </svg>
                <span>{{ __('Profil') }}</span>
            </a>
        </nav>
    </div>

    <div class="border-t border-base-300 px-4 py-4">
        <div class="mb-3 rounded-2xl bg-base-200/70 px-4 py-3">
            <p class="text-sm font-medium">{{ Auth::user()->name }}</p>
            <p class="text-sm text-base-content/65">{{ Auth::user()->email }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline w-full rounded-2xl">
                {{ __('Abmelden') }}
            </button>
        </form>
    </div>
</aside>

{{-- Mobile drawer --}}
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
    <div class="flex items-center justify-between border-b border-base-300 px-4 py-4">
        <div class="flex min-w-0 items-center gap-3">
            @if ($hasLogo)
                <img
                    src="{{ asset('images/logo.png') }}"
                    alt="{{ __('Logo') }}"
                    class="max-h-8 w-auto object-contain"
                >
            @endif

            <div class="min-w-0">
                <p class="truncate font-medium">{{ __('Besucherportal') }}</p>
                <p class="truncate text-xs text-base-content/60">{{ __('Internes Portal') }}</p>
            </div>
        </div>

        <button
            type="button"
            class="btn btn-ghost btn-square rounded-2xl"
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
                <span>{{ __('Dashboard') }}</span>
            </a>

            <a href="{{ route('portal.visits.create') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.create')) }}" @click="navOpen = false">
                <span>{{ __('Besuch erfassen') }}</span>
            </a>

            <a href="{{ route('portal.visits.show') }}" class="{{ $navLinkClasses(request()->routeIs('portal.visits.show')) }}" @click="navOpen = false">
                <span>{{ __('Besuchsdetail') }}</span>
            </a>

            <a href="{{ route('reception.dashboard') }}" class="{{ $navLinkClasses(request()->routeIs('reception.dashboard')) }}" @click="navOpen = false">
                <span>{{ __('Rezeption') }}</span>
            </a>

            <a href="{{ route('monitor.live') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.live')) }}" @click="navOpen = false">
                <span>{{ __('Monitor anzeigen') }}</span>
            </a>

            <a href="{{ route('monitor.editor') }}" class="{{ $navLinkClasses(request()->routeIs('monitor.editor')) }}" @click="navOpen = false">
                <span>{{ __('Monitor Editor') }}</span>
            </a>

            <a href="{{ route('profile.edit') }}" class="{{ $navLinkClasses(request()->routeIs('profile.*')) }}" @click="navOpen = false">
                <span>{{ __('Profil') }}</span>
            </a>
        </nav>
    </div>

    <div class="border-t border-base-300 px-4 py-4">
        <div class="mb-3 rounded-2xl bg-base-200/70 px-4 py-3">
            <p class="text-sm font-medium">{{ Auth::user()->name }}</p>
            <p class="text-sm text-base-content/65">{{ Auth::user()->email }}</p>
        </div>

        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="btn btn-outline w-full rounded-2xl">
                {{ __('Abmelden') }}
            </button>
        </form>
    </div>
</aside>
