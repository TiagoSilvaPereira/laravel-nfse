<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoicePdfController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// Empresas
Route::apiResource('companies', CompanyController::class);

// Notas Fiscais
Route::post('nfse', [InvoiceController::class, 'store']);
Route::get('nfse/{invoice}', [InvoiceController::class, 'show']);
Route::get('nfse/{invoice}/pdf', [InvoicePdfController::class, 'show']);
