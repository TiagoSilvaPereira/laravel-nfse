<?php

use App\Enums\NfseEnvironment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('cnpj', 14)->unique();
            $table->string('municipal_registration', 15)->nullable();
            $table->string('municipality_code', 7);
            
            $table->text('cert_path');
            $table->text('cert_password')->nullable();
            $table->date('cert_expires_at');

            $table->tinyInteger('environment')->default(NfseEnvironment::HOMOLOGATION);
            $table->unsignedInteger('last_dps_number')->default(0);
            $table->string('serie_dps', 5)->default('00001');
            
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
