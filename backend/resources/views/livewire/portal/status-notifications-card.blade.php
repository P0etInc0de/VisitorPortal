<aside wire:poll.10s.visible class="rounded-3xl border border-base-300 bg-base-100 shadow-sm rounded-[1.35rem] lg:flex lg:h-full lg:min-h-0 lg:flex-col lg:overflow-hidden">
    <div class="px-4 pt-4 sm:px-[1.05rem] sm:pt-[1.05rem]">
        <h2 class="mb-3 text-[1.35rem] font-bold leading-tight tracking-tight text-base-content">
            {{ __('Status & Benachrichtigungen') }}
        </h2>
    </div>

    <div class="px-4 pb-4 sm:px-[1.05rem] sm:pb-[1.05rem] lg:min-h-0 lg:flex-1 lg:overflow-y-auto lg:overscroll-contain">
        <div class="grid gap-3">
            @forelse ($notifications as $notification)
                <article
                    wire:key="notification-{{ $notification['id'] }}"
                    @class([
                        'rounded-2xl border px-4 py-3 transition-colors',
                        'border-base-300 bg-base-100/95 shadow-sm' => ! $notification['is_read'],
                        'border-base-300/70 bg-base-200/90 shadow-none' => $notification['is_read'],
                    ])
                >
                    <div class="grid gap-3">
                        <div class="flex items-start gap-3">
                            <div class="min-w-0 flex-1">
                                <h3 @class([
                                    'font-semibold',
                                    'text-base-content' => ! $notification['is_read'],
                                    'text-base-content/40' => $notification['is_read'],
                                ])>
                                    {{ $notification['title'] }}
                                </h3>

                                @if (filled($notification['created_at']))
                                    <div @class([
                                        'mt-1 text-xs text-base-content/50' => ! $notification['is_read'],
                                        'mt-1 text-xs text-base-content/30' => $notification['is_read'],
                                    ])>{{ $notification['created_at'] }}</div>
                                @endif
                            </div>

                            <div class="flex shrink-0 items-center gap-1">
                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm btn-square rounded-xl"
                                    wire:click="toggleRead('{{ $notification['id'] }}')"
                                    aria-label="{{ $notification['is_read'] ? __('Als ungelesen markieren') : __('Als gelesen markieren') }}"
                                    title="{{ $notification['is_read'] ? __('Als ungelesen markieren') : __('Als gelesen markieren') }}"
                                >
                                    @if ($notification['is_read'])
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-base-content/40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M3 3l18 18" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M10.58 10.58a2 2 0 0 0 2.84 2.84" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.88 5.09A9.77 9.77 0 0 1 12 4.86c4.48 0 8.27 2.94 9.54 7.01a10.77 10.77 0 0 1-4.13 5.11" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.61 6.61A10.75 10.75 0 0 0 2.46 11.87c1.27 4.07 5.06 7.01 9.54 7.01 1.47 0 2.88-.32 4.14-.89" />
                                        </svg>
                                    @else
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.458 12C3.732 7.943 7.523 5 12 5s8.268 2.943 9.542 7c-1.274 4.057-5.065 7-9.542 7S3.732 16.057 2.458 12Z" />
                                            <circle cx="12" cy="12" r="3" />
                                        </svg>
                                    @endif
                                </button>

                                <button
                                    type="button"
                                    class="btn btn-ghost btn-sm btn-square rounded-xl"
                                    wire:click="deleteNotification('{{ $notification['id'] }}')"
                                    aria-label="{{ __('Benachrichtigung löschen') }}"
                                    title="{{ __('Benachrichtigung löschen') }}"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" @class([
                                        'h-4 w-4' => ! $notification['is_read'],
                                        'h-4 w-4 text-base-content/40' => $notification['is_read'],
                                    ]) viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 7h16" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M10 11v6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M14 11v6" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 7l1 12h10l1-12" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 7V4h6v3" />
                                    </svg>
                                </button>
                            </div>
                        </div>

                        <div class="min-w-0">
                            <p @class([
                                'text-sm leading-6',
                                'text-base-content/85' => ! $notification['is_read'],
                                'text-base-content/38' => $notification['is_read'],
                            ])>
                                {{ $notification['message'] }}
                            </p>

                            @if (filled($notification['action_url']))
                                <a
                                    href="{{ $notification['action_url'] }}"
                                    target="_blank"
                                    rel="noreferrer noopener"
                                    @class([
                                        'mt-2 inline-flex text-sm font-medium hover:text-primary/80 text-primary' => ! $notification['is_read'],
                                        'mt-2 inline-flex text-sm font-medium text-base-content/35 hover:text-base-content/55' => $notification['is_read'],
                                    ])
                                >
                                    {{ $notification['action_label'] }}
                                </a>
                            @endif
                        </div>
                    </div>
                </article>
            @empty
                <div class="rounded-2xl border border-dashed border-base-300 bg-base-100/80 px-4 py-6 text-sm leading-6 text-base-content/65">
                    {{ __('Aktuell gibt es keine Benachrichtigungen.') }}
                </div>
            @endforelse
        </div>
    </div>
</aside>
