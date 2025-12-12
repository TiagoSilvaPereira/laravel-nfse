<?php

namespace App\Actions\Nfse;

use App\Enums\NfseStatus;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Nfse\NfseTransmitter;
use App\Services\Nfse\NfseValidator;
use App\Services\Nfse\SignatureService;
use App\Services\Nfse\XmlBuilderService;
use Exception;
use Illuminate\Support\Facades\DB;

class EmitInvoice
{
    public function __construct(
        protected NfseValidator $validator,
        protected XmlBuilderService $xmlBuilder,
        protected SignatureService $signatureService,
        protected NfseTransmitter $transmitter
    ) {}

    /**
     * Executa o processo de emissão da NFS-e.
     *
     * @param Company $company
     * @param array $data Dados da nota (tomador, serviço, valores)
     * @return Invoice
     * @throws Exception
     */
    public function execute(Company $company, array $data): Invoice
    {
        $this->validator->validate($data);

        $integrationId = $data['integration_id'] ?? null;
        $existingInvoice = null;

        if ($integrationId) {
            $existingInvoice = $company->invoices()
                ->where('integration_id', $integrationId)
                ->first();
            
            if ($existingInvoice && !in_array($existingInvoice->status, [NfseStatus::ERROR, NfseStatus::REJECTED])) {
                return $existingInvoice;
            }
        }

        return DB::transaction(function () use ($company, $data, $existingInvoice) {
            if ($existingInvoice) {
                $dpsNumber = $existingInvoice->dps_number;
                $dpsSeries = $existingInvoice->dps_series;
            } else {
                $company->increment('last_dps_number');
                $dpsNumber = $company->last_dps_number;
                $dpsSeries = $company->dps_serie;
            }
            
            $data['nDPS'] = $dpsNumber;
            $data['serie'] = $dpsSeries;

            $xmlContent = $this->xmlBuilder->buildDpsXml($company, $data);
            $dpsId = $this->xmlBuilder->generateDpsId($company, $dpsNumber, $dpsSeries);

            $signedXml = $this->signatureService->sign($xmlContent, $company);

            if ($existingInvoice) {
                $invoice = $existingInvoice;
                $invoice->update([
                    'status' => \App\Enums\NfseStatus::PROCESSING,
                    'xml_dps_signed' => $signedXml,
                    'payload_json' => $data,
                    'status_message' => null,
                ]);
            } else {
                $invoice = Invoice::create([
                    'company_id' => $company->id,
                    'environment' => $company->environment,
                    'integration_id' => $data['integration_id'] ?? null,
                    'dps_id' => $dpsId,
                    'dps_number' => $dpsNumber,
                    'dps_series' => $dpsSeries,
                    'status' => \App\Enums\NfseStatus::PROCESSING,
                    'xml_dps_signed' => $signedXml,
                    'payload_json' => $data,
                ]);
            }

            try {
                $response = $this->transmitter->transmit($signedXml, $company);
                
                $invoice->update([
                    'status' => \App\Enums\NfseStatus::AUTHORIZED,
                    'status_message' => 'Autorizada com sucesso',
                    // 'xml_nfse' => 
                ]);
            } catch (Exception $e) {
                $invoice->update([
                    'status' => \App\Enums\NfseStatus::ERROR,
                    'status_message' => $e->getMessage(),
                ]);
            }

            return $invoice;
        });
    }
}
