<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Receptury - System Zarządzania</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<!-- GÓRNY PASEK -->
<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ url('/') }}" class="text-2xl font-bold text-blue-600">← System Receptur</a>
        </div>
        <nav class="flex gap-2 items-center">
            @if(auth()->check() && (auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings))
                <a href="{{ route('magazyn.settings') }}"
                   class="px-3 py-2 text-sm bg-gray-200 text-black rounded whitespace-nowrap">
                    ⚙️Ustawienia
                </a>
            @endif
            <span class="text-gray-700 text-sm">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3 py-2 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded">
                    Wyloguj
                </button>
            </form>
        </nav>
    </div>
</header>

<!-- GŁÓWNA TREŚĆ -->
<main class="max-w-7xl mx-auto mt-8 px-6">
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">
            {{ session('success') }}
        </div>
    @endif

    <h1 class="text-4xl font-bold mb-8">System Zarządzania Recepturami</h1>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Katalog składników -->
        <a href="{{ route('recipes.ingredients') }}" class="block p-8 bg-white rounded-lg shadow hover:shadow-lg transition">
            <div class="text-5xl mb-4">📦</div>
            <h2 class="text-2xl font-bold mb-2">Katalog Składników</h2>
            <p class="text-gray-600">Zarządzaj składnikami dostępnymi do receptur</p>
        </a>

        <!-- Lista receptur -->
        <a href="{{ route('recipes.index') }}" class="block p-8 bg-white rounded-lg shadow hover:shadow-lg transition">
            <div class="text-5xl mb-4">📋</div>
            <h2 class="text-2xl font-bold mb-2">Lista Receptur</h2>
            <p class="text-gray-600">Przeglądaj i zarządzaj recepturami</p>
        </a>

        <!-- Nowa receptura -->
        <a href="{{ route('recipes.create') }}" class="block p-8 bg-white rounded-lg shadow hover:shadow-lg transition">
            <div class="text-5xl mb-4">➕</div>
            <h2 class="text-2xl font-bold mb-2">Nowa Receptura</h2>
            <p class="text-gray-600">Stwórz nową recepturę</p>
        </a>
    </div>
</main>

</body>
</html>
