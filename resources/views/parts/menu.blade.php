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
                $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Magazyn 3C Automation';
            @endphp
            <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
            <span class="text-xl font-bold">
                {{ $companyName }}
            </span>
        </div>

        <!-- PRAWA STRONA: MENU -->
        <nav class="flex gap-2 items-center flex-wrap justify-end">
            <a href="{{ url('/') }}"
               class="px-3 py-2 text-sm bg-gray-200 rounded whitespace-nowrap">
                Start
            </a>

            <a href="{{ route('magazyn.check') }}"
               class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                🔍Katalog
            </a>

            <a href="{{ route('magazyn.remove') }}"
               class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                ➖Pobierz
            </a>

            <a href="{{ route('magazyn.add') }}"
               class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                ➕Dodaj
            </a>

            <a href="{{ route('magazyn.orders') }}"
               class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                📦Zamówienia!
            </a>

            <a href="{{ route('magazyn.settings') }}"
               class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                ⚙️Ustawienia
            </a>

            @auth
                <div class="border-l border-gray-300 pl-2 flex items-center gap-2">
                    <span class="text-gray-700 text-sm whitespace-nowrap">{{ Auth::user()->name }}</span>
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

