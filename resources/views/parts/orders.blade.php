<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Zamówienia</title>
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

@php
// Funkcja generująca podgląd nazwy zamówienia
function generateOrderNamePreview($settings) {
    if (!$settings) return 'Nie skonfigurowano';
    
    $parts = [];
    
    // Element 1
    if (isset($settings->element1_type) && $settings->element1_type !== 'empty') {
        $parts[] = generateElement($settings->element1_type, $settings->element1_value ?? null, $settings);
    }
    
    // Separator 1
    if (!empty($parts) && isset($settings->element2_type) && $settings->element2_type !== 'empty') {
        $parts[] = $settings->separator1 ?? '_';
    }
    
    // Element 2
    if (isset($settings->element2_type) && $settings->element2_type !== 'empty') {
        $parts[] = generateElement($settings->element2_type, $settings->element2_value ?? null, $settings);
    }
    
    // Separator 2
    if (!empty($parts) && isset($settings->element3_type) && $settings->element3_type !== 'empty') {
        $parts[] = $settings->separator2 ?? '_';
    }
    
    // Element 3
    if (isset($settings->element3_type) && $settings->element3_type !== 'empty') {
        $parts[] = generateElement($settings->element3_type, $settings->element3_value ?? null, $settings);
    }
    
    // Separator 3
    if (!empty($parts) && isset($settings->element4_type) && $settings->element4_type !== 'empty') {
        $parts[] = $settings->separator3 ?? '_';
    }
    
    // Element 4
    if (isset($settings->element4_type) && $settings->element4_type !== 'empty') {
        $parts[] = generateElement($settings->element4_type, null, $settings);
    }
    
    return implode('', array_filter($parts, fn($p) => $p !== null && $p !== ''));
}

function generateElement($type, $value, $settings) {
    switch($type) {
        case 'text':
            return $value ?? 'Tekst';
        case 'date':
            $format = $value ?? 'yyyy-mm-dd';
            $date = date('Y-m-d');
            if ($format === 'yyyymmdd') {
                return date('Ymd');
            }
            return date('Y-m-d');
        case 'time':
            $format = $value ?? 'hh-mm-ss';
            if ($format === 'hhmmss') {
                return date('His');
            } elseif ($format === 'hh-mm') {
                return date('H-i');
            } elseif ($format === 'hh') {
                return date('H');
            }
            return date('H-i-s');
        case 'number':
            $digits = $settings->element3_digits ?? 4;
            $start = $settings->start_number ?? 1;
            return str_pad($start, $digits, '0', STR_PAD_LEFT);
        case 'supplier_short_name':
            return 'DOSTAWCA';
        default:
            return '';
    }
}

$orderNamePreview = generateOrderNamePreview($orderSettings ?? null);
@endphp

{{-- MENU --}}
@include('parts.menu')

<div class="max-w-5xl mx-auto bg-white p-6 rounded shadow mt-6">
    <h2 class="text-xl font-bold mb-4">📦 Zamówienia</h2>

    {{-- SEKCJA: ZRÓB ZAMÓWIENIE (ROZWIJALNA) --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="create-order-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Zrób zamówienie</h3>
        </button>
        <div id="create-order-content" class="collapsible-content hidden p-6 border-t">
            {{-- PODSEKCJA: PRODUKTY DO ZAMÓWIENIA (COLLAPSIBLE) --}}
            <div class="mb-6 pb-6 border-b">
                <button type="button" id="selected-products-btn" class="collapsible-btn w-full flex items-center gap-2 px-0 py-2 cursor-pointer hover:bg-gray-50" data-target="selected-products-inner">
                    <span class="toggle-arrow text-lg">▶</span>
                    <h4 class="font-semibold text-sm">Produkty do zamówienia</h4>
                </button>
                <div id="selected-products-inner" class="collapsible-content hidden mt-4 p-4 bg-gray-50 rounded border border-gray-300">
                    <table id="selected-products-table-inner" class="w-full border border-collapse mb-4" style="font-size: 10px;">
                        <thead class="bg-blue-100">
                            <tr>
                                <th class="border p-1 text-center" style="width: 25px;"></th>
                                <th class="border p-1 text-left" style="white-space: nowrap;">Produkt</th>
                                <th class="border p-1 text-left" style="max-width: 150px;">Opis</th>
                                <th class="border p-1 text-center" style="width: 80px;">Dostawca</th>
                                <th class="border p-1 text-center" style="width: 70px;">Cena</th>
                                <th class="border p-1 text-center" style="width: 35px;">Stan</th>
                                <th class="border p-1 text-center" style="width: 40px;">Il.</th>
                                <th class="border p-1 text-center" style="width: 45px;">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    
                    <div class="mb-3">
                        <button type="button" id="remove-all-selected-btn-inner" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-xs mr-2">🗑️ Wyczyść listę</button>
                        <button type="button" id="create-order-btn-inner" class="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1 rounded text-xs">📦 Utwórz zamówienie</button>
                    </div>
                    
                    @if(isset($orderSettings))
                    <div class="p-2 bg-blue-50 border border-blue-200 rounded inline-flex items-center gap-2 mb-3">
                        <label for="order-name-input" class="text-xs font-semibold text-gray-700 whitespace-nowrap">Nazwa zamówienia:</label>
                        <input type="text" id="order-name-input" value="{{ $orderNamePreview }}" class="px-2 py-1 border border-blue-300 rounded font-mono text-sm text-blue-700 focus:outline-none focus:ring-2 focus:ring-blue-500" style="min-width: 300px;">
                    </div>
                    @endif
                    
                    {{-- Dodatkowe pola zamówienia --}}
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div class="flex items-center gap-2">
                            <label for="supplier-offer-number" class="text-xs font-semibold text-gray-700 whitespace-nowrap" style="width: 130px;">Oferta dostawcy nr:</label>
                            <input type="text" id="supplier-offer-number" value="e-mail" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
                        </div>
                        
                        <div class="flex items-center gap-2">
                            <label for="payment-method" class="text-xs font-semibold text-gray-700 whitespace-nowrap">Forma płatności:</label>
                            <select id="payment-method" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option value="">-- wybierz --</option>
                                <option value="gotówka">Gotówka</option>
                                <option value="przelew" selected>Przelew</option>
                                <option value="przedpłata">Przedpłata</option>
                            </select>
                            
                            <div id="payment-days-container" class="flex items-center gap-2 ml-2">
                                <input type="text" id="payment-days" value="14 dni" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" style="width: 100px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="grid grid-cols-2 gap-4 mb-3">
                        <div class="flex items-center gap-2">
                            <label for="delivery-time" class="text-xs font-semibold text-gray-700 whitespace-nowrap" style="width: 130px;">Termin dostawy:</label>
                            <select id="delivery-time" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 flex-1">
                                <option value="">-- wybierz --</option>
                                <option value="3 dni">3 dni</option>
                                <option value="7 dni">7 dni</option>
                                <option value="14 dni" selected>14 dni</option>
                                <option value="1 miesiąc">1 miesiąc</option>
                                <option value="3 miesiące">3 miesiące</option>
                                <option value="ręcznie">Dodaj ręcznie</option>
                            </select>
                            
                            <div id="delivery-time-custom-container" class="hidden items-center gap-2 ml-2">
                                <input type="text" id="delivery-time-custom" placeholder="Wpisz termin" class="px-2 py-1 border border-gray-300 rounded text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" style="width: 150px;">
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            {{-- PODSEKCJA: KATALOG PRODUKTÓW (COLLAPSIBLE) --}}
            <div class="mb-6">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 px-0 py-2 cursor-pointer hover:bg-gray-50" data-target="catalog-content">
                    <span class="toggle-arrow text-lg">▶</span>
                    <h4 class="font-semibold text-sm">Katalog Produktów</h4>
                </button>
                <div id="catalog-content" class="collapsible-content hidden mt-4 p-4 bg-gray-50 rounded border border-gray-300">
                    <table class="w-full border border-collapse text-xs">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-2 text-center text-xs" style="width: 40px;"></th>
                                <th class="border p-2 text-left text-xs whitespace-nowrap min-w-[16rem] max-w-[24rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('name')">Produkty <span class="align-middle ml-1 text-gray-400">↕</span></th>
                                <th class="border p-2 text-left text-xs whitespace-nowrap min-w-[12rem] max-w-[20rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('description')">Opis <span class="align-middle ml-1 text-gray-400">↕</span></th>
                                <th class="border p-2 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem] cursor-pointer hover:bg-gray-200" onclick="sortTable('supplier')">Dostawca <span class="align-middle ml-1 text-gray-400">↕</span></th>
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
                                        <input type="checkbox" class="catalog-checkbox w-4 h-4 cursor-pointer" data-part-name="{{ $p->name }}" data-part-desc="{{ $p->description ?? '' }}" data-part-supplier="{{ $p->supplier ?? '' }}" data-part-supplier-short="{{ $supplierShort }}" data-part-price="{{ $p->net_price ?? '' }}" data-part-currency="{{ $p->currency ?? 'PLN' }}" data-part-qty="{{ $p->quantity }}>">
                                    </td>
                                    <td class="border p-2">{{ $p->name }}</td>
                                    <td class="border p-2 text-xs text-gray-700">{{ $p->description ?? '-' }}</td>
                                    <td class="border p-2 text-gray-700 text-xs text-center"><span style="font-size: 10px;">{{ $supplierShort ?: '-' }}</span></td>
                                    <td class="border p-2 text-center text-xs">
                                            @if($p->net_price)
                                                {{ $p->net_price }} <span class="text-xs">{{ $p->currency ?? 'PLN' }}</span>
                                            @else
                                                -
                                            @endif
                                    </td>
                                    <td class="border p-2">{{ $p->category->name ?? '-' }}</td>
                                    <td class="border p-2 text-center font-bold {{ $p->quantity == 0 ? 'text-red-600 bg-red-50' : '' }}">{{ $p->quantity }}</td>
                                    <td class="border p-2 text-center text-xs text-gray-600">{{ $p->lastModifiedBy ? $p->lastModifiedBy->short_name : '-' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="border p-2 text-center text-gray-400 italic" colspan="8">Brak produktów w katalogu</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- SEKCJA: WYSTAWIONE ZAMÓWIENIA (ROZWIJALNA) --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="receive-order-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Wystawione zamówienia</h3>
        </button>
        <div id="receive-order-content" class="collapsible-content hidden p-6 border-t">
            {{-- SEKCJA: PODGLĄD ZAMÓWIENIA --}}
            <div id="order-preview-section" class="hidden mb-6 p-4 bg-blue-50 border border-blue-200 rounded">
                <div class="flex justify-between items-start mb-4">
                    <h4 class="text-lg font-bold text-blue-900">Podgląd zamówienia</h4>
                    <button id="close-preview-btn" class="text-red-500 hover:text-red-700 font-bold">✕ Zamknij</button>
                </div>
                
                <div id="order-preview-content" class="space-y-3">
                    <!-- Zawartość będzie wstawiana dynamicznie przez JavaScript -->
                </div>
                
                <div class="mt-4 flex gap-2 flex-wrap">
                    <button id="receive-order-btn" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded font-semibold">
                        ✅ Przyjmij zamówienie
                    </button>
                    <button id="preview-generate-word-btn" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded font-semibold">
                        📄 Pobierz do Word
                    </button>
                    <button id="preview-generate-pdf-btn" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded font-semibold">
                        📄 Pobierz do PDF
                    </button>
                    <button id="preview-edit-order-btn" class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded font-semibold">
                        ✏️ Edytuj
                    </button>
                    <button id="preview-delete-order-btn" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded font-semibold">
                        🗑️ Usuń
                    </button>
                </div>
            </div>

            {{-- PODSEKCJA: TABELA ZAMÓWIEŃ --}}
            <div class="mb-6">
                <div class="w-full flex items-center gap-2 px-0 py-2">
                    <h4 class="font-semibold text-sm">Tabela zamówień:</h4>
                </div>
                <div id="issued-orders-content" class="mt-4 p-4 bg-gray-50 rounded border border-gray-300">
                    <div class="flex justify-end mb-2">
                        <button id="delete-selected-orders-btn" class="bg-red-500 hover:bg-red-600 text-white px-3 py-1 rounded text-sm hidden">
                            🗑️ Usuń zaznaczone
                        </button>
                    </div>
                    <table class="w-full border border-collapse text-xs">
                        <thead class="bg-green-100">
                            <tr>
                                <th class="border p-2 text-center" style="width: 40px;">
                                    <input type="checkbox" id="select-all-orders" class="w-4 h-4 cursor-pointer">
                                </th>
                                <th class="border p-2 text-left">Numer zamówienia</th>
                                <th class="border p-2 text-left" style="min-width: 200px;">Dostawca</th>
                                <th class="border p-2 text-center" style="width: 90px;">Data</th>
                                <th class="border p-2 text-center" style="width: 55px;">Godz.</th>
                                <th class="border p-2 text-center" style="width: 180px;">Akcje</th>
                            </tr>
                        </thead>
                        <tbody id="issued-orders-tbody">
                            @forelse($orders ?? [] as $order)
                                <tr>
                                    <td class="border p-2 text-center">
                                        <input type="checkbox" class="order-checkbox w-4 h-4 cursor-pointer" data-order-id="{{ $order->id }}">
                                    </td>
                                    <td class="border p-2 font-mono">{{ $order->order_number }}</td>
                                    <td class="border p-2">{{ $order->supplier ?? '-' }}</td>
                                    <td class="border p-2 text-center">{{ $order->issued_at->format('Y-m-d') }}</td>
                                    <td class="border p-2 text-center">{{ $order->issued_at->format('H:i') }}</td>
                                    <td class="border p-2 text-center">
                                        <div class="flex items-center justify-center gap-1 flex-wrap">
                                            <button class="bg-blue-100 hover:bg-blue-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center preview-order-btn" 
                                                    title="Podgląd zamówienia"
                                                    data-order-id="{{ $order->id }}"
                                                    data-order-number="{{ $order->order_number }}"
                                                    data-order-supplier="{{ $order->supplier ?? '' }}"
                                                    data-order-status="{{ $order->status }}"
                                                    data-order-issued="{{ $order->issued_at->format('Y-m-d H:i:s') }}"
                                                    data-order-user="{{ $order->user->name ?? 'N/A' }}"
                                                    data-order-received="{{ $order->received_at ? $order->received_at->format('Y-m-d H:i:s') : '' }}"
                                                    data-order-received-by="{{ $order->receivedBy->name ?? '' }}"
                                                    data-order-products='@json($order->products)'
                                                    data-order-delivery-time="{{ $order->delivery_time ?? '' }}"
                                                    data-order-supplier-offer="{{ $order->supplier_offer_number ?? '' }}"
                                                    data-order-payment-method="{{ $order->payment_method ?? '' }}"
                                                    data-order-payment-days="{{ $order->payment_days ?? '' }}">
                                                <span role="img" aria-label="Podgląd" class="pointer-events-none">👁️</span>
                                            </button>
                                            <button class="bg-purple-100 hover:bg-purple-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center generate-word-btn" 
                                                    title="Generuj dokument Word"
                                                    data-order-id="{{ $order->id }}"
                                                    data-order-number="{{ $order->order_number }}">
                                                <span role="img" aria-label="Generuj Word" class="pointer-events-none">📄</span>
                                            </button>
                                            @if($order->status !== 'received')
                                            <button class="bg-green-100 hover:bg-green-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center edit-order-btn" 
                                                    title="Edytuj zamówienie"
                                                    data-order-id="{{ $order->id }}"
                                                    data-order-number="{{ $order->order_number }}"
                                                    data-order-products='@json($order->products)'>
                                                <span role="img" aria-label="Edytuj" class="pointer-events-none">✏️</span>
                                            </button>
                                            @endif
                                            @if($order->status !== 'received' || auth()->user()->is_admin)
                                            <button class="bg-red-100 hover:bg-red-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center delete-order-btn" 
                                                    title="Usuń zamówienie"
                                                    data-order-id="{{ $order->id }}"
                                                    data-order-number="{{ $order->order_number }}">
                                                <span role="img" aria-label="Usuń" class="pointer-events-none">🗑️</span>
                                            </button>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr id="no-orders-row">
                                    <td class="border p-2 text-center text-gray-400 italic" colspan="6">Brak wystawionych zamówień</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    console.log('Orders page JavaScript loaded');
    
    // Funkcja wyświetlania powiadomień
    function showNotification(message, type = 'success') {
        const existingNotification = document.getElementById('notification-bar');
        if (existingNotification) {
            existingNotification.remove();
        }
        
        const notification = document.createElement('div');
        notification.id = 'notification-bar';
        notification.className = `fixed top-4 right-4 px-6 py-3 rounded shadow-lg z-50 transition-opacity duration-500 ${type === 'success' ? 'bg-green-500 text-white' : 'bg-red-500 text-white'}`;
        notification.textContent = message;
        document.body.appendChild(notification);
        
        setTimeout(() => {
            notification.style.opacity = '0';
            setTimeout(() => notification.remove(), 500);
        }, 4000);
    }
    
    // Expose showNotification globally
    window.showNotification = showNotification;
    
    // Accordion - Collapsible sekcje
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

    // Multi-select checkboxes w katalogu
    const catalogCheckboxes = document.querySelectorAll('.catalog-checkbox');
    const selectedProductsBtn = document.getElementById('selected-products-btn');
    const selectedProductsContent = document.getElementById('selected-products-inner');
    const selectedProductsTableEl = document.getElementById('selected-products-table-inner');
    const selectedProductsTable = selectedProductsTableEl ? selectedProductsTableEl.querySelector('tbody') : null;
    const removeAllBtnInner = document.getElementById('remove-all-selected-btn-inner');
    const createOrderBtn = document.getElementById('create-order-btn-inner');
    const orderNameInput = document.getElementById('order-name-input');
    
    console.log('Create order button element:', createOrderBtn);
    console.log('Remove all button element:', removeAllBtnInner);
    
    const paymentMethodSelect = document.getElementById('payment-method');
    const paymentDaysContainer = document.getElementById('payment-days-container');
    const deliveryTimeSelect = document.getElementById('delivery-time');
    const deliveryTimeCustomContainer = document.getElementById('delivery-time-custom-container');
    let selectedProducts = {};
    let originalOrderName = orderNameInput ? orderNameInput.value : '';
    
    // Obsługa pokazywania/ukrywania pola dni płatności
    if (paymentMethodSelect && paymentDaysContainer) {
        paymentMethodSelect.addEventListener('change', function() {
            if (this.value === 'przelew') {
                paymentDaysContainer.classList.remove('hidden');
                paymentDaysContainer.classList.add('flex');
            } else {
                paymentDaysContainer.classList.add('hidden');
                paymentDaysContainer.classList.remove('flex');
            }
        });
    }
    
    // Obsługa pokazywania/ukrywania pola ręcznego terminu dostawy
    if (deliveryTimeSelect && deliveryTimeCustomContainer) {
        deliveryTimeSelect.addEventListener('change', function() {
            if (this.value === 'ręcznie') {
                deliveryTimeCustomContainer.classList.remove('hidden');
                deliveryTimeCustomContainer.classList.add('flex');
            } else {
                deliveryTimeCustomContainer.classList.add('hidden');
                deliveryTimeCustomContainer.classList.remove('flex');
            }
        });
    }

    function updateSelectedProductsDisplay() {
        if (!selectedProductsTable) return;
        
        selectedProductsTable.innerHTML = '';
        
        Object.entries(selectedProducts).forEach(([name, data]) => {
            const row = document.createElement('tr');
            const stockClass = data.stockQuantity === 0 ? 'text-red-600 bg-red-50 font-bold' : 'text-blue-600 font-bold';
            
            // Generuj opcje dla selecta dostawców
            let supplierOptions = '<option value="">- wybierz -</option>';
            @foreach($suppliers as $s)
                supplierOptions += `<option value="{{ $s->name }}" ${data.supplier === '{{ $s->name }}' ? 'selected' : ''}>{{ $s->short_name ?? $s->name }}</option>`;
            @endforeach
            
            row.innerHTML = `
                <td class="border p-1 text-center">
                    <input type="checkbox" checked class="w-4 h-4 cursor-pointer selected-product-checkbox" data-product-name="${name}">
                </td>
                <td class="border p-1">${name}</td>
                <td class="border p-1 text-xs text-gray-600" style="max-width: 150px; word-wrap: break-word;">${data.description || ''}</td>
                <td class="border p-1">
                    <select class="w-16 px-1 py-0.5 border rounded text-xs product-supplier" data-product-name="${name}">
                        ${supplierOptions}
                    </select>
                </td>
                <td class="border p-1 text-center">
                    <div class="flex items-center gap-1 justify-center">
                        <input type="text" value="${data.price || ''}" maxlength="9" class="w-16 px-1 py-0.5 border rounded text-xs text-center product-price" data-product-name="${name}" placeholder="0.00">
                        <select class="w-12 px-1 py-0.5 border rounded text-xs product-currency" data-product-name="${name}">
                            <option value="PLN" ${data.currency === 'PLN' ? 'selected' : ''}>PLN</option>
                            <option value="EUR" ${data.currency === 'EUR' ? 'selected' : ''}>EUR</option>
                            <option value="USD" ${data.currency === 'USD' ? 'selected' : ''}>USD</option>
                        </select>
                    </div>
                </td>
                <td class="border p-1 text-center ${stockClass}">${data.stockQuantity}</td>
                <td class="border p-1 text-center">
                    <input type="number" min="1" value="${data.orderQuantity}" size="3" class="px-1 py-0.5 border rounded text-center text-xs order-quantity-input" data-product-name="${name}">
                </td>
                <td class="border p-1 text-center">
                    <button class="bg-red-500 hover:bg-red-600 text-white px-1 py-0 rounded text-xs remove-product-btn" data-product-name="${name}">🗑️</button>
                </td>
            `;
            selectedProductsTable.appendChild(row);
        });

        // Event listeners dla nowych elementów
        document.querySelectorAll('.selected-product-checkbox').forEach(cb => {
            cb.addEventListener('change', function() {
                const productName = this.getAttribute('data-product-name');
                if (!this.checked) {
                    delete selectedProducts[productName];
                    const catalogCb = document.querySelector(`.catalog-checkbox[data-part-name="${productName}"]`);
                    if (catalogCb) catalogCb.checked = false;
                    updateSelectedProductsDisplay();
                }
            });
        });

        document.querySelectorAll('.order-quantity-input').forEach(input => {
            input.addEventListener('change', function() {
                const productName = this.getAttribute('data-product-name');
                const newQty = parseInt(this.value) || 1;
                if (selectedProducts[productName]) {
                    selectedProducts[productName].orderQuantity = newQty;
                }
            });
        });

        document.querySelectorAll('.product-supplier').forEach(select => {
            select.addEventListener('change', function() {
                const productName = this.getAttribute('data-product-name');
                if (selectedProducts[productName]) {
                    selectedProducts[productName].supplier = this.value;
                }
            });
        });

        document.querySelectorAll('.product-price').forEach(input => {
            input.addEventListener('change', function() {
                const productName = this.getAttribute('data-product-name');
                if (selectedProducts[productName]) {
                    selectedProducts[productName].price = this.value.replace(',', '.');
                }
            });
        });

        document.querySelectorAll('.product-currency').forEach(select => {
            select.addEventListener('change', function() {
                const productName = this.getAttribute('data-product-name');
                if (selectedProducts[productName]) {
                    selectedProducts[productName].currency = this.value;
                }
            });
        });

        document.querySelectorAll('.remove-product-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const productName = this.getAttribute('data-product-name');
                delete selectedProducts[productName];
                const catalogCb = document.querySelector(`.catalog-checkbox[data-part-name="${productName}"]`);
                if (catalogCb) catalogCb.checked = false;
                updateSelectedProductsDisplay();
            });
        });

        // Podświetl nagłówek na zielono jeśli są wybrane produkty
        if (selectedProductsBtn) {
            if (Object.keys(selectedProducts).length > 0) {
                selectedProductsBtn.classList.add('bg-green-100');
            } else {
                selectedProductsBtn.classList.remove('bg-green-100');
            }
        }
    }

    catalogCheckboxes.forEach(cb => {
        cb.addEventListener('change', function() {
            const productName = this.getAttribute('data-part-name');
            const productDesc = this.getAttribute('data-part-desc');
            const productSupplier = this.getAttribute('data-part-supplier');
            const productPrice = this.getAttribute('data-part-price');
            const productCurrency = this.getAttribute('data-part-currency');
            const productQty = parseInt(this.getAttribute('data-part-qty')) || 0;
            
            if (this.checked) {
                selectedProducts[productName] = {
                    description: productDesc,
                    supplier: productSupplier,
                    price: productPrice || '',
                    currency: productCurrency || 'PLN',
                    stockQuantity: productQty,
                    orderQuantity: 1
                };
            } else {
                delete selectedProducts[productName];
            }
            
            updateSelectedProductsDisplay();
        });
    });

    if (removeAllBtnInner) {
        removeAllBtnInner.addEventListener('click', function() {
            selectedProducts = {};
            catalogCheckboxes.forEach(cb => cb.checked = false);
            updateSelectedProductsDisplay();
        });
    }

    if (createOrderBtn) {
        console.log('Attaching click event to create order button');
        createOrderBtn.addEventListener('click', function() {
            console.log('Create order button clicked!');
            console.log('Selected products:', selectedProducts);
            
            if (Object.keys(selectedProducts).length === 0) {
                alert('Wybierz produkty do zamówienia');
                return;
            }
        
            const orderName = document.getElementById('order-name-input').value;
            if (!orderName || orderName.trim() === '') {
                alert('Wprowadź nazwę zamówienia');
                return;
            }
        
            // Przygotuj dane produktów
            const productsData = Object.entries(selectedProducts).map(([name, data]) => ({
                name: name,
                supplier: data.supplier || '',
                price: data.price || '',
                currency: data.currency || 'PLN',
                quantity: data.orderQuantity
            }));
        
            // Pobierz pierwszego dostawcę (dla nazwy zamówienia)
            const firstSupplier = productsData.find(p => p.supplier)?.supplier || '';
        
            // Pobierz dane z formularza
            const supplierOfferNumber = document.getElementById('supplier-offer-number').value;
            const paymentMethod = document.getElementById('payment-method').value;
            const paymentDays = document.getElementById('payment-days').value;
            const deliveryTime = document.getElementById('delivery-time').value;
            const deliveryTimeCustom = document.getElementById('delivery-time-custom').value;
        
            // Ustal ostateczny termin dostawy
            const finalDeliveryTime = deliveryTime === 'ręcznie' ? deliveryTimeCustom : deliveryTime;
        
            // Sprawdź czy nazwa została zmieniona ręcznie
            const wasManuallyChanged = orderName !== originalOrderName;
        
            // Wyślij żądanie do serwera
            fetch('{{ route('magazyn.order.create') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    order_name: orderName,
                    products: productsData,
                    supplier: firstSupplier,
                    supplier_offer_number: supplierOfferNumber,
                    payment_method: paymentMethod,
                    payment_days: paymentDays,
                    delivery_time: finalDeliveryTime,
                    increment_counter: !wasManuallyChanged
                })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Błąd tworzenia zamówienia');
                }
                return response.json();
            })
            .then(data => {
                // Pokaż zielony pasek z komunikatem
                const orderCount = data.orders ? data.orders.length : 1;
                showNotification(`Wygenerowano ${orderCount} ${orderCount === 1 ? 'zamówienie' : (orderCount < 5 ? 'zamówienia' : 'zamówień')}`, 'success');
            
                // Dodaj zamówienia do tabeli wystawionych zamówień
                const issuedOrdersTbody = document.getElementById('issued-orders-tbody');
                const noOrdersRow = document.getElementById('no-orders-row');
            
                // Usuń wiersz "Brak wystawionych zamówień" jeśli istnieje
                if (noOrdersRow) {
                    noOrdersRow.remove();
                }
            
                // Dla każdego utworzonego zamówienia
                data.orders.forEach(order => {
                    // Pobierz aktualną datę i godzinę
                    const issuedDate = new Date(order.issued_at);
                    const dateStr = issuedDate.getFullYear() + '-' + 
                                   String(issuedDate.getMonth() + 1).padStart(2, '0') + '-' + 
                                   String(issuedDate.getDate()).padStart(2, '0');
                    const timeStr = String(issuedDate.getHours()).padStart(2, '0') + ':' + 
                                   String(issuedDate.getMinutes()).padStart(2, '0');
                
                    // Dostawca - użyj pełnej nazwy
                    let supplierDisplay = order.supplier || '-';
                
                    // Dodaj nowy wiersz na początku tabeli
                    const newRow = document.createElement('tr');
                    const currentUser = '{{ auth()->user()->name ?? "N/A" }}';
                
                    newRow.innerHTML = `
                        <td class="border p-2 text-center">
                            <input type="checkbox" class="order-checkbox w-4 h-4 cursor-pointer" data-order-id="${order.id}">
                        </td>
                        <td class="border p-2 font-mono">${order.order_number}</td>
                        <td class="border p-2">${supplierDisplay}</td>
                        <td class="border p-2 text-center">${dateStr}</td>
                        <td class="border p-2 text-center">${timeStr}</td>
                        <td class="border p-2 text-center">
                            <div class="flex items-center justify-center gap-1 flex-wrap">
                                <button class="bg-blue-100 hover:bg-blue-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center preview-order-btn" 
                                    title="Podgląd zamówienia"
                                    data-order-id="${order.id}"
                                    data-order-number="${order.order_number}"
                                    data-order-supplier="${order.supplier || ''}"
                                    data-order-status="pending"
                                    data-order-issued="${order.issued_at}"
                                    data-order-user="${currentUser}"
                                    data-order-products='${JSON.stringify(order.products)}'
                                    data-order-delivery-time="${order.delivery_time || ''}"
                                    data-order-supplier-offer="${order.supplier_offer_number || ''}"
                                    data-order-payment-method="${order.payment_method || ''}"
                                    data-order-payment-days="${order.payment_days || ''}">
                                    <span role="img" aria-label="Podgląd" class="pointer-events-none">👁️</span>
                                </button>
                                <button class="bg-purple-100 hover:bg-purple-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center generate-word-btn" 
                                    title="Generuj dokument Word"
                                    data-order-id="${order.id}"
                                    data-order-number="${order.order_number}">
                                    <span role="img" aria-label="Generuj Word" class="pointer-events-none">📄</span>
                                </button>
                                <button class="bg-green-100 hover:bg-green-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center edit-order-btn" 
                                    title="Edytuj zamówienie"
                                    data-order-id="${order.id}"
                                    data-order-number="${order.order_number}"
                                    data-order-products='${JSON.stringify(order.products)}'>
                                    <span role="img" aria-label="Edytuj" class="pointer-events-none">✏️</span>
                                </button>
                                <button class="bg-red-100 hover:bg-red-200 text-gray-800 px-2 py-1 rounded text-xs inline-flex items-center justify-center delete-order-btn" 
                                    title="Usuń zamówienie"
                                    data-order-id="${order.id}"
                                    data-order-number="${order.order_number}">
                                    <span role="img" aria-label="Usuń" class="pointer-events-none">🗑️</span>
                                </button>
                            </div>
                        </td>
                    `;
                    issuedOrdersTbody.insertBefore(newRow, issuedOrdersTbody.firstChild);
                
                    // Dodaj event listenery do nowych przycisków
                    newRow.querySelector('.preview-order-btn').addEventListener('click', function() {
                        // Użyj tego samego kodu co dla istniejących przycisków
                        const btn = this;
                        const orderId = btn.getAttribute('data-order-id');
                        const orderNumber = btn.getAttribute('data-order-number');
                        const supplier = btn.getAttribute('data-order-supplier');
                        const status = btn.getAttribute('data-order-status');
                        const issuedAt = btn.getAttribute('data-order-issued');
                        const userName = btn.getAttribute('data-order-user');
                        const productsJson = btn.getAttribute('data-order-products');
                        const deliveryTime = btn.getAttribute('data-order-delivery-time');
                        const supplierOffer = btn.getAttribute('data-order-supplier-offer');
                        const paymentMethod = btn.getAttribute('data-order-payment-method');
                        const paymentDays = btn.getAttribute('data-order-payment-days');
                    
                        currentPreviewOrderId = orderId;
                    
                        try {
                            const products = JSON.parse(productsJson);
                        
                            let html = `
                                <div class="grid grid-cols-2 gap-4 mb-4">
                                    <div><strong>Numer zamówienia:</strong> ${orderNumber}</div>
                                    <div><strong>Status:</strong> <span class="px-2 py-1 rounded ${status === 'received' ? 'bg-green-200' : 'bg-yellow-200'}">${status === 'received' ? 'Przyjęte' : 'Oczekujące'}</span></div>
                                    <div><strong>Dostawca:</strong> ${supplier || '-'}</div>
                                    <div><strong>Data wystawienia:</strong> ${issuedAt}</div>
                                    <div><strong>Zamówił:</strong> ${userName}</div>
                                </div>
                            `;
                            
                            // Dodaj informacje o zamówieniu
                            if (deliveryTime || supplierOffer || paymentMethod) {
                                html += `<div class="grid grid-cols-3 gap-4 mb-4 p-2 bg-blue-50 rounded">`;
                                if (deliveryTime) {
                                    html += `<div><strong>Termin dostawy:</strong> ${deliveryTime}</div>`;
                                }
                                if (supplierOffer) {
                                    html += `<div><strong>Oferta dostawcy nr:</strong> ${supplierOffer}</div>`;
                                }
                                if (paymentMethod) {
                                    let paymentText = paymentMethod;
                                    if (paymentMethod === 'przelew' && paymentDays) {
                                        paymentText += ` (${paymentDays})`;
                                    }
                                    html += `<div><strong>Forma płatności:</strong> ${paymentText}</div>`;
                                }
                                html += `</div>`;
                            }
                            
                            html += `
                                <h5 class="font-bold mb-2">Produkty:</h5>
                                <table class="w-full border border-collapse text-xs">
                                    <thead class="bg-gray-200">
                                        <tr>
                                        <th class="border p-1 text-left">Produkt</th>
                                        <th class="border p-1 text-left">Dostawca</th>
                                        <th class="border p-1 text-center">Ilość</th>
                                        <th class="border p-1 text-center">Cena netto</th>
                                    </tr>
                                </thead>
                                <tbody>
                        `;
                        
                        products.forEach(product => {
                            const priceDisplay = product.price ? `${product.price} ${product.currency || 'PLN'}` : '-';
                            html += `
                                <tr>
                                    <td class="border p-1">${product.name}</td>
                                    <td class="border p-1">${product.supplier || '-'}</td>
                                    <td class="border p-1 text-center">${product.quantity}</td>
                                    <td class="border p-1 text-center">${priceDisplay}</td>
                                </tr>
                            `;
                        });
                        
                            html += `
                                    </tbody>
                                </table>
                            `;
                        
                            document.getElementById('order-preview-content').innerHTML = html;
                            document.getElementById('receive-order-btn').style.display = status === 'received' ? 'none' : 'block';
                            document.getElementById('preview-edit-order-btn').style.display = status === 'received' ? 'none' : 'block';
                            document.getElementById('preview-delete-order-btn').style.display = status === 'received' ? 'none' : 'block';
                            document.getElementById('order-preview-section').classList.remove('hidden');
                        
                            const receiveSection = document.getElementById('receive-order-content');
                            if (receiveSection.classList.contains('hidden')) {
                                const receiveBtn = document.querySelector('[data-target="receive-order-content"]');
                                if (receiveBtn) {
                                    receiveBtn.click();
                                }
                            }
                        } catch (error) {
                            console.error('Błąd parsowania produktów:', error);
                            alert('Błąd wyświetlania podglądu zamówienia');
                        }
                    });
                    
                    newRow.querySelector('.generate-word-btn').addEventListener('click', handleGenerateWord);
                    newRow.querySelector('.edit-order-btn').addEventListener('click', handleEditOrder);
                    newRow.querySelector('.delete-order-btn').addEventListener('click', handleDeleteOrder);
                });
            
                // Odśwież nazwę zamówienia
                if (!wasManuallyChanged) {
                    // Jeśli nie było zmiany ręcznej, pobierz nową nazwę (licznik już zwiększony)
                    fetch('{{ route('magazyn.order.nextName') }}?supplier=' + encodeURIComponent(firstSupplier))
                        .then(response => response.json())
                    .then(data => {
                        orderNameInput.value = data.order_name;
                        originalOrderName = data.order_name;
                    })
                    .catch(err => console.error('Błąd aktualizacji nazwy:', err));
                } else {
                    // Jeśli była zmiana ręczna, wróć do oryginalnej nazwy (BEZ zwiększania licznika)
                    orderNameInput.value = originalOrderName;
                }
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas tworzenia zamówienia');
            });
        });
    }
    
    // Funkcja obsługi generowania Worda
    function handleGenerateWord(e) {
        const btn = e.currentTarget;
        const orderId = btn.getAttribute('data-order-id');
        const orderNumber = btn.getAttribute('data-order-number');
        
        if (!orderId) {
            alert('Brak ID zamówienia');
            return;
        }
        
        // Wyślij żądanie do serwera
        window.location.href = `/magazyn/zamowienia/${orderId}/generate-word`;
    }
    
    // Funkcja obsługi edycji zamówienia
    function handleEditOrder(e) {
        const btn = e.currentTarget;
        const orderNumber = btn.getAttribute('data-order-number');
        const productsJson = btn.getAttribute('data-order-products');
        
        if (!productsJson) {
            alert('Brak danych produktów dla tego zamówienia');
            return;
        }
        
        try {
            const products = JSON.parse(productsJson);
            
            // Wyczyść obecne produkty
            selectedProducts = {};
            catalogCheckboxes.forEach(cb => cb.checked = false);
            
            // Załaduj produkty z zamówienia
            products.forEach(product => {
                // Sprawdź czy produkt istnieje w katalogu i pobierz jego stan
                const catalogCb = document.querySelector(`.catalog-checkbox[data-part-name="${product.name}"]`);
                const stockQuantity = catalogCb ? parseInt(catalogCb.getAttribute('data-part-qty')) || 0 : 0;
                const description = catalogCb ? catalogCb.getAttribute('data-part-desc') : '';
                const price = catalogCb ? catalogCb.getAttribute('data-part-price') : (product.price || '');
                const currency = catalogCb ? catalogCb.getAttribute('data-part-currency') : (product.currency || 'PLN');
                
                selectedProducts[product.name] = {
                    description: description,
                    supplier: product.supplier || '',
                    price: price,
                    currency: currency,
                    stockQuantity: stockQuantity,
                    orderQuantity: product.quantity
                };
                
                // Zaznacz checkbox w katalogu jeśli produkt istnieje
                if (catalogCb) {
                    catalogCb.checked = true;
                }
            });
            
            // Odśwież tabelę wybranych produktów
            updateSelectedProductsDisplay();
            
            // Ustaw nazwę zamówienia
            orderNameInput.value = orderNumber;
            originalOrderName = orderNumber;
            
            // Najpierw rozwiń sekcję "Zrób zamówienie" jeśli jest zwinięta
            const createOrderSection = document.getElementById('create-order-content');
            if (createOrderSection && createOrderSection.classList.contains('hidden')) {
                const createOrderBtn = document.querySelector('[data-target="create-order-content"]');
                if (createOrderBtn) {
                    createOrderBtn.click();
                }
            }
            
            // Rozwiń sekcję "Produkty do zamówienia" jeśli jest zwinięta
            const productsSection = document.getElementById('selected-products-inner');
            if (productsSection && productsSection.classList.contains('hidden')) {
                document.getElementById('selected-products-btn').click();
            }
            
            // Przewiń do sekcji produktów z małym opóźnieniem żeby sekcje zdążyły się rozwinąć
            setTimeout(() => {
                productsSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
            }, 100);
            
        } catch (err) {
            console.error('Błąd parsowania produktów:', err);
            alert('Błąd podczas ładowania danych zamówienia');
        }
    }
    
    // Dodaj event listenery do istniejących przycisków
    document.querySelectorAll('.generate-word-btn').forEach(btn => {
        btn.addEventListener('click', handleGenerateWord);
    });
    
    document.querySelectorAll('.edit-order-btn').forEach(btn => {
        btn.addEventListener('click', handleEditOrder);
    });
    
    // Funkcja obsługi usuwania zamówienia
    function handleDeleteOrder(e) {
        const btn = e.currentTarget;
        const orderId = btn.getAttribute('data-order-id');
        const orderNumber = btn.getAttribute('data-order-number');
        
        if (!confirm(`Czy na pewno chcesz usunąć zamówienie "${orderNumber}"?`)) {
            return;
        }
        
        fetch(`/magazyn/zamowienia/${orderId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Błąd usuwania zamówienia');
            }
            return response.json();
        })
        .then(data => {
            // Usuń wiersz z tabeli
            const row = btn.closest('tr');
            row.remove();
            
            // Sprawdź czy tabela jest pusta i dodaj wiersz "Brak zamówień"
            const tbody = document.getElementById('issued-orders-tbody');
            if (tbody.children.length === 0) {
                const emptyRow = document.createElement('tr');
                emptyRow.id = 'no-orders-row';
                emptyRow.innerHTML = '<td class="border p-2 text-center text-gray-400 italic" colspan="5">Brak wystawionych zamówień</td>';
                tbody.appendChild(emptyRow);
            }
        })
        .catch(error => {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas usuwania zamówienia');
        });
    }
    
    // Dodaj event listenery do istniejących przycisków usuwania
    document.querySelectorAll('.delete-order-btn').forEach(btn => {
        btn.addEventListener('click', handleDeleteOrder);
    });

    // Funkcja aktualizująca widoczność przycisku "Usuń zaznaczone"
    function updateDeleteButtonVisibility() {
        const selectedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
        const deleteBtn = document.getElementById('delete-selected-orders-btn');
        if (deleteBtn) {
            if (selectedCheckboxes.length > 0) {
                deleteBtn.classList.remove('hidden');
            } else {
                deleteBtn.classList.add('hidden');
            }
        }
    }

    // Obsługa zaznaczania wszystkich zamówień
    const selectAllCheckbox = document.getElementById('select-all-orders');
    if (selectAllCheckbox) {
        selectAllCheckbox.addEventListener('change', (e) => {
            const checkboxes = document.querySelectorAll('.order-checkbox');
            checkboxes.forEach(cb => cb.checked = e.target.checked);
            updateDeleteButtonVisibility();
        });
    }

    // Obsługa pojedynczych checkboxów
    document.addEventListener('change', (e) => {
        if (e.target.classList.contains('order-checkbox')) {
            updateDeleteButtonVisibility();
        }
    });

    // Obsługa usuwania zaznaczonych zamówień
    const deleteSelectedBtn = document.getElementById('delete-selected-orders-btn');
    if (deleteSelectedBtn) {
        deleteSelectedBtn.addEventListener('click', () => {
            const selectedCheckboxes = document.querySelectorAll('.order-checkbox:checked');
            
            if (selectedCheckboxes.length === 0) {
                alert('Zaznacz przynajmniej jedno zamówienie do usunięcia');
                return;
            }

            if (!confirm(`Czy na pewno chcesz usunąć ${selectedCheckboxes.length} zamówień?`)) {
                return;
            }

            const orderIds = Array.from(selectedCheckboxes).map(cb => cb.dataset.orderId);

            fetch('/magazyn/zamowienia/delete-multiple', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({ order_ids: orderIds })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Błąd usuwania zamówień');
                }
                return response.json();
            })
            .then(data => {
                // Usuń wiersze z tabeli
                selectedCheckboxes.forEach(cb => {
                    cb.closest('tr').remove();
                });

                // Odznacz "zaznacz wszystkie"
                if (selectAllCheckbox) {
                    selectAllCheckbox.checked = false;
                }

                // Sprawdź czy tabela jest pusta
                const tbody = document.getElementById('issued-orders-tbody');
                if (tbody.children.length === 0) {
                    const emptyRow = document.createElement('tr');
                    emptyRow.id = 'no-orders-row';
                    emptyRow.innerHTML = '<td class="border p-2 text-center text-gray-400 italic" colspan="6">Brak wystawionych zamówień</td>';
                    tbody.appendChild(emptyRow);
                }

                alert(`Usunięto ${data.deleted} zamówień`);
            })
            .catch(error => {
                console.error('Błąd:', error);
                alert('Wystąpił błąd podczas usuwania zamówień');
            });
        });
    }

    // Obsługa podglądu zamówienia
    let currentPreviewOrderId = null;
    
    document.querySelectorAll('.preview-order-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const orderId = this.getAttribute('data-order-id');
            const orderNumber = this.getAttribute('data-order-number');
            const supplier = this.getAttribute('data-order-supplier');
            const status = this.getAttribute('data-order-status');
            const issuedAt = this.getAttribute('data-order-issued');
            const userName = this.getAttribute('data-order-user');
            const receivedAt = this.getAttribute('data-order-received');
            const receivedBy = this.getAttribute('data-order-received-by');
            const productsJson = this.getAttribute('data-order-products');
            const deliveryTime = this.getAttribute('data-order-delivery-time');
            const supplierOffer = this.getAttribute('data-order-supplier-offer');
            const paymentMethod = this.getAttribute('data-order-payment-method');
            const paymentDays = this.getAttribute('data-order-payment-days');
            
            currentPreviewOrderId = orderId;
            
            try {
                const products = JSON.parse(productsJson);
                
                // Buduj HTML podglądu
                let html = `
                    <div class="grid grid-cols-2 gap-4 mb-4">
                        <div><strong>Numer zamówienia:</strong> ${orderNumber}</div>
                        <div><strong>Status:</strong> <span class="px-2 py-1 rounded ${status === 'received' ? 'bg-green-200' : 'bg-yellow-200'}">${status === 'received' ? 'Przyjęte' : 'Oczekujące'}</span></div>
                        <div><strong>Dostawca:</strong> ${supplier || '-'}</div>
                        <div><strong>Data wystawienia:</strong> ${issuedAt}</div>
                        <div><strong>Zamówił:</strong> ${userName}</div>
                    </div>
                `;
                
                // Dodaj informacje o zamówieniu
                if (deliveryTime || supplierOffer || paymentMethod) {
                    html += `<div class="grid grid-cols-3 gap-4 mb-4 p-2 bg-blue-50 rounded">`;
                    if (deliveryTime) {
                        html += `<div><strong>Termin dostawy:</strong> ${deliveryTime}</div>`;
                    }
                    if (supplierOffer) {
                        html += `<div><strong>Oferta dostawcy nr:</strong> ${supplierOffer}</div>`;
                    }
                    if (paymentMethod) {
                        let paymentText = paymentMethod;
                        if (paymentMethod === 'przelew' && paymentDays) {
                            paymentText += ` (${paymentDays})`;
                        }
                        html += `<div><strong>Forma płatności:</strong> ${paymentText}</div>`;
                    }
                    html += `</div>`;
                }
                
                html += `
                    <h5 class="font-bold mb-2">Produkty:</h5>
                    <table class="w-full border border-collapse text-xs">
                        <thead class="bg-gray-200">
                            <tr>
                                <th class="border p-1 text-left">Produkt</th>
                                <th class="border p-1 text-left">Dostawca</th>
                                <th class="border p-1 text-center">Ilość</th>
                                <th class="border p-1 text-center">Cena netto</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                products.forEach(product => {
                    const priceDisplay = product.price ? `${product.price} ${product.currency || 'PLN'}` : '-';
                    html += `
                        <tr>
                            <td class="border p-1">${product.name}</td>
                            <td class="border p-1">${product.supplier || '-'}</td>
                            <td class="border p-1 text-center">${product.quantity}</td>
                            <td class="border p-1 text-center">${priceDisplay}</td>
                        </tr>
                    `;
                });
                
                html += `
                        </tbody>
                    </table>
                `;
                
                // Dodaj informację o przyjęciu zamówienia
                if (status === 'received' && receivedAt && receivedBy) {
                    html += `
                        <div class="mt-4 p-3 bg-green-50 border border-green-200 rounded">
                            <strong>Zamówienie przyjęte w dniu:</strong> ${receivedAt}<br>
                            <strong>Przyjął:</strong> ${receivedBy}
                        </div>
                    `;
                }
                
                document.getElementById('order-preview-content').innerHTML = html;
                
                // Pokaż/ukryj przyciski w zależności od statusu
                const receiveBtn = document.getElementById('receive-order-btn');
                const editBtn = document.getElementById('preview-edit-order-btn');
                const deleteBtn = document.getElementById('preview-delete-order-btn');
                const isAdmin = {{ auth()->user()->is_admin ? 'true' : 'false' }};
                
                if (status === 'received') {
                    receiveBtn.style.display = 'none';
                    editBtn.style.display = 'none';
                    // Admin może usuwać przyjęte zamówienia
                    deleteBtn.style.display = isAdmin ? 'block' : 'none';
                } else {
                    receiveBtn.style.display = 'block';
                    editBtn.style.display = 'block';
                    deleteBtn.style.display = 'block';
                }
                
                // Pokaż sekcję podglądu
                document.getElementById('order-preview-section').classList.remove('hidden');
                
                // Rozwiń sekcję "Wystawione zamówienia" jeśli jest zwinięta
                const receiveSection = document.getElementById('receive-order-content');
                if (receiveSection.classList.contains('hidden')) {
                    const expandBtn = document.querySelector('[data-target="receive-order-content"]');
                    if (expandBtn) {
                        expandBtn.click();
                    }
                }
                
            } catch (error) {
                console.error('Błąd parsowania produktów:', error);
                alert('Błąd wyświetlania podglądu zamówienia');
            }
        });
    });
    
    // Zamknięcie podglądu
    document.getElementById('close-preview-btn').addEventListener('click', function() {
        document.getElementById('order-preview-section').classList.add('hidden');
        currentPreviewOrderId = null;
    });
    
    // Przyjmowanie zamówienia
    document.getElementById('receive-order-btn').addEventListener('click', function() {
        if (!currentPreviewOrderId) {
            alert('Nie wybrano zamówienia');
            return;
        }
        
        if (!confirm('Czy na pewno chcesz przyjąć to zamówienie? Produkty zostaną dodane do magazynu.')) {
            return;
        }
        
        fetch(`/magazyn/zamowienia/${currentPreviewOrderId}/receive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Błąd przyjmowania zamówienia');
            }
            return response.json();
        })
        .then(data => {
            showNotification(data.message || 'Zamówienie zostało przyjęte', 'success');
            
            // Ukryj przyciski edycji/usuwania po przyjęciu
            document.getElementById('receive-order-btn').style.display = 'none';
            document.getElementById('preview-edit-order-btn').style.display = 'none';
            document.getElementById('preview-delete-order-btn').style.display = 'none';
            
            // Zaktualizuj status w tabeli bez przeładowania
            const orderRow = document.querySelector(`tr:has(.order-checkbox[data-order-id="${currentPreviewOrderId}"])`);
            if (orderRow) {
                // Dodaj zielone podświetlenie
                orderRow.classList.add('bg-green-50');
            }
        })
        .catch(error => {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas przyjmowania zamówienia');
        });
    });
    
    // Pobierz do Word z podglądu
    document.getElementById('preview-generate-word-btn').addEventListener('click', function() {
        if (!currentPreviewOrderId) {
            alert('Nie wybrano zamówienia');
            return;
        }
        window.location.href = `/magazyn/zamowienia/${currentPreviewOrderId}/generate-word`;
    });
    
    // Pobierz do PDF z podglądu
    document.getElementById('preview-generate-pdf-btn').addEventListener('click', function() {
        if (!currentPreviewOrderId) {
            alert('Nie wybrano zamówienia');
            return;
        }
        window.location.href = `/magazyn/zamowienia/${currentPreviewOrderId}/generate-pdf`;
    });
    
    // Edytuj zamówienie z podglądu
    document.getElementById('preview-edit-order-btn').addEventListener('click', function() {
        if (!currentPreviewOrderId) {
            alert('Nie wybrano zamówienia');
            return;
        }
        // Znajdź przycisk edycji w tabeli dla tego zamówienia i kliknij go
        const editBtn = document.querySelector(`.edit-order-btn[data-order-id="${currentPreviewOrderId}"]`);
        if (editBtn) {
            editBtn.click();
        } else {
            alert('Nie można znaleźć opcji edycji dla tego zamówienia');
        }
    });
    
    // Usuń zamówienie z podglądu
    document.getElementById('preview-delete-order-btn').addEventListener('click', function() {
        if (!currentPreviewOrderId) {
            alert('Nie wybrano zamówienia');
            return;
        }
        
        if (!confirm('Czy na pewno chcesz usunąć to zamówienie?')) {
            return;
        }
        
        fetch(`/magazyn/zamowienia/${currentPreviewOrderId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
                'Accept': 'application/json'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error('Błąd usuwania zamówienia');
            }
            return response.json();
        })
        .then(data => {
            alert(data.message || 'Zamówienie zostało usunięte');
            document.getElementById('order-preview-section').classList.add('hidden');
            currentPreviewOrderId = null;
            window.location.reload();
        })
        .catch(error => {
            console.error('Błąd:', error);
            alert('Wystąpił błąd podczas usuwania zamówienia');
        });
    });
    
    console.log('Orders page JavaScript initialization complete');
});
</script>

</body>
</html>
