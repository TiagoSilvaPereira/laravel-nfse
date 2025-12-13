<?php

namespace App\Console\Commands\Nfse;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClearSchemasCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'nfse:clear-schemas';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Limpa o cache dos arquivos XSD utilizados na validação da NFS-e';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $cachePath = storage_path('app/schemas_cache');

        if (File::exists($cachePath)) {
            if (File::deleteDirectory($cachePath)) {
                $this->info('Cache de schemas XSD removido com sucesso.');
                $this->line("Caminho removido: $cachePath");
            } else {
                $this->error('Falha ao remover o diretório de cache.');
            }
        } else {
            $this->info('O diretório de cache de schemas não existe ou já foi removido.');
        }
    }
}
