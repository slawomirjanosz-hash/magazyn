<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Edycja użytkownika</title>
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
                    <label class="block text-sm font-medium text-gray-700 mb-1">Nazwa użytkownika</label>
                    <input 
                        type="text" 
                        name="name" 
                        value="{{ $user->name }}"
                        class="w-full px-3 py-2 border border-gray-300 rounded @error('name') border-red-500 @enderror"
                        required
                    >
                    @error('name')
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
            
            <div class="space-y-3">
                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_view_catalog" 
                        class="w-4 h-4"
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
                        class="w-4 h-4"
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
                        class="w-4 h-4"
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
                        class="w-4 h-4"
                        {{ $user->can_orders ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>📦 Zamówienia</strong>
                        <p class="text-gray-600">Możliwość zarządzania zamówieniami</p>
                    </span>
                </label>

                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_settings" 
                        class="w-4 h-4"
                        {{ $user->can_settings ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>⚙️ Ustawienia</strong>
                        <p class="text-gray-600">Możliwość zarządzania kategoriami i użytkownikami</p>
                    </span>
                </label>

                <label class="flex items-center gap-3 p-3 border rounded hover:bg-gray-50 cursor-pointer">
                    <input 
                        type="checkbox" 
                        name="can_delete_orders" 
                        class="w-4 h-4"
                        {{ $user->can_delete_orders ? 'checked' : '' }}
                    >
                    <span class="text-sm">
                        <strong>🗑️ Usuwanie zamówień</strong>
                        <p class="text-gray-600">Możliwość usuwania zamówień</p>
                    </span>
                </label>
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

</body>
</html>
