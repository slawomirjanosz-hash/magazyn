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
    <form method="GET"
          action="{{ route('magazyn.check') }}"
          class="flex gap-2 mb-4">

        <input
            type="text"
            name="search"
            placeholder="Szukaj po nazwie"
            value="{{ request('search') }}"
            class="border p-2 w-64"
        >

        <select name="category_id" class="border p-2">
            <option value="">Wszystkie kategorie</option>
            @foreach($categories as $c)
                <option value="{{ $c->id }}"
                    @selected(request('category_id') == $c->id)>
                    {{ $c->name }}
                </option>
            @endforeach
        </select>

        <button class="bg-blue-600 text-white px-4 rounded">
            Filtruj
        </button>

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
           class="px-4 py-2 bg-blue-600 text-white rounded ml-2">
            Pobierz do Word
        </button>

        <a href="{{ route('magazyn.check.export', request()->query()) }}"
           id="csv-export-link"
           class="px-4 py-2 bg-gray-600 text-white rounded ml-2">
            Eksportuj CSV
        </a>
        <input type="hidden" id="selected-ids" name="selected_ids" value="">
    </form>

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
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[16rem] max-w-[24rem]" onclick="sortTable('name')">
                    Produkty <span class="align-middle ml-1 {{ $sortBy === 'name' ? '' : 'text-gray-400' }}">{{ $sortBy === 'name' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[16rem] max-w-[28rem]" onclick="sortTable('description')">
                    Opis <span class="align-middle ml-1 {{ $sortBy === 'description' ? '' : 'text-gray-400' }}">{{ $sortBy === 'description' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem]" onclick="sortTable('supplier')">
                    Dost. <span class="align-middle ml-1 {{ $sortBy === 'supplier' ? '' : 'text-gray-400' }}">{{ $sortBy === 'supplier' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[3.5rem] max-w-[6rem]" onclick="sortTable('net_price')">
                    Cena netto <span class="align-middle ml-1 {{ $sortBy === 'net_price' ? '' : 'text-gray-400' }}">{{ $sortBy === 'net_price' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
                </th>
                <th class="border p-2 cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[6.5rem]" onclick="sortTable('category')">
                    Kategoria <span class="align-middle ml-1 {{ $sortBy === 'category' ? '' : 'text-gray-400' }}">{{ $sortBy === 'category' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
                </th>
                <th class="border p-2 text-center cursor-pointer hover:bg-gray-200 text-xs whitespace-nowrap min-w-[2.5rem] max-w-[4rem]" onclick="sortTable('quantity')">
                    Stan <span class="align-middle ml-1 {{ $sortBy === 'quantity' ? '' : 'text-gray-400' }}">{{ $sortBy === 'quantity' && $sortDir === 'desc' ? '▼' : '▲' }}</span>
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
                <tr>
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
                                        data-part-price="{{ $p->net_price }}"
                                        data-part-currency="{{ $p->currency }}"
                                        title="Edytuj cenę">
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
    function sortTable(column) {
        const url = new URL(window.location.href);
        const currentSort = url.searchParams.get('sort_by');
        const currentDir = url.searchParams.get('sort_dir') || 'asc';
        
        if (currentSort === column) {
            // Toggle direction
            url.searchParams.set('sort_dir', currentDir === 'asc' ? 'desc' : 'asc');
        } else {
            // New column, default to asc
            url.searchParams.set('sort_by', column);
            url.searchParams.set('sort_dir', 'asc');
        }
        
        window.location.href = url.toString();
    }
    
    (function(){
        // Auto-submit form na zmianę kategorii
        const categorySelect = document.querySelector('select[name="category_id"]');
        if (categorySelect) {
            categorySelect.addEventListener('change', function() {
                this.form.submit();
            });
        }

        // Auto-submit form na zmianę w wyszukiwaniu (z debounce)
        const searchInput = document.querySelector('input[name="search"]');
        let searchTimeout;
        if (searchInput) {
            // Ustaw kursor na końcu tekstu
            searchInput.focus();
            searchInput.setSelectionRange(searchInput.value.length, searchInput.value.length);
            
            searchInput.addEventListener('input', function() {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    this.form.submit();
                }, 300); // czeka 300ms po ostatnim znaku
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

    // Modal edycji ceny
    const editButtons = document.querySelectorAll('.edit-part-btn');
    editButtons.forEach(btn => {
        btn.addEventListener('click', function() {
            const partId = this.dataset.partId;
            const partName = this.dataset.partName;
            const partPrice = this.dataset.partPrice;
            const partCurrency = this.dataset.partCurrency;

            const modal = document.createElement('div');
            modal.className = 'fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50';
            modal.innerHTML = `
                <div class="bg-white rounded-lg p-6 max-w-md w-full">
                    <h3 class="text-xl font-bold mb-4">Edycja ceny: ${partName}</h3>
                    <form action="/magazyn/parts/${partId}/update-price" method="POST">
                        <input type="hidden" name="_token" value="${document.querySelector('meta[name="csrf-token"]')?.content || ''}">
                        <input type="hidden" name="_method" value="PUT">
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Cena netto:</label>
                            <input type="number" name="net_price" step="0.01" min="0" value="${partPrice || ''}" 
                                   class="w-full px-3 py-2 border rounded" placeholder="Pozostaw puste aby usunąć">
                        </div>
                        <div class="mb-4">
                            <label class="block text-sm font-medium mb-2">Waluta:</label>
                            <select name="currency" class="w-full px-3 py-2 border rounded">
                                <option value="PLN" ${partCurrency === 'PLN' ? 'selected' : ''}>PLN</option>
                                <option value="EUR" ${partCurrency === 'EUR' ? 'selected' : ''}>EUR</option>
                                <option value="$" ${partCurrency === '$' ? 'selected' : ''}>$</option>
                            </select>
                        </div>
                        <div class="flex gap-2">
                            <button type="submit" class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700">Zapisz</button>
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
