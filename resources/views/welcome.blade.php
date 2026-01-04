<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn 3C Automation</title>
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
                    $logoPath = $companySettings && $companySettings->logo ? asset('storage/' . $companySettings->logo) : '/logo.png';
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

    <a href="{{ route('login') }}"
       class="inline-block px-6 py-3 bg-green-600 text-white rounded text-lg hover:bg-green-700">
        Zaloguj się
    </a>
</main>

</body>
</html>
