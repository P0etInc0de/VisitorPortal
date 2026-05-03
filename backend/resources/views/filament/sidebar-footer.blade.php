<style>
    .custom-sb-container {
        padding: 1rem 1rem 1.5rem 1rem;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }
    .custom-sb-divider {
        height: 1px;
        background-color: var(--gray-200);
        margin-bottom: 0.5rem;
    }
    :is(.dark) .custom-sb-divider,
    html[data-theme="true-black"] .custom-sb-divider {
        background-color: rgba(255, 255, 255, 0.1);
    }
    .custom-sb-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        color: inherit;
        transition: all 0.2s ease-in-out;
    }
    .custom-sb-link:hover {
        background-color: rgba(156, 163, 175, 0.15);
    }
    .custom-sb-link svg {
        width: 1.25rem;
        height: 1.25rem;
        opacity: 0.7;
    }
    .custom-sb-box {
        border: 1px solid var(--gray-200);
        border-radius: 1rem;
        padding: 1rem;
        background-color: var(--gray-50);
    }
    :is(.dark) .custom-sb-box,
    html[data-theme="true-black"] .custom-sb-box {
        border-color: rgba(255, 255, 255, 0.1);
        background-color: rgba(255, 255, 255, 0.03);
    }
    .custom-sb-name {
        font-weight: 600;
        font-size: 0.875rem;
    }
    .custom-sb-email {
        font-size: 0.75rem;
        opacity: 0.6;
        margin-top: 0.125rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    .custom-sb-btn {
        display: block;
        width: 100%;
        border-radius: 1rem;
        border: 1px solid var(--gray-200);
        background-color: transparent;
        padding: 0.625rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        color: inherit;
    }
    :is(.dark) .custom-sb-btn,
    html[data-theme="true-black"] .custom-sb-btn {
        border-color: rgba(255, 255, 255, 0.1);
    }
    .custom-sb-btn:hover {
        background-color: rgba(156, 163, 175, 0.15);
    }
</style>

<div class="custom-sb-container">
    {{-- Trennlinie --}}
    <div class="custom-sb-divider"></div>

    {{-- User Area Link --}}
    <a href="{{ route('overview') }}" class="custom-sb-link">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v10h14V10" />
        </svg>
        {{ __('User Area') }}
    </a>

    {{-- User Info Box --}}
    <div class="custom-sb-box">
        <div class="custom-sb-name">{{ auth()->user()->name }}</div>
        <div class="custom-sb-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</div>
    </div>

    {{-- Logout Button --}}
    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
        @csrf
        <button type="submit" class="custom-sb-btn">
            {{ __('Abmelden') }}
        </button>
    </form>
</div>
