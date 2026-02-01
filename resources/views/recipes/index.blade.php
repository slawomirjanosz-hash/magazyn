<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Lista Receptur</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('receptury') }}" class="text-2xl font-bold text-blue-600">← Lista Receptur</a>
        </div>
        <nav class="flex gap-2 items-center">
            <span class="text-gray-700 text-sm">{{ Auth::user()->name }}</span>
            <form action="{{ route('logout') }}" method="POST" class="inline">
                @csrf
                <button type="submit" class="px-3 py-2 text-sm bg-gray-600 hover:bg-gray-700 text-white rounded">Wyloguj</button>
            </form>
        </nav>
    </div>
</header>

<main class="max-w-7xl mx-auto mt-8 px-6">
    @if(session('success'))
        <div class="mb-6 p-4 bg-green-100 border border-green-400 text-green-700 rounded">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    <div class="flex justify-between items-center mb-6">
        <h1 class="text-3xl font-bold">Lista Receptur</h1>
        <a href="{{ route('recipes.create') }}" class="px-6 py-3 bg-green-600 text-white rounded hover:bg-green-700">
            ➕ Nowa Receptura
        </a>
    </div>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        @forelse($recipes as $recipe)
            <div class="bg-white rounded-lg shadow-md p-6 hover:shadow-lg transition">
                <h3 class="text-xl font-bold mb-2">{{ $recipe->name }}</h3>
                <p class="text-gray-600 mb-4">{{ Str::limit($recipe->description, 100) }}</p>
                
                <div class="mb-4 text-sm">
                    <div class="text-gray-500">🔢 Liczba kroków: {{ $recipe->steps_count }}</div>
                    <div class="text-gray-500">⏱️ Szacowany czas: {{ $recipe->estimated_time ?? 0 }} min</div>
                    <div class="text-gray-500">📦 Ilość sztuk: {{ $recipe->output_quantity }}</div>
                    
                    @php
                        $totalCost = $recipe->total_cost;
                        $costPerUnit = $recipe->cost_per_unit;
                        $totalUnits = $recipe->total_ingredients_by_unit;
                        $unitWeight = $recipe->unit_weight_by_unit;
                    @endphp
                    <div class="mt-3 pt-3 border-t">
                        <div class="font-bold text-purple-600">💰 Koszt całkowity: {{ number_format($totalCost, 2) }} zł</div>
                        <div class="font-bold text-green-600">💵 Koszt za 1 szt: {{ number_format($costPerUnit, 2) }} zł</div>
                        @if($totalUnits && count($totalUnits))
                            <div class="font-bold text-blue-700 mt-2">
                                🧪 Całkowita ilość składników:
                                @foreach($totalUnits as $unit => $qty)
                                    <span class="inline-block mr-2">{{ rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',') }} {{ $unit }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($unitWeight && count($unitWeight))
                            <div class="font-bold text-indigo-700 mt-1">
                                ⚖️ Waga/jednostka na 1 sztukę:
                                @foreach($unitWeight as $unit => $qty)
                                    <span class="inline-block mr-2">{{ rtrim(rtrim(number_format($qty, 2, ',', ''), '0'), ',') }} {{ $unit }}</span>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
                
                <div class="flex gap-2">
                    <a href="{{ route('recipes.edit', $recipe) }}" class="flex-1 text-center px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        ✏️ Edytuj
                    </a>
                    <a href="{{ route('recipes.scale', $recipe) }}" class="flex-1 text-center px-4 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                        ⚖️ Skaluj
                    </a>
                </div>
                
                <form action="{{ route('recipes.start', $recipe) }}" method="POST" class="mt-2">
                    @csrf
                    <button type="submit" class="w-full px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        ▶️ Rozpocznij
                    </button>
                </form>
                
                <form action="{{ route('recipes.destroy', $recipe) }}" method="POST" class="mt-2" onsubmit="return confirm('Czy na pewno usunąć tę recepturę?')">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="w-full px-4 py-2 bg-red-600 text-white rounded hover:bg-red-700">
                        🗑️ Usuń
                    </button>
                </form>
            </div>
        @empty
            <div class="col-span-full text-center py-12 text-gray-500">
                <p class="text-xl mb-4">Brak receptur</p>
                <a href="{{ route('recipes.create') }}" class="inline-block px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                    Utwórz pierwszą recepturę
                </a>
            </div>
        @endforelse
    </div>
</main>

</body>
</html>
