<?php

namespace App\Actions\Nfse;

use App\Enums\NfseStatus;
use App\Jobs\ProcessNfseEmission;
use App\Models\Company;
use App\Models\Invoice;
use App\Services\Nfse\NfseValidator;
use Illuminate\Support\Facades\DB;

/**
 * Cria e prepara uma nota em rascunho (DRAFT), para
 * emissão posterior através do Job ProcessNfseEmission.
 */
class PrepareInvoice
{
    public function __construct(
        protected NfseValidator $validator
    ) {}

    /**
     * @param Company $company
     * @param array $payload Dados validados da nota
     * @return Invoice
     * @throws \Exception
     */
    public function execute(Company $company, array $payload): Invoice
    {
        $this->validator->validate($payload);

        $integrationId = $payload['integration_id'] ?? null;

        $invoice = $this->handleInvoice($company, $payload, $integrationId);

        if ($invoice->status !== NfseStatus::DRAFT) {
            // # IMPORTANTE: Se a invoice já estiver autorizada,
            // # ou em processamento, não faz sentido enfileirar novamente
            return $invoice;
        }

        ProcessNfseEmission::dispatch($invoice->id);

        return $invoice;
    }

    protected function handleInvoice(Company $company, array $payload, ?string $integrationId): Invoice
    {
        return DB::transaction(function () use ($company, $payload, $integrationId) {
            $existingInvoice = $this->findExistingInvoice($company, $integrationId);

            if ($existingInvoice) {
                return $this->handleExistingInvoice($existingInvoice, $payload);
            }

            return $this->createNewInvoice($company, $payload, $integrationId);
        });
    }

    protected function findExistingInvoice(Company $company, ?string $integrationId): ?Invoice
    {
        if (!$integrationId) {
            return null;
        }

        // # IMPORTANTE: Bloqueia a invoice para impedir que ela seja modificada
        // # por outros serviços concorrentes, evitando que o estado retornado
        // # esteja desatualizado.
        return $company->invoices()
            ->where('integration_id', $integrationId)
            ->lockForUpdate()
            ->first();
    }

    protected function handleExistingInvoice(Invoice $invoice, array $payload): Invoice
    {
        if ($invoice->status === NfseStatus::AUTHORIZED) {
            return $invoice;
        }

        if ($invoice->status === NfseStatus::PROCESSING && $invoice->processing_at) {
            throw new \RuntimeException(
                'A nota está sendo processada no momento. Aguarde a conclusão antes de tentar novamente.'
            );
        }

        $invoice->resetToDraft($payload);

        return $invoice->fresh();
    }

    protected function createNewInvoice(Company $company, array $payload, ?string $integrationId): Invoice
    {
        return Invoice::create([
            'company_id' => $company->id,
            'environment' => $company->environment,
            'integration_id' => $integrationId,
            'status' => NfseStatus::DRAFT,
            'payload_json' => $payload,
            
            // As numerações serão geradas no momento da emissão
            'dps_id' => null,
            'dps_number' => null,
            'dps_series' => null,
        ]);
    }
}
