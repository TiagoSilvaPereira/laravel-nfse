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

        $signedXml = DB::transaction(function () use ($invoice, $company, $payload) {
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

            return $signedXml;
        });

        // # IMPORTANTE: A transmissão da NFS-e é feita fora da transação
        // # para evitar locks prolongados no banco de dados, que poderiam
        // # impactar a performance e concorrência do sistema. Caso
        // # ocorra uma falha extraordinária, um serviço de reprocessamento
        // # poderá ser implementado para tentar reenviar notas que
        // # ficaram presas em estado PROCESSING.
        $this->transmitAndUpdateStatus($invoice, $signedXml);

        return $invoice->fresh();
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

            $response = $this->transmitter->transmit($signedXml, $invoice->company);
            
            $updateData = [
                'status' => NfseStatus::AUTHORIZED,
                'status_message' => 'Autorizada com sucesso',
                'processing_at' => null,
            ];

            if (!empty($response['chaveAcesso'])) {
                $updateData['access_key'] = $response['chaveAcesso'];
            }

            if (!empty($response['nfseXmlGZipB64'])) {
                $updateData['xml_nfse'] = $response['nfseXmlGZipB64'];
            }

            if (!empty($response['alertas'])) {
                $updateData['alerts'] = $response['alertas'];
            }
            
            $invoice->update($updateData);
        } catch (NfseApiException $e) {
            $invoice->update([
                'status' => NfseStatus::REJECTED,
                'status_message' => $e->getMessage(),
                'processing_at' => null,
            ]);
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
