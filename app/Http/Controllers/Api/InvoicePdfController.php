<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Services\Nfse\NfseClient;
use Symfony\Component\HttpFoundation\Response;

class InvoicePdfController extends Controller
{
    public function show(Invoice $invoice, NfseClient $nfseClient): Response
    {
        if (!$invoice->access_key) {
            abort(404, 'Chave de acesso não encontrada para esta nota fiscal.');
        }

        $nfseClient->setCompany($invoice->company);

        try {
            $pdfContent = $nfseClient->getDanfsePdf($invoice->access_key);

            return response($pdfContent)
                ->header('Content-Type', 'application/pdf')
                ->header('Content-Disposition', 'inline; filename="nfse-' . $invoice->access_key . '.pdf"');
        } catch (\Exception $e) {
            abort(500, 'Erro ao obter o PDF da nota fiscal: ' . $e->getMessage());
        }
    }
}
