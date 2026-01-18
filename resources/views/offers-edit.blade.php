<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Edytuj Ofertę</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                @php
                    try {
                        $companySettings = \App\Models\CompanySetting::first();
                        if ($companySettings && $companySettings->logo) {
                            if (str_starts_with($companySettings->logo, 'data:image')) {
                                $logoPath = $companySettings->logo;
                            } else {
                                $logoPath = asset('storage/' . $companySettings->logo);
                            }
                        } else {
                            $logoPath = '/logo.png';
                        }
                        $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
                    } catch (\Exception $e) {
                        $logoPath = '/logo.png';
                        $companyName = 'Moja Firma';
                    }
                @endphp
                <img src="{{ $logoPath }}" alt="{{ $companyName }}" class="h-10">
                <span class="text-xl font-bold">{{ $companyName }}</span>
                            <span id="datetime" class="ml-4 px-3 py-2 text-sm bg-white-200 text-gray-400 rounded whitespace-nowrap"></span>
            </div>
        </div>
    </header>
    <main class="flex-1 p-6">
        <div class="max-w-5xl mx-auto bg-white rounded shadow p-6 relative">
            <a href="javascript:history.back()" class="absolute top-4 left-4 flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 shadow rounded-full text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition z-10">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
                Powrót
            </a>
            
            <h1 class="text-3xl font-bold mb-6 text-center mt-12">Edycja oferty</h1>
            
            <form action="{{ route('offers.update', $offer) }}" method="POST" class="space-y-6" onkeydown="return event.key != 'Enter';">
                @csrf
                @method('PUT')
                
                <!-- Podstawowe informacje -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nr oferty</label>
                        <input type="text" name="offer_number" value="{{ $offer->offer_number }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tytuł oferty</label>
                        <input type="text" name="offer_title" value="{{ $offer->offer_title }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                        <input type="date" name="offer_date" value="{{ $offer->offer_date->format('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>

                <!-- Sekcja Usługi -->
                <div class="border border-gray-300 rounded">
                    <div class="flex items-center justify-between p-4 bg-gray-50">
                        <button type="button" class="flex-1 flex items-center justify-between hover:bg-gray-100 transition" onclick="toggleSection('services')">
                            <span class="font-semibold text-lg section-name" id="services-name-label">Usługi</span>
                            <svg id="services-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <button type="button" onclick="editSectionName('services')" class="ml-2 px-2 py-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded" title="Edytuj nazwę">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 00-4-4l-8 8v3z" /></svg>
                        </button>
                        <button type="button" onclick="removeMainSection('services')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <div id="services-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left w-20">Ilość</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 text-left w-32">Wartość (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="services-table">
                                @forelse($offer->services ?? [] as $index => $service)
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="{{ $index + 1 }}" readonly></td>
                                    <td class="p-2"><input type="text" name="services[{{ $index }}][name]" value="{{ $service['name'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="{{ $service['quantity'] ?? 1 }}" name="services[{{ $index }}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="services" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="services[{{ $index }}][supplier]" value="{{ $service['supplier'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="services[{{ $index }}][price]" value="{{ $service['price'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="services" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="services[{{ $index }}][value]" value="{{ ($service['quantity'] ?? 1) * ($service['price'] ?? 0) }}" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="services" readonly></td>
                                    <td class="p-2">@if($index > 0)<button type="button" onclick="removeRow(this, 'services')" class="text-red-600 hover:text-red-800">✕</button>@endif</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2"><input type="text" name="services[0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="1" name="services[0][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="services" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="services[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="services[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="services" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="services[0][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="services" readonly></td>
                                    <td class="p-2"></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <button type="button" onclick="addRow('services')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                        <div class="mt-4 text-right">
                            <span class="font-semibold">Suma: </span>
                            <span id="services-total" class="font-bold text-lg">0.00 zł</span>
                        </div>
                    </div>
                </div>

                <!-- Sekcja Prace własne -->
                <div class="border border-gray-300 rounded">
                    <div class="flex items-center justify-between p-4 bg-gray-50">
                        <button type="button" class="flex-1 flex items-center justify-between hover:bg-gray-100 transition" onclick="toggleSection('works')">
                            <span class="font-semibold text-lg section-name" id="works-name-label">Prace własne</span>
                            <svg id="works-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <button type="button" onclick="editSectionName('works')" class="ml-2 px-2 py-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded" title="Edytuj nazwę">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 00-4-4l-8 8v3z" /></svg>
                        </button>
                        <button type="button" onclick="removeMainSection('works')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <div id="works-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left w-20">Ilość</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 text-left w-32">Wartość (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="works-table">
                                @forelse($offer->works ?? [] as $index => $work)
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="{{ $index + 1 }}" readonly></td>
                                    <td class="p-2"><input type="text" name="works[{{ $index }}][name]" value="{{ $work['name'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="{{ $work['quantity'] ?? 1 }}" name="works[{{ $index }}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="works" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="works[{{ $index }}][supplier]" value="{{ $work['supplier'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="works[{{ $index }}][price]" value="{{ $work['price'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="works" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="works[{{ $index }}][value]" value="{{ ($work['quantity'] ?? 1) * ($work['price'] ?? 0) }}" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="works" readonly></td>
                                    <td class="p-2">@if($index > 0)<button type="button" onclick="removeRow(this, 'works')" class="text-red-600 hover:text-red-800">✕</button>@endif</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2"><input type="text" name="works[0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="1" name="works[0][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="works" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="works[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="works[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="works" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="works[0][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="works" readonly></td>
                                    <td class="p-2"></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <button type="button" onclick="addRow('works')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                        <div class="mt-4 text-right">
                            <span class="font-semibold">Suma: </span>
                            <span id="works-total" class="font-bold text-lg">0.00 zł</span>
                        </div>
                    </div>
                </div>

                <!-- Sekcja Materiały -->
                <div class="border border-gray-300 rounded">
                    <div class="flex items-center justify-between p-4 bg-gray-50">
                        <button type="button" class="flex-1 flex items-center justify-between hover:bg-gray-100 transition" onclick="toggleSection('materials')">
                            <span class="font-semibold text-lg section-name" id="materials-name-label">Materiały</span>
                            <svg id="materials-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                        </button>
                        <button type="button" onclick="editSectionName('materials')" class="ml-2 px-2 py-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded" title="Edytuj nazwę">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 00-4-4l-8 8v3z" /></svg>
                        </button>
                        <button type="button" onclick="removeMainSection('materials')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                        </button>
                    </div>
                    <div id="materials-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left w-20">Ilość</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 text-left w-32">Wartość (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="materials-table">
                                @forelse($offer->materials ?? [] as $index => $material)
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="{{ $index + 1 }}" readonly></td>
                                    <td class="p-2"><input type="text" name="materials[{{ $index }}][name]" value="{{ $material['name'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="{{ $material['quantity'] ?? 1 }}" name="materials[{{ $index }}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="materials" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="materials[{{ $index }}][supplier]" value="{{ $material['supplier'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="materials[{{ $index }}][price]" value="{{ $material['price'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="materials" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="materials[{{ $index }}][value]" value="{{ ($material['quantity'] ?? 1) * ($material['price'] ?? 0) }}" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="materials" readonly></td>
                                    <td class="p-2">@if($index > 0)<button type="button" onclick="removeRow(this, 'materials')" class="text-red-600 hover:text-red-800">✕</button>@endif</td>
                                </tr>
                                @empty
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2"><input type="text" name="materials[0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" min="1" value="1" name="materials[0][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="materials" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="text" name="materials[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="materials[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="materials" onchange="calculateRowValue(this)"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="materials[0][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="materials" readonly></td>
                                    <td class="p-2"></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                        <button type="button" onclick="addRow('materials')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                        <div class="mt-4 text-right">
                            <span class="font-semibold">Suma: </span>
                            <span id="materials-total" class="font-bold text-lg">0.00 zł</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamiczne sekcje niestandardowe -->
                <div id="custom-sections-container">
                    @if(isset($offer->custom_sections) && is_array($offer->custom_sections))
                        @foreach($offer->custom_sections as $sectionIndex => $customSection)
                            <div class="border border-gray-300 rounded" id="section-custom{{ $sectionIndex + 1 }}">
                                <div class="flex items-center justify-between p-4 bg-gray-50">
                                    <button type="button" class="flex-1 flex items-center justify-between hover:bg-gray-100 transition" onclick="toggleSection('custom{{ $sectionIndex + 1 }}')">
                                        <span class="font-semibold text-lg">{{ $customSection['name'] ?? 'Sekcja ' . ($sectionIndex + 1) }}</span>
                                        <svg id="custom{{ $sectionIndex + 1 }}-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                                    </button>
                                    <button type="button" onclick="removeCustomSection('custom{{ $sectionIndex + 1 }}')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                                    </button>
                                </div>
                                <div id="custom{{ $sectionIndex + 1 }}-content" class="p-4 hidden">
                                    <input type="hidden" name="custom_sections[{{ $sectionIndex + 1 }}][name]" value="{{ $customSection['name'] ?? '' }}">
                                    <table class="w-full mb-4">
                                        <thead>
                                            <tr class="bg-gray-100">
                                                <th class="p-2 text-left w-16">Nr</th>
                                                <th class="p-2 text-left">Nazwa</th>
                                                <th class="p-2 text-left w-20">Ilość</th>
                                                <th class="p-2 text-left">Dostawca</th>
                                                <th class="p-2 text-left w-32">Cena (zł)</th>
                                                <th class="p-2 text-left w-32">Wartość (zł)</th>
                                                <th class="p-2 w-16"></th>
                                            </tr>
                                        </thead>
                                        <tbody id="custom{{ $sectionIndex + 1 }}-table">
                                            @forelse($customSection['items'] ?? [] as $itemIndex => $item)
                                                <tr>
                                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="{{ $itemIndex + 1 }}" readonly></td>
                                                    <td class="p-2"><input type="text" name="custom_sections[{{ $sectionIndex + 1 }}][items][{{ $itemIndex }}][name]" value="{{ $item['name'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                                    <td class="p-2"><input type="number" min="1" value="{{ $item['quantity'] ?? 1 }}" name="custom_sections[{{ $sectionIndex + 1 }}][items][{{ $itemIndex }}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="custom{{ $sectionIndex + 1 }}" onchange="calculateRowValue(this)"></td>
                                                    <td class="p-2"><input type="text" name="custom_sections[{{ $sectionIndex + 1 }}][items][{{ $itemIndex }}][supplier]" value="{{ $item['supplier'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm"></td>
                                                    <td class="p-2"><input type="number" step="0.01" name="custom_sections[{{ $sectionIndex + 1 }}][items][{{ $itemIndex }}][price]" value="{{ $item['price'] ?? '' }}" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="custom{{ $sectionIndex + 1 }}" onchange="calculateRowValue(this)"></td>
                                                    <td class="p-2"><input type="number" step="0.01" name="custom_sections[{{ $sectionIndex + 1 }}][items][{{ $itemIndex }}][value]" value="{{ ($item['quantity'] ?? 1) * ($item['price'] ?? 0) }}" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="custom{{ $sectionIndex + 1 }}" readonly></td>
                                                    <td class="p-2">@if($itemIndex > 0)<button type="button" onclick="removeRow(this, 'custom{{ $sectionIndex + 1 }}')" class="text-red-600 hover:text-red-800">✕</button>@endif</td>
                                                </tr>
                                            @empty
                                                <tr>
                                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                                    <td class="p-2"><input type="text" name="custom_sections[{{ $sectionIndex + 1 }}][items][0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                                    <td class="p-2"><input type="number" min="1" value="1" name="custom_sections[{{ $sectionIndex + 1 }}][items][0][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="custom{{ $sectionIndex + 1 }}" onchange="calculateRowValue(this)"></td>
                                                    <td class="p-2"><input type="text" name="custom_sections[{{ $sectionIndex + 1 }}][items][0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                                    <td class="p-2"><input type="number" step="0.01" name="custom_sections[{{ $sectionIndex + 1 }}][items][0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="custom{{ $sectionIndex + 1 }}" onchange="calculateRowValue(this)"></td>
                                                    <td class="p-2"><input type="number" step="0.01" name="custom_sections[{{ $sectionIndex + 1 }}][items][0][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="custom{{ $sectionIndex + 1 }}" readonly></td>
                                                    <td class="p-2"></td>
                                                </tr>
                                            @endforelse
                                        </tbody>
                                    </table>
                                    <button type="button" onclick="addCustomRow('custom{{ $sectionIndex + 1 }}', {{ $sectionIndex + 1 }})" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                                    <div class="mt-4 text-right">
                                        <span class="font-semibold">Suma: </span>
                                        <span id="custom{{ $sectionIndex + 1 }}-total" class="font-bold text-lg">0.00 zł</span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                <!-- Przycisk dodawania nowej sekcji -->
                <div class="text-center">
                    <button type="button" onclick="addCustomSection()" class="px-6 py-3 bg-purple-600 text-white rounded-lg hover:bg-purple-700 transition flex items-center gap-2 mx-auto">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4" /></svg>
                        Dodaj nową sekcję
                    </button>
                </div>

                <!-- Suma końcowa -->
                <div class="bg-gray-50 p-4 rounded border border-gray-300">
                    <div class="text-right">
                        <span class="text-xl font-semibold">Suma końcowa: </span>
                        <span id="grand-total" class="text-2xl font-bold text-blue-600">0.00 zł</span>
                    </div>
                </div>

                <!-- Opis oferty -->
                <div class="mt-8">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Opis oferty</label>
                    <div id="offer_description_editor" style="min-height: 150px; background: white; border: 1px solid #d1d5db; border-radius: 0.375rem;"></div>
                    <textarea id="offer_description" name="offer_description" style="display: none;">{{ $offer->offer_description ?? '' }}</textarea>
                </div>

                <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
                <script src="https://cdn.quilljs.com/1.3.6/quill.js"></script>
                <script>
                document.addEventListener('DOMContentLoaded', function() {
                    const quill = new Quill('#offer_description_editor', {
                        theme: 'snow',
                        placeholder: 'Dodaj opis oferty...',
                        modules: {
                            toolbar: [
                                [{ 'header': [1, 2, 3, false] }],
                                ['bold', 'italic', 'underline', 'strike'],
                                [{ 'color': [] }, { 'background': [] }],
                                [{ 'font': [] }],
                                [{ 'align': [] }],
                                [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                                ['link'],
                                ['clean']
                            ]
                        }
                    });
                    
                    // Załaduj istniejący opis
                    const existingDescription = document.getElementById('offer_description').value;
                    if (existingDescription) {
                        quill.root.innerHTML = existingDescription;
                    }
                    
                    // Synchronizuj z hidden textarea
                    quill.on('text-change', function() {
                        document.getElementById('offer_description').value = quill.root.innerHTML;
                    });
                });
                </script>

                <!-- Miejsce docelowe oferty -->
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gdzie ma wylądować oferta?</label>
                    <select name="destination" class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="portfolio" {{ $offer->status === 'portfolio' ? 'selected' : '' }}>Portfolio</option>
                        <option value="inprogress" {{ $offer->status === 'inprogress' ? 'selected' : '' }}>Oferty w toku</option>
                    </select>
                </div>

                <!-- Przycisk Zapisz -->
                <div class="text-center">
                    <button type="submit" class="px-8 py-3 bg-green-600 text-white rounded-lg text-lg font-semibold hover:bg-green-700 transition">
                        Zapisz zmiany
                    </button>
                </div>
            </form>
        </div>
    </main>
    <footer class="bg-white text-center py-4 mt-8 border-t text-gray-400 text-sm">
        Powered by ProximaLumine
    </footer>

    <script>
        let rowCounters = {
            services: {{ count($offer->services ?? []) }},
            works: {{ count($offer->works ?? []) }},
            materials: {{ count($offer->materials ?? []) }}
        };
        
        let customSectionCounter = {{ count($offer->custom_sections ?? []) }};
        let customSections = [];
        
        // Inicjalizuj istniejące sekcje niestandardowe
        @if(isset($offer->custom_sections) && is_array($offer->custom_sections))
            @foreach($offer->custom_sections as $sectionIndex => $customSection)
                customSections.push({{ $sectionIndex + 1 }});
                rowCounters['custom{{ $sectionIndex + 1 }}'] = {{ count($customSection['items'] ?? []) }};
            @endforeach
        @endif

        // Oblicz sumy przy ładowaniu
        document.addEventListener('DOMContentLoaded', function() {
            calculateTotal('services');
            calculateTotal('works');
            calculateTotal('materials');
            
            // Oblicz sumy dla niestandardowych sekcji
            customSections.forEach(sectionNum => {
                calculateTotal(`custom${sectionNum}`);
            });
        });

        function toggleSection(section) {
            const content = document.getElementById(section + '-content');
            const icon = document.getElementById(section + '-icon');
            
            if (content.classList.contains('hidden')) {
                content.classList.remove('hidden');
                icon.classList.add('rotate-180');
            } else {
                content.classList.add('hidden');
                icon.classList.remove('rotate-180');
            }
        }

        function calculateRowValue(input) {
            const row = input.closest('tr');
            const quantityInput = row.querySelector('.quantity-input');
            const priceInput = row.querySelector('.price-input');
            const valueInput = row.querySelector('.value-input');
            
            const quantity = parseFloat(quantityInput.value) || 0;
            const price = parseFloat(priceInput.value) || 0;
            const value = quantity * price;
            
            valueInput.value = value.toFixed(2);
            
            const section = input.dataset.section;
            calculateTotal(section);
        }

        function addRow(section) {
            const table = document.getElementById(section + '-table');
            const rowCount = rowCounters[section];
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="${rowCount + 1}" readonly></td>
                <td class="p-2"><input type="text" name="${section}[${rowCount}][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                <td class="p-2"><input type="number" min="1" value="1" name="${section}[${rowCount}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="${section}" onchange="calculateRowValue(this)"></td>
                <td class="p-2"><input type="text" name="${section}[${rowCount}][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                <td class="p-2"><input type="number" step="0.01" name="${section}[${rowCount}][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${section}" onchange="calculateRowValue(this)"></td>
                <td class="p-2"><input type="number" step="0.01" name="${section}[${rowCount}][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="${section}" readonly></td>
                <td class="p-2"><button type="button" onclick="removeRow(this, '${section}')" class="text-red-600 hover:text-red-800">✕</button></td>
            `;
            
            table.appendChild(row);
            rowCounters[section]++;
            updateRowNumbers(section);
        }

        function removeRow(button, section) {
            button.closest('tr').remove();
            updateRowNumbers(section);
            calculateTotal(section);
        }

        function updateRowNumbers(section) {
            const rows = document.querySelectorAll(`#${section}-table tr`);
            rows.forEach((row, index) => {
                row.querySelector('input[type="number"][readonly]').value = index + 1;
            });
        }

        function calculateTotal(section) {
            const inputs = document.querySelectorAll(`#${section}-table .value-input`);
            let total = 0;
            
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.getElementById(section + '-total').textContent = total.toFixed(2) + ' zł';
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            const servicesInputs = document.querySelectorAll('#services-table .value-input');
            const worksInputs = document.querySelectorAll('#works-table .value-input');
            const materialsInputs = document.querySelectorAll('#materials-table .value-input');
            
            let grandTotal = 0;
            
            servicesInputs.forEach(input => {
                grandTotal += parseFloat(input.value) || 0;
            });
            worksInputs.forEach(input => {
                grandTotal += parseFloat(input.value) || 0;
            });
            materialsInputs.forEach(input => {
                grandTotal += parseFloat(input.value) || 0;
            });
            
            // Dodaj sumy z niestandardowych sekcji
            customSections.forEach(sectionNum => {
                const inputs = document.querySelectorAll(`#custom${sectionNum}-table .value-input`);
                inputs.forEach(input => {
                    grandTotal += parseFloat(input.value) || 0;
                });
            });
            
            document.getElementById('grand-total').textContent = grandTotal.toFixed(2) + ' zł';
        }
        
        // ===========================================
        // OBSŁUGA DYNAMICZNYCH SEKCJI
        // ===========================================
        function addCustomSection() {
            const sectionName = prompt('Podaj nazwę nowej sekcji:');
            if (!sectionName || sectionName.trim() === '') {
                return;
            }
            customSectionCounter++;
            const sectionId = `custom${customSectionCounter}`;
            customSections.push(customSectionCounter);
            rowCounters[sectionId] = 1;
            const container = document.getElementById('custom-sections-container');
            const sectionDiv = document.createElement('div');
            sectionDiv.className = 'border border-gray-300 rounded';
            sectionDiv.id = `section-${sectionId}`;
            sectionDiv.innerHTML = `
                <div class="flex items-center justify-between p-4 bg-gray-50">
                    <button type="button" class="flex-1 flex items-center justify-between hover:bg-gray-100 transition" onclick="toggleSection('${sectionId}')">
                        <span class="font-semibold text-lg section-name" id="${sectionId}-name-label">${escapeHtml(sectionName.trim())}</span>
                    </button>
                    <button type="button" onclick="editSectionName('${sectionId}', ${customSectionCounter})" class="ml-2 px-2 py-1 text-blue-600 hover:text-blue-800 hover:bg-blue-50 rounded" title="Edytuj nazwę">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.232 5.232l3.536 3.536M9 13h3l8-8a2.828 2.828 0 00-4-4l-8 8v3z" /></svg>
                    </button>
                    <button type="button" onclick="removeCustomSection('${sectionId}')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
                <div id="${sectionId}-content" class="p-4 hidden">
                    <input type="hidden" id="${sectionId}-name-input" name="custom_sections[${customSectionCounter}][name]" value="${escapeHtml(sectionName.trim())}">
                    <table class="w-full mb-4">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left w-16">Nr</th>
                                <th class="p-2 text-left">Nazwa</th>
                                <th class="p-2 text-left w-20">Ilość</th>
                                <th class="p-2 text-left">Dostawca</th>
                                <th class="p-2 text-left w-32">Cena (zł)</th>
                                <th class="p-2 text-left w-32">Wartość (zł)</th>
                                <th class="p-2 w-16"></th>
                            </tr>
                        </thead>
                        <tbody id="${sectionId}-table">
                            <tr>
                                <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                <td class="p-2"><input type="text" name="custom_sections[${customSectionCounter}][items][0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                <td class="p-2"><input type="number" min="1" value="1" name="custom_sections[${customSectionCounter}][items][0][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="${sectionId}" onchange="calculateRowValue(this)"></td>
                                <td class="p-2"><input type="text" name="custom_sections[${customSectionCounter}][items][0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${customSectionCounter}][items][0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${sectionId}" onchange="calculateRowValue(this)"></td>
                                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${customSectionCounter}][items][0][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="${sectionId}" readonly></td>
                                <td class="p-2"></td>
                            </tr>
                        </tbody>
                    </table>
                    <button type="button" onclick="addCustomRow('${sectionId}', ${customSectionCounter})" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                    <div class="mt-4 text-right">
                        <span class="font-semibold">Suma: </span>
                        <span id="${sectionId}-total" class="font-bold text-lg">0.00 zł</span>
                    </div>
                </div>
            `;
            container.appendChild(sectionDiv);
            // Automatycznie rozwiń nową sekcję
            toggleSection(sectionId);
        }

        function editSectionName(sectionId, sectionNumber) {
            const label = document.getElementById(`${sectionId}-name-label`);
            if (!label) return;
            const current = label.textContent;
            const newName = prompt('Edytuj nazwę sekcji:', current);
            if (newName && newName.trim() !== '') {
                label.textContent = newName.trim();
                // For custom sections, update hidden input
                const inputId = `${sectionId}-name-input`;
                const input = document.getElementById(inputId);
                if (input) input.value = newName.trim();
            }
        }

        function removeMainSection(sectionId) {
            if (!confirm('Czy na pewno chcesz usunąć tę sekcję?')) {
                return;
            }
            // Hide the section and clear its rows
            const sectionDiv = document.getElementById(`section-${sectionId}`) || document.querySelector(`[onclick*="toggleSection('${sectionId}')"]`).closest('.border');
            if (sectionDiv) {
                sectionDiv.style.display = 'none';
            }
            // Clear table rows
            const table = document.getElementById(`${sectionId}-table`);
            if (table) {
                table.innerHTML = '';
            }
            // Reset total
            const total = document.getElementById(`${sectionId}-total`);
            if (total) {
                total.textContent = '0.00 zł';
            }
            calculateGrandTotal();
        }
        
        function removeCustomSection(sectionId) {
            if (!confirm('Czy na pewno chcesz usunąć tę sekcję?')) {
                return;
            }
            
            const sectionDiv = document.getElementById(`section-${sectionId}`);
            if (sectionDiv) {
                sectionDiv.remove();
                const sectionNumber = parseInt(sectionId.replace('custom', ''));
                const index = customSections.indexOf(sectionNumber);
                if (index > -1) {
                    customSections.splice(index, 1);
                }
                delete rowCounters[sectionId];
                calculateGrandTotal();
            }
        }
        
        function addCustomRow(sectionId, sectionNumber) {
            const table = document.getElementById(`${sectionId}-table`);
            const rowCount = rowCounters[sectionId];
            
            const row = document.createElement('tr');
            row.innerHTML = `
                <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="${rowCount + 1}" readonly></td>
                <td class="p-2"><input type="text" name="custom_sections[${sectionNumber}][items][${rowCount}][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                <td class="p-2"><input type="number" min="1" value="1" name="custom_sections[${sectionNumber}][items][${rowCount}][quantity]" class="w-full px-2 py-1 border rounded text-sm quantity-input" data-section="${sectionId}" onchange="calculateRowValue(this)"></td>
                <td class="p-2"><input type="text" name="custom_sections[${sectionNumber}][items][${rowCount}][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${sectionNumber}][items][${rowCount}][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${sectionId}" onchange="calculateRowValue(this)"></td>
                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${sectionNumber}][items][${rowCount}][value]" class="w-full px-2 py-1 border rounded text-sm bg-gray-100 value-input" data-section="${sectionId}" readonly></td>
                <td class="p-2"><button type="button" onclick="removeRow(this, '${sectionId}')" class="text-red-600 hover:text-red-800">✕</button></td>
            `;
            
            table.appendChild(row);
            rowCounters[sectionId]++;
            updateRowNumbers(sectionId);
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
<script>
function updateDateTime() {
    const now = new Date();
    const day = String(now.getDate()).padStart(2, '0');
    const month = String(now.getMonth() + 1).padStart(2, '0');
    const year = now.getFullYear();
    const hour = String(now.getHours()).padStart(2, '0');
    const minute = String(now.getMinutes()).padStart(2, '0');
    const formatted = `${day}.${month}.${year} ${hour}:${minute}`;
    document.getElementById('datetime').textContent = formatted;
}
setInterval(updateDateTime, 1000);
updateDateTime();
</script>
</html>
