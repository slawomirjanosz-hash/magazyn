<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Ustawienia</title>
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-50">

@include('parts.menu')

<div class="max-w-6xl mx-auto px-6 py-6">

    <!-- Nagłówek z Statystykami -->
    <div class="flex items-center justify-between mb-6">
        <h2 class="text-2xl font-bold">⚙️ Ustawienia Magazynu</h2>
        <div class="bg-white rounded shadow p-2 w-96">
            <div class="flex items-center gap-4">
                <p class="text-sm font-semibold whitespace-nowrap">Statystyki magazynu:</p>
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

    <!-- Sekcja: Zarządzanie kategoriami (rozwijalna) -->
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="categories-content">
            <span class="toggle-arrow text-lg">▼</span>
            <h3 class="text-xl font-semibold">Kategorie</h3>
        </button>
        <div id="categories-content" class="collapsible-content p-6 border-t">
            <div class="mb-4">
                <p class="text-gray-600 mb-3">Lista aktualnych kategorii:</p>
                <div class="flex flex-col gap-2">
                    @forelse($categories as $cat)
                        <div class="flex items-center gap-2">
                            <span class="category-box px-2 py-0.5 bg-gray-100 rounded border border-gray-300 text-xs whitespace-nowrap">{{ $cat->name }}</span>
                            <span class="count-badge bg-blue-600 text-white text-xs px-1.5 py-0 rounded-full">{{ $cat->parts_count }}</span>
                            @if($cat->parts_count > 0)
                                <form action="{{ route('magazyn.category.clearContents', $cat->id) }}" method="POST" class="inline" id="clear-form-{{ $cat->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-orange-600 hover:text-orange-800 font-bold text-sm leading-none" title="Usuń zawartość kategorii" onclick="if(confirm('Czy na pewno chcesz usunąć zawartość kategorii &quot;{{ $cat->name }}&quot;?')) { document.getElementById('clear-form-{{ $cat->id }}').submit(); }">
                                        🗑️
                                    </button>
                                </form>
                            @endif
                            @if($cat->parts_count === 0)
                                <form action="{{ route('magazyn.category.delete', $cat->id) }}" method="POST" class="inline" id="delete-form-{{ $cat->id }}">
                                    @csrf
                                    @method('DELETE')
                                    <button type="button" class="text-red-600 hover:text-red-800 font-bold text-sm leading-none" title="Usuń kategorię" onclick="if(confirm('Czy na pewno usunąć kategorię &quot;{{ $cat->name }}&quot;?')) { document.getElementById('delete-form-{{ $cat->id }}').submit(); }">
                                        ✕
                                    </button>
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

    <!-- Sekcja: Zarządzanie dostawcami (rozwijalna) -->
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="suppliers-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Dostawcy</h3>
        </button>
        <div id="suppliers-content" class="collapsible-content hidden p-6 border-t">
            <div class="mb-4">
                <p class="text-gray-600 mb-3">Lista dostawców:</p>
                <div class="overflow-x-auto">
                    <table class="w-full border border-collapse text-xs">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="border p-2 text-left">Logo</th>
                                <th class="border p-2 text-left">Nazwa</th>
                                <th class="border p-2 text-left">Skrót</th>
                                <th class="border p-2 text-left">NIP</th>
                                <th class="border p-2 text-left">Adres</th>
                                <th class="border p-2 text-left">Kod pocztowy</th>
                                <th class="border p-2 text-left">Miasto</th>
                                <th class="border p-2 text-left">Telefon</th>
                                <th class="border p-2 text-left">Email</th>
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
                                    <td class="border p-2">{{ $supplier->nip ?? '-' }}</td>
                                    <td class="border p-2">{{ $supplier->address ? 'UL. ' . $supplier->address : '-' }}</td>
                                    <td class="border p-2">{{ $supplier->postal_code ?? '-' }}</td>
                                    <td class="border p-2">{{ $supplier->city ?? '-' }}</td>
                                    <td class="border p-2">{{ $supplier->phone ?? '-' }}</td>
                                    <td class="border p-2">{{ $supplier->email ?? '-' }}</td>
                                    <td class="border p-2 text-center">
                                        <form action="{{ route('magazyn.supplier.delete', $supplier->id) }}" method="POST" class="inline" onsubmit="return confirm('Czy na pewno usunąć dostawcę {{ $supplier->name }}?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="text-red-600 hover:text-red-800">❌</button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td class="border p-2 text-center text-gray-400 italic" colspan="10">Brak dostawców</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="border-t pt-4 mt-4">
                <h4 class="font-semibold mb-2">Dodaj dostawcę</h4>
                
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
                    <input 
                        type="text" 
                        name="name" 
                        id="supplier-name"
                        placeholder="Nazwa firmy *" 
                        class="px-3 py-2 border border-gray-300 rounded"
                        required
                    >
                    <input 
                        type="text" 
                        name="short_name" 
                        id="supplier-short-name"
                        placeholder="Skrót nazwy (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                    >
                    <input 
                        type="text" 
                        name="nip" 
                        id="supplier-nip"
                        placeholder="NIP (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                        maxlength="10"
                    >
                    <input 
                        type="text" 
                        name="address" 
                        id="supplier-address"
                        placeholder="Adres (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                    >
                    <input 
                        type="text" 
                        name="city" 
                        id="supplier-city"
                        placeholder="Miasto (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                    >
                    <input 
                        type="text" 
                        name="postal_code" 
                        id="supplier-postal-code"
                        placeholder="Kod pocztowy (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                    >
                    <input 
                        type="text" 
                        name="phone" 
                        id="supplier-phone"
                        placeholder="Telefon (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded"
                    >
                    <input 
                        type="email" 
                        name="email" 
                        id="supplier-email"
                        placeholder="Email (opcjonalnie)" 
                        class="px-3 py-2 border border-gray-300 rounded col-span-2"
                    >
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

    <!-- Sekcja: Dane Mojej Firmy (rozwijalna) -->
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

    <!-- Sekcja: Zarządzanie użytkownikami (rozwijalna) -->
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="users-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Użytkownicy</h3>
        </button>
        <div id="users-content" class="collapsible-content p-6 border-t">
            <div class="border-b pb-4 mb-4">
                <h4 class="font-semibold mb-3">Dodaj nowego użytkownika</h4>
                <form action="{{ route('magazyn.user.add') }}" method="POST" class="flex flex-col gap-3">
                    @csrf
                    <input 
                        type="text" 
                        name="name" 
                        placeholder="Nazwa użytkownika" 
                        class="px-3 py-2 border border-gray-300 rounded @error('name') border-red-500 @enderror"
                        autocomplete="off"
                        required
                    >
                    @error('name')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    
                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Email" 
                        class="px-3 py-2 border border-gray-300 rounded @error('email') border-red-500 @enderror"
                        autocomplete="off"
                        required
                    >
                    @error('email')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    
                    <input 
                        type="text" 
                        name="phone" 
                        placeholder="Numer telefonu" 
                        class="px-3 py-2 border border-gray-300 rounded @error('phone') border-red-500 @enderror"
                        autocomplete="off"
                    >
                    @error('phone')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    
                    <input 
                        type="password" 
                        name="password" 
                        placeholder="Hasło (opcjonalne)" 
                        class="px-3 py-2 border border-gray-300 rounded @error('password') border-red-500 @enderror"
                        autocomplete="new-password"
                    >
                    @error('password')
                        <p class="text-red-600 text-sm">{{ $message }}</p>
                    @enderror
                    
                    <button 
                        type="submit" 
                        class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
                    >
                        Dodaj użytkownika
                    </button>
                </form>
            </div>
            
            <div>
                <p class="text-gray-600 mb-3">Lista użytkowników:</p>
                <div class="flex flex-col gap-2">
                    @forelse(\App\Models\User::all() as $user)
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
                                        @if($user->can_settings)
                                            <span class="text-lg" title="Dostęp do Ustawienia">⚙️</span>
                                        @endif
                                        @if($user->can_delete_orders)
                                            <span class="text-lg" title="Może usuwać zamówienia">🗑️</span>
                                        @endif
                                        @if(!$user->can_view_catalog && !$user->can_add && !$user->can_remove && !$user->can_orders && !$user->can_settings)
                                            <span class="text-gray-400 text-xs italic">Brak uprawnień</span>
                                        @endif
                                    @endif
                                </div>
                            </div>
                            <div class="flex gap-2">
                                <a href="{{ route('magazyn.user.edit', $user->id) }}" class="text-blue-600 hover:text-blue-800 font-bold text-sm" title="Edytuj użytkownika">
                                    ✏️
                                </a>
                                @if(!$user->is_admin)
                                    <form action="{{ route('magazyn.user.delete', $user->id) }}" method="POST" class="inline" id="delete-user-form-{{ $user->id }}">
                                        @csrf
                                        @method('DELETE')
                                        <button type="button" class="text-red-600 hover:text-red-800 font-bold text-sm" title="Usuń użytkownika" onclick="if(confirm('Czy na pewno usunąć użytkownika &quot;{{ $user->name }}&quot;?')) { document.getElementById('delete-user-form-{{ $user->id }}').submit(); }">
                                            ✕
                                        </button>
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

    <!-- Sekcja: Ustawienia eksportu (rozwijalna) -->
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="export-settings-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Ustawienia eksportu</h3>
        </button>
        <div id="export-settings-content" class="collapsible-content p-6 border-t">
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

    <!-- Sekcja: Inne Ustawienia (rozwijalna) -->
    <div class="bg-white rounded shadow mb-6">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="other-settings-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-xl font-semibold">Inne Ustawienia</h3>
        </button>
        <div id="other-settings-content" class="collapsible-content p-6 border-t">
            <p class="text-gray-600 mb-4">Dodatkowe ustawienia systemu:</p>
            
            <!-- Podsekcja: Ustawienia Zamówień (rozwijalna) -->
            <div class="border rounded mb-4">
                <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-4 cursor-pointer hover:bg-gray-50 rounded" data-target="orders-settings-content">
                    <span class="toggle-arrow text-base">▶</span>
                    <h4 class="font-semibold text-gray-800">Ustawienia Zamówień</h4>
                </button>
                <div id="orders-settings-content" class="collapsible-content p-4 border-t bg-gray-50">
                    <p class="text-gray-600 text-sm mb-3">Konfiguracja funkcji zamówień:</p>
                    
                    <!-- Konfiguracja nazwy zamówienia -->
                    <div class="bg-white border rounded p-4 mb-4">
                        <h5 class="font-semibold text-gray-800 mb-3">Konfiguracja nazwy zamówienia</h5>
                        <form action="{{ route('magazyn.order-settings.save') }}" method="POST" class="space-y-3" id="order-settings-form">
                            @csrf
                            <!-- Element 1 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Element 1</label>
                                    <select name="element1_type" id="element1_type" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="empty" {{ (isset($orderSettings) && $orderSettings->element1_type == 'empty') ? 'selected' : '' }}>Puste</option>
                                        <option value="text" {{ (isset($orderSettings) && $orderSettings->element1_type == 'text') ? 'selected' : '' }}>Tekst</option>
                                        <option value="date" {{ (isset($orderSettings) && $orderSettings->element1_type == 'date') ? 'selected' : '' }}>Format daty</option>
                                        <option value="time" {{ (isset($orderSettings) && $orderSettings->element1_type == 'time') ? 'selected' : '' }}>Format godziny</option>
                                        <option value="number" {{ (isset($orderSettings) && $orderSettings->element1_type == 'number') ? 'selected' : '' }}>Liczba</option>
                                    </select>
                                </div>
                                <div class="col-span-1" id="element1_value_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element1_type == 'empty') ? 'display:none;' : '' }}">
                                    <label class="block text-xs font-semibold mb-1">Wartość</label>
                                    <input type="text" id="element1_value_text" value="{{ (isset($orderSettings) && in_array($orderSettings->element1_type, ['text', 'number'])) ? $orderSettings->element1_value : '' }}" placeholder="Wpisz wartość" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" {{ (!isset($orderSettings->element1_type) || in_array($orderSettings->element1_type, ['text', 'number'])) ? 'name=element1_value' : '' }} style="{{ (isset($orderSettings) && in_array($orderSettings->element1_type, ['date', 'time'])) ? 'display:none;' : '' }}">
                                    <select id="element1_value_date" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" {{ (isset($orderSettings) && $orderSettings->element1_type == 'date') ? 'name=element1_value' : '' }} style="{{ (isset($orderSettings) && $orderSettings->element1_type == 'date') ? '' : 'display:none;' }}">
                                        <option value="yyyy-mm-dd" {{ (isset($orderSettings) && $orderSettings->element1_value == 'yyyy-mm-dd') ? 'selected' : '' }}>yyyy-mm-dd</option>
                                        <option value="yyyymmdd" {{ (isset($orderSettings) && $orderSettings->element1_value == 'yyyymmdd') ? 'selected' : '' }}>yyyymmdd</option>
                                    </select>
                                    <select id="element1_value_time" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" {{ (isset($orderSettings) && $orderSettings->element1_type == 'time') ? 'name=element1_value' : '' }} style="{{ (isset($orderSettings) && $orderSettings->element1_type == 'time') ? '' : 'display:none;' }}">
                                        <option value="hh-mm-ss" {{ (isset($orderSettings) && $orderSettings->element1_value == 'hh-mm-ss') ? 'selected' : '' }}>hh-mm-ss</option>
                                        <option value="hhmmss" {{ (isset($orderSettings) && $orderSettings->element1_value == 'hhmmss') ? 'selected' : '' }}>hhmmss</option>
                                        <option value="hh-mm" {{ (isset($orderSettings) && $orderSettings->element1_value == 'hh-mm') ? 'selected' : '' }}>hh-mm</option>
                                        <option value="hh" {{ (isset($orderSettings) && $orderSettings->element1_value == 'hh') ? 'selected' : '' }}>hh</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Separator 1 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Separator po elemencie 1</label>
                                    <select name="separator1" id="separator1" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="_" {{ (!isset($orderSettings->separator1) || $orderSettings->separator1 == '_') ? 'selected' : '' }}>Podkreślenie (_)</option>
                                        <option value="-" {{ (isset($orderSettings->separator1) && $orderSettings->separator1 == '-') ? 'selected' : '' }}>Myślnik (-)</option>
                                        <option value="" {{ (isset($orderSettings->separator1) && $orderSettings->separator1 == '') ? 'selected' : '' }}>Brak separacji</option>
                                        <option value="." {{ (isset($orderSettings->separator1) && $orderSettings->separator1 == '.') ? 'selected' : '' }}>Kropka (.)</option>
                                        <option value="," {{ (isset($orderSettings->separator1) && $orderSettings->separator1 == ',') ? 'selected' : '' }}>Przecinek (,)</option>
                                        <option value=";" {{ (isset($orderSettings->separator1) && $orderSettings->separator1 == ';') ? 'selected' : '' }}>Średnik (;)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Element 2 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Element 2</label>
                                    <select name="element2_type" id="element2_type" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="empty" {{ (isset($orderSettings) && $orderSettings->element2_type == 'empty') ? 'selected' : '' }}>Puste</option>
                                        <option value="text" {{ (isset($orderSettings) && $orderSettings->element2_type == 'text') ? 'selected' : '' }}>Tekst lub liczba</option>
                                        <option value="date" {{ (isset($orderSettings) && $orderSettings->element2_type == 'date') ? 'selected' : '' }}>Format daty</option>
                                        <option value="time" {{ (isset($orderSettings) && $orderSettings->element2_type == 'time') ? 'selected' : '' }}>Format godziny</option>
                                    </select>
                                </div>
                                <div class="col-span-1" id="element2_value_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element2_type == 'empty') ? 'display:none;' : '' }}">
                                    <label class="block text-xs font-semibold mb-1">Wartość</label>
                                    <input type="text" name="element2_value" id="element2_value_text" value="{{ (isset($orderSettings) && in_array($orderSettings->element2_type, ['text', 'number'])) ? $orderSettings->element2_value : '' }}" placeholder="Wpisz wartość" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" style="{{ (isset($orderSettings) && in_array($orderSettings->element2_type, ['date', 'time'])) ? 'display:none;' : '' }}">
                                    <select name="element2_value" id="element2_value_date" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" style="{{ (isset($orderSettings) && $orderSettings->element2_type == 'date') ? '' : 'display:none;' }}">
                                        <option value="yyyy-mm-dd" {{ (isset($orderSettings) && $orderSettings->element2_value == 'yyyy-mm-dd') ? 'selected' : '' }}>yyyy-mm-dd</option>
                                        <option value="yyyymmdd" {{ (isset($orderSettings) && $orderSettings->element2_value == 'yyyymmdd') ? 'selected' : '' }}>yyyymmdd</option>
                                    </select>
                                    <select name="element2_value" id="element2_value_time" class="w-full px-2 py-1 border border-gray-300 rounded text-sm" style="{{ (isset($orderSettings) && $orderSettings->element2_type == 'time') ? '' : 'display:none;' }}">
                                        <option value="hh-mm-ss" {{ (isset($orderSettings) && $orderSettings->element2_value == 'hh-mm-ss') ? 'selected' : '' }}>hh-mm-ss</option>
                                        <option value="hhmmss" {{ (isset($orderSettings) && $orderSettings->element2_value == 'hhmmss') ? 'selected' : '' }}>hhmmss</option>
                                        <option value="hh-mm" {{ (isset($orderSettings) && $orderSettings->element2_value == 'hh-mm') ? 'selected' : '' }}>hh-mm</option>
                                        <option value="hh" {{ (isset($orderSettings) && $orderSettings->element2_value == 'hh') ? 'selected' : '' }}>hh</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Separator 2 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Separator po elemencie 2</label>
                                    <select name="separator2" id="separator2" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="_" {{ (!isset($orderSettings->separator2) || $orderSettings->separator2 == '_') ? 'selected' : '' }}>Podkreślenie (_)</option>
                                        <option value="-" {{ (isset($orderSettings->separator2) && $orderSettings->separator2 == '-') ? 'selected' : '' }}>Myślnik (-)</option>
                                        <option value="" {{ (isset($orderSettings->separator2) && $orderSettings->separator2 == '') ? 'selected' : '' }}>Brak separacji</option>
                                        <option value="." {{ (isset($orderSettings->separator2) && $orderSettings->separator2 == '.') ? 'selected' : '' }}>Kropka (.)</option>
                                        <option value="," {{ (isset($orderSettings->separator2) && $orderSettings->separator2 == ',') ? 'selected' : '' }}>Przecinek (,)</option>
                                        <option value=";" {{ (isset($orderSettings->separator2) && $orderSettings->separator2 == ';') ? 'selected' : '' }}>Średnik (;)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Element 3 - Liczba z ilością cyfr -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Element 3</label>
                                    <select name="element3_type" id="element3_type" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="empty" {{ (isset($orderSettings) && $orderSettings->element3_type == 'empty') ? 'selected' : '' }}>Puste</option>
                                        <option value="text" {{ (isset($orderSettings) && $orderSettings->element3_type == 'text') ? 'selected' : '' }}>Tekst</option>
                                        <option value="date" {{ (isset($orderSettings) && $orderSettings->element3_type == 'date') ? 'selected' : '' }}>Format daty</option>
                                        <option value="time" {{ (isset($orderSettings) && $orderSettings->element3_type == 'time') ? 'selected' : '' }}>Format godziny</option>
                                        <option value="number" {{ (isset($orderSettings) && $orderSettings->element3_type == 'number') ? 'selected' : '' }}>Liczba</option>
                                    </select>
                                </div>
                                <div class="col-span-1" id="element3_value_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element3_type == 'text') ? '' : 'display:none;' }}">
                                    <label class="block text-xs font-semibold mb-1">Wartość</label>
                                    <input type="text" name="element3_value" id="element3_value_text" value="{{ (isset($orderSettings) && $orderSettings->element3_type == 'text') ? $orderSettings->element3_value : '' }}" placeholder="Wpisz wartość" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                                <div class="col-span-1" id="element3_date_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element3_type == 'date') ? '' : 'display:none;' }}">
                                    <label class="block text-xs font-semibold mb-1">Format daty</label>
                                    <select name="element3_value" id="element3_value_date" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="yyyy-mm-dd" {{ (isset($orderSettings) && $orderSettings->element3_value == 'yyyy-mm-dd') ? 'selected' : '' }}>yyyy-mm-dd</option>
                                        <option value="yyyymmdd" {{ (isset($orderSettings) && $orderSettings->element3_value == 'yyyymmdd') ? 'selected' : '' }}>yyyymmdd</option>
                                    </select>
                                </div>
                                <div class="col-span-1" id="element3_time_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element3_type == 'time') ? '' : 'display:none;' }}">
                                    <label class="block text-xs font-semibold mb-1">Format godziny</label>
                                    <select name="element3_value" id="element3_value_time" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="hh-mm-ss" {{ (isset($orderSettings) && $orderSettings->element3_value == 'hh-mm-ss') ? 'selected' : '' }}>hh-mm-ss</option>
                                        <option value="hhmmss" {{ (isset($orderSettings) && $orderSettings->element3_value == 'hhmmss') ? 'selected' : '' }}>hhmmss</option>
                                        <option value="hh-mm" {{ (isset($orderSettings) && $orderSettings->element3_value == 'hh-mm') ? 'selected' : '' }}>hh-mm</option>
                                        <option value="hh" {{ (isset($orderSettings) && $orderSettings->element3_value == 'hh') ? 'selected' : '' }}>hh</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Element 3 - Pola dla liczby -->
                            <div class="grid grid-cols-4 gap-2 items-end" id="element3_number_fields" style="{{ (isset($orderSettings) && $orderSettings->element3_type == 'number') ? '' : 'display:none;' }}">
                                <div class="col-span-1" id="element3_digits_wrapper">
                                    <label class="block text-xs font-semibold mb-1">Cyfr</label>
                                    <select name="element3_digits" id="element3_digits" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="1" {{ (isset($orderSettings) && $orderSettings->element3_digits == 1) ? 'selected' : '' }}>1</option>
                                        <option value="2" {{ (isset($orderSettings) && $orderSettings->element3_digits == 2) ? 'selected' : '' }}>2</option>
                                        <option value="3" {{ (isset($orderSettings) && $orderSettings->element3_digits == 3) ? 'selected' : '' }}>3</option>
                                        <option value="4" {{ (isset($orderSettings) && $orderSettings->element3_digits == 4) ? 'selected' : '' }}>4</option>
                                        <option value="5" {{ (isset($orderSettings) && $orderSettings->element3_digits == 5) ? 'selected' : '' }}>5</option>
                                    </select>
                                </div>
                                <div class="col-span-1" id="start_number_wrapper">
                                    <label class="block text-xs font-semibold mb-1">Od</label>
                                    <input type="number" name="start_number" id="start_number" value="{{ $orderSettings->start_number ?? 1 }}" min="0" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                </div>
                            </div>
                            
                            <!-- Separator 3 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Separator po elemencie 3</label>
                                    <select name="separator3" id="separator3" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="_" {{ (!isset($orderSettings->separator3) || $orderSettings->separator3 == '_') ? 'selected' : '' }}>Podkreślenie (_)</option>
                                        <option value="-" {{ (isset($orderSettings->separator3) && $orderSettings->separator3 == '-') ? 'selected' : '' }}>Myślnik (-)</option>
                                        <option value="" {{ (isset($orderSettings->separator3) && $orderSettings->separator3 == '') ? 'selected' : '' }}>Brak separacji</option>
                                        <option value="." {{ (isset($orderSettings->separator3) && $orderSettings->separator3 == '.') ? 'selected' : '' }}>Kropka (.)</option>
                                        <option value="," {{ (isset($orderSettings->separator3) && $orderSettings->separator3 == ',') ? 'selected' : '' }}>Przecinek (,)</option>
                                        <option value=";" {{ (isset($orderSettings->separator3) && $orderSettings->separator3 == ';') ? 'selected' : '' }}>Średnik (;)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Element 4 -->
                            <div class="grid grid-cols-4 gap-2 items-end">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Element 4</label>
                                    <select name="element4_type" id="element4_type" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="empty" {{ (!isset($orderSettings->element4_type) || $orderSettings->element4_type == 'empty') ? 'selected' : '' }}>Puste</option>
                                        <option value="supplier_short_name" {{ (isset($orderSettings) && $orderSettings->element4_type == 'supplier_short_name') ? 'selected' : '' }}>Skrócona nazwa Dostawcy</option>
                                    </select>
                                </div>
                                <div class="col-span-1">
                                    <!-- Puste pole dla spójności layoutu -->
                                </div>
                            </div>
                            
                            <!-- Separator 4 -->
                            <div class="grid grid-cols-4 gap-2 items-end" id="separator4_wrapper" style="{{ (isset($orderSettings) && $orderSettings->element4_type == 'supplier_short_name') ? '' : 'display:none;' }}">
                                <div class="col-span-1">
                                    <label class="block text-xs font-semibold mb-1">Separator po elemencie 4</label>
                                    <select name="separator4" id="separator4" class="w-full px-2 py-1 border border-gray-300 rounded text-sm">
                                        <option value="_" {{ (!isset($orderSettings->separator4) || $orderSettings->separator4 == '_') ? 'selected' : '' }}>Podkreślenie (_)</option>
                                        <option value="-" {{ (isset($orderSettings->separator4) && $orderSettings->separator4 == '-') ? 'selected' : '' }}>Myślnik (-)</option>
                                        <option value="" {{ (isset($orderSettings->separator4) && $orderSettings->separator4 == '') ? 'selected' : '' }}>Brak separacji</option>
                                        <option value="." {{ (isset($orderSettings->separator4) && $orderSettings->separator4 == '.') ? 'selected' : '' }}>Kropka (.)</option>
                                        <option value="," {{ (isset($orderSettings->separator4) && $orderSettings->separator4 == ',') ? 'selected' : '' }}>Przecinek (,)</option>
                                        <option value=";" {{ (isset($orderSettings->separator4) && $orderSettings->separator4 == ';') ? 'selected' : '' }}>Średnik (;)</option>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Podgląd -->
                            <div class="border-t pt-3 mt-3">
                                <p class="text-xs font-semibold mb-1">Podgląd nazwy zamówienia:</p>
                                <div id="order-name-preview" class="bg-gray-100 px-3 py-2 rounded text-sm font-mono">
                                    ZAM-2026-01-02-0001
                                </div>
                                
                                @if(isset($orderSettings))
                                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded" id="saved-config-container">
                                    <p class="text-xs font-semibold text-blue-800 mb-2">Zapisana konfiguracja:</p>
                                    <div id="saved-config-preview" class="bg-white px-3 py-2 rounded text-sm font-mono border">
                                        <!-- Wypełniane przez JavaScript -->
                                    </div>
                                </div>
                                @else
                                <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded" id="saved-config-container" style="display:none;">
                                    <p class="text-xs font-semibold text-blue-800 mb-2">Zapisana konfiguracja:</p>
                                    <div id="saved-config-preview" class="bg-white px-3 py-2 rounded text-sm font-mono border">
                                        <!-- Wypełniane przez JavaScript -->
                                    </div>
                                </div>
                                @endif
                            </div>
                            
                            <button type="submit" class="w-full px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">
                                Zapisz konfigurację
                            </button>
                        </form>
                    </div>
                    
                    <ul class="list-disc list-inside text-gray-700 text-sm space-y-1">
                        <li>Domyślna ilość do zamówienia</li>
                        <li>Format eksportu zamówień</li>
                        <li>Powiadomienia email o zamówieniach</li>
                    </ul>
                    <p class="text-gray-500 text-xs mt-2 italic">Pozostałe funkcjonalności w przygotowaniu...</p>
                </div>
            </div>
            
        </div>
    </div>

</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Wyrównaj kategorie
        const categoryBoxes = document.querySelectorAll('.category-box');
        let maxCategoryWidth = 0;
        
        categoryBoxes.forEach(box => {
            const width = box.offsetWidth;
            if (width > maxCategoryWidth) {
                maxCategoryWidth = width;
            }
        });
        
        categoryBoxes.forEach(box => {
            box.style.width = maxCategoryWidth + 'px';
            box.style.textAlign = 'center';
        });

        // Wyrównaj liczby produktów (badge'i)
        const countBadges = document.querySelectorAll('.count-badge');
        let maxCountWidth = 0;
        
        countBadges.forEach(badge => {
            const width = badge.offsetWidth;
            if (width > maxCountWidth) {
                maxCountWidth = width;
            }
        });
        
        countBadges.forEach(badge => {
            badge.style.width = maxCountWidth + 'px';
            badge.style.display = 'flex';
            badge.style.alignItems = 'center';
            badge.style.justifyContent = 'center';
        });

        // Obsługa rozwijania/składania sekcji
        const collapsibleBtns = document.querySelectorAll('.collapsible-btn');
        
        collapsibleBtns.forEach(btn => {
            const targetId = btn.getAttribute('data-target');
            const content = document.getElementById(targetId);
            const arrow = btn.querySelector('.toggle-arrow');
            
            // Domyślnie wszystkie sekcje są zwinięte
            content.style.display = 'none';
            arrow.textContent = '▶';
            
            btn.addEventListener('click', function() {
                const isVisible = content.style.display === 'block';
                if (isVisible) {
                    content.style.display = 'none';
                    arrow.textContent = '▶';
                } else {
                    content.style.display = 'block';
                    arrow.textContent = '▼';
                }
            });
        });

        // Automatycznie otwórz sekcję użytkowników jeśli był dodany/usunięty użytkownik
        @if(session('success') && str_contains(session('success'), 'Użytkownik'))
            const usersContent = document.getElementById('users-content');
            const usersBtn = document.querySelector('[data-target="users-content"]');
            const usersArrow = usersBtn.querySelector('.toggle-arrow');
            if (usersContent && usersArrow) {
                usersContent.style.display = 'block';
                usersArrow.textContent = '▼';
            }
            
            // Wyczyść pola formularza
            const userForm = document.querySelector('form[action="{{ route('magazyn.user.add') }}"]');
            if (userForm) {
                userForm.reset();
            }
        @endif

        // Zapisz pozycję scrolla przed submitem formularza zamówień
        const orderForm = document.getElementById('order-settings-form');
        if (orderForm) {
            orderForm.addEventListener('submit', function() {
                sessionStorage.setItem('orderSettingsScrollPos', window.scrollY);
            });
        }

        // Automatycznie otwórz sekcję dostawców jeśli był dodany/usunięty dostawca lub wystąpił błąd
        @if((session('success') && str_contains(session('success'), 'Dostawca')) || ($errors->any() && $errors->has('nip')))
            const suppliersContent = document.getElementById('suppliers-content');
            const suppliersBtn = document.querySelector('[data-target="suppliers-content"]');
            const suppliersArrow = suppliersBtn.querySelector('.toggle-arrow');
            if (suppliersContent && suppliersArrow) {
                suppliersContent.classList.remove('hidden');
                suppliersContent.style.display = 'block';
                suppliersArrow.textContent = '▼';
            }
            
            // Wyczyść pola formularza tylko przy sukcesie
            @if(session('success'))
                const supplierForm = document.getElementById('supplier-form');
                if (supplierForm) {
                    supplierForm.reset();
                }
            @endif
        @endif

        // Automatycznie otwórz sekcję danych firmy jeśli były zapisane dane firmy
        @if(session('success') && str_contains(session('success'), 'Dane firmy'))
            const companyContent = document.getElementById('company-content');
            const companyBtn = document.querySelector('[data-target="company-content"]');
            const companyArrow = companyBtn.querySelector('.toggle-arrow');
            if (companyContent && companyArrow) {
                companyContent.classList.remove('hidden');
                companyContent.style.display = 'block';
                companyArrow.textContent = '▼';
            }
        @endif

        // Automatycznie otwórz sekcję Inne Ustawienia i Ustawienia Zamówień po zapisie konfiguracji
        @if(session('success') && str_contains(session('success'), 'Konfiguracja zamówień'))
            const otherContent = document.getElementById('other-settings-content');
            const otherBtn = document.querySelector('[data-target="other-settings-content"]');
            const otherArrow = otherBtn.querySelector('.toggle-arrow');
            if (otherContent && otherArrow) {
                otherContent.classList.remove('hidden');
                otherContent.style.display = 'block';
                otherArrow.textContent = '▼';
            }
            
            const ordersContent = document.getElementById('orders-settings-content');
            const ordersBtn = document.querySelector('[data-target="orders-settings-content"]');
            const ordersArrow = ordersBtn.querySelector('.toggle-arrow');
            if (ordersContent && ordersArrow) {
                ordersContent.classList.remove('hidden');
                ordersContent.style.display = 'block';
                ordersArrow.textContent = '▼';
            }
            
            // Przywróć pozycję scrolla
            const savedScrollPos = sessionStorage.getItem('orderSettingsScrollPos');
            if (savedScrollPos) {
                window.scrollTo(0, parseInt(savedScrollPos));
                sessionStorage.removeItem('orderSettingsScrollPos');
            }
        @endif
    });

    // Pobieranie danych dostawcy po NIP
    document.getElementById('fetch-nip-btn').addEventListener('click', async function() {
        let nip = document.getElementById('nip-input').value.trim();
        
        // Usuń myślniki i spacje z NIP-u
        nip = nip.replace(/[-\s]/g, '');
        
        if (!nip || nip.length !== 10 || !/^\d{10}$/.test(nip)) {
            alert('Wprowadź prawidłowy 10-cyfrowy NIP');
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Pobieranie...';
        
        try {
            const response = await fetch(`{{ route('magazyn.supplier.fetchByNip') }}?nip=${nip}`);
            const data = await response.json();
            
            console.log('API Response:', data); // Debug
            
            if (data.success && data.data) {
                // Wypełnij wszystkie pola
                document.getElementById('supplier-name').value = data.data.name || '';
                document.getElementById('supplier-nip').value = data.data.nip || '';
                document.getElementById('supplier-address').value = data.data.address || '';
                document.getElementById('supplier-city').value = data.data.city || '';
                document.getElementById('supplier-postal-code').value = data.data.postal_code || '';
                document.getElementById('supplier-phone').value = data.data.phone || '';
                document.getElementById('supplier-email').value = data.data.email || '';
                
                // Podświetl pole skrótu na pomarańczowo (ciągłe)
                const shortNameField = document.getElementById('supplier-short-name');
                shortNameField.classList.add('bg-orange-100', 'border-orange-400');
                
                // Usuń podświetlenie gdy użytkownik zacznie pisać
                shortNameField.addEventListener('input', function removeHighlight() {
                    shortNameField.classList.remove('bg-orange-100', 'border-orange-400');
                    shortNameField.removeEventListener('input', removeHighlight);
                }, { once: true });
                
                let message = 'Dane pobrane pomyślnie!';
                if (data.message) {
                    message += '\n\n' + data.message;
                }
                alert(message);
            } else {
                alert(data.message || 'Nie udało się pobrać danych');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Błąd podczas pobierania danych: ' + error.message);
        } finally {
            this.disabled = false;
            this.textContent = 'Pobierz dane';
        }
    });

    // Pobieranie danych firmy po NIP
    document.getElementById('fetch-company-nip-btn').addEventListener('click', async function() {
        let nip = document.getElementById('company-nip-input').value.trim();
        
        // Usuń myślniki i spacje z NIP-u
        nip = nip.replace(/[-\s]/g, '');
        
        if (!nip || nip.length !== 10 || !/^\d{10}$/.test(nip)) {
            alert('Wprowadź prawidłowy 10-cyfrowy NIP');
            return;
        }
        
        this.disabled = true;
        this.textContent = 'Pobieranie...';
        
        try {
            const response = await fetch(`{{ route('magazyn.supplier.fetchByNip') }}?nip=${nip}`);
            const data = await response.json();
            
            if (data.success && data.data) {
                // Wypełnij wszystkie pola firmy
                document.getElementById('company-name').value = data.data.name || '';
                document.getElementById('company-nip').value = data.data.nip || '';
                document.getElementById('company-address').value = data.data.address || '';
                document.getElementById('company-city').value = data.data.city || '';
                document.getElementById('company-postal-code').value = data.data.postal_code || '';
                document.getElementById('company-phone').value = data.data.phone || '';
                document.getElementById('company-email').value = data.data.email || '';
                
                let message = 'Dane pobrane pomyślnie!';
                if (data.message) {
                    message += '\n\n' + data.message;
                }
                alert(message);
            } else {
                alert(data.message || 'Nie udało się pobrać danych');
            }
        } catch (error) {
            console.error('Fetch error:', error);
            alert('Błąd podczas pobierania danych: ' + error.message);
        } finally {
            this.disabled = false;
            this.textContent = 'Pobierz dane';
        }
    });

    // Dynamiczny podgląd nazwy zamówienia
    function updateOrderNamePreview() {
        const element1Type = document.getElementById('element1_type').value;
        const element2Type = document.getElementById('element2_type').value;
        const element3Type = document.getElementById('element3_type').value;
        const element4Type = document.getElementById('element4_type').value;
        const separator1 = document.getElementById('separator1').value;
        const separator2 = document.getElementById('separator2').value;
        const separator3 = document.getElementById('separator3').value;
        const separator4 = document.getElementById('separator4').value;
        const startNumber = document.getElementById('start_number').value || 1;
        
        // Przełączanie widoczności pól dla element1
        const element1Text = document.getElementById('element1_value_text');
        const element1Date = document.getElementById('element1_value_date');
        const element1Time = document.getElementById('element1_value_time');
        const element1ValueWrapper = document.getElementById('element1_value_wrapper');
        
        if (element1Type === 'empty') {
            element1ValueWrapper.style.display = 'none';
        } else if (element1Type === 'text' || element1Type === 'number') {
            element1ValueWrapper.style.display = 'block';
            element1Text.style.display = 'block';
            element1Date.style.display = 'none';
            element1Time.style.display = 'none';
            element1Text.setAttribute('name', 'element1_value');
            element1Date.removeAttribute('name');
            element1Time.removeAttribute('name');
            // Wyczyść wartość jeśli była data/time
            if (element1Text.value && (element1Text.value.includes('-') || element1Text.value.includes(':'))) {
                element1Text.value = '';
            }
        } else if (element1Type === 'date') {
            element1Text.style.display = 'none';
            element1Date.style.display = 'block';
            element1Time.style.display = 'none';
            element1Date.setAttribute('name', 'element1_value');
            element1Text.removeAttribute('name');
            element1Time.removeAttribute('name');
        } else if (element1Type === 'time') {
            element1Text.style.display = 'none';
            element1Date.style.display = 'none';
            element1Time.style.display = 'block';
            element1Time.setAttribute('name', 'element1_value');
            element1Text.removeAttribute('name');
            element1Date.removeAttribute('name');
        }
        
        // Przełączanie widoczności pól dla element2
        const element2Text = document.getElementById('element2_value_text');
        const element2Date = document.getElementById('element2_value_date');
        const element2Time = document.getElementById('element2_value_time');
        const element2ValueWrapper = document.getElementById('element2_value_wrapper');
        
        if (element2Type === 'empty') {
            element2ValueWrapper.style.display = 'none';
        } else if (element2Type === 'text' || element2Type === 'number') {
            element2ValueWrapper.style.display = 'block';
            element2Text.style.display = 'block';
            element2Date.style.display = 'none';
            element2Time.style.display = 'none';
            element2Text.setAttribute('name', 'element2_value');
            element2Date.removeAttribute('name');
            element2Time.removeAttribute('name');
        } else if (element2Type === 'date') {
            element2Text.style.display = 'none';
            element2Date.style.display = 'block';
            element2Time.style.display = 'none';
            element2Date.setAttribute('name', 'element2_value');
            element2Text.removeAttribute('name');
            element2Time.removeAttribute('name');
        } else if (element2Type === 'time') {
            element2Text.style.display = 'none';
            element2Date.style.display = 'none';
            element2Time.style.display = 'block';
            element2Time.setAttribute('name', 'element2_value');
            element2Text.removeAttribute('name');
            element2Date.removeAttribute('name');
        }
        
        // Przełączanie widoczności pól dla element3
        const element3Text = document.getElementById('element3_value_text');
        const element3Date = document.getElementById('element3_value_date');
        const element3Time = document.getElementById('element3_value_time');
        const element3ValueWrapper = document.getElementById('element3_value_wrapper');
        const element3DateWrapper = document.getElementById('element3_date_wrapper');
        const element3TimeWrapper = document.getElementById('element3_time_wrapper');
        const element3NumberFields = document.getElementById('element3_number_fields');
        
        // Ukryj wszystkie wrapery najpierw
        element3ValueWrapper.style.display = 'none';
        element3DateWrapper.style.display = 'none';
        element3TimeWrapper.style.display = 'none';
        element3NumberFields.style.display = 'none';
        
        // Pokaż odpowiednie pola w zależności od typu
        if (element3Type === 'empty') {
            // Wszystkie już ukryte
        } else if (element3Type === 'text') {
            element3ValueWrapper.style.display = 'block';
            element3Text.setAttribute('name', 'element3_value');
            element3Date.removeAttribute('name');
            element3Time.removeAttribute('name');
        } else if (element3Type === 'date') {
            element3DateWrapper.style.display = 'block';
            element3Date.setAttribute('name', 'element3_value');
            element3Text.removeAttribute('name');
            element3Time.removeAttribute('name');
        } else if (element3Type === 'time') {
            element3TimeWrapper.style.display = 'block';
            element3Time.setAttribute('name', 'element3_value');
            element3Text.removeAttribute('name');
            element3Date.removeAttribute('name');
        } else if (element3Type === 'number') {
            element3NumberFields.style.display = '';
            element3Text.removeAttribute('name');
            element3Date.removeAttribute('name');
            element3Time.removeAttribute('name');
        }
        
        // Przełączanie widoczności separator4 dla element4
        const separator4Wrapper = document.getElementById('separator4_wrapper');
        if (element4Type === 'supplier_short_name') {
            separator4Wrapper.style.display = '';
        } else {
            separator4Wrapper.style.display = 'none';
        }
        
        const now = new Date();
        const parts = [];
        
        // Generowanie podglądu dla każdego elementu
        function generatePreview(type, valueField, dateSelect, timeSelect) {
            if (type === 'empty') {
                return null;
            } else if (type === 'text') {
                return valueField.value || 'TEKST';
            } else if (type === 'date') {
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                const format = dateSelect.value || 'yyyy-mm-dd';
                if (format === 'yyyymmdd') {
                    return `${year}${month}${day}`;
                } else {
                    return `${year}-${month}-${day}`;
                }
            } else if (type === 'time') {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                const format = timeSelect.value || 'hh-mm';
                if (format === 'hh-mm-ss') {
                    return `${hours}-${minutes}-${seconds}`;
                } else if (format === 'hhmmss') {
                    return `${hours}${minutes}${seconds}`;
                } else if (format === 'hh-mm') {
                    return `${hours}-${minutes}`;
                } else if (format === 'hh') {
                    return `${hours}`;
                }
            } else if (type === 'number') {
                return valueField.value || '1';
            }
            return '';
        }
        
        // Element 1
        const part1 = generatePreview(element1Type, element1Text, element1Date, element1Time);
        if (part1 !== null) parts.push(part1);
        
        // Element 2
        const part2 = generatePreview(element2Type, element2Text, element2Date, element2Time);
        if (part2 !== null) parts.push(part2);
        
        // Element 3
        if (element3Type === 'empty') {
            // Nie dodawaj
        } else if (element3Type === 'number') {
            const digits = document.getElementById('element3_digits').value || 4;
            parts.push(String(startNumber).padStart(parseInt(digits), '0'));
        } else {
            parts.push(generatePreview(element3Type, element3Text, element3Date, element3Time));
        }
        
        // Element 4
        if (element4Type === 'supplier_short_name') {
            parts.push('DOSTAWCA');
        }
        
        // Aktualizuj podgląd z separatorami
        let preview = '';
        if (parts.length > 0) preview += parts[0];
        if (parts.length > 1) preview += separator1 + parts[1];
        if (parts.length > 2) preview += separator2 + parts[2];
        if (parts.length > 3) preview += separator3 + parts[3];
        document.getElementById('order-name-preview').textContent = preview;
    }
    
    // Nasłuchuj na zmiany we wszystkich polach
    document.getElementById('element1_type').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element1_value_text').addEventListener('input', updateOrderNamePreview);
    document.getElementById('element1_value_date').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element1_value_time').addEventListener('change', updateOrderNamePreview);
    document.getElementById('separator1').addEventListener('change', updateOrderNamePreview);
    
    document.getElementById('element2_type').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element2_value_text').addEventListener('input', updateOrderNamePreview);
    document.getElementById('element2_value_date').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element2_value_time').addEventListener('change', updateOrderNamePreview);
    document.getElementById('separator2').addEventListener('change', updateOrderNamePreview);
    
    document.getElementById('element3_type').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element3_value_text').addEventListener('input', updateOrderNamePreview);
    document.getElementById('element3_value_date').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element3_value_time').addEventListener('change', updateOrderNamePreview);
    document.getElementById('element3_digits').addEventListener('change', updateOrderNamePreview);
    document.getElementById('start_number').addEventListener('input', updateOrderNamePreview);
    document.getElementById('separator3').addEventListener('change', updateOrderNamePreview);
    
    document.getElementById('element4_type').addEventListener('change', updateOrderNamePreview);
    document.getElementById('separator4').addEventListener('change', updateOrderNamePreview);
    
    // Inicjalizuj podgląd
    updateOrderNamePreview();
    
    // Wczytaj zapisaną konfigurację przy ładowaniu strony
    @if(isset($orderSettings))
    (function() {
        const savedConfigDiv = document.getElementById('saved-config-preview');
        if (savedConfigDiv) {
            const now = new Date();
            const savedParts = [];
            
            // Element 1
            const el1Type = '{{ $orderSettings->element1_type ?? '' }}';
            const el1Value = '{{ $orderSettings->element1_value ?? '' }}';
            if (el1Type === 'empty') {
                // Nie dodawaj
            } else if (el1Type === 'text') {
                savedParts.push(el1Value || 'TEKST');
            } else if (el1Type === 'date') {
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                if (el1Value === 'yyyymmdd') {
                    savedParts.push(`${year}${month}${day}`);
                } else {
                    savedParts.push(`${year}-${month}-${day}`);
                }
            } else if (el1Type === 'time') {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                if (el1Value === 'hh-mm-ss') {
                    savedParts.push(`${hours}-${minutes}-${seconds}`);
                } else if (el1Value === 'hhmmss') {
                    savedParts.push(`${hours}${minutes}${seconds}`);
                } else if (el1Value === 'hh-mm') {
                    savedParts.push(`${hours}-${minutes}`);
                } else if (el1Value === 'hh') {
                    savedParts.push(`${hours}`);
                }
            } else if (el1Type === 'number') {
                savedParts.push(el1Value || '1');
            }
            
            // Element 2
            const el2Type = '{{ $orderSettings->element2_type ?? '' }}';
            const el2Value = '{{ $orderSettings->element2_value ?? '' }}';
            if (el2Type === 'empty') {
                // Nie dodawaj
            } else if (el2Type === 'text') {
                savedParts.push(el2Value || 'TEKST');
            } else if (el2Type === 'date') {
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                if (el2Value === 'yyyymmdd') {
                    savedParts.push(`${year}${month}${day}`);
                } else {
                    savedParts.push(`${year}-${month}-${day}`);
                }
            } else if (el2Type === 'time') {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                if (el2Value === 'hh-mm-ss') {
                    savedParts.push(`${hours}-${minutes}-${seconds}`);
                } else if (el2Value === 'hhmmss') {
                    savedParts.push(`${hours}${minutes}${seconds}`);
                } else if (el2Value === 'hh-mm') {
                    savedParts.push(`${hours}-${minutes}`);
                } else if (el2Value === 'hh') {
                    savedParts.push(`${hours}`);
                }
            } else if (el2Type === 'number') {
                savedParts.push(el2Value || '1');
            }
            
            // Element 3
            const el3Type = '{{ $orderSettings->element3_type ?? '' }}';
            const el3Value = '{{ $orderSettings->element3_value ?? '' }}';
            const el3Digits = {{ $orderSettings->element3_digits ?? 4 }};
            const startNum = {{ $orderSettings->start_number ?? 1 }};
            
            if (el3Type === 'empty') {
                // Nie dodawaj
            } else if (el3Type === 'text') {
                savedParts.push(el3Value || 'TEKST');
            } else if (el3Type === 'date') {
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                if (el3Value === 'yyyymmdd') {
                    savedParts.push(`${year}${month}${day}`);
                } else {
                    savedParts.push(`${year}-${month}-${day}`);
                }
            } else if (el3Type === 'time') {
                const hours = String(now.getHours()).padStart(2, '0');
                const minutes = String(now.getMinutes()).padStart(2, '0');
                const seconds = String(now.getSeconds()).padStart(2, '0');
                if (el3Value === 'hh-mm-ss') {
                    savedParts.push(`${hours}-${minutes}-${seconds}`);
                } else if (el3Value === 'hhmmss') {
                    savedParts.push(`${hours}${minutes}${seconds}`);
                } else if (el3Value === 'hh-mm') {
                    savedParts.push(`${hours}-${minutes}`);
                } else if (el3Value === 'hh') {
                    savedParts.push(`${hours}`);
                }
            } else if (el3Type === 'number') {
                savedParts.push(String(startNum).padStart(el3Digits, '0'));
            }
            
            // Element 4
            const el4Type = '{{ $orderSettings->element4_type ?? 'empty' }}';
            if (el4Type === 'supplier_short_name') {
                savedParts.push('DOSTAWCA');
            }
            
            // Złóż z separatorami
            const sep1 = '{{ $orderSettings->separator1 ?? "_" }}';
            const sep2 = '{{ $orderSettings->separator2 ?? "_" }}';
            const sep3 = '{{ $orderSettings->separator3 ?? "_" }}';
            const sep4 = '{{ $orderSettings->separator4 ?? "_" }}';
            
            let savedPreview = '';
            if (savedParts.length > 0) savedPreview += savedParts[0];
            if (savedParts.length > 1) savedPreview += sep1 + savedParts[1];
            if (savedParts.length > 2) savedPreview += sep2 + savedParts[2];
            if (savedParts.length > 3) savedPreview += sep3 + savedParts[3];
            
            savedConfigDiv.textContent = savedPreview;
        }
    })();
    @endif
</script>

</body>
</html>
