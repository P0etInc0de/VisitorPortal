<section>
    <header>
        <h2 class="text-xl font-semibold tracking-tight text-base-content">
            {{ __('Sprache ändern') }}
        </h2>
    </header>

    <form action="/lang" method="GET" class="mt-6 space-y-6">
        @csrf
        <select
            name="locale"
            onchange="this.form.submit()"
            class="rounded-lg border border-gray-300 bg-white px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
        >
            @if(app()->getLocale() === 'de')
                <option value="de" selected>🇩🇪 Deutsch</option>
                <option value="en">🇬🇧 English</option>
            @else
                <option value="en" selected>🇬🇧 English</option>
                <option value="de">🇩🇪 Deutsch</option>
            @endif
        </select>
    </form>
</section>
