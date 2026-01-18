<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Magazyn – Dodaj</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

{{-- MENU --}}
@include('parts.menu')

<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow mt-6">
    <h2 class="text-xl font-bold mb-4">Dodaj Produkt</h2>

    {{-- SEKCJA: FORMULARZ (ROZWIJALNA) --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="form-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Dodaj Produkt <span class="text-sm font-normal text-gray-600">(wpisz ręcznie)</span></h3>
        </button>
        <div id="form-content" class="collapsible-content hidden p-6 border-t">
            {{-- FORMULARZ --}}
            <form id="add-form" method="POST" action="{{ route('parts.add') }}" class="space-y-3 mb-6">
                @csrf
                {{-- NAZWA --}}
                <div>
                    <label class="block text-sm font-semibold mb-1">Nazwa produktu *</label>
                    <input
                        id="part-name"
                        name="name"
                        placeholder="Nazwa produktu"
                        class="border p-2 rounded w-full"
                        required
                    >
                </div>
                {{-- OPIS --}}
                <div>
                    <label class="block text-sm font-semibold mb-1">Opis</label>
                    <input
                        id="part-description"
                        name="description"
                        placeholder="Opis (opcjonalnie)"
                        class="border p-2 rounded w-full"
                    >
                </div>
                {{-- ILOŚĆ, STAN MIN., LOKALIZACJA --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1">Ilość *</label>
                        <input
                            name="quantity"
                            type="number"
                            min="1"
                            value="1"
                            class="border p-2 rounded w-full"
                            required
                            placeholder="Ilość"
                        >
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1">Stan min.</label>
                        <input
                            name="minimum_stock"
                            type="number"
                            min="0"
                            value="0"
                            class="border p-2 rounded w-full"
                            placeholder="Stan min."
                            title="Stan minimalny"
                        >
                    </div>
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1">Lokalizacja</label>
                        <input
                            name="location"
                            type="text"
                            maxlength="10"
                            class="border p-2 rounded w-full"
                            placeholder="np. A1, B2"
                        >
                    </div>
                </div>
                {{-- CENA I WALUTA --}}
                <div class="flex gap-2">
                    <div class="flex-1">
                        <label class="block text-sm font-semibold mb-1">Cena netto</label>
                        <input
                            id="part-net-price"
                            name="net_price"
                            type="number"
                            step="0.01"
                            min="0"
                            placeholder="Cena netto"
                            class="border p-2 rounded w-full"
                        >
                    </div>
                    <div style="width: 100px;">
                        <label class="block text-sm font-semibold mb-1">Waluta</label>
                        <select
                            id="part-currency"
                            name="currency"
                            class="border p-2 rounded text-sm w-full"
                        >
                            <option value="PLN">PLN</option>
                            <option value="EUR">EUR</option>
                            <option value="$">$</option>
                        </select>
                    </div>
                </div>
                {{-- DOSTAWCA, KATEGORIA --}}
                <div class="flex gap-2">
                    <div style="width: 200px;">
                        <label class="block text-sm font-semibold mb-1">Dostawca</label>
                        <select
                            id="part-supplier"
                            name="supplier"
                            class="border p-2 rounded text-sm w-full"
                        >
                            <option value="">- wybierz -</option>
                            @foreach($suppliers as $s)
                                <option value="{{ $s->name }}">{{ $s->short_name ?? $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div style="width: 200px;">
                        <label class="block text-sm font-semibold mb-1">Kategoria *</label>
                        <select
                            name="category_id"
                            class="border p-2 rounded w-full"
                            required
                        >
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">
                                    {{ $c->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>
                {{-- PRZYCISK --}}
                <div class="flex gap-2 items-center">
                    <button
                        type="submit"
                        class="bg-green-500 hover:bg-green-600 text-white rounded px-4 py-2 mt-2"
                    >
                        ➕ Dodaj
                    </button>
                    <button
                        type="button"
                        id="generate-qr-btn"
                        class="bg-blue-500 hover:bg-blue-600 text-white rounded px-4 py-2 mt-2"
                    >
                        📱 Podgląd QR
                    </button>
                    <button
                        type="button"
                        id="scan-qr-btn"
                        style="background: #7e22ce; color: #fff; border: none; font-weight: normal; padding: 0.5rem 1rem; border-radius: 0.375rem; margin-top: 0.5rem; cursor: pointer;"
                        onmouseover="this.style.background='#6b21a8'"
                        onmouseout="this.style.background='#7e22ce'"
                    >
                        🔍 Odczytaj QR
                    </button>
                    <p class="text-xs text-gray-500 mt-2">💡 Kod QR zostanie automatycznie wygenerowany po dodaniu produktu</p>
                </div>
                
                {{-- KOMUNIKAT SKANOWANIA W FORMULARZU --}}
                <div id="form-scan-message" class="hidden mt-4 p-3 rounded"></div>
                
                {{-- POLE DO SKANOWANIA KODÓW QR W FORMULARZU --}}
                <div id="form-qr-scanner-section" class="mt-4 p-4 bg-purple-50 border-2 border-purple-400 rounded hidden">
                    <h4 class="font-bold text-purple-700 mb-3">📷 Zeskanuj kod QR ze skanera USB</h4>
                    <p class="text-sm text-purple-600 mb-2">Zeskanuj kod QR skanerem (automatyczne wczytanie po Enter)</p>
                    <input
                        type="text"
                        id="form-qr-scanner-input"
                        placeholder="Zeskanuj kod QR..."
                        class="border-2 border-purple-400 p-3 rounded w-full focus:border-purple-600 focus:ring-2 focus:ring-purple-300 text-lg mb-2"
                    >
                    <button
                        type="button"
                        id="form-cancel-scan-btn"
                        class="bg-gray-400 hover:bg-gray-500 text-white rounded px-4 py-2 text-sm"
                    >
                        ✖ Anuluj
                    </button>
                </div>
                
                <div class="text-xs text-gray-500 mt-2 text-left">
                    Dodaje: {{ Auth::user()->name ?? 'Gość' }}
                </div>
                
                {{-- SEKCJA: KOD QR --}}
                <div id="qr-code-section" class="mt-4 p-4 bg-blue-50 border border-blue-200 rounded hidden">
                    <h4 class="font-semibold text-sm mb-2">Wygenerowany kod QR:</h4>
                    <div class="flex gap-4 items-start">
                        <div id="qr-code-image" class="bg-white p-2 border rounded"></div>
                        <div class="flex-1">
                            <p class="text-sm mb-2"><strong>Kod:</strong> <span id="qr-code-text" class="font-mono text-blue-600"></span></p>
                            <p class="text-xs text-gray-600"><strong>Zawiera:</strong> <span id="qr-code-description"></span></p>
                            <input type="hidden" name="qr_code" id="qr-code-hidden">
                        </div>
                    </div>
                </div>
            </form>

            {{-- PODGLĄD STANU --}}
            <div class="mb-4 text-sm text-gray-600">
                Aktualny stan: <span id="current-quantity" class="font-bold">0</span>
            </div>

            {{-- OSTRZEŻENIE O PODOBNYCH PRODUKTACH --}}
            <div id="similar-warning" class="mb-4 p-3 bg-yellow-100 border border-yellow-400 rounded hidden">
                <p class="text-yellow-800 font-semibold mb-2">⚠️ Znaleziono podobne produkty:</p>
                <ul id="similar-list" class="text-sm text-yellow-700 space-y-1"></ul>
                <p class="text-yellow-700 text-xs mt-2 italic">Czy na pewno chcesz dodać nowy produkt?</p>
            </div>
        </div>
    </div>

    {{-- SEKCJA: DODAJ Z KATALOGU PRODUKTÓW (ROZWIJALNA) --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="catalog-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Dodaj z Katalogu Produktów</h3>
        </button>
        <div id="catalog-content" class="collapsible-content hidden p-6 border-t">
            {{-- PODSEKCJA: PRODUKTY DO DODANIA (COLLAPSIBLE) --}}
            <div class="mb-6 pb-6 border-b">
                <button type="button" id="selected-products-btn" class="collapsible-btn w-full flex items-center gap-2 px-0 py-2 cursor-pointer hover:bg-gray-50" data-target="selected-products-inner">
                    <span class="toggle-arrow text-xs">▶</span>
                    <h4 class="font-semibold text-xs">Produkty do dodania</h4>
                </button>
                <div id="selected-products-inner" class="collapsible-content hidden mt-4 p-4 bg-gray-50 rounded border border-gray-300">
                    <table id="selected-products-table-inner" class="w-full border border-collapse text-xs mb-4">
                        <thead class="bg-green-100">
                            <tr>
                                <th class="border p-1 text-center" style="width: 30px;">
                                    <input type="checkbox" id="select-all-add-products" class="w-4 h-4 cursor-pointer" title="Zaznacz wszystkie">
                                </th>
                                <th class="border p-1 text-left" style="white-space: nowrap;">Produkt</th>
                                <th class="border p-1 text-left" style="width: 60px;">Dost.</th>
                                <th class="border p-1 text-center" style="width: 100px;">Cena netto</th>
                                <th class="border p-1 text-left" style="width: 120px;">Kategoria</th>
                                <th class="border p-1 text-center" style="width: 45px;">Stan</th>
                                <th class="border p-1 text-center" style="width: 50px;">Il. do dod.</th>
                                <th class="border p-1 text-center" style="width: 60px;">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <button type="button" id="remove-all-selected-btn-inner" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs mr-2">🗑️ Wyczyść listę</button>
                    <button type="button" id="add-all-btn-inner" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs mr-2">✅ Dodaj wszystkie</button>
                    <span id="add-all-loading-info" class="ml-2 text-xs text-blue-600 font-semibold" style="display:none;">Ładuje produkty...</span>
                    <button type="button" id="import-excel-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">📄 Załaduj z Excela</button>
                </div>
            </div>

            {{-- MODAL IMPORTU Z EXCELA --}}
            <div id="excel-import-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden items-center justify-center z-50" style="display: none;">
                <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
                    <h3 class="text-lg font-bold mb-4">📄 Import produktów z Excel</h3>
                    <p class="text-sm text-gray-600 mb-2">
                        Wybierz plik Excel (.xlsx, .xls) z kolumnami:<br>
                        <strong>produkty, opis, dost., cena, waluta, kategoria, ilość, lok.</strong>
                    </p>
                    <p class="text-xs text-gray-500 mb-4">
                        💡 Kod QR zostanie automatycznie wygenerowany dla każdego importowanego produktu
                    </p>
                    <form id="excel-import-form" enctype="multipart/form-data">
                        @csrf
                        <input type="file" id="excel-file-input" name="excel_file" accept=".xlsx,.xls" class="w-full border rounded p-2 mb-4" required>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded flex-1">
                                Importuj
                            </button>
                            <button type="button" id="close-excel-modal" class="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded flex-1">
                                Anuluj
                            </button>
                        </div>
                    </form>
                    <div id="import-progress" class="mt-4 hidden">
                        <div class="bg-blue-100 rounded p-3 text-sm text-blue-700">
                            ⏳ Importowanie produktów...
                        </div>
                    </div>
                </div>
            </div>

            {{-- PRZYCISK SZUKAJ W BAZIE --}}
            <div class="mb-4">
                <button
                    type="button"
                    id="catalog-scan-qr-btn"
                    style="background: #7e22ce; color: #fff; border: none; font-weight: normal; padding: 0.5rem 1rem; border-radius: 0.375rem; cursor: pointer;"
                    onmouseover="this.style.background='#6b21a8'"
                    onmouseout="this.style.background='#7e22ce'"
                >
                    🔍 Szukaj w bazie
                </button>
            </div>

            {{-- KOMUNIKAT SKANOWANIA --}}
            <div id="catalog-scan-message" class="hidden mt-4 p-3 rounded"></div>
            
            {{-- POLE DO SKANOWANIA KODÓW QR W KATALOGU --}}
            <div id="catalog-qr-scanner-section" class="mt-4 p-4 bg-purple-50 border-2 border-purple-400 rounded hidden">
                <h4 class="font-bold text-purple-700 mb-3">📷 Zeskanuj kod QR ze skanera USB</h4>
                <p class="text-sm text-purple-600 mb-2">Zeskanuj kod QR skanerem (automatyczne wczytanie po Enter)</p>
                <input
                    type="text"
                    id="catalog-qr-scanner-input"
                    placeholder="Zeskanuj kod QR..."
                    class="border-2 border-purple-400 p-3 rounded w-full focus:border-purple-600 focus:ring-2 focus:ring-purple-300 text-lg mb-2"
                >
                <button
                    type="button"
                    id="catalog-cancel-scan-btn"
                    class="bg-gray-400 hover:bg-gray-500 text-white rounded px-4 py-2 text-sm"
                >
                    ✖ Anuluj
                </button>
            </div>

            {{-- DODAJ Z KATALOGU PRODUKTÓW --}}
            <table class="w-full border border-collapse text-xs">
                <thead class="bg-gray-100">
                    <tr>
                        <th class="border p-2 text-center text-xs" style="width: 40px;">
                            <input type="checkbox" id="select-all-catalog-add" class="w-4 h-4 cursor-pointer" title="Zaznacz wszystkie">
                        </th>
                        <th class="border p-2 text-left text-xs whitespace-nowrap min-w-[16rem] max-w-[24rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('name')">Produkty <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-2 text-left text-xs whitespace-nowrap min-w-[12rem] max-w-[20rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('description')">Opis <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-2 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('supplier')">Dost. <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-2 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem] cursor-pointer hover:bg-gray-200" style="width: 100px;" onclick="sortTable('net_price')">Cena netto <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-2 text-left text-xs whitespace-nowrap min-w-[6.5rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('category')">Kategoria <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-2 text-center text-xs whitespace-nowrap min-w-[2.5rem] max-w-[4rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('quantity')">Stan <span class="align-middle ml-1 text-gray-400">↕</span></th>                        <th class="border p-2 text-center text-xs whitespace-nowrap min-w-[2.5rem] max-w-[4rem]">Stan min.</th>                        <th class="border p-1 text-center text-xs whitespace-nowrap min-w-[4.5rem]" style="width: 6ch;">User</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($parts ?? [] as $p)
                        @php
                            $supplierShort = '';
                            if ($p->supplier) {
                                $sup = $suppliers->firstWhere('name', $p->supplier);
                                $supplierShort = $sup ? ($sup->short_name ?? $sup->name) : $p->supplier;
                            }
                        @endphp
                        <tr>
                            <td class="border p-2 text-center">
                                <input type="checkbox" class="catalog-checkbox w-4 h-4 cursor-pointer" 
                                       data-part-name="{{ $p->name }}" 
                                       data-part-desc="{{ $p->description ?? '' }}" 
                                       data-part-supplier="{{ $p->supplier ?? '' }}" 
                                       data-part-supplier-short="{{ $supplierShort }}" 
                                       data-part-qty="{{ $p->quantity }}" 
                                       data-part-cat="{{ $p->category_id }}"
                                       data-part-cat-name="{{ $p->category->name ?? '' }}"
                                       data-part-price="{{ $p->net_price ?? '' }}"
                                       data-part-currency="{{ $p->currency ?? 'PLN' }}">
                            </td>
                            <td class="border p-2">{{ $p->name }}</td>
                            <td class="border p-2 text-xs text-gray-700">{{ $p->description ?? '-' }}</td>
                            <td class="border p-2 text-center text-xs text-gray-700">{{ $supplierShort ?: '-' }}</td>
                            <td class="border p-2 text-center">
                                @if($p->net_price)
                                    {{ number_format($p->net_price, 2) }} <span class="text-xs">{{ $p->currency }}</span>
                                @else
                                    -
                                @endif
                            </td>
                            <td class="border p-2">{{ $p->category->name ?? '-' }}</td>
                            <td class="border p-2 text-center text-xs {{ $p->quantity <= $p->minimum_stock ? 'bg-red-200' : '' }}">{{ $p->quantity }}</td>
                            <td class="border p-2 text-center text-xs text-gray-600">{{ $p->minimum_stock }}</td>
                            <td class="border p-2 text-center text-xs text-gray-600">{{ $p->lastModifiedBy ? $p->lastModifiedBy->short_name : '-' }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td class="border p-2 text-center text-gray-400 italic" colspan="7">Brak produktów w katalogu</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- SEKCJA: HISTORIA SESJI (ROZWIJALNA) --}}
    @if(!empty($sessionAdds) && count($sessionAdds))
        <div class="bg-white rounded shadow mb-6 border">
            <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="history-content">
                <span class="toggle-arrow text-lg">▶</span>
                <h3 class="text-lg font-semibold">Dodane w tej sesji</h3>
            </button>
            <div id="history-content" class="collapsible-content hidden p-6 border-t">
                <div class="flex items-center justify-between mb-4">
                    <form method="POST" action="{{ route('parts.clearSession') }}" style="display: inline;" onsubmit="return confirm('Czy na pewno wyczyścić historię sesji?');">
                        @csrf
                        <input type="hidden" name="type" value="adds">
                        <button type="submit" class="bg-purple-300 hover:bg-purple-400 text-white px-3 py-1 rounded text-sm">🗑️ Wyczyść historię</button>
                    </form>
                </div>

                <table class="w-full border border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2 text-left">Produkt</th>
                            <th class="border p-2 text-left">Opis</th>
                            <th class="border p-2 text-left" style="width: 80px;">Dostawca</th>
                            <th class="border p-2 text-center">Dodano</th>
                            <th class="border p-2 text-center">Stan po</th>
                            <th class="border p-2 text-left">Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($sessionAdds as $r)
                            <tr>
                                <td class="border p-2">{{ $r['name'] ?? '-' }}</td>
                                <td class="border p-2">{{ $r['description'] ?? '-' }}</td>
                                <td class="border p-2 text-xs text-gray-700">{{ $r['supplier'] ?? '-' }}</td>
                                <td class="border p-2 text-center text-green-600 font-bold">
                                    +{{ $r['changed'] ?? 0 }}
                                </td>
                                <td class="border p-2 text-center font-bold">
                                    {{ $r['after'] ?? '-' }}
                                </td>
                                <td class="border p-2">{{ $r['date'] ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif
</div>

{{-- JAVASCRIPT --}}
<script>
document.addEventListener('DOMContentLoaded', () => {

    // 🎯 ACCORDION - COLLAPSIBLE SEKCJE
    document.querySelectorAll('.collapsible-btn').forEach(btn => {
        btn.addEventListener('click', () => {
            const target = btn.getAttribute('data-target');
            const content = document.getElementById(target);
            const arrow = btn.querySelector('.toggle-arrow');
            
            const isVisible = content.classList.contains('hidden') === false;
            
            if (isVisible) {
                content.classList.add('hidden');
                arrow.textContent = '▶';
                localStorage.removeItem('section_' + target);
            } else {
                content.classList.remove('hidden');
                arrow.textContent = '▼';
                localStorage.setItem('section_' + target, 'open');
            }
        });
    });

    // Przywróć otwarte sekcje po załadowaniu strony
    document.querySelectorAll('.collapsible-btn').forEach(btn => {
        const target = btn.getAttribute('data-target');
        if (localStorage.getItem('section_' + target) === 'open') {
            const content = document.getElementById(target);
            const arrow = btn.querySelector('.toggle-arrow');
            content.classList.remove('hidden');
            arrow.textContent = '▼';
        }
    });

    const form      = document.getElementById('add-form');
    const nameInput = document.getElementById('part-name');
    const descInput = document.getElementById('part-description');
    const qtyInput  = document.querySelector('input[name="quantity"]');
    const catSelect = document.querySelector('select[name="category_id"]');
    const qtyInfo   = document.getElementById('current-quantity');
    const similarWarning = document.getElementById('similar-warning');
    const similarList = document.getElementById('similar-list');

    // 🎯 MULTI-SELECT CHECKBOXES W KATALOGU
    const catalogCheckboxes = document.querySelectorAll('.catalog-checkbox');
    const selectedProductsBtn = document.getElementById('selected-products-btn');
    const selectedProductsContent = document.getElementById('selected-products-inner');
    const selectedProductsTable = document.getElementById('selected-products-table-inner').querySelector('tbody');
    const removeAllBtnInner = document.getElementById('remove-all-selected-btn-inner');
    const addAllBtn = document.getElementById('add-all-btn-inner');
    const selectAllAddCheckbox = document.getElementById('select-all-add-products');
    const selectAllCatalogAddCheckbox = document.getElementById('select-all-catalog-add');
    let selectedProducts = {};

    // Globalny checkbox "Zaznacz wszystkie" w katalogu produktów
    if (selectAllCatalogAddCheckbox) {
        selectAllCatalogAddCheckbox.addEventListener('change', function() {
            catalogCheckboxes.forEach(cb => {
                cb.checked = this.checked;
                const event = new Event('change', { bubbles: true });
                cb.dispatchEvent(event);
            });
        });
    }

    function updateSelectedProductsDisplay() {
        selectedProductsTable.innerHTML = '';
        
        Object.entries(selectedProducts).forEach(([name, data]) => {
            const row = document.createElement('tr');
            const stockClass = data.stockQuantity === 0 ? 'text-red-600 bg-red-50 font-bold' : 'text-blue-600 font-bold';
            
            // Generuj opcje dla selecta dostawców
            let supplierOptions = '<option value="">- wybierz -</option>';
            @foreach($suppliers as $s)
                supplierOptions += `<option value="{{ $s->name }}" ${data.supplier === '{{ $s->name }}' ? 'selected' : ''}>{{ $s->short_name ?? $s->name }}</option>`;
            @endforeach
            
            // Generuj opcje dla selecta kategorii
            let categoryOptions = '<option value="">- wybierz -</option>';
            @foreach($categories as $c)
                categoryOptions += `<option value="{{ $c->id }}" data-cat-name="{{ $c->name }}" ${data.categoryId == {{ $c->id }} ? 'selected' : ''}>{{ $c->name }}</option>`;
            @endforeach
            
            row.innerHTML = `
                <td class="border p-2 text-center">
                    <input type="checkbox" checked class="w-4 h-4 cursor-pointer selected-product-checkbox" data-product-name="${name}">
                </td>
                <td class="border p-1">${name}</td>
                <td class="border p-1">
                    <select class="w-16 px-1 py-0.5 border rounded product-supplier text-xs" data-product-name="${name}">
                        ${supplierOptions}
                    </select>
                </td>
                <td class="border p-1 text-center">
                    <div class="flex gap-1 items-center">
                        <input type="text" min="0" value="${data.netPrice || ''}" placeholder="Cena" maxlength="9"
                               class="w-16 px-1 py-0.5 border rounded text-xs product-price" data-product-name="${name}">
                        <select class="px-1 py-0.5 border rounded text-xs product-currency" data-product-name="${name}">
                            <option value="PLN" ${data.currency === 'PLN' ? 'selected' : ''}>PLN</option>
                            <option value="EUR" ${data.currency === 'EUR' ? 'selected' : ''}>EUR</option>
                            <option value="$" ${data.currency === '$' ? 'selected' : ''}>$</option>
                        </select>
                    </div>
                </td>
                <td class="border p-1">
                    <select class="w-full px-1 py-0.5 border rounded product-category text-xs" data-product-name="${name}">
                        ${categoryOptions}
                    </select>
                </td>
                <td class="border p-1 text-center ${stockClass}">${data.stockQuantity}</td>
                <td class="border p-1 text-center">
                    <input type="number" min="1" value="${data.quantity}" size="3" class="px-1 py-0.5 border rounded text-center text-xs product-qty" data-product-name="${name}">
                </td>
                <td class="border p-1 text-center">
                    <button type="button" class="bg-green-500 hover:bg-green-600 text-white px-1 py-0 rounded text-xs add-product-btn" data-product-name="${name}" data-product-cat="${data.categoryId}" data-product-desc="${data.description}" data-product-supplier="${data.supplier}">➕</button>
                    <button type="button" class="bg-red-500 hover:bg-red-600 text-white px-1 py-0 rounded text-xs ml-1 remove-product-btn" data-product-name="${name}">🗑️</button>
                </td>
            `;
            selectedProductsTable.appendChild(row);
        });

        // Obsługa checkboxów w tabeli wybranych produktów
        document.querySelectorAll('.selected-product-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                if (!e.target.checked) {
                    // Usuń produkt z listy
                    delete selectedProducts[productName];
                    
                    // Odznacz checkbox w katalogu
                    catalogCheckboxes.forEach(cb => {
                        if (cb.dataset.partName === productName) {
                            cb.checked = false;
                        }
                    });
                    
                    updateSelectedProductsDisplay();
                }
            });
        });

        // Obsługa przycisków usuwania
        document.querySelectorAll('.remove-product-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productName = e.target.dataset.productName;
                
                // Usuń produkt z listy
                delete selectedProducts[productName];
                
                // Odznacz checkbox w katalogu
                catalogCheckboxes.forEach(cb => {
                    if (cb.dataset.partName === productName) {
                        cb.checked = false;
                    }
                });
                
                updateSelectedProductsDisplay();
            });
        });

        // Podświetl przycisk na zielono (ale nie zmieniaj strzałki)
        if (Object.keys(selectedProducts).length > 0) {
            selectedProductsBtn.classList.add('bg-green-100');
        } else {
            selectedProductsBtn.classList.remove('bg-green-100');
        }

        // Obsługa zmian ilości
        document.querySelectorAll('.product-qty').forEach(input => {
            input.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                selectedProducts[productName].quantity = parseInt(e.target.value) || 1;
            });
        });

        // Obsługa zmian dostawcy
        document.querySelectorAll('.product-supplier').forEach(input => {
            input.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                selectedProducts[productName].supplier = e.target.value;
            });
        });

        // Obsługa zmian ceny
        document.querySelectorAll('.product-price').forEach(input => {
            input.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                selectedProducts[productName].netPrice = e.target.value;
            });
        });

        // Obsługa zmian waluty
        document.querySelectorAll('.product-currency').forEach(input => {
            input.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                selectedProducts[productName].currency = e.target.value;
            });
        });

        // Obsługa zmian kategorii
        document.querySelectorAll('.product-category').forEach(input => {
            input.addEventListener('change', (e) => {
                const productName = e.target.dataset.productName;
                const selectedOption = e.target.options[e.target.selectedIndex];
                selectedProducts[productName].categoryId = parseInt(e.target.value) || 0;
                selectedProducts[productName].categoryName = selectedOption.dataset.catName || '';
            });
        });

        // Obsługa pola ceny - zamiana przecinka na kropkę
        document.querySelectorAll('.product-price').forEach(input => {
            input.addEventListener('input', (e) => {
                e.target.value = e.target.value.replace(',', '.');
            });
        });

        // Obsługa dodawania pojedynczego produktu
        document.querySelectorAll('.add-product-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productName = e.target.dataset.productName;
                const productCat = parseInt(e.target.dataset.productCat);
                const productDesc = e.target.dataset.productDesc;
                const productSupplier = document.querySelector(`.product-supplier[data-product-name="${productName}"]`).value;
                const qty = parseInt(document.querySelector(`.product-qty[data-product-name="${productName}"]`).value) || 1;
                const productPrice = document.querySelector(`.product-price[data-product-name="${productName}"]`)?.value || '';
                const productCurrency = document.querySelector(`.product-currency[data-product-name="${productName}"]`)?.value || 'PLN';
                const productCategoryId = parseInt(document.querySelector(`.product-category[data-product-name="${productName}"]`)?.value) || productCat;

                // Potwierdzenie PRZED dodaniem do magazynu
                if (!confirm('Czy na pewno dodać ' + qty + ' szt. produktu: ' + productName + '?')) {
                    return; // Anulowano - przerwij operację
                }

                const formData = new FormData();
                formData.append('name', productName);
                formData.append('description', productDesc);
                formData.append('supplier', productSupplier);
                formData.append('quantity', qty);
                formData.append('category_id', productCategoryId);
                if (productPrice) {
                    formData.append('net_price', productPrice);
                }
                formData.append('currency', productCurrency);

                console.log('Wysyłam żądanie dodania:', {name: productName, qty, cat: productCategoryId, supplier: productSupplier, price: productPrice, currency: productCurrency});

                fetch('{{ route('parts.add') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    console.log('Status odpowiedzi:', response.status);
                    if (!response.ok) {
                        return response.json().then(err => {
                            throw new Error(err.message || 'Błąd podczas dodawania');
                        });
                    }
                    return response.json;
                })
                .then((data) => {
                    console.log('Odpowiedź serwera:', data);
                    
                    // Zaktualizuj stan magazynu w obiekcie selectedProducts
                    if (selectedProducts[productName] && data.quantity !== undefined) {
                        selectedProducts[productName].stockQuantity = data.quantity;
                    }
                    
                    // Zaktualizuj stan w tabeli katalogu
                    catalogCheckboxes.forEach(cb => {
                        if (cb.dataset.partName === productName) {
                            cb.dataset.partQty = data.quantity;
                            const row = cb.closest('tr');
                            const stateCells = row.querySelectorAll('td');
                            // Ostatnia kolumna to stan magazynu (7. kolumna, indeks 6)
                            const stateCell = stateCells[6];
                            if (stateCell) {
                                stateCell.textContent = data.quantity;
                                stateCell.className = 'border p-2 text-center font-bold' + (data.quantity == 0 ? ' text-red-600 bg-red-50' : '');
                            }
                        }
                    });
                    
                    // Odśwież wyświetlanie tabeli z nowym stanem
                    updateSelectedProductsDisplay();
                    
                    // Produkt został dodany - usuń z listy
                    delete selectedProducts[productName];
                    
                    // Odznacz checkbox w katalogu
                    catalogCheckboxes.forEach(cb => {
                        if (cb.dataset.partName === productName) {
                            cb.checked = false;
                        }
                    });
                    
                    updateSelectedProductsDisplay();
                    
                    alert('✅ Dodano ' + qty + ' szt. produktu: ' + productName);
                })
                .catch(err => {
                    console.error('Błąd:', err);
                    alert('❌ Błąd podczas dodawania produktu');
                });
            });
        });

        // Obsługa usuwania produktu
        document.querySelectorAll('.remove-product-btn').forEach(btn => {
            btn.addEventListener('click', (e) => {
                const productName = e.target.dataset.productName;
                delete selectedProducts[productName];
                
                // Odznacz checkbox w katalogu
                catalogCheckboxes.forEach(cb => {
                    if (cb.dataset.partName === productName) {
                        cb.checked = false;
                    }
                });
                
                updateSelectedProductsDisplay();
            });
        });
    }

    catalogCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', () => {
            const productName = checkbox.dataset.partName;
            const productDesc = checkbox.dataset.partDesc;
            const productSupplier = checkbox.dataset.partSupplier;
            const productSupplierShort = checkbox.dataset.partSupplierShort || '';
            const productQty = parseInt(checkbox.dataset.partQty) || 0;
            const productCat = parseInt(checkbox.dataset.partCat) || 0;
            const productCatName = checkbox.dataset.partCatName || '';
            const productPrice = checkbox.dataset.partPrice || '';
            const productCurrency = checkbox.dataset.partCurrency || 'PLN';
            
            if (checkbox.checked) {
                selectedProducts[productName] = {
                    description: productDesc,
                    supplier: productSupplier,
                    supplierShort: productSupplierShort,
                    quantity: 1,
                    stockQuantity: productQty,
                    categoryId: productCat,
                    categoryName: productCatName,
                    netPrice: productPrice,
                    currency: productCurrency
                };
            } else {
                delete selectedProducts[productName];
            }
            
            updateSelectedProductsDisplay();
        });
    });

    // Globalny checkbox "Zaznacz wszystkie" w tabeli produktów do dodania
    if (selectAllAddCheckbox) {
        selectAllAddCheckbox.addEventListener('change', function() {
            // Obsługa zarówno produktów z katalogu jak i z importu Excel
            const checkboxes = selectedProductsTable.querySelectorAll('.selected-product-checkbox, .row-checkbox-add');
            checkboxes.forEach(cb => {
                cb.checked = this.checked;
            });
        });
    }

    removeAllBtnInner.addEventListener('click', () => {
        selectedProducts = {};
        catalogCheckboxes.forEach(cb => cb.checked = false);
        updateSelectedProductsDisplay();
    });

    addAllBtn.addEventListener('click', () => {
        const loadingInfo = document.getElementById('add-all-loading-info');
        // Sprawdź czy są produkty z katalogu lub zaimportowane z Excel
        const excelRows = selectedProductsTable.querySelectorAll('tr');
        const checkedExcelRows = Array.from(excelRows).filter(row => {
            const checkbox = row.querySelector('.row-checkbox-add');
            return checkbox && checkbox.checked;
        });

        if (Object.keys(selectedProducts).length === 0 && checkedExcelRows.length === 0) {
            if (loadingInfo) loadingInfo.style.display = 'none';
            alert('Zaznacz przynajmniej jeden produkt');
            return;
        }
        if (loadingInfo) loadingInfo.style.display = 'inline';

        let delay = 0;
        let addedCount = 0;

        // Pobierz aktualne wartości dostawców, cen, walut i kategorii z pól przed wysłaniem
        Object.keys(selectedProducts).forEach(name => {
            const supplierSelect = document.querySelector(`.product-supplier[data-product-name="${name}"]`);
            const priceInput = document.querySelector(`.product-price[data-product-name="${name}"]`);
            const currencySelect = document.querySelector(`.product-currency[data-product-name="${name}"]`);
            const categorySelect = document.querySelector(`.product-category[data-product-name="${name}"]`);
            
            if (supplierSelect) {
                selectedProducts[name].supplier = supplierSelect.value;
            }
            if (priceInput) {
                selectedProducts[name].netPrice = priceInput.value;
            }
            if (currencySelect) {
                selectedProducts[name].currency = currencySelect.value;
            }
            if (categorySelect) {
                selectedProducts[name].categoryId = parseInt(categorySelect.value) || selectedProducts[name].categoryId;
            }
        });

        // Dodaj wszystkie produkty z katalogu
        Object.entries(selectedProducts).forEach(([name, data]) => {
            setTimeout(() => {
                const formData = new FormData();
                formData.append('name', name);
                formData.append('description', data.description);
                formData.append('supplier', data.supplier || '');
                formData.append('quantity', data.quantity);
                formData.append('category_id', data.categoryId);
                if (data.netPrice) {
                    formData.append('net_price', data.netPrice);
                }
                formData.append('currency', data.currency || 'PLN');

                fetch('{{ route('parts.add') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Błąd podczas dodawania');
                    return response.json();
                })
                .then(() => {
                    addedCount++;
                })
                .catch(err => {
                    console.error('Błąd:', err);
                });
            }, delay);
            delay += 300; // 300ms między żądaniami
        });

        // Dodaj wszystkie zaznaczone produkty z importu Excel
        checkedExcelRows.forEach(row => {
            setTimeout(() => {
                const formData = new FormData();
                
                // Pobierz dane z pól formularza w wierszu
                const nameInput = row.querySelector('input[name*="[name]"]');
                const supplierSelect = row.querySelector('select[name*="[supplier]"]');
                const priceInput = row.querySelector('input[name*="[net_price]"]');
                const currencySelect = row.querySelector('select[name*="[currency]"]');
                const categorySelect = row.querySelector('select[name*="[category_id]"]');
                const quantityInput = row.querySelector('input[name*="[quantity]"]');
                const descriptionInput = row.querySelector('input[name*="[description]"]');
                const locationInput = row.querySelector('input[name*="[location]"]');
                const qrCodeInput = row.querySelector('input[name*="[qr_code]"]');
                
                if (nameInput) formData.append('name', nameInput.value);
                if (supplierSelect) formData.append('supplier', supplierSelect.value);
                if (priceInput && priceInput.value) formData.append('net_price', priceInput.value);
                if (currencySelect) formData.append('currency', currencySelect.value);
                if (categorySelect) formData.append('category_id', categorySelect.value);
                if (quantityInput) formData.append('quantity', quantityInput.value);
                if (descriptionInput) formData.append('description', descriptionInput.value);
                if (locationInput) formData.append('location', locationInput.value);
                if (qrCodeInput) formData.append('qr_code', qrCodeInput.value);

                fetch('{{ route('parts.add') }}', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    },
                    body: formData
                })
                .then(response => {
                    if (!response.ok) throw new Error('Błąd podczas dodawania');
                    return response.json();
                })
                .then(() => {
                    addedCount++;
                    // Usuń wiersz po pomyślnym dodaniu
                    row.remove();
                })
                .catch(err => {
                    console.error('Błąd:', err);
                    alert('Błąd podczas dodawania produktu: ' + (nameInput ? nameInput.value : 'nieznany'));
                });
            }, delay);
            delay += 300; // 300ms między żądaniami
        });

        // Wyczyść listę po chwili
        setTimeout(() => {
            selectedProducts = {};
            catalogCheckboxes.forEach(cb => cb.checked = false);
            updateSelectedProductsDisplay();
            if (loadingInfo) loadingInfo.style.display = 'none';
            if (addedCount > 0) {
                // Zapamiętaj że katalog ma być otwarty
                localStorage.setItem('katalogOtwarty', 'true');
                window.location.reload();
            }
        }, delay + 500);
    });

    // Funkcja do zaznaczenia różnic w tekście
    function highlightDifferences(input, target) {
        const inputLower = input.toLowerCase();
        const targetLower = target.toLowerCase();
        
        let result = '';
        let inputIdx = 0;
        
        // Przejdź przez target i zaznacz znaki które nie są w input
        for (let i = 0; i < targetLower.length; i++) {
            if (inputIdx < inputLower.length && targetLower[i] === inputLower[inputIdx]) {
                result += target[i];
                inputIdx++;
            } else {
                result += `<span class="bg-red-300 font-bold">${target[i]}</span>`;
            }
        }
        
        return result;
    }

    // ❌ ENTER NIE WYSYŁA FORMULARZA (NIGDZIE)
    form.addEventListener('keydown', (e) => {
        if (e.key === 'Enter') {
            e.preventDefault();
        }
    });

    // � OTWÓRZ KATALOG JEŚLI BYŁ ZAPAMIĘTANY
    if (localStorage.getItem('katalogOtwarty') === 'true') {
        const catalogBtn = document.querySelector('[data-target="catalog-content"]');
        const catalogContent = document.getElementById('catalog-content');
        catalogContent.classList.remove('hidden');
        catalogBtn.querySelector('.toggle-arrow').textContent = '▼';
        localStorage.removeItem('katalogOtwarty');
    }

    // 🔎 PODGLĄD PO NAZWIE (PO WYJŚCIU Z POLA)
    nameInput.addEventListener('blur', () => {
        if (nameInput.value.length < 2) {
            similarWarning.classList.add('hidden');
            return;
        }

        // Sprawdzenie podobnych nazw
        fetch('{{ route('parts.searchSimilar') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: nameInput.value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.similar && data.similar.length > 0) {
                similarList.innerHTML = data.similar.map(part => 
                    `<li>• <strong>${highlightDifferences(nameInput.value, part.name)}</strong> (stan: ${part.quantity})</li>`
                ).join('');
                similarWarning.classList.remove('hidden');
            } else {
                similarWarning.classList.add('hidden');
            }
        });

        // Sprawdzenie dokładnego dopasowania
        fetch('{{ route('parts.preview') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({ name: nameInput.value })
        })
        .then(res => res.json())
        .then(data => {
            if (data.exists) {
                qtyInfo.innerText = data.quantity ?? 0;

                // 👉 uzupełnij opis tylko jeśli pusty
                if (!descInput.value && data.description) {
                    descInput.value = data.description;
                }
            } else {
                qtyInfo.innerText = '0';
            }
        });
    });

    // 📱 GENEROWANIE KODU QR
    document.getElementById('generate-qr-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const productName = document.getElementById('part-name').value;
        const location = document.querySelector('[name="location"]').value;
        
        if (!productName) {
            alert('Wprowadź nazwę produktu, aby wygenerować kod QR');
            return;
        }
        
        fetch('{{ route('parts.generateQr') }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            },
            body: JSON.stringify({
                name: productName,
                location: location
            })
        })
        .then(res => {
            if (!res.ok) {
                return res.json().then(err => {
                    throw new Error(err.message || 'Błąd HTTP: ' + res.status);
                }).catch(() => {
                    throw new Error('Błąd HTTP: ' + res.status);
                });
            }
            return res.json();
        })
        .then(data => {
            console.log('Odpowiedź serwera:', data);
            
            if (data.success) {
                // Wyświetl sekcję QR
                document.getElementById('qr-code-section').classList.remove('hidden');
                
                // Wstaw obraz QR (SVG)
                document.getElementById('qr-code-image').innerHTML = data.qr_image;
                
                // Wstaw tekst kodu
                document.getElementById('qr-code-text').textContent = data.qr_code;
                
                // Wstaw opis
                document.getElementById('qr-code-description').textContent = data.description;
                
                // Zapisz kod QR w hidden input
                document.getElementById('qr-code-hidden').value = data.qr_code;
            } else {
                alert(data.message || 'Błąd podczas generowania kodu QR');
            }
        })
        .catch(err => {
            console.error('Błąd:', err);
            alert('Błąd podczas generowania kodu QR: ' + err.message);
        });
    });

    // =============================
    // ODCZYTYWANIE KODU QR (SKANER USB) - FORMULARZ
    // =============================
    const scanQrBtn = document.getElementById('scan-qr-btn');
    const formQrScannerSection = document.getElementById('form-qr-scanner-section');
    const formQrScannerInput = document.getElementById('form-qr-scanner-input');
    const formCancelScanBtn = document.getElementById('form-cancel-scan-btn');
    
    // =============================
    // ODCZYTYWANIE KODU QR (SKANER USB) - KATALOG
    // =============================
    const catalogScanQrBtn = document.getElementById('catalog-scan-qr-btn');
    const catalogQrScannerSection = document.getElementById('catalog-qr-scanner-section');
    const catalogQrScannerInput = document.getElementById('catalog-qr-scanner-input');
    const catalogCancelScanBtn = document.getElementById('catalog-cancel-scan-btn');
    
    // Przycisk skanowania w katalogu
    if (catalogScanQrBtn) {
        catalogScanQrBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Pokaż sekcję skanowania w katalogu
            catalogQrScannerSection.classList.remove('hidden');
            
            // Ustaw focus na input
            catalogQrScannerInput.value = '';
            catalogQrScannerInput.focus();
        });
    }
    
    // Przycisk skanowania w formularzu
    if (scanQrBtn) {
        scanQrBtn.addEventListener('click', function(e) {
            e.preventDefault();
            
            // Pokaż sekcję skanowania w formularzu
            formQrScannerSection.classList.remove('hidden');
            
            // Ustaw focus na input
            formQrScannerInput.value = '';
            formQrScannerInput.focus();
        });
    }
    
    // Przyciski anulowania
    if (catalogCancelScanBtn) {
        catalogCancelScanBtn.addEventListener('click', function() {
            catalogQrScannerSection.classList.add('hidden');
            catalogQrScannerInput.value = '';
        });
    }
    
    if (formCancelScanBtn) {
        formCancelScanBtn.addEventListener('click', function() {
            formQrScannerSection.classList.add('hidden');
            formQrScannerInput.value = '';
        });
    }
    
    // Obsługa skanowania w katalogu (Enter)
    if (catalogQrScannerInput) {
        catalogQrScannerInput.addEventListener('keydown', function(e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                console.log('Enter wykryty w katalogu! Wartość:', catalogQrScannerInput.value.trim());
                searchProductByQr(catalogQrScannerInput.value.trim(), 'catalog');
            }
        });
    }
    
    // Obsługa skanowania w formularzu (Enter)
    if (formQrScannerInput) {
        console.log('Przypisano listener dla formQrScannerInput');
        formQrScannerInput.addEventListener('keydown', function(e) {
            console.log('Keydown w formularzu:', e.key, 'keyCode:', e.keyCode, 'Wartość:', formQrScannerInput.value);
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                console.log('Enter wykryty w formularzu! Wywołuję searchProductByQr z:', formQrScannerInput.value.trim());
                searchProductByQr(formQrScannerInput.value.trim(), 'form');
            }
        });
    } else {
        console.error('BŁĄD: formQrScannerInput NIE ZNALEZIONY!');
    }
    
    // Funkcja szukająca produkt po kodzie QR
    function searchProductByQr(qrCode, mode) {
        console.log('=== Szukam produktu, QR kod:', qrCode, 'Tryb:', mode, '===');
        
        if (!qrCode) {
            alert('⚠️ Pole kodu QR jest puste!');
            if (mode === 'catalog') {
                catalogQrScannerInput.focus();
            } else {
                formQrScannerInput.focus();
            }
            return;
        }
        
        console.log('Wysyłam request do /parts/find-by-qr...');
        
        // Wyszukaj produkt po kodzie QR
        fetch('/parts/find-by-qr', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || ''
            },
            body: JSON.stringify({ qr_code: qrCode })
        })
        .then(res => {
            console.log('Response status:', res.status);
            return res.json();
        })
        .then(data => {
            console.log('=== Odpowiedź serwera ===', data);
            
            if (data.success && data.part) {
                if (mode === 'catalog') {
                    // Tryb katalogu - zaznacz checkbox produktu
                    console.log('Tryb katalogu - szukam checkboxa dla produktu:', data.part.name);
                    
                    // Pobierz checkboxy dynamicznie
                    const catalogCheckboxes = document.querySelectorAll('.catalog-checkbox');
                    
                    let found = false;
                    catalogCheckboxes.forEach(cb => {
                        if (cb.dataset.partName === data.part.name) {
                            cb.checked = true;
                            // Wywołaj event change aby dodać do listy
                            const event = new Event('change', { bubbles: true });
                            cb.dispatchEvent(event);
                            found = true;
                        }
                    });
                    
                    if (found) {
                        // Pokaż komunikat sukcesu
                        const messageBox = document.getElementById('catalog-scan-message');
                        if (messageBox) {
                            messageBox.className = 'mt-4 p-3 rounded bg-green-100 border border-green-400 text-green-700';
                            messageBox.innerHTML = '✅ <strong>Produkt znaleziony!</strong><br>' +
                                                   'Nazwa: ' + data.part.name + ' | ' +
                                                   'Ilość w magazynie: ' + data.part.quantity;
                            messageBox.classList.remove('hidden');
                            
                            // Ukryj komunikat po 5 sekundach
                            setTimeout(() => {
                                messageBox.classList.add('hidden');
                            }, 5000);
                        }
                        
                        // Otwórz sekcję "Produkty do dodania" jeśli jest zwinięta
                        const selectedProductsContent = document.getElementById('selected-products-inner');
                        if (selectedProductsContent && selectedProductsContent.classList.contains('hidden')) {
                            selectedProductsContent.classList.remove('hidden');
                            const selectedProductsBtn = document.querySelector('[data-target="selected-products-inner"]');
                            if (selectedProductsBtn) {
                                const arrow = selectedProductsBtn.querySelector('.toggle-arrow');
                                if (arrow) arrow.textContent = '▼';
                            }
                        }
                    } else {
                        // Pokaż komunikat ostrzeżenia
                        const messageBox = document.getElementById('catalog-scan-message');
                        if (messageBox) {
                            messageBox.className = 'mt-4 p-3 rounded bg-yellow-100 border border-yellow-400 text-yellow-700';
                            messageBox.innerHTML = '⚠️ <strong>Produkt jest w bazie, ale nie jest widoczny w katalogu.</strong><br>Nazwa: ' + data.part.name;
                            messageBox.classList.remove('hidden');
                            
                            setTimeout(() => {
                                messageBox.classList.add('hidden');
                            }, 5000);
                        }
                    }
                    
                    // Ukryj sekcję skanowania
                    catalogQrScannerSection.classList.add('hidden');
                    catalogQrScannerInput.value = '';
                    
                } else {
                    // Tryb formularza - wypełnij formularz
                    console.log('Tryb formularza - wypełniam formularz...');
                    
                    fillFormWithPartData(data.part);
                    
                    // Pokaż komunikat sukcesu
                    const messageBox = document.getElementById('form-scan-message');
                    if (messageBox) {
                        messageBox.className = 'mt-4 p-3 rounded bg-green-100 border border-green-400 text-green-700';
                        messageBox.innerHTML = '✅ <strong>Produkt znaleziony w bazie danych!</strong><br>' +
                                               'Nazwa: ' + data.part.name + ' | ' +
                                               'Ilość w magazynie: ' + data.part.quantity + ' | ' +
                                               'Lokalizacja: ' + (data.part.location || 'brak');
                        messageBox.classList.remove('hidden');
                        
                        // Ukryj komunikat po 8 sekundach
                        setTimeout(() => {
                            messageBox.classList.add('hidden');
                        }, 8000);
                    }
                    
                    // Ukryj sekcję skanowania
                    formQrScannerSection.classList.add('hidden');
                    formQrScannerInput.value = '';
                    
                    // Otwórz sekcję formularza jeśli jest zwinięta
                    const formContent = document.getElementById('form-content');
                    if (formContent.classList.contains('hidden')) {
                        formContent.classList.remove('hidden');
                        const formBtn = document.querySelector('[data-target="form-content"]');
                        if (formBtn) {
                            const arrow = formBtn.querySelector('.toggle-arrow');
                            if (arrow) arrow.textContent = '▼';
                        }
                    }
                    
                    // Przewiń do formularza
                    formContent.scrollIntoView({ behavior: 'smooth', block: 'start' });
                }
            } else {
                // Pokaż komunikat błędu
                const messageBox = mode === 'catalog' 
                    ? document.getElementById('catalog-scan-message')
                    : document.getElementById('form-scan-message');
                    
                if (messageBox) {
                    messageBox.className = 'mt-4 p-3 rounded bg-red-100 border border-red-400 text-red-700';
                    messageBox.innerHTML = '❌ <strong>Produkt nie znaleziony w bazie danych!</strong><br>Kod QR: ' + qrCode;
                    messageBox.classList.remove('hidden');
                    
                    setTimeout(() => {
                        messageBox.classList.add('hidden');
                    }, 5000);
                }
                if (mode === 'catalog') {
                    catalogQrScannerInput.value = '';
                    catalogQrScannerInput.focus();
                } else {
                    formQrScannerInput.value = '';
                    formQrScannerInput.focus();
                }
            }
        })
        .catch(err => {
            console.error('Błąd:', err);
            const messageBox = mode === 'catalog'
                ? document.getElementById('catalog-scan-message')
                : document.getElementById('form-scan-message');
                
            if (messageBox) {
                messageBox.className = 'mt-4 p-3 rounded bg-red-100 border border-red-400 text-red-700';
                messageBox.innerHTML = '❌ <strong>Błąd podczas wyszukiwania produktu</strong><br>' + err.message;
                messageBox.classList.remove('hidden');
                
                setTimeout(() => {
                    messageBox.classList.add('hidden');
                }, 5000);
            }
            if (mode === 'catalog') {
                catalogQrScannerInput.value = '';
                catalogQrScannerInput.focus();
            } else {
                formQrScannerInput.value = '';
                formQrScannerInput.focus();
            }
        });
    }
    
    function fillFormWithPartData(part) {
        console.log('Wypełniam formularz danymi:', part);
        
        // Wypełnij pola formularza
        const nameInput = document.getElementById('part-name');
        const descInput = document.getElementById('part-description');
        const qtyInput = document.querySelector('input[name="quantity"]');
        const minStockInput = document.querySelector('input[name="minimum_stock"]');
        const locationInput = document.querySelector('input[name="location"]');
        const priceInput = document.getElementById('part-net-price');
        const currencySelect = document.getElementById('part-currency');
        const supplierSelect = document.getElementById('part-supplier');
        const categorySelect = document.querySelector('select[name="category_id"]');
        
        if (nameInput) nameInput.value = part.name || '';
        if (descInput) descInput.value = part.description || '';
        // Nie wypełniaj pola ilość - użytkownik wpisuje ręcznie
        if (minStockInput) minStockInput.value = part.minimum_stock || 0;
        if (locationInput) locationInput.value = part.location || '';
        if (priceInput && part.net_price) priceInput.value = part.net_price;
        if (currencySelect && part.currency) currencySelect.value = part.currency;
        if (supplierSelect && part.supplier) supplierSelect.value = part.supplier;
        if (categorySelect && part.category_id) categorySelect.value = part.category_id;
        
        console.log('Formularz wypełniony!');
    }
});
</script>

{{-- MODAL DO SKANOWANIA KODÓW QR --}}
<div id="qr-scanner-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center">
    <div class="bg-white rounded-lg p-6 max-w-md w-full mx-4">
        <div class="flex justify-between items-center mb-4">
            <h3 class="text-lg font-bold">Skanuj kod QR</h3>
            <button id="close-qr-modal" class="text-gray-500 hover:text-gray-700 text-2xl leading-none">&times;</button>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-semibold mb-2">Ustawienia skanera</label>
            <div class="flex gap-2">
                <select id="scanner-device-select" class="border p-2 rounded text-sm flex-1">
                    <option value="">Wybierz skaner...</option>
                </select>
                <button id="refresh-scanners-btn" class="bg-gray-200 hover:bg-gray-300 text-gray-700 rounded px-3 py-1 text-sm">
                    🔄 Odśwież
                </button>
            </div>
        </div>

        <div class="flex flex-col gap-4">
            <div class="flex items-center gap-2">
                <button id="start-scan-btn" class="bg-green-500 hover:bg-green-600 text-white rounded px-4 py-2 text-sm flex-1">
                    ▶ Rozpocznij skanowanie
                </button>
                <button id="stop-scan-btn" class="bg-red-500 hover:bg-red-600 text-white rounded px-4 py-2 text-sm hidden">
                    ■ Zatrzymaj skanowanie
                </button>
            </div>
            <div id="scanner-output" class="p-4 bg-gray-50 border rounded text-sm font-mono whitespace-pre-wrap" style="height: 100px; overflow-y: auto;">
                Oczekiwanie na zeskanowanie kodu QR...
            </div>
        </div>

        <div class="mt-4">
            <button id="close-scanner-btn" class="bg-gray-300 hover:bg-gray-400 text-gray-800 rounded px-4 py-2 text-sm w-full">
                ✖ Zamknij skaner
            </button>
        </div>
    </div>
</div>

<script>
    // Globalne opcje dostawców i kategorii dla importu
    let supplierOptions = '<option value="">- wybierz -</option>';
    @foreach($suppliers as $s)
        supplierOptions += `<option value="{{ $s->name }}">{{ $s->short_name ?? $s->name }}</option>`;
    @endforeach
    
    let categoryOptions = '<option value="">- wybierz -</option>';
    @foreach($categories as $c)
        categoryOptions += `<option value="{{ $c->id }}">{{ $c->name }}</option>`;
    @endforeach

    // IMPORT Z EXCELA
    const excelImportBtn = document.getElementById('import-excel-btn');
    const excelImportModal = document.getElementById('excel-import-modal');
    const closeExcelModal = document.getElementById('close-excel-modal');
    const excelImportForm = document.getElementById('excel-import-form');
    const importProgress = document.getElementById('import-progress');

    // Funkcja pomocnicza do escape'owania HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    excelImportBtn.addEventListener('click', () => {
        excelImportModal.style.display = 'flex';
    });

    closeExcelModal.addEventListener('click', () => {
        excelImportModal.style.display = 'none';
        excelImportForm.reset();
        importProgress.classList.add('hidden');
    });

    excelImportForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        
        const fileInput = document.getElementById('excel-file-input');
        const file = fileInput.files[0];
        
        if (!file) {
            alert('Wybierz plik Excel');
            return;
        }

        const formData = new FormData();
        formData.append('excel_file', file);

        importProgress.classList.remove('hidden');

        try {
            const response = await fetch('{{ route('parts.importExcel') }}', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: formData
            });

            const data = await response.json();

            if (data.success) {
                importProgress.classList.add('hidden');
                excelImportModal.style.display = 'none';
                excelImportForm.reset();

                // Dodaj produkty do katalogu
                if (data.products && data.products.length > 0) {
                    let existingCount = 0;
                    let newCount = 0;
                    
                    data.products.forEach(product => {
                        if (product.is_existing) {
                            existingCount++;
                        } else {
                            newCount++;
                        }
                        addProductToCatalog(product);
                    });

                    let message = `✅ Zaimportowano ${data.products.length} produktów do katalogu`;
                    if (existingCount > 0 && newCount > 0) {
                        message += `\n\n📦 ${existingCount} produkt(ów) już istnieje w bazie - zostanie zwiększona tylko ilość\n🆕 ${newCount} nowy(ch) produkt(ów)`;
                    } else if (existingCount > 0) {
                        message += `\n\n📦 Wszystkie produkty już istnieją w bazie - zostanie zwiększona tylko ilość`;
                    } else {
                        message += `\n\n🆕 Wszystkie produkty są nowe`;
                    }
                    
                    alert(message);
                } else {
                    alert('✅ Import zakończony, ale nie znaleziono produktów do dodania');
                }
            } else {
                importProgress.classList.add('hidden');
                alert('❌ Błąd: ' + (data.message || 'Nie udało się zaimportować produktów'));
            }
        } catch (error) {
            importProgress.classList.add('hidden');
            console.error('Błąd importu:', error);
            alert('❌ Błąd podczas importowania pliku: ' + error.message);
        }
    });

    // Funkcja dodająca produkt do katalogu
    function addProductToCatalog(product) {
        const catalogTable = document.querySelector('#selected-products-table-inner tbody');
        if (!catalogTable) {
            console.error('Nie znaleziono tabeli produktów');
            return;
        }
        
        const rowCount = catalogTable.querySelectorAll('tr').length;
        
        // Określ klasę CSS na podstawie tego czy produkt już istnieje
        const rowClass = product.is_existing ? 'bg-blue-50' : '';
        const existingBadge = product.is_existing ? '<span class="text-xs text-blue-600 font-semibold ml-1" title="Produkt już istnieje w bazie - zostanie zwiększona tylko ilość">📦 ISTNIEJE</span>' : '';
        
        const newRow = document.createElement('tr');
        newRow.className = rowClass;
        newRow.innerHTML = `
            <td class="border p-1 text-center">
                <input type="checkbox" class="row-checkbox-add w-4 h-4 cursor-pointer">
            </td>
            <td class="border p-1 text-left">
                <input type="text" name="catalog_products[${rowCount}][name]" value="${escapeHtml(product.name)}" class="w-full px-2 py-1 border rounded text-xs" required>
                ${existingBadge}
            </td>
            <td class="border p-1 text-left">
                <select name="catalog_products[${rowCount}][supplier]" class="w-full px-1 py-1 border rounded text-xs">
                    ${supplierOptions}
                </select>
            </td>
            <td class="border p-1 text-center">
                <input type="number" name="catalog_products[${rowCount}][net_price]" value="${product.net_price || ''}" step="0.01" class="w-20 px-1 py-1 border rounded text-xs">
                <select name="catalog_products[${rowCount}][currency]" class="w-16 px-1 py-1 border rounded text-xs ml-1">
                    <option value="PLN" ${product.currency === 'PLN' ? 'selected' : ''}>PLN</option>
                    <option value="EUR" ${product.currency === 'EUR' ? 'selected' : ''}>EUR</option>
                    <option value="$" ${product.currency === '$' ? 'selected' : ''}>$</option>
                </select>
            </td>
            <td class="border p-1 text-left">
                <select name="catalog_products[${rowCount}][category_id]" class="w-full px-1 py-1 border rounded text-xs" required>
                    ${categoryOptions}
                </select>
            </td>
            <td class="border p-1 text-center">
                <span class="text-xs">-</span>
            </td>
            <td class="border p-1 text-center">
                <input type="number" name="catalog_products[${rowCount}][quantity]" value="${product.quantity ?? 0}" min="0" class="w-16 px-1 py-1 border rounded text-xs" required>
            </td>
            <td class="border p-1 text-center">
                <button type="button" class="remove-catalog-product bg-red-500 hover:bg-red-600 text-white px-2 py-1 rounded text-xs">🗑️</button>
            </td>
        `;
        
        // Dodaj ukryte pola dla innych danych
        const hiddenFields = [
            `<input type="hidden" name="catalog_products[${rowCount}][description]" value="${escapeHtml(product.description || '')}">`,
            `<input type="hidden" name="catalog_products[${rowCount}][location]" value="${escapeHtml(product.location || '')}">`,
            `<input type="hidden" name="catalog_products[${rowCount}][qr_code]" value="${escapeHtml(product.qr_code || '')}">`
        ].join('');
        
        newRow.innerHTML += hiddenFields;
        
        catalogTable.appendChild(newRow);
        
        // Ustaw dostawcę jeśli został zaimportowany
        if (product.supplier) {
            const supplierSelect = newRow.querySelector('select[name*="[supplier]"]');
            supplierSelect.value = product.supplier;
        }
        
        // Ustaw kategorię jeśli została zaimportowana
        if (product.category_id) {
            const categorySelect = newRow.querySelector('select[name*="[category_id]"]');
            categorySelect.value = product.category_id;
        }
        
        // Dodaj event listener do przycisku usuwania
        newRow.querySelector('.remove-catalog-product').addEventListener('click', function() {
            newRow.remove();
            updateCatalogRowNumbers();
        });
    }

    function updateCatalogRowNumbers() {
        const rows = document.querySelectorAll('#selected-products-table-inner tbody tr');
        rows.forEach((row, index) => {
            // Aktualizuj numery jeśli potrzeba
        });
    }
</script>

</body>
</html>
