<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Wyceny i Oferty</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                @php
                    try {
                        $companySettings = \App\Models\CompanySetting::first();
                        if ($companySettings && $companySettings->logo) {
                            if (str_starts_with($companySettings->logo, 'data:image')) {
                                $logoPath = $companySettings->logo;
                            } else {
                                $logoPath = asset('storage/' . $companySettings->logo);
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
                <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
                <span class="text-xl font-bold">{{ $companyName }}</span>
            </div>
        </div>
    </header>
    <main class="flex-1">
        <div class="max-w-3xl mx-auto mt-12 p-6 bg-white rounded shadow text-center relative">
            <a href="/" class="absolute top-4 left-4 flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 shadow rounded-full text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition z-10">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
                Powrót
            </a>
            <h1 class="text-3xl font-bold mb-8">Wyceny i Oferty</h1>
            <div class="flex flex-col sm:flex-row flex-wrap gap-4 justify-center items-center">
                <a href="{{ route('offers.portfolio') }}" class="inline-block px-6 py-2 bg-blue-600 text-white rounded text-base hover:bg-blue-700 min-w-[180px]">Portfolio</a>
                <a href="{{ route('offers.new') }}" class="inline-block px-6 py-2 bg-green-600 text-white rounded text-base hover:bg-green-700 min-w-[180px]">Zrób nową Ofertę</a>
                <a href="{{ route('offers.inprogress') }}" class="inline-block px-6 py-2 bg-yellow-600 text-white rounded text-base hover:bg-yellow-700 min-w-[180px]">Oferty w toku</a>
                <a href="{{ route('offers.archived') }}" class="inline-block px-6 py-2 bg-gray-500 text-white rounded text-base hover:bg-gray-600 min-w-[180px]">Oferty zarchiwizowane</a>
            </div>
        </div>
    </main>
    <footer class="bg-white text-center py-4 mt-8 border-t text-gray-400 text-sm">
        Powered by ProximaLumine
    </footer>
</body>
</html>
