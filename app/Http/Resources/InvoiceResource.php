<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InvoiceResource extends JsonResource
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
            'company_id' => $this->company_id,
            'integration_id' => $this->integration_id,
            'dps_id' => $this->dps_id,
            'access_key' => $this->access_key,
            'dps_number' => $this->dps_number,
            'dps_series' => $this->dps_series,
            'status' => $this->status,
            'status_message' => $this->status_message,
            'processing_at' => $this->processing_at,
            'xml_dps_signed' => $this->xml_dps_signed,
            'xml_nfse' => $this->xml_nfse,
            'danfse_pdf_url' => $this->danfse_pdf_url,
            'payload' => $this->payload,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
