<?php

namespace App\Models;

use App\Enums\NfseEnvironment;
use App\Enums\NfseStatus;
use App\Observers\InvoiceObserver;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[ObservedBy([InvoiceObserver::class])]
class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id',
        'environment',
        'integration_id',
        'dps_id',
        'access_key',
        'dps_number',
        'dps_series',
        'status',
        'status_message',
        'processing_at',
        'xml_dps_signed',
        'xml_nfse',
        'danfse_pdf_url',
        'payload',
    ];

    protected $casts = [
        'environment' => NfseEnvironment::class,
        'status' => NfseStatus::class,
        'payload' => 'array',
        'dps_number' => 'integer',
        'processing_at' => 'datetime',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function resetToDraft(array $payload): void
    {
        $this->update([
            'status' => NfseStatus::DRAFT,
            'status_message' => null,
            'processing_at' => null,
            'xml_dps_signed' => null,
            'xml_nfse' => null,
            'danfse_pdf_url' => null,
            'payload' => $payload,
        ]);
    }
}
