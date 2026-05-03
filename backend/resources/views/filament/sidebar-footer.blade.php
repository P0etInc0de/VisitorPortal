<div class="flex flex-col gap-4 px-4 pb-4">
    {{-- Trennlinie --}}
    <div class="h-px bg-gray-200 dark:bg-white/10"></div>

    {{-- User Area Link --}}
    <a href="{{ route('overview') }}" class="flex items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-gray-700 transition hover:bg-gray-100 dark:text-gray-200 dark:hover:bg-white/5">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="w-5 h-5 text-gray-500 dark:text-gray-400">
            <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
        </svg>
        {{ __('User Area') }}
    </a>

    {{-- User Info Box --}}
    <div class="rounded-xl border border-gray-200 bg-white p-3 shadow-sm dark:border-white/10 dark:bg-gray-900">
        <p class="truncate text-sm font-semibold text-gray-950 dark:text-white">{{ auth()->user()->name }}</p>
        <p class="truncate text-xs text-gray-500 dark:text-gray-400">{{ auth()->user()->email }}</p>
    </div>

    {{-- Logout Button --}}
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="w-full rounded-lg bg-danger-600 px-3 py-2 text-sm font-medium text-white shadow-sm hover:bg-danger-500 focus:outline-none focus:ring-2 focus:ring-danger-500 focus:ring-offset-2 dark:focus:ring-offset-gray-900 transition">
            {{ __('Abmelden') }}
        </button>
    </form>
</div>
