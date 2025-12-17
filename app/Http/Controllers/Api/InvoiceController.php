<?php

namespace App\Http\Controllers\Api;

use App\Actions\Nfse\PrepareInvoice;
use App\Http\Controllers\Controller;
use App\Http\Requests\StoreInvoiceRequest;
use App\Http\Resources\InvoiceResource;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Nfse\NfseMapper;
use Illuminate\Http\JsonResponse;

class InvoiceController extends Controller
{
    public function store(
        StoreInvoiceRequest $request,
        PrepareInvoice $prepareInvoice,
        NfseMapper $mapper
    ): JsonResponse
    {
        $data = $request->validated();
        
        try {
            $company = Company::findOrFail($data['company_id']);
            $internalData = $mapper->toInternal($data);
            $internalData['integration_id'] = $data['integration_id'] ?? null;

            $invoice = $prepareInvoice->execute($company, $internalData);
            
            return response()->json([
                'message' => 'Nota fiscal enviada para processamento.',
                'data' => [
                    'id' => $invoice->id,
                    'access_key' => $invoice->access_key,
                    'status' => $invoice->status,
                    'status_message' => $invoice->status_message,
                ],
            ], $invoice->wasRecentlyCreated ? 201 : 200);

        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Erro ao processar nota fiscal.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    public function show(Invoice $invoice): InvoiceResource
    {
        return new InvoiceResource($invoice);
    }
}
