<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Part;
use App\Models\PartRemoval;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Http\Request;

use Maatwebsite\Excel\Facades\Excel;
use App\Exports\PartsExport;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class PartController extends Controller
{
    /* ===================== WIDOKI ===================== */

    // DODAJ
    public function addView()
    {
        return view('parts.add', [
            'categories'  => Category::all(),
            'suppliers'   => \App\Models\Supplier::where('is_supplier', true)->orderBy('name')->get(),
            'sessionAdds' => array_reverse(session('adds', [])),
            'parts' => Part::with(['category', 'lastModifiedBy'])->orderBy('name')->get(),
        ]);
    }

    // POBIERZ
    public function removeView()
    {
        return view('parts.remove', [
            'sessionRemoves' => array_reverse(session('removes', [])),
            'parts' => Part::with(['category', 'lastModifiedBy'])->orderBy('name')->get(),
            'suppliers' => Supplier::orderBy('name')->get(),
            'projects' => \App\Models\Project::where('status', 'in_progress')->orderBy('project_number')->get(),
        ]);
    }

    // SPRAWDŹ / KATALOG
    public function checkView(Request $request)
    {
        $query = Part::with(['category', 'lastModifiedBy']);

        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('category_id')) {
            $query->where('category_id', $request->category_id);
        }

        if ($request->filled('supplier')) {
            $query->where('supplier', $request->supplier);
        }

        // Sortowanie
        $sortBy = $request->get('sort_by', 'name');
        $sortDir = $request->get('sort_dir', 'asc');
        
        if ($sortBy === 'category') {
            $query->join('categories', 'parts.category_id', '=', 'categories.id')
                  ->orderBy('categories.name', $sortDir)
                  ->select('parts.*');
        } else {
            $query->orderBy($sortBy, $sortDir);
        }

        return view('parts.check', [
            'parts'      => $query->get(),
            'categories' => Category::all(),
            'suppliers'  => \App\Models\Supplier::orderBy('name')->get(),
            'sortBy'     => $sortBy,
            'sortDir'    => $sortDir,
        ]);
    }

    // ZAMÓWIENIA
    public function ordersView()
    {
        $orderSettings = \DB::table('order_settings')->first();
        $orderNamePreview = $orderSettings ? $this->generateOrderNamePreview($orderSettings) : 'Zamówienie';
        
        return view('parts.orders', [
            'parts' => Part::with(['category', 'lastModifiedBy'])->orderBy('name')->get(),
            'categories' => Category::all(),
            'suppliers' => \App\Models\Supplier::where('is_supplier', true)->orderBy('name')->get(),
            'orderSettings' => $orderSettings,
            'orderNamePreview' => $orderNamePreview,
            'orders' => \App\Models\Order::with(['user', 'receivedBy'])->orderBy('issued_at', 'desc')->get(),
        ]);
    }

    // USTAWIENIA
    public function settingsView()
    {
        return view('parts.settings', [
            'categories' => Category::withCount('parts')->get(),
            'suppliers' => \App\Models\Supplier::all(),
            'companySettings' => \App\Models\CompanySetting::first(),
            'orderSettings' => \DB::table('order_settings')->first(),
        ]);
    }

    // PROJEKTY
    public function projectsView()
    {
        return view('parts.projects', [
            'users' => User::orderBy('name')->get(),
            'parts' => Part::with('category')->orderBy('name')->get(),
            'inProgressProjects' => \App\Models\Project::where('status', 'in_progress')->with('responsibleUser')->get(),
            'warrantyProjects' => \App\Models\Project::where('status', 'warranty')->with('responsibleUser')->get(),
            'archivedProjects' => \App\Models\Project::where('status', 'archived')->with('responsibleUser')->get(),
        ]);
    }

    public function storeProject(Request $request)
    {
        $request->validate([
            'project_number' => 'required|string|unique:projects,project_number',
            'name' => 'required|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'warranty_period' => 'nullable|integer|min:0',
            'finished_at' => 'nullable|date',
        ]);

        $project = \App\Models\Project::create([
            'project_number' => $request->project_number,
            'name' => $request->name,
            'budget' => $request->budget,
            'responsible_user_id' => $request->responsible_user_id,
            'warranty_period' => $request->warranty_period,
            'started_at' => now(),
            'finished_at' => $request->finished_at,
            'status' => 'in_progress',
        ]);

        return redirect()->route('magazyn.projects')->with('success', 'Projekt "' . $project->name . '" został utworzony.');
    }

    public function showProject(\App\Models\Project $project)
    {
        // Pobierz wszystkie pobierania (niezgrupowane) z informacją o statusie
        $removals = \App\Models\ProjectRemoval::where('project_id', $project->id)
            ->with(['part', 'user', 'returnedBy'])
            ->orderBy('created_at', 'desc')
            ->get();

        return view('parts.project-details', [
            'project' => $project->load('responsibleUser'),
            'removals' => $removals,
        ]);
    }

    public function returnProduct(\App\Models\Project $project, \App\Models\ProjectRemoval $removal)
    {
        // Sprawdź, czy removal należy do projektu
        if ($removal->project_id !== $project->id) {
            return redirect()->back()->with('error', 'Błąd: produkt nie należy do tego projektu.');
        }

        // Sprawdź, czy produkt nie został już zwrócony
        if ($removal->status === 'returned') {
            return redirect()->back()->with('error', 'Ten produkt został już zwrócony.');
        }

        // Dodaj ilość z powrotem do magazynu
        $part = $removal->part;
        $part->quantity += $removal->quantity;
        $part->save();

        // Zaktualizuj status removal
        $removal->status = 'returned';
        $removal->returned_at = now();
        $removal->returned_by_user_id = auth()->id();
        $removal->save();

        return redirect()->back()->with('success', 'Produkt został zwrócony do katalogu.');
    }

    public function finishProject(\App\Models\Project $project)
    {
        // Sprawdź, czy projekt jest w toku
        if ($project->status !== 'in_progress') {
            return redirect()->back()->with('error', 'Można zakończyć tylko projekt w toku.');
        }

        // Zaktualizuj status i datę zakończenia
        $project->status = 'warranty';
        $project->finished_at = now();
        $project->save();

        return redirect()->back()->with('success', 'Projekt został zakończony i przeszedł na gwarancję.');
    }

    public function editProject(\App\Models\Project $project)
    {
        return view('parts.project-edit', [
            'project' => $project,
            'users' => User::orderBy('name')->get(),
        ]);
    }

    public function updateProject(Request $request, \App\Models\Project $project)
    {
        $request->validate([
            'project_number' => 'required|string|unique:projects,project_number,' . $project->id,
            'name' => 'required|string|max:255',
            'budget' => 'nullable|numeric|min:0',
            'responsible_user_id' => 'nullable|exists:users,id',
            'warranty_period' => 'nullable|integer|min:0',
            'started_at' => 'nullable|date',
            'finished_at' => 'nullable|date',
            'status' => 'required|in:in_progress,warranty,archived',
        ]);

        $project->update([
            'project_number' => $request->project_number,
            'name' => $request->name,
            'budget' => $request->budget,
            'responsible_user_id' => $request->responsible_user_id,
            'warranty_period' => $request->warranty_period,
            'started_at' => $request->started_at,
            'finished_at' => $request->finished_at,
            'status' => $request->status,
        ]);

        return redirect()->route('magazyn.projects.show', $project->id)->with('success', 'Projekt został zaktualizowany.');
    }

    public function getRemovalDates($projectId, Request $request)
    {
        $removals = \App\Models\ProjectRemoval::where('project_id', $projectId)
            ->where('part_id', $request->part_id)
            ->where('user_id', $request->user_id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function($r) {
                return [
                    'date' => $r->created_at->format('Y-m-d H:i'),
                    'quantity' => $r->quantity,
                ];
            });

        return response()->json(['removals' => $removals]);
    }

    // DODAJ KATEGORIĘ
    public function addCategory(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name',
        ]);

        Category::create([
            'name' => $request->name,
        ]);

        return redirect()->route('magazyn.settings')->with('success', 'Kategoria "' . $request->name . '" została dodana.');
    }

    // EDYTUJ KATEGORIĘ
    public function updateCategory(Request $request, Category $category)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:categories,name,' . $category->id,
        ]);

        $oldName = $category->name;
        $category->update([
            'name' => $request->name,
        ]);

        return redirect()->route('magazyn.settings')->with('success', 'Kategoria "' . $oldName . '" została zmieniona na "' . $request->name . '".');
    }

    // USUŃ KATEGORIĘ
    public function deleteCategory(Category $category)
    {
        // Check if category has products
        if ($category->parts()->count() > 0) {
            return redirect()->route('magazyn.settings')->with('error', 'Nie można usunąć kategorii "' . $category->name . '" - zawiera produkty.');
        }

        $name = $category->name;
        $category->delete();

        return redirect()->route('magazyn.settings')->with('success', 'Kategoria "' . $name . '" została usunięta.');
    }

    // USUWANIE ZAWARTOŚCI KATEGORII (wszystkie produkty w kategorii)
    public function clearCategoryContents(Category $category)
    {
        $categoryName = $category->name;
        $count = $category->parts()->count();
        
        $category->parts()->delete();

        return redirect()->route('magazyn.settings')->with('success', "Usunięto {$count} produktów z kategorii \"{$categoryName}\".");
    }

    // EKSPORT DO EXCELA (CSV)
    public function export(Request $request)
    {
        $query = Part::with('category')->orderBy('name');

        // Jeśli są zaznaczone IDs (z checkboxów lub filtrów), filtruj tylko te
        if ($request->filled('selected_ids')) {
            $ids = array_filter(explode(',', $request->selected_ids));
            $query->whereIn('id', $ids);
        } elseif ($request->filled('ids')) {
            $ids = array_filter(explode(',', $request->ids));
            $query->whereIn('id', $ids);
        } else {
            // W przeciwnym razie stosuj filtry
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
        }

        $parts = $query->get();

        $headers = [
            'Content-Type' => 'text/csv; charset=utf-8',
            'Content-Disposition' => 'attachment; filename="katalog.csv"',
        ];

        $callback = function() use ($parts) {
            $output = fopen('php://output', 'w');
            // UTF-8 BOM so Excel detects UTF-8 correctly
            fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
            // Tell Excel to use semicolon as separator
            fwrite($output, "sep=;\r\n");
            fputcsv($output, ['Nazwa', 'Opis', 'Kategoria', 'Stan'], ';');

            foreach ($parts as $p) {
                // Ensure description is a single line: replace newlines with spaces and collapse multiple spaces
                $description = $p->description ? preg_replace('/\s+/', ' ', str_replace(["\r", "\n"], ' ', $p->description)) : '-';

                fputcsv($output, [
                    $p->name,
                    $description,
                    $p->category->name ?? '-',
                    $p->quantity,
                ], ';');
            }

            fclose($output);
        };

        return response()->stream($callback, 200, $headers);
    }

    // EKSPORT DO XLSX (sformatowany)
    public function exportXlsx(Request $request)
    {
        // Guard: jeśli pakiet maatwebsite/excel nie jest zainstalowany, pokaż przyjazny komunikat zamiast fatalnego błędu
        if (!class_exists(\Maatwebsite\Excel\Facades\Excel::class)) {
            return redirect()->back()
                ->with('error', 'Brak pakietu "maatwebsite/excel". Zainstaluj go: composer require maatwebsite/excel');
        }

        $query = Part::with('category')->orderBy('name');

        // Jeśli są zaznaczone IDs (z checkboxów lub filtrów), filtruj tylko te
        if ($request->filled('selected_ids')) {
            $ids = array_filter(explode(',', $request->selected_ids));
            $query->whereIn('id', $ids);
        } elseif ($request->filled('ids')) {
            $ids = array_filter(explode(',', $request->ids));
            $query->whereIn('id', $ids);
        } else {
            // W przeciwnym razie stosuj filtry
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
        }

        $parts = $query->get();

        try {
            return Excel::download(new PartsExport($parts), 'katalog.xlsx');
        } catch (\Throwable $e) {
            return redirect()->back()
                ->with('error', 'Wystąpił błąd podczas generowania pliku: ' . $e->getMessage());
        }
    }

    // EKSPORT DO WORD (.docx)
    public function exportWord(Request $request)
    {
        if (!class_exists(\PhpOffice\PhpWord\PhpWord::class)) {
            return redirect()->back()
                ->with('error', 'Brak pakietu "phpoffice/phpword". Zainstaluj go: composer require phpoffice/phpword');
        }

        $query = Part::with('category')->orderBy('name');

        // Jeśli są zaznaczone IDs (z checkboxów lub filtrów), filtruj tylko te
        if ($request->filled('selected_ids')) {
            $ids = array_filter(explode(',', $request->selected_ids));
            $query->whereIn('id', $ids);
        } elseif ($request->filled('ids')) {
            $ids = array_filter(explode(',', $request->ids));
            $query->whereIn('id', $ids);
        } else {
            // W przeciwnym razie stosuj filtry
            if ($request->filled('search')) {
                $query->where('name', 'like', '%' . $request->search . '%');
            }

            if ($request->filled('category_id')) {
                $query->where('category_id', $request->category_id);
            }
        }

        $parts = $query->get();

        try {
            $phpWord = new \PhpOffice\PhpWord\PhpWord();
            $section = $phpWord->addSection();

            // Pobierz dane firmy z bazy danych
            $companySettings = \App\Models\CompanySetting::first();
            
            // header: logo + company info (keeps aspect ratio by setting height on image)
            $logoData = $companySettings && $companySettings->logo 
                ? $companySettings->logo
                : null;
            
            // Jeśli logo jest w formacie base64 (data:image/...), należy je zapisać tymczasowo
            if ($logoData && str_starts_with($logoData, 'data:image')) {
                // Wyodrębniamy dane base64
                preg_match('/data:image\\/([a-zA-Z]+);base64,(.*)/', $logoData, $matches);
                if ($matches) {
                    $extension = $matches[1];
                    $base64Data = $matches[2];
                    $tempLogoPath = sys_get_temp_dir() . '/temp_logo_' . uniqid() . '.' . $extension;
                    file_put_contents($tempLogoPath, base64_decode($base64Data));
                    $logoPath = $tempLogoPath;
                } else {
                    $logoPath = public_path('logo.png');
                }
            } elseif ($logoData) {
                // Stary format - ścieżka do pliku
                $logoPath = storage_path('app/public/' . $logoData);
            } else {
                $logoPath = public_path('logo.png');
            }
            
            $header = $section->addHeader();
            $headerTable = $header->addTable(['cellMargin' => 40]);
            $headerTable->addRow();
            if (file_exists($logoPath)) {
                // logo cell: set height to ~1.2cm (≈34pt) and center vertically; add small top margin to visually center with text
                $headerTable->addCell(1600, ['valign' => 'center'])->addImage($logoPath, [
                    'height' => 34,
                    'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                    'marginTop' => 6,
                ]);
                
                // Usuwamy tymczasowy plik jeśli został utworzony
                if (isset($tempLogoPath) && file_exists($tempLogoPath)) {
                    unlink($tempLogoPath);
                }
            } else {
                $headerTable->addCell(1600, ['valign' => 'center']);
            }

            // company info cell: expanded width so the three-line block fits neatly
            $companyCell = $headerTable->addCell(8000, ['valign' => 'center']);
            // Reduce font sizes and spacing so text does not appear larger than the logo
            
            // Użyj danych z bazy lub domyślnych
            $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
            $companyAddress = $companySettings && $companySettings->address && $companySettings->city 
                ? ($companySettings->address . ', ' . ($companySettings->postal_code ? $companySettings->postal_code . ' ' : '') . $companySettings->city)
                : 'ul. Słoneczna, 40-100 Warszawa';
            $companyEmail = $companySettings && $companySettings->email ? $companySettings->email : 'test@example.com';
            
            $companyCell->addText($companyName, ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
            $companyCell->addText($companyAddress, ['size' => 9], ['spaceAfter' => 0]);
            $companyCell->addLink('mailto:' . $companyEmail, $companyEmail, ['size' => 9, 'color' => '4B5563'], ['spaceAfter' => 0]);

            // Info line with date (kept in body below header)
            $infoText = 'Wygenerowano żądaną zawartość magazynu — ' . now()->format('Y-m-d H:i');
            $section->addText($infoText, ['size' => 9, 'italic' => true], ['spaceAfter' => 200]);

            // table style + header (gray palette)
            $tableStyle = [
                'borderSize' => 6,
                'borderColor' => 'CCCCCC',
                'cellMargin' => 80,
            ];
            $phpWord->addTableStyle('PartsTable', $tableStyle);
            $table = $section->addTable('PartsTable');

            // Compute max text lengths for Kategoria and Stan so their column widths match widest text
            // Include header text length ("Kategoria" = 9 chars, "Stan" = 4 chars) so headers don't wrap
            $maxCategoryLen = max(9, collect($parts)->map(function ($p) { return mb_strlen($p->category->name ?? '-', 'UTF-8'); })->max() ?: 1);
            $maxStanLen = max(4, collect($parts)->map(function ($p) { return mb_strlen((string)($p->quantity ?? ''), 'UTF-8'); })->max() ?: 1);
            $maxStanMinLen = max(9, collect($parts)->map(function ($p) { return mb_strlen((string)($p->minimum_stock ?? ''), 'UTF-8'); })->max() ?: 1);

            // Approximate width per character in Word (dxa); use higher multiplier for bold headers with padding
            $charWidth = 220; // increased for bold text + centered alignment + padding
            $categoryWidth = max(900, $maxCategoryLen * $charWidth);
            $stanWidth = max(1100, $maxStanLen * $charWidth); // increased minimum to ensure "Stan" header fits
            $stanMinWidth = max(1300, $maxStanMinLen * $charWidth); // "Stan min." = 9 chars

            // header row (modern gray with white text)
            $table->addRow();
            $cellStyleHeader = ['bgColor' => '4B5563']; // gray-600
            $headerFont = ['bold' => true, 'color' => 'FFFFFF'];

            // Use calculated widths for Kategoria and Stan; keep Opis as-is
            $table->addCell(4500, $cellStyleHeader)->addText('Nazwa', $headerFont);
            $table->addCell(8500, $cellStyleHeader)->addText('Opis', $headerFont);
            $table->addCell($categoryWidth, $cellStyleHeader)->addText('Kategoria', $headerFont, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $table->addCell($stanWidth, $cellStyleHeader)->addText('Stan', $headerFont, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            $table->addCell($stanMinWidth, $cellStyleHeader)->addText('Stan min.', $headerFont, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);

            $rowIndex = 0;
            foreach ($parts as $p) {
                $rowIndex++;
                $table->addRow();
                // alternating subtle gray rows
                $cellStyle = ($rowIndex % 2 === 0) ? ['bgColor' => 'F3F4F6'] : [];

                $table->addCell(4500, $cellStyle)->addText($p->name);
                $table->addCell(8500, $cellStyle)->addText($p->description ?? '-');
                $table->addCell($categoryWidth, $cellStyle)->addText($p->category->name ?? '-', null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $table->addCell($stanWidth, $cellStyle)->addText((string)$p->quantity, null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
                $table->addCell($stanMinWidth, $cellStyle)->addText((string)$p->minimum_stock, null, ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            }

            $temp = tempnam(sys_get_temp_dir(), 'word');
            $file = $temp . '.docx';
            \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007')->save($file);

            return response()->download($file, 'katalog.docx')->deleteFileAfterSend(true);
        } catch (\Throwable $e) {
            return redirect()->back()->with('error', 'Wystąpił błąd podczas generowania dokumentu: ' . $e->getMessage());
        }
    }

    /* ===================== PODGLĄD (AJAX) ===================== */

    // PODGLĄD STANU + OPISU PO NAZWIE
    public function preview(Request $request)
    {
        $part = Part::where('name', $request->name)->first();

        if (!$part) {
            return response()->json([
                'exists' => false,
            ]);
        }

        return response()->json([
            'exists'      => true,
            'quantity'    => $part->quantity,
            'description' => $part->description,
        ]);
    }

    // Szukaj podobnych nazw (do sprawdzenia literówek)
    public function searchSimilar(Request $request)
    {
        $inputName = $request->input('name', '');
        
        if (strlen($inputName) < 2) {
            return response()->json(['similar' => []]);
        }

        // Znajdź wszystkie części i oblicz podobieństwo
        $parts = Part::all();
        $similar = [];

        foreach ($parts as $part) {
            $similarity = $this->stringSimilarity($inputName, $part->name);
            // Jeśli podobieństwo >= 60%, dodaj do listy
            if ($similarity >= 60) {
                $similar[] = [
                    'name' => $part->name,
                    'quantity' => $part->quantity,
                    'description' => $part->description,
                    'similarity' => round($similarity, 0)
                ];
            }
        }

        // Posortuj po podobieństwie (malejąco)
        usort($similar, function($a, $b) {
            return $b['similarity'] - $a['similarity'];
        });

        return response()->json(['similar' => $similar]);
    }

    // Funkcja obliczająca podobieństwo stringów (podobna do Levenshtein)
    private function stringSimilarity($str1, $str2)
    {
        $str1 = strtolower($str1);
        $str2 = strtolower($str2);
        
        $len1 = strlen($str1);
        $len2 = strlen($str2);
        $maxLen = max($len1, $len2);
        
        if ($maxLen === 0) {
            return 100;
        }

        $distance = levenshtein($str1, $str2);
        return (1 - ($distance / $maxLen)) * 100;
    }


    /* ===================== AKCJE ===================== */

    // DODAWANIE
    public function add(Request $request)
    {
        try {
            $data = $request->validate([
                'name'        => 'required|string',
                'description' => 'nullable|string',
                'supplier'    => 'nullable|string',
                'quantity'    => 'required|integer|min:0',
                'minimum_stock' => 'nullable|integer|min:0',
                'location'    => 'nullable|string|max:10',
                'category_id' => 'required|exists:categories,id',
                'net_price'   => 'nullable|numeric|min:0',
                'currency'    => 'nullable|in:PLN,EUR,$',
                'qr_code'     => 'nullable|string',
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Błąd walidacji podczas dodawania produktu', [
                'name' => $request->input('name'),
                'category_id' => $request->input('category_id'),
                'supplier' => $request->input('supplier'),
                'errors' => $e->errors()
            ]);
            
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Błąd walidacji: ' . implode(', ', array_map(fn($err) => implode(', ', $err), $e->errors()))
                ], 422);
            }
            throw $e;
        }

        try {
            // znajdź lub utwórz część
            $part = Part::firstOrCreate(
                ['name' => $data['name']],
                [
                    'category_id' => $data['category_id'],
                    'description' => $data['description'] ?? null,
                    'supplier'    => $data['supplier'] ?? null,
                    'quantity'    => 0,
                    'minimum_stock' => $data['minimum_stock'] ?? 0,
                    'location'    => $data['location'] ?? null,
                    'net_price'   => $data['net_price'] ?? null,
                    'currency'    => $data['currency'] ?? 'PLN',
                    'qr_code'     => $data['qr_code'] ?? null,
                ]
            );

            // Sprawdź czy produkt był już w bazie (wasRecentlyCreated = false oznacza że już istniał)
            $wasExisting = !$part->wasRecentlyCreated;

            // aktualizacja opisu, dostawcy, ceny, waluty, lokalizacji, kodu QR i kategorii (jeśli zmieniony / wpisany)
            if (array_key_exists('description', $data)) {
                $part->description = $data['description'];
            }
            if (array_key_exists('supplier', $data)) {
                $part->supplier = $data['supplier'];
            }
            if (array_key_exists('minimum_stock', $data)) {
                $part->minimum_stock = $data['minimum_stock'];
            }
            if (array_key_exists('location', $data)) {
                $part->location = $data['location'];
            }
            if (array_key_exists('net_price', $data)) {
                $part->net_price = $data['net_price'];
            }
            if (array_key_exists('currency', $data)) {
                $part->currency = $data['currency'];
            }
            if (array_key_exists('category_id', $data)) {
                $part->category_id = $data['category_id'];
            }
            
            // Aktualizuj kod QR TYLKO jeśli został przekazany ORAZ (produkt jest nowy LUB nie ma jeszcze kodu QR)
            if (array_key_exists('qr_code', $data) && $data['qr_code']) {
                // Jeśli produkt już istniał i ma swój kod QR, NIE nadpisuj go
                if (!$wasExisting || !$part->qr_code) {
                    $part->qr_code = $data['qr_code'];
                }
            }
            
            // Automatyczne generowanie kodu QR TYLKO dla nowych produktów bez kodu QR
            if (!$wasExisting && !$part->qr_code) {
                $qrCode = $this->autoGenerateQrCode($data['name'], $data['location'] ?? null);
                if ($qrCode) {
                    $part->qr_code = $qrCode;
                }
            }

            // zwiększenie stanu
            $part->quantity += (int) $data['quantity'];
            
            // przypisanie użytkownika, który dodał/zmodyfikował produkt
            $part->last_modified_by = auth()->id();
            
            $part->save();

            // Pobierz skróconą nazwę dostawcy dla historii
            $supplierDisplay = '';
            if ($part->supplier) {
                $supplier = \App\Models\Supplier::where('name', $part->supplier)->first();
                $supplierDisplay = $supplier && $supplier->short_name ? $supplier->short_name : $part->supplier;
            }
            
            // historia sesji (DODAJ)
            session()->push('adds', [
                'date'        => now()->format('Y-m-d H:i'),
                'name'        => $part->name,
                'description' => $part->description,
                'supplier'    => $supplierDisplay,
                'changed'     => (int) $data['quantity'],
                'after'       => $part->quantity,
                'category'    => $part->category->name ?? '-',
            ]);

            // Sprawdź czy to request AJAX
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => true, 
                    'message' => 'Produkt dodany',
                    'quantity' => $part->quantity
                ]);
            }

            if ($request->input('redirect_to') === 'check') {
                $queryParams = [];
                if ($request->filled('search')) {
                    $queryParams['search'] = $request->input('search');
                }
                if ($request->filled('filter_category_id')) {
                    $queryParams['category_id'] = $request->input('filter_category_id');
                }
                return redirect()->route('magazyn.check', $queryParams);
            }
            return redirect()->route('magazyn.add');
            
        } catch (\Exception $e) {
            \Log::error('Błąd podczas dodawania produktu: ' . $e->getMessage(), [
                'name' => $request->input('name'),
                'category_id' => $request->input('category_id'),
                'supplier' => $request->input('supplier'),
                'quantity' => $request->input('quantity'),
                'location' => $request->input('location'),
                'error_class' => get_class($e),
                'error_code' => $e->getCode(),
                'error_file' => $e->getFile(),
                'error_line' => $e->getLine(),
                'trace' => $e->getTraceAsString()
            ]);
            
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json([
                    'success' => false,
                    'message' => 'Błąd serwera: ' . $e->getMessage(),
                    'error_type' => get_class($e),
                    'product_name' => $request->input('name')
                ], 500);
            }
            
            return redirect()->back()->with('error', 'Błąd podczas dodawania produktu: ' . $e->getMessage());
        }
    }

    // POBIERANIE
    public function remove(Request $request)
    {
        $data = $request->validate([
            'name'     => 'required|string',
            'quantity' => 'required|integer|min:1',
            'project_id' => 'nullable|exists:projects,id',
        ]);

        $part = Part::where('name', $data['name'])->first();

        if (!$part) {
            // Sprawdź czy to request AJAX
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json(['error' => 'Część nie istnieje'], 404);
            }
            return redirect()->back()
                ->with('error', 'Część nie istnieje');
        }

        if ($data['quantity'] > $part->quantity) {
            // Sprawdź czy to request AJAX
            if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
                return response()->json(['error' => 'Za mało części w magazynie'], 422);
            }
            return redirect()->back()
                ->with('error', 'Za mało części w magazynie');
        }

        $removed = (int) $data['quantity'];

        // zmniejszenie stanu
        $part->quantity -= $removed;
        
        // przypisanie użytkownika, który zmodyfikował produkt
        $part->last_modified_by = auth()->id();
        
        $part->save();

        // Zapis do bazy danych
        PartRemoval::create([
            'user_id' => auth()->id(),
            'part_id' => $part->id,
            'part_name' => $part->name,
            'description' => $part->description,
            'quantity' => $removed,
            'price' => $part->price ?? null,
            'currency' => $part->currency ?? 'PLN',
            'stock_after' => $part->quantity,
        ]);

        // Jeśli pobieranie do projektu, zapisz w project_removals
        if ($request->filled('project_id')) {
            \App\Models\ProjectRemoval::create([
                'project_id' => $data['project_id'],
                'part_id' => $part->id,
                'user_id' => auth()->id(),
                'quantity' => $removed,
            ]);
        }

        // Pobierz skróconą nazwę dostawcy dla historii
        $supplierDisplay = '';
        if ($part->supplier) {
            $supplier = \App\Models\Supplier::where('name', $part->supplier)->first();
            $supplierDisplay = $supplier && $supplier->short_name ? $supplier->short_name : $part->supplier;
        }
        
        // historia sesji (POBIERZ) — 🔧 DODANY OPIS
        session()->push('removes', [
            'date'        => now()->format('Y-m-d H:i'),
            'name'        => $part->name,
            'description' => $part->description,
            'supplier'    => $supplierDisplay,
            'changed'     => $removed,
            'after'       => $part->quantity,
        ]);

        // Sprawdź czy to request AJAX
        if ($request->wantsJson() || $request->ajax() || $request->header('Accept') === 'application/json') {
            return response()->json([
                'success' => true, 
                'message' => 'Produkt pobrany',
                'quantity' => $part->quantity
            ]);
        }

        if ($request->input('redirect_to') === 'check') {
            $queryParams = [];
            if ($request->filled('search')) {
                $queryParams['search'] = $request->input('search');
            }
            if ($request->filled('filter_category_id')) {
                $queryParams['category_id'] = $request->input('filter_category_id');
            }
            return redirect()->route('magazyn.check', $queryParams);
        }
        return redirect()->back();
    }

    // USUWANIE CZĘŚCI (❌ z katalogu)
    public function destroy(Part $part)
    {

        // Pozwól superadminowi (is_admin) usuwać nawet jeśli quantity > 0
        $user = auth()->user();
        if ($part->quantity > 0 && (!$user || !$user->is_admin)) {
            return redirect()->back()
                ->with('error', "Nie można usunąć '{$part->name}' — stan wynosi {$part->quantity}. Najpierw zmniejsz stan na 0.");
        }

        $part->delete();

        return redirect()->back()
            ->with('success', 'Część została usunięta z magazynu');
    }

    // MASOWE USUWANIE CZĘŚCI
    public function bulkDelete(Request $request)
    {
        $request->validate([
            'part_ids' => 'required|array',
            'part_ids.*' => 'exists:parts,id',
        ]);

        $user = auth()->user();
        $response = redirect()->back();

        if ($user && $user->is_admin) {
            // Superadmin może usunąć wszystko
            $parts = Part::whereIn('id', $request->part_ids)->get();
            $count = $parts->count();
            if ($count > 0) {
                Part::whereIn('id', $parts->pluck('id'))->delete();
                $names = $parts->pluck('name')->implode(', ');
                $response->with('success', "Usunięto: {$names}");
            }
        } else {
            // Zwykły użytkownik - tylko stan 0
            $removableParts = Part::whereIn('id', $request->part_ids)
                ->where('quantity', 0)
                ->get();

            $unremovableParts = Part::whereIn('id', $request->part_ids)
                ->where('quantity', '>', 0)
                ->get();

            $count = $removableParts->count();
            if ($count > 0) {
                Part::whereIn('id', $removableParts->pluck('id'))->delete();
                $names = $removableParts->pluck('name')->implode(', ');
                $response->with('success', "Usunięto: {$names}");
            }
            if ($unremovableParts->count() > 0) {
                $unremovableCount = $unremovableParts->count();
                $partWord = match($unremovableCount % 10) {
                    1 => 'część',
                    default => 'części'
                };
                $errorMsg = "Nie usunięto {$unremovableCount} {$partWord} – stan nie wynosi zero.";
                $response->with('error', $errorMsg);
            }
        }
        return $response;
    }

    // AKTUALIZACJA CENY PRODUKTU
    public function updatePrice(Request $request, Part $part)
    {
        $request->validate([
            'net_price' => 'nullable|numeric|min:0',
            'currency' => 'required|in:PLN,EUR,$',
        ]);

        $part->net_price = $request->net_price;
        $part->currency = $request->currency;
        $part->last_modified_by = auth()->id();
        $part->save();

        return redirect()->route('magazyn.check')->with('success', "Cena produktu \"{$part->name}\" została zaktualizowana.");
    }

    public function updatePart(Request $request, Part $part)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'description' => 'nullable|string',
            'quantity' => 'required|integer|min:0',
            'minimum_stock' => 'nullable|integer|min:0',
            'location' => 'nullable|string|max:10',
            'net_price' => 'nullable|numeric|min:0',
            'currency' => 'required|in:PLN,EUR,$',
            'supplier' => 'nullable|string|max:255',
        ]);

        $part->update([
            'name' => $request->name,
            'category_id' => $request->category_id,
            'description' => $request->description,
            'quantity' => $request->quantity,
            'minimum_stock' => $request->minimum_stock ?? 0,
            'location' => $request->location,
            'net_price' => $request->net_price,
            'currency' => $request->currency,
            'supplier' => $request->supplier,
            'last_modified_by' => auth()->id(),
        ]);

        return redirect()->route('magazyn.check')->with('success', "Produkt \"{$part->name}\" został zaktualizowany.");
    }

    // AKTUALIZACJA LOKALIZACJI PRODUKTU
    public function updateLocation(Request $request, Part $part)
    {
        $request->validate([
            'location' => 'nullable|string|max:10',
        ]);

        $part->location = $request->location;
        $part->last_modified_by = auth()->id();
        $part->save();

        return response()->json(['success' => true]);
    }

    // DODAWANIE UŻYTKOWNIKA
    public function addUser(Request $request)
    {
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users',
            'phone' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        // Generowanie pełnej nazwy
        $fullName = $request->first_name . ' ' . $request->last_name;

        // Generowanie skróconej nazwy: 3 znaki z imienia + 3 z nazwiska (pierwsze wielkie)
        $shortName = $request->input('short_name');
        if (!$shortName) {
            $firstName = $request->first_name;
            $lastName = $request->last_name;
            
            $firstPart = mb_strlen($firstName) >= 3 
                ? mb_strtoupper(mb_substr($firstName, 0, 1)) . mb_strtolower(mb_substr($firstName, 1, 2))
                : mb_strtoupper(mb_substr($firstName, 0, 1)) . mb_strtolower(mb_substr($firstName, 1));
            
            $lastPart = mb_strlen($lastName) >= 3 
                ? mb_strtoupper(mb_substr($lastName, 0, 1)) . mb_strtolower(mb_substr($lastName, 1, 2))
                : mb_strtoupper(mb_substr($lastName, 0, 1)) . mb_strtolower(mb_substr($lastName, 1));
            
            $shortName = $firstPart . $lastPart;
        }

        User::create([
            'name' => $fullName,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'short_name' => $shortName,
            'email' => $request->email,
            'phone' => $request->phone,
            'password' => $request->password ? Hash::make($request->password) : Hash::make(Str::random(32)),
            'can_view_catalog' => true, // Domyślnie dostęp do katalogu
            'created_by' => auth()->id(), // Zapisz ID twórcy
        ]);

        // Wyczyść stare wartości z sesji
        $request->session()->forget('_old_input');

        return redirect()->route('magazyn.settings')->with('success', "Użytkownik \"{$fullName}\" został dodany.");
    }

    // USUWANIE UŻYTKOWNIKA
    public function deleteUser(User $user)
    {
        // Nie pozwalaj usunąć głównego admina (proximalumine@gmail.com)
        if ($user->email === 'proximalumine@gmail.com') {
            return redirect()->route('magazyn.settings')->with('error', 'Nie można usunąć głównego konta Admin!');
        }

        // Jeśli użytkownik jest adminem, sprawdź czy zalogowany to główny admin
        if ($user->is_admin && auth()->user()->email !== 'proximalumine@gmail.com') {
            return redirect()->route('magazyn.settings')->with('error', 'Tylko główny administrator może usuwać konta administratorów!');
        }

        $name = $user->name;
        $user->delete();

        return redirect()->route('magazyn.settings')->with('success', "Użytkownik \"{$name}\" został usunięty.");
    }

    // EDYCJA UŻYTKOWNIKA - WIDOK
    public function editUserView(User $user)
    {
        // Zwykły użytkownik (nie admin) nie może edytować admina
        if (!auth()->user()->is_admin && $user->is_admin) {
            return redirect()->route('magazyn.settings')->with('error', 'Nie masz uprawnień do edycji konta administratora.');
        }
        
        return view('parts.user-edit', [
            'user' => $user,
        ]);
    }

    // EDYCJA UŻYTKOWNIKA - AKTUALIZACJA
    public function updateUser(Request $request, User $user)
    {
        // Zwykły użytkownik (nie admin) nie może edytować admina
        if (!auth()->user()->is_admin && $user->is_admin) {
            return redirect()->route('magazyn.settings')->with('error', 'Nie masz uprawnień do edycji konta administratora.');
        }
        
        $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'phone' => 'nullable|string',
            'password' => 'nullable|string',
        ]);

        // Generowanie pełnej nazwy
        $fullName = $request->first_name . ' ' . $request->last_name;

        // Generowanie skróconej nazwy: 3 znaki z imienia + 3 z nazwiska (pierwsze wielkie)
        $shortName = $request->input('short_name');
        if (!$shortName) {
            $firstName = $request->first_name;
            $lastName = $request->last_name;
            
            $firstPart = mb_strlen($firstName) >= 3 
                ? mb_strtoupper(mb_substr($firstName, 0, 1)) . mb_strtolower(mb_substr($firstName, 1, 2))
                : mb_strtoupper(mb_substr($firstName, 0, 1)) . mb_strtolower(mb_substr($firstName, 1));
            
            $lastPart = mb_strlen($lastName) >= 3 
                ? mb_strtoupper(mb_substr($lastName, 0, 1)) . mb_strtolower(mb_substr($lastName, 1, 2))
                : mb_strtoupper(mb_substr($lastName, 0, 1)) . mb_strtolower(mb_substr($lastName, 1));
            
            $shortName = $firstPart . $lastPart;
        }

        // Zaktualizuj nazwę, email i telefon
        $user->name = $fullName;
        $user->first_name = $request->first_name;
        $user->last_name = $request->last_name;
        $user->short_name = $shortName;
        $user->email = $request->email;
        $user->phone = $request->phone;

        // Zaktualizuj hasło jeśli zostało podane
        if ($request->filled('password')) {
            $user->password = Hash::make($request->password);
        }

        // Sprawdzenie uprawnień do nadawania dostępów
        $isSuperAdmin = auth()->user()->email === 'proximalumine@gmail.com';
        $isAdmin = auth()->user()->is_admin;
        
        // Superadmin i Admin z odpowiednimi uprawnieniami mogą nadawać uprawnienia
        $canEditMagazyn = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_magazyn);
        $canEditOffers = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_offers);
        $canEditRecipes = $isSuperAdmin || ($isAdmin && auth()->user()->can_view_recipes);
        $canEditCrm = $isSuperAdmin || ($isAdmin && auth()->user()->can_crm);

        // Zaktualizuj uprawnienia (konwertuj na int dla boolean kolumn)
        // Tylko jeśli mają uprawnienia do edycji danej sekcji
        if ($canEditMagazyn) {
            $user->can_view_magazyn = (int) $request->has('can_view_magazyn');
        }
        
        if ($canEditOffers) {
            $user->can_view_offers = (int) $request->has('can_view_offers');
        }
        
        if ($canEditRecipes) {
            $user->can_view_recipes = (int) $request->has('can_view_recipes');
        }
        
        if ($canEditCrm) {
            $user->can_crm = (int) $request->has('can_crm');
        }
        
        $user->can_view_catalog = (int) $request->has('can_view_catalog');
        $user->can_add = (int) $request->has('can_add');
        $user->can_remove = (int) $request->has('can_remove');
        $user->can_orders = (int) $request->has('can_orders');
        $user->can_settings = (int) $request->has('can_settings');
        $user->can_settings_categories = (int) $request->has('can_settings_categories');
        $user->can_settings_suppliers = (int) $request->has('can_settings_suppliers');
        $user->can_settings_company = (int) $request->has('can_settings_company');
        $user->can_settings_users = (int) $request->has('can_settings_users');
        $user->can_settings_export = (int) $request->has('can_settings_export');
        $user->can_settings_other = (int) $request->has('can_settings_other');
        $user->can_delete_orders = (int) $request->has('can_delete_orders');
        $user->show_action_column = (int) $request->has('show_action_column');

        $user->save();

        return redirect()->route('magazyn.settings')->with('success', "Użytkownik \"{$user->name}\" został zaktualizowany.");
    }

    // MIANOWANIE UŻYTKOWNIKA NA ADMINA
    public function toggleAdmin(User $user)
    {
        // Tylko admin może mianować innych na admina
        if (!auth()->user()->is_admin) {
            return redirect()->route('magazyn.settings')->with('error', 'Nie masz uprawnień do mianowania użytkowników na admina.');
        }

        // Nie można zmienić statusu admina superadmina (proximalumine@gmail.com)
        if ($user->email === 'proximalumine@gmail.com') {
            return redirect()->route('magazyn.settings')->with('error', 'Nie można zmienić statusu superadmina.');
        }

        // Przełącz status admina
        if ($user->is_admin) {
            $user->is_admin = 0;
            $user->save();
            return redirect()->route('magazyn.settings')->with('success', "Użytkownik \"{$user->name}\" został zdegradowany do zwykłego użytkownika.");
        } else {
            $user->is_admin = 1;
            $user->save();
            return redirect()->route('magazyn.settings')->with('success', "Użytkownik \"{$user->name}\" został mianowany adminem.");
        }
    }

    // DODAJ DOSTAWCĘ
    public function addSupplier(Request $request)
    {
        // Usuń myślniki z NIP przed walidacją
        $nipClean = null;
        $nipFormatted = null;
        if ($request->has('nip') && $request->nip) {
            $nipClean = str_replace('-', '', $request->nip);
            
            // Sformatuj NIP z myślnikami
            if (strlen($nipClean) === 10) {
                $nipFormatted = substr($nipClean, 0, 3) . '-' . 
                                substr($nipClean, 3, 3) . '-' . 
                                substr($nipClean, 6, 2) . '-' . 
                                substr($nipClean, 8, 2);
                
                // Sprawdź czy NIP z myślnikami już istnieje w bazie
                $existingSupplier = \App\Models\Supplier::where('nip', $nipFormatted)->first();
                if ($existingSupplier) {
                    return redirect()->back()
                        ->withErrors(['nip' => 'Dostawca o podanym NIP-ie już istnieje w bazie danych.'])
                        ->withInput();
                }
            }
            
            $request->merge(['nip' => $nipClean]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'nip' => 'nullable|digits:10',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_supplier' => 'nullable|boolean',
            'is_client' => 'nullable|boolean',
        ]);

        // Użyj sformatowanego NIP-u
        if ($nipFormatted) {
            $validated['nip'] = $nipFormatted;
        }
        
        // Ustaw domyślne wartości dla checkboxów jeśli nie zostały zaznaczone
        $validated['is_supplier'] = $request->has('is_supplier') ? 1 : 0;
        $validated['is_client'] = $request->has('is_client') ? 1 : 0;

        // Obsługa uploadu loga
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoBase64 = base64_encode(file_get_contents($logoFile->getRealPath()));
            $mimeType = $logoFile->getMimeType();
            $validated['logo'] = 'data:' . $mimeType . ';base64,' . $logoBase64;
        }

        $supplier = \App\Models\Supplier::create($validated);

        return redirect()->route('magazyn.settings')->with('success', 'Dostawca "' . $supplier->name . '" został dodany.');
    }

    // USUŃ DOSTAWCĘ
    public function deleteSupplier(\App\Models\Supplier $supplier)
    {
        $name = $supplier->name;
        $supplier->delete();

        return redirect()->route('magazyn.settings')->with('success', "Dostawca \"{$name}\" został usunięty.");
    }

    // EDYTUJ DOSTAWCĘ
    public function updateSupplier(Request $request, \App\Models\Supplier $supplier)
    {
        // Usuń myślniki z NIP przed walidacją
        $nipClean = null;
        $nipFormatted = null;
        if ($request->has('nip') && $request->nip) {
            $nipClean = str_replace('-', '', $request->nip);
            
            // Sformatuj NIP z myślnikami
            if (strlen($nipClean) === 10) {
                $nipFormatted = substr($nipClean, 0, 3) . '-' . 
                                substr($nipClean, 3, 3) . '-' . 
                                substr($nipClean, 6, 2) . '-' . 
                                substr($nipClean, 8, 2);
                
                // Sprawdź czy NIP z myślnikami już istnieje w bazie (u innego dostawcy)
                $existingSupplier = \App\Models\Supplier::where('nip', $nipFormatted)
                    ->where('id', '!=', $supplier->id)
                    ->first();
                if ($existingSupplier) {
                    return redirect()->back()
                        ->withErrors(['nip' => 'Inny dostawca o podanym NIP-ie już istnieje w bazie danych.'])
                        ->withInput();
                }
            }
            
            $request->merge(['nip' => $nipClean]);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'short_name' => 'nullable|string|max:100',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'nip' => 'nullable|digits:10',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:10',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'is_supplier' => 'nullable|boolean',
            'is_client' => 'nullable|boolean',
        ]);

        // Użyj sformatowanego NIP-u
        if ($nipFormatted) {
            $validated['nip'] = $nipFormatted;
        } elseif (empty($request->nip)) {
            $validated['nip'] = null;
        }
        
        // Ustaw domyślne wartości dla checkboxów jeśli nie zostały zaznaczone
        $validated['is_supplier'] = $request->has('is_supplier') ? 1 : 0;
        $validated['is_client'] = $request->has('is_client') ? 1 : 0;

        // Obsługa uploadu loga
        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoBase64 = base64_encode(file_get_contents($logoFile->getRealPath()));
            $mimeType = $logoFile->getMimeType();
            $validated['logo'] = 'data:' . $mimeType . ';base64,' . $logoBase64;
        } else {
            // Zachowaj obecne logo jeśli nie przesłano nowego
            unset($validated['logo']);
        }

        $supplier->update($validated);

        return redirect()->route('magazyn.settings')->with('success', 'Dostawca "' . $supplier->name . '" został zaktualizowany.');
    }

    // POBIERZ DANE MOJEJ FIRMY PO NIP
    public function fetchCompanyByNip(Request $request)
    {
        $nip = $request->get('nip');
        if (!$nip || strlen($nip) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Nieprawidłowy NIP'
            ]);
        }

        $formatCompanyName = function($name) {
            $name = str_replace('SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ', 'SP. Z O. O.', $name);
            $name = str_replace('SPÓŁKA AKCYJNA', 'S.A.', $name);
            return $name;
        };
        $formatNip = function($nip) {
            if (strlen($nip) === 10) {
                return substr($nip, 0, 3) . '-' . substr($nip, 3, 3) . '-' . substr($nip, 6, 2) . '-' . substr($nip, 8, 2);
            }
            return $nip;
        };

        try {
            // API CEIDG
            $url = "https://dane.biznes.gov.pl/api/ceidg/v2/firmy?nip={$nip}&status=aktywny";
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0'
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            \Log::info('CEIDG API Response (Company)', ['code' => $httpCode, 'response' => $response, 'error' => $curlError]);
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['firmy']) && isset($data['firmy'][0])) {
                    $company = $data['firmy'][0];
                    $address = trim(
                        ($company['adres']['ulica'] ?? '') . ' ' .
                        ($company['adres']['nrNieruchomosci'] ?? '') .
                        (isset($company['adres']['nrLokalu']) ? '/' . $company['adres']['nrLokalu'] : '')
                    );
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name' => $formatCompanyName($company['nazwa'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => trim($address),
                            'city' => $company['adres']['miejscowosc'] ?? '',
                            'postal_code' => $company['adres']['kodPocztowy'] ?? '',
                        ],
                        'message' => 'Dane pobrane z CEIDG'
                    ]);
                }
            }
            // API białej listy VAT
            $url = "https://wl-api.mf.gov.pl/api/search/nip/{$nip}?date=" . date('Y-m-d');
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0'
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            \Log::info('MF VAT API Response (Company)', ['code' => $httpCode, 'response' => $response, 'error' => $curlError]);
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                if (!empty($data['result']['subject'])) {
                    $subject = $data['result']['subject'];
                    $address = $subject['workingAddress'] ?? ($subject['residenceAddress'] ?? '');
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name' => $formatCompanyName($subject['name'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => $address,
                            'city' => '',
                            'postal_code' => '',
                        ],
                        'message' => 'Dane pobrane z MF VAT API'
                    ]);
                }
            }
        } catch (\Exception $e) {
            \Log::error('Błąd pobierania danych firmy po NIP: ' . $e->getMessage());
        }
        return response()->json([
            'success' => false,
            'message' => 'Nie znaleziono danych dla podanego NIP'
        ]);
    }

    // POBIERZ DANE DOSTAWCY PO NIP
    public function fetchSupplierByNip(Request $request)
    {
        $nip = $request->get('nip');
        
        if (!$nip || strlen($nip) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Nieprawidłowy NIP'
            ]);
        }

        // Funkcja formatująca nazwę firmy
        $formatCompanyName = function($name) {
            $name = str_replace('SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ', 'SP. Z O. O.', $name);
            $name = str_replace('SPÓŁKA AKCYJNA', 'S.A.', $name);
            return $name;
        };

        // Funkcja formatująca NIP (xxx-xxx-xx-xx)
        $formatNip = function($nip) {
            if (strlen($nip) === 10) {
                return substr($nip, 0, 3) . '-' . substr($nip, 3, 3) . '-' . substr($nip, 6, 2) . '-' . substr($nip, 8, 2);
            }
            return $nip;
        };

        try {
            // Próba 1: API CEIDG - zwraca telefon i email (dla JDG)
            $url = "https://dane.biznes.gov.pl/api/ceidg/v2/firmy?nip={$nip}&status=aktywny";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            \Log::info('CEIDG API Response', ['code' => $httpCode, 'response' => $response, 'error' => $curlError]);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (!empty($data['firmy']) && isset($data['firmy'][0])) {
                    $company = $data['firmy'][0];
                    
                    // Budowanie adresu
                    $address = trim(
                        ($company['adres']['ulica'] ?? '') . ' ' . 
                        ($company['adres']['nrNieruchomosci'] ?? '') . 
                        (isset($company['adres']['nrLokalu']) ? '/' . $company['adres']['nrLokalu'] : '')
                    );
                    
                    // Pobierz telefon i email
                    $phone = '';
                    $email = '';
                    
                    if (!empty($company['telefony'])) {
                        $phone = is_array($company['telefony']) ? $company['telefony'][0] : $company['telefony'];
                    }
                    
                    if (!empty($company['adresy_email'])) {
                        $email = is_array($company['adresy_email']) ? $company['adresy_email'][0] : $company['adresy_email'];
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name' => $formatCompanyName($company['nazwa'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => trim($address),
                            'city' => $company['adres']['miejscowosc'] ?? '',
                            'postal_code' => $company['adres']['kodPocztowy'] ?? '',
                            'phone' => $phone,
                            'email' => $email,
                        ],
                        'message' => 'Dane pobrane z CEIDG (z telefonem i emailem)'
                    ]);
                }
            }
            
            // Próba 2: API białej listy VAT (dla wszystkich firm, ale bez telefonu/emaila)
            $url = "https://wl-api.mf.gov.pl/api/search/nip/{$nip}?date=" . date('Y-m-d');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                \Log::info('Biała lista VAT Response', ['data' => $data]);
                
                if (isset($data['result']['subject'])) {
                    $subject = $data['result']['subject'];
                    $address = '';
                    $city = '';
                    $postalCode = '';
                    
                    // Parsowanie adresu - API zwraca go jako string "ULICA NR, KOD MIASTO"
                    $addressString = $subject['workingAddress'] ?? $subject['residenceAddress'] ?? '';
                    
                    if ($addressString) {
                        // Format: "TARNOGÓRSKA 9, 42-677 SZAŁSZA"
                        $parts = explode(',', $addressString, 2);
                        $address = trim($parts[0] ?? ''); // "TARNOGÓRSKA 9"
                        
                        if (isset($parts[1])) {
                            // "42-677 SZAŁSZA"
                            $cityPart = trim($parts[1]);
                            if (preg_match('/^(\d{2}-\d{3})\s+(.+)$/', $cityPart, $matches)) {
                                $postalCode = $matches[1]; // "42-677"
                                $city = $matches[2];       // "SZAŁSZA"
                            } else {
                                $city = $cityPart;
                            }
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'data' => [
                            'name' => $formatCompanyName($subject['name'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => $address,
                            'city' => $city,
                            'postal_code' => $postalCode,
                            'phone' => '',
                            'email' => '',
                        ],
                        'message' => 'Dane pobrane z białej listy VAT (bez telefonu i emaila)'
                    ]);
                }
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Nie znaleziono firmy o podanym NIP. Sprawdź NIP lub dodaj dane ręcznie.'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas pobierania danych: ' . $e->getMessage()
            ]);
        }
    }

    // CZYSZCZENIE HISTORII SESJI
    public function clearSession(Request $request)
    {
        $type = $request->input('type', 'adds');
        
        if ($type === 'removes') {
            session()->forget('removes');
            $message = 'Historia pobrań została wyczyszczona.';
        } else {
            session()->forget('adds');
            $message = 'Historia dodań została wyczyszczona.';
        }
        
        return redirect()->back()->with('success', $message);
    }

    public function deleteSelectedHistory(Request $request)
    {
        $indices = $request->input('indices', []);
        
        if (empty($indices)) {
            return response()->json(['success' => false, 'message' => 'Brak zaznaczonych pozycji']);
        }
        
        $sessionRemoves = session()->get('removes', []);
        
        // Usuń zaznaczone pozycje (w odwrotnej kolejności, żeby indeksy się nie zmieniały)
        $indices = array_map('intval', $indices);
        rsort($indices);
        
        foreach ($indices as $index) {
            if (isset($sessionRemoves[$index])) {
                unset($sessionRemoves[$index]);
            }
        }
        
        // Reindeksuj tablicę
        $sessionRemoves = array_values($sessionRemoves);
        
        session()->put('removes', $sessionRemoves);
        
        return response()->json(['success' => true, 'message' => 'Usunięto zaznaczone pozycje']);
    }

    // ZAPIS DANYCH FIRMY
    public function saveCompanySettings(Request $request)
    {
        $request->validate([
            'name' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:255',
            'postal_code' => 'nullable|string|max:20',
            'nip' => 'nullable|string|max:20',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
            'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
        ]);

        $companySetting = \App\Models\CompanySetting::firstOrNew(['id' => 1]);
        
        $companySetting->name = $request->name;
        $companySetting->address = $request->address;
        $companySetting->city = $request->city;
        $companySetting->postal_code = $request->postal_code;
        $companySetting->nip = $request->nip;
        $companySetting->phone = $request->phone;
        $companySetting->email = $request->email;

        if ($request->hasFile('logo')) {
            $logoFile = $request->file('logo');
            $logoBase64 = base64_encode(file_get_contents($logoFile->getRealPath()));
            $mimeType = $logoFile->getMimeType();
            $companySetting->logo = 'data:' . $mimeType . ';base64,' . $logoBase64;
        }

        $companySetting->save();

        return redirect()->route('magazyn.settings')->with('success', 'Dane firmy zostały zapisane.');
    }

    // ZAPIS USTAWIEŃ ZAMÓWIEŃ
    public function saveOrderSettings(Request $request)
    {
        $validated = $request->validate([
            'element1_type' => 'nullable|string|max:50',
            'element1_value' => 'nullable|string|max:255',
            'separator1' => 'nullable|string|max:5',
            'element2_type' => 'nullable|string|max:50',
            'element2_value' => 'nullable|string|max:255',
            'separator2' => 'nullable|string|max:5',
            'element3_type' => 'nullable|string|max:50',
            'element3_value' => 'nullable|string|max:255',
            'element3_digits' => 'nullable|integer|min:1|max:5',
            'start_number' => 'nullable|integer|min:0',
            'separator3' => 'nullable|string|max:5',
            'element4_type' => 'nullable|string|max:50',
            'element4_value' => 'nullable|string|max:255',
            'separator4' => 'nullable|string|max:5',
        ]);

        // Usuń wszystkie poprzednie ustawienia i stwórz nowe (zawsze tylko 1 rekord)
        \DB::table('order_settings')->truncate();
        \DB::table('order_settings')->insert($validated);

        return redirect()->route('magazyn.settings')->with('success', 'Konfiguracja zamówień została zapisana.');
    }

    // ZAPISZ USTAWIENIA KODÓW QR
    public function saveQrSettings(Request $request)
    {
        $validated = $request->validate([
            'element1_type' => 'nullable|string|max:50',
            'element1_value' => 'nullable|string|max:255',
            'separator1' => 'nullable|string|max:5',
            'element2_type' => 'nullable|string|max:50',
            'element2_value' => 'nullable|string|max:255',
            'separator2' => 'nullable|string|max:5',
            'element3_type' => 'nullable|string|max:50',
            'element3_value' => 'nullable|string|max:255',
            'separator3' => 'nullable|string|max:5',
            'element4_type' => 'nullable|string|max:50',
            'start_number' => 'nullable|integer|min:1',
        ]);

        // Usuń wszystkie poprzednie ustawienia i stwórz nowe (zawsze tylko 1 rekord)
        \DB::table('qr_settings')->truncate();
        \DB::table('qr_settings')->insert($validated);

        return redirect()->route('magazyn.settings')->with('success', 'Konfiguracja kodów QR została zapisana.');
    }

    // UTWÓRZ ZAMÓWIENIE - ZAPISZ DO BAZY DANYCH
    public function createOrder(Request $request)
    {
        $request->validate([
            'order_name' => 'required|string|max:255',
            'products' => 'required|array|min:1',
            'products.*.name' => 'required|string',
            'products.*.supplier' => 'nullable|string',
            'products.*.quantity' => 'required|integer|min:1',
            'supplier' => 'nullable|string',
            'supplier_offer_number' => 'nullable|string',
            'payment_method' => 'nullable|string',
            'payment_days' => 'nullable|string',
            'delivery_time' => 'nullable|string',
            'increment_counter' => 'nullable|boolean',
        ]);

        $orderNameTemplate = $request->input('order_name');
        $products = $request->input('products');
        
        // Grupuj produkty według dostawców
        $productsBySupplier = [];
        foreach ($products as $product) {
            $supplierName = $product['supplier'] ?? '';
            if (!isset($productsBySupplier[$supplierName])) {
                $productsBySupplier[$supplierName] = [];
            }
            $productsBySupplier[$supplierName][] = $product;
        }
        
        $createdOrders = [];
        $shouldIncrement = $request->input('increment_counter', true);
        
        // Pobierz ustawienia zamówień RAZ, na zewnątrz pętli
        $orderSettings = \DB::table('order_settings')->first();
        $hasNumberElement = false;
        if ($orderSettings) {
            $hasNumberElement = ($orderSettings->element1_type ?? '') === 'number' 
                || ($orderSettings->element2_type ?? '') === 'number'
                || ($orderSettings->element3_type ?? '') === 'number'
                || ($orderSettings->element4_type ?? '') === 'number';
        }
        
        // Dla każdego dostawcy utwórz osobne zamówienie
        foreach ($productsBySupplier as $supplierName => $supplierProducts) {
            // Generuj rzeczywistą nazwę zamówienia
            $orderName = $this->generateRealOrderName($orderNameTemplate, $supplierName);
            
            // Zapisz zamówienie w bazie danych
            $order = \App\Models\Order::create([
                'order_number' => $orderName,
                'supplier' => empty($supplierName) ? null : $supplierName,
                'products' => $supplierProducts,
                'supplier_offer_number' => $request->input('supplier_offer_number'),
                'payment_method' => $request->input('payment_method'),
                'payment_days' => $request->input('payment_days'),
                'delivery_time' => $request->input('delivery_time'),
                'issued_at' => now(),
                'user_id' => auth()->id(),
            ]);
            
            // Zwiększ numer zamówienia ZARAZ PO zapisaniu (przed następną iteracją)
            if ($shouldIncrement && $orderSettings && $hasNumberElement) {
                \DB::table('order_settings')->update([
                    'start_number' => ($orderSettings->start_number ?? 1) + 1
                ]);
                // Odśwież wartość dla następnej iteracji
                $orderSettings = \DB::table('order_settings')->first();
                // Zaktualizuj też template dla następnej iteracji
                $orderNameTemplate = $this->generateOrderNamePreview($orderSettings, '');
            }
            
            $createdOrders[] = [
                'id' => $order->id,
                'order_number' => $order->order_number,
                'supplier' => $order->supplier,
                'issued_at' => $order->issued_at->format('Y-m-d H:i:s'),
                'products' => $order->products,
                'delivery_time' => $order->delivery_time,
                'supplier_offer_number' => $order->supplier_offer_number,
                'payment_method' => $order->payment_method,
                'payment_days' => $order->payment_days,
            ];
        }
        
        return response()->json([
            'success' => true,
            'message' => count($createdOrders) === 1 
                ? 'Zamówienie zostało utworzone' 
                : 'Utworzono ' . count($createdOrders) . ' zamówienia dla różnych dostawców',
            'orders' => $createdOrders
        ]);
    }
    
    // GENERUJ DOKUMENT WORD DLA ZAMÓWIENIA
    public function generateOrderWord($orderId)
    {
        $order = \App\Models\Order::findOrFail($orderId);
        
        $orderName = $order->order_number;
        $supplierName = $order->supplier;
        $products = $order->products;

        // Tworzenie dokumentu Word
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Dodaj sekcję
        $section = $phpWord->addSection();
        
        // Pobierz dane firmy z bazy danych
        $companySettings = \App\Models\CompanySetting::first();
        
        // Pobierz dane dostawcy z bazy danych
        $supplier = null;
        if (!empty($supplierName)) {
            $supplier = \App\Models\Supplier::where('name', $supplierName)->first();
        }
        
        // Tablica na pliki tymczasowe do usunięcia na końcu
        $tempFilesToDelete = [];
        
        // HEADER - tylko dane Mojej Firmy
        $header = $section->addHeader();
        $headerTable = $header->addTable(['cellMargin' => 40]);
        $headerTable->addRow();
        
        // Logo firmy - obsługa base64 data URI
        if ($companySettings && $companySettings->logo) {
            try {
                // Jeśli logo to data URI (base64)
                if (strpos($companySettings->logo, 'data:image') === 0) {
                    // Wyciągnij dane base64
                    $imageData = explode(',', $companySettings->logo);
                    if (count($imageData) === 2) {
                        $base64Data = $imageData[1];
                        $imageContent = base64_decode($base64Data);
                        
                        // Określ rozszerzenie na podstawie typu MIME
                        $extension = '.png';
                        if (strpos($companySettings->logo, 'data:image/jpeg') === 0) {
                            $extension = '.jpg';
                        } elseif (strpos($companySettings->logo, 'data:image/gif') === 0) {
                            $extension = '.gif';
                        }
                        
                        // Zapisz do tymczasowego pliku
                        $tempLogoPath = tempnam(sys_get_temp_dir(), 'logo_') . $extension;
                        file_put_contents($tempLogoPath, $imageContent);
                        $tempFilesToDelete[] = $tempLogoPath;
                        
                        $headerTable->addCell(2000, ['valign' => 'center', 'borderRightSize' => 0])->addImage($tempLogoPath, [
                            'height' => 34,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                            'marginTop' => 6,
                            'marginRight' => 200,
                        ]);
                    } else {
                        $headerTable->addCell(2000, ['valign' => 'center']);
                    }
                } else {
                    // Jeśli to ścieżka do pliku
                    $logoPath = storage_path('app/public/' . $companySettings->logo);
                    if (file_exists($logoPath)) {
                        $headerTable->addCell(2000, ['valign' => 'center', 'borderRightSize' => 0])->addImage($logoPath, [
                            'height' => 34,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                            'marginTop' => 6,
                            'marginRight' => 200,
                        ]);
                    } else {
                        $headerTable->addCell(2000, ['valign' => 'center']);
                    }
                }
            } catch (\Exception $e) {
                $headerTable->addCell(2000, ['valign' => 'center']);
            }
        } else {
            $headerTable->addCell(2000, ['valign' => 'center']);
        }

        $companyCell = $headerTable->addCell(8000, ['valign' => 'center']);
        
        $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
        $companyAddress = $companySettings && $companySettings->address && $companySettings->city 
            ? ('Ul. ' . $companySettings->address . ', ' . ($companySettings->postal_code ? $companySettings->postal_code . ' ' : '') . $companySettings->city)
            : 'ul. Słoneczna, 40-100 Warszawa';
        $companyEmail = $companySettings && $companySettings->email ? $companySettings->email : 'test@example.com';
        
        $companyCell->addText($companyName, ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
        $companyCell->addText($companyAddress, ['size' => 9], ['spaceAfter' => 0]);
        $companyCell->addLink('mailto:' . $companyEmail, $companyEmail, ['size' => 9, 'color' => '4B5563'], ['spaceAfter' => 0]);

        // FOOTER - Stopka z informacją o zakazie kopiowania
        $footer = $section->addFooter();
        $footer->addText(
            'Dokumentu nie wolno kopiować ani rozpowszechniać bez zgody ' . $companyName,
            ['size' => 8, 'italic' => true, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        
        // Data z miejscowością w prawym górnym rogu
        $companyCity = $companySettings && $companySettings->city ? $companySettings->city : 'Warszawa';
        $dateText = $companyCity . ', ' . now()->format('d.m.Y');
        $section->addText(
            $dateText,
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]
        );
        
        // BODY - Dane dostawcy po prawej stronie
        // Przerwa przed danymi dostawcy (1 linijka)
        $section->addTextBreak(1);
        
        $mainTable = $section->addTable(['cellMargin' => 40]);
        $mainTable->addRow();
        
        // Pusta komórka po lewej dla wyrównania do prawej
        $mainTable->addCell(5000, ['valign' => 'top']);
        
        // Dane dostawcy
        $supplierDataCell = $mainTable->addCell(5200, ['valign' => 'top']);
        
        if ($supplier) {
            if ($supplier->name) {
                $supplierDataCell->addText($supplier->name, ['bold' => true, 'size' => 10], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            if ($supplier->nip) {
                $supplierDataCell->addText('NIP: ' . $supplier->nip, ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            if ($supplier->address) {
                $supplierDataCell->addText('Ul. ' . $supplier->address, ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            if ($supplier->postal_code || $supplier->city) {
                $cityLine = trim(($supplier->postal_code ?? '') . ' ' . ($supplier->city ?? ''));
                $supplierDataCell->addText($cityLine, ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            if ($supplier->email) {
                $supplierDataCell->addLink('mailto:' . $supplier->email, $supplier->email, ['size' => 9, 'color' => '4B5563'], ['spaceAfter' => 100, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            // Logo dostawcy poniżej (jeśli jest) - maksymalnie do prawej
            if ($supplier->logo) {
                try {
                    // Jeśli logo to data URI (base64)
                    if (strpos($supplier->logo, 'data:image') === 0) {
                        // Wyciągnij dane base64
                        $imageData = explode(',', $supplier->logo);
                        if (count($imageData) === 2) {
                            $base64Data = $imageData[1];
                            $imageContent = base64_decode($base64Data);
                            
                            // Określ rozszerzenie na podstawie typu MIME
                            $extension = '.png';
                            if (strpos($supplier->logo, 'data:image/jpeg') === 0) {
                                $extension = '.jpg';
                            } elseif (strpos($supplier->logo, 'data:image/gif') === 0) {
                                $extension = '.gif';
                            }
                            
                            // Zapisz do tymczasowego pliku
                            $tempSupplierLogoPath = tempnam(sys_get_temp_dir(), 'supplier_logo_') . $extension;
                            file_put_contents($tempSupplierLogoPath, $imageContent);
                            $tempFilesToDelete[] = $tempSupplierLogoPath;
                            
                            $supplierDataCell->addImage($tempSupplierLogoPath, [
                                'height' => 40,
                                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
                                'wrappingStyle' => 'inline'
                            ]);
                        }
                    } else {
                        // Jeśli to ścieżka do pliku
                        $supplierLogoPath = storage_path('app/public/' . $supplier->logo);
                        if (file_exists($supplierLogoPath)) {
                            $supplierDataCell->addImage($supplierLogoPath, [
                                'height' => 40,
                                'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT,
                                'wrappingStyle' => 'inline'
                            ]);
                        }
                    }
                } catch (\Exception $e) {
                    // Logo nie załadowane
                }
            }
        } else {
            // Miejsce na dostawcę do wpisania ręcznie
            $supplierDataCell->addText('Dostawca: _______________________', ['size' => 10], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $supplierDataCell->addText('NIP: _______________________', ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $supplierDataCell->addText('Adres: _______________________', ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $supplierDataCell->addText('_______________________', ['size' => 9], ['spaceAfter' => 0, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            $supplierDataCell->addText('Email: _______________________', ['size' => 9], ['spaceAfter' => 100, 'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        }
        
        $section->addTextBreak(1);
        
        // Zamówienie wycentrowane poniżej
        $section->addText(
            'Zamówienie: ' . $orderName,
            ['bold' => true, 'size' => 14],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
        );
        
        // Oblicz maksymalną długość dla kolumny Ilość
        $maxQuantityLen = max(5, collect($products)->map(function ($p) { 
            return mb_strlen((string)($p['quantity'] ?? ''), 'UTF-8'); 
        })->max() ?: 1);
        $quantityWidth = max(800, $maxQuantityLen * 200);
        
        // Tabela z produktami - wyśrodkowana
        $table = $section->addTable([
            'borderSize' => 6,
            'borderColor' => 'CCCCCC',
            'cellMargin' => 40,
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        
        // Nagłówek tabeli - szary 200
        $table->addRow();
        $cellStyleHeader = ['bgColor' => 'E0E0E0', 'valign' => 'center'];
        $table->addCell(3500, $cellStyleHeader)->addText('Produkt', ['bold' => true, 'size' => 9]);
        $table->addCell(2000, $cellStyleHeader)->addText('Dostawca', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $table->addCell($quantityWidth, $cellStyleHeader)->addText('Ilość', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
        $table->addCell(1500, $cellStyleHeader)->addText('Cena netto', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        
        // Wiersze z produktami - co drugi szary 100
        $rowIndex = 0;
        foreach ($products as $product) {
            $rowIndex++;
            $table->addRow();
            
            // Co drugi wiersz szary
            $cellStyle = ($rowIndex % 2 === 0) ? ['bgColor' => 'F5F5F5', 'valign' => 'center'] : ['valign' => 'center'];
            
            $table->addCell(3500, $cellStyle)->addText($product['name'], ['size' => 9]);
            
            // Pobierz skróconą nazwę dostawcy z bazy danych
            $supplierShortName = '-';
            if (!empty($product['supplier'])) {
                $supplierInTable = \App\Models\Supplier::where('name', $product['supplier'])->first();
                if ($supplierInTable && !empty($supplierInTable->short_name)) {
                    $supplierShortName = $supplierInTable->short_name;
                } elseif ($supplierInTable) {
                    $supplierShortName = $supplierInTable->name;
                } else {
                    // Jeśli nie znaleziono w bazie, użyj tego co przyszło
                    $supplierShortName = $product['supplier'];
                }
            }
            
            $table->addCell(2000, $cellStyle)->addText($supplierShortName, ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Ilość
            $table->addCell($quantityWidth, $cellStyle)->addText((string)$product['quantity'], ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]);
            
            // Cena netto
            $priceText = '-';
            if (!empty($product['price'])) {
                $currency = $product['currency'] ?? 'PLN';
                $priceText = $product['price'] . ' ' . $currency;
            }
            $table->addCell(1500, $cellStyle)->addText($priceText, ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        }
        
        // Wiersz z sumą netto
        $totalNet = 0;
        $mainCurrency = 'PLN';
        foreach ($products as $product) {
            if (!empty($product['price'])) {
                $priceValue = floatval(str_replace(',', '.', $product['price']));
                $totalNet += $priceValue * ($product['quantity'] ?? 1);
                if (!empty($product['currency'])) {
                    $mainCurrency = $product['currency'];
                }
            }
        }
        
        $table->addRow();
        $sumCellStyle = ['bgColor' => 'E8E8E8', 'valign' => 'center'];
        $table->addCell(3500, $sumCellStyle)->addText('');
        $table->addCell(2000, $sumCellStyle)->addText('');
        $table->addCell($quantityWidth, $sumCellStyle)->addText('SUMA:', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        $table->addCell(1500, $sumCellStyle)->addText(number_format($totalNet, 2, ',', ' ') . ' ' . $mainCurrency, ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        
        // Opis i zakres zamówienia
        $section->addTextBreak(1);
        $section->addText('Opis i zakres zamówienia:', ['bold' => true, 'size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        $section->addTextBreak(3);
        
        // Kreska przerywana - używamy tekstu z ciągiem myślników
        $section->addText(
            str_repeat('- ', 80),
            ['size' => 8, 'color' => '999999'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
        );
        
        // Informacje pod kreską
        $section->addTextBreak(1);
        
        $deliveryTime = $order->delivery_time;
        $supplierOfferNumber = $order->supplier_offer_number;
        $paymentMethod = $order->payment_method;
        
        if (!empty($deliveryTime)) {
            $section->addText('Termin dostawy: ' . $deliveryTime, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        }
        
        if (!empty($supplierOfferNumber)) {
            $section->addText('Oferta dostawcy: ' . $supplierOfferNumber, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        }
        
        if (!empty($paymentMethod)) {
            $paymentText = 'Rodzaj płatności: ' . $paymentMethod;
            if ($paymentMethod === 'przelew') {
                $paymentDays = $order->payment_days ?? '30 dni';
                $paymentText .= ' (' . $paymentDays . ')';
            }
            $section->addText($paymentText, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]);
        }
        
        // Informacja o kontakcie - na samym dole strony
        $section->addTextBreak(4);
        
        // Pobierz dane użytkownika który utworzył zamówienie
        $user = $order->user;
        $userName = $user ? $user->name : '';
        $userEmail = $user ? $user->email : '';
        $userPhone = $user ? $user->phone : '';
        
        $section->addText(
            'W razie problemów z realizacją zamówienia prosimy o kontakt z osobą składającą zamówienie:',
            ['size' => 9, 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT, 'spaceAfter' => 0]
        );
        
        // Pozdrowienia na samym dole
        $section->addTextBreak(1);
        $section->addText('Pozdrawiam:', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]);
        if (!empty($userName)) {
            $section->addText($userName, ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]);
        }
        if (!empty($userEmail)) {
            $section->addText('email: ' . $userEmail, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]);
        }
        if (!empty($userPhone)) {
            $section->addText('nr. tel.: ' . $userPhone, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]);
        }
        
        // Nazwa pliku (bezpieczna dla systemu plików) - używamy już przetworzonej nazwy z zamienioną nazwą dostawcy
        $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $orderName) . '.docx';
        
        // Zapisz do tymczasowego pliku
        $tempFile = tempnam(sys_get_temp_dir(), 'order_');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        // Usuń pliki tymczasowe logo PO zapisaniu dokumentu
        foreach ($tempFilesToDelete as $tempFilePath) {
            @unlink($tempFilePath);
        }
        
        // Zwróć plik do pobrania
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }
    
    // GENERUJ DOKUMENT PDF DLA ZAMÓWIENIA
    public function generateOrderPdf($orderId)
    {
        $order = \App\Models\Order::findOrFail($orderId);
        
        $orderName = $order->order_number;
        $supplierName = $order->supplier;
        $products = $order->products;

        // Pobierz dane firmy z bazy danych
        $companySettings = \App\Models\CompanySetting::first();
        
        // Pobierz dane dostawcy z bazy danych
        $supplier = null;
        if (!empty($supplierName)) {
            $supplier = \App\Models\Supplier::where('name', $supplierName)->first();
        }
        
        // Pobierz dane użytkownika który utworzył zamówienie
        $user = $order->user;
        $userName = $user ? $user->name : '';
        $userEmail = $user ? $user->email : '';
        $userPhone = $user ? $user->phone : '';
        
        // Przygotuj dane firmy
        $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
        $companyAddress = $companySettings && $companySettings->address && $companySettings->city 
            ? ('Ul. ' . $companySettings->address . ', ' . ($companySettings->postal_code ? $companySettings->postal_code . ' ' : '') . $companySettings->city)
            : 'ul. Słoneczna, 40-100 Warszawa';
        $companyEmail = $companySettings && $companySettings->email ? $companySettings->email : 'test@example.com';
        $companyCity = $companySettings && $companySettings->city ? $companySettings->city : 'Warszawa';
        $companyLogo = $companySettings && $companySettings->logo ? $companySettings->logo : null;
        
        // Oblicz sumę netto
        $totalNet = 0;
        $mainCurrency = 'PLN';
        foreach ($products as $product) {
            if (!empty($product['price'])) {
                $priceValue = floatval(str_replace(',', '.', $product['price']));
                $totalNet += $priceValue * ($product['quantity'] ?? 1);
                if (!empty($product['currency'])) {
                    $mainCurrency = $product['currency'];
                }
            }
        }
        
        // Generuj HTML dla PDF
        $html = '
        <!DOCTYPE html>
        <html>
        <head>
            <meta charset="utf-8">
            <style>
                body { font-family: DejaVu Sans, sans-serif; font-size: 10px; margin: 20px; }
                .header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid #ccc; padding-bottom: 10px; }
                .header-left { display: flex; align-items: center; gap: 20px; }
                .header-logo img { height: 40px; }
                .header-company { }
                .header-company .name { font-weight: bold; font-size: 11px; }
                .header-company .address { font-size: 9px; color: #666; }
                .date { text-align: right; margin-bottom: 20px; }
                .supplier-info { text-align: right; margin-bottom: 20px; }
                .supplier-info .name { font-weight: bold; }
                .supplier-info .detail { font-size: 9px; }
                .order-title { text-align: center; font-size: 14px; font-weight: bold; margin: 20px 0; }
                table { width: 100%; border-collapse: collapse; margin: 10px 0; }
                th, td { border: 1px solid #ccc; padding: 5px; font-size: 9px; vertical-align: middle; }
                th { background-color: #e0e0e0; font-weight: bold; }
                .row-even { background-color: #f5f5f5; }
                .text-center { text-align: center; }
                .text-right { text-align: right; }
                .sum-row { background-color: #e8e8e8; }
                .description-section { margin-top: 20px; }
                .description-title { font-weight: bold; font-size: 11px; }
                .dashed-line { border-top: 1px dashed #999; margin: 30px 0 10px 0; }
                .info-section { margin-top: 10px; }
                .contact-info { font-style: italic; font-size: 9px; margin-top: 30px; }
                .signature { text-align: right; margin-top: 20px; }
                .footer { text-align: center; font-size: 8px; color: #666; font-style: italic; margin-top: 50px; border-top: 1px solid #ccc; padding-top: 10px; }
            </style>
        </head>
        <body>
            <table style="border: none; width: 100%; margin-bottom: 20px;">
                <tr>
                    <td style="border: none; width: 60px; vertical-align: middle;">';
        
        if ($companyLogo) {
            $html .= '<img src="' . $companyLogo . '" style="height: 40px;">';
        }
        
        $html .= '</td>
                    <td style="border: none; vertical-align: middle; padding-left: 15px;">
                        <div style="font-weight: bold; font-size: 11px;">' . htmlspecialchars($companyName) . '</div>
                        <div style="font-size: 9px;">' . htmlspecialchars($companyAddress) . '</div>
                        <div style="font-size: 9px; color: #4B5563;">' . htmlspecialchars($companyEmail) . '</div>
                    </td>
                </tr>
            </table>
            
            <div class="date">' . htmlspecialchars($companyCity) . ', ' . now()->format('d.m.Y') . '</div>
            
            <div class="supplier-info">';
        
        if ($supplier) {
            if ($supplier->name) {
                $html .= '<div class="name">' . htmlspecialchars($supplier->name) . '</div>';
            }
            if ($supplier->nip) {
                $html .= '<div class="detail">NIP: ' . htmlspecialchars($supplier->nip) . '</div>';
            }
            if ($supplier->address) {
                $html .= '<div class="detail">Ul. ' . htmlspecialchars($supplier->address) . '</div>';
            }
            if ($supplier->postal_code || $supplier->city) {
                $html .= '<div class="detail">' . htmlspecialchars(trim(($supplier->postal_code ?? '') . ' ' . ($supplier->city ?? ''))) . '</div>';
            }
            if ($supplier->email) {
                $html .= '<div class="detail">' . htmlspecialchars($supplier->email) . '</div>';
            }
            if ($supplier->logo) {
                $html .= '<div style="margin-top: 10px;"><img src="' . $supplier->logo . '" style="height: 40px;"></div>';
            }
        } else {
            $html .= '<div>Dostawca: _______________________</div>';
            $html .= '<div class="detail">NIP: _______________________</div>';
            $html .= '<div class="detail">Adres: _______________________</div>';
        }
        
        $html .= '</div>
            
            <div class="order-title">Zamówienie: ' . htmlspecialchars($orderName) . '</div>
            
            <table>
                <thead>
                    <tr>
                        <th>Produkt</th>
                        <th class="text-center">Dostawca</th>
                        <th class="text-center">Ilość</th>
                        <th class="text-right">Cena netto</th>
                    </tr>
                </thead>
                <tbody>';
        
        $rowIndex = 0;
        foreach ($products as $product) {
            $rowIndex++;
            $rowClass = ($rowIndex % 2 === 0) ? 'row-even' : '';
            
            $supplierShortName = '-';
            if (!empty($product['supplier'])) {
                $supplierInTable = \App\Models\Supplier::where('name', $product['supplier'])->first();
                if ($supplierInTable && !empty($supplierInTable->short_name)) {
                    $supplierShortName = $supplierInTable->short_name;
                } elseif ($supplierInTable) {
                    $supplierShortName = $supplierInTable->name;
                } else {
                    $supplierShortName = $product['supplier'];
                }
            }
            
            $priceText = '-';
            if (!empty($product['price'])) {
                $currency = $product['currency'] ?? 'PLN';
                $priceText = $product['price'] . ' ' . $currency;
            }
            
            $html .= '<tr class="' . $rowClass . '">
                <td>' . htmlspecialchars($product['name']) . '</td>
                <td class="text-center">' . htmlspecialchars($supplierShortName) . '</td>
                <td class="text-center">' . htmlspecialchars($product['quantity']) . '</td>
                <td class="text-right">' . htmlspecialchars($priceText) . '</td>
            </tr>';
        }
        
        $html .= '<tr class="sum-row">
                <td></td>
                <td></td>
                <td class="text-right"><strong>SUMA:</strong></td>
                <td class="text-right"><strong>' . number_format($totalNet, 2, ',', ' ') . ' ' . $mainCurrency . '</strong></td>
            </tr>
                </tbody>
            </table>
            
            <div class="description-section">
                <div class="description-title">Opis i zakres zamówienia:</div>
                <br><br><br>
            </div>
            
            <div class="dashed-line"></div>
            
            <div class="info-section">';
        
        if (!empty($order->delivery_time)) {
            $html .= '<div>Termin dostawy: ' . htmlspecialchars($order->delivery_time) . '</div>';
        }
        if (!empty($order->supplier_offer_number)) {
            $html .= '<div>Oferta dostawcy: ' . htmlspecialchars($order->supplier_offer_number) . '</div>';
        }
        if (!empty($order->payment_method)) {
            $paymentText = $order->payment_method;
            if ($order->payment_method === 'przelew' && $order->payment_days) {
                $paymentText .= ' (' . $order->payment_days . ')';
            }
            $html .= '<div>Rodzaj płatności: ' . htmlspecialchars($paymentText) . '</div>';
        }
        
        $html .= '</div>
            
            <div class="contact-info">
                W razie problemów z realizacją zamówienia prosimy o kontakt z osobą składającą zamówienie:
            </div>
            
            <div class="signature">
                <div>Pozdrawiam:</div>';
        
        if (!empty($userName)) {
            $html .= '<div>' . htmlspecialchars($userName) . '</div>';
        }
        if (!empty($userEmail)) {
            $html .= '<div style="font-size: 10px;">email: ' . htmlspecialchars($userEmail) . '</div>';
        }
        if (!empty($userPhone)) {
            $html .= '<div style="font-size: 10px;">nr. tel.: ' . htmlspecialchars($userPhone) . '</div>';
        }
        
        $html .= '</div>
            
            <div class="footer">
                Dokumentu nie wolno kopiować ani rozpowszechniać bez zgody ' . htmlspecialchars($companyName) . '
            </div>
        </body>
        </html>';
        
        // Użyj Dompdf do generowania PDF
        $dompdf = new \Dompdf\Dompdf();
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();
        
        // Nazwa pliku
        $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $orderName) . '.pdf';
        
        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }
    
    // Generuj rzeczywistą nazwę zamówienia ze skróconą nazwą dostawcy
    private function generateRealOrderName($template, $supplierName)
    {
        if (empty($supplierName)) {
            // Zamień "DOSTAWCA" na "brak" gdy nie ma dostawcy
            return str_replace('DOSTAWCA', 'brak', $template);
        }
        
        // Pobierz skróconą nazwę dostawcy
        $supplier = \App\Models\Supplier::where('name', $supplierName)->first();
        $shortName = $supplier && !empty($supplier->short_name) ? $supplier->short_name : $supplierName;
        
        // Zamień "DOSTAWCA" na rzeczywistą skróconą nazwę
        return str_replace('DOSTAWCA', $shortName, $template);
    }
    
    // Pobierz następną nazwę zamówienia (dla odświeżenia po utworzeniu zamówienia)
    public function getNextOrderName(Request $request)
    {
        $orderSettings = \DB::table('order_settings')->first();
        $supplierName = $request->input('supplier', '');
        $shouldIncrement = $request->input('increment', 0);
        
        // Jeśli flaga increment=1, zwiększ licznik teraz
        if ($shouldIncrement && $orderSettings) {
            // Sprawdź czy którykolwiek element używa numeru
            $hasNumberElement = ($orderSettings->element1_type ?? '') === 'number' 
                || ($orderSettings->element2_type ?? '') === 'number'
                || ($orderSettings->element3_type ?? '') === 'number'
                || ($orderSettings->element4_type ?? '') === 'number';
                
            if ($hasNumberElement) {
                \DB::table('order_settings')->update([
                    'start_number' => ($orderSettings->start_number ?? 1) + 1
                ]);
                // Pobierz zaktualizowane ustawienia
                $orderSettings = \DB::table('order_settings')->first();
            }
        }
        
        if (!$orderSettings) {
            return response()->json(['order_name' => 'Nie skonfigurowano']);
        }
        
        // Generuj nazwę używając tej samej logiki co w widoku
        $orderName = $this->generateOrderNamePreview($orderSettings, $supplierName);
        
        return response()->json(['order_name' => $orderName]);
    }
    
    // Generuj podgląd nazwy zamówienia (ta sama logika co w Blade)
    private function generateOrderNamePreview($settings, $supplierName = '')
    {
        $parts = [];
        
        // Element 1
        if (isset($settings->element1_type) && $settings->element1_type !== 'empty') {
            $parts[] = $this->generateElementValue($settings->element1_type, $settings->element1_value ?? null, $settings);
        }
        
        // Separator 1
        if (!empty($parts) && isset($settings->element2_type) && $settings->element2_type !== 'empty') {
            $parts[] = $settings->separator1 ?? '_';
        }
        
        // Element 2
        if (isset($settings->element2_type) && $settings->element2_type !== 'empty') {
            $parts[] = $this->generateElementValue($settings->element2_type, $settings->element2_value ?? null, $settings);
        }
        
        // Separator 2
        if (!empty($parts) && isset($settings->element3_type) && $settings->element3_type !== 'empty') {
            $parts[] = $settings->separator2 ?? '_';
        }
        
        // Element 3
        if (isset($settings->element3_type) && $settings->element3_type !== 'empty') {
            $parts[] = $this->generateElementValue($settings->element3_type, $settings->element3_value ?? null, $settings);
        }
        
        // Separator 3
        if (!empty($parts) && isset($settings->element4_type) && $settings->element4_type !== 'empty') {
            $parts[] = $settings->separator3 ?? '_';
        }
        
        // Element 4
        if (isset($settings->element4_type) && $settings->element4_type !== 'empty') {
            $value = $settings->element4_type === 'supplier' ? $supplierName : null;
            $parts[] = $this->generateElementValue($settings->element4_type, $value, $settings);
        }
        
        return implode('', array_filter($parts, fn($p) => $p !== null && $p !== ''));
    }
    
    private function generateElementValue($type, $value, $settings)
    {
        switch($type) {
            case 'text':
                return $value ?? 'Tekst';
            case 'date':
                $format = $value ?? 'yyyy-mm-dd';
                if ($format === 'yyyymmdd') {
                    return date('Ymd');
                }
                return date('Y-m-d');
            case 'time':
                $format = $value ?? 'hh-mm-ss';
                if ($format === 'hhmmss') {
                    return date('His');
                } elseif ($format === 'hh-mm') {
                    return date('H-i');
                } elseif ($format === 'hh') {
                    return date('H');
                }
                return date('H-i-s');
            case 'number':
                $digits = $settings->element3_digits ?? 4;
                $start = $settings->start_number ?? 1;
                return str_pad($start, $digits, '0', STR_PAD_LEFT);
            case 'supplier':
                if (empty($value)) {
                    return 'DOSTAWCA';
                }
                $supplier = \App\Models\Supplier::where('name', $value)->first();
                return $supplier && !empty($supplier->short_name) ? $supplier->short_name : ($value ?? 'DOSTAWCA');
            default:
                return '';
        }
    }
    
    // Usuń zamówienie
    public function deleteOrder(\App\Models\Order $order)
    {
        $order->delete();
        return response()->json(['success' => true, 'message' => 'Zamówienie zostało usunięte']);
    }

    // Usuń wiele zamówień
    public function deleteMultipleOrders(Request $request)
    {
        $data = $request->validate([
            'order_ids' => 'required|array',
            'order_ids.*' => 'required|integer|exists:orders,id'
        ]);

        $deleted = \App\Models\Order::whereIn('id', $data['order_ids'])->delete();

        return response()->json([
            'success' => true, 
            'message' => 'Zamówienia zostały usunięte',
            'deleted' => $deleted
        ]);
    }

    // Przyjmij zamówienie
    public function receiveOrder(\App\Models\Order $order)
    {
        // Sprawdź czy zamówienie już zostało przyjęte
        if ($order->status === 'received') {
            return response()->json([
                'success' => false,
                'message' => 'To zamówienie zostało już przyjęte'
            ], 400);
        }

        // Pobierz produkty z zamówienia
        $products = $order->products;

        if (!is_array($products) || empty($products)) {
            return response()->json([
                'success' => false,
                'message' => 'Brak produktów w zamówieniu'
            ], 400);
        }

        // Dodaj produkty do magazynu
        foreach ($products as $product) {
            $partName = $product['name'] ?? null;
            $quantity = $product['quantity'] ?? 0;
            $supplier = $product['supplier'] ?? null;
            $price = $product['price'] ?? null;
            $currency = $product['currency'] ?? 'PLN';

            if (!$partName || $quantity <= 0) {
                continue;
            }

            // Znajdź część w bazie
            $part = Part::where('name', $partName)->first();

            if ($part) {
                // Jeśli część istnieje, zwiększ stan
                $part->quantity += $quantity;
                
                // Zaktualizuj dostawcę i cenę jeśli są podane
                if ($supplier) {
                    $part->supplier = $supplier;
                }
                if ($price) {
                    $part->net_price = $price;
                    $part->currency = $currency;
                }
                
                $part->save();
            } else {
                // Jeśli część nie istnieje, możesz ją utworzyć lub pominąć
                // Na razie pomijamy - można dodać tworzenie nowej części
                continue;
            }
        }

        // Zaktualizuj status zamówienia
        $order->status = 'received';
        $order->received_at = now();
        $order->received_by_user_id = auth()->id();
        $order->save();

        return response()->json([
            'success' => true,
            'message' => 'Zamówienie zostało przyjęte i produkty dodane do magazynu'
        ]);
    }

    public function generateQrCode(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'location' => 'nullable|string|max:10',
        ]);

        $qrSettings = \DB::table('qr_settings')->first();
        
        if (!$qrSettings) {
            return response()->json([
                'success' => false,
                'message' => 'Brak konfiguracji kodów QR. Skonfiguruj ustawienia w zakładce Ustawienia > Inne > Ustawienia Kodów QR'
            ], 400);
        }

        // Buduj kod QR na podstawie ustawień
        $qrCodeParts = [];
        $qrDescription = [];
        
        // Element 1
        if ($qrSettings->element1_type !== 'empty') {
            if ($qrSettings->element1_type === 'product_name') {
                $qrCodeParts[] = $validated['name'];
                $qrDescription[] = 'Nazwa produktu';
            } elseif ($qrSettings->element1_type === 'text') {
                $qrCodeParts[] = $qrSettings->element1_value;
                $qrDescription[] = 'Tekst: ' . $qrSettings->element1_value;
            } elseif ($qrSettings->element1_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
                $qrDescription[] = 'Data';
            } elseif ($qrSettings->element1_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
                $qrDescription[] = 'Godzina';
            }
        }
        
        // Element 2
        if ($qrSettings->element2_type !== 'empty') {
            if ($qrSettings->element2_type === 'location') {
                $qrCodeParts[] = $validated['location'] ?? 'BRAK';
                $qrDescription[] = 'Lokalizacja';
            } elseif ($qrSettings->element2_type === 'text') {
                $qrCodeParts[] = $qrSettings->element2_value;
                $qrDescription[] = 'Tekst: ' . $qrSettings->element2_value;
            } elseif ($qrSettings->element2_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
                $qrDescription[] = 'Data';
            } elseif ($qrSettings->element2_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
                $qrDescription[] = 'Godzina';
            }
        }
        
        // Element 3
        if ($qrSettings->element3_type !== 'empty') {
            if ($qrSettings->element3_type === 'text') {
                $qrCodeParts[] = $qrSettings->element3_value;
                $qrDescription[] = 'Tekst: ' . $qrSettings->element3_value;
            } elseif ($qrSettings->element3_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
                $qrDescription[] = 'Data';
            } elseif ($qrSettings->element3_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
                $qrDescription[] = 'Godzina';
            }
        }
        
        // Element 4 (liczba inkrementowana lub data)
        if ($qrSettings->element4_type !== 'empty') {
            if ($qrSettings->element4_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
                $qrDescription[] = 'Data';
            } elseif ($qrSettings->element4_type === 'number') {
                // Inkrementuj liczbę w bazie
                $currentNumber = $qrSettings->start_number;
                $qrCodeParts[] = $currentNumber;
                $qrDescription[] = 'Numer: ' . $currentNumber;
                
                // Zaktualizuj start_number (inkrementuj)
                \DB::table('qr_settings')->update([
                    'start_number' => $currentNumber + 1
                ]);
            }
        }
        
        // Buduj finalny kod QR z separatorami
        $separators = [
            $qrSettings->separator1 ?? '_',
            $qrSettings->separator2 ?? '_',
            $qrSettings->separator3 ?? '_'
        ];
        
        $qrCode = '';
        foreach ($qrCodeParts as $index => $part) {
            $qrCode .= $part;
            if ($index < count($qrCodeParts) - 1) {
                $qrCode .= $separators[$index] ?? '_';
            }
        }
        
        // Generuj obrazek QR kodu za pomocą biblioteki SimpleSoftwareIO
        try {
            // Generuj SVG jako string
            $qrImageSvg = \QrCode::format('svg')->size(200)->generate($qrCode);
            // Konwertuj do stringa jeśli to obiekt
            $qrImageString = is_string($qrImageSvg) ? $qrImageSvg : (string)$qrImageSvg;
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd generowania kodu QR: ' . $e->getMessage()
            ], 500);
        }
        
        return response()->json([
            'success' => true,
            'qr_code' => $qrCode,
            'qr_image' => $qrImageString, // SVG string
            'description' => implode(' | ', $qrDescription)
        ]);
    }

    /**
     * Wyszukaj produkt po kodzie QR
     */
    public function findByQr(Request $request)
    {
        $request->validate([
            'qr_code' => 'required|string'
        ]);

        $qrCode = $request->input('qr_code');

        // Szukaj produktu z tym kodem QR
        $part = Part::where('qr_code', $qrCode)->first();

        if ($part) {
            return response()->json([
                'success' => true,
                'part' => [
                    'id' => $part->id,
                    'name' => $part->name,
                    'description' => $part->description,
                    'quantity' => $part->quantity,
                    'minimum_stock' => $part->minimum_stock,
                    'location' => $part->location,
                    'net_price' => $part->net_price,
                    'currency' => $part->currency,
                    'supplier' => $part->supplier,
                    'category_id' => $part->category_id,
                    'qr_code' => $part->qr_code
                ]
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Nie znaleziono produktu z tym kodem QR'
        ]);
    }

    /**
     * Automatyczne generowanie kodu QR dla produktu (bez zwracania obrazka)
     */
    private function autoGenerateQrCode($productName, $location = null)
    {
        $qrSettings = \DB::table('qr_settings')->first();
        
        if (!$qrSettings) {
            return null; // Brak ustawień - nie generujemy kodu
        }

        // Buduj kod QR na podstawie ustawień
        $qrCodeParts = [];
        
        // Element 1
        if ($qrSettings->element1_type !== 'empty') {
            if ($qrSettings->element1_type === 'product_name') {
                $qrCodeParts[] = $productName;
            } elseif ($qrSettings->element1_type === 'text') {
                $qrCodeParts[] = $qrSettings->element1_value;
            } elseif ($qrSettings->element1_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
            } elseif ($qrSettings->element1_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
            }
        }
        
        // Element 2
        if ($qrSettings->element2_type !== 'empty') {
            if ($qrSettings->element2_type === 'location') {
                $qrCodeParts[] = $location ?? 'BRAK';
            } elseif ($qrSettings->element2_type === 'text') {
                $qrCodeParts[] = $qrSettings->element2_value;
            } elseif ($qrSettings->element2_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
            } elseif ($qrSettings->element2_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
            }
        }
        
        // Element 3
        if ($qrSettings->element3_type !== 'empty') {
            if ($qrSettings->element3_type === 'text') {
                $qrCodeParts[] = $qrSettings->element3_value;
            } elseif ($qrSettings->element3_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
            } elseif ($qrSettings->element3_type === 'time') {
                $qrCodeParts[] = now()->format('Hi');
            }
        }
        
        // Element 4 (liczba inkrementowana lub data)
        if ($qrSettings->element4_type !== 'empty') {
            if ($qrSettings->element4_type === 'date') {
                $qrCodeParts[] = now()->format('Ymd');
            } elseif ($qrSettings->element4_type === 'number') {
                // Inkrementuj liczbę w bazie
                $currentNumber = $qrSettings->start_number;
                $qrCodeParts[] = $currentNumber;
                
                // Zaktualizuj start_number (inkrementuj)
                \DB::table('qr_settings')->update([
                    'start_number' => $currentNumber + 1
                ]);
            }
        }
        
        // Buduj finalny kod QR z separatorami
        $separators = [
            $qrSettings->separator1 ?? '_',
            $qrSettings->separator2 ?? '_',
            $qrSettings->separator3 ?? '_'
        ];
        
        $qrCode = '';
        foreach ($qrCodeParts as $index => $part) {
            $qrCode .= $part;
            if ($index < count($qrCodeParts) - 1) {
                $qrCode .= $separators[$index] ?? '_';
            }
        }
        
        return $qrCode;
    }

    /**
     * Import produktów z pliku Excel
     */
    public function importExcel(Request $request)
    {
        $request->validate([
            'excel_file' => 'required|file|mimes:xlsx,xls|max:10240'
        ]);

        try {
            $file = $request->file('excel_file');
            
            // Załaduj plik Excel
            $spreadsheet = \PhpOffice\PhpSpreadsheet\IOFactory::load($file->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray();

            // Zakładamy że pierwszy wiersz to nagłówki
            $headers = array_shift($rows);
            
            // Normalizuj nagłówki (lowercase, bez białych znaków)
            $headers = array_map(function($h) {
                return strtolower(trim($h));
            }, $headers);

            // Mapowanie kolumn (elastyczne nazwy)
            $columnMap = [
                'produkty' => ['produkty', 'produkt', 'nazwa', 'name'],
                'opis' => ['opis', 'description', 'desc'],
                'dost' => ['dost', 'dost.', 'dostawca', 'supplier'],
                'cena' => ['cena', 'price', 'net_price', 'cena netto'],
                'waluta' => ['waluta', 'currency', 'curr'],
                'kategoria' => ['kategoria', 'category', 'kat'],
                'ilosc' => ['ilość', 'ilosc', 'quantity', 'qty', 'sztuk'],
                'lokalizacja' => ['lok', 'lok.', 'lokalizacja', 'location', 'miejsce']
            ];

            // Znajdź indeksy kolumn
            $colIndexes = [];
            foreach ($columnMap as $key => $possibleNames) {
                foreach ($headers as $index => $header) {
                    if (in_array($header, $possibleNames)) {
                        $colIndexes[$key] = $index;
                        break;
                    }
                }
            }

            // Sprawdź czy znaleziono kolumnę z nazwą produktu
            if (!isset($colIndexes['produkty'])) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nie znaleziono kolumny z nazwą produktu (produkty/nazwa/name)'
                ], 400);
            }

            $products = [];
            $categories = \App\Models\Category::all();
            $suppliers = \App\Models\Supplier::all();
            $unknownSuppliers = []; // Lista nieznanych dostawców
            $unknownCategories = []; // Lista nieznanych kategorii

            // Zapisz dostępne kategorie do logu (do debugowania Railway)
            \Log::info('Dostępne kategorie w bazie', [
                'categories' => $categories->pluck('name')->toArray()
            ]);

            foreach ($rows as $row) {
                // Pomiń puste wiersze
                if (empty(array_filter($row))) {
                    continue;
                }

                $productName = isset($colIndexes['produkty']) ? trim($row[$colIndexes['produkty']] ?? '') : '';
                
                if (empty($productName)) {
                    continue;
                }
                
                // Ogranicz długość nazwy do 255 znaków (limit w bazie)
                if (strlen($productName) > 255) {
                    $productName = substr($productName, 0, 252) . '...';
                    \Log::info('Nazwa produktu została skrócona', [
                        'original_name' => $row[$colIndexes['produkty']] ?? '',
                        'truncated_name' => $productName
                    ]);
                }

                // Pobierz dane z wiersza
                $description = isset($colIndexes['opis']) ? trim($row[$colIndexes['opis']] ?? '') : '';
                
                // Ogranicz długość opisu do 255 znaków (limit w bazie)
                if (strlen($description) > 255) {
                    $description = substr($description, 0, 252) . '...';
                    \Log::info('Opis produktu został skrócony', [
                        'product' => $productName,
                        'original_length' => strlen($row[$colIndexes['opis']] ?? ''),
                        'truncated_length' => strlen($description)
                    ]);
                }
                
                $supplierName = isset($colIndexes['dost']) ? trim($row[$colIndexes['dost']] ?? '') : '';
                
                // Ogranicz długość nazwy dostawcy do 255 znaków (limit w bazie)
                if (strlen($supplierName) > 255) {
                    $supplierName = substr($supplierName, 0, 255);
                    \Log::info('Nazwa dostawcy została skrócona', [
                        'product' => $productName,
                        'original_supplier' => $row[$colIndexes['dost']] ?? '',
                        'truncated_supplier' => $supplierName
                    ]);
                }
                
                // Normalizuj cenę - obsługa przecinka jako separatora dziesiętnego
                $priceRaw = isset($colIndexes['cena']) ? trim($row[$colIndexes['cena']] ?? '') : '';
                $priceRaw = str_replace(',', '.', $priceRaw); // Zamień przecinek na kropkę
                $priceRaw = preg_replace('/[^\d.]/', '', $priceRaw); // Usuń wszystkie znaki oprócz cyfr i kropki
                $price = floatval($priceRaw);
                
                // Normalizuj walutę - obsługa różnych formatów
                $currencyRaw = isset($colIndexes['waluta']) ? trim($row[$colIndexes['waluta']] ?? '') : '';
                $currency = null;
                
                if (!empty($currencyRaw)) {
                    $currencyRaw = strtoupper($currencyRaw);
                    
                    // Mapowanie różnych formatów walut
                    if (in_array($currencyRaw, ['PLN', 'ZŁ', 'ZŁOTY', 'ZŁOTE', 'ZLOTY', 'ZLOTE', 'ZL'])) {
                        $currency = 'PLN';
                    } elseif (in_array($currencyRaw, ['EUR', 'EURO', '€'])) {
                        $currency = 'EUR';
                    } elseif (in_array($currencyRaw, ['$', 'USD', 'DOLLAR', 'DOLAR'])) {
                        $currency = '$';
                    }
                }
                
                // Jeśli nie rozpoznano waluty, pozostaw null (zostanie ustawiona w bazie danych)
                if (!$currency) {
                    $currency = 'PLN'; // Domyślnie PLN jeśli pole puste
                }
                
                $categoryName = isset($colIndexes['kategoria']) ? trim($row[$colIndexes['kategoria']] ?? '') : '';
                $quantity = isset($colIndexes['ilosc']) ? intval($row[$colIndexes['ilosc']] ?? 1) : 1;
                $location = isset($colIndexes['lokalizacja']) ? trim($row[$colIndexes['lokalizacja']] ?? '') : '';

                // Znajdź ID kategorii
                $categoryId = null;
                if (!empty($categoryName)) {
                    $category = $categories->firstWhere('name', $categoryName);
                    if ($category) {
                        $categoryId = $category->id;
                    } else {
                        // Dodaj do listy nieznanych kategorii
                        if (!in_array($categoryName, $unknownCategories)) {
                            $unknownCategories[] = $categoryName;
                        }
                        \Log::warning('Kategoria nie znaleziona w bazie', [
                            'product' => $productName,
                            'category_name' => $categoryName,
                            'available_categories' => $categories->pluck('name')->toArray()
                        ]);
                    }
                }
                
                // Jeśli nie znaleziono kategorii, użyj pierwszej dostępnej
                if (!$categoryId && $categories->count() > 0) {
                    $categoryId = $categories->first()->id;
                    \Log::info('Używam domyślnej kategorii', [
                        'product' => $productName,
                        'default_category' => $categories->first()->name
                    ]);
                }
                
                // Jeśli nadal brak kategorii (pusta baza)
                if (!$categoryId) {
                    \Log::error('Brak kategorii w bazie danych', [
                        'product' => $productName
                    ]);
                    continue; // Pomiń ten produkt
                }

                // Znajdź pełną nazwę dostawcy (może być skrót)
                $fullSupplierName = null; // Domyślnie null jeśli nie ma w bazie
                if (!empty($supplierName)) {
                    $supplier = $suppliers->first(function($s) use ($supplierName) {
                        return $s->name === $supplierName || 
                               ($s->short_name && $s->short_name === $supplierName);
                    });
                    if ($supplier) {
                        $fullSupplierName = $supplier->name;
                    } else {
                        // Dodaj do listy nieznanych dostawców
                        if (!in_array($supplierName, $unknownSuppliers)) {
                            $unknownSuppliers[] = $supplierName;
                        }
                        \Log::warning('Dostawca nie znaleziony w bazie', [
                            'product' => $productName,
                            'supplier_name' => $supplierName,
                            'available_suppliers' => $suppliers->pluck('name')->toArray(),
                            'available_short_names' => $suppliers->pluck('short_name')->toArray()
                        ]);
                        // NIE wpisuj dostawcy jeśli nie ma go w bazie
                        $fullSupplierName = null;
                    }
                }

                // Sprawdź czy produkt już istnieje w bazie
                $existingPart = Part::where('name', $productName)->first();
                
                // Generuj kod QR TYLKO dla nowych produktów
                $qrCode = null;
                if (!$existingPart) {
                    $qrCode = $this->autoGenerateQrCode($productName, $location);
                } else {
                    // Dla istniejących produktów użyj ich obecnego kodu QR
                    $qrCode = $existingPart->qr_code;
                }

                $products[] = [
                    'name' => $productName,
                    'description' => $description ?: null,
                    'supplier' => $fullSupplierName ?: null,
                    'net_price' => $price > 0 ? $price : null,
                    'currency' => $currency,
                    'category_id' => $categoryId,
                    'quantity' => $quantity,
                    'location' => $location ?: null,
                    'qr_code' => $qrCode,
                    'is_existing' => $existingPart ? true : false
                ];
            }

            if (empty($products)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Nie znaleziono produktów do zaimportowania w pliku Excel'
                ], 400);
            }

            // Przygotuj komunikat z ostrzeżeniem o nieznanych dostawcach
            $message = 'Zaimportowano ' . count($products) . ' produktów';
            $warnings = [];
            
            if (!empty($unknownSuppliers)) {
                $warnings[] = 'Następujący dostawcy nie zostali znalezieni w bazie i nie zostaną przypisani do produktów: ' . implode(', ', $unknownSuppliers);
            }
            
            if (!empty($unknownCategories)) {
                $warnings[] = 'Następujące kategorie nie zostały znalezione w bazie (produkty użyją domyślnej kategorii): ' . implode(', ', $unknownCategories) . '. Dostępne kategorie: ' . $categories->pluck('name')->implode(', ');
            }

            return response()->json([
                'success' => true,
                'products' => $products,
                'message' => $message,
                'warnings' => $warnings
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Błąd podczas importu: ' . $e->getMessage()
            ], 500);
        }
    }

    // GENERUJ DOKUMENT WORD DLA OFERTY
    public function generateOfferWord($offerId)
    {
        $offer = \App\Models\Offer::findOrFail($offerId);
        
        $offerNumber = $offer->offer_number;
        $offerTitle = $offer->offer_title;
        $services = $offer->services ?? [];
        $works = $offer->works ?? [];
        $materials = $offer->materials ?? [];

        // Tworzenie dokumentu Word
        $phpWord = new \PhpOffice\PhpWord\PhpWord();
        
        // Dodaj sekcję
        $section = $phpWord->addSection();
        
        // Pobierz dane firmy z bazy danych
        $companySettings = \App\Models\CompanySetting::first();
        
        // Tablica na pliki tymczasowe do usunięcia na końcu
        $tempFilesToDelete = [];
        
        // HEADER - dane Mojej Firmy
        $header = $section->addHeader();
        $headerTable = $header->addTable(['cellMargin' => 40]);
        $headerTable->addRow();
        
        // Logo firmy - obsługa base64 data URI
        if ($companySettings && $companySettings->logo) {
            try {
                // Jeśli logo to data URI (base64)
                if (strpos($companySettings->logo, 'data:image') === 0) {
                    $imageData = explode(',', $companySettings->logo);
                    if (count($imageData) === 2) {
                        $base64Data = $imageData[1];
                        $imageContent = base64_decode($base64Data);
                        
                        $extension = '.png';
                        if (strpos($companySettings->logo, 'data:image/jpeg') === 0) {
                            $extension = '.jpg';
                        } elseif (strpos($companySettings->logo, 'data:image/gif') === 0) {
                            $extension = '.gif';
                        }
                        
                        $tempLogoPath = tempnam(sys_get_temp_dir(), 'logo_') . $extension;
                        file_put_contents($tempLogoPath, $imageContent);
                        $tempFilesToDelete[] = $tempLogoPath;
                        
                        $headerTable->addCell(2000, ['valign' => 'center', 'borderRightSize' => 0])->addImage($tempLogoPath, [
                            'height' => 34,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                            'marginTop' => 6,
                            'marginRight' => 200,
                        ]);
                    } else {
                        $headerTable->addCell(2000, ['valign' => 'center']);
                    }
                } else {
                    $logoPath = storage_path('app/public/' . $companySettings->logo);
                    if (file_exists($logoPath)) {
                        $headerTable->addCell(2000, ['valign' => 'center', 'borderRightSize' => 0])->addImage($logoPath, [
                            'height' => 34,
                            'alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT,
                            'marginTop' => 6,
                            'marginRight' => 200,
                        ]);
                    } else {
                        $headerTable->addCell(2000, ['valign' => 'center']);
                    }
                }
            } catch (\Exception $e) {
                $headerTable->addCell(2000, ['valign' => 'center']);
            }
        } else {
            $headerTable->addCell(2000, ['valign' => 'center']);
        }

        $companyCell = $headerTable->addCell(8000, ['valign' => 'center']);
        
        $companyName = $companySettings && $companySettings->name ? $companySettings->name : 'Moja Firma';
        $companyAddress = $companySettings && $companySettings->address && $companySettings->city 
            ? ('Ul. ' . $companySettings->address . ', ' . ($companySettings->postal_code ? $companySettings->postal_code . ' ' : '') . $companySettings->city)
            : 'ul. Słoneczna, 40-100 Warszawa';
        $companyEmail = $companySettings && $companySettings->email ? $companySettings->email : 'test@example.com';
        
        $companyCell->addText($companyName, ['bold' => true, 'size' => 10], ['spaceAfter' => 0]);
        $companyCell->addText($companyAddress, ['size' => 9], ['spaceAfter' => 0]);
        $companyCell->addLink('mailto:' . $companyEmail, $companyEmail, ['size' => 9, 'color' => '4B5563'], ['spaceAfter' => 0]);

        // FOOTER - Stopka
        $footer = $section->addFooter();
        $footer->addText(
            'Dokumentu nie wolno kopiować ani rozpowszechniać bez zgody ' . $companyName,
            ['size' => 8, 'italic' => true, 'color' => '666666'],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER]
        );
        
        // Data w prawym górnym rogu
        $companyCity = $companySettings && $companySettings->city ? $companySettings->city : 'Warszawa';
        $dateText = $companyCity . ', ' . $offer->offer_date->format('d.m.Y');
        $section->addText(
            $dateText,
            ['size' => 10],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]
        );
        
        $section->addTextBreak(2);
        
        // Tytuł oferty wycentrowany
        $section->addText(
            'Oferta nr. ' . $offerNumber,
            ['bold' => true, 'size' => 14],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 100]
        );
        
        if (!empty($offerTitle)) {
            $section->addText(
                $offerTitle,
                ['bold' => true, 'size' => 12],
                ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::CENTER, 'spaceAfter' => 200]
            );
        }
        
        // Opis oferty
        if (!empty($offer->offer_description)) {
            $section->addTextBreak(1);
            
            // Konwersja HTML na tekst z formatowaniem dla Worda
            $descriptionHtml = $offer->offer_description;
            
            // Użyj HTMLtoPhpWord do parsowania HTML
            try {
                \PhpOffice\PhpWord\Shared\Html::addHtml($section, $descriptionHtml, false, false);
            } catch (\Exception $e) {
                // Jeśli parsowanie HTML nie zadziała, dodaj jako zwykły tekst
                $plainText = strip_tags($descriptionHtml);
                $section->addText($plainText, ['size' => 10]);
            }
            
            $section->addTextBreak(1);
        }
        
        // Tabela z usługami
        if (!empty($services) && count($services) > 0) {
            $section->addText('Usługi:', ['bold' => true, 'size' => 11], ['spaceAfter' => 100]);
            
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'CCCCCC',
                'cellMargin' => 40,
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            ]);
            
            $table->addRow();
            $cellStyleHeader = ['bgColor' => 'E0E0E0', 'valign' => 'center'];
            $table->addCell(6000, $cellStyleHeader)->addText('Nazwa usługi', ['bold' => true, 'size' => 9]);
            $table->addCell(2000, $cellStyleHeader)->addText('Cena netto', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            
            $rowIndex = 0;
            foreach ($services as $service) {
                $rowIndex++;
                $table->addRow();
                $cellStyle = ($rowIndex % 2 === 0) ? ['bgColor' => 'F5F5F5', 'valign' => 'center'] : ['valign' => 'center'];
                
                $table->addCell(6000, $cellStyle)->addText($service['name'] ?? '', ['size' => 9]);
                $table->addCell(2000, $cellStyle)->addText(number_format($service['price'] ?? 0, 2, ',', ' ') . ' zł', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            $section->addTextBreak(1);
        }
        
        // Tabela z pracami
        if (!empty($works) && count($works) > 0) {
            $section->addText('Prace:', ['bold' => true, 'size' => 11], ['spaceAfter' => 100]);
            
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'CCCCCC',
                'cellMargin' => 40,
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            ]);
            
            $table->addRow();
            $cellStyleHeader = ['bgColor' => 'E0E0E0', 'valign' => 'center'];
            $table->addCell(6000, $cellStyleHeader)->addText('Nazwa pracy', ['bold' => true, 'size' => 9]);
            $table->addCell(2000, $cellStyleHeader)->addText('Cena netto', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            
            $rowIndex = 0;
            foreach ($works as $work) {
                $rowIndex++;
                $table->addRow();
                $cellStyle = ($rowIndex % 2 === 0) ? ['bgColor' => 'F5F5F5', 'valign' => 'center'] : ['valign' => 'center'];
                
                $table->addCell(6000, $cellStyle)->addText($work['name'] ?? '', ['size' => 9]);
                $table->addCell(2000, $cellStyle)->addText(number_format($work['price'] ?? 0, 2, ',', ' ') . ' zł', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            $section->addTextBreak(1);
        }
        
        // Tabela z materiałami
        if (!empty($materials) && count($materials) > 0) {
            $section->addText('Materiały:', ['bold' => true, 'size' => 11], ['spaceAfter' => 100]);
            
            $table = $section->addTable([
                'borderSize' => 6,
                'borderColor' => 'CCCCCC',
                'cellMargin' => 40,
                'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
            ]);
            
            $table->addRow();
            $cellStyleHeader = ['bgColor' => 'E0E0E0', 'valign' => 'center'];
            $table->addCell(6000, $cellStyleHeader)->addText('Nazwa materiału', ['bold' => true, 'size' => 9]);
            $table->addCell(2000, $cellStyleHeader)->addText('Cena netto', ['bold' => true, 'size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            
            $rowIndex = 0;
            foreach ($materials as $material) {
                $rowIndex++;
                $table->addRow();
                $cellStyle = ($rowIndex % 2 === 0) ? ['bgColor' => 'F5F5F5', 'valign' => 'center'] : ['valign' => 'center'];
                
                $table->addCell(6000, $cellStyle)->addText($material['name'] ?? '', ['size' => 9]);
                $table->addCell(2000, $cellStyle)->addText(number_format($material['price'] ?? 0, 2, ',', ' ') . ' zł', ['size' => 9], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
            }
            
            $section->addTextBreak(1);
        }
        
        // Suma końcowa
        $section->addTextBreak(1);
        $sumTable = $section->addTable([
            'alignment' => \PhpOffice\PhpWord\SimpleType\JcTable::CENTER,
        ]);
        $sumTable->addRow();
        $sumTable->addCell(6000)->addText('');
        $sumTable->addCell(2000, ['bgColor' => 'E8E8E8', 'valign' => 'center'])->addText('SUMA: ' . number_format($offer->total_price, 2, ',', ' ') . ' zł', ['bold' => true, 'size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        
        // Informacja kontaktowa
        $section->addTextBreak(3);
        $section->addText(
            'W razie pytań prosimy o kontakt:',
            ['size' => 9, 'italic' => true],
            ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::LEFT]
        );
        
        $section->addTextBreak(1);
        $section->addText('Pozdrawiam,', ['size' => 11], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        if ($companySettings && $companySettings->email) {
            $section->addText('email: ' . $companySettings->email, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT, 'spaceAfter' => 0]);
        }
        if ($companySettings && $companySettings->phone) {
            $section->addText('nr. tel.: ' . $companySettings->phone, ['size' => 10], ['alignment' => \PhpOffice\PhpWord\SimpleType\Jc::RIGHT]);
        }
        
        // Nazwa pliku: numer oferty + opis
        $fileName = $offerNumber;
        if (!empty($offerTitle)) {
            $fileName .= '_' . $offerTitle;
        }
        $fileName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $fileName) . '.docx';
        
        // Zapisz do tymczasowego pliku
        $tempFile = tempnam(sys_get_temp_dir(), 'offer_');
        $objWriter = \PhpOffice\PhpWord\IOFactory::createWriter($phpWord, 'Word2007');
        $objWriter->save($tempFile);
        
        // Usuń pliki tymczasowe logo
        foreach ($tempFilesToDelete as $tempFilePath) {
            @unlink($tempFilePath);
        }
        
        // Zwróć plik do pobrania
        return response()->download($tempFile, $fileName)->deleteFileAfterSend(true);
    }

    // ========== CRM ==========

    public function crmView()
    {
        $userId = auth()->id();
        
        // Companies are visible to all users
        $companies = \App\Models\CrmCompany::with('owner', 'addedBy')->orderBy('name')->get();
        
        // Deals: show only deals owned by user OR assigned to user
        $deals = \App\Models\CrmDeal::with(['company', 'owner', 'user', 'assignedUsers'])
            ->where(function($query) use ($userId) {
                $query->where('user_id', $userId)
                      ->orWhereHas('assignedUsers', function($q) use ($userId) {
                          $q->where('user_id', $userId);
                      });
            })
            ->orderBy('created_at', 'desc')
            ->get();
            
        $tasks = \App\Models\CrmTask::with(['assignedTo', 'company', 'deal'])
            ->where('status', '!=', 'zakonczone')
            ->orderBy('due_date', 'asc')
            ->get();
        $activities = \App\Models\CrmActivity::with(['user', 'company', 'deal'])
            ->orderBy('activity_date', 'desc')
            ->limit(50)
            ->get();
        
        // Etapy CRM
        $crmStages = \DB::table('crm_stages')->orderBy('order')->get();
        
        // Statystyki - tylko dla szans użytkownika
        $stats = [
            'total_companies' => \App\Models\CrmCompany::count(),
            'active_deals' => \App\Models\CrmDeal::whereNotIn('stage', ['wygrana', 'przegrana'])
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('assignedUsers', function($q) use ($userId) {
                              $q->where('user_id', $userId);
                          });
                })
                ->count(),
            'total_pipeline_value' => \App\Models\CrmDeal::whereNotIn('stage', ['wygrana', 'przegrana'])
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('assignedUsers', function($q) use ($userId) {
                              $q->where('user_id', $userId);
                          });
                })
                ->sum('value'),
            'overdue_tasks' => \App\Models\CrmTask::where('status', '!=', 'zakonczone')
                ->where('due_date', '<', now())
                ->count(),
            'deals_by_stage' => \App\Models\CrmDeal::with(['company'])
                ->whereNotIn('stage', ['wygrana', 'przegrana'])
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('assignedUsers', function($q) use ($userId) {
                              $q->where('user_id', $userId);
                          });
                })
                ->get()
                ->groupBy('stage'),
            'recent_won_deals' => \App\Models\CrmDeal::whereIn('stage', ['wygrana', 'przegrana'])
                ->whereNotNull('actual_close_date')
                ->where(function($query) use ($userId) {
                    $query->where('user_id', $userId)
                          ->orWhereHas('assignedUsers', function($q) use ($userId) {
                              $q->where('user_id', $userId);
                          });
                })
                ->orderBy('actual_close_date', 'desc')
                ->get(),
        ];
        
        $users = \App\Models\User::where('can_crm', true)->orWhere('email', 'proximalumine@gmail.com')->get();
        
        // Get suppliers not in CRM for selection
        $availableSuppliers = \App\Models\Supplier::whereNotIn('id', function($query) {
            $query->select('supplier_id')
                  ->from('crm_companies')
                  ->whereNotNull('supplier_id');
        })->orderBy('short_name')->get();
        
        return view('parts.crm', compact('companies', 'deals', 'tasks', 'activities', 'stats', 'users', 'crmStages', 'availableSuppliers'));
    }

    public function crmSettingsView()
    {
        $crmStages = \DB::table('crm_stages')->orderBy('order')->get();
        return view('parts.crm-settings', compact('crmStages'));
    }

    public function addCrmInteraction(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|in:call,email,meeting,note,offer_sent,order_received,complaint,other',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string',
            'interaction_date' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $validated['user_id'] = auth()->id();

        \App\Models\CrmInteraction::create($validated);

        return redirect()->route('magazyn.crm')->with('success', 'Interakcja została dodana.');
    }

    public function updateCrmInteraction(Request $request, \App\Models\CrmInteraction $interaction)
    {
        $validated = $request->validate([
            'supplier_id' => 'required|exists:suppliers,id',
            'type' => 'required|in:call,email,meeting,note,offer_sent,order_received,complaint,other',
            'subject' => 'nullable|string|max:255',
            'description' => 'required|string',
            'interaction_date' => 'required|date',
            'priority' => 'required|in:low,medium,high',
            'status' => 'required|in:pending,completed,cancelled',
        ]);

        $interaction->update($validated);

        return redirect()->route('magazyn.crm')->with('success', 'Interakcja została zaktualizowana.');
    }

    public function deleteCrmInteraction(\App\Models\CrmInteraction $interaction)
    {
        $interaction->delete();
        return redirect()->route('magazyn.crm')->with('success', 'Interakcja została usunięta.');
    }

    // CRM Companies
    public function searchCompanyByNip(Request $request)
    {
        $nip = $request->get('nip');
        
        // Normalizacja NIP - usunięcie wszystkich znaków oprócz cyfr
        $nip = preg_replace('/[^0-9]/', '', $nip);
        
        if (!$nip || strlen($nip) !== 10) {
            return response()->json([
                'success' => false,
                'message' => 'Nieprawidłowy NIP'
            ]);
        }

        // Funkcja formatująca nazwę firmy
        $formatCompanyName = function($name) {
            $name = str_replace('SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ', 'SP. Z O. O.', $name);
            $name = str_replace('SPÓŁKA AKCYJNA', 'S.A.', $name);
            return $name;
        };

        // Funkcja formatująca NIP (xxx-xxx-xx-xx)
        $formatNip = function($nip) {
            if (strlen($nip) === 10) {
                return substr($nip, 0, 3) . '-' . substr($nip, 3, 3) . '-' . substr($nip, 6, 2) . '-' . substr($nip, 8, 2);
            }
            return $nip;
        };

        // 1. Najpierw sprawdź w lokalnej bazie CRM
        $localCompany = \App\Models\CrmCompany::where('nip', 'LIKE', '%' . $nip . '%')->first();
        
        if ($localCompany) {
            return response()->json([
                'success' => true,
                'found' => true,
                'source' => 'local',
                'data' => [
                    'name' => $localCompany->name,
                    'nip' => $localCompany->nip,
                    'address' => $localCompany->address,
                    'city' => $localCompany->city,
                    'postal_code' => $localCompany->postal_code,
                    'phone' => $localCompany->phone,
                    'email' => $localCompany->email,
                ],
                'message' => 'Dane pobrane z lokalnej bazy CRM'
            ]);
        }

        try {
            // Próba 2: API CEIDG - zwraca telefon i email (dla JDG)
            $url = "https://dane.biznes.gov.pl/api/ceidg/v2/firmy?nip={$nip}&status=aktywny";
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 15);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_HTTPHEADER, [
                'Accept: application/json',
                'User-Agent: Mozilla/5.0'
            ]);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (!empty($data['firmy']) && isset($data['firmy'][0])) {
                    $company = $data['firmy'][0];
                    
                    // Budowanie adresu
                    $address = trim(
                        ($company['adres']['ulica'] ?? '') . ' ' . 
                        ($company['adres']['nrNieruchomosci'] ?? '') . 
                        (isset($company['adres']['nrLokalu']) ? '/' . $company['adres']['nrLokalu'] : '')
                    );
                    
                    // Pobierz telefon i email
                    $phone = '';
                    $email = '';
                    
                    if (!empty($company['telefony'])) {
                        $phone = is_array($company['telefony']) ? $company['telefony'][0] : $company['telefony'];
                    }
                    
                    if (!empty($company['adresy_email'])) {
                        $email = is_array($company['adresy_email']) ? $company['adresy_email'][0] : $company['adresy_email'];
                    }
                    
                    return response()->json([
                        'success' => true,
                        'found' => true,
                        'source' => 'ceidg',
                        'data' => [
                            'name' => $formatCompanyName($company['nazwa'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => trim($address),
                            'city' => $company['adres']['miejscowosc'] ?? '',
                            'postal_code' => $company['adres']['kodPocztowy'] ?? '',
                            'phone' => $phone,
                            'email' => $email,
                        ],
                        'message' => 'Dane pobrane z CEIDG'
                    ]);
                }
            }
            
            // Próba 3: API białej listy VAT (dla wszystkich firm, ale bez telefonu/emaila)
            $url = "https://wl-api.mf.gov.pl/api/search/nip/{$nip}?date=" . date('Y-m-d');
            
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, 10);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);
            
            if ($httpCode === 200 && $response) {
                $data = json_decode($response, true);
                
                if (isset($data['result']['subject'])) {
                    $subject = $data['result']['subject'];
                    $address = '';
                    $city = '';
                    $postalCode = '';
                    
                    // Parsowanie adresu - API zwraca go jako string "ULICA NR, KOD MIASTO"
                    $addressString = $subject['workingAddress'] ?? $subject['residenceAddress'] ?? '';
                    
                    if ($addressString) {
                        // Format: "TARNOGÓRSKA 9, 42-677 SZAŁSZA"
                        $parts = explode(',', $addressString, 2);
                        $address = trim($parts[0] ?? ''); // "TARNOGÓRSKA 9"
                        
                        if (isset($parts[1])) {
                            // "42-677 SZAŁSZA"
                            $cityPart = trim($parts[1]);
                            if (preg_match('/^(\d{2}-\d{3})\s+(.+)$/', $cityPart, $matches)) {
                                $postalCode = $matches[1]; // "42-677"
                                $city = $matches[2];       // "SZAŁSZA"
                            } else {
                                $city = $cityPart;
                            }
                        }
                    }
                    
                    return response()->json([
                        'success' => true,
                        'found' => true,
                        'source' => 'vat',
                        'data' => [
                            'name' => $formatCompanyName($subject['name'] ?? ''),
                            'nip' => $formatNip($nip),
                            'address' => $address,
                            'city' => $city,
                            'postal_code' => $postalCode,
                            'phone' => '',
                            'email' => '',
                        ],
                        'message' => 'Dane pobrane z białej listy VAT'
                    ]);
                }
            }
            
        } catch (\Exception $e) {
            \Log::error('Search by NIP error: ' . $e->getMessage());
        }
        
        return response()->json([
            'success' => false,
            'found' => false,
            'message' => 'Nie znaleziono danych dla podanego NIP'
        ]);
    }

    public function addCompany(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'type' => 'required|in:klient,potencjalny,partner,konkurencja',
            'status' => 'required|in:aktywny,nieaktywny,zawieszony',
            'notes' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'owner_id' => 'nullable|exists:users,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
        ]);

        // Normalizacja NIP przed zapisem
        if (!empty($validated['nip'])) {
            $validated['nip'] = preg_replace('/[^0-9]/', '', $validated['nip']);
        }
        
        // Set added_by to current user
        $validated['added_by'] = auth()->id();

        \App\Models\CrmCompany::create($validated);
        return redirect()->route('crm')->with('success', 'Firma została dodana.');
    }

    public function getCompany($id)
    {
        $company = \App\Models\CrmCompany::findOrFail($id);
        return response()->json($company);
    }

    public function updateCompany(Request $request, $id)
    {
        $company = \App\Models\CrmCompany::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'nip' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'website' => 'nullable|url',
            'address' => 'nullable|string',
            'city' => 'nullable|string|max:100',
            'postal_code' => 'nullable|string|max:10',
            'country' => 'nullable|string|max:100',
            'type' => 'required|in:klient,potencjalny,partner,konkurencja',
            'status' => 'required|in:aktywny,nieaktywny,zawieszony',
            'notes' => 'nullable|string',
            'source' => 'nullable|string|max:100',
            'owner_id' => 'nullable|exists:users,id',
        ]);

        $company->update($validated);
        return redirect()->route('crm')->with('success', 'Firma została zaktualizowana.');
    }

    public function deleteCompany($id)
    {
        $company = \App\Models\CrmCompany::findOrFail($id);
        $company->delete();
        return redirect()->route('crm')->with('success', 'Firma została usunięta.');
    }

    // CRM Deals
    public function addDeal(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|exists:crm_companies,id',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stage' => 'required|in:nowy_lead,kontakt,wycena,negocjacje,wygrana,przegrana',
            'probability' => 'required|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);

        if (!isset($validated['owner_id'])) {
            $validated['owner_id'] = auth()->id();
        }
        
        // Set user_id to current user (owner of the deal)
        $validated['user_id'] = auth()->id();
        
        $assignedUsers = $validated['assigned_users'] ?? [];
        unset($validated['assigned_users']);

        $deal = \App\Models\CrmDeal::create($validated);
        
        // Attach assigned users
        if (!empty($assignedUsers)) {
            $deal->assignedUsers()->attach($assignedUsers);
        }
        
        return redirect()->route('crm')->with('success', 'Szansa sprzedażowa została dodana.');
    }

    public function getDeal($id)
    {
        $deal = \App\Models\CrmDeal::with('assignedUsers')->findOrFail($id);
        return response()->json($deal);
    }

    public function updateDeal(Request $request, $id)
    {
        $deal = \App\Models\CrmDeal::findOrFail($id);
        
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'company_id' => 'nullable|exists:crm_companies,id',
            'value' => 'required|numeric|min:0',
            'currency' => 'nullable|string|max:10',
            'stage' => 'required|in:nowy_lead,kontakt,wycena,negocjacje,wygrana,przegrana',
            'probability' => 'required|integer|min:0|max:100',
            'expected_close_date' => 'nullable|date',
            'actual_close_date' => 'nullable|date',
            'owner_id' => 'nullable|exists:users,id',
            'description' => 'nullable|string',
            'lost_reason' => 'nullable|string',
            'assigned_users' => 'nullable|array',
            'assigned_users.*' => 'exists:users,id',
        ]);
        
        $assignedUsers = $validated['assigned_users'] ?? [];
        unset($validated['assigned_users']);
        
        // Automatycznie ustaw actual_close_date gdy stage zmienia się na "wygrana" lub "przegrana"
        if (in_array($validated['stage'], ['wygrana', 'przegrana']) && 
            !in_array($deal->stage, ['wygrana', 'przegrana']) && 
            empty($validated['actual_close_date'])) {
            $validated['actual_close_date'] = now();
        }

        $deal->update($validated);
        
        // Sync assigned users
        $deal->assignedUsers()->sync($assignedUsers);
        
        return redirect()->route('crm')->with('success', 'Szansa sprzedażowa została zaktualizowana.');
    }

    public function deleteDeal($id)
    {
        $deal = \App\Models\CrmDeal::findOrFail($id);
        $deal->delete();
        return redirect()->route('crm')->with('success', 'Szansa sprzedażowa została usunięta.');
    }

    // CRM Tasks
    public function addTask(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:telefon,email,spotkanie,zadanie,follow_up',
            'priority' => 'required|in:niska,normalna,wysoka,pilna',
            'status' => 'required|in:do_zrobienia,w_trakcie,zakonczone,anulowane',
            'due_date' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:crm_companies,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
        ]);

        $validated['created_by'] = auth()->id();

        \App\Models\CrmTask::create($validated);
        return redirect()->route('crm')->with('success', 'Zadanie zostało dodane.');
    }

    public function getTask($id)
    {
        $task = \App\Models\CrmTask::findOrFail($id);
        return response()->json($task);
    }

    public function updateTask(Request $request, $id)
    {
        $task = \App\Models\CrmTask::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'type' => 'required|in:telefon,email,spotkanie,zadanie,follow_up',
            'priority' => 'required|in:niska,normalna,wysoka,pilna',
            'status' => 'required|in:do_zrobienia,w_trakcie,zakonczone,anulowane',
            'due_date' => 'nullable|date',
            'completed_at' => 'nullable|date',
            'assigned_to' => 'nullable|exists:users,id',
            'company_id' => 'nullable|exists:crm_companies,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
        ]);

        if ($validated['status'] === 'zakonczone' && !$task->completed_at) {
            $validated['completed_at'] = now();
        }

        $task->update($validated);
        return redirect()->route('crm')->with('success', 'Zadanie zostało zaktualizowane.');
    }

    public function deleteTask($id)
    {
        $task = \App\Models\CrmTask::findOrFail($id);
        $task->delete();
        return redirect()->route('crm')->with('success', 'Zadanie zostało usunięte.');
    }

    // CRM Activities
    public function addActivity(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:telefon,email,spotkanie,notatka,sms,oferta,umowa,faktura,reklamacja',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'required|date',
            'duration' => 'nullable|integer|min:0',
            'outcome' => 'nullable|in:pozytywny,neutralny,negatywny,brak_odpowiedzi',
            'company_id' => 'nullable|exists:crm_companies,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
        ]);

        $validated['user_id'] = auth()->id();

        \App\Models\CrmActivity::create($validated);
        return redirect()->route('crm')->with('success', 'Aktywność została dodana.');
    }

    public function getActivity($id)
    {
        $activity = \App\Models\CrmActivity::findOrFail($id);
        return response()->json($activity);
    }

    public function updateActivity(Request $request, $id)
    {
        $activity = \App\Models\CrmActivity::findOrFail($id);
        
        $validated = $request->validate([
            'type' => 'required|in:telefon,email,spotkanie,notatka,sms,oferta,umowa,faktura,reklamacja',
            'subject' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'required|date',
            'duration' => 'nullable|integer|min:0',
            'outcome' => 'nullable|in:pozytywny,neutralny,negatywny,brak_odpowiedzi',
            'company_id' => 'nullable|exists:crm_companies,id',
            'deal_id' => 'nullable|exists:crm_deals,id',
        ]);

        $activity->update($validated);
        return redirect()->route('crm')->with('success', 'Aktywność została zaktualizowana.');
    }

    public function deleteActivity($id)
    {
        $activity = \App\Models\CrmActivity::findOrFail($id);
        $activity->delete();
        return redirect()->route('crm')->with('success', 'Aktywność została usunięta.');
    }

    // Dodaj firmę CRM do bazy dostawców/klientów
    public function addCrmCompanyToSuppliers($id)
    {
        $crmCompany = \App\Models\CrmCompany::findOrFail($id);
        
        // Sprawdź czy firma już nie istnieje w dostawcach
        $existingSupplier = \App\Models\Supplier::where('nip', $crmCompany->nip)
            ->orWhere('name', $crmCompany->name)
            ->first();
        
        if ($existingSupplier) {
            $crmCompany->supplier_id = $existingSupplier->id;
            $crmCompany->save();
            return redirect()->route('crm')->with('success', 'Firma została powiązana z istniejącym dostawcą/klientem.');
        }
        
        // Stwórz nowego dostawcę/klienta
        $supplier = \App\Models\Supplier::create([
            'name' => $crmCompany->name,
            'short_name' => $this->generateShortName($crmCompany->name),
            'nip' => $crmCompany->nip,
            'email' => $crmCompany->email,
            'phone' => $crmCompany->phone,
            'address' => $crmCompany->address,
            'city' => $crmCompany->city,
            'postal_code' => $crmCompany->postal_code,
            'is_supplier' => false,
            'is_client' => true,
        ]);
        
        $crmCompany->supplier_id = $supplier->id;
        $crmCompany->save();
        
        return redirect()->route('crm')->with('success', 'Firma została dodana do bazy dostawców/klientów.');
    }
    
    private function generateShortName($name)
    {
        $words = explode(' ', $name);
        if (count($words) >= 2) {
            return strtolower(substr($words[0], 0, 3) . substr($words[1], 0, 3));
        }
        return strtolower(substr($name, 0, 6));
    }

    // Zarządzanie etapami CRM
    public function addCrmStage(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:crm_stages',
            'color' => 'required|string|max:50',
            'order' => 'required|integer|min:0',
        ]);
        
        \DB::table('crm_stages')->insert([
            'name' => $validated['name'],
            'slug' => $validated['slug'],
            'color' => $validated['color'],
            'order' => $validated['order'],
            'is_active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        
        return redirect()->route('crm.settings')->with('success', 'Etap został dodany.');
    }
    
    public function updateCrmStage(Request $request, $id)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'color' => 'required|string|max:50',
            'order' => 'required|integer|min:0',
            'is_active' => 'nullable|boolean',
        ]);
        
        \DB::table('crm_stages')->where('id', $id)->update([
            'name' => $validated['name'],
            'color' => $validated['color'],
            'order' => $validated['order'],
            'is_active' => $request->has('is_active') ? 1 : 0,
            'updated_at' => now(),
        ]);
        
        return redirect()->route('crm.settings')->with('success', 'Etap został zaktualizowany.');
    }
    
    public function getCrmStage($id)
    {
        $stage = \DB::table('crm_stages')->where('id', $id)->first();
        return response()->json($stage);
    }
    
    public function deleteCrmStage($id)
    {
        $stage = \DB::table('crm_stages')->where('id', $id)->first();
        
        if (in_array($stage->slug, ['wygrana', 'przegrana'])) {
            return redirect()->route('crm')->with('error', 'Nie można usunąć domyślnych etapów.');
        }
        
        \DB::table('crm_stages')->where('id', $id)->delete();
        return redirect()->route('crm.settings')->with('success', 'Etap został usunięty.');
    }
}
