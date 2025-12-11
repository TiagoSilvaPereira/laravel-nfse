<?php

namespace App\Http\Controllers\Api;

use App\Actions\Nfse\EmitInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Models\Company;
use App\Services\Nfse\NfseMapper;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function store(
        StoreInvoiceRequest $request, 
        EmitInvoice $emitInvoice,
        NfseMapper $mapper
    ): JsonResponse
    {
        $data = $request->validated();
        
        $company = Company::findOrFail($data['company_id']);

        $internalData = $mapper->toInternal($data);

        try {
            $invoice = $emitInvoice->execute($company, $internalData);
            
            return response()->json([
                'message' => 'Nota fiscal processada com sucesso.',
                'data' => $invoice,
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar nota fiscal.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }
}
