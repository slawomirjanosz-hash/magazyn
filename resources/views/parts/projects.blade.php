<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Projekty</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

@include('parts.menu')

{{-- KOMUNIKATY --}}
@if(session('success'))
    <div class="max-w-6xl mx-auto mt-4 bg-green-100 text-green-800 p-2 rounded">
        {{ session('success') }}
    </div>
@endif

@if(session('error'))
    <div class="max-w-6xl mx-auto mt-4 bg-red-100 text-red-800 p-2 rounded">
        {{ session('error') }}
    </div>
@endif

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-6">
    
    <h2 class="text-xl font-bold mb-4">Projekty</h2>
    
    {{-- PRZYCISKI WIDOKÓW --}}
    <div class="flex gap-2 mb-6">
        <button type="button" id="btn-in-progress" class="px-4 py-2 bg-blue-500 text-white rounded active-tab">
            Projekty w toku
        </button>
        <button type="button" id="btn-warranty" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">
            Projekty na gwarancji
        </button>
        <button type="button" id="btn-archived" class="px-4 py-2 bg-gray-300 text-gray-800 rounded">
            Projekty Archiwalne
        </button>
    </div>
    
    {{-- SEKCJA: DODAJ PROJEKT --}}
    <div class="bg-white rounded shadow mb-6 border">
        <button type="button" class="collapsible-btn w-full flex items-center gap-2 p-6 cursor-pointer hover:bg-gray-50" data-target="add-project-content">
            <span class="toggle-arrow text-lg">▶</span>
            <h3 class="text-lg font-semibold">Dodaj Projekt</h3>
        </button>
        <div id="add-project-content" class="collapsible-content hidden p-6 border-t">
            <form method="POST" action="{{ route('magazyn.projects.store') }}" class="space-y-4">
                @csrf
                
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium mb-2">Nr projektu *</label>
                        <input type="text" name="project_number" required class="w-full px-3 py-2 border rounded" placeholder="PR-2024-001">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Nazwa projektu *</label>
                        <input type="text" name="name" required class="w-full px-3 py-2 border rounded" placeholder="Projekt X">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Budżet projektu (PLN)</label>
                        <input type="number" name="budget" step="0.01" min="0" class="w-full px-3 py-2 border rounded" placeholder="10000.00">
                    </div>
                    
                    <div>
                        <label class="block text-sm font-medium mb-2">Osoba odpowiedzialna</label>
                        <select name="responsible_user_id" class="w-full px-3 py-2 border rounded">
                            <option value="">- Wybierz -</option>
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                
                <button type="submit" class="bg-blue-600 text-white px-6 py-2 rounded hover:bg-blue-700">
                    Utwórz Projekt
                </button>
            </form>
        </div>
    </div>
    
    {{-- SEKCJA: PROJEKTY W TOKU --}}
    <div id="section-in-progress" class="project-section">
        <div class="bg-white rounded shadow border">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Projekty w toku</h3>
                <table class="w-full border border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Nr projektu</th>
                            <th class="border p-2">Nazwa</th>
                            <th class="border p-2">Budżet</th>
                            <th class="border p-2">Osoba odpowiedzialna</th>
                            <th class="border p-2">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($inProgressProjects as $project)
                            <tr>
                                <td class="border p-2">{{ $project->project_number }}</td>
                                <td class="border p-2">{{ $project->name }}</td>
                                <td class="border p-2 text-right">{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</td>
                                <td class="border p-2">{{ $project->responsibleUser->name ?? '-' }}</td>
                                <td class="border p-2 text-center">
                                    <a href="{{ route('magazyn.projects.show', $project->id) }}" class="text-blue-600 hover:underline text-sm">Szczegóły</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border p-4 text-center text-gray-500">Brak projektów w toku</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- SEKCJA: PROJEKTY NA GWARANCJI --}}
    <div id="section-warranty" class="project-section hidden">
        <div class="bg-white rounded shadow border">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Projekty na gwarancji</h3>
                <table class="w-full border border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Nr projektu</th>
                            <th class="border p-2">Nazwa</th>
                            <th class="border p-2">Budżet</th>
                            <th class="border p-2">Osoba odpowiedzialna</th>
                            <th class="border p-2">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($warrantyProjects as $project)
                            <tr>
                                <td class="border p-2">{{ $project->project_number }}</td>
                                <td class="border p-2">{{ $project->name }}</td>
                                <td class="border p-2 text-right">{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</td>
                                <td class="border p-2">{{ $project->responsibleUser->name ?? '-' }}</td>
                                <td class="border p-2 text-center">
                                    <a href="{{ route('magazyn.projects.show', $project->id) }}" class="text-blue-600 hover:underline text-sm">Szczegóły</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border p-4 text-center text-gray-500">Brak projektów na gwarancji</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
    {{-- SEKCJA: PROJEKTY ARCHIWALNE --}}
    <div id="section-archived" class="project-section hidden">
        <div class="bg-white rounded shadow border">
            <div class="p-6">
                <h3 class="text-lg font-semibold mb-4">Projekty Archiwalne</h3>
                <table class="w-full border border-collapse text-xs">
                    <thead class="bg-gray-100">
                        <tr>
                            <th class="border p-2">Nr projektu</th>
                            <th class="border p-2">Nazwa</th>
                            <th class="border p-2">Budżet</th>
                            <th class="border p-2">Osoba odpowiedzialna</th>
                            <th class="border p-2">Akcje</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($archivedProjects as $project)
                            <tr>
                                <td class="border p-2">{{ $project->project_number }}</td>
                                <td class="border p-2">{{ $project->name }}</td>
                                <td class="border p-2 text-right">{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</td>
                                <td class="border p-2">{{ $project->responsibleUser->name ?? '-' }}</td>
                                <td class="border p-2 text-center">
                                    <a href="{{ route('magazyn.projects.show', $project->id) }}" class="text-blue-600 hover:underline text-sm">Szczegóły</a>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="border p-4 text-center text-gray-500">Brak projektów archiwalnych</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
    
</div>

<script>
    // Collapsible sections
    document.querySelectorAll('.collapsible-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const targetId = this.dataset.target;
            const content = document.getElementById(targetId);
            const arrow = this.querySelector('.toggle-arrow');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                arrow.textContent = '▼';
            } else {
                content.classList.add('hidden');
                arrow.textContent = '▶';
            }
        });
    });
    
    // Tab switching
    const tabs = {
        'btn-in-progress': 'section-in-progress',
        'btn-warranty': 'section-warranty',
        'btn-archived': 'section-archived'
    };
    
    Object.keys(tabs).forEach(btnId => {
        document.getElementById(btnId).addEventListener('click', function() {
            // Ukryj wszystkie sekcje
            document.querySelectorAll('.project-section').forEach(s => s.classList.add('hidden'));
            
            // Usuń active z wszystkich przycisków
            Object.keys(tabs).forEach(id => {
                const btn = document.getElementById(id);
                btn.classList.remove('bg-blue-500', 'text-white', 'active-tab');
                btn.classList.add('bg-gray-300', 'text-gray-800');
            });
            
            // Pokaż wybraną sekcję
            document.getElementById(tabs[btnId]).classList.remove('hidden');
            
            // Oznacz aktywny przycisk
            this.classList.remove('bg-gray-300', 'text-gray-800');
            this.classList.add('bg-blue-500', 'text-white', 'active-tab');
        });
    });
</script>

</body>
</html>
