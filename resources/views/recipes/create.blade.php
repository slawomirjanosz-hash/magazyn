<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Nowa Receptura</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100">

<header class="bg-white shadow">
    <div class="max-w-7xl mx-auto px-6 py-4 flex items-center justify-between">
        <div class="flex items-center gap-4">
            <a href="{{ route('recipes.index') }}" class="text-2xl font-bold text-blue-600">← Nowa Receptura</a>
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

<main class="max-w-4xl mx-auto mt-8 px-6 pb-12">
    @if(session('error'))
        <div class="mb-6 p-4 bg-red-100 border border-red-400 text-red-700 rounded">{{ session('error') }}</div>
    @endif

    <h1 class="text-3xl font-bold mb-6">Utwórz Nową Recepturę</h1>

    <form method="POST" action="{{ route('recipes.store') }}" class="bg-white rounded-lg shadow p-6">
        @csrf
        
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Nazwa receptury *</label>
            <input type="text" name="name" required class="w-full px-4 py-2 border rounded">
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Opis</label>
            <textarea name="description" rows="3" class="w-full px-4 py-2 border rounded"></textarea>
        </div>
        
        <div class="mb-6">
            <label class="block text-sm font-medium mb-2">Ilość sztuk z receptury *</label>
            <input type="number" name="output_quantity" min="1" value="1" required class="w-full px-4 py-2 border rounded">
            <small class="text-gray-500">Ile sztuk produktu finalnego wychodzi z tej receptury (np. 100 sztuk chleba)</small>
        </div>
        
        <hr class="my-6">
        
        <!-- Sekcja Mąki -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">🌾 Mąka (Razem = 100%)</h2>
                <button type="button" onclick="addFlourRow()" class="px-4 py-2 bg-amber-600 text-white rounded hover:bg-amber-700">
                    ➕ Dodaj Mąkę
                </button>
            </div>
            
            <div class="bg-amber-50 border-2 border-amber-300 rounded-lg p-4">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-amber-300">
                            <th class="text-left py-2 px-2">Składnik</th>
                            <th class="text-left py-2 px-2">Waga (kg)</th>
                            <th class="text-left py-2 px-2">Procent (%)</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="flourTable">
                        <!-- Wiersze mąki dodawane dynamicznie -->
                    </tbody>
                    <tfoot>
                        <tr class="border-t-2 border-amber-400 font-bold">
                            <td class="py-2 px-2">SUMA MĄKI</td>
                            <td class="py-2 px-2"><span id="flourTotalWeight">0</span> kg</td>
                            <td class="py-2 px-2"><span id="flourTotalPercent">0</span>%</td>
                            <td></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
        
        <!-- Sekcja Pozostałych Składników -->
        <div class="mb-8">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-xl font-bold">📦 Pozostałe Składniki</h2>
                <button type="button" onclick="addIngredientRow()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                    ➕ Dodaj Składnik
                </button>
            </div>
            
            <div class="bg-green-50 border-2 border-green-300 rounded-lg p-4">
                <table class="w-full">
                    <thead>
                        <tr class="border-b border-green-300">
                            <th class="text-left py-2 px-2">Składnik</th>
                            <th class="text-left py-2 px-2">Ilość</th>
                            <th class="text-left py-2 px-2">Procent (% od mąki)</th>
                            <th class="w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="ingredientTable">
                        <!-- Wiersze składników dodawane dynamicznie -->
                    </tbody>
                </table>
                <p class="text-sm text-gray-600 mt-2">💡 Procent odnosi się do całkowitej wagi mąki</p>
            </div>
        </div>
        
        <div id="stepsContainer" class="hidden">
            <!-- Ukryte pole do przekazania danych -->
        </div>
        
        <button type="submit" class="w-full px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 font-bold">
            💾 Zapisz Recepturę
        </button>
    </form>
</main>

<script>
let flourCounter = 0;
let ingredientCounter = 0;
const ingredients = @json($ingredients);

// Dodaj mąkę
function addFlourRow() {
    flourCounter++;
    let ingredientOptions = '<option value="">-- Wybierz mąkę --</option>';
    ingredients.forEach(ing => {
        ingredientOptions += `<option value="${ing.id}" data-unit="${ing.unit}">${ing.name} (${ing.quantity} ${ing.unit})</option>`;
    });
    
    const html = `
        <tr id="flour-${flourCounter}">
            <td class="py-2 px-2">
                <select name="flour[${flourCounter}][ingredient_id]" required onchange="updateFlourUnit(${flourCounter})" class="w-full px-3 py-2 border rounded flour-select">
                    ${ingredientOptions}
                </select>
            </td>
            <td class="py-2 px-2">
                <input type="number" name="flour[${flourCounter}][weight]" step="0.01" min="0.01" required 
                       onchange="updateFlourPercentage(${flourCounter})" 
                       class="w-full px-3 py-2 border rounded flour-weight" id="flour-weight-${flourCounter}">
            </td>
            <td class="py-2 px-2">
                <input type="number" name="flour[${flourCounter}][percentage]" step="0.01" min="0.01" max="100" required 
                       onchange="updateFlourWeight(${flourCounter})" 
                       class="w-full px-3 py-2 border rounded flour-percentage" id="flour-percent-${flourCounter}">
            </td>
            <td class="py-2 px-2 text-center">
                <button type="button" onclick="removeFlourRow(${flourCounter})" class="text-red-600 hover:text-red-800">🗑️</button>
            </td>
        </tr>
    `;
    document.getElementById('flourTable').insertAdjacentHTML('beforeend', html);
}

// Usuń mąkę
function removeFlourRow(id) {
    document.getElementById(`flour-${id}`).remove();
    calculateFlourTotals();
}

// Aktualizuj procent mąki na podstawie wagi
function updateFlourPercentage(id) {
    const totalWeight = getTotalFlourWeight();
    if (totalWeight > 0) {
        const weight = parseFloat(document.getElementById(`flour-weight-${id}`).value) || 0;
        const percentage = (weight / totalWeight * 100).toFixed(2);
        document.getElementById(`flour-percent-${id}`).value = percentage;
    }
    calculateFlourTotals();
}

// Aktualizuj wagę mąki na podstawie procentu
function updateFlourWeight(id) {
    const totalWeight = getTotalFlourWeight();
    if (totalWeight > 0) {
        const percentage = parseFloat(document.getElementById(`flour-percent-${id}`).value) || 0;
        const weight = (totalWeight * percentage / 100).toFixed(2);
        document.getElementById(`flour-weight-${id}`).value = weight;
    }
    calculateFlourTotals();
}

// Pobierz całkowitą wagę mąki
function getTotalFlourWeight() {
    let total = 0;
    document.querySelectorAll('.flour-weight').forEach(input => {
        total += parseFloat(input.value) || 0;
    });
    return total;
}

// Oblicz sumy mąki
function calculateFlourTotals() {
    const totalWeight = getTotalFlourWeight();
    let totalPercent = 0;
    
    document.querySelectorAll('.flour-percentage').forEach(input => {
        totalPercent += parseFloat(input.value) || 0;
    });
    
    document.getElementById('flourTotalWeight').textContent = totalWeight.toFixed(2);
    document.getElementById('flourTotalPercent').textContent = totalPercent.toFixed(2);
    
    // Ostrzeżenie jeśli procent != 100%
    const percentDisplay = document.getElementById('flourTotalPercent');
    if (Math.abs(totalPercent - 100) > 0.01 && totalPercent > 0) {
        percentDisplay.classList.add('text-red-600');
    } else {
        percentDisplay.classList.remove('text-red-600');
    }
    
    // Przelicz składniki
    recalculateIngredients();
}

// Dodaj składnik
function addIngredientRow() {
    ingredientCounter++;
    let ingredientOptions = '<option value="">-- Wybierz składnik --</option>';
    ingredients.forEach(ing => {
        ingredientOptions += `<option value="${ing.id}" data-unit="${ing.unit}">${ing.name} (${ing.quantity} ${ing.unit})</option>`;
    });
    
    const html = `
        <tr id="ingredient-${ingredientCounter}">
            <td class="py-2 px-2">
                <select name="ingredient[${ingredientCounter}][ingredient_id]" required onchange="updateIngredientUnit(${ingredientCounter})" class="w-full px-3 py-2 border rounded ingredient-select">
                    ${ingredientOptions}
                </select>
            </td>
            <td class="py-2 px-2">
                <input type="number" name="ingredient[${ingredientCounter}][quantity]" step="0.01" min="0.01" required 
                       onchange="updateIngredientPercentage(${ingredientCounter})" 
                       class="w-full px-3 py-2 border rounded ingredient-quantity" id="ingredient-quantity-${ingredientCounter}">
                <span class="text-xs text-gray-500" id="ingredient-unit-${ingredientCounter}"></span>
            </td>
            <td class="py-2 px-2">
                <input type="number" name="ingredient[${ingredientCounter}][percentage]" step="0.01" min="0.01" required 
                       onchange="updateIngredientQuantity(${ingredientCounter})" 
                       class="w-full px-3 py-2 border rounded ingredient-percentage" id="ingredient-percent-${ingredientCounter}">
            </td>
            <td class="py-2 px-2 text-center">
                <button type="button" onclick="removeIngredientRow(${ingredientCounter})" class="text-red-600 hover:text-red-800">🗑️</button>
            </td>
        </tr>
    `;
    document.getElementById('ingredientTable').insertAdjacentHTML('beforeend', html);
}

// Usuń składnik
function removeIngredientRow(id) {
    document.getElementById(`ingredient-${id}`).remove();
}

// Aktualizuj jednostkę składnika
function updateIngredientUnit(id) {
    const select = document.querySelector(`#ingredient-${id} .ingredient-select`);
    const unit = select.options[select.selectedIndex]?.dataset?.unit || '';
    document.getElementById(`ingredient-unit-${id}`).textContent = unit;
}

// Aktualizuj procent składnika na podstawie ilości
function updateIngredientPercentage(id) {
    const flourTotal = getTotalFlourWeight();
    if (flourTotal > 0) {
        const quantity = parseFloat(document.getElementById(`ingredient-quantity-${id}`).value) || 0;
        const percentage = (quantity / flourTotal * 100).toFixed(2);
        document.getElementById(`ingredient-percent-${id}`).value = percentage;
    }
}

// Aktualizuj ilość składnika na podstawie procentu
function updateIngredientQuantity(id) {
    const flourTotal = getTotalFlourWeight();
    if (flourTotal > 0) {
        const percentage = parseFloat(document.getElementById(`ingredient-percent-${id}`).value) || 0;
        const quantity = (flourTotal * percentage / 100).toFixed(2);
        document.getElementById(`ingredient-quantity-${id}`).value = quantity;
    }
}

// Przelicz wszystkie składniki przy zmianie wagi mąki
function recalculateIngredients() {
    const flourTotal = getTotalFlourWeight();
    if (flourTotal > 0) {
        document.querySelectorAll('[id^="ingredient-percent-"]').forEach(input => {
            const id = input.id.replace('ingredient-percent-', '');
            updateIngredientQuantity(id);
        });
    }
}

// Walidacja przed wysłaniem
document.querySelector('form').addEventListener('submit', function(e) {
    const flourTotal = parseFloat(document.getElementById('flourTotalPercent').textContent);
    
    if (Math.abs(flourTotal - 100) > 0.01) {
        e.preventDefault();
        alert('BŁĄD: Suma procentów mąki musi wynosić 100%! Aktualnie: ' + flourTotal + '%');
        return false;
    }
    
    const flourRows = document.querySelectorAll('[id^="flour-"]').length;
    if (flourRows === 0) {
        e.preventDefault();
        alert('Musisz dodać przynajmniej jeden rodzaj mąki!');
        return false;
    }
});
</script>

</body>
</html>
