<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PartController;
use App\Http\Controllers\AuthController;

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
    Route::delete('/magazyn/ustawienia/kategoria/{category}', [PartController::class, 'deleteCategory'])->name('magazyn.category.delete')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/kategoria/{category}/clear', [PartController::class, 'clearCategoryContents'])->name('magazyn.category.clearContents')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/user', [PartController::class, 'addUser'])->name('magazyn.user.add')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/user/{user}/edit', [PartController::class, 'editUserView'])->name('magazyn.user.edit')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/user/{user}', [PartController::class, 'updateUser'])->name('magazyn.user.update')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/user/{user}', [PartController::class, 'deleteUser'])->name('magazyn.user.delete')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/supplier', [PartController::class, 'addSupplier'])->name('magazyn.supplier.add')->middleware('permission:settings');
    Route::put('/magazyn/ustawienia/supplier/{supplier}', [PartController::class, 'updateSupplier'])->name('magazyn.supplier.update')->middleware('permission:settings');
    Route::delete('/magazyn/ustawienia/supplier/{supplier}', [PartController::class, 'deleteSupplier'])->name('magazyn.supplier.delete')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/supplier/fetch-by-nip', [PartController::class, 'fetchSupplierByNip'])->name('magazyn.supplier.fetchByNip')->middleware('permission:settings');
    Route::get('/magazyn/ustawienia/company/fetch-by-nip', [PartController::class, 'fetchCompanyByNip'])->name('magazyn.company.fetchByNip')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/company', [PartController::class, 'saveCompanySettings'])->name('magazyn.company.save')->middleware('permission:settings');
    Route::post('/magazyn/ustawienia/order-settings', [PartController::class, 'saveOrderSettings'])->name('magazyn.order-settings.save')->middleware('permission:settings');
    Route::delete('/magazyn/parts/bulk-delete', [PartController::class, 'bulkDelete'])->name('magazyn.parts.bulkDelete')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update-price', [PartController::class, 'updatePrice'])->name('magazyn.parts.updatePrice')->middleware('permission:view_catalog');
    Route::put('/magazyn/parts/{part}/update', [PartController::class, 'updatePart'])->name('magazyn.parts.update')->middleware('permission:view_catalog');
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

    Route::get('/magazyn/projekty', [PartController::class, 'projectsView'])->name('magazyn.projects')->middleware('auth');
    Route::post('/magazyn/projekty', [PartController::class, 'storeProject'])->name('magazyn.projects.store')->middleware('auth');
    Route::get('/magazyn/projekty/{project}', [PartController::class, 'showProject'])->name('magazyn.projects.show')->middleware('auth');
    Route::get('/magazyn/projects/{project}/removal-dates', [PartController::class, 'getRemovalDates'])->name('magazyn.projects.removal-dates')->middleware('auth');
});