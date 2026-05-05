@props(['title', 'subtitle' => null, 'index'])
<div>
    <article class="mb-2 rounded-3xl border border-base-300 bg-base-100 px-5 py-5">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">

            <div class="flex items-center gap-4">
                <div class="mr-4">
                    <div class="text-[1.22rem] font-bold leading-none tracking-tight text-base-content">
                        {{ $index ?? '' }}
                    </div>
                </div>

                <div class="min-w-40">
                    <div class="truncate text-base font-bold leading-tight tracking-tight text-base-content">
                        {{$title ?? '' }}
                    </div>
                    @if($subtitle)
                        <p class="mt-1 text-sm text-base-content/65">
                            {{ $subtitle}}
                        </p>
                    @endif
                </div>

                <div class="flex-center">
                    {{ $items?? '' }}
                </div>
            </div>
            <div class="flex shrink-0 items-center gap-4">
                {{ $status ?? '' }}
                <div class="flex flex-wrap gap-2">
                    {{ $action ?? '' }}
                </div>
            </div>
        </div>
    </article>
</div>
