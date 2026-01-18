<?php
// PODSTRONY WYCEN I OFERT
Route::middleware('auth')->get('/wyceny/portfolio', function () {
    $offers = \App\Models\Offer::where('status', 'portfolio')->orderBy('created_at', 'desc')->get();
    return view('offers-portfolio', compact('offers'));
})->name('offers.portfolio');
Route::middleware('auth')->get('/wyceny/nowa', function () {
    return view('offers-new');
})->name('offers.new');

// API endpoint do wyszukiwania części
Route::middleware('auth')->get('/api/parts/search', function (Illuminate\Http\Request $request) {
    $query = $request->input('q', '');
    
    if (strlen($query) < 2) {
        return response()->json([]);
    }
    
    $parts = \App\Models\Part::where('name', 'LIKE', "%{$query}%")
        ->orWhere('description', 'LIKE', "%{$query}%")
        ->limit(20)
        ->get(['id', 'name', 'description', 'net_price', 'supplier', 'quantity']);
    
    return response()->json($parts);
})->name('api.parts.search');

// API endpoint do pobierania wszystkich części (katalog)
Route::middleware('auth')->get('/api/parts/catalog', function () {
    $parts = \App\Models\Part::with('category')
        ->orderBy('name')
        ->get(['id', 'name', 'description', 'net_price', 'supplier', 'quantity', 'category_id']);
    
    return response()->json($parts);
})->name('api.parts.catalog');
Route::middleware('auth')->post('/wyceny/nowa', function (Illuminate\Http\Request $request) {
    // Oblicz całkowitą cenę
    $totalPrice = 0;
    
    $services = collect($request->input('services', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $works = collect($request->input('works', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $materials = collect($request->input('materials', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $customSections = $request->input('custom_sections', []);
    
    foreach ($services as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    foreach ($works as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    foreach ($materials as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    
    // Dodaj ceny z niestandardowych sekcji
    foreach ($customSections as &$section) {
        if (isset($section['items']) && is_array($section['items'])) {
            foreach ($section['items'] as &$item) {
                $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
                $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
            }
        }
    }
    
    // Zapisz ofertę
    \App\Models\Offer::create([
        'offer_number' => $request->input('offer_number'),
        'offer_title' => $request->input('offer_title'),
        'offer_date' => $request->input('offer_date'),
        'offer_description' => $request->input('offer_description'),
        'services' => $services,
        'works' => $works,
        'materials' => $materials,
        'custom_sections' => $customSections,
        'total_price' => $totalPrice,
        'status' => $request->input('destination')
    ]);
    
    $destination = $request->input('destination');
    $routeName = $destination === 'portfolio' ? 'offers.portfolio' : 'offers.inprogress';
    
    return redirect()->route($routeName)->with('success', 'Oferta została zapisana pomyślnie!');
})->name('offers.store');
Route::middleware('auth')->get('/wyceny/w-toku', function () {
    $offers = \App\Models\Offer::where('status', 'inprogress')->orderBy('created_at', 'desc')->get();
    return view('offers-inprogress', compact('offers'));
})->name('offers.inprogress');
Route::middleware('auth')->get('/wyceny/zarchiwizowane', function () {
    $offers = \App\Models\Offer::where('status', 'archived')->orderBy('created_at', 'desc')->get();
    return view('offers-archived', compact('offers'));
})->name('offers.archived');

// Akcje na ofertach
Route::middleware('auth')->post('/wyceny/{offer}/archive', function (\App\Models\Offer $offer) {
    $offer->update(['status' => 'archived']);
    return redirect()->back()->with('success', 'Oferta przeniesiona do archiwum.');
})->name('offers.archive');

Route::middleware('auth')->post('/wyceny/{offer}/convert-to-project', function (\App\Models\Offer $offer) {
    // Zmień "OF" na "PROJ" w numerze oferty
    $projectNumber = str_replace('OF', 'PROJ', $offer->offer_number);
    
    // Utwórz projekt na podstawie oferty
    $project = \App\Models\Project::create([
        'project_number' => $projectNumber,
        'name' => $offer->offer_title,
        'budget' => $offer->total_price,
        'responsible_user_id' => auth()->id(),
        'status' => 'in_progress',
        'started_at' => now(),
    ]);
    
    return redirect()->route('magazyn.projects.show', $project)
        ->with('success', 'Projekt został utworzony z oferty! Oferta pozostała w ' . ($offer->status === 'portfolio' ? 'portfolio' : 'ofertach w toku') . '.');
})->name('offers.convertToProject');

Route::middleware('auth')->post('/wyceny/{offer}/copy', function (\App\Models\Offer $offer) {
    $newOffer = $offer->replicate();
    $newOffer->offer_title = $offer->offer_title . '_kopia';
    $newOffer->offer_date = now();
    $newOffer->save();
    
    return redirect()->back()->with('success', 'Oferta została skopiowana.');
})->name('offers.copy');

Route::middleware('auth')->get('/wyceny/{offer}/edit', function (\App\Models\Offer $offer) {
    return view('offers-edit', compact('offer'));
})->name('offers.edit');

Route::middleware('auth')->put('/wyceny/{offer}', function (Illuminate\Http\Request $request, \App\Models\Offer $offer) {
    // Oblicz całkowitą cenę
    $totalPrice = 0;
    
    $services = collect($request->input('services', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $works = collect($request->input('works', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $materials = collect($request->input('materials', []))
        ->filter(fn($item) => !empty($item['price']))
        ->map(function($item) {
            $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
            return $item;
        })->toArray();
    $customSections = $request->input('custom_sections', []);
    
    foreach ($services as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    foreach ($works as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    foreach ($materials as $item) {
        $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
    }
    
    // Dodaj ceny z niestandardowych sekcji
    foreach ($customSections as &$section) {
        if (isset($section['items']) && is_array($section['items'])) {
            foreach ($section['items'] as &$item) {
                $item['quantity'] = isset($item['quantity']) && $item['quantity'] !== '' ? (int)$item['quantity'] : 1;
                $totalPrice += (floatval($item['price'] ?? 0)) * (intval($item['quantity'] ?? 1));
            }
        }
    }
    
    // Zaktualizuj ofertę
    $offer->update([
        'offer_number' => $request->input('offer_number'),
        'offer_title' => $request->input('offer_title'),
        'offer_date' => $request->input('offer_date'),
        'offer_description' => $request->input('offer_description'),
        'services' => $services,
        'works' => $works,
        'materials' => $materials,
        'custom_sections' => $customSections,
        'total_price' => $totalPrice,
        'status' => $request->input('destination')
    ]);
    
    $destination = $request->input('destination');
    $routeName = $destination === 'portfolio' ? 'offers.portfolio' : 'offers.inprogress';
    
    return redirect()->route($routeName)->with('success', 'Oferta została zaktualizowana pomyślnie!');
})->name('offers.update');

// WYCENY I OFERTY
Route::middleware('auth')->get('/wyceny', function () {
    return view('offers');
})->name('offers');

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartController;
use App\Http\Controllers\AuthController;

// Generowanie dokumentów dla ofert
Route::middleware('auth')->get('/wyceny/{offer}/generate-word', [PartController::class, 'generateOfferWord'])->name('offers.generateWord');

// TEST ENDPOINT
Route::get('/test', function () {
    return response()->json([
        'status' => 'OK',
        'message' => 'Laravel działa!',
        'env' => config('app.env'),
        'debug' => config('app.debug'),
        'db' => DB::connection()->getPdo() ? 'DB connected' : 'DB failed'
    ]);
});

// STRONA STARTOWA
Route::get('/', function () {
    return view('welcome');
});

// LOGOWANIE
Route::get('/login', [AuthController::class, 'loginView'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

// MAGAZYN - CHRONIONE TRASAMI
Route::middleware('auth')->group(function () {
    Route::get('/magazyn/dodaj', [PartController::class, 'addView'])->name('magazyn.add')->middleware('permission:add');
    Route::get('/magazyn/pobierz', [PartController::class, 'removeView'])->name('magazyn.remove')->middleware('permission:remove');
    Route::get('/magazyn/sprawdz', [PartController::class, 'checkView'])->name('magazyn.check')->middleware('permission:view_catalog');
    Route::get('/magazyn/zamowienia', [PartController::class, 'ordersView'])->name('magazyn.orders')->middleware('permission:orders');
    Route::get('/magazyn/ustawienia', [PartController::class, 'settingsView'])->name('magazyn.settings')->middleware('permission:settings');
    Route::get('/magazyn/sprawdz/eksport', [PartController::class, 'export'])->name('magazyn.check.export')->middleware('permission:view_catalog');
    Route::get('/magazyn/sprawdz/eksport-xlsx', [PartController::class, 'exportXlsx'])->name('magazyn.check.export.xlsx')->middleware('permission:view_catalog');
    Route::get('/magazyn/sprawdz/eksport-word', [PartController::class, 'exportWord'])->name('magazyn.check.export.word')->middleware('permission:view_catalog');

    // AKCJE
    Route::post('/parts/add', [PartController::class, 'add'])->name('parts.add')->middleware('permission:add');
    Route::post('/parts/remove', [PartController::class, 'remove'])->name('parts.remove')->middleware('permission:remove');
    Route::post('/magazyn/ustawienia/kategoria', [PartController::class, 'addCategory'])->name('magazyn.category.add')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/kategoria/{category}', [PartController::class, 'updateCategory'])->name('magazyn.category.update')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/kategoria/{category}', [PartController::class, 'deleteCategory'])->name('magazyn.category.delete')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/kategoria/{category}/clear', [PartController::class, 'clearCategoryContents'])->name('magazyn.category.clearContents')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/user', [PartController::class, 'addUser'])->name('magazyn.user.add')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/user/{user}/edit', [PartController::class, 'editUserView'])->name('magazyn.user.edit')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/user/{user}', [PartController::class, 'updateUser'])->name('magazyn.user.update')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/user/{user}/toggle-admin', [PartController::class, 'toggleAdmin'])->name('magazyn.user.toggleAdmin')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/user/{user}', [PartController::class, 'deleteUser'])->name('magazyn.user.delete')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/supplier', [PartController::class, 'addSupplier'])->name('magazyn.supplier.add')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/supplier/{supplier}', [PartController::class, 'updateSupplier'])->name('magazyn.supplier.update')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/supplier/{supplier}', [PartController::class, 'deleteSupplier'])->name('magazyn.supplier.delete')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/supplier/fetch-by-nip', [PartController::class, 'fetchSupplierByNip'])->name('magazyn.supplier.fetchByNip')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/company/fetch-by-nip', [PartController::class, 'fetchCompanyByNip'])->name('magazyn.company.fetchByNip')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/company', [PartController::class, 'saveCompanySettings'])->name('magazyn.company.save')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/order-settings', [PartController::class, 'saveOrderSettings'])->name('magazyn.order-settings.save')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/qr-settings', [PartController::class, 'saveQrSettings'])->name('magazyn.qr-settings.save')->middleware('permission:settings');
    Route::post('/parts/generate-qr', [PartController::class, 'generateQrCode'])->name('parts.generateQr')->middleware('permission:add');
    Route::post('/parts/find-by-qr', [PartController::class, 'findByQr'])->name('parts.findByQr')->middleware('permission:add');
    Route::post('/parts/import-excel', [PartController::class, 'importExcel'])->name('parts.importExcel')->middleware('permission:add');
    Route::delete('/magazyn/parts/bulk-delete', [PartController::class, 'bulkDelete'])->name('magazyn.parts.bulkDelete')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update-price', [PartController::class, 'updatePrice'])->name('magazyn.parts.updatePrice')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update', [PartController::class, 'updatePart'])->name('magazyn.parts.update')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update-location', [PartController::class, 'updateLocation'])->name('magazyn.parts.updateLocation')->middleware('permission:view_catalog');
    Route::post('/magazyn/zamowienia/create', [PartController::class, 'createOrder'])->name('magazyn.order.create')->middleware('permission:orders');
    Route::get('/magazyn/zamowienia/{order}/generate-word', [PartController::class, 'generateOrderWord'])->name('magazyn.order.generateWord')->middleware('permission:orders');
    Route::get('/magazyn/zamowienia/{order}/generate-pdf', [PartController::class, 'generateOrderPdf'])->name('magazyn.order.generatePdf')->middleware('permission:orders');
    Route::get('/magazyn/zamowienia/next-name', [PartController::class, 'getNextOrderName'])->name('magazyn.order.nextName')->middleware('permission:orders');
    Route::delete('/magazyn/zamowienia/{order}', [PartController::class, 'deleteOrder'])->name('magazyn.order.delete')->middleware('permission:orders');
    Route::post('/magazyn/zamowienia/delete-multiple', [PartController::class, 'deleteMultipleOrders'])->name('magazyn.order.deleteMultiple')->middleware('permission:orders');
    Route::post('/magazyn/zamowienia/{order}/receive', [PartController::class, 'receiveOrder'])->name('magazyn.order.receive')->middleware('permission:orders');

    Route::delete('/parts/{part}', [PartController::class, 'destroy'])->name('parts.destroy')->middleware('permission:settings');
    Route::post('/parts/preview', [PartController::class, 'preview'])->name('parts.preview')->middleware('permission:view_catalog');
    Route::post('/parts/search-similar', [PartController::class, 'searchSimilar'])->name('parts.searchSimilar')->middleware('permission:view_catalog');
    Route::post('/parts/clear-session', [PartController::class, 'clearSession'])->name('parts.clearSession');
    Route::post('/parts/delete-selected-history', [PartController::class, 'deleteSelectedHistory'])->name('parts.deleteSelectedHistory')->middleware('permission:remove');

    Route::get('/magazyn/projekty', [PartController::class, 'projectsView'])->name('magazyn.projects')->middleware('auth');
    Route::post('/magazyn/projekty', [PartController::class, 'storeProject'])->name('magazyn.projects.store')->middleware('auth');
    Route::get('/magazyn/projekty/{project}', [PartController::class, 'showProject'])->name('magazyn.projects.show')->middleware('auth');
    Route::get('/magazyn/projekty/{project}/edit', [PartController::class, 'editProject'])->name('magazyn.editProject')->middleware('auth');
    Route::put('/magazyn/projekty/{project}', [PartController::class, 'updateProject'])->name('magazyn.updateProject')->middleware('auth');
    Route::get('/magazyn/projects/{project}/removal-dates', [PartController::class, 'getRemovalDates'])->name('magazyn.projects.removal-dates')->middleware('auth');
    Route::post('/magazyn/projekty/{project}/return/{removal}', [PartController::class, 'returnProduct'])->name('magazyn.returnProduct')->middleware('auth');
    Route::post('/magazyn/projekty/{project}/finish', [PartController::class, 'finishProject'])->name('magazyn.finishProject')->middleware('auth');
});