<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>Magazyn – Sprawdź</title>
    <link rel="icon" type="image/png" href="{{ asset('logo_proxima_male.png') }}">
    @vite(['resources/css/app.css'])
</head>
<body class="bg-gray-100">

{{-- MENU --}}
@include('parts.menu')

{{-- KOMUNIKAT BŁĘDU / SUKCES --}}
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

    <h2 class="text-xl font-bold mb-4">Katalog produktów</h2>

    {{-- FILTRY --}}
    <div class="mb-4">
        <div class="flex gap-2 mb-2">
            <input
                type="text"
                id="search-input"
                placeholder="Szukaj po nazwie (wpisuj na żywo)"
                class="border p-2 flex-1"
            >

            <select id="category-filter" class="border p-2">
                <option value="">Wszystkie kategorie</option>
                @foreach($categories as $c)
                    <option value="{{ $c->name }}">{{ $c->name }}</option>
                @endforeach
            </select>

            <select id="supplier-filter" class="border p-2">
                <option value="">Wszyscy dostawcy</option>
                @foreach($suppliers as $s)
                    <option value="{{ $s->name }}">{{ $s->short_name ?? $s->name }}</option>
                @endforeach
            </select>

            <button id="clear-filters-btn" class="bg-gray-500 text-white px-4 py-2 rounded">
                Wyczyść
            </button>
        </div>

        <div class="flex gap-2">

            <button type="button"
               id="btn-download-xlsx"
               data-filename="katalog.xlsx"
               data-selected-ids=""
               class="px-4 py-2 bg-green-600 text-white rounded">
                Pobierz do Excel
            </button>

            <button type="button"
               id="btn-download-word"
               data-filename="katalog.docx"
               data-selected-ids=""
               class="px-4 py-2 bg-blue-600 text-white rounded">
                Pobierz do Word
            </button>

            <a href="{{ route('magazyn.check.export', request()->query()) }}"
               id="csv-export-link"
               class="px-4 py-2 bg-gray-600 text-white rounded">
                Eksportuj CSV
            </a>
        </div>
    </div>

    {{-- BULK ACTION BUTTONS --}}
    <div id="bulk-actions" class="mt-4 hidden flex gap-2">
        <button type="button" id="view-selected-btn" class="px-3 py-2 bg-blue-300 text-gray-800 rounded text-xl" title="Wyświetl zaznaczone">
            👁️
        </button>
        <form id="bulk-delete-form" method="POST" action="{{ route('magazyn.parts.bulkDelete') }}" class="inline" onsubmit="return confirmBulkDelete();">
            @csrf
            @method('DELETE')
            <button type="submit" class="px-3 py-2 bg-red-300 text-gray-800 rounded text-xl" title="Usuń zaznaczone">
                🗑️
            </button>
        </form>
    </div>

    {{-- JS ALERT CONTAINER (dla pobierania) --}}
    <div id="js-alert-container" class="max-w-6xl mx-auto mt-4"></div>

    {{-- TABELA --}}

    {{-- TABELA --}}
    <table class="w-full border border-collapse text-xs">
        <thead>
            <tr class="bg-gray-100">
                <th class="border p-2 text-center text-xs">
                    <input type="checkbox" id="select-all" class="w-4 h-4 cursor-pointer" title="Zaznacz wszystkie">
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[16rem] max-w-[24rem] sortable" data-column="name">
                    Produkty <span class="sort-icon">▲</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[16rem] max-w-[28rem] sortable" data-column="description">
                    Opis <span class="sort-icon">▲</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem] sortable" data-column="supplier">
                    Dost. <span class="sort-icon">▲</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem] sortable" data-column="price">
                    Cena netto <span class="sort-icon">▲</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[6.5rem] sortable" data-column="category">
                    Kategoria <span class="sort-icon">▲</span>
                </th>
                <th class="border p-2 text-center cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[2.5rem] max-w-[4rem] sortable" data-column="quantity">
                    Stan <span class="sort-icon">▲</span>
                </th>
                <th class="border p-1 text-center text-xs whitespace-nowrap min-w-[4.5rem]" style="width: 6ch;">User</th>
                @if(auth()->user()->show_action_column)
                    <th class="border p-2 text-center text-xs whitespace-nowrap min-w-[5.5rem]">Akcja</th>
                @endif
            </tr>
        </thead>

        <tbody>
            @foreach($parts as $p)
                @php
                    $supplierShort = '';
                    if ($p->supplier) {
                        $sup = $suppliers->firstWhere('name', $p->supplier);
                        $supplierShort = $sup ? ($sup->short_name ?? $sup->name) : $p->supplier;
                    }
                @endphp
                <tr data-name="{{ strtolower($p->name) }}"
                    data-description="{{ strtolower($p->description ?? '') }}"
                    data-supplier="{{ strtolower($p->supplier ?? '') }}"
                    data-category="{{ strtolower($p->category->name ?? '') }}"
                    data-price="{{ $p->net_price ?? 0 }}"
                    data-quantity="{{ $p->quantity }}">
                    {{-- CHECKBOX --}}
                    <td class="border p-2 text-center">
                        <input type="checkbox" name="part_ids[]" value="{{ $p->id }}" class="part-checkbox w-4 h-4 cursor-pointer" form="bulk-delete-form">
                    </td>

                    {{-- CZĘŚĆ --}}
                    <td class="border p-2">
                        {{ $p->name }}
                    </td>

                    {{-- OPIS --}}
                    <td class="border p-2 text-xs text-gray-700">
                        {{ $p->description ?? '-' }}
                    </td>

                    {{-- DOSTAWCA --}}
                    <td class="border p-2 text-center text-xs text-gray-700">
                        {{ $supplierShort ?: '-' }}
                    </td>

                    {{-- CENA NETTO --}}
                    <td class="border p-2 text-center">
                        @if($p->net_price)
                            {{ number_format($p->net_price, 2) }} <span class="text-xs">{{ $p->currency }}</span>
                        @else
                            -
                        @endif
                    </td>

                    {{-- KATEGORIA --}}
                    <td class="border p-2">
                        {{ $p->category->name ?? '-' }}
                    </td>

                    {{-- STAN --}}
                    <td class="border p-2 text-center font-bold text-xs">
                        {{ $p->quantity }}
                    </td>

                    {{-- UŻYTKOWNIK --}}
                    <td class="border p-2 text-center text-xs text-gray-600">
                        {{ $p->lastModifiedBy ? $p->lastModifiedBy->short_name : '-' }}
                    </td>

                    {{-- AKCJE --}}
                    @if(auth()->user()->show_action_column)
                        <td class="border p-2">
                            <div class="flex items-center justify-between gap-2">

                                <!-- + / - -->
                                <div class="flex gap-2 justify-center flex-1">

                                    {{-- ➕ --}}
                                    <form method="POST" action="{{ route('parts.add') }}">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $p->name }}">
                                        <input type="hidden" name="category_id" value="{{ $p->category_id }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="redirect_to" value="check">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <input type="hidden" name="filter_category_id" value="{{ request('category_id') }}">

                                        <button class="bg-gray-200 px-2 rounded">
                                            ➕
                                        </button>
                                    </form>

                                    {{-- ➖ --}}
                                    <form method="POST" action="{{ route('parts.remove') }}">
                                        @csrf
                                        <input type="hidden" name="name" value="{{ $p->name }}">
                                        <input type="hidden" name="quantity" value="1">
                                        <input type="hidden" name="redirect_to" value="check">
                                        <input type="hidden" name="search" value="{{ request('search') }}">
                                        <input type="hidden" name="filter_category_id" value="{{ request('category_id') }}">

                                        <button class="bg-gray-200 px-2 rounded">
                                            ➖
                                        </button>
                                    </form>

                                </div>

                                {{-- ✏️ EDYCJA --}}
                                <button class="bg-blue-100 hover:bg-blue-200 px-2 rounded text-sm edit-part-btn" 
                                        data-part-id="{{ $p->id }}"
                                        data-part-name="{{ $p->name }}"
                                        data-part-description="{{ $p->description ?? '' }}"
                                        data-part-quantity="{{ $p->quantity }}"
                                        data-part-price="{{ $p->net_price }}"
                                        data-part-currency="{{ $p->currency }}"
                                        data-part-supplier="{{ $p->supplier ?? '' }}"
                                        data-part-category-id="{{ $p->category_id }}"
                                        title="Edytuj produkt">
                                    ✏️
                                </button>

                                {{-- ❌ --}}
                                <form method="POST"
                                      action="{{ route('parts.destroy', $p->id) }}"
                                      onsubmit="return confirm('Usunąć część z bazy danych?');">
                                    @csrf
                                    @method('DELETE')

                                    <button class="bg-gray-200 px-2 rounded">
                                        ❌
                                    </button>
                                </form>

                            </div>
                        </td>
                    @endif
                </tr>
            @endforeach
        </tbody>
    </table>

</div>

<script>
    let currentSortColumn = null;
    let currentSortDirection = 'asc';
    
    function sortTable(column) {
        const table = document.querySelector('table');
        const tbody = table.querySelector('tbody');
        const rows = Array.from(tbody.querySelectorAll('tr[data-name]'));
        
        if (currentSortColumn === column) {
            currentSortDirection = currentSortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            currentSortColumn = column;
            currentSortDirection = 'asc';
        }
        
        rows.sort((a, b) => {
            let aVal, bVal;
            
            if (column === 'price' || column === 'quantity') {
                aVal = parseFloat(a.getAttribute('data-' + column)) || 0;
                bVal = parseFloat(b.getAttribute('data-' + column)) || 0;
            } else {
                aVal = (a.getAttribute('data-' + column) || '').toLowerCase();
                bVal = (b.getAttribute('data-' + column) || '').toLowerCase();
            }
            
            if (aVal < bVal) return currentSortDirection === 'asc' ? -1 : 1;
            if (aVal > bVal) return currentSortDirection === 'asc' ? 1 : -1;
            return 0;
        });
        
        rows.forEach(row => tbody.appendChild(row));
        
        // Aktualizuj ikony sortowania
        table.querySelectorAll('.sortable .sort-icon').forEach(icon => {
            icon.textContent = '▲';
            icon.style.color = '#9CA3AF';
        });
        
        const activeHeader = table.querySelector(`.sortable[data-column="${column}"] .sort-icon`);
        if (activeHeader) {
            activeHeader.textContent = currentSortDirection === 'asc' ? '▲' : '▼';
            activeHeader.style.color = '#000';
        }
    }
    
    // Event listeners dla sortowania kolumn
    document.addEventListener('DOMContentLoaded', function() {
        const table = document.querySelector('table');
        if (table) {
            table.querySelectorAll('.sortable').forEach(header => {
                header.addEventListener('click', function() {
                    const column = this.getAttribute('data-column');
                    sortTable(column);
                });
            });
        }
    });

    (function(){
        const table = document.querySelector('table');
        const searchInput = document.getElementById('search-input');
        const categoryFilter = document.getElementById('category-filter');
        const supplierFilter = document.getElementById('supplier-filter');
        const clearFiltersBtn = document.getElementById('clear-filters-btn');
        
        // Funkcja filtrowania tabeli
        function filterTable() {
            const searchTerm = searchInput.value.toLowerCase().trim();
            const categoryValue = categoryFilter.value;
            const supplierValue = supplierFilter.value;
            
            const rows = table.querySelectorAll('tbody tr[data-name]');
            let visibleCount = 0;
            
            rows.forEach(row => {
                const name = row.getAttribute('data-name') || '';
                const category = row.getAttribute('data-category') || '';
                const supplier = row.getAttribute('data-supplier') || '';
                
                const matchesSearch = !searchTerm || name.includes(searchTerm);
                const matchesCategory = !categoryValue || category === categoryValue.toLowerCase();
                const matchesSupplier = !supplierValue || supplier === supplierValue.toLowerCase();
                
                if (matchesSearch && matchesCategory && matchesSupplier) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });
        }
        
        // Event listeners dla filtrów
        if (searchInput) {
            let searchTimeout;
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(filterTable, 300);
            });
        }
        
        if (categoryFilter) {
            categoryFilter.addEventListener('change', filterTable);
        }
        
        if (supplierFilter) {
            supplierFilter.addEventListener('change', filterTable);
        }
        
        if (clearFiltersBtn) {
            clearFiltersBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                if (categoryFilter) categoryFilter.value = '';
                if (supplierFilter) supplierFilter.value = '';
                filterTable();
            });
        }


        function showAlert(type, message, timeout = 5000) {
            const container = document.getElementById('js-alert-container');
            if (!container) return;
            container.innerHTML = '';

            const div = document.createElement('div');
            div.className = 'max-w-6xl mx-auto mt-4 p-2 rounded';
            if (type === 'success') div.className += ' bg-green-100 text-green-800';
            if (type === 'error') div.className += ' bg-red-100 text-red-800';
            div.textContent = message;
            container.appendChild(div);

            if (timeout) setTimeout(() => { if (container.contains(div)) container.removeChild(div); }, timeout);
        }

        function attachDownloader(btnId, defaultFilename) {
            const btn = document.getElementById(btnId);
            if (!btn) return;

            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const url = btn.getAttribute('href');
                const filename = btn.dataset.filename || defaultFilename;

                showAlert('success', 'Pobieranie...');

                fetch(url, { credentials: 'same-origin' })
                    .then(response => {
                        const ct = response.headers.get('content-type') || '';
                        if (!response.ok) throw new Error('Błąd serwera');
                        if (ct.indexOf('application') === -1 && ct.indexOf('text') !== -1) {
                            // probably HTML with error message
                            return response.text().then(text => { throw new Error(text || 'Nieoczekiwana odpowiedź'); });
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        const blobUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = blobUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(blobUrl);
                        showAlert('success', 'Pobrano plik.');
                    })
                    .catch(err => {
                        const message = (err && err.message) ? err.message : 'Wystąpił błąd podczas pobierania';
                        showAlert('error', message, 8000);
                    });
            });
        }


        // Bulk delete functionality
        const selectAllCheckbox = document.getElementById('select-all');
        const partCheckboxes = document.querySelectorAll('.part-checkbox');
        const bulkActions = document.getElementById('bulk-actions');
        const viewSelectedBtn = document.getElementById('view-selected-btn');
        const tableRows = document.querySelectorAll('tbody tr');

        function updateBulkDeleteButton() {
            const checkedCount = document.querySelectorAll('.part-checkbox:checked').length;
            
            if (checkedCount > 0) {
                bulkActions.classList.remove('hidden');
            } else {
                bulkActions.classList.add('hidden');
            }
        }

        // View selected functionality
        let viewingSelected = false;
        viewSelectedBtn.addEventListener('click', function() {
            viewingSelected = !viewingSelected;
            
            if (viewingSelected) {
                this.classList.remove('bg-blue-300');
                this.classList.add('bg-green-400');
                tableRows.forEach(row => {
                    const checkbox = row.querySelector('.part-checkbox');
                    if (!checkbox.checked) {
                        row.style.display = 'none';
                    }
                });
            } else {
                this.classList.remove('bg-green-400');
                this.classList.add('bg-blue-300');
                tableRows.forEach(row => {
                    row.style.display = '';
                });
            }
        });

        selectAllCheckbox.addEventListener('change', function() {
            partCheckboxes.forEach(cb => cb.checked = this.checked);
            updateBulkDeleteButton();
            updateSelectedIds();
        });

        partCheckboxes.forEach(cb => {
            cb.addEventListener('change', function() {
                selectAllCheckbox.checked = [...partCheckboxes].every(c => c.checked);
                updateBulkDeleteButton();
                updateSelectedIds();
            });
        });

        window.confirmBulkDelete = function() {
            const count = document.querySelectorAll('.part-checkbox:checked').length;
            return count > 0 && confirm(`Czy na pewno usunąć ${count} zaznaczonych produktów?`);
        };

        function updateSelectedIds() {
            const selectedIds = [...document.querySelectorAll('.part-checkbox:checked')]
                .map(cb => cb.value)
                .join(',');
            
            // Update hidden input for CSV
            document.getElementById('selected-ids').value = selectedIds;
            
            // Update data attribute for XLSX and Word buttons
            document.getElementById('btn-download-xlsx').setAttribute('data-selected-ids', selectedIds);
            document.getElementById('btn-download-word').setAttribute('data-selected-ids', selectedIds);
            
            // Update CSV link
            const csvLink = document.getElementById('csv-export-link');
            const baseUrl = '{{ route("magazyn.check.export") }}';
            if (selectedIds) {
                csvLink.href = baseUrl + '?selected_ids=' + selectedIds;
            } else {
                csvLink.href = baseUrl;
            }
        }

        function attachDownloaderWithSelected(buttonId, filename, endpoint) {
            const btn = document.getElementById(buttonId);
            if (!btn) return;
            btn.addEventListener('click', function(e) {
                e.preventDefault();
                const selectedIds = this.getAttribute('data-selected-ids');
                const params = new URLSearchParams();
                if (selectedIds) params.append('selected_ids', selectedIds);
                const url = `/magazyn/sprawdz/${endpoint}?${params.toString()}`;
                
                fetch(url)
                    .then(response => {
                        showAlert('info', 'Pobieranie...');
                        const ct = response.headers.get('content-type');
                        if (!response.ok) throw new Error('Błąd serwera');
                        if (ct.indexOf('application') === -1 && ct.indexOf('text') !== -1) {
                            return response.text().then(text => { throw new Error(text || 'Nieoczekiwana odpowiedź'); });
                        }
                        return response.blob();
                    })
                    .then(blob => {
                        const blobUrl = window.URL.createObjectURL(blob);
                        const a = document.createElement('a');
                        a.href = blobUrl;
                        a.download = filename;
                        document.body.appendChild(a);
                        a.click();
                        a.remove();
                        window.URL.revokeObjectURL(blobUrl);
                        showAlert('success', 'Pobrano plik.');
                    })
                    .catch(err => {
                        showAlert('error', 'Błąd pobierania: ' + err.message);
                    });
            });
        }

        attachDownloaderWithSelected('btn-download-xlsx', 'katalog.xlsx', 'eksport-xlsx');
        attachDownloaderWithSelected('btn-download-word', 'katalog.docx', 'eksport-word');
    })();

    // Modal edycji produktu
    const editButtons = document.querySelectorAll('.edit-part-btn');
    const categories = @json($categories);
    const suppliers = @json($suppliers);
    
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const partId = this.dataset.partId;
            const partName = this.dataset.partName;
            const partDescription = this.dataset.partDescription || '';
            const partQuantity = this.dataset.partQuantity || 0;
            const partPrice = this.dataset.partPrice || '';
            const partCurrency = this.dataset.partCurrency || 'PLN';
            const partSupplier = this.dataset.partSupplier || '';
            const partCategoryId = this.dataset.partCategoryId;

            const categoriesOptions = categories.map(cat => 
                `<option value="${cat.id}" ${cat.id == partCategoryId ? 'selected' : ''}>${cat.name}</option>`
            ).join('');
            
            const suppliersOptions = '<option value="">Brak</option>' + suppliers.map(sup => 
                `<option value="${sup.name}" ${sup.name === partSupplier ? 'selected' : ''}>${sup.name}</option>`
            ).join('');

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-2xl w-full max-h-[90vh] overflow-y-auto">
                    <h3 class="text-xl font-bold mb-4">Edycja produktu</h3>
                    <form action="/magazyn/parts/${partId}/update" method="POST">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <input type="hidden" name="_method" value="PUT">
                        
                        <div class="grid grid-cols-2 gap-4">
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">Nazwa produktu *</label>
                                <input type="text" name="name" value="${partName}" required
                                       class="w-full px-3 py-2 border rounded">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Kategoria *</label>
                                <select name="category_id" required class="w-full px-3 py-2 border rounded">
                                    ${categoriesOptions}
                                </select>
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Ilość *</label>
                                <input type="number" name="quantity" value="${partQuantity}" min="0" required
                                       class="w-full px-3 py-2 border rounded">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Cena netto</label>
                                <input type="number" name="net_price" step="0.01" min="0" value="${partPrice}" 
                                       class="w-full px-3 py-2 border rounded" placeholder="Opcjonalne">
                            </div>
                            
                            <div>
                                <label class="block text-sm font-medium mb-2">Waluta *</label>
                                <select name="currency" required class="w-full px-3 py-2 border rounded">
                                    <option value="PLN" ${partCurrency === 'PLN' ? 'selected' : ''}>PLN</option>
                                    <option value="EUR" ${partCurrency === 'EUR' ? 'selected' : ''}>EUR</option>
                                    <option value="$" ${partCurrency === '$' ? 'selected' : ''}>$</option>
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">Dostawca</label>
                                <select name="supplier" class="w-full px-3 py-2 border rounded">
                                    ${suppliersOptions}
                                </select>
                            </div>
                            
                            <div class="col-span-2">
                                <label class="block text-sm font-medium mb-2">Opis</label>
                                <textarea name="description" rows="3" class="w-full px-3 py-2 border rounded" placeholder="Opcjonalne">${partDescription}</textarea>
                            </div>
                        </div>
                        
                        <div class="flex gap-2 mt-6">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Zapisz zmiany</button>
                            <button type="button" class="bg-gray-400 text-white px-4 py-2 rounded hover:bg-gray-500 close-modal">Anuluj</button>
                        </div>
                    </form>
                </div>
            `;
            document.body.appendChild(modal);

            modal.querySelector('.close-modal').addEventListener('click', () => {
                modal.remove();
            });

            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    modal.remove();
                }
            });
        });
    });
</script>
</body>
</html>
