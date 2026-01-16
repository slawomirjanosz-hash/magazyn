<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- GÓRNY PASEK -->
<header class="bg-white shadow">
    <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            @php
                try {
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
                    $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
                } catch (\Exception $e) {
                    $logoPath = '/logo.png';
                    $companyName = 'Moja Firma';
                }
            @endphp
            <!-- LOGO -->
            <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
            <span class="text-xl font-bold">{{ $companyName }}</span>
        </div>

        <!-- MENU -->
        @auth
        <nav class="flex gap-2 items-center flex-wrap justify-end">
            @if(Auth::user()->is_admin || Auth::user()->can_settings)
                <a href="{{ route('magazyn.settings') }}" class="px-3 py-2 text-sm bg-yellow-500 hover:bg-yellow-600 text-white rounded transition whitespace-nowrap font-semibold">Ustawienia</a>
            @endif
            <div class="border-l border-gray-300 pl-2 flex items-center gap-2">
                <span class="text-gray-700 text-sm whitespace-nowrap">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
                    <span id="datetime" class="mr-3 px-3 py-2 text-sm bg-gray-200 text-gray-700 rounded whitespace-nowrap"></span>
                    <button type="submit" class="px-3 py-2 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded transition whitespace-nowrap">
                        Wyloguj
                    </button>
                </form>
            </div>
        </nav>
        @endauth
    </div>
</header>

<!-- TREŚĆ GŁÓWNA -->
<main class="max-w-6xl mx-auto mt-20 text-center">
    <!-- KOMUNIKATY -->
    @if(session('success'))
        <div class="max-w-md mx-auto mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <h1 class="text-4xl font-bold mb-4">
        Magazyn {{ $companyName }}
    </h1>

    <p class="text-gray-600 mb-8">
        System zarządzania częściami magazynowymi
    </p>

    @auth
        <div class="flex flex-col gap-4 justify-center items-center">
            @if(Auth::user()->is_admin || Auth::user()->email === 'proximalumine@gmail.com' || Auth::user()->can_view_magazyn)
                <a href="{{ route('magazyn.check') }}"
                   class="inline-block px-6 py-3 bg-blue-600 text-white rounded text-lg hover:bg-blue-700">
                    Wejdź do magazynu
                </a>
            @endif
            @if(Auth::user()->is_admin || Auth::user()->email === 'proximalumine@gmail.com' || Auth::user()->can_view_offers)
                <a href="{{ route('offers') }}" class="inline-block px-6 py-3 bg-green-600 text-white rounded text-lg hover:bg-green-700 min-w-[220px]">
                    Wyceny i Oferty
                </a>
            @endif
        </div>
    @else
        <a href="{{ route('login') }}"
           class="inline-block px-6 py-3 bg-green-600 text-white rounded text-lg hover:bg-green-700">
            Zaloguj się
        </a>
    @endauth
</main>

<!-- Stopka z logo i napisem Powered by ProximaLumine -->
<div style="position: fixed; right: 20px; bottom: 10px; z-index: 50; color: #888; font-style: italic; font-size: 1rem; pointer-events: none; display: flex; align-items: center; gap: 8px;">
    <img src="{{ asset('logo_proxima.png') }}" alt="ProximaLumine" style="height:44px;vertical-align:middle;">
    <span>Powered by ProximaLumine</span>
</div>
</body>
<script>
function updateDateTime() {
    const now = new Date();
    const formatted = now.toLocaleString('pl-PL', {
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', second: '2-digit'
    });
    document.getElementById('datetime').textContent = formatted;
}
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</html>
