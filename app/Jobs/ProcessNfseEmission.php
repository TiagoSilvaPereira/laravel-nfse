<?php

namespace App\Jobs;

use App\Actions\Nfse\EmitInvoice;
use App\Enums\NfseStatus;
use App\Exceptions\NfseApiException;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;

/**
 * Job que realiza a emissão da NFS-e
 * 
 * Responsável por:
 * - Marcar a invoice como PROCESSING
 * - Executar a emissão via EmitInvoice
 * - Atualizar status conforme resultado
 * 
 * Obs: Aumentamos o timeout padrão de 60s para 120s, pois
 * a emissão de NFS-e pode demorar mais dependendo da situação
 * da RFB.
 * 
 * Obs2: Implementamos backoff exponencial para tentativas
 * de reprocessamento em caso de falhas temporárias. A primeira
 * tentativa ocorrerá após 30 segundos, a segunda após 1 minuto,
 * a terceira após 2 minutos, e assim por diante, até um máximo
 * de 5 tentativas.
 * 
 * @see App\Actions\Nfse\EmitInvoice
 * @see App\Actions\Nfse\PrepareInvoice
 */
class ProcessNfseEmission implements ShouldQueue
{
    use Queueable;

    public int $tries = 5;
    public int $timeout = 120;
    public array $backoff = [30, 60, 120, 240, 300];

    public function __construct(
        public int $invoiceId
    ) {}

    public function handle(EmitInvoice $emitter): void
    {
        // # IMPORTANTE: Bloqueia a invoice para impedir que ela seja modificada
        // # por outros serviços concorrentes, evitando a alteração incorreta
        // # de estado ou processamento paralelo.
        $invoice = Invoice::query()
            ->lockForUpdate()
            ->find($this->invoiceId);

        if (!$invoice) {
            Log::warning('Invoice não encontrada para emissão', ['id' => $this->invoiceId]);
            return;
        }

        if ($invoice->status === NfseStatus::AUTHORIZED) {
            Log::info('Invoice já autorizada, pulando processamento', ['id' => $invoice->id]);
            return;
        }

        $invoice->update([
            'status' => NfseStatus::PROCESSING,
            'processing_at' => now(),
        ]);

        try {
            $emitter->execute($invoice);
        } catch (\Throwable $e) {
            Log::error('Erro ao emitir NFS-e', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // # IMPORTANTE: Re-lança a exceção para o 
            // # Laravel tentar novamente
            throw $e;
        }
    }

    public function failed(\Throwable $exception): void
    {
        // Apenas uma segurança, teóricamente o NfseApiException
        // já é tratado dentro do serviço EmitInvoice.php e
        // nunca deveria chegar aqui.
        if ($exception instanceof NfseApiException) {
            return;
        }

        $invoice = Invoice::find($this->invoiceId);

        if ($invoice) {
            $invoice->update([
                'status' => NfseStatus::ERROR,
                'status_message' => 'Falha após múltiplas tentativas: ' . $exception->getMessage(),
                'processing_at' => null,
            ]);
        }

        Log::error('Job de emissão de NFS-e falhou definitivamente', [
            'invoice_id' => $this->invoiceId,
            'error' => $exception->getMessage(),
        ]);
    }
}
