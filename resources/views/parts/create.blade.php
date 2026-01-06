<h1 style="color:red; font-size:40px;">
    TO JEST INDEX.BLADE.PHP
</h1>

<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Magazyn – Dodaj część</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
</head>
<body class="bg-gray-100">

    <div class="max-w-xl mx-auto mt-10 bg-white p-6 rounded shadow">

        <h1 class="text-xl font-bold mb-4">Dodaj część do magazynu</h1>

        @if(session('success'))
            <div class="bg-green-100 text-green-800 p-2 mb-4">
                {{ session('success') }}
            </div>
        @endif

        <form method="POST" action="{{ route('parts.store') }}">
            @csrf

            <div class="mb-4">
                <label class="block mb-1">Nazwa części</label>
                <input
                    name="name"
                    class="border p-2 w-full"
                    required
                >
            </div>

            <div class="mb-4">
                <label class="block mb-1">Kategoria</label>
                <select name="category_id" class="border p-2 w-full" required>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">
                            {{ $category->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <button class="bg-blue-500 text-white px-4 py-2 rounded">
                Zapisz
            </button>
        </form>

    </div>

</body>
</html>