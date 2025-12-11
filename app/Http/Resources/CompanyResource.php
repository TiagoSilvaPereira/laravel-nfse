<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CompanyResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'cnpj' => $this->cnpj,
            'municipal_registration' => $this->municipal_registration,
            'municipality_code' => $this->municipality_code,
            'environment' => $this->environment,
            'cert_expires_at' => $this->cert_expires_at,
            'last_dps_number' => $this->last_dps_number,
            'dps_serie' => $this->dps_serie,
            'has_certificate' => !empty($this->cert_path),
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
