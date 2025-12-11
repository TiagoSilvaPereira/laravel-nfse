<?php

namespace App\Actions\Nfse;

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

        return DB::transaction(function () use ($company, $data) {
            $company->increment('last_dps_number');
            $dpsNumber = $company->last_dps_number;
            
            $data['nDPS'] = $dpsNumber;
            $data['serie'] = $company->dps_serie;

            $xmlContent = $this->xmlBuilder->buildDpsXml($company, $data);
            $dpsId = $this->xmlBuilder->generateDpsId($company, $dpsNumber, $company->dps_serie);

            $signedXml = $this->signatureService->sign($xmlContent, $company);

            $invoice = Invoice::create([
                'company_id' => $company->id,
                'environment' => $company->environment,
                'dps_id' => $dpsId,
                'dps_number' => $dpsNumber,
                'dps_series' => $company->dps_serie,
                'status' => \App\Enums\NfseStatus::PROCESSING,
                'xml_dps_signed' => $signedXml,
                'payload_json' => $data,
            ]);

            try {
                $response = $this->transmitter->transmit($signedXml, $company);
                
                $invoice->update([
                    'status' => \App\Enums\NfseStatus::AUTHORIZED,
                    'status_message' => 'Autorizada com sucesso',
                    // 'xml_nfse' => 
                ]);
            } catch (Exception $e) {
                $invoice->update([
                    'status' => \App\Enums\NfseStatus::REJECTED,
                    'status_message' => $e->getMessage(),
                ]);
            }

            return $invoice;
        });
    }
}
