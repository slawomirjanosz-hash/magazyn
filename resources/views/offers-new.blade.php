<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Zrób nową Ofertę</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-gray-100 min-h-screen flex flex-col">
    <header class="bg-white shadow">
        <div class="max-w-6xl mx-auto px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <img src="{{ asset('logo.png') }}" alt="Logo" class="h-10">
                <span class="text-xl font-bold">{{ config('app.name') }}</span>
            </div>
        </div>
    </header>
    <main class="flex-1 p-6">
        <div class="max-w-5xl mx-auto bg-white rounded shadow p-6 relative">
            <a href="{{ route('offers') }}" class="absolute top-4 left-4 flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 shadow rounded-full text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition z-10">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
                Powrót
            </a>
            
            <h1 class="text-3xl font-bold mb-6 text-center mt-12">Tworzenie nowej oferty</h1>
            
            <form action="#" method="POST" class="space-y-6" onkeydown="return event.key != 'Enter';">
                @csrf
                
                <!-- Podstawowe informacje -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Nr oferty</label>
                        <input type="text" name="offer_number" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Tytuł oferty</label>
                        <input type="text" name="offer_title" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-1">Data</label>
                        <input type="date" name="offer_date" value="{{ date('Y-m-d') }}" class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                    </div>
                </div>

                <!-- Sekcja Usługi -->
                <div class="border border-gray-300 rounded">
                    <button type="button" class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 transition" onclick="toggleSection('services')">
                        <span class="font-semibold text-lg">Usługi</span>
                        <svg id="services-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="services-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="services-table">
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2"><input type="text" name="services[0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="text" name="services[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="services[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="services" onchange="calculateTotal('services')"></td>
                                    <td class="p-2"></td>
                                </tr>
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
                    <button type="button" class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 transition" onclick="toggleSection('works')">
                        <span class="font-semibold text-lg">Prace własne</span>
                        <svg id="works-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="works-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="works-table">
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2"><input type="text" name="works[0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="text" name="works[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="works[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="works" onchange="calculateTotal('works')"></td>
                                    <td class="p-2"></td>
                                </tr>
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
                    <button type="button" class="w-full flex items-center justify-between p-4 bg-gray-50 hover:bg-gray-100 transition" onclick="toggleSection('materials')">
                        <span class="font-semibold text-lg">Materiały</span>
                        <svg id="materials-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <div id="materials-content" class="p-4 hidden">
                        <table class="w-full mb-4">
                            <thead>
                                <tr class="bg-gray-100">
                                    <th class="p-2 text-left w-16">Nr</th>
                                    <th class="p-2 text-left">Nazwa</th>
                                    <th class="p-2 text-left">Dostawca</th>
                                    <th class="p-2 text-left w-32">Cena (zł)</th>
                                    <th class="p-2 w-16"></th>
                                </tr>
                            </thead>
                            <tbody id="materials-table">
                                <tr>
                                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                    <td class="p-2">
                                        <div class="relative">
                                            <input type="text" 
                                                name="materials[0][name]" 
                                                class="w-full px-2 py-1 border rounded text-sm part-search-input" 
                                                data-index="0"
                                                placeholder="Nazwa lub wyszukaj w magazynie..."
                                                autocomplete="off">
                                            <div class="part-search-results absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                                        </div>
                                    </td>
                                    <td class="p-2"><input type="text" name="materials[0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                    <td class="p-2"><input type="number" step="0.01" name="materials[0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="materials" onchange="calculateTotal('materials')"></td>
                                    <td class="p-2"></td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="flex gap-2">
                            <button type="button" onclick="addRow('materials')" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700 text-sm">+ Dodaj wiersz</button>
                            <button type="button" onclick="openPartsCatalog()" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 text-sm">📂 Wybierz z katalogu</button>
                        </div>
                        <div class="mt-4 text-right">
                            <span class="font-semibold">Suma: </span>
                            <span id="materials-total" class="font-bold text-lg">0.00 zł</span>
                        </div>
                    </div>
                </div>

                <!-- Dynamiczne sekcje niestandardowe -->
                <div id="custom-sections-container"></div>

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

                <!-- Miejsce docelowe oferty -->
                <div class="border-t pt-6">
                    <label class="block text-sm font-medium text-gray-700 mb-2">Gdzie ma wylądować oferta?</label>
                    <select name="destination" class="w-full md:w-1/3 px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500 focus:border-blue-500" required>
                        <option value="portfolio">Portfolio</option>
                        <option value="inprogress">Oferty w toku</option>
                    </select>
                </div>

                <!-- Przycisk Zapisz -->
                <div class="text-center">
                    <button type="submit" class="px-8 py-3 bg-green-600 text-white rounded-lg text-lg font-semibold hover:bg-green-700 transition">
                        Zapisz ofertę
                    </button>
                </div>
            </form>
        </div>
    </main>
    <footer class="bg-white text-center py-4 mt-8 border-t text-gray-400 text-sm">
        Powered by ProximaLumine
    </footer>

    <!-- Modal katalogu części -->
    <div id="parts-catalog-modal" class="hidden fixed inset-0 bg-black bg-opacity-50 z-50 flex items-center justify-center p-4">
        <div class="bg-white rounded-lg shadow-xl max-w-4xl w-full max-h-[90vh] flex flex-col">
            <div class="p-4 border-b flex justify-between items-center">
                <h3 class="text-xl font-bold">Katalog części z magazynu</h3>
                <button type="button" onclick="closePartsCatalog()" class="text-gray-500 hover:text-gray-700 text-2xl">&times;</button>
            </div>
            
            <div class="p-4 border-b">
                <input type="text" 
                    id="catalog-search" 
                    placeholder="Szukaj w katalogu..." 
                    class="w-full px-3 py-2 border border-gray-300 rounded focus:ring-2 focus:ring-blue-500">
            </div>
            
            <div class="flex-1 overflow-y-auto p-4">
                <div id="catalog-loading" class="text-center py-8">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-b-2 border-blue-600"></div>
                    <p class="mt-2 text-gray-600">Wczytywanie katalogu...</p>
                </div>
                <div id="catalog-content" class="hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-gray-100 sticky top-0">
                            <tr>
                                <th class="p-2 text-left w-10">
                                    <input type="checkbox" id="select-all-parts" onchange="toggleSelectAll()">
                                </th>
                                <th class="p-2 text-left">Nazwa</th>
                                <th class="p-2 text-left">Opis</th>
                                <th class="p-2 text-left">Dostawca</th>
                                <th class="p-2 text-left">Ilość</th>
                                <th class="p-2 text-left">Cena</th>
                            </tr>
                        </thead>
                        <tbody id="catalog-parts-list">
                        </tbody>
                    </table>
                </div>
            </div>
            
            <div class="p-4 border-t flex justify-between items-center">
                <span id="selected-count" class="text-gray-600">Wybrano: 0</span>
                <div class="flex gap-2">
                    <button type="button" onclick="closePartsCatalog()" class="px-4 py-2 bg-gray-300 text-gray-700 rounded hover:bg-gray-400">Anuluj</button>
                    <button type="button" onclick="addSelectedParts()" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">Dodaj wybrane</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let rowCounters = {
            services: 1,
            works: 1,
            materials: 1
        };
        
        let customSectionCounter = 0;
        let customSections = [];

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

        function addRow(section) {
            const table = document.getElementById(section + '-table');
            const rowCount = rowCounters[section];
            
            const row = document.createElement('tr');
            
            if (section === 'materials') {
                row.innerHTML = `
                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="${rowCount + 1}" readonly></td>
                    <td class="p-2">
                        <div class="relative">
                            <input type="text" 
                                name="${section}[${rowCount}][name]" 
                                class="w-full px-2 py-1 border rounded text-sm part-search-input" 
                                data-index="${rowCount}"
                                placeholder="Nazwa lub wyszukaj w magazynie..."
                                autocomplete="off">
                            <div class="part-search-results absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                    </td>
                    <td class="p-2"><input type="text" name="${section}[${rowCount}][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                    <td class="p-2"><input type="number" step="0.01" name="${section}[${rowCount}][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${section}" onchange="calculateTotal('${section}')"></td>
                    <td class="p-2"><button type="button" onclick="removeRow(this, '${section}')" class="text-red-600 hover:text-red-800">✕</button></td>
                `;
            } else {
                row.innerHTML = `
                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="${rowCount + 1}" readonly></td>
                    <td class="p-2"><input type="text" name="${section}[${rowCount}][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                    <td class="p-2"><input type="text" name="${section}[${rowCount}][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                    <td class="p-2"><input type="number" step="0.01" name="${section}[${rowCount}][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${section}" onchange="calculateTotal('${section}')"></td>
                    <td class="p-2"><button type="button" onclick="removeRow(this, '${section}')" class="text-red-600 hover:text-red-800">✕</button></td>
                `;
            }
            
            table.appendChild(row);
            rowCounters[section]++;
            updateRowNumbers(section);
            
            if (section === 'materials') {
                initPartSearch();
            }
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
            const inputs = document.querySelectorAll(`#${section}-table .price-input`);
            let total = 0;
            
            inputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                total += value;
            });
            
            document.getElementById(section + '-total').textContent = total.toFixed(2) + ' zł';
            calculateGrandTotal();
        }

        function calculateGrandTotal() {
            const servicesInputs = document.querySelectorAll('#services-table .price-input');
            const worksInputs = document.querySelectorAll('#works-table .price-input');
            const materialsInputs = document.querySelectorAll('#materials-table .price-input');
            
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
            customSections.forEach(sectionId => {
                const inputs = document.querySelectorAll(`#custom-${sectionId}-table .price-input`);
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
                        <span class="font-semibold text-lg">${escapeHtml(sectionName.trim())}</span>
                        <svg id="${sectionId}-icon" class="h-5 w-5 transform transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7" /></svg>
                    </button>
                    <button type="button" onclick="removeCustomSection('${sectionId}')" class="ml-2 px-3 py-1 text-red-600 hover:text-red-800 hover:bg-red-50 rounded" title="Usuń sekcję">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" /></svg>
                    </button>
                </div>
                <div id="${sectionId}-content" class="p-4 hidden">
                    <input type="hidden" name="custom_sections[${customSectionCounter}][name]" value="${escapeHtml(sectionName.trim())}">
                    <table class="w-full mb-4">
                        <thead>
                            <tr class="bg-gray-100">
                                <th class="p-2 text-left w-16">Nr</th>
                                <th class="p-2 text-left">Nazwa</th>
                                <th class="p-2 text-left">Dostawca</th>
                                <th class="p-2 text-left w-32">Cena (zł)</th>
                                <th class="p-2 w-16"></th>
                            </tr>
                        </thead>
                        <tbody id="${sectionId}-table">
                            <tr>
                                <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="1" readonly></td>
                                <td class="p-2"><input type="text" name="custom_sections[${customSectionCounter}][items][0][name]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                <td class="p-2"><input type="text" name="custom_sections[${customSectionCounter}][items][0][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${customSectionCounter}][items][0][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${sectionId}" onchange="calculateTotal('${sectionId}')"></td>
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
                <td class="p-2"><input type="text" name="custom_sections[${sectionNumber}][items][${rowCount}][supplier]" class="w-full px-2 py-1 border rounded text-sm"></td>
                <td class="p-2"><input type="number" step="0.01" name="custom_sections[${sectionNumber}][items][${rowCount}][price]" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="${sectionId}" onchange="calculateTotal('${sectionId}')"></td>
                <td class="p-2"><button type="button" onclick="removeRow(this, '${sectionId}')" class="text-red-600 hover:text-red-800">✕</button></td>
            `;
            
            table.appendChild(row);
            rowCounters[sectionId]++;
            updateRowNumbers(sectionId);
        }

        // ===========================================
        // OBSŁUGA WYSZUKIWANIA CZĘŚCI
        // ===========================================
        let searchTimeout;
        
        function initPartSearch() {
            document.querySelectorAll('.part-search-input').forEach(input => {
                if (input.dataset.initialized) return;
                input.dataset.initialized = 'true';
                
                input.addEventListener('input', function(e) {
                    clearTimeout(searchTimeout);
                    const query = e.target.value;
                    const resultsDiv = e.target.closest('.relative').querySelector('.part-search-results');
                    
                    if (query.length < 2) {
                        resultsDiv.classList.add('hidden');
                        return;
                    }
                    
                    searchTimeout = setTimeout(() => {
                        fetch(`/api/parts/search?q=${encodeURIComponent(query)}`)
                            .then(response => response.json())
                            .then(parts => {
                                if (parts.length === 0) {
                                    resultsDiv.innerHTML = '<div class="p-2 text-gray-500 text-sm">Nie znaleziono części</div>';
                                    resultsDiv.classList.remove('hidden');
                                    return;
                                }
                                
                                resultsDiv.innerHTML = parts.map(part => `
                                    <div class="p-2 hover:bg-gray-100 cursor-pointer border-b part-search-item" 
                                         data-name="${part.name}"
                                         data-supplier="${part.supplier || ''}"
                                         data-price="${part.net_price || ''}">
                                        <div class="font-medium text-sm">${part.name}</div>
                                        <div class="text-xs text-gray-600">
                                            ${part.description || ''} | 
                                            Dostępne: ${part.quantity || 0} szt. | 
                                            Cena: ${part.net_price || '0.00'} zł
                                        </div>
                                    </div>
                                `).join('');
                                
                                resultsDiv.classList.remove('hidden');
                                
                                // Obsługa kliknięcia na wynik
                                resultsDiv.querySelectorAll('.part-search-item').forEach(item => {
                                    item.addEventListener('click', function() {
                                        const row = e.target.closest('tr');
                                        row.querySelector('[name*="[name]"]').value = this.dataset.name;
                                        row.querySelector('[name*="[supplier]"]').value = this.dataset.supplier;
                                        row.querySelector('[name*="[price]"]').value = this.dataset.price;
                                        resultsDiv.classList.add('hidden');
                                        calculateTotal('materials');
                                    });
                                });
                            })
                            .catch(error => {
                                console.error('Błąd wyszukiwania:', error);
                                resultsDiv.classList.add('hidden');
                            });
                    }, 300);
                });
                
                // Ukryj wyniki po kliknięciu poza polem
                document.addEventListener('click', function(event) {
                    if (!input.contains(event.target)) {
                        const resultsDiv = input.closest('.relative').querySelector('.part-search-results');
                        resultsDiv.classList.add('hidden');
                    }
                });
            });
        }
        
        // Inicjalizuj wyszukiwanie dla pierwszego wiersza
        document.addEventListener('DOMContentLoaded', function() {
            initPartSearch();
        });

        // ===========================================
        // OBSŁUGA KATALOGU CZĘŚCI
        // ===========================================
        let allParts = [];
        let filteredParts = [];
        
        async function openPartsCatalog() {
            const modal = document.getElementById('parts-catalog-modal');
            modal.classList.remove('hidden');
            
            if (allParts.length === 0) {
                await loadPartsCatalog();
            }
        }
        
        function closePartsCatalog() {
            document.getElementById('parts-catalog-modal').classList.add('hidden');
            document.getElementById('catalog-search').value = '';
            document.querySelectorAll('.part-checkbox').forEach(cb => cb.checked = false);
            document.getElementById('select-all-parts').checked = false;
            updateSelectedCount();
        }
        
        async function loadPartsCatalog() {
            try {
                const response = await fetch('/api/parts/catalog');
                allParts = await response.json();
                filteredParts = [...allParts];
                
                document.getElementById('catalog-loading').classList.add('hidden');
                document.getElementById('catalog-content').classList.remove('hidden');
                
                renderCatalog();
                setupCatalogSearch();
            } catch (error) {
                console.error('Błąd ładowania katalogu:', error);
                alert('Nie udało się załadować katalogu części');
            }
        }
        
        function renderCatalog() {
            const tbody = document.getElementById('catalog-parts-list');
            
            if (filteredParts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="p-4 text-center text-gray-500">Nie znaleziono części</td></tr>';
                return;
            }
            
            tbody.innerHTML = filteredParts.map(part => `
                <tr class="border-b hover:bg-gray-50">
                    <td class="p-2">
                        <input type="checkbox" 
                            class="part-checkbox" 
                            data-id="${part.id}"
                            data-name="${escapeHtml(part.name)}"
                            data-supplier="${escapeHtml(part.supplier || '')}"
                            data-price="${part.net_price || 0}"
                            onchange="updateSelectedCount()">
                    </td>
                    <td class="p-2 font-medium">${escapeHtml(part.name)}</td>
                    <td class="p-2 text-gray-600">${escapeHtml(part.description || '-')}</td>
                    <td class="p-2">${escapeHtml(part.supplier || '-')}</td>
                    <td class="p-2">${part.quantity || 0}</td>
                    <td class="p-2 font-medium">${parseFloat(part.net_price || 0).toFixed(2)} zł</td>
                </tr>
            `).join('');
        }
        
        function setupCatalogSearch() {
            const searchInput = document.getElementById('catalog-search');
            let searchTimeout;
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const query = this.value.toLowerCase();
                    
                    if (query === '') {
                        filteredParts = [...allParts];
                    } else {
                        filteredParts = allParts.filter(part => 
                            part.name.toLowerCase().includes(query) ||
                            (part.description && part.description.toLowerCase().includes(query)) ||
                            (part.supplier && part.supplier.toLowerCase().includes(query))
                        );
                    }
                    
                    renderCatalog();
                    document.getElementById('select-all-parts').checked = false;
                    updateSelectedCount();
                }, 300);
            });
        }
        
        function toggleSelectAll() {
            const selectAll = document.getElementById('select-all-parts');
            const checkboxes = document.querySelectorAll('.part-checkbox');
            
            checkboxes.forEach(cb => {
                cb.checked = selectAll.checked;
            });
            
            updateSelectedCount();
        }
        
        function updateSelectedCount() {
            const count = document.querySelectorAll('.part-checkbox:checked').length;
            document.getElementById('selected-count').textContent = `Wybrano: ${count}`;
        }
        
        function addSelectedParts() {
            const selected = document.querySelectorAll('.part-checkbox:checked');
            
            if (selected.length === 0) {
                alert('Nie wybrano żadnych części');
                return;
            }
            
            selected.forEach(checkbox => {
                const name = checkbox.dataset.name;
                const supplier = checkbox.dataset.supplier;
                const price = checkbox.dataset.price;
                
                // Dodaj nowy wiersz
                const table = document.getElementById('materials-table');
                const rowCount = rowCounters.materials;
                
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td class="p-2"><input type="number" class="w-full px-2 py-1 border rounded text-sm" value="${rowCount + 1}" readonly></td>
                    <td class="p-2">
                        <div class="relative">
                            <input type="text" 
                                name="materials[${rowCount}][name]" 
                                class="w-full px-2 py-1 border rounded text-sm part-search-input" 
                                data-index="${rowCount}"
                                value="${name}"
                                placeholder="Nazwa lub wyszukaj w magazynie..."
                                autocomplete="off">
                            <div class="part-search-results absolute z-10 w-full bg-white border border-gray-300 rounded mt-1 shadow-lg hidden max-h-60 overflow-y-auto"></div>
                        </div>
                    </td>
                    <td class="p-2"><input type="text" name="materials[${rowCount}][supplier]" value="${supplier}" class="w-full px-2 py-1 border rounded text-sm"></td>
                    <td class="p-2"><input type="number" step="0.01" name="materials[${rowCount}][price]" value="${price}" class="w-full px-2 py-1 border rounded text-sm price-input" data-section="materials" onchange="calculateTotal('materials')"></td>
                    <td class="p-2"><button type="button" onclick="removeRow(this, 'materials')" class="text-red-600 hover:text-red-800">✕</button></td>
                `;
                
                table.appendChild(row);
                rowCounters.materials++;
            });
            
            updateRowNumbers('materials');
            calculateTotal('materials');
            initPartSearch();
            closePartsCatalog();
        }
        
        function escapeHtml(text) {
            const div = document.createElement('div');
            div.textContent = text;
            return div.innerHTML;
        }
    </script>
</body>
</html>
