<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn</title>
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
                    $companyName = $companySettings && $companySettings->name ? $companySettings->name : '3C Automation';
                } catch (\Exception $e) {
                    $logoPath = '/logo.png';
                    $companyName = '3C Automation';
                }
            @endphp
            <!-- LOGO -->
            <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
            <span class="text-xl font-bold">Magazyn {{ $companyName }}</span>
        </div>

        <!-- MENU -->
        @auth
        <nav class="flex gap-2 items-center flex-wrap justify-end">
            <a href="{{ route('magazyn.check') }}"
               class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded whitespace-nowrap">
                Wejdź do magazynu
            </a>
            
            <div class="border-l border-gray-300 pl-2 flex items-center gap-2">
                <span class="text-gray-700 text-sm whitespace-nowrap">{{ Auth::user()->name }}</span>
                <form action="{{ route('logout') }}" method="POST" class="inline">
                    @csrf
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
        <a href="{{ route('magazyn.check') }}"
           class="inline-block px-6 py-3 bg-blue-600 text-white rounded text-lg hover:bg-blue-700">
            Wejdź do magazynu
        </a>
    @else
        <a href="{{ route('login') }}"
           class="inline-block px-6 py-3 bg-green-600 text-white rounded text-lg hover:bg-green-700">
            Zaloguj się
        </a>
    @endauth
</main>

</body>
</html>
