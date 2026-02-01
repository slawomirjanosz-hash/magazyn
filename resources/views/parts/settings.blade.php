<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Ustawienia</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

@include('parts.menu')

<div class="max-w-6xl mx-auto px-6 py-6">

    <!-- Nagłówek z Statystykami -->
    <div class="flex items-center justify-between mb-6 relative">
        <a href="/" class="flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 shadow rounded-full text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition">
            <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
            Powrót
        </a>
           <h2 class="text-2xl font-bold mb-2">⚙️ Ustawienia Magazynu</h2>
        <div class="bg-white rounded shadow p-1 w-96 ml-auto" style="word-break:break-word;">
            <div class="flex items-center gap-4">
                <div>
                    <p class="text-sm font-semibold whitespace-nowrap">Statystyki magazynu:</p>
                    @php [$warehouseValue, $eurPln] = \App\Helpers\WarehouseHelper::getWarehouseValuePln(); @endphp
                    <span class="text-[10px] text-gray-400 block mt-0.5">Kurs Euro = {{ number_format($eurPln, 4, ',', ' ') }} PLN</span>
                </div>
                <div class="flex gap-4">
                    <div class="text-center">
                        <p class="text-lg font-bold text-blue-600">{{ \App\Models\Part::count() }}</p>
                        <p class="text-gray-600 text-xs">Produktów</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-green-600">{{ \App\Models\Category::count() }}</p>
                        <p class="text-gray-600 text-xs">Kategorii</p>
                    </div>
                    <div class="text-center">
                        <p class="text-lg font-bold text-purple-600">{{ \App\Models\Part::sum('quantity') }}</p>
                        <p class="text-gray-600 text-xs">Sztuk łącznie</p>
                    </div>
                    <div class="text-center">
                        @php
                            [$warehouseValue, $eurPln] = \App\Helpers\WarehouseHelper::getWarehouseValuePln();
                        @endphp
                        <p class="text-xs font-bold text-amber-600" style="font-size:0.75rem;">{{ number_format($warehouseValue, 2, ',', ' ') }} PLN</p>
                        <p class="text-gray-600 text-xs">Wartość magazynu</p>
                        <span class="text-[10px] text-gray-400 block mt-0.5">Kurs Euro = {{ number_format($eurPln, 4, ',', ' ') }} PLN</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Sekcja: Superadmin (widoczna tylko dla proximalumine@gmail.com) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com')
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="superadmin-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold text-red-700">Superadmin</h3>
        </button>
        <div id="superadmin-content" class="collapsible-content hidden p-6 border-t">
            <p class="text-gray-700 mb-2">Ta sekcja jest widoczna tylko dla Superadmina.</p>
            <div class="p-4 bg-red-50 border border-red-300 rounded">
                <p class="font-bold text-red-700">Jesteś Superadminem! Możesz wykonywać operacje niedostępne dla innych użytkowników.</p>
                <!-- Tu możesz dodać dowolne opcje/ustawienia dla superadmina -->
            </div>
        </div>
    </div>
    @endif

    <!-- Sekcja: Zarządzanie kategoriami (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_categories)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="categories-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Kategorie</h3>
        </button>
        <div id="categories-content" class="collapsible-content hidden p-6 border-t">
            <div class="mb-4">
                <p class="text-gray-600 mb-3">Lista aktualnych kategorii:</p>
                <div class="flex flex-col gap-2">
                    @forelse($categories as $cat)
                        <div class="flex items-center gap-2" id="category-row-{{ $cat->id }}">
                            <form action="{{ route('magazyn.category.update', $cat->id) }}" method="POST" class="flex items-center gap-2 flex-1" id="edit-form-{{ $cat->id }}">
                                @csrf
                                @method('PUT')
                                <input 
                                    type="text" 
                                    name="name" 
                                    value="{{ $cat->name }} ({{ $cat->parts_count }})"
                                    class="category-box px-2 py-0.5 bg-gray-100 rounded border-4 border-gray-300 text-xs whitespace-nowrap w-1/3"
                                    id="category-input-{{ $cat->id }}"
                                    readonly
                                    onclick="enableCategoryEdit({{ $cat->id }})"
                                    data-original-name="{{ $cat->name }}"
                                    maxlength="15"
                                >
                                @if($cat->parts_count > 0)
                                    <button type="button" class="text-red-600 hover:text-red-800 font-bold text-base leading-none ml-1" title="Usuń zawartość kategorii" onclick="if(confirm('Czy na pewno chcesz usunąć zawartość kategorii &quot;{{ $cat->name }}&quot;?')) { document.getElementById('clear-form-{{ $cat->id }}').submit(); }">
                                        <span class="material-icons align-middle">delete</span>
                                    </button>
                                @endif
                                @if($cat->parts_count === 0)
                                    <button type="button" class="text-red-600 hover:text-red-800 font-bold text-base leading-none ml-1" title="Usuń kategorię" onclick="if(confirm('Czy na pewno usunąć kategorię &quot;{{ $cat->name }}&quot;?')) { document.getElementById('delete-form-{{ $cat->id }}').submit(); }">
                                        <span class="material-icons align-middle">delete</span>
                                    </button>
                                @endif
                                <button 
                                    type="submit" 
                                    class="hidden text-green-600 hover:text-green-800 font-bold text-sm" 
                                    id="save-btn-{{ $cat->id }}"
                                    title="Zapisz zmiany"
                                >
                                    ✓
                                </button>
                                <button 
                                    type="button" 
                                    class="hidden text-gray-600 hover:text-gray-800 font-bold text-sm" 
                                    id="cancel-btn-{{ $cat->id }}"
                                    onclick="cancelCategoryEdit({{ $cat->id }}, '{{ $cat->name }}', {{ $cat->parts_count }})"
                                    title="Anuluj"
                                >
                                    ✕
                                </button>
                            </form>
                            @if($cat->parts_count > 0)
                                <form action="{{ route('magazyn.category.clearContents', $cat->id) }}" method="POST" class="hidden" id="clear-form-{{ $cat->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                            @if($cat->parts_count === 0)
                                <form action="{{ route('magazyn.category.delete', $cat->id) }}" method="POST" class="hidden" id="delete-form-{{ $cat->id }}">
                                    @csrf
                                    @method('DELETE')
                                </form>
                            @endif
                        </div>
                    @empty
                        <p class="text-gray-400 italic">Brak kategorii</p>
                    @endforelse
                </div>
            </div>

            <div class="border-t pt-4 mt-4">
                <h4 class="font-semibold mb-2">Dodaj nową kategorię</h4>
                <form action="{{ route('magazyn.category.add') }}" method="POST" class="flex gap-2">
                    @csrf
                    <input 
                        type="text" 
                        name="name" 
                        placeholder="Nazwa kategorii" 
                        class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        maxlength="15"
                        required
                    >
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Dodaj
                    </button>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Sekcja: Zarządzanie dostawcami (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_suppliers)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="suppliers-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Dostawcy i Klienci</h3>
        </button>
        <div id="suppliers-content" class="collapsible-content hidden p-6 border-t">
            <div class="mb-4">
                <p class="text-gray-600 mb-3">Lista dostawców i klientów:</p>
                <div class="overflow-x-auto">
                    <table class="w-full border border-collapse text-xs">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-2 text-left">Logo</th>
                                <th class="border p-2 text-left">Nazwa</th>
                                <th class="border p-2 text-left">Skrót</th>
                                <th class="border p-2 text-left min-w-[110px] max-w-[110px]">NIP</th>
                                <!-- <th class="border p-2 text-left">Adres</th> -->
                                <!-- <th class="border p-2 text-left">Kod pocztowy</th> -->
                                <th class="border p-2 text-left">Miasto</th>
                                <th class="border p-2 text-left min-w-[140px]">Telefon</th>
                                <th class="border p-2 text-left min-w-[200px]">Email</th>
                                <th class="border p-2 text-center">Typ</th>
                                <th class="border p-2 text-center">Akcja</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($suppliers as $supplier)
                                <tr>
                                    <td class="border p-2">
                                        @if($supplier->logo)
                                            @php
                                                // Logo może być w formacie base64 (data:image/...) lub ścieżka do pliku
                                                if (str_starts_with($supplier->logo, 'data:image')) {
                                                    $supplierLogoSrc = $supplier->logo; // już jest base64
                                                } else {
                                                    $supplierLogoSrc = asset('storage/' . $supplier->logo); // stary format
                                                }
                                            @endphp
                                            <img src="{{ $supplierLogoSrc }}" alt="Logo" class="h-8 w-auto">
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="border p-2">{{ $supplier->name }}</td>
                                    <td class="border p-2">{{ $supplier->short_name ?? '-' }}</td>
                                    <td class="border p-2 min-w-[110px] max-w-[110px] truncate">{{ $supplier->nip ?? '-' }}</td>
                                    <!-- <td class="border p-2">{{ $supplier->address ? 'UL. ' . $supplier->address : '-' }}</td> -->
                                    <!-- <td class="border p-2">{{ $supplier->postal_code ?? '-' }}</td> -->
                                    <td class="border p-2">{{ $supplier->city ?? '-' }}</td>
                                    <td class="border p-2 min-w-[140px]">{{ $supplier->phone ?? '-' }}</td>
                                    <td class="border p-2 min-w-[200px]">{{ $supplier->email ?? '-' }}</td>
                                    <td class="border p-2 text-center whitespace-nowrap">
                                        @php
                                            $types = [];
                                            if($supplier->is_supplier) $types[] = '🏭 Dostawca';
                                            if($supplier->is_client) $types[] = '👤 Klient';
                                        @endphp
                                        <span class="text-xs">{{ implode(' / ', $types) ?: '-' }}</span>
                                    </td>
                                    <td class="border p-2 text-center whitespace-nowrap">
                                        <button type="button" 
                                            class="text-blue-600 hover:text-blue-800 mr-2 edit-supplier-btn"
                                            title="Edytuj dostawcę"
                                            data-supplier-id="{{ $supplier->id }}"
                                            data-supplier-name="{{ $supplier->name }}"
                                            data-supplier-short-name="{{ $supplier->short_name ?? '' }}"
                                            data-supplier-nip="{{ $supplier->nip ?? '' }}"
                                            data-supplier-address="{{ $supplier->address ?? '' }}"
                                            data-supplier-city="{{ $supplier->city ?? '' }}"
                                            data-supplier-postal-code="{{ $supplier->postal_code ?? '' }}"
                                            data-supplier-phone="{{ $supplier->phone ?? '' }}"
                                            data-supplier-email="{{ $supplier->email ?? '' }}"
                                            data-supplier-logo="{{ $supplier->logo ?? '' }}"
                                            data-supplier-is-supplier="{{ $supplier->is_supplier ? '1' : '0' }}"
                                            data-supplier-is-client="{{ $supplier->is_client ? '1' : '0' }}"
                                        >✏️</button>
                                        <form action="{{ route('magazyn.supplier.delete', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Czy na pewno usunąć dostawcę {{ $supplier->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800" title="Usuń dostawcę">❌</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="border p-2 text-center text-gray-400 italic" colspan="11">Brak dostawców</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="border-t pt-4 mt-4">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 py-2 cursor-pointer hover:bg-gray-50" data-target="add-supplier-content">
                    <span class="toggle-arrow text-lg">▶</span>
                    <h4 class="font-semibold">Dodaj dostawcę</h4>
                </button>
                <div id="add-supplier-content" class="collapsible-content hidden mt-4">
                <!-- Formularz: Pobierz dane po NIP -->
                <div class="mb-4 p-4 bg-blue-50 rounded border border-blue-200">
                    <h5 class="font-semibold mb-2 text-sm">Pobierz dane z bazy GUS</h5>
                    <div class="flex gap-2">
                        <input 
                            type="text" 
                            id="nip-input"
                            placeholder="Wpisz NIP (10 cyfr lub z myślnikami)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                        <button 
                            type="button"
                            id="fetch-nip-btn"
                            class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Pobierz dane
                        </button>
                    </div>
                    <p class="text-xs text-gray-600 mt-1">Po pobraniu danych możesz je edytować przed zapisaniem</p>
                </div>

                <!-- Formularz: Dodaj ręcznie -->
                <form action="{{ route('magazyn.supplier.add') }}" method="POST" id="supplier-form" class="grid grid-cols-2 gap-3" enctype="multipart/form-data">
                    @csrf
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Nazwa firmy *</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="supplier-name"
                            placeholder="Nazwa firmy *" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                            required
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Skrócona nazwa:</label>
                        <input 
                            type="text" 
                            name="short_name"
                            id="supplier-short-name"
                            placeholder="Skrócona nazwa (np. slajan) - zostanie ustawiona automatycznie"
                            class="flex-1 px-3 py-2 border-2 border-red-500 bg-red-50 rounded @error('short_name') border-red-500 @enderror"
                            autocomplete="off"
                            required
                        >
                    </div>
                    @error('short_name')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">NIP:</label>
                        <input 
                            type="text" 
                            name="nip" 
                            id="supplier-nip"
                            placeholder="NIP (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                            maxlength="10"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Adres:</label>
                        <input 
                            type="text" 
                            name="address" 
                            id="supplier-address"
                            placeholder="Adres (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Miasto:</label>
                        <input 
                            type="text" 
                            name="city" 
                            id="supplier-city"
                            placeholder="Miasto (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Kod pocztowy:</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="supplier-postal-code"
                            placeholder="Kod pocztowy (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Telefon:</label>
                        <input 
                            type="text" 
                            name="phone" 
                            id="supplier-phone"
                            placeholder="Telefon (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Email:</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="supplier-email"
                            placeholder="Email (opcjonalnie)" 
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1">Logo dostawcy</label>
                        <input 
                            type="file" 
                            name="logo" 
                            accept="image/*"
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                        <p class="text-xs text-gray-600 mt-1">Dozwolone formaty: JPG, PNG, GIF, SVG (max 2MB)</p>
                    </div>
                    <div class="col-span-2 border-t pt-3 mt-2">
                        <label class="block text-sm font-semibold mb-2">Typ podmiotu:</label>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="is_supplier" 
                                    id="supplier-is-supplier"
                                    value="1"
                                    checked
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-gray-700">Dostawca</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="is_client" 
                                    id="supplier-is-client"
                                    value="1"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-gray-700">Klient</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Podmiot może być zaznaczony jako Dostawca, Klient lub oba jednocześnie</p>
                    </div>
                    <button 
                        type="submit" 
                        class="col-span-2 px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                    >
                        Dodaj dostawcę
                    </button>
                </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Edytuj dostawcę -->
    <div id="edit-supplier-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center">
        <div class="bg-white rounded-lg shadow-xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
            <div class="p-6">
                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-xl font-semibold">Edytuj dostawcę</h3>
                    <button type="button" id="close-edit-supplier-modal" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
                </div>
                
                <form id="edit-supplier-form" method="POST" enctype="multipart/form-data" class="grid grid-cols-2 gap-3">
                    @csrf
                    @method('PUT')
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Nazwa firmy *</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="edit-supplier-name"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                            required
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Skrócona nazwa:</label>
                        <input 
                            type="text" 
                            name="short_name" 
                            id="edit-supplier-short-name"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">NIP:</label>
                        <input 
                            type="text" 
                            name="nip" 
                            id="edit-supplier-nip"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                            maxlength="13"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Adres:</label>
                        <input 
                            type="text" 
                            name="address" 
                            id="edit-supplier-address"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Miasto:</label>
                        <input 
                            type="text" 
                            name="city" 
                            id="edit-supplier-city"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Kod pocztowy:</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="edit-supplier-postal-code"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Telefon:</label>
                        <input 
                            type="text" 
                            name="phone" 
                            id="edit-supplier-phone"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="flex items-center gap-3">
                        <label class="block text-sm font-medium text-gray-700 whitespace-nowrap w-40">Email:</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="edit-supplier-email"
                            class="flex-1 px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1">Aktualne logo</label>
                        <div id="edit-supplier-current-logo" class="mb-2"></div>
                        <label class="block text-sm font-semibold mb-1">Nowe logo (opcjonalnie)</label>
                        <input 
                            type="file" 
                            name="logo" 
                            accept="image/*"
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                        <p class="text-xs text-gray-600 mt-1">Pozostaw puste, aby zachować aktualne logo</p>
                    </div>
                    <div class="col-span-2 border-t pt-3 mt-2">
                        <label class="block text-sm font-semibold mb-2">Typ podmiotu:</label>
                        <div class="flex gap-6">
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="is_supplier" 
                                    id="edit-supplier-is-supplier"
                                    value="1"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-gray-700">Dostawca</span>
                            </label>
                            <label class="flex items-center gap-2 cursor-pointer">
                                <input 
                                    type="checkbox" 
                                    name="is_client" 
                                    id="edit-supplier-is-client"
                                    value="1"
                                    class="w-4 h-4 text-blue-600 rounded focus:ring-blue-500"
                                >
                                <span class="text-sm font-medium text-gray-700">Klient</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-600 mt-1">Podmiot może być zaznaczony jako Dostawca, Klient lub oba jednocześnie</p>
                    </div>
                    <div class="col-span-2 flex gap-2">
                        <button 
                            type="submit" 
                            class="flex-1 px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                        >
                            Zapisz zmiany
                        </button>
                        <button 
                            type="button" 
                            id="cancel-edit-supplier"
                            class="px-4 py-2 bg-gray-500 text-white rounded hover:bg-gray-600"
                        >
                            Anuluj
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    @endif

    <!-- Sekcja: Dane Mojej Firmy (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_company)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="company-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Dane Mojej Firmy</h3>
        </button>
        <div id="company-content" class="collapsible-content hidden p-6 border-t">
            <div class="mb-4 p-4 bg-blue-50 rounded border border-blue-200">
                <h5 class="font-semibold mb-2 text-sm">Pobierz dane z bazy GUS po NIP</h5>
                <div class="flex gap-2">
                    <input 
                        type="text" 
                        id="company-nip-input"
                        placeholder="Wpisz NIP (10 cyfr lub z myślnikami)" 
                        class="flex-1 px-3 py-2 border border-gray-300 rounded"
                    >
                    <button 
                        type="button"
                        id="fetch-company-nip-btn"
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Pobierz dane
                    </button>
                </div>
                <p class="text-xs text-gray-600 mt-1">Po pobraniu danych możesz je edytować przed zapisaniem</p>
            </div>

            <form action="{{ route('magazyn.company.save') }}" method="POST" enctype="multipart/form-data" class="space-y-4" id="company-form">
                @csrf
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-semibold mb-1">Nazwa firmy</label>
                        <input 
                            type="text" 
                            name="name" 
                            id="company-name"
                            value="{{ $companySettings->name ?? '' }}"
                            placeholder="Nazwa firmy" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">NIP</label>
                        <input 
                            type="text" 
                            name="nip" 
                            id="company-nip"
                            value="{{ $companySettings->nip ?? '' }}"
                            placeholder="NIP" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">Adres</label>
                        <input 
                            type="text" 
                            name="address" 
                            id="company-address"
                            value="{{ $companySettings->address ?? '' }}"
                            placeholder="Ulica i numer" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">Miasto</label>
                        <input 
                            type="text" 
                            name="city" 
                            id="company-city"
                            value="{{ $companySettings->city ?? '' }}"
                            placeholder="Miasto" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">Kod pocztowy</label>
                        <input 
                            type="text" 
                            name="postal_code" 
                            id="company-postal-code"
                            value="{{ $companySettings->postal_code ?? '' }}"
                            placeholder="00-000" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div>
                        <label class="block text-sm font-semibold mb-1">Telefon</label>
                        <input 
                            type="text" 
                            name="phone" 
                            id="company-phone"
                            value="{{ $companySettings->phone ?? '' }}"
                            placeholder="Telefon" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                    
                    <div class="col-span-2">
                        <label class="block text-sm font-semibold mb-1">Email</label>
                        <input 
                            type="email" 
                            name="email" 
                            id="company-email"
                            value="{{ $companySettings->email ?? '' }}"
                            placeholder="Email" 
                            class="w-full px-3 py-2 border border-gray-300 rounded"
                        >
                    </div>
                </div>
                
                <div class="border-t pt-4">
                    <label class="block text-sm font-semibold mb-2">Logo firmy</label>
                    @if($companySettings && $companySettings->logo)
                        <div class="mb-3">
                            @php
                                // Logo może być w formacie base64 (data:image/...) lub ścieżka do pliku
                                if (str_starts_with($companySettings->logo, 'data:image')) {
                                    $companyLogoSrc = $companySettings->logo; // już jest base64
                                } else {
                                    $companyLogoSrc = asset('storage/' . $companySettings->logo); // stary format
                                }
                            @endphp
                            <img src="{{ $companyLogoSrc }}" alt="Logo firmy" class="max-h-32 border rounded">
                            <p class="text-xs text-gray-600 mt-1">Aktualne logo</p>
                        </div>
                    @endif
                    <input 
                        type="file" 
                        name="logo" 
                        accept="image/*"
                        class="w-full px-3 py-2 border border-gray-300 rounded"
                    >
                    <p class="text-xs text-gray-600 mt-1">Dozwolone formaty: JPG, PNG, GIF, SVG (max 2MB)</p>
                </div>
                
                <div class="flex justify-end">
                    <button 
                        type="submit" 
                        class="px-6 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Zapisz dane firmy
                    </button>
                </div>
            </form>
        </div>
    </div>
    @endif

    <!-- Sekcja: Zarządzanie użytkownikami (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_users)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="users-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Użytkownicy</h3>
        </button>
        <div id="users-content" class="collapsible-content hidden p-6 border-t">
            <!-- Formularz dodawania użytkownika (rozwijany) -->
            <div class="bg-gray-50 rounded shadow-sm mb-4">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-4 cursor-pointer hover:bg-gray-100 rounded" data-target="add-user-form-content">
                    <span class="toggle-arrow text-base">▶</span>
                    <h4 class="font-semibold">Dodaj nowego użytkownika</h4>
                </button>
                <div id="add-user-form-content" class="collapsible-content hidden p-4 border-t bg-white">
                    <form action="{{ route('magazyn.user.add') }}" method="POST" class="flex flex-col gap-3">
                        @csrf
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Imię:</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="text" 
                                    name="first_name" 
                                    id="first_name"
                                    placeholder="Imię" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('first_name') border-red-500 @enderror"
                                    autocomplete="off"
                                    required
                                >
                                @error('first_name')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Nazwisko:</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="text" 
                                    name="last_name" 
                                    id="last_name"
                                    placeholder="Nazwisko" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('last_name') border-red-500 @enderror"
                                    autocomplete="off"
                                    required
                                >
                                @error('last_name')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Email:</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="email" 
                                    name="email" 
                                    placeholder="Email" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('email') border-red-500 @enderror"
                                    autocomplete="off"
                                    required
                                >
                                @error('email')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Numer telefonu:</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="text" 
                                    name="phone" 
                                    placeholder="Numer telefonu" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('phone') border-red-500 @enderror"
                                    autocomplete="off"
                                >
                                @error('phone')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Hasło (opcjonalne):</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="password" 
                                    name="password" 
                                    placeholder="Hasło (opcjonalne)" 
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('password') border-red-500 @enderror"
                                    autocomplete="new-password"
                                >
                                @error('password')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <label class="text-sm font-medium text-gray-700 w-40">Skrócona nazwa:</label>
                            <div class="flex-1 max-w-xs">
                                <input 
                                    type="text" 
                                    name="short_name" 
                                    id="short_name"
                                    placeholder="np. MicKow"
                                    class="w-full px-3 py-2 border border-gray-300 rounded @error('short_name') border-red-500 @enderror"
                                    autocomplete="off"
                                >
                                @error('short_name')
                                    <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                                @enderror
                            </div>
                        </div>
                        <button 
                            type="submit" 
                            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
                        >
                            Dodaj użytkownika
                        </button>
                    </form>
                </div>
            </div>

            
            <div>
                <p class="text-gray-600 mb-3">Lista użytkowników:</p>
                <div class="flex flex-col gap-2">
                    @forelse(\App\Models\User::where('email', '!=', 'proximalumine@gmail.com')->with('creator')->get() as $user)
                        <div class="px-3 py-2 bg-gray-100 rounded border border-gray-300 text-sm flex items-center justify-between">
                            <div class="flex-1">
                                <p class="font-semibold flex items-center gap-2">
                                    {{ $user->name }}
                                    @if($user->is_admin)
                                        <span class="text-xs bg-red-500 text-white px-2 py-0.5 rounded-full">👑 ADMIN</span>
                                    @endif
                                </p>
                                <p class="text-gray-600 text-xs">{{ $user->email }}</p>
                                @if($user->phone)
                                    <p class="text-gray-600 text-xs">📞 {{ $user->phone }}</p>
                                @endif
                                <div class="flex gap-1 mt-2">
                                    @if($user->is_admin)
                                        <span class="text-lg" title="Dostęp do wszystkiego">⭐</span>
                                    @else
                                        @if($user->can_view_magazyn)
                                            <span class="text-lg" title="Dostęp do Magazynu">📦</span>
                                        @endif
                                        @if($user->can_view_offers)
                                            <span class="text-lg" title="Dostęp do Wycen i Ofert">💼</span>
                                        @endif
                                        @if($user->can_view_recipes)
                                            <span class="text-lg" title="Dostęp do Receptur">🧪</span>
                                        @endif
                                        @if($user->can_view_catalog)
                                            <span class="text-lg" title="Dostęp do Katalogu">🔍</span>
                                        @endif
                                        @if($user->can_add)
                                            <span class="text-lg" title="Dostęp do Dodaj">➕</span>
                                        @endif
                                        @if($user->can_remove)
                                            <span class="text-lg" title="Dostęp do Pobierz">➖</span>
                                        @endif
                                        @if($user->can_orders)
                                            <span class="text-lg" title="Dostęp do Zamówienia">📦</span>
                                        @endif
                                        @if($user->can_crm)
                                            <span class="text-lg" title="Dostęp do CRM">👥</span>
                                        @endif
                                        @if($user->can_settings)
                                            <span class="text-lg" title="Dostęp do Ustawienia">⚙️</span>
                                        @endif
                                        @if($user->can_delete_orders)
                                            <span class="text-lg" title="Może usuwać zamówienia">🗑️</span>
                                        @endif
                                        @if(!$user->can_view_magazyn && !$user->can_view_offers && !$user->can_view_recipes && !$user->can_view_catalog && !$user->can_add && !$user->can_remove && !$user->can_orders && !$user->can_settings)
                                            <span class="text-gray-400 text-xs italic">Brak uprawnień</span>
                                        @endif
                                    @endif
                                </div>
                                @if($user->creator)
                                    <p class="text-gray-500 text-xs mt-1">Utworzył: <span class="font-semibold">{{ $user->creator->short_name ?? $user->creator->name }}</span></p>
                                @endif
                            </div>
                            <div class="flex gap-2">
                                @if(auth()->user()->is_admin && $user->email !== 'proximalumine@gmail.com')
                                    @if(!$user->is_admin)
                                    <form action="{{ route('magazyn.user.toggleAdmin', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-purple-600 hover:text-purple-800 font-bold text-sm" title="Mianuj na admina" onclick="return confirm('Czy na pewno chcesz mianować użytkownika {{ $user->name }} adminem?')">
                                            👑
                                        </button>
                                    </form>
                                    @else
                                    <form action="{{ route('magazyn.user.toggleAdmin', $user->id) }}" method="POST" class="inline">
                                        @csrf
                                        @method('PUT')
                                        <button type="submit" class="text-gray-500 hover:text-gray-700 font-bold text-sm" title="Degraduj do zwykłego użytkownika" onclick="return confirm('Czy na pewno chcesz zdegradować admina {{ $user->name }} do zwykłego użytkownika?')">
                                            🡻
                                        </button>
                                    </form>
                                    @endif
                                @endif
                                @if(auth()->user()->is_admin || !$user->is_admin)
                                <a href="{{ route('magazyn.user.edit', $user->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-sm" title="Edytuj użytkownika">
                                    ✏️
                                </a>
                                @endif
                                @if(auth()->user()->email === 'proximalumine@gmail.com')
                                    {{-- Główny admin może usunąć każdego użytkownika --}}
                                    <form action="{{ route('magazyn.user.delete', $user->id) }}" method="POST" class="inline" id="delete-user-form-{{ $user->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="text-red-600 hover:text-red-800 font-bold text-sm" title="Usuń użytkownika" onclick="if(confirm('Czy na pewno usunąć użytkownika &quot;{{ $user->name }}&quot;?')) { document.getElementById('delete-user-form-{{ $user->id }}').submit(); }">
                                            ✕
                                        </button>
                                    </form>
                                @elseif(!$user->is_admin)
                                    {{-- Inni admini mogą usunąć tylko nie-adminów --}}
                                    <form action="{{ route('magazyn.user.delete', $user->id) }}" method="POST" class="inline" id="delete-user-form-{{ $user->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="text-red-600 hover:text-red-800 font-bold text-sm" title="Usuń użytkownika" onclick="if(confirm('Czy na pewno usunąć użytkownika &quot;{{ $user->name }}&quot;?')) { document.getElementById('delete-user-form-{{ $user->id }}').submit(); }">
                                            ✕
                                        </form>
                                @else
                                    <span class="text-gray-400 text-xs italic ml-2">Admin</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <p class="text-gray-400 italic">Brak użytkowników</p>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
    @endif

    <!-- Sekcja: Ustawienia eksportu (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_export)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="export-settings-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Ustawienia eksportu</h3>
        </button>
        <div id="export-settings-content" class="collapsible-content hidden p-6 border-t">
            <p class="text-gray-600 mb-4">Tutaj będzie można dostosować format eksportów (CSV/XLSX/DOCX):</p>
            <ul class="list-disc list-inside text-gray-700 space-y-1">
                <li>Szerokość kolumny "Opis" w XLSX</li>
                <li>Domyślny separator w CSV</li>
                <li>Szerokość kolumn tabeli w Word</li>
                <li>Logo i dane firmy w nagłówku dokumentu</li>
            </ul>
            <p class="text-gray-500 text-sm mt-4 italic">Funkcjonalność w przygotowaniu...</p>
        </div>
    </div>
    @endif

    <!-- Sekcja: Inne Ustawienia (rozwijalna) -->
    @if(auth()->user()->email === 'proximalumine@gmail.com' || auth()->user()->can_settings_other)
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="other-settings-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Inne Ustawienia</h3>
        </button>
        <div id="other-settings-content" class="collapsible-content hidden p-6 border-t">
            <p class="text-gray-600 mb-4">Dodatkowe ustawienia systemu:</p>
            
            <!-- Podsekcja: Ustawienia Zamówień (rozwijalna) -->
            <div class="border rounded mb-4">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-4 cursor-pointer hover:bg-gray-50 rounded" data-target="orders-settings-content">
                    <span class="toggle-arrow text-base">▶</span>
                    <h4 class="font-semibold text-gray-800">Ustawienia Zamówień</h4>
                </button>
                <div id="orders-settings-content" class="collapsible-content hidden p-4 border-t bg-gray-50">
                    <p class="text-gray-600 text-sm mb-4 font-semibold">Konfigurator formatu nazwy zamówienia:</p>
                    
                    @php
                        $orderSettings = \DB::table('order_settings')->first();
                        if (!$orderSettings) {
                            $orderSettings = (object)[
                                'element1_type' => 'empty',
                                'element1_value' => '',
                                'separator1' => '_',
                                'element2_type' => 'empty',
                                'element2_value' => '',
                                'separator2' => '_',
                                'element3_type' => 'empty',
                                'element3_value' => '',
                                'separator3' => '_',
                                'element4_type' => 'empty',
                                'element4_value' => '',
                                'start_number' => 1
                            ];
                        }
                    @endphp
                    
                    <form action="{{ route('magazyn.order-settings.save') }}" method="POST" class="space-y-4" id="order-settings-form">
                        @csrf
                        
                        {{-- Element 1 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 1:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element1_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleElementInput('element1', this.value)">
                                    <option value="empty" {{ ($orderSettings->element1_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="text" {{ ($orderSettings->element1_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($orderSettings->element1_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($orderSettings->element1_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                    <option value="supplier" {{ ($orderSettings->element1_type ?? '') === 'supplier' ? 'selected' : '' }}>Skrót dostawcy</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element1_value" 
                                    id="element1_value"
                                    value="{{ $orderSettings->element1_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="6"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                    style="{{ ($orderSettings->element1_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator1" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($orderSettings->separator1 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($orderSettings->separator1 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($orderSettings->separator1 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($orderSettings->separator1 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($orderSettings->separator1 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($orderSettings->separator1 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Element 2 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 2:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element2_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleElementInput('element2', this.value)">
                                    <option value="empty" {{ ($orderSettings->element2_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="text" {{ ($orderSettings->element2_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($orderSettings->element2_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($orderSettings->element2_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                    <option value="supplier" {{ ($orderSettings->element2_type ?? '') === 'supplier' ? 'selected' : '' }}>Skrót dostawcy</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element2_value" 
                                    id="element2_value"
                                    value="{{ $orderSettings->element2_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="6"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                    style="{{ ($orderSettings->element2_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator2" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($orderSettings->separator2 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($orderSettings->separator2 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($orderSettings->separator2 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($orderSettings->separator2 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($orderSettings->separator2 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($orderSettings->separator2 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Element 3 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 3:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element3_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleElementInput('element3', this.value)">
                                    <option value="empty" {{ ($orderSettings->element3_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="text" {{ ($orderSettings->element3_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($orderSettings->element3_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($orderSettings->element3_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                    <option value="number" {{ ($orderSettings->element3_type ?? '') === 'number' ? 'selected' : '' }}>Nr oferty</option>
                                    <option value="supplier" {{ ($orderSettings->element3_type ?? '') === 'supplier' ? 'selected' : '' }}>Skrót dostawcy</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element3_value" 
                                    id="element3_value"
                                    value="{{ $orderSettings->element3_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="6"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                    style="{{ ($orderSettings->element3_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator3" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($orderSettings->separator3 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($orderSettings->separator3 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($orderSettings->separator3 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($orderSettings->separator3 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($orderSettings->separator3 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($orderSettings->separator3 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Numer startowy (dla type=number) --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Numer startowy (dla elementu typu "Nr oferty"):</label>
                            <input 
                                type="number" 
                                name="start_number" 
                                id="start_number"
                                value="{{ $orderSettings->start_number ?? 1 }}"
                                min="1"
                                class="px-2 py-1 border border-gray-300 rounded text-sm w-32"
                            >
                            <span class="text-xs text-gray-500 ml-2">(np. 1, 100, 1000 itd.)</span>
                        </div>
                        
                        {{-- Element 4 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 4:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element4_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleElementInput('element4', this.value)">
                                    <option value="empty" {{ ($orderSettings->element4_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="text" {{ ($orderSettings->element4_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($orderSettings->element4_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($orderSettings->element4_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                    <option value="number" {{ ($orderSettings->element4_type ?? '') === 'number' ? 'selected' : '' }}>Nr oferty</option>
                                    <option value="supplier" {{ ($orderSettings->element4_type ?? '') === 'supplier' ? 'selected' : '' }}>Skrót dostawcy</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element4_value" 
                                    id="element4_value"
                                    value="{{ $orderSettings->element4_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="6"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                    style="{{ ($orderSettings->element4_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                                >
                            </div>
                        </div>
                        
                        {{-- Podgląd --}}
                        <div class="bg-blue-50 p-3 rounded border border-blue-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Podgląd nazwy zamówienia:</label>
                            <div id="order-name-preview" class="font-mono text-lg text-blue-700 font-bold"></div>
                            <script>
                                function getOrderNamePreview() {
                                    const types = [
                                        document.querySelector('[name="element1_type"]').value,
                                        document.querySelector('[name="element2_type"]').value,
                                        document.querySelector('[name="element3_type"]').value,
                                        document.querySelector('[name="element4_type"]').value
                                    ];
                                    const values = [
                                        document.getElementById('element1_value').value,
                                        document.getElementById('element2_value').value,
                                        document.getElementById('element3_value').value,
                                        document.getElementById('element4_value').value
                                    ];
                                    const seps = [
                                        document.querySelector('[name="separator1"]').value,
                                        document.querySelector('[name="separator2"]').value,
                                        document.querySelector('[name="separator3"]').value
                                    ];
                                    const startNumber = parseInt(document.getElementById('start_number')?.value || 1);
                                    let parts = [];
                                    for (let i = 0; i < 4; i++) {
                                        if (types[i] === 'empty') continue;
                                        if (types[i] === 'text') {
                                            parts.push(values[i] || `ELEMENT${i+1}`);
                                        } else if (types[i] === 'date') {
                                            parts.push('20260107');
                                        } else if (types[i] === 'time') {
                                            parts.push('1200');
                                        } else if (types[i] === 'number') {
                                            parts.push(String(startNumber).padStart(4, '0'));
                                        } else if (types[i] === 'supplier') {
                                            parts.push('SUPPL');
                                        }
                                    }
                                    let preview = '';
                                    for (let i = 0; i < parts.length; i++) {
                                        preview += parts[i];
                                        if (i < parts.length - 1) preview += seps[i] || '_';
                                    }
                                    document.getElementById('order-name-preview').textContent = preview;
                                }
                                document.getElementById('order-settings-form').addEventListener('input', getOrderNamePreview);
                                window.addEventListener('DOMContentLoaded', getOrderNamePreview);
                            </script>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Zapisz format nazwy zamówienia
                        </button>
                    </form>
                    
                    <script>
                        function toggleElementInput(element, type) {
                            document.getElementById(element + '_value').style.display = type === 'text' ? 'block' : 'none';
                            getOrderNamePreview();
                        }
                    </script>
                </div>
            </div>

            <!-- Podsekcja: Ustawienia Kodów QR (rozwijalna) -->
            <div class="border rounded mb-4">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-4 cursor-pointer hover:bg-gray-50 rounded" data-target="qr-settings-content">
                    <span class="toggle-arrow text-base">▶</span>
                    <h4 class="font-semibold text-gray-800">Ustawienia Kodów QR</h4>
                </button>
                <div id="qr-settings-content" class="collapsible-content hidden p-4 border-t bg-gray-50">
                    <p class="text-gray-600 text-sm mb-4 font-semibold">Konfigurator formatu kodu QR:</p>
                    
                    @php
                        $qrSettings = \DB::table('qr_settings')->first();
                        if (!$qrSettings) {
                            $qrSettings = (object)[
                                'element1_type' => 'product_name',
                                'element1_value' => '',
                                'separator1' => '_',
                                'element2_type' => 'location',
                                'element2_value' => '',
                                'separator2' => '_',
                                'element3_type' => 'empty',
                                'element3_value' => '',
                                'separator3' => '_',
                                'element4_type' => 'number',
                                'start_number' => 1,
                            ];
                        }
                    @endphp
                    
                    <form action="{{ route('magazyn.qr-settings.save') }}" method="POST" class="space-y-4" id="qr-settings-form">
                        @csrf
                        
                        {{-- Element 1 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 1:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element1_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleQrElementInput('element1', this.value)">
                                    <option value="empty" {{ ($qrSettings->element1_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="product_name" {{ ($qrSettings->element1_type ?? '') === 'product_name' ? 'selected' : '' }}>Nazwa produktu</option>
                                    <option value="text" {{ ($qrSettings->element1_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($qrSettings->element1_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($qrSettings->element1_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element1_value" 
                                    id="qr_element1_value"
                                    value="{{ $qrSettings->element1_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="20"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-32"
                                    style="{{ ($qrSettings->element1_type ?? 'product_name') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator1" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($qrSettings->separator1 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($qrSettings->separator1 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($qrSettings->separator1 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($qrSettings->separator1 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($qrSettings->separator1 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($qrSettings->separator1 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Element 2 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 2:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element2_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleQrElementInput('element2', this.value)">
                                    <option value="empty" {{ ($qrSettings->element2_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="location" {{ ($qrSettings->element2_type ?? '') === 'location' ? 'selected' : '' }}>Lokalizacja</option>
                                    <option value="text" {{ ($qrSettings->element2_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($qrSettings->element2_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($qrSettings->element2_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element2_value" 
                                    id="qr_element2_value"
                                    value="{{ $qrSettings->element2_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="20"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-32"
                                    style="{{ ($qrSettings->element2_type ?? 'location') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator2" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($qrSettings->separator2 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($qrSettings->separator2 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($qrSettings->separator2 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($qrSettings->separator2 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($qrSettings->separator2 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($qrSettings->separator2 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Element 3 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 3:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element3_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleQrElementInput('element3', this.value)">
                                    <option value="empty" {{ ($qrSettings->element3_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="text" {{ ($qrSettings->element3_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                    <option value="date" {{ ($qrSettings->element3_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="time" {{ ($qrSettings->element3_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                </select>
                                <input 
                                    type="text" 
                                    name="element3_value" 
                                    id="qr_element3_value"
                                    value="{{ $qrSettings->element3_value ?? '' }}"
                                    placeholder="Wartość"
                                    maxlength="20"
                                    class="px-2 py-1 border border-gray-400 rounded text-sm w-32"
                                    style="{{ ($qrSettings->element3_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                                >
                                <div class="flex items-center gap-1">
                                    <label class="text-xs text-gray-600">Separator:</label>
                                    <select name="separator3" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                        <option value="_" {{ ($qrSettings->separator3 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                        <option value="-" {{ ($qrSettings->separator3 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                        <option value="," {{ ($qrSettings->separator3 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                        <option value="." {{ ($qrSettings->separator3 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                        <option value="/" {{ ($qrSettings->separator3 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                        <option value="\\" {{ ($qrSettings->separator3 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        
                        {{-- Element 4 --}}
                        <div class="bg-white p-3 rounded border">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Element 4:</label>
                            <div class="flex gap-2 items-center">
                                <select name="element4_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleQrElement4Input(this.value)">
                                    <option value="empty" {{ ($qrSettings->element4_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                    <option value="date" {{ ($qrSettings->element4_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                    <option value="number" {{ ($qrSettings->element4_type ?? '') === 'number' ? 'selected' : '' }}>Liczba (inkrementowana)</option>
                                </select>
                                <div id="qr_element4_number_input" class="flex items-center gap-1" style="{{ ($qrSettings->element4_type ?? 'empty') !== 'number' ? 'display:none;' : '' }}">
                                    <label class="text-xs text-gray-600">Liczba startowa:</label>
                                    <input 
                                        type="number" 
                                        name="start_number" 
                                        value="{{ $qrSettings->start_number ?? 1 }}"
                                        min="1"
                                        class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                    >
                                </div>
                            </div>
                        </div>
                        
                        {{-- Wzór kodu QR --}}
                        <div class="bg-blue-50 p-4 rounded border border-blue-200">
                            <label class="block text-sm font-semibold text-gray-700 mb-2">Wzór kodu QR:</label>
                            <div class="font-mono text-sm text-gray-800" id="qr-pattern">
                                <span class="text-blue-600" id="pattern-preview">Wybierz elementy powyżej, aby zobaczyć wzór</span>
                            </div>
                        </div>
                        
                        <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                            Zapisz ustawienia kodów QR
                        </button>
                    </form>
                    
                    <script>
                        function toggleQrElementInput(element, type) {
                            document.getElementById('qr_' + element + '_value').style.display = type === 'text' ? 'block' : 'none';
                            updateQrPattern();
                        }
                        
                        function toggleQrElement4Input(type) {
                            document.getElementById('qr_element4_number_input').style.display = type === 'number' ? 'flex' : 'none';
                            updateQrPattern();
                        }
                        
                        function updateQrPattern() {
                            const element1Type = document.querySelector('[name="element1_type"]').value;
                            const element1Value = document.getElementById('qr_element1_value').value;
                            const separator1 = document.querySelector('[name="separator1"]').value;
                            
                            const element2Type = document.querySelector('[name="element2_type"]').value;
                            const element2Value = document.getElementById('qr_element2_value').value;
                            const separator2 = document.querySelector('[name="separator2"]').value;
                            
                            const element3Type = document.querySelector('[name="element3_type"]').value;
                            const element3Value = document.getElementById('qr_element3_value').value;
                            const separator3 = document.querySelector('[name="separator3"]').value;
                            
                            const element4Type = document.querySelector('[name="element4_type"]').value;
                            const startNumber = document.querySelector('[name="start_number"]').value;
                            
                            let pattern = '';
                            
                            // Element 1
                            if (element1Type !== 'empty') {
                                if (element1Type === 'product_name') pattern += '[NAZWA_PRODUKTU]';
                                else if (element1Type === 'text') pattern += element1Value || '[TEKST]';
                                else if (element1Type === 'date') pattern += '[YYYYMMDD]';
                                else if (element1Type === 'time') pattern += '[HHMM]';
                                
                                if (element2Type !== 'empty' || element3Type !== 'empty' || element4Type !== 'empty') {
                                    pattern += separator1;
                                }
                            }
                            
                            // Element 2
                            if (element2Type !== 'empty') {
                                if (element2Type === 'location') pattern += '[LOKALIZACJA]';
                                else if (element2Type === 'text') pattern += element2Value || '[TEKST]';
                                else if (element2Type === 'date') pattern += '[YYYYMMDD]';
                                else if (element2Type === 'time') pattern += '[HHMM]';
                                
                                if (element3Type !== 'empty' || element4Type !== 'empty') {
                                    pattern += separator2;
                                }
                            }
                            
                            // Element 3
                            if (element3Type !== 'empty') {
                                if (element3Type === 'text') pattern += element3Value || '[TEKST]';
                                else if (element3Type === 'date') pattern += '[YYYYMMDD]';
                                else if (element3Type === 'time') pattern += '[HHMM]';
                                
                                if (element4Type !== 'empty') {
                                    pattern += separator3;
                                }
                            }
                            
                            // Element 4
                            if (element4Type !== 'empty') {
                                if (element4Type === 'date') pattern += '[YYYYMMDD]';
                                else if (element4Type === 'number') pattern += '[' + startNumber + ', ' + (parseInt(startNumber) + 1) + ', ' + (parseInt(startNumber) + 2) + '...]';
                            }
                            
                            document.getElementById('pattern-preview').textContent = pattern || 'Wybierz elementy powyżej, aby zobaczyć wzór';
                        }
                        
                        // Update pattern on page load
                        document.addEventListener('DOMContentLoaded', function() {
                            updateQrPattern();
                            
                            // Add event listeners to all inputs
                            document.querySelectorAll('[name^="element"], [name^="separator"], [name="start_number"]').forEach(el => {
                                el.addEventListener('change', updateQrPattern);
                                el.addEventListener('input', updateQrPattern);
                            });
                        });
                    </script>
                </div>
            </div>
        </div>
    </div>
    @endif

</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        // Sprawdź czy jest komunikat sukcesu i otwórz odpowiednie sekcje
        @if(session('success'))
            const successMessage = @json(session('success'));
            
            // Jeśli dodano użytkownika lub dostawcę, pozostaw sekcję otwartą
            if (successMessage.includes('użytkownik') || successMessage.includes('Użytkownik')) {
                const usersContent = document.getElementById('users-content');
                const addUserContent = document.getElementById('add-user-form-content');
                const usersArrow = document.querySelector('[data-target="users-content"] .toggle-arrow');
                const addUserArrow = document.querySelector('[data-target="add-user-form-content"] .toggle-arrow');
                
                if (usersContent) {
                    usersContent.classList.remove('hidden');
                    if (usersArrow) usersArrow.textContent = '▼';
                }
                if (addUserContent) {
                    addUserContent.classList.remove('hidden');
                    if (addUserArrow) addUserArrow.textContent = '▼';
                }
            }
            
            if (successMessage.includes('dostawca') || successMessage.includes('Dostawca')) {
                const suppliersContent = document.getElementById('suppliers-content');
                const addSupplierContent = document.getElementById('add-supplier-content');
                const suppliersArrow = document.querySelector('[data-target="suppliers-content"] .toggle-arrow');
                const addSupplierArrow = document.querySelector('[data-target="add-supplier-content"] .toggle-arrow');
                
                if (suppliersContent) {
                    suppliersContent.classList.remove('hidden');
                    if (suppliersArrow) suppliersArrow.textContent = '▼';
                }
                if (addSupplierContent) {
                    addSupplierContent.classList.remove('hidden');
                    if (addSupplierArrow) addSupplierArrow.textContent = '▼';
                }
            }
        @endif

        // Obsługa rozwijania/zamykania sekcji
        document.querySelectorAll('.collapsible-btn').forEach(function(btn) {
            btn.addEventListener('click', function(e) {
                e.stopPropagation();
                var targetId = btn.getAttribute('data-target');
                var content = document.getElementById(targetId);
                var arrow = btn.querySelector('.toggle-arrow');
                if (content && content.classList.contains('hidden')) {
                    content.classList.remove('hidden');
                    if (arrow) arrow.textContent = '▼';
                } else if (content) {
                    content.classList.add('hidden');
                    if (arrow) arrow.textContent = '▶';
                }
            });
        });
        
        // Obsługa hashowania w URL (np. #offer-settings)
        if (window.location.hash) {
            const hash = window.location.hash.substring(1); // Usuń #
            const element = document.getElementById(hash);
            if (element) {
                // Znajdź przycisk rozwijający dla tej sekcji
                const parentSection = element.closest('.border.rounded');
                if (parentSection) {
                    const btn = parentSection.querySelector('.collapsible-btn');
                    if (btn) {
                        btn.click(); // Rozwiń sekcję
                        // Jeśli to podsekcja w "Inne Ustawienia", rozwiń też parent
                        const otherSettings = document.getElementById('other-settings-content');
                        if (otherSettings && otherSettings.contains(element)) {
                            const parentBtn = document.querySelector('[data-target="other-settings-content"]');
                            if (parentBtn && otherSettings.classList.contains('hidden')) {
                                parentBtn.click();
                            }
                        }
                    }
                    // Przewiń do elementu
                    setTimeout(() => {
                        element.scrollIntoView({ behavior: 'smooth', block: 'start' });
                    }, 100);
                }
            }
        }
        
        // Przycisk pobierania danych dostawcy po NIP
        const fetchNipBtn = document.getElementById('fetch-nip-btn');
        const nipInput = document.getElementById('nip-input');
        
        if (fetchNipBtn && nipInput) {
            fetchNipBtn.addEventListener('click', function() {
                const nip = nipInput.value.trim().replace(/[^0-9]/g, '');
                if (!nip || nip.length !== 10) {
                    alert('Wpisz poprawny NIP (10 cyfr)');
                    return;
                }
                
                fetchNipBtn.disabled = true;
                fetchNipBtn.textContent = 'Pobieranie...';
                
                fetch(`/magazyn/ustawienia/supplier/fetch-by-nip?nip=${encodeURIComponent(nip)}`)
                    .then(response => response.json())
                    .then(result => {
                        if (result.success && result.data) {
                            // Wypełnij formularz danymi z GUS
                            const data = result.data;
                            document.getElementById('supplier-name').value = data.name || '';
                            document.getElementById('supplier-nip').value = data.nip || '';
                            document.getElementById('supplier-address').value = data.address || '';
                            document.getElementById('supplier-city').value = data.city || '';
                            
                            const postalInput = document.getElementById('supplier-postal-code');
                            if (postalInput) {
                                postalInput.value = data.postal_code || '';
                            }
                            
                            const phoneInput = document.getElementById('supplier-phone');
                            if (phoneInput && data.phone) {
                                phoneInput.value = data.phone;
                            }
                            
                            const emailInput = document.getElementById('supplier-email');
                            if (emailInput && data.email) {
                                emailInput.value = data.email;
                            }
                            
                            alert('Dane pobrane pomyślnie! ' + (result.message || 'Sprawdź i uzupełnij pozostałe pola.'));
                        } else {
                            alert(result.message || 'Nie znaleziono firmy o podanym NIP');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('Błąd podczas pobierania danych. Sprawdź połączenie internetowe.');
                    })
                    .finally(() => {
                        fetchNipBtn.disabled = false;
                        fetchNipBtn.textContent = 'Pobierz dane';
                    });
            });
        }
        
        // Podświetlanie skróconej nazwy dostawcy na czerwono dopóki nie jest wypełniona
        const supplierShortNameInput = document.getElementById('supplier-short-name');
        if (supplierShortNameInput) {
            supplierShortNameInput.addEventListener('input', function() {
                if (this.value.trim().length > 0) {
                    this.classList.remove('border-red-500', 'bg-red-50');
                    this.classList.add('border-green-500', 'bg-white');
                } else {
                    this.classList.remove('border-green-500', 'bg-white');
                    this.classList.add('border-red-500', 'bg-red-50');
                }
            });
        }
        
        // Auto-generowanie skróconej nazwy użytkownika
        var firstNameInput = document.getElementById('first_name');
        var lastNameInput = document.getElementById('last_name');
        var shortNameInput = document.getElementById('short_name');
        
        if (firstNameInput && lastNameInput && shortNameInput) {
            function generateShortName() {
                var firstName = firstNameInput.value.trim();
                var lastName = lastNameInput.value.trim();
                
                if (firstName.length >= 3 && lastName.length >= 3) {
                    var firstPart = firstName.charAt(0).toUpperCase() + firstName.substring(1, 3).toLowerCase();
                    var lastPart = lastName.charAt(0).toUpperCase() + lastName.substring(1, 3).toLowerCase();
                    shortNameInput.value = firstPart + lastPart;
                }
            }
            
            firstNameInput.addEventListener('input', generateShortName);
            lastNameInput.addEventListener('input', generateShortName);
        }
    });

    // Pobierz dane firmy po NIP (Dane Mojej Firmy)
    var fetchCompanyBtn = document.getElementById('fetch-company-nip-btn');
    var companyNipInput = document.getElementById('company-nip-input');
    if (fetchCompanyBtn && companyNipInput) {
        fetchCompanyBtn.addEventListener('click', function() {
            var nip = companyNipInput.value.replace(/[^0-9]/g, '');
            if (nip.length !== 10) {
                alert('Podaj prawidłowy NIP (10 cyfr)');
                return;
            }
            fetch(`/magazyn/ustawienia/company/fetch-by-nip?nip=${nip}`)
                .then(res => res.json())
                .then(data => {
                    if (data.success && data.data) {
                        document.getElementById('company-name').value = data.data.name || '';
                        document.getElementById('company-nip').value = data.data.nip || '';
                        document.getElementById('company-address').value = data.data.address || '';
                        document.getElementById('company-city').value = data.data.city || '';
                        document.getElementById('company-postal-code').value = data.data.postal_code || '';
                    } else {
                        alert(data.message || 'Nie znaleziono danych dla podanego NIP');
                    }
                })
                .catch(() => alert('Błąd podczas pobierania danych firmy.'));
        });
    }

    // Obsługa modala edycji dostawcy
    var editSupplierModal = document.getElementById('edit-supplier-modal');
    var editSupplierForm = document.getElementById('edit-supplier-form');
    var closeEditSupplierModal = document.getElementById('close-edit-supplier-modal');
    var cancelEditSupplier = document.getElementById('cancel-edit-supplier');

    document.querySelectorAll('.edit-supplier-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var supplierId = btn.getAttribute('data-supplier-id');
            var supplierName = btn.getAttribute('data-supplier-name');
            var supplierShortName = btn.getAttribute('data-supplier-short-name');
            var supplierNip = btn.getAttribute('data-supplier-nip');
            var supplierAddress = btn.getAttribute('data-supplier-address');
            var supplierCity = btn.getAttribute('data-supplier-city');
            var supplierPostalCode = btn.getAttribute('data-supplier-postal-code');
            var supplierPhone = btn.getAttribute('data-supplier-phone');
            var supplierEmail = btn.getAttribute('data-supplier-email');
            var supplierLogo = btn.getAttribute('data-supplier-logo');
            var supplierIsSupplier = btn.getAttribute('data-supplier-is-supplier');
            var supplierIsClient = btn.getAttribute('data-supplier-is-client');

            // Ustaw action formularza
            editSupplierForm.action = '/magazyn/ustawienia/supplier/' + supplierId;

            // Wypełnij pola formularza
            document.getElementById('edit-supplier-name').value = supplierName || '';
            document.getElementById('edit-supplier-short-name').value = supplierShortName || '';
            document.getElementById('edit-supplier-nip').value = supplierNip || '';
            document.getElementById('edit-supplier-address').value = supplierAddress || '';
            document.getElementById('edit-supplier-city').value = supplierCity || '';
            document.getElementById('edit-supplier-postal-code').value = supplierPostalCode || '';
            document.getElementById('edit-supplier-phone').value = supplierPhone || '';
            document.getElementById('edit-supplier-email').value = supplierEmail || '';
            
            // Ustaw checkboxy dla typu podmiotu
            document.getElementById('edit-supplier-is-supplier').checked = supplierIsSupplier === '1';
            document.getElementById('edit-supplier-is-client').checked = supplierIsClient === '1';

            // Pokaż aktualne logo
            var currentLogoDiv = document.getElementById('edit-supplier-current-logo');
            if (supplierLogo && supplierLogo.startsWith('data:image')) {
                currentLogoDiv.innerHTML = '<img src="' + supplierLogo + '" alt="Logo" class="h-12 w-auto">';
            } else if (supplierLogo) {
                currentLogoDiv.innerHTML = '<img src="/storage/' + supplierLogo + '" alt="Logo" class="h-12 w-auto">';
            } else {
                currentLogoDiv.innerHTML = '<span class="text-gray-400 text-sm">Brak logo</span>';
            }

            // Pokaż modal
            editSupplierModal.classList.remove('hidden');
        });
    });

    function closeSupplierModal() {
        editSupplierModal.classList.add('hidden');
    }

    if (closeEditSupplierModal) {
        closeEditSupplierModal.addEventListener('click', closeSupplierModal);
    }
    if (cancelEditSupplier) {
        cancelEditSupplier.addEventListener('click', closeSupplierModal);
    }

    // Zamknij modal po kliknięciu poza nim
    editSupplierModal.addEventListener('click', function(e) {
        if (e.target === editSupplierModal) {
            closeSupplierModal();
        }
    });

    // Edycja kategorii
    function enableCategoryEdit(categoryId) {
        const input = document.getElementById('category-input-' + categoryId);
        const saveBtn = document.getElementById('save-btn-' + categoryId);
        const cancelBtn = document.getElementById('cancel-btn-' + categoryId);
        
        // Usuń licznik z wartości
        const originalName = input.dataset.originalName;
        input.value = originalName;
        
        input.removeAttribute('readonly');
        input.classList.remove('bg-gray-100');
        input.classList.add('bg-white', 'border-blue-500');
        input.focus();
        input.select();
        
        saveBtn.classList.remove('hidden');
        cancelBtn.classList.remove('hidden');
    }

    function cancelCategoryEdit(categoryId, originalName, count) {
        const input = document.getElementById('category-input-' + categoryId);
        const saveBtn = document.getElementById('save-btn-' + categoryId);
        const cancelBtn = document.getElementById('cancel-btn-' + categoryId);
        
        input.value = originalName + ' (' + count + ')';
        input.setAttribute('readonly', 'readonly');
        input.classList.remove('bg-white', 'border-blue-500');
        input.classList.add('bg-gray-100');
        
        saveBtn.classList.add('hidden');
        cancelBtn.classList.add('hidden');
    }
</script>

<!-- Ikony Material Icons -->
<link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
</body>
</html>
