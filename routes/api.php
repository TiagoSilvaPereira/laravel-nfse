<?php

use App\Http\Controllers\Api\CompanyController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

Route::apiResource('companies', CompanyController::class);

Route::post('nfse', [App\Http\Controllers\Api\InvoiceController::class, 'store']);
