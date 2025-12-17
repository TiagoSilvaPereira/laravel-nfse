<?php

namespace App\Actions\Nfse;

use App\Enums\NfseStatus;
use App\Exceptions\NfseApiException;
use App\Models\Invoice;
use App\Services\Nfse\NfseTransmitter;
use App\Services\Nfse\XmlValidatorService;
use App\Services\Nfse\SignatureService;
use App\Services\Nfse\XmlBuilderService;
use Exception;
use Illuminate\Support\Facades\DB;

/**
 * Realiza a emissão da NFS-e a partir de uma invoice 
 * préviamente cadastrada no banco de dados.
 */
class EmitInvoice
{
    public function __construct(
        protected XmlValidatorService $xmlValidator,
        protected XmlBuilderService $xmlBuilder,
        protected SignatureService $signatureService,
        protected NfseTransmitter $transmitter
    ) {}

    /**
     * @param Invoice $invoice Invoice já criada em estado DRAFT ou PROCESSING
     * @return Invoice
     * @throws Exception
     */
    public function execute(Invoice $invoice): Invoice
    {
        $company = $invoice->company;
        $payload = $invoice->payload;

        $this->xmlBuilder->setCompany($company);
        $this->signatureService->setCompany($company);
        $this->transmitter->setCompany($company);

        return DB::transaction(function () use ($invoice, $company, $payload) {
            $dpsInfo = $this->resolveDpsInfo($invoice, $company);
            
            $payload['nDPS'] = $dpsInfo['number'];
            $payload['serie'] = $dpsInfo['series'];

            $xmlContent = $this->xmlBuilder->buildDpsXml($payload);

            $dpsId = $this->xmlBuilder->generateDpsId($dpsInfo['number'], $dpsInfo['series']);
            
            $signedXml = $this->signatureService->sign($xmlContent, $company);

            $invoice->update([
                'dps_id' => $dpsId,
                'dps_number' => $dpsInfo['number'],
                'dps_series' => $dpsInfo['series'],
                'xml_dps_signed' => $signedXml,
                'status' => NfseStatus::PROCESSING,
            ]);

            $this->transmitAndUpdateStatus($invoice, $signedXml);

            return $invoice->fresh();
        });
    }

    protected function resolveDpsInfo(Invoice $invoice, $company): array
    {
        $dpsHasValidNumber = !empty($invoice->dps_number) && $invoice->dps_number > 0;

        if ($dpsHasValidNumber) {
            return [
                'number' => $invoice->dps_number,
                'series' => $invoice->dps_series,
            ];
        }

        // # IMPORTANTE: Bloqueia a empresa para evitar saltos 
        // # ou duplicações do número da DPS, principalmente em
        // # cenários de alta concorrência.
        $company = $company->lockForUpdate()->find($company->id);
        $company->increment('last_dps_number');

        return [
            'number' => $company->last_dps_number,
            'series' => $company->dps_series,
        ];
    }

    protected function transmitAndUpdateStatus(Invoice $invoice, string $signedXml): void
    {
        try {
            $this->xmlValidator->validate($signedXml);

            $this->transmitter->transmit($signedXml, $invoice->company);
            
            $invoice->update([
                'status' => NfseStatus::AUTHORIZED,
                'status_message' => 'Autorizada com sucesso',
                'processing_at' => null,
                // 'xml_nfse' => ... extrair do retorno quando implementar
            ]);
        } catch (NfseApiException $e) {
            $invoice->update([
                'status' => NfseStatus::REJECTED,
                'status_message' => $e->getMessage(),
                'processing_at' => null,
            ]);
            
            throw $e;
        } catch (Exception $e) {
            $invoice->update([
                'status' => NfseStatus::ERROR,
                'status_message' => $e->getMessage(),
                'processing_at' => null,
            ]);
            
            throw $e;
        }
    }
}
