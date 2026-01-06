<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>403 - Brak dostępu</title>
    @vite('resources/css/app.css')
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
</head>
<body class="bg-gray-100">
    <div class="min-h-screen flex items-center justify-center">
        <div class="bg-white p-8 rounded-lg shadow-lg text-center max-w-md">
            <h1 class="text-6xl font-bold text-red-600 mb-4">403</h1>
            <h2 class="text-2xl font-bold text-gray-800 mb-4">Brak dostępu</h2>
            <p class="text-gray-600 mb-6">
                Nie masz uprawnień do dostępu do tej strony. Skontaktuj się z administratorem.
            </p>
            
            <a href="{{ url('/') }}" class="inline-block bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded-lg transition">
                Wróć do magazynu
            </a>
        </div>
    </div>
</body>
</html>
