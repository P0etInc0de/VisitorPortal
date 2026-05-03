<style>
    /* Base container styling without horizontal padding to inherit standard Filament navigation alignment */
    .custom-sb-container {
        padding: 1rem 0 1.5rem 0;
        display: flex;
        flex-direction: column;
        gap: 0.75rem;
    }

    /* Divider line */
    .custom-sb-divider {
        height: 1px;
        background-color: #e5e7eb;
        /* Match standard left and right margin of native navigation items */
        margin: 0.5rem 0.75rem;
    }
    :is(.dark) .custom-sb-divider,
    html[data-theme="true-black"] .custom-sb-divider {
        background-color: rgba(255, 255, 255, 0.1);
    }

    /* Link to user area */
    .custom-sb-link {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        /* Match Filament's native item padding */
        padding: 0.5rem 0.75rem;
        border-radius: 0.5rem;
        font-size: 0.875rem;
        font-weight: 500;
        text-decoration: none;
        color: #374151;
        transition: all 0.2s ease-in-out;
    }
    :is(.dark) .custom-sb-link,
    html[data-theme="true-black"] .custom-sb-link {
        color: #d1d5db;
    }
    .custom-sb-link:hover {
        background-color: #f3f4f6;
    }
    :is(.dark) .custom-sb-link:hover,
    html[data-theme="true-black"] .custom-sb-link:hover {
        background-color: rgba(255, 255, 255, 0.05);
        color: #ffffff;
    }

    /* Icon styling to match Filament's standard h-6 w-6 sizing */
    .custom-sb-link svg {
        width: 1.5rem;
        height: 1.5rem;
        color: #9ca3af;
        transition: color 0.2s ease-in-out;
    }
    .custom-sb-link:hover svg {
        color: #6b7280;
    }
    :is(.dark) .custom-sb-link svg,
    html[data-theme="true-black"] .custom-sb-link svg {
        color: #6b7280;
    }
    :is(.dark) .custom-sb-link:hover svg,
    html[data-theme="true-black"] .custom-sb-link:hover svg {
        color: #d1d5db;
    }

    /* User information box */
    .custom-sb-box {
        border: 1px solid #e5e7eb;
        border-radius: 0.75rem;
        padding: 0.75rem 1rem;
        background-color: #f9fafb;
        margin: 0 0.75rem;
    }
    :is(.dark) .custom-sb-box,
    html[data-theme="true-black"] .custom-sb-box {
        border-color: rgba(255, 255, 255, 0.1);
        background-color: rgba(255, 255, 255, 0.03);
    }
    .custom-sb-name {
        font-weight: 600;
        font-size: 0.875rem;
        color: #111827;
    }
    :is(.dark) .custom-sb-name,
    html[data-theme="true-black"] .custom-sb-name {
        color: #ffffff;
    }
    .custom-sb-email {
        font-size: 0.75rem;
        color: #6b7280;
        margin-top: 0.125rem;
        overflow: hidden;
        text-overflow: ellipsis;
        white-space: nowrap;
    }
    :is(.dark) .custom-sb-email,
    html[data-theme="true-black"] .custom-sb-email {
        color: #9ca3af;
    }

    /* Logout action */
    .custom-sb-btn {
        display: block;
        width: calc(100% - 1.5rem);
        margin: 0 0.75rem;
        border-radius: 0.75rem;
        border: 1px solid #d1d5db;
        background-color: transparent;
        padding: 0.5rem 1rem;
        font-size: 0.875rem;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease-in-out;
        color: #374151;
    }
    :is(.dark) .custom-sb-btn,
    html[data-theme="true-black"] .custom-sb-btn {
        border-color: rgba(255, 255, 255, 0.2);
        color: #e5e7eb;
    }
    .custom-sb-btn:hover {
        background-color: #f9fafb;
    }
    :is(.dark) .custom-sb-btn:hover,
    html[data-theme="true-black"] .custom-sb-btn:hover {
        background-color: rgba(255, 255, 255, 0.1);
        border-color: rgba(255, 255, 255, 0.4);
    }
</style>

<div class="custom-sb-container">
    <!-- Divider line -->
    <div class="custom-sb-divider"></div>

    <!-- Link to user area -->
    <a href="{{ route('overview') }}" class="custom-sb-link">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3 12l9-8 9 8" />
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 10v10h14V10" />
        </svg>
        {{ __('User Area') }}
    </a>

    <!-- User information box -->
    <div class="custom-sb-box">
        <div class="custom-sb-name">{{ auth()->user()->name }}</div>
        <div class="custom-sb-email" title="{{ auth()->user()->email }}">{{ auth()->user()->email }}</div>
    </div>

    <!-- Logout action -->
    <form method="POST" action="{{ route('logout') }}" style="margin: 0;">
        @csrf
        <button type="submit" class="custom-sb-btn">
            {{ __('Logout') }}
        </button>
    </form>
</div>
