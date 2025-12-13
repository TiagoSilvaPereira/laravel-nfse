<?php

namespace App\Actions\Nfse;

use App\Enums\NfseStatus;
use App\Exceptions\NfseApiException;
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

        $existingInvoice = $this->findExistingInvoice($company, $data['integration_id'] ?? null);

        if ($this->shouldReturnExisting($existingInvoice)) {
            return $existingInvoice;
        }

        return DB::transaction(fn () => $this->processEmission($company, $data, $existingInvoice));
    }

    protected function findExistingInvoice(Company $company, ?string $integrationId): ?Invoice
    {
        if (!$integrationId) {
            return null;
        }

        return $company->invoices()
            ->where('integration_id', $integrationId)
            ->first();
    }

    protected function shouldReturnExisting(?Invoice $invoice): bool
    {
        if (!$invoice) {
            return false;
        }

        return !in_array($invoice->status, [NfseStatus::ERROR, NfseStatus::REJECTED]);
    }

    protected function processEmission(Company $company, array $data, ?Invoice $existingInvoice): Invoice
    {
        $dpsInfo = $this->resolveDpsInfo($company, $existingInvoice);
        
        $data['nDPS'] = $dpsInfo['number'];
        $data['serie'] = $dpsInfo['series'];

        $xmlContent = $this->xmlBuilder->buildDpsXml($company, $data);

        $dpsId = $this->xmlBuilder->generateDpsId($company, $dpsInfo['number'], $dpsInfo['series']);
        
        $signedXml = $this->signatureService->sign($xmlContent, $company);

        $invoice = $this->persistInvoice($company, $data, $dpsId, $signedXml, $dpsInfo, $existingInvoice);

        $this->transmitAndUpdateStatus($invoice, $company, $signedXml);

        return $invoice;
    }

    protected function resolveDpsInfo(Company $company, ?Invoice $existingInvoice): array
    {
        if ($existingInvoice) {
            return [
                'number' => $existingInvoice->dps_number,
                'series' => $existingInvoice->dps_series,
            ];
        }

        $company->increment('last_dps_number');

        return [
            'number' => $company->last_dps_number,
            'series' => $company->dps_series,
        ];
    }

    protected function persistInvoice(
        Company $company, 
        array $data, 
        string $dpsId, 
        string $signedXml, 
        array $dpsInfo, 
        ?Invoice $existingInvoice
    ): Invoice {
        $attributes = [
            'status' => NfseStatus::PROCESSING,
            'xml_dps_signed' => $signedXml,
            'payload_json' => $data,
            'status_message' => null, // Limpa mensagem de erro anterior se houver
        ];

        if ($existingInvoice) {
            $existingInvoice->update($attributes);
            return $existingInvoice;
        }

        return Invoice::create(array_merge($attributes, [
            'company_id' => $company->id,
            'environment' => $company->environment,
            'integration_id' => $data['integration_id'] ?? null,
            'dps_id' => $dpsId,
            'dps_number' => $dpsInfo['number'],
            'dps_series' => $dpsInfo['series'],
        ]));
    }

    protected function transmitAndUpdateStatus(Invoice $invoice, Company $company, string $signedXml): void
    {
        try {
            $response = $this->transmitter->transmit($signedXml, $company);
            
            $invoice->update([
                'status' => NfseStatus::AUTHORIZED,
                'status_message' => 'Autorizada com sucesso',
                // 'xml_nfse' => ... extrair do retorno
            ]);
        } catch (NfseApiException $e) {
            $invoice->update([
                'status' => NfseStatus::REJECTED,
                'status_message' => $e->getMessage(),
            ]);
        } catch (Exception $e) {
            $invoice->update([
                'status' => NfseStatus::ERROR,
                'status_message' => $e->getMessage(),
            ]);
        }
    }
}
