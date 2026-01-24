<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Edycja użytkownika</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

@include('parts.menu')

<div class="max-w-2xl mx-auto bg-white p-6 rounded shadow mt-6">
    <a href="{{ route('magazyn.settings') }}" class="text-blue-600 hover:underline mb-4 inline-block">← Wróć do ustawień</a>

    <h2 class="text-2xl font-bold mb-6">Edycja użytkownika: {{ $user->name }}</h2>

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul class="list-disc list-inside">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('magazyn.user.update', $user->id) }}" method="POST" class="flex flex-col gap-4">
        @csrf
        @method('PUT')

        <!-- Informacje o użytkowniku -->
        <div class="border-b pb-4">
            <h3 class="font-semibold mb-3">Dane użytkownika</h3>
            <div class="space-y-3">
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Imię</label>
                    <input 
                        type="text" 
                        name="first_name" 
                        id="edit_first_name"
                        value="{{ $user->first_name ?? explode(' ', $user->name)[0] }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('first_name') border-red-500 @enderror"
                        required
                    >
                    @error('first_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nazwisko</label>
                    <input 
                        type="text" 
                        name="last_name" 
                        id="edit_last_name"
                        value="{{ $user->last_name ?? (count(explode(' ', $user->name)) > 1 ? explode(' ', $user->name)[1] : '') }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('last_name') border-red-500 @enderror"
                        required
                    >
                    @error('last_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                    <input 
                        type="email" 
                        name="email" 
                        value="{{ $user->email }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('email') border-red-500 @enderror"
                        required
                    >
                    @error('email')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Numer telefonu</label>
                    <input 
                        type="text" 
                        name="phone" 
                        value="{{ $user->phone }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('phone') border-red-500 @enderror"
                    >
                    @error('phone')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-1">Skrócona nazwa użytkownika</label>
                    <input 
                        type="text" 
                        name="short_name" 
                        id="edit_short_name"
                        value="{{ $user->short_name ?? '' }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('short_name') border-red-500 @enderror"
                        placeholder="np. MicKow"
                    >
                    @error('short_name')
                        <p class="text-red-600 text-sm mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Zmiana hasła -->
        <div class="border-b pb-4">
            <h3 class="font-semibold mb-3">Zmiana hasła (opcjonalne)</h3>
            <input 
                type="password" 
                name="password" 
                placeholder="Nowe hasło" 
                class="w-full px-3 py-2 border border-gray-300 rounded @error('password') border-red-500 @enderror"
                autocomplete="new-password"
            >
            <p class="text-xs text-gray-500 mt-1">Pozostaw puste, jeśli nie chcesz zmieniać hasła</p>
        </div>

        <!-- Uprawnienia -->
        <div class="border-b pb-4">
            <h3 class="font-semibold mb-4">Dostęp do zakładek</h3>
            @php
                $isSuperAdmin = auth()->user()->email === 'proximalumine@gmail.com';
                $isAdmin = auth()->user()->is_admin;
                $canEditMagazyn = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_magazyn);
                $canEditOffers = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_offers);
                $canEditRecipes = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_recipes);
                $canEditCrm = $isSuperAdmin || ($isAdmin && auth()->user()->can_crm);
            @endphp
            <div class="space-y-3">
                @if($canEditMagazyn)
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_view_magazyn" 
                        id="can_view_magazyn_checkbox"
                        class="w-4 h-4"
                        {{ $user->can_view_magazyn ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>📦 Magazyn</strong>
                        <p class="text-gray-600">Możliwość wejścia do magazynu</p>
                    </span>
                </label>
                @else
                    @if($user->can_view_magazyn)
                    <div class="flex items-center gap-3 p-3 border rounded bg-gray-50">
                        <input type="checkbox" class="w-4 h-4" checked disabled>
                        <span class="text-sm text-gray-500">
                            <strong>📦 Magazyn</strong>
                            <p class="text-gray-600">Możliwość wejścia do magazynu (tylko do odczytu)</p>
                        </span>
                    </div>
                    <input type="hidden" name="can_view_magazyn" value="1">
                    @endif
                @endif
                
                <!-- Podrzędne uprawnienia Magazynu (wcięte) -->
                <div class="ml-8 space-y-2" id="magazyn_sub_permissions">
                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_view_catalog" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->can_view_catalog ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>🔍 Katalog</strong>
                            <p class="text-gray-600">Możliwość przeglądania katalogu produktów</p>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_add" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->can_add ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>➕ Dodaj</strong>
                            <p class="text-gray-600">Możliwość dodawania produktów do magazynu</p>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_remove" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->can_remove ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>➖ Pobierz</strong>
                            <p class="text-gray-600">Możliwość pobierania produktów z magazynu</p>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_orders" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->can_orders ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>📦 Pobierz zamówienie</strong>
                            <p class="text-gray-600">Możliwość zarządzania zamówieniami</p>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_delete_orders" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->can_delete_orders ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>🗑️ Usuwanie zamówień</strong>
                            <p class="text-gray-600">Możliwość usuwania zamówień</p>
                        </span>
                    </label>

                    <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="show_action_column" 
                            class="w-4 h-4 magazyn-sub-checkbox"
                            {{ $user->show_action_column ? 'checked' : '' }}
                        >
                        <span class="text-sm">
                            <strong>👁️ Pokaż kolumnę akcja w Magazyn/Sprawdź</strong>
                            <p class="text-gray-600">Wyświetlaj kolumnę "Akcja" w tabeli Magazyn/Sprawdź</p>
                        </span>
                    </label>
                </div>
                
                @if($canEditOffers)
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_view_offers" 
                        class="w-4 h-4"
                        {{ $user->can_view_offers ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>💼 Wyceny i Oferty</strong>
                        <p class="text-gray-600">Możliwość wejścia do sekcji Wyceny i Oferty</p>
                    </span>
                </label>
                @else
                    @if($user->can_view_offers)
                    <div class="flex items-center gap-3 p-3 border rounded bg-gray-50">
                        <input type="checkbox" class="w-4 h-4" checked disabled>
                        <span class="text-sm text-gray-500">
                            <strong>💼 Wyceny i Oferty</strong>
                            <p class="text-gray-600">Możliwość wejścia do sekcji Wyceny i Oferty (tylko do odczytu)</p>
                        </span>
                    </div>
                    <input type="hidden" name="can_view_offers" value="1">
                    @endif
                @endif
                
                @if($canEditRecipes)
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_view_recipes" 
                        class="w-4 h-4"
                        {{ $user->can_view_recipes ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>🧪 Receptury</strong>
                        <p class="text-gray-600">Możliwość wejścia do sekcji Receptury {{ !$isSuperAdmin ? '(admin z tym uprawnieniem może nadawać)' : '' }}</p>
                    </span>
                </label>
                @else
                    @if($user->can_view_recipes)
                    <div class="flex items-center gap-3 p-3 border rounded bg-gray-50">
                        <input type="checkbox" class="w-4 h-4" checked disabled>
                        <span class="text-sm text-gray-500">
                            <strong>🧪 Receptury</strong>
                            <p class="text-gray-600">Możliwość wejścia do sekcji Receptury (tylko do odczytu)</p>
                        </span>
                    </div>
                    <input type="hidden" name="can_view_recipes" value="1">
                    @endif
                @endif

                @if($canEditCrm)
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_crm" 
                        class="w-4 h-4"
                        {{ $user->can_crm ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>👥 CRM</strong>
                        <p class="text-gray-600">Dostęp do systemu zarządzania relacjami z klientami</p>
                    </span>
                </label>
                @else
                    @if($user->can_crm)
                    <div class="flex items-center gap-3 p-3 border rounded bg-gray-50">
                        <input type="checkbox" class="w-4 h-4" checked disabled>
                        <span class="text-sm text-gray-500">
                            <strong>👥 CRM</strong>
                            <p class="text-gray-600">Dostęp do systemu zarządzania relacjami z klientami (tylko do odczytu)</p>
                        </span>
                    </div>
                    <input type="hidden" name="can_crm" value="1">
                    @endif
                @endif

                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_settings" 
                        id="can_settings_checkbox"
                        class="w-4 h-4"
                        {{ $user->can_settings ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>⚙️ Ustawienia</strong>
                        <p class="text-gray-600">Możliwość zarządzania kategoriami i użytkownikami</p>
                    </span>
                </label>

                <!-- Granularne uprawnienia do ustawień (widoczne tylko gdy can_settings jest zaznaczone) -->
                <div id="settings_sub_permissions" class="ml-8 space-y-2 {{ $user->can_settings ? '' : 'hidden' }}">
                    <p class="text-sm text-gray-500 mb-2">Dostęp do poszczególnych sekcji ustawień:</p>
                    
                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_categories" 
                            class="w-4 h-4"
                            {{ $user->can_settings_categories ? 'checked' : '' }}
                        >
                        <span class="text-sm">📁 Kategorie</span>
                    </label>

                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_suppliers" 
                            class="w-4 h-4"
                            {{ $user->can_settings_suppliers ? 'checked' : '' }}
                        >
                        <span class="text-sm">🏢 Dostawcy</span>
                    </label>

                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_company" 
                            class="w-4 h-4"
                            {{ $user->can_settings_company ? 'checked' : '' }}
                        >
                        <span class="text-sm">🏭 Dane mojej firmy</span>
                    </label>

                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_users" 
                            class="w-4 h-4"
                            {{ $user->can_settings_users ? 'checked' : '' }}
                        >
                        <span class="text-sm">👥 Użytkownicy</span>
                    </label>

                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_export" 
                            class="w-4 h-4"
                            {{ $user->can_settings_export ? 'checked' : '' }}
                        >
                        <span class="text-sm">📤 Ustawienia eksportu</span>
                    </label>

                    <label class="flex items-center gap-2 p-2 border rounded hover:bg-gray-50 cursor-pointer">
                        <input 
                            type="checkbox" 
                            name="can_settings_other" 
                            class="w-4 h-4"
                            {{ $user->can_settings_other ? 'checked' : '' }}
                        >
                        <span class="text-sm">⚡ Inne ustawienia</span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Przyciski -->
        <div class="flex gap-2 pt-4 border-t">
            <button 
                type="submit" 
                class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700"
            >
                Zapisz zmiany
            </button>
            <a 
                href="{{ route('magazyn.settings') }}" 
                class="px-4 py-2 bg-gray-400 text-white rounded hover:bg-gray-500"
            >
                Anuluj
            </a>
        </div>
    </form>
</div>

<script>
    // Auto-generowanie skróconej nazwy użytkownika w formularzu edycji
    var editFirstNameInput = document.getElementById('edit_first_name');
    var editLastNameInput = document.getElementById('edit_last_name');
    var editShortNameInput = document.getElementById('edit_short_name');
    
    if (editFirstNameInput && editLastNameInput && editShortNameInput) {
        function generateEditShortName() {
            var firstName = editFirstNameInput.value.trim();
            var lastName = editLastNameInput.value.trim();
            
            if (firstName.length >= 3 && lastName.length >= 3) {
                var firstPart = firstName.charAt(0).toUpperCase() + firstName.substring(1, 3).toLowerCase();
                var lastPart = lastName.charAt(0).toUpperCase() + lastName.substring(1, 3).toLowerCase();
                editShortNameInput.value = firstPart + lastPart;
            }
        }
        
        editFirstNameInput.addEventListener('input', generateEditShortName);
        editLastNameInput.addEventListener('input', generateEditShortName);
    }

    // Przełączanie widoczności granularnych uprawnień ustawień
    var canSettingsCheckbox = document.getElementById('can_settings_checkbox');
    var settingsSubPermissions = document.getElementById('settings_sub_permissions');
    
    if (canSettingsCheckbox && settingsSubPermissions) {
        canSettingsCheckbox.addEventListener('change', function() {
            if (this.checked) {
                settingsSubPermissions.classList.remove('hidden');
            } else {
                settingsSubPermissions.classList.add('hidden');
            }
        });
    }

    // Automatyczne odznaczanie podrzędnych uprawnień Magazynu
    var canViewMagazynCheckbox = document.getElementById('can_view_magazyn_checkbox');
    var magazynSubCheckboxes = document.querySelectorAll('.magazyn-sub-checkbox');
    
    if (canViewMagazynCheckbox && magazynSubCheckboxes.length > 0) {
        canViewMagazynCheckbox.addEventListener('change', function() {
            if (!this.checked) {
                // Jeśli Magazyn został odznaczony, odznacz wszystkie podrzędne
                magazynSubCheckboxes.forEach(function(checkbox) {
                    checkbox.checked = false;
                });
            }
        });
    }
</script>

</body>
</html>
