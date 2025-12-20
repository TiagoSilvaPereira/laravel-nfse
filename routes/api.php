<?php

use App\Http\Controllers\Api\CompanyController;
use App\Http\Controllers\Api\InvoiceController;
use App\Http\Controllers\Api\InvoicePdfController;
use App\Http\Controllers\Api\MunicipalParamsController;
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

// Parâmetros Municipais
// Mantidos em português para facilitar o entendimento,
// já que utilizam muitos termos técnicos específicos da NFS-e.
// Além disso, esses endpoints serão mais utilizados para consulta,
// já que os parâmetros devem ser obtidos automaticamente para a emissão
// da NFS-e.
Route::prefix('parametros')->group(function () {
    Route::get('{cityCode}/convenio', [MunicipalParamsController::class, 'getConvention']);
    Route::get('{cityCode}/servico/{serviceCode}/{competencia}/aliquota', [MunicipalParamsController::class, 'getAliquota']);
    Route::get('{cityCode}/servico/{serviceCode}/historicoaliquotas', [MunicipalParamsController::class, 'getHistoricoAliquotas']);
    Route::get('{cityCode}/{numeroBeneficio}/{competencia}/beneficio', [MunicipalParamsController::class, 'getBeneficio']);
    Route::get('{cityCode}/servico/{serviceCode}/{competencia}/regimes-especiais', [MunicipalParamsController::class, 'getRegimesEspeciais']);
    Route::get('{cityCode}/{competencia}/retencoes', [MunicipalParamsController::class, 'getRetencoes']);
});
