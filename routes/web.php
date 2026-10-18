<?php

use App\Http\Controllers\Admin as Admin;
use App\Http\Controllers\Company as Company;
use App\Http\Controllers\Employee as Employee;
use App\Http\Controllers\TicketController;
use App\MyApp;
use Illuminate\Support\Facades\Route;

/*
| Tres paneles, cada uno con su guard y su login (Fortify, ver FortifyServiceProvider):
|   /admin/login    → administrador del sistema: registra las empresas
|   /company/login  → empresa (dueño de la tienda): catálogo, stock, ventas, cajas, fiados y empleados
|   /employee/login → empleado / cajero: abre caja, vende, cobra fiados, registra mercadería y cierra caja
*/

Route::get('/', function () {
    return view('welcome');
});

Route::prefix(MyApp::ADMINS_SUBDIR)->middleware('auth:admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.home');
    })->withoutMiddleware('auth:admin');

    Route::get('/home', [Admin\AdminController::class, 'index'])->name('home');

    Route::resource('companies', Admin\CompanyController::class)->except(['show', 'destroy']);
});

Route::prefix(MyApp::COMPANY_SUBDIR)->middleware('auth:company')->name('company.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('company.home');
    })->withoutMiddleware('auth:company');

    Route::get('/home', [Company\CompanyController::class, 'index'])->name('home');

    // Catálogo
    Route::resource('categories', Company\CategoryController::class)->except(['create', 'show', 'edit']);
    Route::get('products/{product}/history', [Company\ProductController::class, 'history'])->name('products.history');
    Route::resource('products', Company\ProductController::class)->except(['show']);

    // Llegó mercadería
    Route::get('purchases', [Company\PurchaseController::class, 'index'])->name('purchases.index');
    Route::get('purchases/create', [Company\PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [Company\PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [Company\PurchaseController::class, 'show'])->name('purchases.show');

    // Ventas y cajas
    Route::get('sales', [Company\SaleController::class, 'index'])->name('sales.index');
    Route::get('sales/{sale}', [Company\SaleController::class, 'show'])->name('sales.show');
    Route::post('sales/{sale}/void', [Company\SaleController::class, 'void'])->name('sales.void');
    Route::get('sales/{sale}/ticket', [TicketController::class, 'company'])->name('sales.ticket');
    Route::get('cash-registers', [Company\CashRegisterController::class, 'index'])->name('cash-registers.index');
    Route::get('cash-registers/{cashRegister}', [Company\CashRegisterController::class, 'show'])->name('cash-registers.show');

    // Fiados
    Route::get('clients', [Company\ClientController::class, 'index'])->name('clients.index');
    Route::get('clients/{client}', [Company\ClientController::class, 'show'])->name('clients.show');
    Route::post('clients/{client}/pay', [Company\ClientController::class, 'pay'])->name('clients.pay');

    // Personal
    Route::resource('employees', Company\EmployeeController::class)->except(['show']);
});

Route::prefix(MyApp::EMPLOYEE_SUBDIR)->middleware('auth:employee')->name('employee.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('employee.home');
    })->withoutMiddleware('auth:employee');

    Route::get('/home', [Employee\EmployeeController::class, 'index'])->name('home');

    // Abrir caja (sin caja abierta)
    Route::get('cash/open', [Employee\CashRegisterController::class, 'create'])->name('cash.open');
    Route::post('cash/open', [Employee\CashRegisterController::class, 'store'])->name('cash.store');

    // Consultar precios y stock
    Route::get('products', [Employee\ProductController::class, 'index'])->name('products.index');

    // Llegó mercadería (puede pagarse con dinero de la caja)
    Route::get('purchases/create', [Employee\PurchaseController::class, 'create'])->name('purchases.create');
    Route::post('purchases', [Employee\PurchaseController::class, 'store'])->name('purchases.store');
    Route::get('purchases/{purchase}', [Employee\PurchaseController::class, 'show'])->name('purchases.show');

    // Ticket de una venta (también de cajas ya cerradas)
    Route::get('sales/{sale}/ticket', [TicketController::class, 'employee'])->name('sales.ticket');

    // Todo lo que mueve dinero necesita la caja abierta
    Route::middleware('cash.open')->group(function () {
        Route::get('pos', [Employee\PosController::class, 'index'])->name('pos');
        Route::post('sales', [Employee\PosController::class, 'store'])->name('sales.store');

        Route::get('sales', [Employee\SaleController::class, 'index'])->name('sales.index');
        Route::get('sales/{sale}', [Employee\SaleController::class, 'show'])->name('sales.show');
        Route::post('sales/{sale}/void', [Employee\SaleController::class, 'void'])->name('sales.void');

        Route::get('cash/movements', [Employee\CashMovementController::class, 'index'])->name('cash.movements');
        Route::post('cash/movements', [Employee\CashMovementController::class, 'store'])->name('cash.movements.store');

        Route::get('cash/close', [Employee\CashRegisterController::class, 'edit'])->name('cash.close');
        Route::post('cash/close', [Employee\CashRegisterController::class, 'update'])->name('cash.update');

        Route::get('clients/search', [Employee\ClientController::class, 'search'])->name('clients.search');
        Route::get('clients', [Employee\ClientController::class, 'index'])->name('clients.index');
        Route::get('clients/{client}', [Employee\ClientController::class, 'show'])->name('clients.show');
        Route::post('clients/{client}/pay', [Employee\ClientController::class, 'pay'])->name('clients.pay');
    });
});
