<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot:heading>
            {{ __('GitHub Repository') }}
        </x-slot:heading>

        <x-slot:description>
            {{ __('Besuchen Sie unser Repository') }}
        </x-slot:description>

        <x-slot:afterHeader>
            <x-filament::button
                :href="config('services.github-repo-url')"
                tag="a"
                target="_blank"
                color="gray"
                outlined
                icon="heroicon-m-arrow-top-right-on-square"
                class="bp-github-link-button"
            >
                {{ __('Open in GitHub') }}
            </x-filament::button>
        </x-slot:afterHeader>
    </x-filament::section>
</x-filament-widgets::widget>
