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
    
    <div class="flex justify-between items-start mb-6">
        <div>
            <h2 class="text-xl font-bold mb-2">Szczegóły projektu</h2>
            <a href="{{ route('magazyn.projects') }}" class="text-blue-600 hover:underline">← Powrót do listy projektów</a>
        </div>
        
        {{-- DATY W PRAWYM GÓRNYM ROGU --}}
        <div class="text-right space-y-1">
            @if($project->started_at)
            <div class="text-sm">
                <span class="font-semibold text-gray-600">Data rozpoczęcia:</span>
                <span class="text-gray-800">{{ $project->started_at->format('d.m.Y') }}</span>
            </div>
            @endif
            @if($project->finished_at)
            <div class="text-sm">
                <span class="font-semibold text-gray-600">Data zakończenia:</span>
                <span class="text-gray-800">{{ $project->finished_at->format('d.m.Y') }}</span>
            </div>
            @endif
            @if($project->warranty_period)
            <div class="text-sm">
                <span class="font-semibold text-gray-600">Okres gwarancji:</span>
                <span class="text-gray-800">{{ $project->warranty_period }} miesięcy</span>
            </div>
            @endif
            @if($project->status === 'warranty' && $project->finished_at && $project->warranty_period)
            <div class="text-sm">
                <span class="font-semibold text-gray-600">Data zakończenia gwarancji:</span>
                <span class="text-gray-800">{{ $project->finished_at->addMonths($project->warranty_period)->format('d.m.Y') }}</span>
            </div>
            @endif
        </div>
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
        
        <div class="mt-4 flex gap-2 justify-end">
            <a href="{{ route('magazyn.editProject', $project->id) }}" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">
                Edytuj projekt
            </a>
            @if($project->status === 'in_progress')
            <button id="finish-project-btn" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                Zakończ projekt
            </button>
            @endif
        </div>
    </div>
    
    {{-- TABELA PRODUKTÓW --}}
    <h3 class="text-lg font-semibold mb-4">Pobrane produkty</h3>
    <table class="w-full border border-collapse text-xs">
        <thead class="bg-gray-100">
            <tr>
                <th class="border p-2">Nazwa produktu</th>
                <th class="border p-2 text-center">Ilość</th>
                <th class="border p-2 text-center">Data/Godzina</th>
                <th class="border p-2 text-center">Pobrał</th>
                <th class="border p-2 text-center">Status</th>
                <th class="border p-2 text-center">Akcje</th>
            </tr>
        </thead>
        <tbody>
            @forelse($removals as $removal)
                <tr class="{{ $removal->status === 'returned' ? 'bg-green-50' : '' }}">
                    <td class="border p-2">{{ $removal->part->name }}</td>
                    <td class="border p-2 text-center">{{ $removal->quantity }}</td>
                    <td class="border p-2 text-center">
                        {{ $removal->created_at->format('d.m.Y H:i') }}
                    </td>
                    <td class="border p-2 text-center">{{ $removal->user->short_name ?? $removal->user->name }}</td>
                    <td class="border p-2 text-center">
                        @if($removal->status === 'added')
                            <span class="text-blue-600 font-semibold">Dodany</span>
                        @else
                            <span class="text-green-600 font-semibold">Zwrócony</span>
                            <br>
                            <span class="text-xs text-gray-500">{{ $removal->returned_at->format('d.m.Y H:i') }}</span>
                            <br>
                            <span class="text-xs text-gray-500">przez {{ $removal->returnedBy->short_name ?? $removal->returnedBy->name }}</span>
                        @endif
                    </td>
                    <td class="border p-2 text-center">
                        @if($removal->status === 'added')
                            <form action="{{ route('magazyn.returnProduct', ['project' => $project->id, 'removal' => $removal->id]) }}" method="POST" class="inline" onsubmit="return confirm('Czy na pewno chcesz zwrócić ten produkt do katalogu?');">
                                @csrf
                                <button type="submit" class="text-green-600 hover:underline text-xs font-semibold">
                                    Zwróć produkt
                                </button>
                            </form>
                        @else
                            <span class="text-gray-400 text-xs">-</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="6" class="border p-4 text-center text-gray-500">Brak pobranych produktów</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
</div>

{{-- MODAL ZAKOŃCZENIA PROJEKTU --}}
<div id="finish-modal" class="fixed inset-0 bg-black bg-opacity-50 hidden flex items-center justify-center z-50">
    <div class="bg-white rounded-lg p-6 max-w-md w-full">
        <h3 class="text-lg font-bold mb-4">Zakończ projekt</h3>
        <p class="mb-4 text-gray-700">Czy na pewno chcesz zakończyć ten projekt? Status projektu zmieni się na "Na gwarancji".</p>
        <form action="{{ route('magazyn.finishProject', $project->id) }}" method="POST">
            @csrf
            <div class="flex gap-2 justify-end">
                <button type="button" id="cancel-finish-btn" class="bg-gray-500 text-white px-4 py-2 rounded hover:bg-gray-600">
                    Anuluj
                </button>
                <button type="submit" class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700">
                    Potwierdź
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    const finishBtn = document.getElementById('finish-project-btn');
    const finishModal = document.getElementById('finish-modal');
    const cancelFinishBtn = document.getElementById('cancel-finish-btn');

    if (finishBtn) {
        finishBtn.addEventListener('click', function() {
            finishModal.classList.remove('hidden');
        });
    }

    if (cancelFinishBtn) {
        cancelFinishBtn.addEventListener('click', function() {
            finishModal.classList.add('hidden');
        });
    }

    if (finishModal) {
        finishModal.addEventListener('click', function(e) {
            if (e.target === this) {
                this.classList.add('hidden');
            }
        });
    }
</script>

