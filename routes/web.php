<?php
// PODSTRONY WYCEN I OFERT
Route::middleware('auth')->get('/wyceny/portfolio', function () {
    try {
        if (class_exists('\App\Models\CrmDeal')) {
            $offers = \App\Models\Offer::with('crmDeal.company')->where('status', 'portfolio')->orderBy('created_at', 'desc')->get();
        } else {
            $offers = \App\Models\Offer::where('status', 'portfolio')->orderBy('created_at', 'desc')->get();
        }
    } catch (\Exception $e) {
        $offers = \App\Models\Offer::where('status', 'portfolio')->orderBy('created_at', 'desc')->get();
    }
    return view('offers-portfolio', compact('offers'));
})->name('offers.portfolio');
Route::middleware('auth')->get('/wyceny/nowa', function (Illuminate\Http\Request $request) {
    $dealId = $request->input('deal_id');
    $deal = null;
    
    try {
        if ($dealId && class_exists('\App\Models\CrmDeal')) {
            $deal = \App\Models\CrmDeal::with(['company.supplier'])->find($dealId);
        }
    } catch (\Exception $e) {
        // CRM tables might not exist yet
        \Log::warning('CRM deal not found or tables not migrated: ' . $e->getMessage());
    }
    
    $companies = [];
    try {
        if (class_exists('\App\Models\CrmCompany')) {
            $companies = \App\Models\CrmCompany::with('supplier')->orderBy('name')->get();
        }
    } catch (\Exception $e) {
        // CRM tables might not exist yet
        \Log::warning('CRM companies not found or tables not migrated: ' . $e->getMessage());
    }
    
    return view('offers-new', ['deal' => $deal, 'companies' => $companies]);
})->name('offers.new');

Route::middleware('auth')->get('/wyceny/ustawienia', function () {
    return view('offers-settings');
})->name('offers.settings');

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
    // Pobierz numer oferty z formularza
    $offerNumber = $request->input('offer_number');
    
    // Sprawdź czy taki numer już istnieje
    $existingOffer = \App\Models\Offer::where('offer_number', $offerNumber)->first();
    if ($existingOffer) {
        return redirect()->back()
            ->withInput()
            ->withErrors(['offer_number' => 'Oferta o numerze "' . $offerNumber . '" już istnieje. Zmień numer oferty.']);
    }
    
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
        'offer_number' => $offerNumber,
        'offer_title' => $request->input('offer_title'),
        'offer_date' => $request->input('offer_date'),
        'offer_description' => $request->input('offer_description'),
        'services' => $services,
        'works' => $works,
        'materials' => $materials,
        'custom_sections' => $customSections,
        'total_price' => $totalPrice,
        'status' => $request->input('destination'),
        'crm_deal_id' => $request->input('crm_deal_id'),
        'customer_name' => $request->input('customer_name'),
        'customer_nip' => $request->input('customer_nip'),
        'customer_address' => $request->input('customer_address'),
        'customer_city' => $request->input('customer_city'),
        'customer_postal_code' => $request->input('customer_postal_code'),
        'customer_phone' => $request->input('customer_phone'),
        'customer_email' => $request->input('customer_email')
    ]);
    
    $destination = $request->input('destination');
    $routeName = $destination === 'portfolio' ? 'offers.portfolio' : 'offers.inprogress';
    
    return redirect()->route($routeName)->with('success', 'Oferta została zapisana pomyślnie!');
})->name('offers.store');
Route::middleware('auth')->get('/wyceny/w-toku', function () {
    try {
        if (class_exists('\App\Models\CrmDeal')) {
            $offers = \App\Models\Offer::with('crmDeal.company')->where('status', 'inprogress')->orderBy('created_at', 'desc')->get();
        } else {
            $offers = \App\Models\Offer::where('status', 'inprogress')->orderBy('created_at', 'desc')->get();
        }
    } catch (\Exception $e) {
        $offers = \App\Models\Offer::where('status', 'inprogress')->orderBy('created_at', 'desc')->get();
    }
    return view('offers-inprogress', compact('offers'));
})->name('offers.inprogress');
Route::middleware('auth')->get('/wyceny/zarchiwizowane', function () {
    try {
        if (class_exists('\App\Models\CrmDeal')) {
            $offers = \App\Models\Offer::with('crmDeal.company')->where('status', 'archived')->orderBy('created_at', 'desc')->get();
        } else {
            $offers = \App\Models\Offer::where('status', 'archived')->orderBy('created_at', 'desc')->get();
        }
    } catch (\Exception $e) {
        $offers = \App\Models\Offer::where('status', 'archived')->orderBy('created_at', 'desc')->get();
    }
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
    $newOffer->offer_number = app(\App\Http\Controllers\PartController::class)->generateOfferNumber();
    $newOffer->offer_title = $offer->offer_title . '_kopia';
    $newOffer->offer_date = now();
    $newOffer->save();
    
    return redirect()->back()->with('success', 'Oferta została skopiowana.');
})->name('offers.copy');

Route::middleware('auth')->get('/wyceny/{offer}/edit', function (\App\Models\Offer $offer) {
    try {
        if (class_exists('\App\Models\CrmDeal')) {
            $offer->load('crmDeal.company');
        }
    } catch (\Exception $e) {
        // CRM tables might not exist yet
        \Log::warning('CRM deal relation not loaded: ' . $e->getMessage());
    }
    
    $companies = [];
    try {
        if (class_exists('\App\Models\CrmCompany')) {
            $companies = \App\Models\CrmCompany::orderBy('name')->get();
        }
    } catch (\Exception $e) {
        // CRM tables might not exist yet
        \Log::warning('CRM companies not found: ' . $e->getMessage());
    }
    
    return view('offers-edit', compact('offer', 'companies'));
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
        'status' => $request->input('destination'),
        'crm_deal_id' => $request->input('crm_deal_id'),
        'customer_name' => $request->input('customer_name'),
        'customer_nip' => $request->input('customer_nip'),
        'customer_address' => $request->input('customer_address'),
        'customer_city' => $request->input('customer_city'),
        'customer_postal_code' => $request->input('customer_postal_code'),
        'customer_phone' => $request->input('customer_phone'),
        'customer_email' => $request->input('customer_email')
    ]);
    
    $destination = $request->input('destination');
    $routeName = $destination === 'portfolio' ? 'offers.portfolio' : 'offers.inprogress';
    
    return redirect()->route($routeName)->with('success', 'Oferta została zaktualizowana pomyślnie!');
})->name('offers.update');

// WYCENY I OFERTY
Route::middleware('auth')->get('/wyceny', function () {
    return view('offers');
})->name('offers');

// RECEPTURY - dla użytkowników z uprawnieniem lub superadmina
Route::middleware('auth')->get('/receptury', function () {
    // Walidacja uprawnień
    if (!auth()->user()->can_view_recipes && auth()->user()->email !== 'proximalumine@gmail.com') {
        abort(403);
    }
    return view('receptury');
})->name('receptury');

// RECEPTURY - trasy
Route::middleware(['auth'])->prefix('receptury')->group(function () {
    // Katalog składników
    Route::get('/skladniki', [App\Http\Controllers\RecipeController::class, 'ingredientsIndex'])->name('recipes.ingredients');
    Route::post('/skladniki', [App\Http\Controllers\RecipeController::class, 'ingredientStore'])->name('recipes.ingredients.store');
    Route::put('/skladniki/{ingredient}', [App\Http\Controllers\RecipeController::class, 'ingredientUpdate'])->name('recipes.ingredients.update');
    Route::delete('/skladniki/{ingredient}', [App\Http\Controllers\RecipeController::class, 'ingredientDestroy'])->name('recipes.ingredients.destroy');
    
    // Receptury CRUD
    Route::get('/lista', [App\Http\Controllers\RecipeController::class, 'index'])->name('recipes.index');
    Route::get('/nowa', [App\Http\Controllers\RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/nowa', [App\Http\Controllers\RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/{recipe}/edytuj', [App\Http\Controllers\RecipeController::class, 'edit'])->name('recipes.edit');
    Route::put('/{recipe}', [App\Http\Controllers\RecipeController::class, 'update'])->name('recipes.update');
    Route::delete('/{recipe}', [App\Http\Controllers\RecipeController::class, 'destroy'])->name('recipes.destroy');
    
    // Realizacja receptury
    Route::post('/{recipe}/rozpocznij', [App\Http\Controllers\RecipeController::class, 'startExecution'])->name('recipes.start');
    Route::get('/realizacja/{execution}', [App\Http\Controllers\RecipeController::class, 'execute'])->name('recipes.execute');
    Route::post('/realizacja/{execution}/potwierdz-krok', [App\Http\Controllers\RecipeController::class, 'confirmStep'])->name('recipes.confirmStep');
});


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
    Route::post('/wyceny/ustawienia', [PartController::class, 'saveOfferSettings'])->name('offers.settings.save')->middleware('permission:settings');
    Route::post('/wyceny/ustawienia/upload-template', [PartController::class, 'uploadOfferTemplate'])->name('offers.settings.upload-template')->middleware('permission:settings');
    Route::delete('/wyceny/ustawienia/delete-template', [PartController::class, 'deleteOfferTemplate'])->name('offers.settings.delete-template')->middleware('permission:settings');
    Route::post('/parts/generate-qr', [PartController::class, 'generateQrCode'])->name('parts.generateQr')->middleware('permission:add');
    Route::post('/parts/find-by-qr', [PartController::class, 'findByQr'])->name('parts.findByQr')->middleware('permission:add');
    Route::post('/parts/import-excel', [PartController::class, 'importExcel'])->name('parts.importExcel')->middleware('permission:add');
    Route::delete('/magazyn/parts/bulk-delete', [PartController::class, 'bulkDelete'])->name('magazyn.parts.bulkDelete')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update-price', [PartController::class, 'updatePrice'])->name('magazyn.parts.updatePrice')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update', [PartController::class, 'updatePart'])->name('magazyn.parts.update')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update-location', [PartController::class, 'updateLocation'])->name('magazyn.parts.updateLocation')->middleware('permission:view_catalog');

    // CRM Routes (Main Catalog)
    Route::middleware('auth')->group(function () {
        Route::get('/crm', [PartController::class, 'crmView'])->name('crm');
        Route::get('/crm/ustawienia', [PartController::class, 'crmSettingsView'])->name('crm.settings');
        
        // Test page for NIP search
        Route::get('/test-nip', function () {
            return view('test-nip');
        })->name('test.nip');
        
        // Old interaction routes (deprecated, kept for backwards compatibility)
        Route::post('/crm/interaction', [PartController::class, 'addCrmInteraction'])->name('crm.addInteraction');
        Route::put('/crm/interaction/{interaction}', [PartController::class, 'updateCrmInteraction'])->name('crm.updateInteraction');
        Route::delete('/crm/interaction/{interaction}', [PartController::class, 'deleteCrmInteraction'])->name('crm.deleteInteraction');
        
        // Companies
        Route::get('/crm/company/search-by-nip', [PartController::class, 'searchCompanyByNip'])->name('crm.company.searchByNip');
        Route::get('/crm/company/{id}/edit', [PartController::class, 'getCompany'])->name('crm.company.edit');
        Route::post('/crm/company', [PartController::class, 'addCompany'])->name('crm.company.add');
        Route::put('/crm/company/{id}', [PartController::class, 'updateCompany'])->name('crm.company.update');
        Route::delete('/crm/company/{id}', [PartController::class, 'deleteCompany'])->name('crm.company.delete');
        
        // Deals
        Route::get('/crm/deal/{id}/edit', [PartController::class, 'getDeal'])->name('crm.deal.edit');
        Route::post('/crm/deal', [PartController::class, 'addDeal'])->name('crm.deal.add');
        Route::put('/crm/deal/{id}', [PartController::class, 'updateDeal'])->name('crm.deal.update');
        Route::delete('/crm/deal/{id}', [PartController::class, 'deleteDeal'])->name('crm.deal.delete');
        
        // Tasks
        Route::get('/crm/task/{id}/edit', [PartController::class, 'getTask'])->name('crm.task.edit');
        Route::post('/crm/task', [PartController::class, 'addTask'])->name('crm.task.add');
        Route::put('/crm/task/{id}', [PartController::class, 'updateTask'])->name('crm.task.update');
        Route::delete('/crm/task/{id}', [PartController::class, 'deleteTask'])->name('crm.task.delete');
        
        // Activities
        Route::get('/crm/activity/{id}/edit', [PartController::class, 'getActivity'])->name('crm.activity.edit');
        
        // Activities
        Route::post('/crm/activity', [PartController::class, 'addActivity'])->name('crm.activity.add');
        Route::put('/crm/activity/{id}', [PartController::class, 'updateActivity'])->name('crm.activity.update');
        Route::delete('/crm/activity/{id}', [PartController::class, 'deleteActivity'])->name('crm.activity.delete');
        
        // CRM Company to Suppliers
        Route::post('/crm/company/{id}/add-to-suppliers', [PartController::class, 'addCrmCompanyToSuppliers'])->name('crm.company.addToSuppliers');
        
        // CRM Stages
        Route::get('/crm/stage/{id}/edit', [PartController::class, 'getCrmStage'])->name('crm.stage.edit');
        Route::post('/crm/stage', [PartController::class, 'addCrmStage'])->name('crm.stage.add');
        Route::put('/crm/stage/{id}', [PartController::class, 'updateCrmStage'])->name('crm.stage.update');
        Route::delete('/crm/stage/{id}', [PartController::class, 'deleteCrmStage'])->name('crm.stage.delete');
        
        // CRM Customer Types
        Route::get('/crm/customer-types/{id}', [\App\Http\Controllers\CrmCustomerTypeController::class, 'show'])->name('crm.customer-types.show');
        Route::post('/crm/customer-types', [\App\Http\Controllers\CrmCustomerTypeController::class, 'store'])->name('crm.customer-types.store');
        Route::put('/crm/customer-types/{id}', [\App\Http\Controllers\CrmCustomerTypeController::class, 'update'])->name('crm.customer-types.update');
        Route::delete('/crm/customer-types/{id}', [\App\Http\Controllers\CrmCustomerTypeController::class, 'destroy'])->name('crm.customer-types.destroy');
    });
    
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