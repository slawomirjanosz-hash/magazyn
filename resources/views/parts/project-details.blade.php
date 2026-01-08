<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Szczegóły projektu - {{ $project->name }}</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

@include('parts.menu')

<div class="max-w-6xl mx-auto bg-white p-6 rounded shadow mt-6">
    
    <div class="flex justify-between items-center mb-6">
        <h2 class="text-xl font-bold">Szczegóły projektu</h2>
        <a href="{{ route('magazyn.projects') }}" class="text-blue-600 hover:underline">← Powrót do listy projektów</a>
    </div>
    
    {{-- INFORMACJE O PROJEKCIE --}}
    <div class="bg-gray-50 border rounded p-4 mb-6">
        <div class="grid grid-cols-2 gap-4">
            <div>
                <span class="text-sm font-semibold text-gray-600">Nr projektu:</span>
                <p class="text-lg">{{ $project->project_number }}</p>
            </div>
            <div>
                <span class="text-sm font-semibold text-gray-600">Nazwa:</span>
                <p class="text-lg">{{ $project->name }}</p>
            </div>
            <div>
                <span class="text-sm font-semibold text-gray-600">Budżet:</span>
                <p class="text-lg">{{ $project->budget ? number_format($project->budget, 2) . ' PLN' : '-' }}</p>
            </div>
            <div>
                <span class="text-sm font-semibold text-gray-600">Osoba odpowiedzialna:</span>
                <p class="text-lg">{{ $project->responsibleUser->name ?? '-' }}</p>
            </div>
            <div>
                <span class="text-sm font-semibold text-gray-600">Status:</span>
                <p class="text-lg">
                    @if($project->status === 'in_progress') W toku
                    @elseif($project->status === 'warranty') Na gwarancji
                    @elseif($project->status === 'archived') Archiwalny
                    @endif
                </p>
            </div>
        </div>
    </div>
    
    {{-- TABELA PRODUKTÓW --}}
    <h3 class="text-lg font-semibold mb-4">Pobrane produkty</h3>
    <table class="w-full border border-collapse text-xs">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Nazwa produktu</th>
                <th class="border p-2">Opis</th>
                <th class="border p-2 text-center">Ilość</th>
                <th class="border p-2 text-center">Pobrał</th>
                <th class="border p-2 text-center">Akcje</th>
            </tr>
        </thead>
        <tbody>
            @forelse($removals as $removal)
                <tr>
                    <td class="border p-2">{{ $removal->part->name }}</td>
                    <td class="border p-2 text-xs text-gray-700">{{ $removal->part->description ?? '-' }}</td>
                    <td class="border p-2 text-center">{{ $removal->total_quantity }}</td>
                    <td class="border p-2 text-center">{{ $removal->user->short_name ?? $removal->user->name }}</td>
                    <td class="border p-2 text-center">
                        <button class="text-blue-600 hover:underline text-xs view-dates-btn" 
                                data-project-id="{{ $project->id }}"
                                data-part-id="{{ $removal->part_id }}"
                                data-user-id="{{ $removal->user_id }}"
                                data-part-name="{{ $removal->part->name }}"
                                data-user-name="{{ $removal->user->short_name ?? $removal->user->name }}">
                            Podgląd
                        </button>
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="5" class="border p-4 text-center text-gray-500">Brak pobranych produktów</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
</div>

{{-- MODAL PODGLĄDU DAT --}}
<div id="dates-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-2xl w-full">
        <h3 class="text-lg font-bold mb-4">Historia pobierań</h3>
        <div id="dates-content">
            <!-- Będzie wypełnione dynamicznie -->
        </div>
        <button id="close-modal-btn" class="mt-4 bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">Zamknij</button>
    </div>
</div>

<script>
    document.querySelectorAll('.view-dates-btn').forEach(btn => {
        btn.addEventListener('click', function() {
            const projectId = this.dataset.projectId;
            const partId = this.dataset.partId;
            const userId = this.dataset.userId;
            const partName = this.dataset.partName;
            const userName = this.dataset.userName;
            
            fetch(`/magazyn/projects/${projectId}/removal-dates?part_id=${partId}&user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    let html = `<p class="mb-3"><strong>Produkt:</strong> ${partName}<br><strong>User:</strong> ${userName}</p>`;
                    html += '<table class="w-full border border-collapse text-xs"><thead class="bg-gray-100"><tr><th class="border p-2">Data</th><th class="border p-2">Ilość</th></tr></thead><tbody>';
                    
                    if (data.removals && data.removals.length > 0) {
                        data.removals.forEach(r => {
                            html += `<tr><td class="border p-2">${r.date}</td><td class="border p-2 text-center">${r.quantity}</td></tr>`;
                        });
                    } else {
                        html += '<tr><td colspan="2" class="border p-4 text-center text-gray-500">Brak danych</td></tr>';
                    }
                    
                    html += '</tbody></table>';
                    document.getElementById('dates-content').innerHTML = html;
                    document.getElementById('dates-modal').classList.remove('hidden');
                });
        });
    });
    
    document.getElementById('close-modal-btn').addEventListener('click', function() {
        document.getElementById('dates-modal').classList.add('hidden');
    });
    
    document.getElementById('dates-modal').addEventListener('click', function(e) {
        if (e.target === this) {
            this.classList.add('hidden');
        }
    });
</script>

</body>
</html>
