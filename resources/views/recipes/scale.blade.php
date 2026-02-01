<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Skalowanie Receptury - {{ $recipe->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('recipes.index') }}" class="text-2xl font-bold text-purple-600">← Skalowanie Receptury</a>
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

<main class="max-w-5xl mx-auto mt-8 px-6 pb-12">
    <h1 class="text-3xl font-bold mb-6">⚖️ Skalowanie Receptury: {{ $recipe->name }}</h1>

    <div class="bg-white rounded-lg shadow p-6 mb-6">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <p class="text-sm text-gray-600">Oryginalna ilość sztuk:</p>
                <p class="text-2xl font-bold text-blue-600">{{ $recipe->output_quantity }} szt.</p>
            </div>
            @if(isset($desiredQuantity))
            <div>
                <p class="text-sm text-gray-600">Pożądana ilość sztuk:</p>
                <p class="text-2xl font-bold text-green-600">{{ $desiredQuantity }} szt.</p>
            </div>
            @endif
        </div>

        @if(isset($scaleFactor))
        <div class="bg-purple-100 border border-purple-300 rounded p-4 mb-4">
            <p class="text-sm text-purple-800">Współczynnik skalowania: <span class="font-bold text-xl">{{ number_format($scaleFactor, 2) }}x</span></p>
        </div>
        @endif

        <form method="POST" action="{{ route('recipes.processScale', $recipe) }}" class="flex gap-2">
            @csrf
            <input type="number" name="desired_quantity" min="1" value="{{ $desiredQuantity ?? '' }}" 
                   placeholder="Podaj ilość sztuk" required 
                   class="flex-1 px-4 py-2 border rounded">
            <button type="submit" class="px-6 py-2 bg-purple-600 text-white rounded hover:bg-purple-700">
                🔄 Przelicz
            </button>
        </form>
    </div>

    @if(isset($scaledSteps))
    <div class="bg-white rounded-lg shadow p-6">
        <h2 class="text-2xl font-bold mb-4">Przeskalowane Składniki</h2>
        
        <!-- Sekcja Mąki -->
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-3 text-amber-700">🌾 Mąka</h3>
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-amber-300 bg-amber-50">
                        <th class="text-left py-3 px-4">Składnik</th>
                        <th class="text-right py-3 px-4">Oryginalna ilość</th>
                        <th class="text-right py-3 px-4">Procent</th>
                        <th class="text-right py-3 px-4 font-bold text-green-700">Przeskalowana ilość</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $flourSteps = collect($scaledSteps)->where('is_flour', true);
                        $flourTotalOriginal = $flourSteps->sum('quantity');
                        $flourTotalScaled = $flourSteps->sum('scaled_quantity');
                    @endphp
                    @foreach($flourSteps as $step)
                    <tr class="border-b border-gray-200 hover:bg-amber-50">
                        <td class="py-2 px-4">{{ $step->ingredient->name ?? 'N/A' }}</td>
                        <td class="text-right py-2 px-4">{{ number_format($step->quantity, 2) }} {{ $step->ingredient->unit ?? 'kg' }}</td>
                        <td class="text-right py-2 px-4">{{ number_format($step->percentage, 1) }}%</td>
                        <td class="text-right py-2 px-4 font-bold text-green-700">{{ number_format($step->scaled_quantity, 2) }} {{ $step->ingredient->unit ?? 'kg' }}</td>
                    </tr>
                    @endforeach
                    <tr class="bg-amber-100 font-bold">
                        <td class="py-3 px-4">SUMA MĄKI</td>
                        <td class="text-right py-3 px-4">{{ number_format($flourTotalOriginal, 2) }} kg</td>
                        <td class="text-right py-3 px-4">100%</td>
                        <td class="text-right py-3 px-4 text-green-700">{{ number_format($flourTotalScaled, 2) }} kg</td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sekcja Pozostałych Składników -->
        @php
            $otherSteps = collect($scaledSteps)->where('is_flour', false);
        @endphp
        @if($otherSteps->count() > 0)
        <div class="mb-8">
            <h3 class="text-xl font-semibold mb-3 text-green-700">📦 Pozostałe Składniki</h3>
            <table class="w-full">
                <thead>
                    <tr class="border-b-2 border-green-300 bg-green-50">
                        <th class="text-left py-3 px-4">Składnik</th>
                        <th class="text-right py-3 px-4">Oryginalna ilość</th>
                        <th class="text-right py-3 px-4">Procent (od mąki)</th>
                        <th class="text-right py-3 px-4 font-bold text-green-700">Przeskalowana ilość</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($otherSteps as $step)
                    <tr class="border-b border-gray-200 hover:bg-green-50">
                        <td class="py-2 px-4">{{ $step->ingredient->name ?? 'N/A' }}</td>
                        <td class="text-right py-2 px-4">{{ number_format($step->quantity, 2) }} {{ $step->ingredient->unit ?? '' }}</td>
                        <td class="text-right py-2 px-4">{{ number_format($step->percentage, 1) }}%</td>
                        <td class="text-right py-2 px-4 font-bold text-green-700">{{ number_format($step->scaled_quantity, 2) }} {{ $step->ingredient->unit ?? '' }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        @endif

        <!-- Podsumowanie -->
        <div class="bg-blue-50 border-2 border-blue-300 rounded-lg p-6 mt-6">
            <h3 class="text-xl font-bold mb-4 text-blue-800">📊 Podsumowanie</h3>
            <div class="grid grid-cols-2 gap-6">
                <div>
                    <p class="text-sm text-gray-600">Koszt oryginalny ({{ $recipe->output_quantity }} szt.):</p>
                    <p class="text-2xl font-bold text-blue-600">{{ number_format($recipe->total_cost, 2) }} zł</p>
                    <p class="text-sm text-gray-500">{{ number_format($recipe->cost_per_unit, 2) }} zł/szt.</p>
                </div>
                <div>
                    <p class="text-sm text-gray-600">Koszt przeskalowany ({{ $desiredQuantity }} szt.):</p>
                    <p class="text-2xl font-bold text-green-600">{{ number_format($recipe->total_cost * $scaleFactor, 2) }} zł</p>
                    <p class="text-sm text-gray-500">{{ number_format($recipe->cost_per_unit, 2) }} zł/szt.</p>
                </div>
            </div>
        </div>

        <!-- Przyciski akcji -->
        <div class="flex gap-4 mt-6">
            <a href="{{ route('recipes.index') }}" class="flex-1 text-center px-6 py-3 bg-gray-600 text-white rounded hover:bg-gray-700">
                ← Powrót
            </a>
            <button onclick="window.print()" class="flex-1 px-6 py-3 bg-blue-600 text-white rounded hover:bg-blue-700">
                🖨️ Drukuj
            </button>
        </div>
    </div>
    @endif
</main>

<style>
@media print {
    header, button, a { display: none !important; }
    body { background: white; }
    .bg-white { box-shadow: none !important; }
}
</style>

</body>
</html>
