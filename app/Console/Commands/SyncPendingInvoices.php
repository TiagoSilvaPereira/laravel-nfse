<?php

namespace App\Console\Commands;

use App\Enums\NfseStatus;
use App\Models\Invoice;
use App\Services\Nfse\NfseClient;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncPendingInvoices extends Command
{
    protected $signature = 'nfse:sync-pending';
    protected $description = 'Sincroniza notas fiscais pendentes verificando se já foram processadas pela Receita Federal';

    public function handle(NfseClient $client): int
    {
        $oneHourAgo = now()->subHour();

        $pendingInvoices = Invoice::query()
            ->where('status', NfseStatus::PROCESSING)
            ->where('created_at', '<=', $oneHourAgo)
            ->whereNotNull('dps_id')
            ->with('company')
            ->get();

        if ($pendingInvoices->isEmpty()) {
            $this->info('Nenhuma invoice pendente encontrada.');
            return self::SUCCESS;
        }

        $this->info("Encontradas {$pendingInvoices->count()} invoices pendentes para sincronizar.");

        $synchronized = 0;
        $failed = 0;

        foreach ($pendingInvoices as $invoice) {
            try {
                $this->info("Verificando invoice #{$invoice->id} (DPS: {$invoice->dps_id})...");
                
                $client->setCompany($invoice->company);

                if (!$client->checkDpsExists($invoice->dps_id)) {
                    $this->warn("DPS {$invoice->dps_id} ainda não processada pela Receita Federal.");
                    continue;
                }

                $dpsInfo = $client->getDpsInfo($invoice->dps_id);
                
                if (empty($dpsInfo['chaveAcesso'])) {
                    $this->error("Chave de acesso não retornada para DPS {$invoice->dps_id}.");
                    $failed++;
                    continue;
                }

                $nfseData = $client->getNfseByAccessKey($dpsInfo['chaveAcesso']);

                if (empty($nfseData['nfseXmlGZipB64'])) {
                    $this->error("XML da NFSe não retornado para chave {$dpsInfo['chaveAcesso']}.");
                    $failed++;
                    continue;
                }

                $invoice->update([
                    'status' => NfseStatus::AUTHORIZED,
                    'status_message' => 'Sincronizada automaticamente',
                    'access_key' => $dpsInfo['chaveAcesso'],
                    'xml_nfse' => $nfseData['nfseXmlGZipB64'],
                    'processing_at' => null,
                ]);

                $this->info("✓ Invoice #{$invoice->id} sincronizada com sucesso!");

                $synchronized++;

            } catch (\Exception $e) {
                $this->error("Erro ao sincronizar invoice #{$invoice->id}: {$e->getMessage()}");

                Log::error("Erro ao sincronizar invoice #{$invoice->id}", [
                    'invoice_id' => $invoice->id,
                    'dps_id' => $invoice->dps_id,
                    'error' => $e->getMessage(),
                ]);

                $failed++;
            }
        }

        $this->newLine();
        $this->info("Sincronização concluída:");
        $this->info("Sincronizadas: {$synchronized}");
        $this->info("Falhas: {$failed}");
        $this->info("Pendentes: " . ($pendingInvoices->count() - $synchronized - $failed));

        return self::SUCCESS;
    }
}
