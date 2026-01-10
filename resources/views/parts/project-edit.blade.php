<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Edycja projektu - {{ $project->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

@include('parts.menu')

<div class="max-w-4xl mx-auto bg-white p-6 rounded shadow mt-6">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Edycja projektu</h2>
        <a href="{{ route('magazyn.projects.show', $project->id) }}" class="text-blue-600 hover:underline">← Powrót do projektu</a>
    </div>
    
    @if(session('success'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
            {{ session('success') }}
        </div>
    @endif

    @if($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
            <ul>
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('magazyn.updateProject', $project->id) }}" method="POST">
        @csrf
        @method('PUT')
        
        <div class="space-y-4">
            <div>
                <label class="block text-sm font-semibold mb-1">Nr projektu</label>
                <input type="text" name="project_number" value="{{ old('project_number', $project->project_number) }}" 
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Nazwa projektu</label>
                <input type="text" name="name" value="{{ old('name', $project->name) }}" 
                       class="w-full border p-2 rounded" required>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Budżet (PLN)</label>
                <input type="number" name="budget" step="0.01" value="{{ old('budget', $project->budget) }}" 
                       class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Osoba odpowiedzialna</label>
                <select name="responsible_user_id" class="w-full border p-2 rounded">
                    <option value="">-- Nie przypisano --</option>
                    @foreach($users as $user)
                        <option value="{{ $user->id }}" {{ old('responsible_user_id', $project->responsible_user_id) == $user->id ? 'selected' : '' }}>
                            {{ $user->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Okres gwarancji (miesiące)</label>
                <input type="number" name="warranty_period" value="{{ old('warranty_period', $project->warranty_period) }}" 
                       class="w-full border p-2 rounded" min="0">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Data rozpoczęcia</label>
                <input type="date" name="started_at" value="{{ old('started_at', $project->started_at ? $project->started_at->format('Y-m-d') : '') }}" 
                       class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Data zakończenia</label>
                <input type="date" name="finished_at" value="{{ old('finished_at', $project->finished_at ? $project->finished_at->format('Y-m-d') : '') }}" 
                       class="w-full border p-2 rounded">
            </div>

            <div>
                <label class="block text-sm font-semibold mb-1">Status</label>
                <select name="status" class="w-full border p-2 rounded" required>
                    <option value="in_progress" {{ old('status', $project->status) === 'in_progress' ? 'selected' : '' }}>W toku</option>
                    <option value="warranty" {{ old('status', $project->status) === 'warranty' ? 'selected' : '' }}>Na gwarancji</option>
                    <option value="archived" {{ old('status', $project->status) === 'archived' ? 'selected' : '' }}>Archiwalny</option>
                </select>
            </div>
        </div>

        <div class="mt-6 flex gap-2">
            <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                Zapisz zmiany
            </button>
            <a href="{{ route('magazyn.projects.show', $project->id) }}" class="bg-gray-500 text-white px-6 py-2 rounded hover:bg-gray-600">
                Anuluj
            </a>
        </div>
    </form>

</div>

</body>
</html>
