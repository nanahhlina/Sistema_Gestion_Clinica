<?php

use App\Modules\Acceso\Controllers\AuthController;
use App\Modules\Auditoria\Controllers\AuditController;
use App\Modules\Catalogos\Controllers\CatalogController;
use App\Modules\Inicio\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('/ingresar', [AuthController::class, 'show'])->name('login');
    Route::post('/ingresar', [AuthController::class, 'login'])->middleware('throttle:20,1');
});

Route::middleware(['auth', 'active'])->group(function (): void {
    Route::get('/', [HomeController::class, 'index'])->name('home');
    Route::post('/salir', [AuthController::class, 'logout'])->name('logout');

    Route::get('/gestion/{module}', [CatalogController::class, 'index'])->name('catalog.index');
    Route::get('/gestion/{module}/nuevo', [CatalogController::class, 'create'])->name('catalog.create');
    Route::post('/gestion/{module}', [CatalogController::class, 'store'])->name('catalog.store');
    Route::get('/gestion/{module}/{id}/editar', [CatalogController::class, 'edit'])->whereNumber('id')->name('catalog.edit');
    Route::match(['put', 'post'], '/gestion/{module}/{id}', [CatalogController::class, 'update'])->whereNumber('id')->name('catalog.update');

    Route::get('/bitacora', [AuditController::class, 'index'])->name('auditoria.index');
});
