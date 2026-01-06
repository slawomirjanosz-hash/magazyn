<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
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
                    <input
                        id="part-description"
                        name="description"
                        placeholder="Opis (opcjonalnie)"
                        class="border p-2 rounded w-full"
                    >
                </div>
                {{-- ILOŚĆ, CENA, WALUTA --}}
                <div class="flex gap-2">
                    <input
                        name="quantity"
                        type="number"
                        min="1"
                        value="1"
                        class="border p-2 rounded w-24"
                        required
                        placeholder="Ilość"
                    >
                    <input
                        id="part-net-price"
                        name="net_price"
                        type="number"
                        step="0.01"
                        min="0"
                        placeholder="Cena netto"
                        class="border p-2 rounded w-32"
                    >
                    <select
                        id="part-currency"
                        name="currency"
                        class="border p-2 rounded text-sm w-24"
                    >
                        <option value="PLN">PLN</option>
                        <option value="EUR">EUR</option>
                        <option value="$">$</option>
                    </select>
                </div>
                {{-- DOSTAWCA, KATEGORIA --}}
                <div class="flex gap-2">
                    <select
                        id="part-supplier"
                        name="supplier"
                        class="border p-2 rounded text-sm flex-1"
                    >
                        <option value="">- wybierz dostawcę -</option>
                        @foreach($suppliers as $s)
                            <option value="{{ $s->name }}">{{ $s->short_name ?? $s->name }}</option>
                        @endforeach
                    </select>
                    <select
                        name="category_id"
                        class="border p-2 rounded flex-1"
                        required
                    >
                        @foreach($categories as $c)
                            <option value="{{ $c->id }}">
                                {{ $c->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                {{-- PRZYCISK --}}
                <button
                    type="submit"
                    class="bg-green-500 hover:bg-green-600 text-white rounded px-4 py-2 mt-2"
                >
                    ➕ Dodaj
                </button>
                <div class="text-xs text-gray-500 mt-2 text-left">
                    Dodaje: {{ Auth::user()->name ?? 'Gość' }}
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

    {{-- SEKCJA: KATALOG PRODUKTÓW (ROZWIJALNA) --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="catalog-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Katalog Produktów</h3>
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
                    <button type="button" id="add-all-btn-inner" class="bg-green-500 hover:bg-green-600 text-white px-3 py-1 rounded text-xs">✅ Dodaj wszystkie</button>
                </div>
            </div>

            {{-- KATALOG PRODUKTÓW --}}
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
                        <th class="border p-2 text-center text-xs whitespace-nowrap min-w-[2.5rem] max-w-[4rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('quantity')">Stan <span class="align-middle ml-1 text-gray-400">↕</span></th>
                        <th class="border p-1 text-center text-xs whitespace-nowrap min-w-[4.5rem]" style="width: 6ch;">User</th>
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
                            <td class="border p-2 text-center text-xs {{ $p->quantity == 0 ? 'text-red-600 bg-red-50' : '' }}">{{ $p->quantity }}</td>
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
            } else {
                content.classList.remove('hidden');
                arrow.textContent = '▼';
            }
        });
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
            const checkboxes = selectedProductsTable.querySelectorAll('.selected-product-checkbox');
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
        if (Object.keys(selectedProducts).length === 0) {
            alert('Zaznacz przynajmniej jeden produkt');
            return;
        }

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

        // Dodaj wszystkie produkty
        let delay = 0;
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
                    // Zaznaczony produkt został dodany
                })
                .catch(err => {
                    console.error('Błąd:', err);
                });
            }, delay);
            delay += 300; // 300ms między żądaniami
        });

        // Wyczyść listę po chwili
        setTimeout(() => {
            selectedProducts = {};
            catalogCheckboxes.forEach(cb => cb.checked = false);
            updateSelectedProductsDisplay();
            
            // Zapamiętaj że katalog ma być otwarty
            localStorage.setItem('katalogOtwarty', 'true');
            window.location.reload();
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
});
</script>
</body>
</html>
