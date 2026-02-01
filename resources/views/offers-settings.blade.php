<!DOCTYPE html>
<html lang="pl">
<head>
    <meta charset="UTF-8">
    <title>Ustawienia Ofert</title>
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
            <nav class="flex gap-2 items-center">
                @auth
                    <span class="text-gray-700 text-sm">{{ Auth::user()->name }}</span>
                    <form action="{{ route('logout') }}" method="POST" class="inline">
                        @csrf
                        <button type="submit" class="px-3 py-2 text-sm bg-blue-600 hover:bg-blue-700 text-white rounded transition whitespace-nowrap">
                            Wyloguj
                        </button>
                    </form>
                @endauth
            </nav>
        </div>
    </header>
    
    <main class="flex-1">
        <div class="max-w-4xl mx-auto mt-8 p-6 bg-white rounded shadow relative">
            <a href="{{ route('offers') }}" class="absolute top-4 left-4 flex items-center gap-2 px-4 py-2 bg-white border border-gray-300 shadow rounded-full text-gray-700 hover:bg-gray-100 hover:border-gray-400 transition z-10">
                <svg xmlns='http://www.w3.org/2000/svg' class='h-5 w-5' fill='none' viewBox='0 0 24 24' stroke='currentColor'><path stroke-linecap='round' stroke-linejoin='round' stroke-width='2' d='M15 19l-7-7 7-7' /></svg>
                Powrót
            </a>
            
            <h1 class="text-3xl font-bold mb-8 text-center">⚙️ Ustawienia Ofert</h1>
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
                    <span class="block sm:inline">{{ session('success') }}</span>
                </div>
            @endif
            
            <div class="bg-gray-50 p-6 rounded border">
                <p class="text-gray-600 text-sm mb-4 font-semibold">Konfigurator formatu numeru oferty:</p>
                
                @php
                    $offerSettings = \DB::table('offer_settings')->first();
                    if (!$offerSettings) {
                        $offerSettings = (object)[
                            'element1_type' => 'text',
                            'element1_value' => 'OFF',
                            'separator1' => '_',
                            'element2_type' => 'date',
                            'element2_value' => '',
                            'separator2' => '_',
                            'element3_type' => 'number',
                            'element3_value' => '',
                            'separator3' => '_',
                            'element4_type' => 'empty',
                            'element4_value' => '',
                            'start_number' => 1
                        ];
                    }
                @endphp
                
                <form action="{{ route('offers.settings.save') }}" method="POST" class="space-y-4" id="offer-settings-form">
                    @csrf
                    
                    {{-- Element 1 --}}
                    <div class="bg-white p-3 rounded border">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Element 1:</label>
                        <div class="flex gap-2 items-center">
                            <select name="element1_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleOfferElementInput('element1', this.value)">
                                <option value="empty" {{ ($offerSettings->element1_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                <option value="text" {{ ($offerSettings->element1_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                <option value="date" {{ ($offerSettings->element1_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                <option value="time" {{ ($offerSettings->element1_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                            </select>
                            <input 
                                type="text" 
                                name="element1_value" 
                                id="offer_element1_value"
                                value="{{ $offerSettings->element1_value ?? '' }}"
                                placeholder="Wartość"
                                maxlength="6"
                                class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                style="{{ ($offerSettings->element1_type ?? 'text') !== 'text' ? 'display:none;' : '' }}"
                            >
                            <div class="flex items-center gap-1">
                                <label class="text-xs text-gray-600">Separator:</label>
                                <select name="separator1" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                    <option value="_" {{ ($offerSettings->separator1 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                    <option value="-" {{ ($offerSettings->separator1 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                    <option value="," {{ ($offerSettings->separator1 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                    <option value="." {{ ($offerSettings->separator1 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                    <option value="/" {{ ($offerSettings->separator1 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                    <option value="\\" {{ ($offerSettings->separator1 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Element 2 --}}
                    <div class="bg-white p-3 rounded border">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Element 2:</label>
                        <div class="flex gap-2 items-center">
                            <select name="element2_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleOfferElementInput('element2', this.value)">
                                <option value="empty" {{ ($offerSettings->element2_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                <option value="text" {{ ($offerSettings->element2_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                <option value="date" {{ ($offerSettings->element2_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                <option value="time" {{ ($offerSettings->element2_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                            </select>
                            <input 
                                type="text" 
                                name="element2_value" 
                                id="offer_element2_value"
                                value="{{ $offerSettings->element2_value ?? '' }}"
                                placeholder="Wartość"
                                maxlength="6"
                                class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                style="{{ ($offerSettings->element2_type ?? 'date') !== 'text' ? 'display:none;' : '' }}"
                            >
                            <div class="flex items-center gap-1">
                                <label class="text-xs text-gray-600">Separator:</label>
                                <select name="separator2" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                    <option value="_" {{ ($offerSettings->separator2 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                    <option value="-" {{ ($offerSettings->separator2 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                    <option value="," {{ ($offerSettings->separator2 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                    <option value="." {{ ($offerSettings->separator2 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                    <option value="/" {{ ($offerSettings->separator2 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                    <option value="\\" {{ ($offerSettings->separator2 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Element 3 --}}
                    <div class="bg-white p-3 rounded border">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Element 3:</label>
                        <div class="flex gap-2 items-center">
                            <select name="element3_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleOfferElementInput('element3', this.value)">
                                <option value="empty" {{ ($offerSettings->element3_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                <option value="text" {{ ($offerSettings->element3_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                <option value="date" {{ ($offerSettings->element3_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                <option value="time" {{ ($offerSettings->element3_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                <option value="number" {{ ($offerSettings->element3_type ?? '') === 'number' ? 'selected' : '' }}>Nr oferty</option>                                <option value="customer" {{ ($offerSettings->element4_type ?? '') === 'customer' ? 'selected' : '' }}>Skrót klienta</option>                            </select>
                            <input 
                                type="text" 
                                name="element3_value" 
                                id="offer_element3_value"
                                value="{{ $offerSettings->element3_value ?? '' }}"
                                placeholder="Wartość"
                                maxlength="6"
                                class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                style="{{ ($offerSettings->element3_type ?? 'number') !== 'text' ? 'display:none;' : '' }}"
                            >
                            <div class="flex items-center gap-1">
                                <label class="text-xs text-gray-600">Separator:</label>
                                <select name="separator3" class="px-2 py-1 border border-gray-300 rounded text-sm w-16">
                                    <option value="_" {{ ($offerSettings->separator3 ?? '_') === '_' ? 'selected' : '' }}>_</option>
                                    <option value="-" {{ ($offerSettings->separator3 ?? '') === '-' ? 'selected' : '' }}>-</option>
                                    <option value="," {{ ($offerSettings->separator3 ?? '') === ',' ? 'selected' : '' }}>,</option>
                                    <option value="." {{ ($offerSettings->separator3 ?? '') === '.' ? 'selected' : '' }}>.</option>
                                    <option value="/" {{ ($offerSettings->separator3 ?? '') === '/' ? 'selected' : '' }}>/</option>
                                    <option value="\\" {{ ($offerSettings->separator3 ?? '') === '\\' ? 'selected' : '' }}>\</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    {{-- Numer startowy (dla type=number) --}}
                    <div class="bg-white p-3 rounded border">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Numer startowy (dla elementu typu "Nr oferty"):</label>
                        <input 
                            type="number" 
                            name="start_number" 
                            id="offer_start_number"
                            value="{{ $offerSettings->start_number ?? 1 }}"
                            min="1"
                            class="px-2 py-1 border border-gray-300 rounded text-sm w-32"
                        >
                        <span class="text-xs text-gray-500 ml-2">(np. 1, 100, 1000 itd.)</span>
                    </div>
                    
                    {{-- Element 4 --}}
                    <div class="bg-white p-3 rounded border">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Element 4:</label>
                        <div class="flex gap-2 items-center">
                            <select name="element4_type" class="px-2 py-1 border border-gray-300 rounded text-sm w-auto" onchange="toggleOfferElementInput('element4', this.value)">
                                <option value="empty" {{ ($offerSettings->element4_type ?? '') === 'empty' ? 'selected' : '' }}>-- brak --</option>
                                <option value="text" {{ ($offerSettings->element4_type ?? '') === 'text' ? 'selected' : '' }}>Tekst</option>
                                <option value="date" {{ ($offerSettings->element4_type ?? '') === 'date' ? 'selected' : '' }}>Data (YYYYMMDD)</option>
                                <option value="time" {{ ($offerSettings->element4_type ?? '') === 'time' ? 'selected' : '' }}>Godzina (HHMM)</option>
                                <option value="number" {{ ($offerSettings->element4_type ?? '') === 'number' ? 'selected' : '' }}>Nr oferty</option>
                                <option value="customer" {{ ($offerSettings->element4_type ?? '') === 'customer' ? 'selected' : '' }}>Skrót klienta</option>
                            </select>
                            <input 
                                type="text" 
                                name="element4_value" 
                                id="offer_element4_value"
                                value="{{ $offerSettings->element4_value ?? '' }}"
                                placeholder="Wartość"
                                maxlength="6"
                                class="px-2 py-1 border border-gray-400 rounded text-sm w-24"
                                style="{{ ($offerSettings->element4_type ?? 'empty') !== 'text' ? 'display:none;' : '' }}"
                            >
                        </div>
                    </div>
                    
                    {{-- Podgląd --}}
                    <div class="bg-blue-50 p-3 rounded border border-blue-200">
                        <label class="block text-sm font-semibold text-gray-700 mb-2">Podgląd numeru oferty:</label>
                        <div id="offer-number-preview" class="font-mono text-lg text-blue-700 font-bold"></div>
                    </div>
                    
                    <button type="submit" class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700">
                        Zapisz format numeru oferty
                    </button>
                </form>
            </div>
            
            {{-- Sekcja uploadu szablonu ofertówki --}}
            <div class="bg-gray-50 p-6 rounded border mt-6">
                <p class="text-gray-600 text-sm mb-4 font-semibold">📄 Szablon ofertówki Word:</p>
                <p class="text-xs text-gray-500 mb-4">
                    Wgraj własny szablon ofertówki w formacie Word (.docx). System zastąpi znaczniki w dokumencie danymi oferty.<br>
                    <strong>Dostępne znaczniki:</strong> 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;OFFER_NUMBER&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;OFFER_TITLE&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;OFFER_DATE&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;OFFER_DESCRIPTION&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;TOTAL_PRICE&#125;&#125;</code>,
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;COMPANY_NAME&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;COMPANY_ADDRESS&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;COMPANY_EMAIL&#125;&#125;</code>, 
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;COMPANY_PHONE&#125;&#125;</code>,
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;CUSTOMER_NAME&#125;&#125;</code>,
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;CUSTOMER_ADDRESS&#125;&#125;</code>,
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;CUSTOMER_EMAIL&#125;&#125;</code>,
                    <code class="bg-gray-200 px-1 rounded">&#123;&#123;CUSTOMER_PHONE&#125;&#125;</code>
                </p>
                
                @php
                    $templatePath = $offerSettings->offer_template_path ?? null;
                    $templateName = $offerSettings->offer_template_original_name ?? null;
                @endphp
                
                @if($templatePath && $templateName)
                    <div class="bg-green-50 border border-green-300 rounded p-3 mb-4 flex items-center justify-between">
                        <div>
                            <span class="text-green-700 font-semibold">✅ Aktualny szablon:</span>
                            <span class="text-green-800">{{ $templateName }}</span>
                        </div>
                        <form action="{{ route('offers.settings.delete-template') }}" method="POST" class="inline" onsubmit="return confirm('Czy na pewno usunąć szablon?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="px-3 py-1 bg-red-500 text-white text-sm rounded hover:bg-red-600">
                                Usuń szablon
                            </button>
                        </form>
                    </div>
                @else
                    <div class="bg-yellow-50 border border-yellow-300 rounded p-3 mb-4">
                        <span class="text-yellow-700">⚠️ Brak wgranego szablonu – oferty będą generowane domyślnie.</span>
                    </div>
                @endif
                
                <form action="{{ route('offers.settings.upload-template') }}" method="POST" enctype="multipart/form-data" class="flex items-center gap-4">
                    @csrf
                    <input type="file" name="offer_template" accept=".docx" class="border border-gray-300 rounded px-2 py-1 text-sm" required>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700">
                        Wgraj szablon
                    </button>
                </form>
            </div>
        </div>
    </main>
    
    <footer class="bg-white text-center py-4 mt-8 border-t text-gray-400 text-sm">
        Powered by ProximaLumine
    </footer>
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

function getOfferNumberPreview() {
    const types = [
        document.querySelector('[name="element1_type"]').value,
        document.querySelector('[name="element2_type"]').value,
        document.querySelector('[name="element3_type"]').value,
        document.querySelector('[name="element4_type"]').value
    ];
    const values = [
        document.getElementById('offer_element1_value').value,
        document.getElementById('offer_element2_value').value,
        document.getElementById('offer_element3_value').value,
        document.getElementById('offer_element4_value').value
    ];
    const seps = [
        document.querySelector('[name="separator1"]').value,
        document.querySelector('[name="separator2"]').value,
        document.querySelector('[name="separator3"]').value
    ];
    const startNumber = parseInt(document.getElementById('offer_start_number')?.value || 1);
    let parts = [];
    for (let i = 0; i < 4; i++) {
        if (types[i] === 'empty') continue;
        if (types[i] === 'text') {
            parts.push(values[i] || `ELEMENT${i+1}`);
        } else if (types[i] === 'date') {
            parts.push('20260127');
        } else if (types[i] === 'time') {
            parts.push('1200');
        } else if (types[i] === 'number') {
            parts.push(String(startNumber).padStart(4, '0'));
        } else if (types[i] === 'customer') {
            parts.push('KLIENT');
        }
    }
    let preview = '';
    for (let i = 0; i < parts.length; i++) {
        preview += parts[i];
        if (i < parts.length - 1) preview += seps[i] || '_';
    }
    document.getElementById('offer-number-preview').textContent = preview;
}

function toggleOfferElementInput(element, type) {
    const input = document.getElementById('offer_' + element + '_value');
    if (type === 'text') {
        input.style.display = 'block';
    } else {
        input.style.display = 'none';
    }
    getOfferNumberPreview();
}

document.getElementById('offer-settings-form').addEventListener('input', getOfferNumberPreview);
window.addEventListener('DOMContentLoaded', getOfferNumberPreview);
</script>
</html>
