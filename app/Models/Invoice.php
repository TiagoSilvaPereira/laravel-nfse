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
        'xml_dps_signed',
        'xml_nfse',
        'danfse_pdf_url',
        'payload_json',
    ];

    protected $casts = [
        'environment' => NfseEnvironment::class,
        'status' => NfseStatus::class,
        'payload_json' => 'array',
        'dps_number' => 'integer',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }
}
