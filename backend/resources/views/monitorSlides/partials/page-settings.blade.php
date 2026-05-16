@php
    $slideNumber = $slideNumber ?? null;
    $toggleValues = $toggleValues ?? [];
    $displayMode = $displayMode ?? \App\Models\Monitor::DEFAULT_MONITOR_DISPLAY_MODE;
@endphp

<div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
    <div class="rounded-2xl border border-base-300 bg-base-200 p-3.5">
        <label class="form-control max-w-[7rem]">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">{{ __('Seitennummer') }}</span>
            </div>

            <input
                type="number"
                min="1"
                step="1"
                name="slide_number"
                value="{{ old('slide_number', $slideNumber) }}"
                class="input input-bordered h-11 w-full rounded-xl border-base-300 bg-base-100 focus:border-primary transition-all"
            >
        </label>

        @error('slide_number')
            <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
        @enderror
    </div>

    @foreach ($toggleValues as $toggle)
        <div class="rounded-2xl border border-base-300 bg-base-200 p-3.5">
            <label class="flex cursor-pointer flex-col items-start gap-3">
                <span class="text-sm font-medium text-base-content">{{ $toggle['label'] }}</span>

                <span class="shrink-0">
                    <input type="hidden" name="{{ $toggle['name'] }}" value="0">
                    <input
                        type="checkbox"
                        name="{{ $toggle['name'] }}"
                        value="1"
                        @checked(old($toggle['name'], $toggle['checked']))
                        class="toggle toggle-primary toggle-sm transition-all"
                    >
                </span>
            </label>
        </div>
    @endforeach

    <div class="rounded-2xl border border-base-300 bg-base-200 p-3.5 sm:col-span-2 xl:col-span-4">
        <label class="form-control">
            <div class="label px-0 pb-2">
                <span class="label-text text-sm font-medium text-base-content">{{ __('Besucheranzeige') }}</span>
            </div>
            <select
                id="monitorDisplayMode"
                name="monitor_display_mode"
                class="select select-bordered h-11 w-full rounded-xl border-base-300 bg-base-100 focus:border-primary transition-all @error('monitor_display_mode') select-error @enderror"
            >
                @foreach (\App\Models\Monitor::displayModeOptions() as $value => $label)
                    <option value="{{ $value }}" @selected(old('monitor_display_mode', $displayMode) === $value)>{{ $label }}</option>
                @endforeach
            </select>
        </label>

        @error('monitor_display_mode')
            <span class="mt-1 block text-sm font-medium text-red-500">{{ $message }}</span>
        @enderror
    </div>
</div>
