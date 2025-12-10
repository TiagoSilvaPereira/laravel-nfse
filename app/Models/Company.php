<?php

namespace App\Models;

use App\Enums\NfseEnvironment;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Company extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'cnpj',
        'municipal_registration',
        'municipality_code',
        'cert_path',
        'cert_password',
        'cert_expires_at',
        'environment',
        'last_dps_number',
        'serie_dps',
    ];

    protected $casts = [
        'cert_expires_at' => 'date',
        'cert_password' => 'encrypted',
        'environment' => NfseEnvironment::class,
        'last_dps_number' => 'integer',
    ];

    protected $hidden = [
        'cert_password',
    ];

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
