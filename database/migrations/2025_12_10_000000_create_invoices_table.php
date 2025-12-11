<?php

use App\Enums\NfseEnvironment;
use App\Enums\NfseStatus;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            
            $table->tinyInteger('environment')->default(NfseEnvironment::HOMOLOGATION);

            // Chave de idempotência - gerada pelo sistema emissor
            $table->string('dps_id', 50)->unique();
            
            // Chave de acesso da NFS-e - gerada pelo sistema da Sefaz
            $table->string('access_key', 50)->nullable(); 
            
            $table->unsignedBigInteger('dps_number');
            $table->string('dps_series', 5);

            // Identificador único da integração (para idempotência do cliente)
            $table->string('integration_id', 48)->nullable()->index();
            
            $table->string('status')->default(NfseStatus::DRAFT)->index(); 
            $table->text('status_message')->nullable();
            
            $table->mediumText('xml_dps_signed')->nullable();
            $table->mediumText('xml_nfse')->nullable();
            $table->string('danfse_pdf_url')->nullable();
            
            $table->json('payload_json');
            
            $table->timestamps();

            // Índice para garantir unicidade da DPS por empresa
            $table->unique(['company_id', 'dps_number', 'dps_series']);
            
            // Índice para garantir unicidade da integração por empresa
            $table->unique(['company_id', 'integration_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};