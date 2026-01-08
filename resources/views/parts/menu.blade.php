<header class="bg-white shadow">
    <div class="mx-auto px-6 py-4 flex items-center justify-between">

        <!-- LEWA STRONA: LOGO + NAZWA -->
        <div class="flex items-center gap-4">
            @php
                $companySettings = \App\Models\CompanySetting::first();
                // Logo może być w formacie base64 (data:image/...) lub ścieżka do pliku
                if ($companySettings && $companySettings->logo) {
                    if (str_starts_with($companySettings->logo, 'data:image')) {
                        $logoPath = $companySettings->logo; // już jest base64
                    } else {
                        $logoPath = asset('storage/' . $companySettings->logo); // stary format
                    }
                } else {
                    $logoPath = '/logo.png';
                }
                $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Magazyn "ProximaLumine"';
            @endphp
            <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
            <span class="text-xl font-bold">
                {{ $companyName }}
                @if(!$companySettings || !$companySettings->name)
                    <span class="block text-xs text-gray-400 font-normal mt-1">(Ustaw dane swojej firmy w Ustawieniach/Dane Mojej Firmy)</span>
                @endif
            </span>
        </div>

        <!-- PRAWA STRONA: MENU -->
        <nav class="flex gap-2 items-center flex-wrap justify-end">
            <a href="{{ url('/') }}"
               class="px-3 py-2 text-sm bg-gray-200 rounded whitespace-nowrap">
                Start
            </a>

            @if(auth()->check() && auth()->user()->can_view_catalog)
                <a href="{{ route('magazyn.check') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    🔍Katalog
                </a>
            @endif

            @if(auth()->check() && auth()->user()->can_remove)
                <a href="{{ route('magazyn.remove') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    ➖Pobierz
                </a>
            @endif

            @if(auth()->check() && auth()->user()->can_add)
                <a href="{{ route('magazyn.add') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    ➕Dodaj
                </a>
            @endif

            @if(auth()->check() && auth()->user()->can_orders)
                <a href="{{ route('magazyn.orders') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    📦Zamówienia
                </a>
            @endif

            @if(auth()->check())
                <a href="{{ route('magazyn.projects') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    🗂️Projekty
                </a>
            @endif

            @if(auth()->check() && auth()->user()->can_settings)
                <a href="{{ route('magazyn.settings') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    ⚙️Ustawienia
                </a>
            @endif

            @auth
                <div class="border-l border-gray-300 pl-2 flex items-center gap-2">
                    <div class="text-right">
                        <span class="text-gray-700 text-sm whitespace-nowrap block">{{ Auth::user()->name }}</span>
                        <span class="text-gray-500 text-xs whitespace-nowrap block">{{ Auth::user()->is_admin ? 'Administrator' : 'Użytkownik' }}</span>
                    </div>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition whitespace-nowrap">
                            Wyloguj
                        </button>
                    </form>
                </div>
            @endauth
        </nav>

    </div>
</header>

<!-- Stopka z logo i napisem Powered by ProximaLumine -->
<div style="position: fixed; right: 20px; bottom: 10px; z-index: 50; color: #888; font-style: italic; font-size: 1rem; pointer-events: none; display: flex; align-items: center; gap: 8px;">
    <img src="{{ asset('logo_proxima.png') }}" alt="ProximaLumine" style="height:44px;vertical-align:middle;">
    <span>Powered by ProximaLumine</span>
</div>

